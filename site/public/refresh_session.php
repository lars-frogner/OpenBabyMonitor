<?php
session_start();
require_once(dirname(__DIR__) . '/config/error_config.php');

if (isset($_SESSION['login'])) {
  $_SESSION['login'] = $_SESSION['login'];
}
