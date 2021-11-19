<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

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
