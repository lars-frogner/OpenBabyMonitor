<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(dirname(__DIR__) . '/config/network_config.php');
require_once(__DIR__ . '/database.php');
require_once(__DIR__ . '/control.php');

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
