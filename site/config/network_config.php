<?php
require_once(__DIR__ . '/error_config.php');
require_once(__DIR__ . '/path_config.php');

$output = null;
$result_code = null;
exec(SERVER_CONTROL_DIR . '/connected_to_external_network.sh', $output, $result_code);
if ($result_code != 0) {
  bm_error("Check for connection to external network failed with error code $result_code:\n" . join("\n", $output));
}
define('CONNECTED_TO_EXTERNAL_NETWORK', $output[0] == '1');

$output = null;
$result_code = null;
exec(SERVER_CONTROL_DIR . '/access_point_active.sh', $output, $result_code);
if ($result_code != 0) {
  bm_error("Check for access point status failed with error code $result_code:\n" . join("\n", $output));
}
define('ACCESS_POINT_ACTIVE', $output[0] == '1');
