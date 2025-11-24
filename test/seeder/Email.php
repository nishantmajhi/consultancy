<?php
namespace Seeder;

require dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'seed' . DIRECTORY_SEPARATOR . 'Email.php';
use Seed\EmailData;

use SQLite3;

class EmailSeeder {
    public static function seed() {
        $emails = EmailData::get();
        
        $db = new SQLite3(__DIR__ . '/../../db/waitlist.sqlite');
        $query = "CREATE TABLE IF NOT EXISTS submitted_emails (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            submittedDate TEXT NOT NULL
        )";
        $db->exec($query);
        
        $stmt = $db->prepare("
        INSERT INTO submitted_emails (email, submittedDate)
        VALUES (:email, :submittedDate)
        ON CONFLICT(email) DO UPDATE SET submittedDate = :submittedDate
        ");
        
        foreach($emails as $email) {
            $submittedDate = date('Y-m-d H:i:s');
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':submittedDate', $submittedDate, SQLITE3_TEXT);
            $stmt->execute();
        }
        
        $db->close();
        return "✅ Emails seeded successfully.\n";
    }
}
