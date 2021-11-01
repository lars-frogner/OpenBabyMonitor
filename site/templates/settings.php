<?php
require_once(dirname(__DIR__) . '/config/language_config.php');
require_once(dirname(__DIR__) . '/config/settings_config.php');

function line($string) {
  echo $string . "\n";
}

function generateInputs($setting_type, $grouped_setting_values, $pre_group_html, $post_group_html) {
  $settings = getSettings($setting_type);
  $group_names = getSettingGroups($setting_type);
  foreach ($grouped_setting_values as $group => $content) {
    $group_name = $group_names[$group];
    generateGroupStart($group_name);
    if (array_key_exists($group, $pre_group_html)) {
      echo $pre_group_html[$group];
    }
    foreach ($content as $setting_name => $initial_value) {
      $setting = $settings[$setting_name];
      if (array_key_exists('values', $setting)) {
        generateSelect($setting, $setting_name, $initial_value);
      } elseif (array_key_exists('radiovalues', $setting)) {
        generateRadio($setting, $setting_name, $initial_value);
      } elseif (array_key_exists('range', $setting)) {
        generateRange($setting, $setting_name, $initial_value);
      } else {
        generateCheckbox($setting, $setting_name, $initial_value);
      }
    }
    if (array_key_exists($group, $post_group_html)) {
      echo $post_group_html[$group];
    }
    generateGroupEnd();
  }
}

function generateGroupStart($name) {
  $name_trans = LANG[$name];
  line('<div class="col-auto mx-2">');
  line("<h2>$name_trans</h2>");
}

function generateGroupEnd() {
  line('</div>');
}

function generateSelect($setting, $setting_name, $initial_value) {
  $id = $setting_name;
  $name = $setting['name'];
  $name_trans = LANG[$name];
  $values = $setting['values'];

  line('<div class="mb-3">');
  line('  <div class="row">');
  line('    <div class="col-10">');
  line("  <label class=\"form-label\" for=\"$id\">$name_trans</label>");
  line("  <select name=\"$setting_name\" class=\"form-select\" id=\"$id\">");
  foreach ($values as $name => $value) {
    $name_trans = array_key_exists($name, LANG) ? LANG[$name] : $name;
    $selected = ($value == $initial_value) ? ' selected' : '';
    line("    <option value=\"$value\"$selected>$name_trans</option>");
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
  $name_trans = LANG[$name];
  $min = $setting['range']['min'];
  $max = $setting['range']['max'];
  $step = $setting['range']['step'];

  line('<div class="mb-3">');
  line("  <label class=\"form-label\" for=\"$id\">$name_trans</label>");
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
  $name_trans = LANG[$name];
  $name_attribute =
    "name=\"$setting_name\"";
  if ($initial_value) {
    $checked = 'checked';
    $hidden_input_name = '';
    $checkbox_name = $name_attribute;
    $checkbox_value = '1';
  } else {
    $checked = '';
    $hidden_input_name =
      $name_attribute;
    $checkbox_name = '';
    $checkbox_value = '0';
  }
  line('<div class="mb-3 form-check">');
  line("  <input type=\"hidden\" $hidden_input_name value=\"0\"><input type=\"checkbox\" class=\"form-check-input\" $checkbox_name value=\"$checkbox_value\" id=\"$id\" onclick=\"if (this.checked) { this.value = 1; this.name = this.previousSibling.name; this.previousSibling.name = ''; } else { this.value = 0; this.previousSibling.name = this.name; this.name = ''; }\"$checked>");
  line("  <label class=\"form-check-label\" for=\"$id\">$name_trans</label>");
  line('</div>');
}

function generateRadio($setting, $setting_name, $initial_value) {
  $values = $setting['radiovalues'];
  line("<div class=\"mb-3\" id=\"$setting_name\">");
  foreach ($values as $name => $value) {
    $id = $value;
    $checked = ($value == $initial_value) ? ' checked' : '';
    $name_trans = LANG[$name];
    line("<div class=\"form-check form-check-inline\">");
    line("  <input type=\"radio\" class=\"form-check-input\" name=\"$setting_name\" autocomplete=\"off\" id=\"$id\" value=\"$value\" $checked>");
    line("  <label class=\"form-check-label\" for=\"$id\">$name_trans</label>");
    line('</div>');
  }
  line('</div>');
}

function generateBehavior($setting_type) {
  $disabled_when = getSettingAttributes($setting_type, 'children_disabled_when');
  $children_selector = ' *';
  if (count($disabled_when) == 0) {
    $children_selector = '';
    $disabled_when = getSettingAttributes($setting_type, 'disabled_when');
  }
  $condition_statements = array();
  foreach ($disabled_when as $setting_name => $criteria) {
    foreach ($criteria as $id => $condition) {
      $operator = $condition['operator'];
      $value = $condition['value'];
      $condition_statement = "$('#$id').prop('value') $operator '$value'";
      if (array_key_exists($setting_name, $condition_statements)) {
        $condition_statements[$setting_name] = $condition_statements[$setting_name] . ' || ' . $condition_statement;
      } else {
        $condition_statements[$setting_name] =
          $condition_statement;
      }
    }
  }
  $change_statements = array();
  foreach ($disabled_when as $setting_name => $criteria) {
    $condition_statement = $condition_statements[$setting_name];
    $setter_statement = "$('#$setting_name $children_selector').prop('disabled', $condition_statement); ";
    foreach ($criteria as $id => $condition) {
      if (array_key_exists($id, $change_statements)) {
        $change_statements[$id] = $change_statements[$id] . $setter_statement;
      } else {
        $change_statements[$id] =
          $setter_statement;
      }
    }
  }
  line('$(function() {');
  foreach ($change_statements as $id => $statement) {
    line("$('#$id').change(function() { $statement });");
    line("$('#$id').change();");
  }
  line('});');
}
