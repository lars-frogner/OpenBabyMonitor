<?php
require_once(dirname(__DIR__) . '/config/error_config.php');

function connectToAccount($host, $user, $password) {
  $connection = new mysqli($host, $user, $password);
  if ($connection->connect_error) {
    bm_error("Connection to account for user $user failed: " . $connection->connect_error);
  }
  $connection->autocommit(true);
  return $connection;
}

function createUserIfMissing($connection, $host, $user, $password) {
  if (!$connection->query("CREATE USER IF NOT EXISTS '$user'@'$host' IDENTIFIED BY '$password';")) {
    bm_error("Could not create user $user: " . $connection->error);
  }
}

function createDatabaseIfMissing($connection, $name) {
  if (!$connection->query("CREATE DATABASE IF NOT EXISTS $name")) {
    bm_error("Could not create database $name: " . $connection->error);
  }
}

function grantUserAllPrivilegesOnDatabase($connection, $host, $user, $name) {
  if (!$connection->query("GRANT ALL PRIVILEGES ON $name.* TO '$user'@'$host'")) {
    bm_error("Could not grant user $user privileges on database $name: " . $connection->error);
  }
}

function useDatabase($connection, $name) {
  if (!$connection->query("USE $name")) {
    bm_error("Could not use database $name: " . $connection->error);
  }
}

function connectToDatabase($host, $user, $password, $name) {
  $connection = connectToAccount($host, $user, $password);
  useDatabase($connection, $name);
  return $connection;
}

function closeConnection($connection) {
  $connection->close();
}

function createTableIfMissing($database, $table_name, $columns) {
  $entries = '';
  foreach ($columns as $name => $type) {
    $entries = $entries . "$name $type, ";
  }
  if (count($columns) > 0) {
    $entries = substr($entries, 0, -2);
  }
  $create_table = "CREATE TABLE IF NOT EXISTS $table_name ($entries)";
  if (!$database->query($create_table)) {
    bm_error("Could not create table for $table_name: " . $database->error);
  }
}

function insertValuesIntoTable($database, $table_name, $column_values) {
  $names = '';
  $values = '';
  $updates = '';
  foreach ($column_values as $name => $value) {
    if (is_null($value)) {
      $value = 'NULL';
    } else if ($value === false) {
      $value = 'FALSE';
    } else if (is_string($value)) {
      $value = "'$value'";
    }
    $names = $names . "$name, ";
    $values = $values . "$value, ";
    $updates = $updates . "$name = $value, ";
  }
  if (count($column_values) > 0) {
    $names = substr($names, 0, -2);
    $values = substr($values, 0, -2);
    $updates = substr($updates, 0, -2);
  }
  $insert_values = "INSERT INTO $table_name ($names) VALUES($values) ON DUPLICATE KEY UPDATE $updates";
  if (!$database->query($insert_values)) {
    bm_error("Could not insert values into table $table_name: " . $database->error);
  }
}

function updateValuesInTable($database, $table_name, $column_values, $condition_key) {
  $updates = '';
  $condition = NULL;
  foreach ($column_values as $name => $value) {
    if ($name == $condition_key) {
      $condition = "$name = $value";
    } else {
      if (is_string($value) && $value != 'NULL') {
        $value = "'$value'";
      }
      $updates = $updates . "$name = $value, ";
    }
  }
  if (is_null($condition)) {
    bm_error("No value provided for condition key $condition_key");
  }
  if (count($column_values) > 1) {
    $updates = substr($updates, 0, -2);
  }
  $update_values = "UPDATE $table_name SET $updates WHERE $condition";
  if (!$database->query($update_values)) {
    bm_error("Could not update values in table $table_name: " . $database->error);
  }
}

function readValuesFromTable($database, $table_name, $columns, $condition) {
  if (is_array($columns)) {
    $columns = join(', ', $columns);
  }
  $result = $database->query("SELECT $columns FROM $table_name WHERE $condition");
  if (!$result) {
    bm_error("Could not select $columns from table $table_name: " . $database->error);
  }
  return $result->fetch_all(MYSQLI_ASSOC);
}
