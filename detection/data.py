from math import ceil
import os
import pathlib
import json
import subprocess
import datetime
import random
import requests
import numpy as np
import torch
import torch.utils.data as torch_data
from torchsampler import ImbalancedDatasetSampler
import sklearn.model_selection
from tqdm import tqdm
from features import AudioFeatureExtractor

SRC_DIR = pathlib.Path(os.path.realpath(os.path.dirname(__file__)))

LABELS = ['bad', 'good', 'ambient']
BAD_BABY_SOUNDS = ['Baby cry, infant cry']
GOOD_BABY_SOUNDS = ['Babbling', 'Baby laughter']
AMBIENT_SOUNDS = [
    'Silence', 'Wind noise (microphone)', 'Rain on surface', 'Conversation',
    'Female speech, woman speaking', 'Male speech, man speaking', 'Whispering',
    'Whistling', 'Cough', 'Sneeze', 'Burping, eructation', 'Hiccup', 'Fart',
    'Hubbub, speech noise, speech babble', 'Dog', 'Cat',
    'Bird vocalization, bird call, bird song', 'Helicopter', 'Idling',
    'Domestic sounds, home sounds', 'Chime', 'Telephone', 'Doorbell',
    'Car alarm', 'Mechanical fan', 'Music', 'Rattle (instrument)'
]
CATEGORIES = {
    label: categories
    for label, categories in zip(
        LABELS, [BAD_BABY_SOUNDS, GOOD_BABY_SOUNDS, AMBIENT_SOUNDS])
}

numpy_to_torch_dtype_dict = {
    bool: torch.bool,
    np.uint8: torch.uint8,
    np.int8: torch.int8,
    np.int16: torch.int16,
    np.int32: torch.int32,
    np.int64: torch.int64,
    np.float16: torch.float16,
    np.float32: torch.float32,
    np.float64: torch.float64,
    np.complex64: torch.complex64,
    np.complex128: torch.complex128
}
torch_to_numpy_dtype_dict = {
    value: key
    for (key, value) in numpy_to_torch_dtype_dict.items()
}


class VideoSegmentInfo:
    def __init__(self, segment_line):
        splitted = segment_line.split(', ')
        self.video_id = splitted[0]
        self.start_time_s = float(splitted[1])
        self.end_time_s = float(splitted[2])
        self.tags = splitted[3][1:-1].split(',')
        self.tag_string = ','.join(self.tags)

    def __str__(self):
        return f'{self.video_id} {self.start_time_s:4g} -> {self.end_time_s:4g} s ({self.tag_string})'

    def all_tags_in(self, tag_list):
        for tag in self.tags:
            if tag not in tag_list:
                return False
        return True

    def any_tag_in(self, tag_list):
        for tag in self.tags:
            if tag in tag_list:
                return True
        return False

    def no_tag_in(self, tag_list):
        return not self.any_tag_in(tag_list)

    def number_of_tags_in(self, tag_list):
        return len(list(filter(lambda tag: tag in tag_list, self.tags)))


