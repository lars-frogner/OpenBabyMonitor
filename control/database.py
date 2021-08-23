import mysql.connector


class Database:
    @staticmethod
    def from_config(config):
        db_info = config['database']
        account_info = db_info['account']
        return Database(account_info['host'], account_info['user'],
                        account_info['password'], db_info['name'])

    def __init__(self, host, user, password, name, **extra_config):
        self.host = host
        self.user = user
        self.password = password
        self.name = name
        self.extra_config = extra_config

    def __enter__(self):
        self.connection = mysql.connector.connect(host=self.host,
                                                  user=self.user,
                                                  password=self.password,
                                                  database=self.name,
                                                  **self.extra_config)
        self.connection.autocommit = True

        self.cursor = self.connection.cursor(dictionary=True)

        return self

    def update_values_in_table(self,
                               table_name,
                               column_values,
                               condition_key='id'):
        updates = ''
        condition = None
        for name, value in column_values.items():
            if name == condition_key:
                condition = '`{}` = {}'.format(name, value)
            else:
                if value is None:
                    value = 'NULL'
                elif isinstance(value, str):
                    value = "'{}'".format(value)
                updates += '`{}` = {}, '.format(name, value)

        if condition is None:
            raise ValueError(
                'No value provided for condition key {}'.format(condition_key))

        if len(column_values) > 1:
            updates = updates[:-2]

        self.cursor.execute('UPDATE `{}` SET {} WHERE {};'.format(
            table_name, updates, condition))

    def read_values_from_table(self, table_name, columns, condition='id = 0'):
        multiple_columns = hasattr(
            columns, '__iter__') and not isinstance(columns, str)
        column_string = ', '.join(columns) if multiple_columns else columns

        self.cursor.execute('SELECT {} FROM `{}` WHERE {};'.format(
            column_string, table_name, condition))
        result = self.cursor.fetchall()

        if multiple_columns:
            result = [[values[name] for name in columns] for values in result]
        else:
            result = [values[columns] for values in result]
        if condition == 'id = 0':
            result = result[0]
        return result

    def close(self):
        self.connection.close()

    def __exit__(self, *args):
        self.close()
