<?php
require_once(dirname(dirname(__DIR__)) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

header('Content-Type: application/json');

// Handle DELETE — clear all events
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!$_DATABASE->query("DELETE FROM `events`")) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to clear events']);
        exit;
    }
    echo json_encode(['ok' => true]);
    exit;
}

$days = max(1, min(90, intval($_GET['days'] ?? 7)));
$since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

// Raw events for list (most recent first, limit 200)
$stmt = $_DATABASE->prepare(
    "SELECT id, event_type AS type, started_at, ended_at,
            TIMESTAMPDIFF(SECOND, started_at, ended_at) AS duration,
            confidence
     FROM `events`
     WHERE started_at >= ?
     ORDER BY started_at DESC
     LIMIT 200"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => $_DATABASE->error]);
    exit;
}
$stmt->bind_param('s', $since);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats (today only)
$today = date('Y-m-d') . ' 00:00:00';
$stmt = $_DATABASE->prepare(
    "SELECT
        SUM(event_type IN ('cry','bad_and_good','bad_or_good')) AS cry,
        SUM(event_type = 'babble') AS babble,
        SUM(event_type = 'sound')  AS sound,
        COUNT(*) AS total
     FROM `events`
     WHERE started_at >= ?"
);
$stmt->bind_param('s', $today);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Distribution across selected period
$stmt = $_DATABASE->prepare(
    "SELECT
        SUM(event_type IN ('cry','bad_and_good','bad_or_good')) AS cry,
        SUM(event_type = 'babble') AS babble,
        SUM(event_type = 'sound')  AS sound
     FROM `events`
     WHERE started_at >= ?"
);
$stmt->bind_param('s', $since);
$stmt->execute();
$distribution = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Timeline: events grouped by day (or hour if <=1 day)
if ($days <= 1) {
    $groupFmt  = '%Y-%m-%d %H:00';
    $labelFmt  = 'H:i';
    $interval  = new DateInterval('PT1H');
    $dateStart = new DateTime(date('Y-m-d H:00:00'));
    $dateStart->modify('-23 hours');
    $dateEnd   = new DateTime(date('Y-m-d H:00:00'));
} else {
    $groupFmt  = '%Y-%m-%d';
    $labelFmt  = 'D d/m';
    $interval  = new DateInterval('P1D');
    $dateStart = new DateTime(date('Y-m-d', strtotime("-{$days} days")));
    $dateEnd   = new DateTime(date('Y-m-d'));
}

$stmt = $_DATABASE->prepare(
    "SELECT DATE_FORMAT(started_at, '{$groupFmt}') AS period,
            SUM(event_type IN ('cry','bad_and_good','bad_or_good')) AS cry,
            SUM(event_type = 'babble') AS babble,
            SUM(event_type = 'sound')  AS sound
     FROM `events`
     WHERE started_at >= ?
     GROUP BY period
     ORDER BY period ASC"
);
$stmt->bind_param('s', $since);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Build a complete date series (fill gaps with zeros)
$rowMap = [];
foreach ($rows as $r) $rowMap[$r['period']] = $r;

$timeline = [];
$cur = clone $dateStart;
while ($cur <= $dateEnd) {
    $key   = $cur->format($days <= 1 ? 'Y-m-d H:00' : 'Y-m-d');
    $label = $cur->format($labelFmt);
    $row   = $rowMap[$key] ?? [];
    $timeline[] = [
        'label'  => $label,
        'cry'    => intval($row['cry']    ?? 0),
        'babble' => intval($row['babble'] ?? 0),
        'sound'  => intval($row['sound']  ?? 0),
    ];
    $cur->add($interval);
}

echo json_encode([
    'events'       => $events,
    'stats'        => $stats,
    'distribution' => $distribution,
    'timeline'     => $timeline,
]);
