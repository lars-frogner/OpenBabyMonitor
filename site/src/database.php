<?php

function connectToAccount($host, $user, $password) {
  $connection = new mysqli($host, $user, $password);
  if ($connection->connect_error) {
    echo "Error: Connection to account for user $user failed: " . $connection->connect_error . "\n";
    exit(1);
  }
  return $connection;
}

function createUserIfMissing($connection, $host, $user, $password) {
  if (!$connection->query("CREATE USER IF NOT EXISTS '$user'@'$host' IDENTIFIED BY '$password';")) {
    echo "Error: Could not create user $user: " . $connection->error . "\n";
    exit(1);
  }
}

function createDatabaseIfMissing($connection, $name) {
  if (!$connection->query("CREATE DATABASE IF NOT EXISTS $name")) {
    echo "Error: Could not create database $name: " . $connection->error . "\n";
    exit(1);
  }
}

function grantUserAllPrivilegesOnDatabase($connection, $host, $user, $name) {
  if (!$connection->query("GRANT ALL PRIVILEGES ON $name.* TO '$user'@'$host'")) {
    echo "Error: Could not grant user $user privileges on database $name: " . $connection->error . "\n";
    exit(1);
  }
}

function useDatabase($connection, $name) {
  if (!$connection->query("USE $name")) {
    echo "Error: Could not use database $name: " . $connection->error . "\n";
    exit(1);
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
    echo "Error: Could not create table for $table_name: " . $database->error . "\n";
    exit(1);
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
    echo "Error: Could not insert values into table $table_name: " . $database->error . "\n";
    exit(1);
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
    echo "Error: No value provided for condition key $condition_key\n";
    exit(1);
  }
  if (count($column_values) > 1) {
    $updates = substr($updates, 0, -2);
  }
  $update_values = "UPDATE $table_name SET $updates WHERE $condition";
  if (!$database->query($update_values)) {
    echo "Error: Could update values in table $table_name: " . $database->error . "\n";
    exit(1);
  }
}

function readValuesFromTable($database, $table_name, $columns, $condition) {
  if (is_array($columns)) {
    $columns = join(', ', $columns);
  }
  $result = $database->query("SELECT $columns FROM $table_name WHERE $condition");
  if (!$result) {
    echo "Error: Could not select $columns from table $table_name: " . $database->error . "\n";
    exit(1);
  }
  return $result->fetch_all(MYSQLI_ASSOC);
}
