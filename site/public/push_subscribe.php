<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $endpoint = $body['endpoint'] ?? '';
    $p256dh   = $body['p256dh']   ?? '';
    $auth     = $body['auth']     ?? '';

    if (empty($endpoint)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing endpoint']);
        exit;
    }

    $stmt = $_DATABASE->prepare(
        "INSERT INTO `push_subscriptions` (endpoint, p256dh, auth)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth)"
    );
    $stmt->bind_param('sss', $endpoint, $p256dh, $auth);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $endpoint = $body['endpoint'] ?? '';
    if (!empty($endpoint)) {
        $stmt = $_DATABASE->prepare("DELETE FROM `push_subscriptions` WHERE endpoint = ?");
        $stmt->bind_param('s', $endpoint);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['ok' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return VAPID public key so client can subscribe
    $vapid_public = getenv('BM_VAPID_PUBLIC_KEY');
    if (empty($vapid_public)) {
        $vapid_file = dirname(dirname(__DIR__)) . '/config/vapid_public.key';
        $vapid_public = file_exists($vapid_file) ? trim(file_get_contents($vapid_file)) : '';
    }
    echo json_encode(['vapidPublicKey' => $vapid_public]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
