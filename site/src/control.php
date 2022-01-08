<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/env_config.php');
require_once(dirname(__DIR__) . '/config/control_config.php');
require_once(__DIR__ . '/io.php');

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
  while (!file_exists($file_path) || filemtime($file_path) < $start_time) {
    clearstatcache(true, $file_path);
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

function obtainKnownNetworkSSIDs() {
  $output = null;
  $result_code = null;
  exec(SERVERCONTROL_DIR . '/get_known_network_ssids.sh', $output, $result_code);
  if ($result_code != 0) {
    bm_error("Obtaining known network SSIDs failed with error code $result_code:\n" . join("\n", $output));
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
  exec(SERVERCONTROL_DIR . '/get_connected_network_ssid.sh', $output, $result_code);
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
  $result = executeServerControlActionWithResult('remove_network', "'$ssid'");
  $result_code = $result['result_code'];
  if ($result_code != 0) {
    if ($result_code == 1) {
      return false;
    } else {
      bm_error("Removal of to network with SSID $ssid failed with error code $result_code:\n" . join("\n", $result['output']));
    }
  }
  return true;
}

function executeSettingOfNewAPChannel($ap_channel) {
  executeServerControlAction('set_ap_channel', $ap_channel);
}

function executeSettingOfNewAPSSIDAndPassword($ssid, $password) {
  executeServerControlAction('set_ap_ssid_password', array($ssid, $password));
}

function executeSettingOfNewAPPassword($password) {
  executeServerControlAction('set_ap_password', $password);
}

function executeSettingOfNewCountryCode($country_code) {
  executeServerControlAction('set_country_code', $country_code);
}

function executeSettingOfEnvVar($name, $value) {
  executeServerControlAction('set_env_var', array($name, $value));
}
