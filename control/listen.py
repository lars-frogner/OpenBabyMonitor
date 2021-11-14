#!/usr/bin/env python3

import sys
import os
import time
import pathlib
import json
import collections
import multiprocessing
import numpy as np
import cv2
sys.path.append(os.path.join(os.environ['BM_DIR'], 'detection'))
import features
import control
from audiostream import update_gain

MODE = 'listen'


class Model:
    def __init__(self, model_path):
        self.model = cv2.dnn.readNetFromONNX(str(model_path))

    def forward(self, feature):
        self.model.setInput(feature[np.newaxis, np.newaxis, :, :])
        return self.model.forward().squeeze()


class SoundLevelNotifier:
    def __init__(self,
                 min_sound_level=-30,
                 fraction_threshold=60,
                 consecutive_recordings=5,
                 min_notification_interval=180):
        self.min_sound_level = min_sound_level
        self.fraction_threshold = fraction_threshold * 1e-2
        self.consecutive_recordings = consecutive_recordings
        self.min_notification_interval = min_notification_interval

        self.prediction_history = collections.deque(
            maxlen=self.consecutive_recordings)

        self.last_notification_time = -np.inf

    def add_sound_level_measurement(self, sound_level):
        self.prediction_history.append(sound_level >= self.min_sound_level)

    def compute_fraction_in_prediction_history(self):
        return self.prediction_history.count(True) / len(
            self.prediction_history)

    def notification_allowed(self):
        return time.time(
        ) - self.last_notification_time > self.min_notification_interval

    def detect_notification(self):
        if not self.notification_allowed() or len(
                self.prediction_history
        ) < self.consecutive_recordings or self.compute_fraction_in_prediction_history(
        ) < self.fraction_threshold:
            return None
        else:
            return 'sound'


class InferenceNotifier:
    def __init__(self,
                 labels,
                 fraction_threshold=60,
                 consecutive_recordings=5,
                 probability_threshold=85,
                 min_notification_interval=180,
                 notify_on_crying=True,
                 notify_and_or='or',
                 notify_on_babbling=False):
        self.labels = labels
        self.label_names = {idx: name for name, idx in labels.items()}
        self.fraction_threshold = fraction_threshold * 1e-2
        self.consecutive_recordings = consecutive_recordings
        self.probability_threshold = probability_threshold * 1e-2
        self.min_notification_interval = min_notification_interval
        self.notify_on = {'bad': notify_on_crying, 'good': notify_on_babbling}
        self.notify_on_any = notify_on_crying and notify_on_babbling
        self.notify_and_or = notify_and_or

        self.prediction_history = collections.deque(
            maxlen=self.consecutive_recordings)

        self.last_notification_time = -np.inf

    def add_prediction(self, probabilities):
        best_label = np.argmax(probabilities)
        if best_label != self.labels['ambient'] and probabilities[
                best_label] >= self.probability_threshold:
            predicted_label = best_label
        elif 1 - probabilities[
                self.labels['ambient']] >= self.probability_threshold:
            predicted_label = set((self.labels['bad'], self.labels['good']))
        else:
            predicted_label = self.labels['ambient']

        self.prediction_history.append(predicted_label)

    def compute_label_fraction_in_prediction_history(self, target_label):
        if isinstance(target_label, (tuple, list, set)):
            target_label = set(target_label)
            count = 0
            for label in self.prediction_history:
                if isinstance(label, set):
                    if label == target_label:
                        count += 1
                else:
                    if label in target_label:
                        count += 1
            return count / len(self.prediction_history)
        else:
            return self.prediction_history.count(target_label) / len(
                self.prediction_history)

    def notification_allowed(self):
        return time.time(
        ) - self.last_notification_time > self.min_notification_interval

    def detect_notification(self):
        if not self.notification_allowed() or len(
                self.prediction_history) < self.consecutive_recordings:
            return None

        detected_label = None
        detected_labels = []
        for label_name in ['bad', 'good']:
            if self.notify_on[
                    label_name] and self.compute_label_fraction_in_prediction_history(
                        self.labels[label_name]) >= self.fraction_threshold:
                detected_labels.append(label_name)

        if len(detected_labels) == 1:
            detected_label = detected_labels[0]
        elif len(detected_labels) > 1:
            detected_label = 'bad_and_good'
        elif self.notify_on_any and self.notify_and_or == 'or' and self.compute_label_fraction_in_prediction_history(
            (self.labels['bad'], self.labels['good'])):
            detected_label = 'bad_or_good'

        if detected_label is not None:
            self.last_notification_time = time.time()

        return detected_label


class QueueWorker:
    def __init__(self, target, *args):
        self.task_queue = multiprocessing.Queue(1)
        self.process = multiprocessing.Process(target=target,
                                               args=(self.task_queue, ) + args,
                                               daemon=True)

    def __enter__(self):
        self.process.start()
        return self.task_queue

    def __exit__(self, *args):
        self.stop()
        self.close_queue()

    def stop(self):
        if self.process.is_alive():
            self.process.terminate()
            self.process.join()

    def close_queue(self):
        if not self.task_queue.empty():
            # Empty the queue before closing to prevent feeder thread from crashing
            self.task_queue.get()
        self.task_queue.close()
        self.task_queue.join_thread()


def listen():
    control.enter_mode(
        MODE, lambda mode, config, database: listen_with_settings(
            config, **control.read_settings(mode, config, database)))


def listen_with_settings(*args, model='large_network', **kwargs):
    if model == 'sound_level_threshold':
        listen_with_settings_sound_level_threshold(*args, **kwargs)
    else:
        listen_with_settings_network(*args, model=model, **kwargs)


