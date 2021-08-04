<?php
require_once(__DIR__ . '/config.php');
require_once(SRC_PATH . '/database.php');

$_DATABASE_INFO = $_CONFIG['database'];
$_DATABASE = connectToDatabase($_DATABASE_INFO['account']['host'], $_DATABASE_INFO['account']['user'], $_DATABASE_INFO['account']['password'], $_DATABASE_INFO['name']);

function withPrimaryKey($columns) {
  global $_DATABASE_INFO;
  $columns_with_key = $columns;
  $columns_with_key['id'] = $_DATABASE_INFO['key']['value'];
  return $columns_with_key;
}
