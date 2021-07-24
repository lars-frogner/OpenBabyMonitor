<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/mode_config.php');
require_once(__DIR__ . '/database.php');

function readCurrentMode($database) {
  $result = readValuesFromTable($database, 'modes', 'current', 'id = 0');
  if (empty($result)) {
    bm_error('Modes not present in database');
  }
  return $result[0]['current'];
}

function startMode($mode) {
  if (!is_null(MODE_START_COMMANDS[$mode])) {
    $output = null;
    $result_code = null;
    exec(MODE_START_COMMANDS[$mode], $output, $result_code);
    if ($result_code != 0) {
      bm_error("Request for mode start failed with error code $result_code:\n" . join("\n", $output));
    }
  }
}

function stopMode($mode) {
  if (!is_null(MODE_STOP_COMMANDS[$mode])) {
    $output = null;
    $result_code = null;
    exec(MODE_STOP_COMMANDS[$mode], $output, $result_code);
    if ($result_code != 0) {
      bm_error("Request for mode stop failed with error code $result_code:\n" . join("\n", $output));
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
      bm_error('Request for mode switch timed out');
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