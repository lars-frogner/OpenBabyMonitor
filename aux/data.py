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
from torch.utils.data import dataset
from torchvision.transforms import ToTensor
from sklearn.model_selection import KFold

SRC_DIR = pathlib.Path(os.path.realpath(os.path.dirname(__file__)))

LABELS = ['bad', 'good', 'ambient']
BAD_BABY_SOUNDS = ['Baby cry, infant cry', 'Crying, sobbing', 'Whimper']
GOOD_BABY_SOUNDS = [
    'Babbling', 'Baby laughter', 'Child speech, kid speaking', 'Giggle'
]
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
                    print(len(self),
                          self.segment_info_list[most_common_idx].tags)
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
            for segment in self.find_videos_by_categories(
                    at_least_one_category_from=at_least_one_category_from,
                    only_categories_from=only_categories_from,
                    no_categories_from=no_categories_from,
                    max_video_count=max_video_count,
                    data_groups=data_groups).segment_info_list:
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
                         use_test_dir=False,
                         **kwargs):
        raw_label_data = self.raw_label_file.get_data()
        labels = raw_label_data.keys() if labels == 'all' else labels
        for label in labels:
            audio_dir = self.raw_data_dir / label
            feature_dir = self.feature_data_dir / label
            if use_test_dir:
                audio_dir = audio_dir / 'test'
                feature_dir = feature_dir / 'test'
            feature_dir.mkdir(parents=True, exist_ok=True)

            feature_ids = []
            for video_id in raw_label_data[label]:
                audio_file_path = audio_dir / f'{video_id}.wav'
                waveform = feature_extractor.read_wav(audio_file_path)
                if waveform.size == 0:
                    continue
                for idx, feature in enumerate(
                        feature_extractor(waveform, **kwargs)):
                    feature_id = f'{video_id}_{idx}'
                    np.save(feature_dir / f'{feature_id}.npy',
                            feature,
                            allow_pickle=False,
                            fix_imports=False)
                    feature_ids.append(feature_id)

            self.feature_label_file.update(label, feature_ids)


class AudioDataset(torch_data.Dataset):
    def __init__(self,
                 data_dir,
                 label_file_path,
                 transform=None,
                 target_transform=None):
        self.data_dir = data_dir
        label_data = (label_file_path if isinstance(label_file_path, LabelFile)
                      else LabelFile(label_file_path)).get_data()

        self.transform = transform
        self.target_transform = target_transform

        self.label_names, self.id_lists = zip(*sorted(label_data.items()))
        self.id_list_lengths = list(map(len, self.id_lists))
        self.length = sum(self.id_list_lengths)

        self.cumul_id_list_lengths = np.cumsum(
            np.array(self.id_list_lengths, dtype=int))

    def __len__(self):
        return self.length

    def __idx_to_feature_id_and_label(self, idx):
        label_idx = np.searchsorted(self.cumul_id_list_lengths,
                                    idx,
                                    side='right')
        feature_id = self.id_lists[label_idx][idx - (
            0 if label_idx == 0 else self.cumul_id_list_lengths[label_idx -
                                                                1])]
        return feature_id, label_idx

    def get_n_labels(self):
        return len(self.label_names)

    def get_label_name(self, label):
        return self.label_names[label]

    def get_feature_shape(self):
        return self.__read_feature(
            *self.__idx_to_feature_id_and_label(0)).shape

    def get_n_features_for_label(self, label):
        return self.id_list_lengths[
            self.label_names.index(label) if isinstance(label, str) else label]

    def __read_feature(self, feature_id, label):
        return np.load(self.data_dir / self.get_label_name(label) /
                       f'{feature_id}.npy')

    def __getitem__(self, idx):
        feature_id, label = self.__idx_to_feature_id_and_label(idx)
        feature = self.__read_feature(feature_id, label)

        if self.transform:
            feature = self.transform(feature)
        if self.target_transform:
            label = self.target_transform(label)

        return feature, label


def fetch_raw_data(max_video_count_per_label=2000, labels='all', **kwargs):
    manager = AudioSetDataManager(**kwargs)

    labels = ['bad', 'good', 'ambient'] if labels == 'all' else labels
    if 'bad' in labels:
        manager.create_raw_data_set_for_label(
            'bad',
            at_least_one_category_from=CATEGORIES['bad'],
            only_categories_from=(CATEGORIES['bad'] + CATEGORIES['ambient']),
            max_video_count=max_video_count_per_label,
            data_groups='all')
    if 'good' in labels:
        manager.create_raw_data_set_for_label(
            'good',
            at_least_one_category_from=CATEGORIES['good'],
            only_categories_from=(CATEGORIES['good'] + CATEGORIES['ambient']),
            no_categories_from=manager.get_child_categories('Music'),
            max_video_count=max_video_count_per_label,
            data_groups='all')
    if 'ambient' in labels:
        manager.create_raw_data_set_for_label(
            'ambient',
            only_categories_from=CATEGORIES['ambient'],
            no_categories_from=manager.get_child_categories('Music'),
            max_video_count=max_video_count_per_label,
            data_groups='all')


def compute_features(**kwargs):
    from features import AudioFeatureExtractor
    manager = AudioSetDataManager()
    extractor = AudioFeatureExtractor(**kwargs)
    manager.extract_features(extractor)


def create_dataset():
    manager = AudioSetDataManager()
    dataset = AudioDataset(manager.feature_data_dir,
                           manager.feature_label_file,
                           transform=ToTensor())
    return dataset


def create_dataloaders(test_proportion=0.2,
                       batch_size=32,
                       num_workers=6,
                       seed=42):
    dataset = create_dataset()
    dataset_size = len(dataset)
    test_size = int(test_proportion * dataset_size)
    train_size = dataset_size - test_size
    train_dataset, test_dataset = torch_data.random_split(
        dataset, (train_size, test_size),
        generator=torch.Generator().manual_seed(seed))

    train_dataloader = torch_data.DataLoader(train_dataset,
                                             batch_size=batch_size,
                                             num_workers=num_workers,
                                             shuffle=True)
    test_dataloader = torch_data.DataLoader(test_dataset,
                                            batch_size=batch_size,
                                            num_workers=num_workers,
                                            shuffle=True)

    return dataset, train_dataloader, test_dataloader


def create_kfold_dataloaders(n_splits=5,
                             batch_size=32,
                             num_workers=6,
                             seed=42):
    dataset = create_dataset()
    return dataset, [
        tuple(
            map(
                lambda indices: torch_data.DataLoader(
                    dataset,
                    batch_size=batch_size,
                    num_workers=num_workers,
                    sampler=torch_data.SubsetRandomSampler(
                        indices, generator=torch.Generator().manual_seed(
                            seed))), train_or_test_indices))
        for train_or_test_indices in KFold(
            n_splits=n_splits, shuffle=True, random_state=seed).split(dataset)
    ]


if __name__ == '__main__':
    # fetch_raw_data()
    compute_features()
