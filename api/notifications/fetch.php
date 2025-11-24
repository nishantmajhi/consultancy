<?php
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
Session::init();

header('Content-Type: application/json');

if (!isset($_SESSION['user_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$userId = $_SESSION['user_name'];
$dbPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'notifications.sqlite';

try {
    $db = new SQLite3($dbPath);
    $lastSeenId = getLastSeenNotificationId($db, $userId);
    $notifications = fetchNewNotifications($db);
    $maxId = getMaxNotificationId($notifications, $lastSeenId);

    if ($maxId > $lastSeenId) {
        updateLastSeenNotificationId($db, $userId, $maxId);
    }

    echo json_encode(['status' => 'success', 'notifications' => $notifications]);
    $db->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function getLastSeenNotificationId(SQLite3 $db, string $userId): int {
    $stmt = $db->prepare('SELECT last_seen_notification_id FROM notification_user_tracking WHERE user_id = :user_id');
    $stmt->bindValue(':user_id', $userId, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return $result ? intval($result['last_seen_notification_id']) : 0;
}

function fetchNewNotifications(SQLite3 $db): array {
    $results = $db->query('SELECT id, title, message FROM notifications ORDER BY id DESC');
    $notifications = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $notifications[] = $row;
    }
    return $notifications;
}

function getMaxNotificationId(array $notifications, int $default): int {
    $maxId = $default;
    foreach ($notifications as $notification) {
        if ($notification['id'] > $maxId) {
            $maxId = $notification['id'];
        }
    }
    return $maxId;
}

function updateLastSeenNotificationId(SQLite3 $db, string $userId, int $maxId): void {
    $stmt = $db->prepare('INSERT INTO notification_user_tracking (user_id, last_seen_notification_id)
        VALUES (:user_id, :max_id)
        ON CONFLICT(user_id) DO UPDATE SET last_seen_notification_id = :max_id');
    $stmt->bindValue(':user_id', $userId, SQLITE3_TEXT);
    $stmt->bindValue(':max_id', $maxId, SQLITE3_INTEGER);
    $stmt->execute();
}
