import os
import json

SCRIPT_DIR = os.path.dirname(os.path.realpath(__file__))
CONFIG_PATH = os.path.realpath(
    os.path.join(SCRIPT_DIR, '..', 'config', 'config.json'))


def read_config():
    with open(CONFIG_PATH, 'r') as f:
        config = json.loads(f.read())
    return config