class VideoSegmentList:
    def __init__(self,
                 file_path,
                 at_least_one_tag_from=[],
                 only_tags_from=[],
                 no_tags_from=[]):
        self.segment_info_list = []

        if file_path is None:
            return

        with open(file_path, 'r') as f:
            text = f.read()

        for line in text.splitlines():
            if line[0] == '#':
                continue
            info = VideoSegmentInfo(line)
            if len(at_least_one_tag_from) > 0 and not info.any_tag_in(
                    at_least_one_tag_from):
                continue
            if len(only_tags_from) > 0 and not info.all_tags_in(
                    only_tags_from):
                continue
            if len(no_tags_from) > 0 and not info.no_tag_in(no_tags_from):
                continue
            self.segment_info_list.append(info)

    def __len__(self):
        return len(self.segment_info_list)

    def __str__(self):
        return '\n'.join(map(str, self.segment_info_list))

    def merge(self, other):
        self.segment_info_list.extend(other.segment_info_list)

    def shuffle(self):
        random.shuffle(self.segment_info_list)

    def keep_only_first(self, n):
        self.segment_info_list = self.segment_info_list[:min(
            n, len(self.segment_info_list))]

    def compute_tag_counts(self):
        counts = {}
        for segment in self.segment_info_list:
            for tag in segment.tags:
                if tag in counts:
                    counts[tag] += 1
                else:
                    counts[tag] = 1
        return counts

    def get_segments_by_tag(self):
        segments = {}
        for segment in self.segment_info_list:
            for tag in segment.tags:
                if tag in segments:
                    segments[tag].append(segment)
                else:
                    segments[tag] = [segment]
        return segments

    def print_tag_counts(self, counts=None, tag_names=None):
        counts = self.compute_tag_counts() if counts is None else counts
        for tag, count in sorted(counts.items(), key=lambda item: item[1]):
            name = tag if tag_names is None else tag_names[tag]
            print(f'{name}: {count:d}')

    def determine_segment_groups(self):
        groups = {}
        for segment in self.segment_info_list:
            if segment.tag_string in groups:
                groups[segment.tag_string].append(segment)
            else:
                groups[segment.tag_string] = [segment]
        return sorted(groups.values(),
                      key=lambda segments: len(segments),
                      reverse=True)

    def prune_common_segment_groups(self,
                                    levels,
                                    forced_max_len=None,
                                    groups=None):
        groups = self.determine_segment_groups() if groups is None else groups
        levels = min(levels, len(groups) - 1)
        max_len = len(
            groups[levels]) if forced_max_len is None else forced_max_len
        pruned_segments = []
        for i in range(levels + 1):
            pruned_segments.extend(groups[i][:max_len])
        for i in range(levels + 1, len(groups)):
            pruned_segments.extend(groups[i])
        self.segment_info_list = pruned_segments

    def find_level_for_common_group_pruning(self, count, groups=None):
        groups = self.determine_segment_groups() if groups is None else groups

        group_sizes = np.fromiter(map(len, groups), int)
        min_count = len(groups) * group_sizes[-1]

        if count >= min_count:
            if count == min_count:
                level = len(groups) - 1
            else:
                for level in range(1, len(groups)):
                    if count + np.sum(group_sizes[:level] -
                                      group_sizes[level]) > len(self):
                        break
        else:
            level = None

        return level

    def prune_segment_groups(self,
                             min_segments_per_group,
                             extra_segments,
                             groups=None):
        groups = self.determine_segment_groups() if groups is None else groups
        pruned_segments = []
        for group in groups[:extra_segments]:
            pruned_segments.extend(group[:(min_segments_per_group + 1)])
        for group in groups[extra_segments:]:
            pruned_segments.extend(group[:min_segments_per_group])
        self.segment_info_list = pruned_segments

    def prune_common_segments_until_below_count(self, count):
        if count >= len(self):
            return
        groups = self.determine_segment_groups()
        level = self.find_level_for_common_group_pruning(count, groups=groups)
        if level is not None:
            self.prune_common_segment_groups(level, groups=groups)
        else:
            min_segments_per_group = count // len(groups)
            extra_segments = count % len(groups)
            if min_segments_per_group > 0:
                self.prune_segment_groups(min_segments_per_group,
                                          extra_segments,
                                          groups=groups)
            else:
                self.segment_info_list = [group[0] for group in groups]
                tag_counts = self.compute_tag_counts()
                while len(self) > count:
                    group_commonnesses = np.fromiter(
                        (sum((tag_counts[tag] for tag in segment.tags))
                         for segment in self.segment_info_list), int)
                    most_common_idx = np.argmax(group_commonnesses)
                    for tag in self.segment_info_list[most_common_idx].tags:
                        tag_counts[tag] -= 1
                    self.segment_info_list.pop(most_common_idx)


