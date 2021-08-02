<?php
require_once(__DIR__ . '/config.php');
require_once(SRC_PATH . '/database.php');

$_DATABASE_INFO = $_CONFIG['database'];
$_DATABASE = connectToDatabase($_DATABASE_INFO['account']['host'], $_DATABASE_INFO['account']['user'], $_DATABASE_INFO['account']['password'], $_DATABASE_INFO['name']);

function readTableColumnNamesFromConfig($table_name) {
  global $_CONFIG;
  return array_keys($_CONFIG[$table_name]);
}

function readTableColumnsFromConfig($table_name, $include_id = true) {
  global $_CONFIG;
  global $_DATABASE_INFO;
  $columns = $include_id ? array('id' => $_DATABASE_INFO['key']['type']) : array();
  foreach ($_CONFIG[$table_name] as $column_name => $content) {
    $columns[$column_name] = $content['type'];
  }
  return $columns;
}

function readTableInitialValuesFromConfig($table_name, $include_id = true) {
  global $_CONFIG;
  global $_DATABASE_INFO;
  $values = $include_id ? array('id' => $_DATABASE_INFO['key']['value']) : array();
  foreach ($_CONFIG[$table_name] as $column_name => $content) {
    $values[$column_name] = $content['initial_value'];
  }
  return $values;
}

function withPrimaryKey($columns) {
  global $_DATABASE_INFO;
  $columns_with_key = $columns;
  $columns_with_key['id'] = $_DATABASE_INFO['key']['value'];
  return $columns_with_key;
}
