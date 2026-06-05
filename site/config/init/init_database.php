<?php
include_once(dirname(__DIR__) . '/error_config.php');
require_once(dirname(__DIR__) . '/env_config.php');
require_once(dirname(__DIR__) . '/config.php');
require_once(SRC_DIR . '/database.php');
require_once(SRC_DIR . '/control.php');
require_once(SRC_DIR . '/security.php');

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

$table_names = array('modes', 'language', 'listen_settings', 'audiostream_settings', 'system_settings', 'videostream_settings');

// Create events table for history dashboard
$events_create = "CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_type` ENUM('cry','babble','sound','bad_and_good','bad_or_good') NOT NULL,
  `started_at` DATETIME NOT NULL,
  `ended_at` DATETIME DEFAULT NULL,
  `confidence` FLOAT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if (!$database->query($events_create)) {
  bm_error("Could not create events table: " . $database->error);
}
echo "Created events table\n";

// Create push_subscriptions table for Web Push
$push_create = "CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `endpoint` TEXT NOT NULL,
  `p256dh` TEXT NOT NULL,
  `auth` VARCHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `endpoint_hash` (endpoint(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if (!$database->query($push_create)) {
  bm_error("Could not create push_subscriptions table: " . $database->error);
}
echo "Created push_subscriptions table\n";
foreach ($table_names as $table_name) {
  echo "Creating table $table_name in database $db_name\n";
  createTableIfMissing($database, $table_name, readTableColumnsFromConfig($table_name));
  echo "Writing initial values to table $table_name in database $db_name\n";
  insertValuesIntoTable($database, $table_name, readTableInitialValuesFromConfig($table_name));
}

foreach (array('known_networks') as $table_name) {
  echo "Creating table $table_name in database $db_name\n";
  createTableIfMissing($database, $table_name, readTableColumnsFromConfig($table_name, false));
  foreach (obtainKnownNetworkSSIDs() as $ssid) {
    insertValuesIntoTable($database, $table_name, array('ssid' => $ssid));
  }
}

echo "Closing connection to database $db_name\n";
closeConnection($database);