class LabelFile:
    def __init__(self, file_path):
        self.file_path = file_path
        self.data = None

    def get_data(self, force_read=False):
        if not force_read and self.data is not None:
            label_data = self.data
        elif self.file_path.exists():
            with open(self.file_path, 'r') as f:
                label_data = json.load(f)
        else:
            label_data = {}
        return label_data

    def write(self, label_data=None):
        if label_data is None:
            assert self.data is not None
            label_data = self.data
        with open(self.file_path, 'w') as f:
            json.dump(label_data, f)

    def update(self, label, ids, append=True):
        label_data = self.get_data()
        if append and label in label_data:
            label_data[label] = sorted(
                list(set(label_data[label]).union(set(ids))))
        else:
            label_data[label] = sorted(ids)
        self.write(label_data)


class AudioSetDataManager:
    def __init__(self):
        self.download_script = SRC_DIR / 'fetch_youtube_audio.sh'

        self.data_dir = SRC_DIR / 'data'
        self.data_dir.mkdir(exist_ok=True)

        self.raw_label_file = LabelFile(self.data_dir / 'raw_labels.json')
        self.feature_label_file = LabelFile(self.data_dir /
                                            'feature_labels.json')
        self.discarded_feature_label_file = LabelFile(
            self.data_dir / 'discarded_feature_labels.json')

        self.raw_data_dir = self.data_dir / 'raw'
        self.raw_data_dir.mkdir(exist_ok=True)

        self.feature_data_dir = self.data_dir / 'features'
        self.feature_data_dir.mkdir(exist_ok=True)

        self.error_log_file = SRC_DIR / 'error.log'

        self.ontology_file = self.raw_data_dir / 'ontology.json'
        self.balanced_train_segments_file = self.raw_data_dir / 'balanced_train_segments.csv'
        self.unbalanced_train_segments_file = self.raw_data_dir / 'unbalanced_train_segments.csv'
        self.eval_segments_file = self.raw_data_dir / 'eval_segments.csv'

        if not self.ontology_file.exists():
            open(self.ontology_file, 'wb').write(
                requests.get(
                    f'https://raw.githubusercontent.com/audioset/ontology/master/{self.ontology_file.name}',
                    allow_redirects=True).content)
        self.sound_category_tags, self.sound_category_child_tags, self.sound_category_parent_tags, self.sound_categories_by_tag = self.read_sound_category_tags(
        )
        for segments_file in [
                self.balanced_train_segments_file,
                self.unbalanced_train_segments_file, self.eval_segments_file
        ]:
            if not segments_file.exists():
                open(segments_file, 'wb').write(
                    requests.get(
                        f'http://storage.googleapis.com/us_audioset/youtube_corpus/v1/csv/{segments_file.name}',
                        allow_redirects=True).content)

        self.segment_files = dict(
            train_balanced=self.balanced_train_segments_file,
            train_unbalanced=self.unbalanced_train_segments_file,
            eval=self.eval_segments_file)

    def read_sound_category_tags(self):
        with open(self.ontology_file, 'r') as f:
            ontology = json.load(f)

        sound_category_tags = {
            entry['name']: entry['id']
            for entry in ontology
        }

        sound_categories_by_tag = {
            v: k
            for k, v in sound_category_tags.items()
        }

        sound_category_child_tags = {}
        sound_category_parent_tags = {}
        for entry in ontology:
            name = entry['name']
            tag = entry['id']
            child_tags = entry['child_ids']
            sound_category_child_tags[name] = child_tags
            if name not in sound_category_parent_tags:
                sound_category_parent_tags[name] = []
            for child_tag in child_tags:
                child_category = sound_categories_by_tag[child_tag]
                if child_category in sound_category_parent_tags:
                    sound_category_parent_tags[child_category].append(tag)
                else:
                    sound_category_parent_tags[child_category] = [tag]

        return sound_category_tags, sound_category_child_tags, sound_category_parent_tags, sound_categories_by_tag

    def get_child_categories(self, categories):
        all_child_categories = []
        if not isinstance(categories, (list, tuple)):
            categories = [] if categories is None else [categories]
        for name in categories:
            child_tags = self.sound_category_child_tags[name]
            while len(child_tags) > 0:
                child_categories = [
                    self.sound_categories_by_tag[child_tag]
                    for child_tag in child_tags
                ]
                all_child_categories.extend(child_categories)
                child_tags = sum([
                    self.sound_category_child_tags[child_name]
                    for child_name in child_categories
                ], [])
        return list(set(all_child_categories))

    def get_category_tags(self, categories, include_children=False):
        tags = []
        for name in categories:
            tags.append(self.sound_category_tags[name])
            if include_children:
                child_tags = self.sound_category_child_tags[name]
                while len(child_tags) > 0:
                    tags.extend(child_tags)
                    child_tags = sum([
                        self.sound_category_child_tags[
                            self.sound_categories_by_tag[child_tag]]
                        for child_tag in child_tags
                    ], [])
        return list(set(tags))

    def create_raw_data_set_for_label(self,
                                      label,
                                      at_least_one_category_from=None,
                                      only_categories_from=None,
                                      no_categories_from=None,
                                      max_video_count=None,
                                      data_groups='all',
                                      overwrite=False,
                                      append=True):
        video_ids = []
        with open(self.error_log_file, 'a') as error_log:
            for segment in tqdm(
                    self.find_videos_by_categories(
                        at_least_one_category_from=at_least_one_category_from,
                        only_categories_from=only_categories_from,
                        no_categories_from=no_categories_from,
                        max_video_count=max_video_count,
                        data_groups=data_groups).segment_info_list):
                success = self.download_audio(label,
                                              segment.video_id,
                                              segment.start_time_s,
                                              segment.end_time_s,
                                              error_log=error_log,
                                              overwrite=overwrite)
                if success:
                    video_ids.append(segment.video_id)

        self.raw_label_file.update(label, video_ids, append=append)

    def find_videos_by_categories(self,
                                  at_least_one_category_from=None,
                                  only_categories_from=None,
                                  no_categories_from=None,
                                  max_video_count=None,
                                  data_groups='all'):
        def ensure_list(obj):
            if not isinstance(obj, (list, tuple)):
                obj = [] if obj is None else [obj]
            return obj

        at_least_one_category_from = ensure_list(at_least_one_category_from)
        only_categories_from = ensure_list(only_categories_from)
        no_categories_from = ensure_list(no_categories_from)

        at_least_one_tag_from = self.get_category_tags(
            at_least_one_category_from)
        only_tags_from = self.get_category_tags(only_categories_from,
                                                include_children=True)
        no_tags_from = self.get_category_tags(no_categories_from)

        data_groups = self.segment_files.keys(
        ) if data_groups == 'all' else data_groups
        input_files = [self.segment_files[group] for group in data_groups]

        segments = VideoSegmentList(None)
        for input_file in input_files:
            segments.merge(
                VideoSegmentList(input_file,
                                 at_least_one_tag_from=at_least_one_tag_from,
                                 only_tags_from=only_tags_from,
                                 no_tags_from=no_tags_from))

        if max_video_count is not None:
            segments.prune_common_segments_until_below_count(max_video_count)

        return segments

    def download_audio(self,
                       label,
                       video_id,
                       start_time_s,
                       end_time_s,
                       error_log=None,
                       overwrite=False):
        audio_clip_dir = self.raw_data_dir / label
        audio_clip_dir.mkdir(exist_ok=True)
        if not overwrite:
            audio_file = audio_clip_dir / f'{video_id}.wav'
            if audio_file.exists():
                return True

        start_time = str(datetime.timedelta(seconds=start_time_s))
        end_time = str(datetime.timedelta(seconds=end_time_s))

        completed_process = subprocess.run([
            self.download_script, video_id, start_time, end_time,
            audio_clip_dir
        ],
                                           stderr=error_log)
        return completed_process.returncode == 0

    def extract_features(self,
                         feature_extractor,
                         labels='all',
                         allow_all_energies_for_labels=[],
                         adjust_overlaps_to_balance_feature_counts=True,
                         drop_overrepresented_features=True,
                         save_audio=True,
                         save_discarded=True,
                         **kwargs):
        raw_label_data = self.raw_label_file.get_data()
        labels = list(raw_label_data.keys()) if labels == 'all' else labels
        n_videos = np.array([len(raw_label_data[label]) for label in labels])
        sort_indices = np.argsort(n_videos)
        n_videos = n_videos[sort_indices]
        labels = [labels[i] for i in sort_indices]

        if adjust_overlaps_to_balance_feature_counts:
            overlaps = 1 - (1 - feature_extractor.feature_overlap_fraction
                            ) * n_videos / n_videos[-1]
        else:
            overlaps = [feature_extractor.feature_overlap_fraction
                        ] * len(labels)

        target_feature_num = None

        for label_idx, label in enumerate(labels):
            audio_dir = self.raw_data_dir / label
            feature_dir = self.feature_data_dir / label
            feature_dir.mkdir(parents=True, exist_ok=True)

            allow_all_energies = label in allow_all_energies_for_labels

            if save_discarded and not allow_all_energies:
                discarded_feature_dir = feature_dir / 'discarded'
                discarded_feature_dir.mkdir(parents=True, exist_ok=True)

            if save_audio:
                feature_audio_dir = feature_dir / 'audio'
                feature_audio_dir.mkdir(parents=True, exist_ok=True)

                if save_discarded and not allow_all_energies:
                    discarded_feature_audio_dir = discarded_feature_dir / 'audio'
                    discarded_feature_audio_dir.mkdir(parents=True,
                                                      exist_ok=True)

            print(label, overlaps[label_idx])
            feature_extractor.set_feature_overlap_fraction(overlaps[label_idx])

            feature_ids = []
            discarded_feature_ids = []

            for video_id in tqdm(raw_label_data[label]):
                audio_file_path = audio_dir / f'{video_id}.wav'
                waveform = feature_extractor.read_wav(audio_file_path)
                if not feature_extractor.can_extract_feature(waveform):
                    continue
                for idx, result in enumerate(
                        zip(*feature_extractor(
                            waveform,
                            allow_all_energies=allow_all_energies,
                            return_waveforms=save_audio,
                            **kwargs))):

                    feature_id = f'{video_id}_{idx}'
                    filename = f'{feature_id}.npy'

                    if save_audio:
                        audio_filename = f'{feature_id}.wav'

                        if allow_all_energies:
                            feature, waveform = result
                            accepted = True
                        else:
                            accepted, feature, waveform = result

                        if accepted:
                            feature_extractor.write_wav(
                                feature_audio_dir / audio_filename, waveform)
                        elif save_discarded:
                            feature_extractor.write_wav(
                                discarded_feature_audio_dir / audio_filename,
                                waveform)
                    else:
                        if allow_all_energies:
                            feature, = result
                            accepted = True
                        else:
                            accepted, feature = result

                    if accepted:
                        np.save(feature_dir / filename,
                                feature,
                                allow_pickle=False,
                                fix_imports=False)
                        feature_ids.append(feature_id)
                    elif save_discarded:
                        np.save(discarded_feature_dir / filename,
                                feature,
                                allow_pickle=False,
                                fix_imports=False)
                        discarded_feature_ids.append(feature_id)

            if drop_overrepresented_features:
                if target_feature_num is None:
                    target_feature_num = len(feature_ids)
                else:
                    random.shuffle(feature_ids)
                    feature_ids = feature_ids[:target_feature_num]

            self.feature_label_file.update(label, feature_ids)

            if save_discarded and not allow_all_energies:
                self.discarded_feature_label_file.update(
                    label, discarded_feature_ids)


