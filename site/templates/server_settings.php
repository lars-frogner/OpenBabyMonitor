<?php

function line($string) {
  echo $string . "\n";
}

function generateAvailableNetworksSelect($networks, $id) {
  $size = count($networks);
  uasort($networks, function ($a, $b) {
    return $b['quality'] <=> $a['quality'];
  });
  line("<select class=\"form-select\" size=\"$size\" id=\"$id\">");
  foreach ($networks as $ssid => $data) {
    $value = $data['quality'];
    line("<option value=\"$value\">$ssid</option>");
  }
  line('</select>');
}
