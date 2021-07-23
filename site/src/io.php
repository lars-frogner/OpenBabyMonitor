<?php
require_once(dirname(__DIR__) . '/config/error_config.php');

function readJSON($filepath) {
  $contents = file_get_contents($filepath);
  if (!$contents) {
    bm_error("Could not read $filepath");
  }
  $json_data = json_decode($contents, true);
  if (!isset($json_data)) {
    bm_error("Could not decode $filepath as JSON");
  }
  return $json_data;
}