class AudioDataset(torch_data.Dataset):
    def __init__(self,
                 data_dir,
                 label_file_path,
                 standardize=True,
                 apply_transforms=True,
                 device='cpu',
                 caching='initial',
                 move_all_to_device=True,
                 subset_proportion=None):
        self.data_dir = data_dir
        self.standardize = standardize
        self.apply_transforms = apply_transforms
        self.device = device
        self.caching = caching
        self.move_all_to_device = move_all_to_device

        label_data = (label_file_path if isinstance(label_file_path, LabelFile)
                      else LabelFile(label_file_path)).get_data()

        self.label_names, self.id_lists = zip(*sorted(label_data.items()))
        if subset_proportion is not None:
            self.id_lists = [
                id_list[:min(len(id_list
                                 ), ceil(subset_proportion * len(id_list)))]
                for id_list in self.id_lists
            ]
        self.id_list_lengths = list(map(len, self.id_lists))
        self.length = sum(self.id_list_lengths)

        self.cumul_id_list_lengths = np.cumsum(
            np.array(self.id_list_lengths, dtype=int))

        if self.caching == 'initial' or self.standardize:
            self._read_all_data()
            if self.standardize:
                self._standardize()
        else:
            self.all_loaded = False

    def __len__(self):
        return self.length

    def _read_all_data(self):
        if self.length == 0:
            return
        feature, label = self._read_data(0)
        feature = torch.from_numpy(feature[np.newaxis, :, :])
        self.features = torch.empty((self.length, *feature.shape),
                                    dtype=feature.dtype)
        self.features[0, ...] = feature
        self.labels = torch.empty(self.length, dtype=int)
        self.labels[0] = label
        for idx in tqdm(range(1, self.length)):
            feature, label = self._read_data(idx)
            self.features[idx,
                          ...] = torch.from_numpy(feature[np.newaxis, :, :])
            self.labels[idx] = label

        if self.move_all_to_device and self.device != 'cpu':
            self.features = self.features.to(self.device)
            self.labels = self.labels.to(self.device)

        self.all_loaded = True

    def _standardize(self):
        if not self.all_loaded:
            self._read_all_data()

        self.standard_deviation, self.mean = torch.std_mean(
            self.features, True)

        self.features -= self.mean
        self.features /= self.standard_deviation

        np.savez(self.data_dir / 'standardization.npz',
                 mean=self.mean.cpu().numpy(),
                 standard_deviation=self.standard_deviation.cpu().numpy())

    def _idx_to_feature_id_and_label(self, idx):
        label_idx = np.searchsorted(self.cumul_id_list_lengths,
                                    idx,
                                    side='right')
        feature_id = self.id_lists[label_idx][idx - (
            0 if label_idx == 0 else self.cumul_id_list_lengths[label_idx -
                                                                1])]
        return feature_id, label_idx

    def get_label_names(self):
        return self.label_names

    def get_n_labels(self):
        return len(self.label_names)

    def get_label_name(self, label):
        return self.label_names[label]

    def get_feature_shape(self):
        if self.all_loaded:
            return self.features.shape[1:]
        else:
            return self.__read_feature(
                *self._idx_to_feature_id_and_label(0)).shape

    def get_all_labels(self):
        assert self.all_loaded
        return self.labels.cpu()

    def get_n_features_for_label(self, label):
        return self.id_list_lengths[
            self.label_names.index(label) if isinstance(label, str) else label]

    def get_imbalanced_dataset_sampler(self, indices=None, **kwargs):
        if indices is None:
            callback_get_label = lambda dataset: dataset.get_all_labels()
        else:
            callback_get_label = lambda dataset: dataset.get_all_labels()[
                indices]
        return ImbalancedDatasetSampler(self,
                                        indices=indices,
                                        callback_get_label=callback_get_label,
                                        **kwargs)

    def _read_feature(self, feature_id, label):
        return np.load(self.data_dir / self.get_label_name(label) /
                       f'{feature_id}.npy')

    def _read_data(self, idx):
        feature_id, label = self._idx_to_feature_id_and_label(idx)
        feature = self._read_feature(feature_id, label)
        return feature, label

    def _apply_transforms(self, feature, label):
        if self.all_loaded:
            if not self.move_all_to_device:
                feature, label = feature.to(self.device), label.to(self.device)
        else:
            feature = torch.from_numpy(feature[np.newaxis, :, :]).to(
                self.device)
            label = torch.Tensor([label]).to(int).to(self.device)
        return feature, label

    def __getitem__(self, idx):
        assert idx < self.length
        if self.all_loaded:
            feature, label = self.features[idx], self.labels[idx]
        else:
            feature, label = self._read_data(idx)
        return self._apply_transforms(
            feature, label) if self.apply_transforms else (feature, label)


