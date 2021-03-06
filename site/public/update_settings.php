<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(SRC_DIR . '/session.php');
require_once(dirname(__DIR__) . '/config/database_config.php');
require_once(dirname(__DIR__) . '/config/control_config.php');
require_once(SRC_DIR . '/settings.php');
require_once(SRC_DIR . '/database.php');

abortIfSessionExpired();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $setting_type = $_POST['setting_type'];
  $table_name = $setting_type . '_settings';
  unset($_POST['setting_type']);
  updateValuesInTable($_DATABASE, $table_name, withPrimaryKey(convertSettingValues($setting_type, $_POST)));
  echo ACTION_OK;
} else {
  echo 'POST request required';
}
