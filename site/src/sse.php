<?php
require_once(dirname(__DIR__) . '/config/error_config.php');

function sendSSEHeaders() {
  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache');
}

function sendSSEMessage($event, $data = null) {
  echo "event: $event\n";
  echo "data: $data\n\n";
  while (ob_get_level() > 0) {
    ob_end_flush();
  }
  flush();
}
