<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(SRC_DIR . '/session.php');
require_once(SRC_DIR . '/control.php');

redirectIfLoggedOut('index.php');

logout('index.php', function () {
  executeServerControlAction('reboot');
});
