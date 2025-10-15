<?php
namespace Seeder;

use SQLite3;

class AdminSeeder {
    public static function seed() {
        $db = new SQLite3(__DIR__ . '/../../db/login.sqlite');
        $db->exec("CREATE TABLE IF NOT EXISTS credentials (
        username TEXT PRIMARY KEY,
        hashed_secret TEXT NOT NULL
        )");

        $username = 'Epuser';
        $password = 'userEp';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT OR IGNORE INTO credentials (username, hashed_secret) VALUES (:username, :hashed)");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':hashed', $hashedPassword, SQLITE3_TEXT);
        $stmt->execute();

        $db->close();
        return "✅ Admin credentials seeded successfully.\n";
    }
}
