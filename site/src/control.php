<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/env_config.php');
require_once(dirname(__DIR__) . '/config/database_config.php');
require_once(dirname(__DIR__) . '/config/control_config.php');
require_once(__DIR__ . '/database.php');

function readCurrentMode($database) {
  return readValuesFromTable($database, 'modes', 'current', true);
}

function acquireModeLock() {
  bm_warning('Opening lock file');
  $lock = fopen(MODE_LOCK_FILE, 'r');
  bm_warning('Opened lock file');
  if ($lock === false) {
    bm_error('Could not open lock file ' . MODE_LOCK_FILE);
  }
  bm_warning('Acquiring lock');
  $success = flock($lock, LOCK_EX);
  bm_warning('Acquired lock');
  if (!$success) {
    bm_error('Lock acquisition with flock on ' . MODE_LOCK_FILE . ' failed');
  }
  return $lock;
}

function releaseModeLock($lock) {
  bm_warning('Releasing lock');
  $success = flock($lock, LOCK_UN);
  bm_warning('Released lock');
  if (!$success) {
    bm_error('Lock release with flock on ' . MODE_LOCK_FILE . ' failed');
  }
  bm_warning('Closing lock file');
  $success = fclose($lock);
  bm_warning('Closed lock file');
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
    bm_warning($mode_start_command);
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
    bm_warning($mode_stop_command);
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
  bm_warning("Waiting for mode switch to $new_mode");
  while (readCurrentMode($database) != $new_mode) {
    usleep(FILE_QUERY_INTERVAL);
    $elapsed_time += FILE_QUERY_INTERVAL;
    if ($elapsed_time > MODE_SWITCH_TIMEOUT) {
      updateCurrentMode($database, MODE_VALUES['standby']);
      releaseModeLock($lock);
      bm_error('Request for mode switch timed out');
    }
  }
  bm_warning("Mode has switched to $new_mode");
}

function updateCurrentMode($database, $new_mode) {
  updateValuesInTable($database, 'modes', withPrimaryKey(array('current' => $new_mode)));
}

function waitForFileToExist($file_path, $interval, $timeout) {
  $elapsed_time = 0;
  while (!file_exists($file_path)) {
    usleep($interval);
    $elapsed_time += $interval;
    if ($elapsed_time > $timeout) {
      bm_warning("Wait for creation of $file_path timed out");
      return ACTION_TIMED_OUT;
    }
  }
  return ACTION_OK;
}

function waitForFileUpdate($file_path, $start_time, $interval, $timeout) {
  $elapsed_time = 0;
  while (!file_exists($file_path) || filemtime($file_path) <= $start_time) {
    clearstatcache(true, $file_path);
    usleep($interval);
    $elapsed_time += $interval;
    if ($elapsed_time > $timeout) {
      bm_warning("Wait for update of $file_path timed out");
      return ACTION_TIMED_OUT;
    }
  }
  bm_warning("File $file_path updated");
  return ACTION_OK;
}

