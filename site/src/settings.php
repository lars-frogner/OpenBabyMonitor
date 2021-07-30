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
