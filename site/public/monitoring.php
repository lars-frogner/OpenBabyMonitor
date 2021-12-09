<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/env_config.php');
require_once(dirname(__DIR__) . '/config/monitoring_config.php');
require_once(SRC_DIR . '/sse.php');

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

function queryVitals() {
  sendSSEMessage('temperature', measureTemperature());
  $throttled_status = getThrottled();
  if ($throttled_status & UNDER_VOLTAGE_FLAG) {
    bm_warning_to_file('Detected under-voltage', getenv('BM_SERVER_LOG_PATH'));
    sendSSEMessage('under_voltage');
  } elseif ($throttled_status & (FREQUENCY_CAPPED_FLAG | CURRENTLY_THROTTLED_FLAG)) {
    bm_warning_to_file('Detected frequency capping or throttling due to overheating', getenv('BM_SERVER_LOG_PATH'));
    sendSSEMessage('overheat');
  }
  return microtime(true);
}

function sendPing() {
  sendSSEMessage('ping');
  return microtime(true);
}

sendSSEHeaders();

$inotify_instance = inotify_init();
if (!$inotify_instance) {
  sendSSEMessage('error', 'Could not initialize inotify');
  bm_error('Could not initialize inotify');
}
stream_set_blocking($inotify_instance, false);

define('MODE_SIGNAL_FILE_STEM', getenv('BM_MODE_SIGNAL_FILE_STEM'));
$descriptors = array();
foreach (array('standby', 'listen', 'audiostream', 'videostream') as $mode_name) {
  $signal_file =  MODE_SIGNAL_FILE_STEM . ".$mode_name";

  if (!file_exists($signal_file)) {
    $msg = "Signal file does not exist: $signal_file";
    sendSSEMessage('error', $msg);
    bm_error($msg);
  }

  $descriptors[$mode_name] = inotify_add_watch($inotify_instance, $signal_file, IN_ATTRIB);
}

$last_vitals_query_time = queryVitals();
$last_ping_time = sendPing();

while (!connection_aborted()) {
  $events = inotify_read($inotify_instance);
  if ($events !== false) {
    foreach ($events as $event) {
      foreach ($descriptors as $mode_name => $descriptor) {
        if ($event['wd'] == $descriptor) {
          sendSSEMessage('mode', $mode_name);
        }
      }
    }
  }
  usleep(MODE_QUERY_INTERVAL_MICROSEC);

  if (microtime(true) - $last_vitals_query_time >= VITALS_QUERY_INTERVAL) {
    $last_vitals_query_time = queryVitals();
  }
  if (microtime(true) - $last_ping_time >= PING_INTERVAL) {
    $last_ping_time = sendPing();
  }
}

fclose($inotify_instance);
