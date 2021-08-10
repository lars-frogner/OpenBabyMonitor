<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(SRC_PATH . '/database.php');
require_once(SRC_PATH . '/control.php');

define('KNOWN_NETWORKS_TABLE_NAME', 'known_networks');

function line($string) {
  echo $string . "\n";
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
  executeRemovalOfKnownNetwork($ssid);
  deleteTableRows($database, KNOWN_NETWORKS_TABLE_NAME, "ssid = '$ssid'");
}

function generateAvailableNetworksSelect($available_networks, $known_networks, $connected_network, $id) {
  function getColorClass($quality) {
    if ($quality > 2 / 3) {
      return 'text-success';
    } elseif ($quality < 1 / 3) {
      return 'text-danger';
    } else {
      return 'text-warning';
    }
  }
  $size = max(2, count($available_networks));
  uasort($available_networks, function ($a, $b) {
    return $b['quality'] <=> $a['quality'];
  });
  line("<select name=\"$id\" class=\"form-select\" size=\"$size\" id=\"$id\">");
  foreach ($available_networks as $ssid => $data) {
    $requires_password = $data['authentication'] ? 'true' : 'false';
    $is_known = in_array($ssid, $known_networks) ? 'true' : 'false';
    $is_connected = $ssid == $connected_network;
    $name = $ssid . ($is_connected ? ' (tilkoblet)' : '');
    $is_connected = $is_connected ? 'true' : 'false';
    $color_class = getColorClass($data['quality']);
    line("<option class=\"$color_class\" value=\"$ssid\" id=\"$ssid\">$name</option>");
    line("<script>$('#' + '$ssid').data('networkMeta', {requiresPassword: $requires_password, isKnown: $is_known, isConnected: $is_connected});</script>");
  }
  line('</select>');
}

function generateKnownNetworksSelect($known_networks, $id) {
  $size = max(2, count($known_networks));
  line("<select name=\"$id\" class=\"form-select\" size=\"$size\" id=\"$id\">");
  foreach ($known_networks as $ssid) {
    line("<option value=\"$ssid\">$ssid</option>");
  }
  line('</select>');
}
