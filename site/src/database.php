<?php

function connectToDatabase($host, $user, $password, $name) {
  $connection = new mysqli($host, $user, $password);
  if ($connection->connect_error) {
    echo "Connection failed: " . $connection->connect_error;
    exit(1);
  }
  if (! $connection->query("CREATE DATABASE IF NOT EXISTS $name")) {
    echo "Error creating database: " . $connection->error;
    exit(1);
  }
  if (! $connection->query("USE $name")) {
    echo "Error using database: " . $connection->error;
    exit(1);
  }
  return $connection;
}

function closeDatabaseConnection($database) {
  $database->close();
}

function storePassword($database, $password) {
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  $hash_len = strval(strlen($hashed_password));
  $create_table = "CREATE TABLE IF NOT EXISTS password (
    id TINYINT UNSIGNED PRIMARY KEY,
    hash CHAR($hash_len)
  )";
  if (! $database->query($create_table)) {
    echo "Error creating table for password: " . $database->error;
    exit(1);
  }
  $insert_hash = "INSERT INTO password (id, hash)
    VALUES(0, '$hashed_password')
    ON DUPLICATE KEY UPDATE
    hash = '$hashed_password'";
  if (! $database->query($insert_hash)) {
    echo "Error storing password: " . $database->error;
    exit(1);
  }
}

function obtainPasswordHash($database) {
    $result = $database->query("SELECT hash FROM password WHERE id = 0");
    if (! $result) {
        echo "Error selecting password hash: " . $database->error;
        exit(1);
    }
    $row = $result->fetch_row();
    if (! $row) {
        echo "Password hash not present in database: " . $database->error;
        exit(1);
    }
    return $row[0];
}
