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

function readLines($filepath) {
  $f = fopen($filepath, 'r');
  if ($f) {
    while (($line = fgets($f)) !== false) {
      $lines[] = str_replace("\n", '', $line);
    }
    fclose($f);
  } else {
    bm_error("Could not open $filepath");
  }
  return $lines;
}
