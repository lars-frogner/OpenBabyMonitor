<?php
require_once(dirname(__DIR__) . '/config/settings_config.php');

function line($string) {
  echo $string . "\n";
}

function generateInputs($setting_type, $grouped_setting_values) {
  $settings = getSettings($setting_type);
  $group_names = getSettingGroups($setting_type);
  foreach ($grouped_setting_values as $group => $content) {
    generateGroupStart($group_names[$group]);
    foreach ($content as $setting_name => $initial_value) {
      $setting = $settings[$setting_name];
      if (array_key_exists('values', $setting)) {
        generateSelect($setting, $setting_name, $initial_value);
      } elseif (array_key_exists('range', $setting)) {
        generateRange($setting, $setting_name, $initial_value);
      } else {
        generateCheckbox($setting, $setting_name, $initial_value);
      }
    }
    generateGroupEnd();
  }
}

function generateGroupStart($name) {
  line('<div class="col-lg">');
  line("<h2>$name</h2>");
}

function generateGroupEnd() {
  line('</div>');
}

function generateSelect($setting, $setting_name, $initial_value) {
  $id = $setting_name;
  $name = $setting['name'];
  $values = $setting['values'];

  line('<div class="mb-3">');
  line('  <div class="row">');
  line('    <div class="col-10">');
  line("  <label class=\"form-label\" for=\"$id\">$name</label>");
  line("  <select name=\"$setting_name\" class=\"form-select\" id=\"$id\">");
  foreach ($values as $name => $value) {
    $selected = ($value == $initial_value) ? ' selected' : '';
    line("    <option value=\"$value\"$selected>$name</option>");
  }
  line('  </select>');
  line('  </div>');
  line('  </div>');
  line('</div>');
}

function generateRange($setting, $setting_name, $initial_value) {
  $id = $setting_name;
  $value_id = $id . '_value';
  $name = $setting['name'];
  $min = $setting['range']['min'];
  $max = $setting['range']['max'];
  $step = $setting['range']['step'];

  line('<div class="mb-3">');
  line("  <label class=\"form-label\" for=\"$id\">$name</label>");
  line('  <div class="row flex-nowrap">');
  line('    <div class="col-10">');
  line("      <input type=\"range\" name=\"$setting_name\" class=\"form-range\" value=\"$initial_value\" min=\"$min\" max=\"$max\" step=\"$step\" id=\"$id\" oninput=\"$('#' + '$value_id').prop('value', this.value);\">");
  line('    </div>');
  line('    <div class="col-1">');
  line("      <output id=\"$value_id\">$initial_value</output>");
  line('    </div>');
  line('  </div>');
  line('</div>');
}

function generateCheckbox($setting, $setting_name, $initial_value) {
  $id = $setting_name;
  $name = $setting['name'];
  $name_attribute =
    "name=\"$setting_name\"";
  if ($initial_value) {
    $checked = 'checked';
    $hidden_input_name = '';
    $checkbox_name = $name_attribute;
  } else {
    $checked = '';
    $hidden_input_name =
      $name_attribute;
    $checkbox_name = '';
  }
  line('<div class="mb-3 form-check">');
  line("  <input type=\"hidden\" $hidden_input_name value=\"0\"><input type=\"checkbox\" class=\"form-check-input\" $checkbox_name value=\"1\" id=\"$id\" onclick=\"if (this.checked) { this.name = this.previousSibling.name; this.previousSibling.name = ''; } else { this.previousSibling.name = this.name; this.name = ''; }\"$checked>");
  line("  <label class=\"form-check-label\" for=\"$id\">$name</label>");
  line('</div>');
}