class DiscardedAudioDataset(AudioDataset):
    def __init__(self, *args, caching=None, **kwargs):
        super().__init__(*args, caching=caching, **kwargs)

    def _read_feature(self, feature_id, label):
        return np.load(self.data_dir / self.get_label_name(label) /
                       'discarded' / f'{feature_id}.npy')


class AudioListenDataset(AudioDataset):
    def __init__(self, *args, apply_transforms=False, caching=None, **kwargs):
        super().__init__(*args,
                         apply_transforms=apply_transforms,
                         caching=caching,
                         **kwargs)

    def _read_feature(self, feature_id, label):
        return self.data_dir / self.get_label_name(
            label) / 'audio' / f'{feature_id}.wav'


class DiscardedAudioListenDataset(AudioDataset):
    def __init__(self, *args, apply_transforms=False, caching=None, **kwargs):
        super().__init__(*args,
                         apply_transforms=apply_transforms,
                         caching=caching,
                         **kwargs)

    def _read_feature(self, feature_id, label):
        return self.data_dir / self.get_label_name(
            label) / 'discarded' / 'audio' / f'{feature_id}.wav'


def fetch_raw_data(max_video_count_per_label=4000, labels='all', **kwargs):
    manager = AudioSetDataManager(**kwargs)

    labels = ['bad', 'good', 'ambient'] if labels == 'all' else labels
    if 'bad' in labels:
        manager.create_raw_data_set_for_label(
            'bad',
            at_least_one_category_from=CATEGORIES['bad'],
            max_video_count=max_video_count_per_label,
            data_groups='all')
    if 'good' in labels:
        manager.create_raw_data_set_for_label(
            'good',
            at_least_one_category_from=CATEGORIES['good'],
            max_video_count=max_video_count_per_label,
            data_groups='all')
    if 'ambient' in labels:
        manager.create_raw_data_set_for_label(
            'ambient',
            only_categories_from=CATEGORIES['ambient'],
            no_categories_from=manager.get_child_categories('Music'),
            max_video_count=max_video_count_per_label,
            data_groups='all')


