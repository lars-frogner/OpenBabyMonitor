<?php
require_once(__DIR__ . '/security.php');

function tryLogin($database, $password, $destination, $otherwise) {
  $password_hash = readHashedPassword($database);
  if (password_verify($password, $password_hash)) {
    $_SESSION['login'] = true;
    redirectTo($destination);
  } else {
    echo $otherwise;
  }
}

function redirectIfLoggedIn($destination) {
  session_start();
  if (isset($_SESSION['login'])) {
    redirectTo($destination);
  }
}

function redirectIfLoggedOut($destination) {
  session_start();
  if (!isset($_SESSION['login'])) {
    redirectTo($destination);
  }
}

function logout($destination) {
  session_start();
  session_destroy();
  redirectTo($destination);
}

function redirectTo($destination) {
  header('LOCATION:' . $destination);
  exit();
}
