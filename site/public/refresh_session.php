<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
session_start();

if (isset($_SESSION['login'])) {
  $_SESSION['login'] = $_SESSION['login'];
}
