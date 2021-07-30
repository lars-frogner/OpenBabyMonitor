<?php
require_once(dirname(__DIR__) . '/config/settings_config.php');

function line($string) {
  echo $string . "\n";
}

function generateInputs($setting_type, $setting_values) {
  $settings = getSettings($setting_type);
  foreach ($setting_values as $setting_name => $initial_value) {
    $setting = $settings[$setting_name];
    if (array_key_exists('values', $setting)) {
      generateSelect($setting, $setting_name, $initial_value);
    } elseif (array_key_exists('range', $setting)) {
      generateRange($setting, $setting_name, $initial_value);
    } else {
      generateCheckbox($setting, $setting_name, $initial_value);
    }
  }
}

function generateSelect($setting, $setting_name, $initial_value) {
  $id = $setting_name . '_select';
  $name = $setting['name'];
  $values = $setting['values'];

  line('<div class="mb-3">');
  line("  <label class=\"form-label\" for=\"$id\">$name</label>");
  line("  <select name=\"$setting_name\" class=\"form-select\" id=\"$id\">");
  foreach ($values as $name => $value) {
    $selected = ($value == $initial_value) ? ' selected' : '';
    line("    <option value=\"$value\"$selected>$name</option>");
  }
  line('  </select>');
  line('</div>');
}

function generateRange($setting, $setting_name, $initial_value) {
  $id = $setting_name . '_range';
  $value_id = $id . '_value';
  $name = $setting['name'];
  $min = $setting['range']['min'];
  $max = $setting['range']['max'];
  $step = $setting['range']['step'];

  line('<div class="mb-3">');
  line("  <label class=\"form-label\" for=\"$id\">$name</label>");
  line('  <div class="row">');
  line('    <div class="col-sm-11">');
  line("      <input type=\"range\" name=\"$setting_name\" class=\"form-range\" value=\"$initial_value\" min=\"$min\" max=\"$max\" step=\"$step\" id=\"$id \" oninput=\"$('#' + '$value_id').value = this.value;\">");
  line('    </div>');
  line("    <output class=\"col-sm-1\" id=\"$value_id\">$initial_value</output>");
  line('  </div>');
  line('</div>');
}

function generateCheckbox($setting, $setting_name, $initial_value) {
  $id = $setting_name . '_check';
  $name = $setting['name'];
  $value = $initial_value ? ' checked' : '';
  line('<div class="mb-3 form-check">');
  line("  <input type=\"checkbox\" name=\"$setting_name\" class=\"form-check-input\" id=\"$id\"$value>");
  line("  <label class=\"form-check-label\" for=\"$id\">$name</label>");
  line('</div>');
}
