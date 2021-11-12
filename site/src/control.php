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
  $result = waitForFileToExist(getenv('BM_MODE_LOCK_FILE'), FILE_QUERY_INTERVAL, MODE_SWITCH_TIMEOUT);
  if ($result == ACTION_TIMED_OUT) {
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
    usleep(FILE_QUERY_INTERVAL);
    $elapsed_time += FILE_QUERY_INTERVAL;
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

function waitForFileUpdate($file_path, $interval, $timeout) {
  $initial_timestamp = time();
  $elapsed_time = 0;
  while (!file_exists($file_path) || filemtime($file_path) <= $initial_timestamp) {
    usleep($interval);
    $elapsed_time += $interval;
    if ($elapsed_time > $timeout) {
      bm_warning("Wait for update of $file_path timed out");
      return ACTION_TIMED_OUT;
    }
  }
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
  $current_mode = readCurrentMode($database);
  if ($current_mode == $new_mode && ($skip_if_same || $current_mode == MODE_VALUES['standby'])) {
    return ACTION_OK;
  }
  acquireModeLock();
  stopMode($current_mode);
  waitForModeSwitch($database, MODE_VALUES['standby']);
  startMode($new_mode);
  waitForModeSwitch($database, $new_mode);
  $requirement = getWaitForRequirement(MODE_NAMES[$new_mode]);
  if (!is_null($requirement)) {
    $type = $requirement['type'];
    switch ($type) {
      case 'file':
        waitForFileUpdate($requirement['file_path'], FILE_QUERY_INTERVAL, MODE_SWITCH_TIMEOUT);
        break;
      case 'socket':
        waitForSocketToOpen($requirement['hostname'], $requirement['port'], SOCKET_QUERY_INTERVAL, MODE_SWITCH_TIMEOUT);
        break;
      default:
        bm_warning("Unknown wait_for requirement type $type");
        break;
    }
  }
  releaseModeLock();
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
  exec('rm -f ' . SERVER_ACTION_RESULT_FILE, $output, $result_code);
  if ($result_code != 0) {
    bm_error("Deletion of action result file failed with error code $result_code:\n" . join("\n", $output));
  }
  $output = null;
  $result_code = null;
  exec(ENVVAR_ASSIGNMENT . SERVER_ACTION_COMMANDS[$action] . $arguments, $output, $result_code);
  if ($result_code != 0) {
    bm_error("Initiation of server action command $action failed with error code $result_code:\n" . join("\n", $output));
  }
}

function executeServerControlActionWithResult($action, $arguments = null, $return_result_code = false) {
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

function obtainWirelessScanResults() {
  $output = executeServerControlActionWithResult('scan_wireless_networks');
  return parseJSON(join("\n", $output));
}

function obtainConnectedNetworkSSID() {
  $output = null;
  $result_code = null;
  exec(GET_CONNECTED_NETWORK_SSID_SCRIPT, $output, $result_code);
  if ($result_code == 255) {
    $ssid = null;
  } elseif ($result_code != 0) {
    bm_error("Obtaining connected network SSID failed with error code $result_code:\n" . join("\n", $output));
  } else {
    $ssid = $output[0];
  }
  return $ssid;
}

function executeConnectionToNetwork($ssid, $password, $remember) {
  $arguments = array("'$ssid'", "'$password'");
  if ($remember) {
    array_push($arguments, '--save');
  }
  $result = executeServerControlActionWithResult('connect_to_network', $arguments, true);
  $result_code = $result['result_code'];
  if ($result_code != 0) {
    if ($result_code == 1) {
      return false;
    } else {
      bm_error("Connection to network with SSID $ssid failed with error code $result_code:\n" . join("\n", $result['output']));
    }
  }
  return true;
}

function executeRemovalOfKnownNetwork($ssid) {
  executeServerControlActionWithResult('remove_network', "'$ssid'");
}

function executeSettingOfNewAPPassword($password) {
  executeServerControlAction('set_ap_password', $password);
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
