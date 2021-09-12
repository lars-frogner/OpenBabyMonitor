<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

logout('index.php', function () {
  executeServerControlAction('activate_client_mode');
});
