<?php
require_once(dirname(__DIR__) . '/config/site_config.php');

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

function sendSSEMessage($event, $data) {
  echo "event: $event\n";
  echo "data: $data\n\n";
  while (ob_get_level() > 0) {
    ob_end_flush();
  }
  flush();
}

$inotify_instance = inotify_init();
if (!$inotify_instance) {
  sendSSEMessage('error', 'Could not initialize inotify');
  bm_error('Could not initialize inotify');
}
stream_set_blocking($inotify_instance, true);

$probabilities_filename = 'probabilities.json';
$comm_dir = CONTROL_DIR . '/.comm';
$probabilities_file = "$comm_dir/$probabilities_filename";

if (!file_exists($probabilities_file)) {
  $watch_descriptor = inotify_add_watch($inotify_instance, $comm_dir, IN_CREATE);
  while (true) {
    $events = inotify_read($inotify_instance);
    foreach ($events as $event) {
      if ($event['name'] == $probabilities_filename) {
        break 2;
      }
    }
  }
  inotify_rm_watch($inotify_instance, $watch_descriptor);
}

inotify_add_watch($inotify_instance, $probabilities_file, IN_CLOSE_WRITE);

while (!connection_aborted()) {
  inotify_read($inotify_instance);
  sendSSEMessage('classification_result', file_get_contents($probabilities_file));
}

fclose($inotify_instance);