def listen_with_settings_sound_level_threshold(config,
                                               interval=5.0,
                                               min_sound_level=-30,
                                               fraction_threshold=60,
                                               consecutive_recordings=5,
                                               min_notification_interval=180,
                                               **kwargs):
    control.register_shutdown_handler()

    control_dir = pathlib.Path(os.environ['BM_DIR']) / 'control'
    comm_dir = control_dir / '.comm'
    sound_level_file = comm_dir / 'sound_level.dat'
    notification_file = comm_dir / 'notification.txt'

    update_gain(os.environ['BM_SOUND_CARD_NUMBER'], 100,
                os.environ['BM_SERVER_LOG_PATH'])

    recorder = features.Recorder(get_audio_device())

    feature_extractor = create_feature_extractor(config)

    notifier = SoundLevelNotifier(
        min_sound_level=min_sound_level,
        fraction_threshold=fraction_threshold,
        consecutive_recordings=consecutive_recordings,
        min_notification_interval=min_notification_interval)

    while True:
        last_record_time = time.time()
        _, sound_level = recorder.record_waveform(
            feature_extractor.feature_length)

        notifier.add_sound_level_measurement(sound_level)
        notification = notifier.detect_notification()

        write_sound_level(sound_level_file, sound_level)

        if notification is not None:
            write_notification(notification_file, notification)

        time.sleep(max(0, interval - (time.time() - last_record_time)))


def listen_with_settings_network(config,
                                 amplification=10,
                                 interval=5.0,
                                 min_sound_level=-30,
                                 model='large_network',
                                 fraction_threshold=60,
                                 consecutive_recordings=5,
                                 probability_threshold=85,
                                 min_notification_interval=180,
                                 notify_on_crying=True,
                                 notify_and_or='or',
                                 notify_on_babbling=False,
                                 **kwargs):
    control_dir = pathlib.Path(os.environ['BM_DIR']) / 'control'

    worker = QueueWorker(
        process_features, config, control_dir, model,
        dict(fraction_threshold=fraction_threshold,
             consecutive_recordings=consecutive_recordings,
             probability_threshold=probability_threshold,
             min_notification_interval=min_notification_interval,
             notify_on_crying=notify_on_crying,
             notify_and_or=notify_and_or,
             notify_on_babbling=notify_on_babbling))

    with worker as task_queue:
        # Register shutdown handlers after starting worker to
        # prevent it from inheriting the handlers
        control.register_shutdown_handler()

        update_gain(os.environ['BM_SOUND_CARD_NUMBER'], 100,
                    os.environ['BM_SERVER_LOG_PATH'])

        feature_provider = create_feature_provider(config, control_dir,
                                                   min_sound_level,
                                                   amplification)

        while True:
            last_record_time = time.time()
            feature = feature_provider()
            task_queue.put(feature)
            time.sleep(max(0, interval - (time.time() - last_record_time)))


def process_features(task_queue, config, control_dir, model,
                     notifier_settings):

    notifier = create_inference_notifier(config, **notifier_settings)

    comm_dir = control_dir / '.comm'
    probabilities_file = comm_dir / 'probabilities.json'
    notification_file = comm_dir / 'notification.txt'

    model_file = control_dir / config['inference']['models'][model]

    model = create_model(model_file)

    ambient_probabilities = np.array([1, 0, 0])

    while True:
        feature = task_queue.get()

        if feature is None:
            probabilities = ambient_probabilities
        else:
            probabilities = 10**model.forward(feature)
            probabilities /= np.sum(probabilities)

        notifier.add_prediction(probabilities)
        notification = notifier.detect_notification()

        write_probabilities(notifier.label_names, probabilities_file,
                            probabilities)

        if notification is not None:
            write_notification(notification_file, notification)


def set_mic_gain_to_max():
    update_gain(os.environ['BM_SOUND_CARD_NUMBER'], 100,
                os.environ['BM_SERVER_LOG_PATH'])


def create_model(model_file):
    model = Model(model_file)
    return model


def get_audio_device():
    mic_id = os.environ['BM_MIC_ID']
    audio_device = 'plug{}'.format(mic_id)
    return audio_device


def create_feature_extractor(config):
    feature_shape = config['inference']['input_shape']
    return features.AudioFeatureExtractor(
        n_mel_bands=feature_shape[0],
        feature_window_count=feature_shape[1],
        backend='python_speech_features',
        disable_io=True)


def create_inference_notifier(config, **notifier_settings):
    labels = config['inference']['labels']
    notifier = InferenceNotifier(labels, **notifier_settings)
    return notifier


def create_feature_provider(config, control_dir, min_sound_level,
                            amplification):
    standardization_file = control_dir / 'standardization.npz'
    return features.FeatureProvider(get_audio_device(),
                                    create_feature_extractor(config),
                                    min_sound_level=min_sound_level,
                                    amplification=amplification,
                                    standardization_file=standardization_file)


def write_sound_level(sound_level_file, sound_level):
    with open(sound_level_file, 'w') as f:
        f.write(f'{sound_level:.2f}')


def write_probabilities(label_names, probabilities_file, probabilities):
    with open(probabilities_file, 'w') as f:
        json.dump(
            {
                label_names[i]: '{:.3f}'.format(p)
                for i, p in enumerate(probabilities)
            }, f)


def write_notification(notification_file, notification):
    with open(notification_file, 'w') as f:
        f.write(notification)


if __name__ == '__main__':
    listen()
    # listen_with_settings(control.get_config(), interval=0, min_energy=0)
