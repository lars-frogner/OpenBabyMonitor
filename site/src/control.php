<?php
require_once(dirname(__DIR__) . '/config/mode_config.php');
require_once(__DIR__ . '/database.php');

function readCurrentMode($database) {
  $result = readValuesFromTable($database, 'modes', 'current', 'id = 0');
  if (empty($result)) {
    echo "Modes not present in database\n";
    exit(1);
  }
  return $result[0]['current'];
}

function startMode($mode) {
  if (!is_null(MODE_START_COMMANDS[$mode])) {
    $output = null;
    if (!exec(MODE_START_COMMANDS[$mode], $output)) {
      echo "Error: Request for mode start failed:\n" . join("\n", $output);
      exit(1);
    }
  }
}

function stopMode($mode) {
  if (!is_null(MODE_STOP_COMMANDS[$mode])) {
    $output = null;
    if (!exec(MODE_STOP_COMMANDS[$mode], $output)) {
      echo "Error: Request for mode stop failed:\n" . join("\n", $output);
      exit(1);
    }
  }
}

function waitForModeSwitch($database, $new_mode) {
  $elapsed_time = 0;
  while (readCurrentMode($database) != $new_mode) {
    usleep(MODE_QUERY_INTERVAL);
    $elapsed_time += MODE_QUERY_INTERVAL;
    if ($elapsed_time > MODE_SWITCH_TIMEOUT) {
      updateCurrentMode($database, STANDBY_MODE);
      echo "Error: Request for mode switch timed out";
      exit(1);
    }
  }
}

function updateCurrentMode($database, $new_mode) {
  updateValuesInTable($database, 'modes', array('id' => 0, 'current' => $new_mode), "id");
}

function switchMode($database, $new_mode) {
  $current_mode = readCurrentMode($database);
  if ($current_mode == $new_mode) {
    return MODE_SWITCH_OK;
  }
  stopMode($current_mode);
  waitForModeSwitch($database, STANDBY_MODE);
  startMode($new_mode);
  waitForModeSwitch($database, $new_mode);
  return MODE_SWITCH_OK;
}
