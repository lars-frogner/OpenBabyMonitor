<?php
require_once(__DIR__ . '/config.php');

function getSettings($setting_type) {
  global $_CONFIG;
  return $_CONFIG[$setting_type . '_settings'];
}

function getSettingGroups($setting_type) {
  global $_CONFIG;
  return $_CONFIG[$setting_type . '_groups'];
}

function getSettingAttributes($setting_type, $attribute_name, $setting_name = null, $filter = null) {
  if (is_null($setting_name)) {
    $attributes = array();
    foreach (getSettings($setting_type) as $setting_name => $content) {
      if (array_key_exists($attribute_name, $content)) {
        $attribute =
          $content[$attribute_name];
        if (!is_callable($filter) || $filter($content)) {
          $attributes[$setting_name] = $attribute;
        }
      }
    }
    return $attributes;
  } else {
    return getSettings($setting_type)[$setting_name][$attribute_name];
  }
}
