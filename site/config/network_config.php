<?php
require_once(__DIR__ . '/error_config.php');
require_once(__DIR__ . '/path_config.php');
require_once(__DIR__ . '/env_config.php');
require_once(__DIR__ . '/config.php');

$output = null;
$result_code = null;
exec(SERVERCONTROL_DIR . '/get_wireless_operating_mode.sh', $output, $result_code);
if ($result_code != 0) {
  bm_error("Check for wireless operating mode failed with error code $result_code:\n" . join("\n", $output));
}

$_NETWORK_INFO = $_CONFIG['network'];

define('NETWORK_QUERY_INTERVAL', intval($_NETWORK_INFO['network_query_interval'] * 1e6)); // In microseconds
define('NETWORK_SWITCH_TIMEOUT', intval($_NETWORK_INFO['network_switch_timeout'] * 1e6));

define('ACCESS_POINT_ACTIVE', $output[0] == $_NETWORK_INFO['wireless_modes']['access_point']);
define('CONNECTED_TO_EXTERNAL_NETWORK', $output[0] == $_NETWORK_INFO['wireless_modes']['client']);

define('KNOWN_NETWORKS_TABLE_NAME', 'known_networks');

define('SERVER_HOSTNAME', getenv('BM_HOSTNAME'));
define('AP_SSID', getenv('BM_NW_AP_SSID'));
define('AP_CHANNEL', getenv('BM_NW_AP_CHANNEL'));
define('COUNTRY_CODE', getenv('BM_NW_COUNTRY_CODE'));

define('IS_CLI', http_response_code() === false);

if (!IS_CLI) {
  define('USES_SECURE_PROTOCOL', !(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on'));
  define('PROTOCOL', USES_SECURE_PROTOCOL ? 'https' : 'http');
  $request_uri = substr("$_SERVER[REQUEST_URI]", 1);
  define('URI_WITHOUT_SEARCH', ($request_uri == '') ? 'index.php' : strtok($request_uri, '?'));
  define('URL_WITHOUT_SEARCH', PROTOCOL . "://$_SERVER[HTTP_HOST]/" . URI_WITHOUT_SEARCH);
}
