<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
abortIfSessionExpired();

echo restartCurrentMode($_DATABASE);
