<?php
$file_size = intval($_GET['file_size']);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: application/octet-stream');

require_once(dirname(__DIR__) . '/config/path_config.php');
$file_path = SITE_PATH . "/bandwidth_test/test_data.bin";

header("Content-Length: $file_size");

$start_time = microtime(true);

$f = fopen($file_path, 'rb');
$data = fread($f, $file_size);
fclose($f);

$read_time = (microtime(true) - $start_time) * 1e3; // [ms]

echo pack('g', $read_time);
echo $data;
