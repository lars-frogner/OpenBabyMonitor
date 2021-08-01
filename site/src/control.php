<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/env_config.php');
require_once(dirname(__DIR__) . '/config/database_config.php');
require_once(dirname(__DIR__) . '/config/control_config.php');
require_once(__DIR__ . '/io.php');
require_once(__DIR__ . '/database.php');

function readCurrentMode($database) {
  return readValuesFromTable($database, 'modes', 'current', true);
}

function acquireModeLock() {
  waitForModeLock();
  $output = null;
  $result_code = null;
  exec(ENVVAR_ASSIGNMENT . SERVER_LOCK_COMMANDS['acquire'], $output, $result_code);
  if ($result_code != 0) {
    bm_error("Lock acquisition command failed with error code $result_code:\n" . join("\n", $output));
  }
}

function releaseModeLock() {
  $output = null;
  $result_code = null;
  exec(ENVVAR_ASSIGNMENT . SERVER_LOCK_COMMANDS['release'], $output, $result_code);
  if ($result_code != 0) {
    bm_error("Lock release command failed with error code $result_code:\n" . join("\n", $output));
  }
}

function waitForModeLock() {
  $result = waitForFileToExist(getenv('BM_MODE_LOCK_FILE'));
  if ($result == MODE_ACTION_TIMED_OUT) {
    releaseModeLock();
  }
}

function startMode($mode) {
  $mode_start_command = getModeAttributes('start_command', MODE_NAMES[$mode]);
  if (!is_null($mode_start_command)) {
    $output = null;
    $result_code = null;
    exec($mode_start_command, $output, $result_code);
    if ($result_code != 0) {
      releaseModeLock();
      bm_error("Request for mode start failed with error code $result_code:\n" . join("\n", $output));
    }
  }
}

function stopMode($mode) {
  $mode_stop_command = getModeAttributes('stop_command', MODE_NAMES[$mode]);
  if (!is_null($mode_stop_command)) {
    $output = null;
    $result_code = null;
    exec($mode_stop_command, $output, $result_code);
    if ($result_code != 0) {
      releaseModeLock();
      bm_error("Request for mode stop failed with error code $result_code:\n" . join("\n", $output));
    }
  }
}

function restartMode($mode) {
  $mode_restart_command = getModeAttributes('restart_command', MODE_NAMES[$mode]);
  if (!is_null($mode_restart_command)) {
    $output = null;
    $result_code = null;
    exec($mode_restart_command, $output, $result_code);
    if ($result_code != 0) {
      releaseModeLock();
      bm_error("Request for mode restart failed with error code $result_code:\n" . join("\n", $output));
    }
  }
}

function waitForModeSwitch($database, $new_mode) {
  $elapsed_time = 0;
  while (readCurrentMode($database) != $new_mode) {
    usleep(MODE_QUERY_INTERVAL);
    $elapsed_time += MODE_QUERY_INTERVAL;
    if ($elapsed_time > MODE_SWITCH_TIMEOUT) {
      updateCurrentMode($database, MODE_VALUES['standby']);
      releaseModeLock();
      bm_error('Request for mode switch timed out');
    }
  }
}

function updateCurrentMode($database, $new_mode) {
  updateValuesInTable($database, 'modes', withPrimaryKey(array('current' => $new_mode)));
}

function waitForFileToExist($file_path) {
  $elapsed_time = 0;
  while (!file_exists($file_path)) {
    usleep(MODE_QUERY_INTERVAL);
    $elapsed_time += MODE_QUERY_INTERVAL;
    if ($elapsed_time > MODE_SWITCH_TIMEOUT) {
      bm_warning("Wait for creation of $file_path timed out");
      return MODE_ACTION_TIMED_OUT;
    }
  }
  return MODE_ACTION_OK;
}

function waitForFileUpdate($file_path) {
  $initial_timestamp = time();
  $elapsed_time = 0;
  while (!file_exists($file_path) || filemtime($file_path) <= $initial_timestamp) {
    usleep(MODE_QUERY_INTERVAL);
    $elapsed_time += MODE_QUERY_INTERVAL;
    if ($elapsed_time > MODE_SWITCH_TIMEOUT) {
      bm_warning("Wait for update of $file_path timed out");
      return MODE_ACTION_TIMED_OUT;
    }
  }
  return MODE_ACTION_OK;
}

function switchMode($database, $new_mode, $skip_if_same = true) {
  $current_mode = readCurrentMode($database);
  if ($current_mode == $new_mode && ($skip_if_same || $current_mode == MODE_VALUES['standby'])) {
    return MODE_ACTION_OK;
  }
  acquireModeLock();
  stopMode($current_mode);
  waitForModeSwitch($database, MODE_VALUES['standby']);
  startMode($new_mode);
  waitForModeSwitch($database, $new_mode);
  $wait_for_file_path = getWaitForFilePath(MODE_NAMES[$new_mode]);
  if (!is_null($wait_for_file_path)) {
    waitForFileUpdate($wait_for_file_path);
  }
  releaseModeLock();
  return MODE_ACTION_OK;
}

function restartCurrentMode($database) {
  switchMode($database, readCurrentMode($database), false);
}

function executeServerControlAction($action, $argument_env_name = null, $argument_value = null) {
  if (!key_exists($action, SERVER_ACTION_COMMANDS)) {
    bm_error("Invalid server action $action");
  }
  $output = null;
  $result_code = null;
  exec(ENVVAR_ASSIGNMENT . ((is_null($argument_env_name) || is_null($argument_value)) ? '' : "$argument_env_name=$argument_value; ") . SERVER_ACTION_COMMANDS[$action], $output, $result_code);
  if ($result_code != 0) {
    bm_error("Server action command $action failed with error code $result_code:\n" . join("\n", $output));
  }
}

function obtainWirelessScanResults() {
  executeServerControlAction('scan_wireless_networks');
  waitForFileToExist(WIRELESS_SCAN_RESULT_PATH);
  return readJSON(WIRELESS_SCAN_RESULT_PATH);
}
