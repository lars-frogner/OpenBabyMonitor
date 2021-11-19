<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/network_config.php');
require_once(__DIR__ . '/io.php');
require_once(__DIR__ . '/database.php');
require_once(__DIR__ . '/control.php');

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

function executeSettingOfNewAPPassword($password) {
  executeServerControlAction('set_ap_password', $password);
}

function anyKnownNetworks($database) {
  return tableHasEntries($database, KNOWN_NETWORKS_TABLE_NAME);
}

function networkIsKnown($database, $ssid) {
  return tableKeyExists($database, KNOWN_NETWORKS_TABLE_NAME, 'ssid', "'$ssid'");
}

function readKnownNetworks($database) {
  return readValuesFromTable($database, KNOWN_NETWORKS_TABLE_NAME, 'ssid', true, true);
}

function connectToNetwork($database, $ssid, $password, $remember) {
  $success = executeConnectionToNetwork($ssid, $password, $remember);
  if ($success && $remember) {
    insertValuesIntoTable($database, KNOWN_NETWORKS_TABLE_NAME, array('ssid' => $ssid));
  }
  return $success;
}

function removeKnownNetwork($database, $ssid) {
  $success = executeRemovalOfKnownNetwork($ssid);
  if ($success) {
    deleteTableRows($database, KNOWN_NETWORKS_TABLE_NAME, "ssid = '$ssid'");
  }
  return $success;
}
