<?php
require_once(__DIR__ . '/error_config.php');
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

define('MODE_SIGNAL_FILE_STEM', getenv('BM_MODE_SIGNAL_FILE_STEM'));

function getWaitForRequirement($mode_name) {
  return getModeAttributes('wait_for', $mode_name, function ($wait_for) use ($mode_name) {
    if (!is_null($wait_for)) {
      if ($wait_for == 'signal') {
        $file_path = MODE_SIGNAL_FILE_STEM . ".$mode_name";
        return array('type' => 'file', 'file_path' => $file_path);
      }
      $split = explode('=', $wait_for, 2);
      if (empty($split)) {
        bm_error("Invalid wait_for entry: $wait_for");
      }
      $type = $split[0];
      $content = $split[1];
      switch ($type) {
        case 'stream':
        case 'file':
          $file_path = getenv($content) ? getenv($content) : $content;
          return array('type' => $type, 'file_path' => $file_path);
        case 'socket':
          $split = explode(':', $content, 2);
          if (empty($split)) {
            $hostname = getenv($content) ? getenv($content) : $content;
            $port = -1;
          } else {
            $hostname = getenv($split[0]) ? getenv($split[0]) : $split[0];
            $port = getenv($split[1]) ? getenv($split[1]) : $split[1];
          }
          return array('type' => $type, 'hostname' => $hostname, 'port' => $port);
        default:
          bm_error("Invalid wait_for type: $type");
      }
    } else {
      return null;
    }
  });
}

$mode_values = getModeAttributes('value');
define('MODE_VALUES', $mode_values);
define('MODE_NAMES', array_flip(MODE_VALUES));

define('ACTION_OK', 0);
define('ACTION_TIMED_OUT', 1);

$_CONTROL_INFO = $_CONFIG['control'];
define('MODE_LOCK_FILE', getenv('BM_MODE_LOCK_FILE'));
define('FILE_QUERY_INTERVAL', intval($_CONTROL_INFO['file_query_interval'] * 1e6)); // In microseconds
define('SOCKET_QUERY_INTERVAL', intval($_CONTROL_INFO['socket_query_interval'] * 1e6));
define('MODE_SWITCH_TIMEOUT', intval($_CONTROL_INFO['mode_switch_timeout'] * 1e6));

define('SERVER_ACTION_RESULT_FILE', getenv('BM_SERVER_ACTION_RESULT_FILE'));
define('SERVER_ACTION_COMMANDS', $_CONTROL_INFO['server_actions']['commands']);

define('TIME_SYNCED', file_exists(getenv('BM_CONTROL_TIME_SYNCED_FILE')));
