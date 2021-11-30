<?php
require_once(__DIR__ . '/env_config.php');

if (getenv('BM_DEBUG') == '1') {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
} else {
  ini_set('display_errors', 0);
  ini_set('display_startup_errors', 0);
  error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}
ini_set('log_errors', 1);

function bm_error($message) {
  error_log('Error: ' . $message);
  exit(1);
}

function bm_warning($message) {
  error_log('Warning: ' . $message);
}

function bm_warning_to_file($message, $file_path) {
  error_log(bm_get_timestamp() . 'Warning: ' . $message . "\n", 3, $file_path);
}

function bm_get_timestamp() {
  return '[' . date('D M d H:i:s:u Y') . '] ';
}

define('SESSION_EXPIRED', -1);
