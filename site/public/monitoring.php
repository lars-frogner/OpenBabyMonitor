<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/env_config.php');
require_once(dirname(__DIR__) . '/config/monitoring_config.php');
require_once(SRC_DIR . '/sse.php');

sendSSEHeaders();

function measureTemperature() {
  $output = null;
  $result_code = null;
  exec('sudo vcgencmd measure_temp', $output, $result_code);
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
  exec('sudo vcgencmd get_throttled', $output, $result_code);
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
    bm_warning_to_file('Detected under-voltage', getenv('BM_SERVER_LOG_PATH'));
    sendSSEMessage('under_voltage');
  } elseif ($throttled_status & (FREQUENCY_CAPPED_FLAG | CURRENTLY_THROTTLED_FLAG)) {
    bm_warning_to_file('Detected frequency capping or throttling due to overheating', getenv('BM_SERVER_LOG_PATH'));
    sendSSEMessage('overheat');
  }
  sleep(MONITORING_QUERY_INTERVAL);
}
