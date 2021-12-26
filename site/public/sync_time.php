<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(dirname(__DIR__) . '/config/network_config.php');
require_once(SRC_DIR . '/control.php');

if (ACCESS_POINT_ACTIVE && isset($_POST['current_timestamp'])) {
  $timestamp = $_POST['current_timestamp'];
  executeServerControlAction('set_timestamp', $timestamp);
}
