<?php
include_once(dirname(__DIR__) . '/error_config.php');
require_once(dirname(__DIR__) . '/config.php');
require_once(SRC_PATH . '/database.php');
require_once(SRC_PATH . '/security.php');

if (count($argv) < 2) {
  bm_error('Password must be passed as first command line argument');
}

$database_info = $_CONFIG['database'];

$root_account_info = $database_info['root_account'];
$root_db_user = $root_account_info['user'];
echo "Connecting with user $root_db_user\n";
$root_connection = connectToAccount($root_account_info['host'], $root_db_user, $root_account_info['password']);

$account_info = $database_info['account'];
$db_user = $account_info['user'];
echo "Creating new user $db_user\n";
dropDatabaseIfExists($root_connection, $db_user);
createUserIfMissing($root_connection, $account_info['host'], $db_user, $account_info['password']);

$db_name = $database_info['name'];
echo "Creating new database $db_name\n";
dropDatabaseIfExists($root_connection, $db_name);
createDatabaseIfMissing($root_connection, $db_name);
useDatabase($root_connection, $db_name);

echo "Granting user $db_user all privileges on database $db_name\n";
grantUserAllPrivilegesOnDatabase($root_connection, $account_info['host'], $db_user, $db_name);

echo "Closing connection with user $root_db_user\n";
closeConnection($root_connection);

echo "Connecting to database $db_name with user $db_user\n";
$database = connectToDatabase($account_info['host'], $db_user, $account_info['password'], $db_name);

echo "Hashing password\n";
$password = $argv[1];
$hashed_password = hashPassword($password);

echo "Storing password hash in database $db_name\n";
createPasswordTableIfMissing($database, strlen($hashed_password));
storeHashedPassword($database, $hashed_password);

foreach (array('modes', 'audiostream_settings', 'videostream_settings') as $table_name) {
  echo "Creating table $table_name in database $db_name\n";
  createTableIfMissing($database, $table_name, readTableColumnsFromConfig($table_name));
  echo "Writing initial values to table $table_name in database $db_name\n";
  insertValuesIntoTable($database, $table_name, readTableInitialValuesFromConfig($table_name));
}

foreach (array('known_networks') as $table_name) {
  echo "Creating table $table_name in database $db_name\n";
  createTableIfMissing($database, $table_name, readTableColumnsFromConfig($table_name, false));
}

echo "Closing connection to database $db_name\n";
closeConnection($database);
