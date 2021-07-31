<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/settings_config.php');

define('STRING_TYPES', array('CHAR', 'VARCHAR'));
define('INT_TYPES', array('TINYINT', 'INT', 'BOOLEAN'));
define('FLOAT_TYPES', array('FLOAT', 'DOUBLE'));

function convertSettingValue($string_value, $type_string) {
  $type = explode('(', explode(' ', trim($type_string))[0])[0];
  if (in_array($type, STRING_TYPES)) {
    return $string_value;
  } elseif (in_array($type, INT_TYPES)) {
    return intval($string_value);
  } elseif (in_array($type, FLOAT_TYPES)) {
    return floatval($string_value);
  } else {
    bm_error("Setting value type $type not recognized");
  }
}

function convertSettingValues($setting_type, $string_values) {
  $types = getSettingAttributes($setting_type, 'type');
  $values = array();
  foreach ($string_values as $name => $string_value) {
    $values[$name] = convertSettingValue(
      $string_value,
      $types[$name]
    );
  }
  return $values;
}

function groupSettingValues($setting_type, $values) {
  $group_names = getSettingGroups($setting_type);
  $grouped_values = array();
  foreach ($group_names as $group => $group_name) {
    $settings_in_group = getSettingAttributes($setting_type, 'group', null, function ($attributes) use ($group) {
      return $attributes['group'] == $group;
    });
    $grouped_values[$group] = array();
    foreach ($settings_in_group as $setting_name => $group) {
      $grouped_values[$group][$setting_name] = $values[$setting_name];
    }
  }
  return $grouped_values;
}
