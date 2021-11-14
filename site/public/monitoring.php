<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/monitoring_config.php');
require_once(SRC_DIR . '/session.php');
require_once(SRC_DIR . '/sse.php');

redirectIfLoggedOut('index.php');

sendSSEHeaders();

function measureTemperature() {
  $output = null;
  $result_code = null;
  exec('sudo /usr/bin/vcgencmd measure_temp', $output, $result_code);
  if ($result_code != 0) {
    $msg = "Measuring temperature failed with error code $result_code:\n" . join("\n", $output);
    sendSSEMessage('error', $msg);
    bm_error($msg);
  }
  return substr($output[0], 5, strlen($output[0]) - 7);
}

function getThrottled() {
  $output = null;
  $result_code = null;
  exec('sudo /usr/bin/vcgencmd get_throttled', $output, $result_code);
  if ($result_code != 0) {
    $msg = "Getting throttled status failed with error code $result_code:\n" . join("\n", $output);
    sendSSEMessage('error', $msg);
    bm_error($msg);
  }
  return hexdec(substr($output[0], 12));
}

while (!connection_aborted()) {
  sendSSEMessage('temperature', measureTemperature());
  $throttled_status = getThrottled();
  if ($throttled_status & UNDER_VOLTAGE_FLAG) {
    sendSSEMessage('under_voltage');
  }
  if ($throttled_status & FREQUENCY_CAPPED_FLAG) {
    sendSSEMessage('frequency_capped');
  }
  if ($throttled_status & CURRENTLY_THROTTLED_FLAG) {
    sendSSEMessage('throttled');
  }
  if ($throttled_status & SOFT_TEMP_LIM_ACTIVE_FLAG) {
    sendSSEMessage('temp_lim_active');
  }
  sleep(MONITORING_QUERY_INTERVAL);
}
