<?php
function createModeRadioButtonIcon($name, $icon, $checked) {
  if ($checked) {
    $checked_string = 'checked';
    $icon = $icon . '-fill';
  } else {
    $checked_string = 'unchecked';
  }
  $style_size = 'min(12vh, calc(25vw - 2rem - 2px))';
  echo "<div class=\"$checked_string\">\n";
  echo "  <svg class=\"bi\" style=\"height: $style_size; width: $style_size;\" fill=\"currentColor\">\n";
  echo "    <use xlink:href=\"media/bootstrap-icons.svg#$icon\" />\n";
  echo "  </svg>\n";
  echo "  <p class=\"mb-0 mt-2\">$name</p>\n";
  echo "</div>\n";
}

function createModeRadioButtonIcons($name, $icon) {
  createModeRadioButtonIcon($name, $icon, false);
  createModeRadioButtonIcon($name, $icon, true);
}

function createModeRadioButton($current_mode, $mode_name, $text, $icon) {
  echo "<label class=\"btn btn-bm px-3" . (($current_mode == MODE_VALUES[$mode_name]) ? ' active' : '') . " disabled\">\n";
  echo "<input type=\"radio\" class=\"btn-check\" name=\"mode_radio\" id=\"mode_radio_$mode_name\" autocomplete=\"off\" disabled" . (($current_mode == MODE_VALUES[$mode_name]) ? ' checked' : '') . " value=\"" . MODE_VALUES[$mode_name] . "\">\n";
  createModeRadioButtonIcons($text, $icon);
  echo "</label>\n";
}
