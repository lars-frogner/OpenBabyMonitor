#!/usr/bin/env python3

import sys
import os
import time
import pathlib
import json
import collections
import numpy as np
import cv2
sys.path.append(os.path.join(os.environ['BM_DIR'], 'detection'))
import features
import control

MODE = 'listen'


class Model:
    def __init__(self, model_path):
        self.model = cv2.dnn.readNetFromONNX(str(model_path))

    def forward(self, feature):
        self.model.setInput(feature[np.newaxis, np.newaxis, :, :])
        return self.model.forward().squeeze()


class Notifier:
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
        self.fraction_threshold = fraction_threshold * 1e-2
        self.consecutive_recordings = consecutive_recordings
        self.probability_threshold = probability_threshold * 1e-2
        self.min_notification_interval = min_notification_interval
        self.notify_on = {'bad': notify_on_crying, 'good': notify_on_babbling}
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
        elif self.notify_and_or == 'or' and self.compute_label_fraction_in_prediction_history(
            (self.labels['bad'], self.labels['good'])):
            detected_label = 'bad_or_good'

        if detected_label is not None:
            self.last_notification_time = time.time()

        return detected_label


def listen():
    control.register_shutdown_handler()
    control.enter_mode(
        MODE, lambda mode, config, database: listen_with_settings(
            config, **control.read_settings(mode, config, database)))


def listen_with_settings(config,
                         interval=5.0,
                         fraction_threshold=60,
                         consecutive_recordings=5,
                         probability_threshold=85,
                         min_notification_interval=180,
                         notify_on_crying=True,
                         notify_and_or='or',
                         notify_on_babbling=False,
                         **kwargs):
    mic_id = os.environ['BM_MIC_ID']
    audio_device = 'plug{}'.format(mic_id)
    control_dir = pathlib.Path(os.environ['BM_DIR']) / 'control'
    comm_dir = control_dir / '.comm'
    standardization_file = control_dir / 'standardization.npz'
    model_file = control_dir / 'crynet.onnx'
    probabilities_file = comm_dir / 'probabilities.json'
    notification_file = comm_dir / 'notification.txt'

    labels = config['inference']['labels']
    label_names = {idx: name for name, idx in labels.items()}

    feature_provider = features.FeatureProvider(
        audio_device,
        features.AudioFeatureExtractor(backend='python_speech_features',
                                       disable_io=True),
        standardization_file=standardization_file)

    model = Model(model_file)

    notifier = Notifier(labels,
                        fraction_threshold=fraction_threshold,
                        consecutive_recordings=consecutive_recordings,
                        probability_threshold=probability_threshold,
                        min_notification_interval=min_notification_interval,
                        notify_on_crying=notify_on_crying,
                        notify_and_or=notify_and_or,
                        notify_on_babbling=notify_on_babbling)

    while True:
        feature = feature_provider()

        probabilities = 10**model.forward(feature)
        probabilities /= np.sum(probabilities)

        notifier.add_prediction(probabilities)
        notification = notifier.detect_notification()

        write_probabilities(label_names, probabilities_file, probabilities)

        if notification is not None:
            write_notification(notification_file, notification)

        time.sleep(interval)


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
    # listen_with_settings(control.get_config())
