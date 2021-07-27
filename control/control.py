import sys
import six
import signal
import config
from database import Database

DEFAULT_MODE = 'standby'


def get_config():
    return config.read_config()


def get_database(config):
    return Database.from_config(config)


def read_settings(mode, config=None, database=None):
    if config is None:
        config = get_config()

    table_name = mode + '_settings'
    if table_name not in config['database']['tables']:
        return {}

    settings = list(config['database']['tables'][table_name]['types'].keys())
    settings.remove('id')

    if database is None:
        database = get_database(config)
    values = database.read_values_from_table(table_name, settings)

    return dict(zip(settings, values))


def update_mode_in_database(mode, config=None, database=None):
    if config is None:
        config = get_config()
    if database is None:
        database = get_database(config)
    database.update_values_in_table(
        'modes', dict(id=0, current=config['modes'][mode]['value']))


def handle_shutdown(*args):
    update_mode_in_database(DEFAULT_MODE)
    sys.exit(0)


def register_shutdown_handler():
    signal.signal(signal.SIGTERM, handle_shutdown)
    signal.signal(signal.SIGINT, handle_shutdown)


def enter_mode(mode, run_mode):
    config = get_config()
    database = get_database(config)
    settings = read_settings(mode, config=config, database=database)
    update_mode_in_database(mode, config=config, database=database)
    try:
        run_mode(**settings)
    except:
        exc_info = sys.exc_info()
        update_mode_in_database(DEFAULT_MODE, config=config, database=database)
        six.reraise(*exc_info)
