<?php
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
Session::init();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

if (!isset($_SESSION['user_name'])) {
    sendSSEerror('Unauthorized access');
    exit;
}

$userId = $_SESSION['user_name'];
$dbPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'notifications.sqlite';

set_time_limit(0);
ob_implicit_flush(true);

try {
    $db = new SQLite3($dbPath);
} catch (Exception $e) {
    sendSSEerror('Could not open database');
    exit;
}

do {
    $notifications = fetchQueuedNotifications($db);
    $maxId = 0;

    foreach ($notifications as $notification) {
        sendSSENotification($notification);
        removeFromQueue($db, $notification['queue_id']);
        $maxId = max($maxId, $notification['id']);
        @flush();
    }

    if ($maxId > 0) {
        updateLastSeenNotificationId($db, $userId, $maxId);
    }
} while(false);

echo "event: keepalive\n";
echo "data: {}\n\n";
@flush();

function sendSSEerror(string $message): void {
    echo "event: error\n";
    echo "data: {$message}\n\n";
}

function fetchQueuedNotifications(SQLite3 $db): array {
    $stmt = $db->query('SELECT notification_queue.id AS queue_id,
        notifications.id,
        notifications.title,
        notifications.message
        FROM notification_queue
        JOIN notifications ON notification_queue.notification_id = notifications.id
        ORDER BY notification_queue.id ASC');

    $notifications = [];
    while ($row = $stmt->fetchArray(SQLITE3_ASSOC)) {
        $notifications[] = $row;
    }
    return $notifications;
}

function sendSSENotification(array $notification): void {
    echo "event: notification\n";
    echo "data: " . json_encode([
        'id' => $notification['id'],
        'title' => $notification['title'],
        'message' => $notification['message']
    ]) . "\n\n";
}

function removeFromQueue(SQLite3 $db, int $queueId): void {
    $stmt = $db->prepare('DELETE FROM notification_queue WHERE id = :queue_id');
    $stmt->bindValue(':queue_id', $queueId, SQLITE3_INTEGER);
    $stmt->execute();
}

function updateLastSeenNotificationId(SQLite3 $db, string $userId, int $maxId): void {
    $stmt = $db->prepare('INSERT INTO notification_user_tracking (user_id, last_seen_notification_id)
        VALUES (:user_id, :max_id)
        ON CONFLICT(user_id) DO UPDATE SET last_seen_notification_id = :max_id');
    $stmt->bindValue(':user_id', $userId, SQLITE3_TEXT);
    $stmt->bindValue(':max_id', $maxId, SQLITE3_INTEGER);
    $stmt->execute();
}
