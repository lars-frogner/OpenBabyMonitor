<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(dirname(__DIR__) . '/config/network_config.php');
require_once(SRC_DIR . '/database.php');
require_once(SRC_DIR . '/control.php');

function line($string) {
  echo $string . "\n";
}

function ssidToValidId($ssid) {
  return hash('md5', $ssid);
}

function storeNetworkInfo($available_networks, $known_networks, $connected_network) {
  $ssids = array_unique(array_merge(array_keys($available_networks), $known_networks));

  line('<script>');
  line('const NETWORK_INFO = {');
  foreach ($ssids as $idx => $ssid) {
    $id = ssidToValidId($ssid);
    $html_ssid = htmlspecialchars($ssid);

    line("\"$html_ssid\": {");

    line("id: '$id',");

    $is_known = in_array($ssid, $known_networks) ? 'true' : 'false';
    line("isKnown: $is_known,");

    $is_connected = ($ssid == $connected_network) ? 'true' : 'false';
    line("isConnected: $is_connected,");

    if (array_key_exists($ssid, $available_networks)) {
      line('isAvailable: true,');
      $data = $available_networks[$ssid];
      $requires_password = $data['authentication'] ? 'true' : 'false';
      line("requiresPassword: $requires_password");
    } else {
      line('isAvailable: false,');
      line('requiresPassword: null');
    }

    $delim = ($idx < count($ssids) - 1) ? ',' : '';
    line("}$delim");
  }
  line('};');
  line('</script>');
}

function generateAvailableNetworksRadioAndSelect($available_networks, $name) {
  uasort($available_networks, function ($a, $b) {
    return $b['quality'] <=> $a['quality'];
  });
  foreach ($available_networks as $ssid => $data) {
    createAvailableNetworksHiddenRadioButton($name, $ssid);
  }
  line('<div class="border d-grid" data-toggle="buttons">');
  if (empty($available_networks)) {
    $message = LANG['no_networks_available'];
    line("<div class=\"text-bm mx-2 my-2\">$message</div>");
  }
  foreach ($available_networks as $ssid => $data) {
    createAvailableNetworksSelectOption($ssid, $data['quality']);
  }
  line('</div>');
}

function generateKnownNetworksRadioAndSelect($known_networks, $name) {
  foreach ($known_networks as $ssid) {
    createKnownNetworksHiddenRadioButton($name, $ssid);
  }
  line('<div class="border d-grid" data-toggle="buttons">');
  if (empty($known_networks)) {
    $message = LANG['no_known_networks'];
    line("<div class=\"text-bm mx-2 my-2\">$message</div>");
  }
  foreach ($known_networks as $ssid) {
    createKnownNetworksSelectOption($ssid);
  }
  line('</div>');
}

function createAvailableNetworksHiddenRadioButton($name, $ssid) {
  $id = ssidToValidId($ssid);
  $html_ssid = htmlspecialchars($ssid);
  line("<div class=\"form-check\" style=\"display: none;\">");
  line("  <input type=\"radio\" class=\"form-check-input\" name=\"$name\" id=\"available_radio_$id\" autocomplete=\"off\" value=\"$html_ssid\">");
  line('</div>');
}

function createKnownNetworksHiddenRadioButton($name, $ssid) {
  $id = ssidToValidId($ssid);
  $html_ssid = htmlspecialchars($ssid);
  line("<div class=\"form-check\" style=\"display: none;\">");
  line("  <input type=\"radio\" class=\"form-check-input\" name=\"$name\" id=\"known_radio_$id\" autocomplete=\"off\" value=\"$html_ssid\">");
  line('</div>');
}

function createAvailableNetworksSelectOption($ssid, $quality) {
  $id = ssidToValidId($ssid);
  line("<div class=\"btn network-option text-start\" id=\"available_option_$id\">");
  createAvailableNetworksSelectIcon($ssid, $id, $quality);
  line('</div>');
}

function createKnownNetworksSelectOption($ssid) {
  $id = ssidToValidId($ssid);
  line("<div class=\"btn network-option text-start\" id=\"known_option_$id\">");
  line("  <label>$ssid</label>");
  line('</div>');
}

function createAvailableNetworksSelectIcon($ssid, $id, $quality) {
  $html_ssid = htmlspecialchars($ssid);
  $icon = getWifiIcon($quality);
  $circle_size = '2.0rem';
  $icon_size = '1.5rem';
  $icon_offset = '0.25rem';
  line('<div class="d-flex flex-nowrap align-items-center">');
  line("  <div id=\"available_icon_$id\" class=\"network-icon me-2\" style=\"position: relative; width: $circle_size; height: $circle_size; border-radius: 50%;\">");
  line("    <svg class=\"bi\" style=\"position: absolute; left: $icon_offset; top: $icon_offset; height: $icon_size; width: $icon_size;\" fill=\"currentColor\">");
  line("      <use href=\"media/bootstrap-icons.svg#$icon\" />");
  line('    </svg>');
  line('  </div>');
  line("  <span>$html_ssid</span>");
  line('</div>');
}

function getWifiIcon($quality) {
  if ($quality > 2 / 3) {
    return 'wifi';
  } elseif ($quality < 1 / 3) {
    return 'wifi-2';
  } else {
    return 'wifi-1';
  }
}
