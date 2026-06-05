<?php
/**
 * Non-destructive database migration script.
 * Adds new tables (events, push_subscriptions) if they don't exist.
 * Safe to run on existing installations.
 *
 * Usage: php migrate_database.php
 */
include_once(dirname(__DIR__) . '/error_config.php');
require_once(dirname(__DIR__) . '/env_config.php');
require_once(dirname(__DIR__) . '/config.php');
require_once(SRC_DIR . '/database.php');

$database_info = $_CONFIG['database'];
$account_info  = $database_info['account'];

echo "Connecting to database {$database_info['name']}...\n";
$database = connectToDatabase(
    $account_info['host'],
    $account_info['user'],
    $account_info['password'],
    $database_info['name']
);

// Events table — records cry/babble/sound events for history dashboard
$result = $database->query("CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_type` ENUM('cry','babble','sound','bad_and_good','bad_or_good') NOT NULL,
  `started_at` DATETIME NOT NULL,
  `ended_at` DATETIME DEFAULT NULL,
  `confidence` FLOAT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
echo $result ? "Table 'events' ready.\n" : "Error creating events: " . $database->error . "\n";

// Push subscriptions table — stores browser Web Push subscriptions
$result = $database->query("CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `endpoint` TEXT NOT NULL,
  `p256dh` TEXT NOT NULL,
  `auth` VARCHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `endpoint_hash` (endpoint(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
echo $result ? "Table 'push_subscriptions' ready.\n" : "Error creating push_subscriptions: " . $database->error . "\n";

closeConnection($database);
echo "Migration complete.\n";
