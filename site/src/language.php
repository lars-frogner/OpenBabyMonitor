<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/env_config.php');
require_once(__DIR__ . '/database.php');

function readCurrentLanguage($database) {
  return readValuesFromTable($database, 'language', 'current', true);
}

function updateCurrentLanguage($database, $new_language) {
  updateValuesInTable($database, 'language', withPrimaryKey(array('current' => $new_language)));
  setPHPSysInfoDefaultLanguage($new_language);
}

function setPHPSysInfoDefaultLanguage($new_language) {
  $output = null;
  $result_code = null;
  exec("sed -i 's/DEFAULT_LANG=.*/DEFAULT_LANG=\"$new_language\"/g' " . getenv('BM_PHPSYSINFO_CONFIG_FILE'), $output, $result_code);
  if ($result_code != 0) {
    bm_error("Setting default language for phpSysInfo failed with error code $result_code:\n" . join("\n", $output));
  }
}
