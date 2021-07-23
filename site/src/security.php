<?php
require_once(__DIR__ . 'database.php');

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
  $result = readValuesFromTable($database, 'password', 'hash', 'id = 0');
  if (empty($result)) {
    echo "Password hash not present in database\n";
    exit(1);
  }
  return $result[0]['hash'];
}
