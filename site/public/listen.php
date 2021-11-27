<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(dirname(__DIR__) . '/config/env_config.php');
require_once(dirname(__DIR__) . '/config/error_config.php');
require_once(SRC_DIR . '/settings.php');
require_once(SRC_DIR . '/sse.php');

sendSSEHeaders();

$inotify_instance = inotify_init();
if (!$inotify_instance) {
  sendSSEMessage('error', 'Could not initialize inotify');
  bm_error('Could not initialize inotify');
}
stream_set_blocking($inotify_instance, true);

$comm_dir = CONTROL_DIR . '/.comm';
$sound_level_filename = 'sound_level.dat';
$sound_level_file = "$comm_dir/$sound_level_filename";
$probabilities_filename = 'probabilities.json';
$probabilities_file = "$comm_dir/$probabilities_filename";
$notification_filename = 'notification.txt';
$notification_file = "$comm_dir/$notification_filename";

if (!file_exists($sound_level_file)) {
  $msg = "Sound level file does not exist: $sound_level_file";
  sendSSEMessage('error', $msg);
  bm_error($msg);
}
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

$sound_level_descriptor = inotify_add_watch($inotify_instance, $sound_level_file, IN_CLOSE_WRITE);
$probabilities_descriptor = inotify_add_watch($inotify_instance, $probabilities_file, IN_CLOSE_WRITE);
$notification_descriptor = inotify_add_watch($inotify_instance, $notification_file, IN_CLOSE_WRITE);

while (!connection_aborted()) {
  $events = inotify_read($inotify_instance);
  foreach ($events as $event) {
    if ($event['wd'] == $sound_level_descriptor) {
      sendSSEMessage('sound_level', file_get_contents($sound_level_file));
    } elseif ($event['wd'] == $probabilities_descriptor) {
      $probabilities = file_get_contents($probabilities_file);
      bm_warning($probabilities);
      sendSSEMessage('probabilities', $probabilities);
    } elseif ($event['wd'] == $notification_descriptor) {
      sendSSEMessage('notification', file_get_contents($notification_file));
    }
  }
}

fclose($inotify_instance);
