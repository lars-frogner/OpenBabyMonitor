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

$comm_dir = CONTROL_DIR . '/.comm';
$probabilities_filename = 'probabilities.json';
$probabilities_file = "$comm_dir/$probabilities_filename";
$notification_filename = 'notification.txt';
$notification_file = "$comm_dir/$notification_filename";

if (!file_exists($probabilities_file)) {
  $msg = "Probabilities file does not exist: $probabilities_file";
  sendSSEMessage('error', $msg);
  bm_error($msg);
}
if (!file_exists($notification_file)) {
  $msg = "Notification file does not exist: $notification_file";
  sendSSEMessage('error', $msg);
  bm_error($msg);
}

$probabilities_descriptor = inotify_add_watch($inotify_instance, $probabilities_file, IN_CLOSE_WRITE);
$notification_descriptor = inotify_add_watch($inotify_instance, $notification_file, IN_CLOSE_WRITE);

while (!connection_aborted()) {
  $events = inotify_read($inotify_instance);
  foreach ($events as $event) {
    if ($event['wd'] == $probabilities_descriptor) {
      sendSSEMessage('probabilities', file_get_contents($probabilities_file));
    } elseif ($event['wd'] == $notification_descriptor) {
      sendSSEMessage('notification', file_get_contents($notification_file));
    }
  }
}

fclose($inotify_instance);
