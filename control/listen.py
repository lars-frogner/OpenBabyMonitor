#!/usr/bin/env python3

import sys
import os
import time
import pathlib
import json
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


def listen():
    control.register_shutdown_handler()
    control.enter_mode(
        MODE, lambda mode, config, database: listen_with_settings(
            config, **control.read_settings(mode, config, database)))


def listen_with_settings(config,
                         interval=5.0,
                         probability_threshold=85,
                         notify_on_crying=True,
                         notify_on_babbling=False,
                         **kwargs):
    mic_id = os.environ['BM_MIC_ID']
    audio_device = 'plug{}'.format(mic_id)
    control_dir = pathlib.Path(os.environ['BM_DIR']) / 'control'
    comm_dir = control_dir / '.comm'
    standardization_file = control_dir / 'standardization.npz'
    model_file = control_dir / 'test_model.onnx'
    probabilities_file = comm_dir / 'probabilities.json'

    labels = config['inference']['labels']
    label_names = {idx: name for name, idx in labels.items()}

    feature_provider = features.FeatureProvider(
        audio_device,
        features.AudioFeatureExtractor(backend='python_speech_features',
                                       disable_io=True),
        standardization_file=standardization_file)

    model = Model(model_file)

    while True:
        feature = feature_provider()
        probabilities = 10**model.forward(feature)
        probabilities = np.random.rand(probabilities.size)
        probabilities /= np.sum(probabilities)
        write_probabilities(label_names, probabilities_file, probabilities)
        time.sleep(interval)


def write_probabilities(label_names, probabilities_file, probabilities):
    with open(probabilities_file, 'w') as f:
        json.dump(
            {
                label_names[i]: '{:.3f}'.format(p)
                for i, p in enumerate(probabilities)
            }, f)


if __name__ == '__main__':
    listen()
    # listen_with_settings(control.get_config())
