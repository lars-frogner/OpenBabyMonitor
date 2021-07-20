<?php
require_once('config.php');

$_MODES_INFO = $_CONFIG['modes'];
define('STANDBY_MODE', $_MODES_INFO['standby']);
define('LISTEN_MODE', $_MODES_INFO['listen']);
define('AUDIOSTREAM_MODE', $_MODES_INFO['audiostream']);
define('VIDEOSTREAM_MODE', $_MODES_INFO['videostream']);
