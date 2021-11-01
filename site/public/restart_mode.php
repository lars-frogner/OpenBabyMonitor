<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(dirname(__DIR__) . '/config/database_config.php');
require_once(SRC_DIR . '/session.php');
require_once(SRC_DIR . '/control.php');

abortIfSessionExpired();

echo restartCurrentMode($_DATABASE);