function waitForSocketToOpen($hostname, $port, $interval, $timeout) {
  $error_code = null;
  $error_message = null;
  $elapsed_time = 0;
  $previous_timestamp = microtime(true) * 1e6;
  while (!($f = @fsockopen($hostname, $port, $error_code, $error_message, $timeout))) {
    $timestamp = microtime(true) * 1e6;
    $call_duration = $timestamp - $previous_timestamp;
    $previous_timestamp = $timestamp;
    $remaining_wait_time = $interval - $call_duration;
    if ($remaining_wait_time > 0) {
      usleep($remaining_wait_time);
    }
    $elapsed_time += max($call_duration, $interval);
    if ($elapsed_time > $timeout) {
      bm_warning("Wait for socket $hostname:$port timed out");
      return ACTION_TIMED_OUT;
    }
  }
  fclose($f);
  return ACTION_OK;
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

function executeServerControlAction($action, $arguments = null) {
  if (is_null($arguments)) {
    $arguments = '';
  } elseif (!is_array($arguments)) {
    $arguments = " $arguments";
  } else {
    $arguments = ' ' . join(' ', $arguments);
  }
  if (!key_exists($action, SERVER_ACTION_COMMANDS)) {
    bm_error("Invalid server action $action");
  }
  $output = null;
  $result_code = null;
  exec(ENVVAR_ASSIGNMENT . SERVER_ACTION_COMMANDS[$action] . $arguments, $output, $result_code);
  if ($result_code != 0) {
    bm_error("Initiation of server action command $action failed with error code $result_code:\n" . join("\n", $output));
  }
}

function executeServerControlActionWithResult($action, $arguments = null, $return_result_code = false) {
  $output = null;
  $result_code = null;
  exec('rm -f ' . SERVER_ACTION_RESULT_FILE, $output, $result_code);
  if ($result_code != 0) {
    bm_error("Deletion of action result file failed with error code $result_code:\n" . join("\n", $output));
  }
  executeServerControlAction($action, $arguments);
  waitForFileToExist(SERVER_ACTION_RESULT_FILE, NETWORK_QUERY_INTERVAL, NETWORK_SWITCH_TIMEOUT);
  $result = file(SERVER_ACTION_RESULT_FILE, FILE_IGNORE_NEW_LINES);
  $result_code = $result[0];
  $output = (count($result) > 1) ? array_slice($result, 1) : array();
  if ($return_result_code) {
    return array('result_code' => $result_code, 'output' => $output);
  } elseif ($result_code != 0) {
    bm_error("Server action command $action failed with error code $result_code:\n" . join("\n", $output));
  }
  return $output;
}

function executeAutoSelectionOfMic() {
  executeServerControlAction('select_mic');
}

function microphoneIsConnected() {
  $output = null;
  $result_code = null;
  exec(SERVERCONTROL_DIR . '/check_microphone_connected.sh', $output, $result_code);
  if ($result_code != 0) {
    bm_error("Checking whether microphone is connected failed with error code $result_code:\n" . join("\n", $output));
  }
  return $output[0] == '1';
}

function cameraIsConnected() {
  $output = null;
  $result_code = null;
  exec(SERVERCONTROL_DIR . '/check_camera_connected.sh', $output, $result_code);
  if ($result_code != 0) {
    bm_error("Checking whether camera is connected failed with error code $result_code:\n" . join("\n", $output));
  }
  return $output[0] == '1';
}

function executeSettingOfNewTimezone($timezone) {
  executeServerControlAction('set_timezone', $timezone);
}

function executeSettingOfNewHostname($hostname) {
  executeServerControlAction('set_hostname', $hostname);
}

function executeSettingOfNewDevicePassword($password) {
  executeServerControlAction('set_device_password', $password);
}

function readCurrentLanguage($database) {
  return readValuesFromTable($database, 'language', 'current', true);
}

function updateCurrentLanguage($database, $new_language) {
  updateValuesInTable($database, 'language', withPrimaryKey(array('current' => $new_language)));
  setPHPSysInfoDefaultLanguage($new_language);
}

function setPHPSysInfoDefaultLanguage($new_language) {
  $output = null;
  $result_code = null;
  exec("sed -i 's/DEFAULT_LANG=.*/DEFAULT_LANG=\"$new_language\"/g' " . getenv('BM_PHPSYSINFO_CONFIG_FILE'), $output, $result_code);
  if ($result_code != 0) {
    bm_error("Setting default language for phpSysInfo failed with error code $result_code:\n" . join("\n", $output));
  }
}

function setPHPSysInfoDefaultTemplate($new_template) {
  $output = null;
  $result_code = null;
  exec("sed -i 's/DEFAULT_BOOTSTRAP_TEMPLATE=.*/DEFAULT_BOOTSTRAP_TEMPLATE=\"$new_template\"/g' " . getenv('BM_PHPSYSINFO_CONFIG_FILE'), $output, $result_code);
  if ($result_code != 0) {
    bm_error("Setting default template for phpSysInfo failed with error code $result_code:\n" . join("\n", $output));
  }
}

function getValidTimezones() {
  $output = null;
  $result_code = null;
  exec('timedatectl list-timezones', $output, $result_code);
  if ($result_code != 0) {
    bm_error("Obtaining valid time zones failed with error code $result_code:\n" . join("\n", $output));
  }
  return $output;
}

function getValidCountryCodes() {
  $output = null;
  $result_code = null;
  exec(SERVERCONTROL_DIR . '/get_country_codes.sh', $output, $result_code);
  if ($result_code != 0) {
    bm_error("Obtaining valid country codes failed with error code $result_code:\n" . join("\n", $output));
  }
  return $output;
}