def compute_features():
    manager = AudioSetDataManager()
    extractor = AudioFeatureExtractor(feature_window_count=64)
    manager.extract_features(extractor,
                             allow_all_energies_for_labels=['ambient'])


def create_dataset(**kwargs):
    manager = AudioSetDataManager()
    dataset = AudioDataset(manager.feature_data_dir,
                           manager.feature_label_file, **kwargs)
    return dataset


def create_discarded_dataset():
    manager = AudioSetDataManager()
    dataset = DiscardedAudioDataset(manager.feature_data_dir,
                                    manager.discarded_feature_label_file)
    return dataset


def create_listen_dataset():
    manager = AudioSetDataManager()
    dataset = AudioListenDataset(manager.feature_data_dir,
                                 manager.feature_label_file)
    return dataset


def create_discarded_listen_dataset():
    manager = AudioSetDataManager()
    dataset = DiscardedAudioListenDataset(manager.feature_data_dir,
                                          manager.discarded_feature_label_file)
    return dataset


def listen_to_dataset(listen_dataset, n=10, shuffle=True, only_labels=None):
    from simpleaudio import WaveObject
    idx = 0
    for audio_file, label in torch_data.DataLoader(
            listen_dataset,
            batch_size=1,
            shuffle=shuffle,
            collate_fn=lambda results: results[0]):
        if idx == n:
            break
        label_name = listen_dataset.get_label_name(label)
        if only_labels is not None and label_name not in only_labels:
            continue
        with open(audio_file, 'rb') as f:
            wave_obj = WaveObject.from_wave_file(f)
        print(label_name)
        play_obj = wave_obj.play()
        play_obj.wait_done()
        idx += 1


