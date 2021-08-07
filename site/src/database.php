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

function dropUserIfExists($connection, $user) {
  if (!$connection->query("DROP USER IF EXISTS `$user`;")) {
    bm_error("Could not drop user $user: " . $connection->error);
  }
}

function createUserIfMissing($connection, $host, $user, $password) {
  if (!$connection->query("CREATE USER IF NOT EXISTS '$user'@'$host' IDENTIFIED BY '$password';")) {
    bm_error("Could not create user $user: " . $connection->error);
  }
}

function databaseExists($connection, $name) {
  $result = $connection->query("SHOW DATABASES LIKE '$name';");
  if (!$result) {
    bm_error("Could not check if database $name exists: " . $connection->error);
  }
  $result = $result->fetch_all(MYSQLI_ASSOC);
  return count($result) > 0;
}

function dropDatabaseIfExists($connection, $name) {
  if (!$connection->query("DROP DATABASE IF EXISTS `$name`;")) {
    bm_error("Could not drop database $name: " . $connection->error);
  }
}

function createDatabaseIfMissing($connection, $name) {
  if (!$connection->query("CREATE DATABASE IF NOT EXISTS `$name`;")) {
    bm_error("Could not create database $name: " . $connection->error);
  }
}

function grantUserAllPrivilegesOnDatabase($connection, $host, $user, $name) {
  if (!$connection->query("GRANT ALL PRIVILEGES ON `$name`.* TO `$user`@`$host`;")) {
    bm_error("Could not grant user $user privileges on database $name: " . $connection->error);
  }
}

function useDatabase($connection, $name) {
  if (!$connection->query("USE `$name`;")) {
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
    $entries = $entries . "`$name` $type, ";
  }
  if (count($columns) > 0) {
    $entries = substr($entries, 0, -2);
  }
  $create_table = "CREATE TABLE IF NOT EXISTS `$table_name` ($entries);";
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
    $names = $names . "`$name`, ";
    $values = $values . "$value, ";
    $updates = $updates . "`$name` = $value, ";
  }
  if (count($column_values) > 0) {
    $names = substr($names, 0, -2);
    $values = substr($values, 0, -2);
    $updates = substr($updates, 0, -2);
  }
  $insert_values = "INSERT INTO `$table_name` ($names) VALUES($values) ON DUPLICATE KEY UPDATE $updates;";
  if (!$database->query($insert_values)) {
    bm_error("Could not insert values into table $table_name: " . $database->error);
  }
}

function updateValuesInTable($database, $table_name, $column_values, $condition_key = 'id') {
  $updates = '';
  $condition = NULL;
  foreach ($column_values as $name => $value) {
    if ($name == $condition_key) {
      $condition = "$name = $value";
    } else {
      if (is_string($value) && $value != 'NULL') {
        $value = "'$value'";
      } elseif ($value === null) {
        $value = 'NULL';
      } elseif ($value === false) {
        $value = 0;
      }
      $updates = $updates . "`$name` = $value, ";
    }
  }
  if (is_null($condition)) {
    bm_error("No value provided for condition key $condition_key");
  }
  if (count($column_values) > 1) {
    $updates = substr($updates, 0, -2);
  }
  $update_values = "UPDATE `$table_name` SET $updates WHERE $condition;";
  if (!$database->query($update_values)) {
    bm_error("Could not update values in table $table_name: " . $database->error);
  }
}

function deleteTableRows($database, $table_name, $condition) {
  $result = $database->query("DELETE FROM `$table_name` WHERE $condition;");
  if (!$result) {
    bm_error("Could not delete rows where $condition from table $table_name: " . $database->error);
  }
}

function readValuesFromTable($database, $table_name, $columns, $return_with_numeric_keys = false, $condition = 'id = 0') {
  $column_string = is_array($columns) ? join(', ', $columns) : $columns;

  $command = "SELECT $column_string FROM `$table_name`" . (($condition === true) ? ';' : " WHERE $condition;");
  $result = $database->query($command);
  if (!$result) {
    bm_error("Could not select $column_string from table $table_name: " . $database->error);
  }
  $result = $result->fetch_all(MYSQLI_ASSOC);
  if (empty($result)) {
    bm_warning("Column(s) $column_string not present in table $table_name");
    return $result;
  }
  $columns = array_keys($result[0]);
  $multiple_columns = count($columns) > 1;
  if (!$multiple_columns) {
    $columns = $columns[0];
  }
  if ($return_with_numeric_keys) {
    if ($multiple_columns) {
      $result =
        array_map(function ($values) use ($columns) {
          return array_map(function ($name) use ($values) {
            return $values[$name];
          }, $columns);
        }, $result);
    } else {
      $result = array_map(function ($values) use ($columns) {
        return $values[$columns];
      }, $result);
    }
  }
  if ($condition === 'id = 0') {
    $result = $result[0];
  }
  return $result;
}

function tableKeyExists($database, $table_name, $primary_key, $key_value) {
  $result = $database->query("SELECT EXISTS(SELECT * FROM `$table_name` WHERE `$primary_key` = $key_value);");
  if (!$result) {
    bm_error("Could not check if key $primary_key = $key_value exists in $table_name: " . $database->error);
  }
  $result = $result->fetch_all(MYSQLI_NUM);
  return $result[0][0];
}
