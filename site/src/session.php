<?php
session_start();
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(__DIR__ . '/security.php');

function tryLogin($database, $password, $destination) {
  $password_hash = readHashedPassword($database);
  if (password_verify($password, $password_hash)) {
    $_SESSION['login'] = true;
    redirectTo($destination);
  } else {
    return false;
  }
}

function isLoggedIn() {
  return isset($_SESSION['login']);
}

function redirectIfLoggedIn($destination) {
  if (isLoggedIn()) {
    redirectTo($destination);
  }
}

function redirectIfLoggedOut($destination, $pass_uri = true) {
  if (!isLoggedIn()) {
    redirectTo($pass_uri ? addCurrentURIAsGETRequest($destination) : $destination);
  }
}

function logout($destination, $exit_command = null) {
  destroySession();
  redirectTo($destination, $exit_command);
}

function destroySession() {
  session_unset();
  session_destroy();
}

function redirectTo($destination, $exit_command = null) {
  header('LOCATION:' . $destination);
  if (!is_null($exit_command)) {
    $exit_command();
  }
  exit();
}

function abortIfSessionExpired() {
  if (!isLoggedIn()) {
    echo SESSION_EXPIRED;
    exit();
  }
}

function addCurrentURIAsGETRequest($destination) {
  return addQueryToURI($destination, 'target_uri', substr("$_SERVER[REQUEST_URI]", 1));
}

function getURIFromGETRequest() {
  return isset($_GET['target_uri']) ? urldecode($_GET['target_uri']) : null;
}

function addQueryToURI($uri, $name, $value, $full_url = false, $allow_multiple = false) {
  $url_parts = parse_url($uri);
  if (isset($url_parts['query'])) {
    parse_str($url_parts['query'], $params);
  } else {
    $params = array();
  }

  if ($allow_multiple) {
    $params[$name][] = $value;
  } else {
    $params[$name] = $value;
  }

  $url_parts['query'] = http_build_query($params);

  $new_uri = $url_parts['path'] . '?' . $url_parts['query'];
  return $full_url ? ($url_parts['scheme'] . '://' . $url_parts['host'] . $uri) : $new_uri;
}
