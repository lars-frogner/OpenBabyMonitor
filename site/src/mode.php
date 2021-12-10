<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/env_config.php');
require_once(dirname(__DIR__) . '/config/database_config.php');
require_once(dirname(__DIR__) . '/config/control_config.php');
require_once(__DIR__ . '/database.php');
require_once(__DIR__ . '/control.php');

function readCurrentMode($database) {
  return readValuesFromTable($database, 'modes', 'current', true);
}

function acquireModeLock() {
  $lock = fopen(MODE_LOCK_FILE, 'r');
  if ($lock === false) {
    bm_error('Could not open lock file ' . MODE_LOCK_FILE);
  }
  $success = flock($lock, LOCK_EX);
  if (!$success) {
    bm_error('Lock acquisition with flock on ' . MODE_LOCK_FILE . ' failed');
  }
  return $lock;
}

function releaseModeLock($lock) {
  $success = flock($lock, LOCK_UN);
  if (!$success) {
    bm_error('Lock release with flock on ' . MODE_LOCK_FILE . ' failed');
  }
  $success = fclose($lock);
  if (!$success) {
    bm_error('Could not close lock file ' . MODE_LOCK_FILE);
  }
}

function waitForModeLock() {
  $lock = acquireModeLock();
  releaseModeLock($lock);
}

function startMode($lock, $mode) {
  $mode_start_command = getModeAttributes('start_command', MODE_NAMES[$mode]);
  $start_time = time();
  if (!is_null($mode_start_command)) {
    $output = null;
    $result_code = null;
    exec($mode_start_command, $output, $result_code);
    if ($result_code != 0) {
      releaseModeLock($lock);
      bm_error("Request for mode start failed with error code $result_code:\n" . join("\n", $output));
    }
  }
  return $start_time;
}

function stopMode($lock, $mode) {
  $mode_stop_command = getModeAttributes('stop_command', MODE_NAMES[$mode]);
  if (!is_null($mode_stop_command)) {
    $output = null;
    $result_code = null;
    exec($mode_stop_command, $output, $result_code);
    if ($result_code != 0) {
      releaseModeLock($lock);
      bm_error("Request for mode stop failed with error code $result_code:\n" . join("\n", $output));
    }
  }
}

function restartMode($lock, $mode) {
  $mode_restart_command = getModeAttributes('restart_command', MODE_NAMES[$mode]);
  if (!is_null($mode_restart_command)) {
    $output = null;
    $result_code = null;
    exec($mode_restart_command, $output, $result_code);
    if ($result_code != 0) {
      releaseModeLock($lock);
      bm_error("Request for mode restart failed with error code $result_code:\n" . join("\n", $output));
    }
  }
}

function waitForModeSwitch($database, $lock, $new_mode) {
  $elapsed_time = 0;
  while (readCurrentMode($database) != $new_mode) {
    usleep(FILE_QUERY_INTERVAL);
    $elapsed_time += FILE_QUERY_INTERVAL;
    if ($elapsed_time > MODE_SWITCH_TIMEOUT) {
      updateCurrentMode($database, MODE_VALUES['standby']);
      releaseModeLock($lock);
      bm_error('Request for mode switch timed out');
    }
  }
}

function updateCurrentMode($database, $new_mode) {
  updateValuesInTable($database, 'modes', withPrimaryKey(array('current' => $new_mode)));
}

function switchMode($database, $new_mode, $skip_if_same = true) {
  $lock = acquireModeLock();
  $current_mode = readCurrentMode($database);
  if ($current_mode == $new_mode && ($skip_if_same || $current_mode == MODE_VALUES['standby'])) {
    releaseModeLock($lock);
    return ACTION_OK;
  }
  stopMode($lock, $current_mode);
  $start_time = startMode($lock, $new_mode);
  $requirement = getWaitForRequirement(MODE_NAMES[$new_mode]);
  if (!is_null($requirement)) {
    $type = $requirement['type'];
    switch ($type) {
      case 'stream':
      case 'file':
        waitForFileUpdate($requirement['file_path'], $start_time, FILE_QUERY_INTERVAL, MODE_SWITCH_TIMEOUT);
        break;
      case 'socket':
        waitForSocketToOpen($requirement['hostname'], $requirement['port'], SOCKET_QUERY_INTERVAL, MODE_SWITCH_TIMEOUT);
        break;
      default:
        bm_warning("Unknown wait_for requirement type $type");
        break;
    }
  }
  waitForModeSwitch($database, $lock, $new_mode);
  releaseModeLock($lock);
  return ACTION_OK;
}

function waitForModeStream($mode) {
  $start_time = time();
  $requirement = getWaitForRequirement(MODE_NAMES[$mode]);
  if (!is_null($requirement) && $requirement['type'] == 'stream') {
    waitForFileUpdate($requirement['file_path'], $start_time, FILE_QUERY_INTERVAL, MODE_SWITCH_TIMEOUT);
  }
  return ACTION_OK;
}

function restartCurrentMode($database) {
  switchMode($database, readCurrentMode($database), false);
}
