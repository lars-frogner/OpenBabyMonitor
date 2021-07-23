<?php
require_once(__DIR__ . '/config.php');
require_once(SRC_PATH . '/database.php');

$_DATABASE_INFO = $_CONFIG['database'];
$_DATABASE = connectToDatabase($_DATABASE_INFO['account']['host'], $_DATABASE_INFO['account']['user'], $_DATABASE_INFO['account']['password'], $_DATABASE_INFO['name']);
