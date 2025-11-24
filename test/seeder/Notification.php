<?php
namespace Seeder;

use SQLite3;

class NotificationSeeder {
    public static function seed() {
        $db = new SQLite3(__DIR__ . '/../../db/notifications.sqlite');
        $query = "CREATE TABLE IF NOT EXISTS notifications (
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
                END;";
        $db->exec($query);

        for ($i = 1; $i <= 20; $i++) {
            $title = "Hello There!";
            $message = "This is notification $i.";

            $stmt = $db->prepare('INSERT INTO notifications (title, message) VALUES (:title, :message)');
            $stmt->bindValue(':title', $title, SQLITE3_TEXT);
            $stmt->bindValue(':message', $message, SQLITE3_TEXT);
            $stmt->execute();
        }

        $db->close();
        return "✅ Notifications seeded successfully.\n";
    }
}
