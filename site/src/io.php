<?php
require_once(dirname(__DIR__) . '/config/error_config.php');

function readJSON($filepath) {
  $contents = file_get_contents($filepath);
  if (!$contents) {
    bm_error("Could not read $filepath");
  }
  return parseJSON($contents);
}

function parseJSON($string) {
  $json_data = json_decode($string, true);
  if (!isset($json_data)) {
    bm_error("Could not decode JSON:\n$string");
  }
  return $json_data;
}
