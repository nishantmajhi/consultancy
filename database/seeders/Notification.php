<?php
namespace Seeder;


require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'Schema.php';

use Schema;
use SQLite3;

class NotificationSeeder {
    public static function seed() {
        $db = new SQLite3(__DIR__ . '/../../db/notifications.sqlite');
        \Schema::ensureNotifications($db);

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
