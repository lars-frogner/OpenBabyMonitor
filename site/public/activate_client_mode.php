<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
logout('index.php', function () {
  executeServerControlAction('activate_client_mode');
});