def visualize_dataset(extractor,
                      dataset,
                      n=10,
                      shuffle=True,
                      only_labels=None):
    features = []
    for feature, label in torch_data.DataLoader(
            dataset,
            batch_size=1,
            shuffle=shuffle,
            collate_fn=lambda results: results[0]):
        if len(features) == n:
            break
        label_name = dataset.get_label_name(label)
        if only_labels is not None and label_name not in only_labels:
            continue
        features.append(feature.numpy().squeeze())

    extractor.plot_cepstrum(features)


def visualize_clip(manager, extractor, label_name, video_id):
    data_path = manager.feature_data_dir / label_name
    features = []
    while True:
        file_path = data_path / f'{video_id}_{len(features)}.npy'
        if file_path.exists():
            features.append(np.load(file_path))
        else:
            break
    extractor.plot_cepstrum(features)


def create_dataloaders(dataset,
                       test_proportion=0.2,
                       batch_size=32,
                       num_workers=0,
                       seed=42,
                       pin_memory=False,
                       use_imbalanced_sampler=False):
    dataset_size = len(dataset)
    all_indices = np.arange(dataset_size)
    train_indices, test_indices = sklearn.model_selection.train_test_split(
        all_indices,
        test_size=test_proportion,
        random_state=seed,
        shuffle=True)

    generator = torch.Generator().manual_seed(seed)
    if use_imbalanced_sampler:
        train_sampler = dataset.get_imbalanced_dataset_sampler(
            indices=train_indices)
        test_sampler = dataset.get_imbalanced_dataset_sampler(
            indices=test_indices)
    else:
        train_sampler = torch_data.SubsetRandomSampler(train_indices,
                                                       generator=generator)
        test_sampler = torch_data.SubsetRandomSampler(test_indices,
                                                      generator=generator)

    train_dataloader = torch_data.DataLoader(dataset,
                                             batch_size=batch_size,
                                             num_workers=num_workers,
                                             pin_memory=pin_memory,
                                             generator=generator,
                                             sampler=train_sampler)
    test_dataloader = torch_data.DataLoader(dataset,
                                            batch_size=batch_size,
                                            num_workers=num_workers,
                                            pin_memory=pin_memory,
                                            generator=generator,
                                            sampler=test_sampler)

    return train_dataloader, test_dataloader


def create_kfold_dataloaders(dataset,
                             n_splits=5,
                             batch_size=32,
                             num_workers=0,
                             seed=42,
                             pin_memory=False):
    return [
        tuple(
            map(
                lambda indices: torch_data.DataLoader(
                    dataset,
                    batch_size=batch_size,
                    num_workers=num_workers,
                    pin_memory=pin_memory,
                    generator=torch.Generator().manual_seed(seed),
                    sampler=dataset.get_sampler(indices=indices)),
                train_or_test_indices))
        for train_or_test_indices in sklearn.model_selection.KFold(
            n_splits=n_splits, shuffle=True, random_state=seed).split(dataset)
    ]


if __name__ == '__main__':
    # fetch_raw_data()
    compute_features()
