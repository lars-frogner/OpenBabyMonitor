<?php
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(dirname(__DIR__) . '/config/env_config.php');
require_once(SRC_DIR . '/session.php');
redirectIfLoggedOut('index.php');

function readLog($log_file_path) {
  $rotated_log_file_path = "$log_file_path.1";
  $log = '';
  if (file_exists($rotated_log_file_path)) {
    $text = file_get_contents($rotated_log_file_path);
    if ($text === false) {
      bm_error("Could not read log file $rotated_log_file_path");
    }
    $log = "$text\n";
  }
  if (file_exists($log_file_path)) {
    $text = file_get_contents($log_file_path);
    if ($text === false) {
      bm_error("Could not read log file $log_file_path");
    }
    $log = $log . $text;
  } else {
    bm_error("Log file $log_file_path does not exist");
  }
  return $log;
}

$zip_filename = 'babymonitor_logs.zip';
$zip_path = tempnam(sys_get_temp_dir(), $zip_filename);
$zip = new ZipArchive();
$error_code = $zip->open($zip_path, ZIPARCHIVE::CREATE | ZipArchive::OVERWRITE);
if ($error_code !== true) {
  bm_error("Creating archive for logs at $zip_path failed with error code $error_code");
}
if ($zip->addFromString('apache2.log', readLog('/var/log/apache2/error.log')) === false) {
  bm_error('Adding apache2 log file to zip archive failed');
}
if ($zip->addFromString('babymonitor.log', readLog(getenv('BM_SERVER_LOG_PATH'))) === false) {
  bm_error('Adding babymonitor log file to zip archive failed');
}
if ($zip->close() === false) {
  bm_error("Closing $zip_path failed");
}

header('Content-Type: application/zip');
header("Content-Disposition: attachment; filename=$zip_filename");
header('Content-Length: ' . filesize($zip_path));
readfile($zip_path);
