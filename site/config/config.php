<?php
require_once(__DIR__ . '/path_config.php');
require_once(SRC_PATH . '/io.php');

$_CONFIG = readJSON(CONFIG_FILE_PATH);

function readTableColumnNamesFromConfig($table_name) {
  global $_CONFIG;
  return array_keys($_CONFIG[$table_name]);
}

function readTableColumnsFromConfig($table_name, $include_id = true) {
  global $_CONFIG;
  $columns = $include_id ? array('id' => $_CONFIG['database']['key']['type']) : array();
  foreach ($_CONFIG[$table_name] as $column_name => $content) {
    $columns[$column_name] = $content['type'];
  }
  return $columns;
}

function readTableInitialValuesFromConfig($table_name, $include_id = true) {
  global $_CONFIG;
  $values = $include_id ? array('id' => $_CONFIG['database']['key']['value']) : array();
  foreach ($_CONFIG[$table_name] as $column_name => $content) {
    $values[$column_name] = $content['initial_value'];
  }
  return $values;
}
