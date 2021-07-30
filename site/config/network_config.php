<?php
require_once(__DIR__ . '/error_config.php');
require_once(__DIR__ . '/path_config.php');
require_once(__DIR__ . '/config.php');

$output = null;
$result_code = null;
exec(SERVER_CONTROL_DIR . '/get_wireless_operating_mode.sh', $output, $result_code);
if ($result_code != 0) {
  bm_error("Check for wireless operating mode failed with error code $result_code:\n" . join("\n", $output));
}
define('ACCESS_POINT_ACTIVE', $output[0] == $_CONFIG['wireless_modes']['access_point']);
define('CONNECTED_TO_EXTERNAL_NETWORK', $output[0] == $_CONFIG['wireless_modes']['client']);
