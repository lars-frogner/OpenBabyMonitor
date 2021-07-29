<?php
require_once(__DIR__ . '/path_config.php');
require_once(SRC_PATH . '/io.php');

$envvars = readLines(ENV_FILE_PATH);

// Create string with environment variable assignment for prepending to commands in exec()
define('ENVVAR_ASSIGNMENT', join('; ', $envvars) . '; ');

// Include all environment variables in PHP scope
foreach ($envvars as $assignment) {
  putenv($assignment);
}
