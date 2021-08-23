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


def read_settings(mode, config, database):
    table_name = mode + '_settings'
    if table_name not in config:
        return {}

    settings = list(config[table_name].keys())

    with database as open_database:
        values = open_database.read_values_from_table(table_name, settings)

    return dict(zip(settings, values))


def update_mode_in_database(mode, config, open_database):
    open_database.update_values_in_table(
        'modes',
        dict(id=0,
             current=config['modes']['current']['values'][mode]['value']))


def handle_shutdown(*args):
    config = get_config()
    with get_database(config) as open_database:
        update_mode_in_database(DEFAULT_MODE, config, open_database)
    sys.exit(0)


def register_shutdown_handler():
    signal.signal(signal.SIGTERM, handle_shutdown)
    signal.signal(signal.SIGINT, handle_shutdown)


def enter_mode(mode, run_mode):
    config = get_config()
    database = get_database(config)
    with database as open_database:
        update_mode_in_database(mode, config, open_database)
    try:
        run_mode(mode, config, database)
    except:
        exc_info = sys.exc_info()
        with database as open_database:
            update_mode_in_database(DEFAULT_MODE, config, open_database)
        six.reraise(*exc_info)
