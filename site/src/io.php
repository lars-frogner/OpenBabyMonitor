<?php

function readJSON($filepath) {
  $contents = file_get_contents($filepath);
  if (! $contents) {
    echo "Error: Could not read $filepath\n";
    exit(1);
  }
  $json_data = json_decode($contents, true);
  if (! isset($json_data)) {
    echo "Error: Could not decode $filepath as JSON\n";
    exit(1);
  }
  return $json_data;
}
