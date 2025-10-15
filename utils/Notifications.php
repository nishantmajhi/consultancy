<?php

class Notification {
    private $db;

    public function __construct($title, $message) {
        $dbPath = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'notifications.sqlite';

        try {
            $this->db = new SQLite3($dbPath);
            $this->setupDatabase();
            $this->insertNotification($title, $message);
        } catch (Exception $e) {
            error_log('Notification Error: ' . $e->getMessage());
        }
    }

    public function __destruct()
    {
        if ($this->db) $this->db->close();
    }


    private function setupDatabase(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS notification_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                notification_id INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS notification_user_tracking (
                user_id TEXT PRIMARY KEY,
                last_seen_notification_id INTEGER DEFAULT 0
            );

            CREATE TRIGGER IF NOT EXISTS queue_new_notification
            AFTER INSERT ON notifications
            BEGIN
                INSERT INTO notification_queue (notification_id)
                VALUES (NEW.id);
            END;
        ");
    }

    private function insertNotification($title, $message): void {
        $stmt = $this->db->prepare('INSERT INTO notifications (title, message) VALUES (:title, :message)');
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':message', $message, SQLITE3_TEXT);
        $stmt->execute();
    }
}
