#!/usr/bin/env python3

import sys
import os
import time
import numpy as np
import cv2
sys.path.append(os.path.join(os.environ['BM_DIR'], 'detection'))
import features
import control

MODE = 'listen'


class Model:
    def __init__(self, model_path):
        self.model = cv2.dnn.readNetFromONNX(model_path)

    def forward(self, feature):
        self.model.setInput(feature[np.newaxis, np.newaxis, :, :])
        return self.model.forward().squeeze()


def listen():
    control.register_shutdown_handler()
    control.enter_mode(
        MODE, lambda mode, config, database: listen_with_settings(
            **control.read_settings(mode, config, database)))


def listen_with_settings(interval=5.0, **kwargs):
    mic_id = os.environ['BM_MIC_ID']
    audio_device = 'plug{}'.format(mic_id)

    feature_provider = features.FeatureProvider(
        audio_device,
        features.AudioFeatureExtractor(backend='python_speech_features',
                                       disable_io=True))

    model = Model('test_model.onnx')

    while True:
        start = time.time()
        feature = feature_provider()
        print(time.time() - start)
        print(feature.shape, feature.min(), np.mean(feature), feature.max())
        print(model.forward(feature))
        print(time.time() - start)
        #time.sleep(interval)

    # while True:
    #     time.sleep(0.1)


if __name__ == '__main__':
    # listen()
    listen_with_settings()
