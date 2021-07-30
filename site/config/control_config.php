<?php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/env_config.php');

function getModeAttributes($attribute_name, $mode_name = null, $converter = null) {
  global $_CONFIG;
  if (is_null($mode_name)) {
    $attributes = array();
    foreach ($_CONFIG['modes']['current']['values'] as $mode_name => $content) {
      $attribute = $content[$attribute_name];
      $attributes[$mode_name] = is_callable($converter) ? $converter($attribute) : $attribute;
    }
    return $attributes;
  } else {
    $attribute = $_CONFIG['modes']['current']['values'][$mode_name][$attribute_name];
    return is_callable($converter) ? $converter($attribute) : $attribute;
  }
}

function getWaitForFilePath($mode_name) {
  return getModeAttributes('wait_for_file', $mode_name, function ($f) {
    return (!is_null($f) && getenv($f)) ? getenv($f) : $f;
  });
}

define('MODE_VALUES', getModeAttributes('value'));
define('MODE_NAMES', array_flip(MODE_VALUES));

define('MODE_ACTION_OK', 0);
define('MODE_ACTION_TIMED_OUT', 1);

$_CONTROL_INFO = $_CONFIG['control'];
define('MODE_QUERY_INTERVAL', intval($_CONTROL_INFO['mode_query_interval'] * 1e6)); // In microseconds
define('MODE_SWITCH_TIMEOUT', intval($_CONTROL_INFO['mode_switch_timeout'] * 1e6));

define('SERVER_ACTION_COMMANDS', $_CONTROL_INFO['server_actions']['commands']);

define('SERVER_LOCK_COMMANDS', $_CONTROL_INFO['lock_commands']);
