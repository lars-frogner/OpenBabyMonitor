<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(__DIR__ . '/database.php');

function hashPassword($password) {
  return password_hash($password, PASSWORD_DEFAULT);
}

function createPasswordTableIfMissing($database, $hash_len) {
  $hash_len = strval($hash_len);
  createTableIfMissing($database, 'password', array('id' => 'TINYINT UNSIGNED PRIMARY KEY', 'hash' => "CHAR($hash_len) NOT NULL"));
}

function storeHashedPassword($database, $hashed_password) {
  insertValuesIntoTable($database, 'password', array('id' => 0, 'hash' => $hashed_password));
}

function readHashedPassword($database) {
  return readValuesFromTable($database, 'password', 'hash', true);
}
