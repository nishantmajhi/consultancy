<?php
namespace Seeder;


require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'Schema.php';

use Schema;
require dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Email.php';
use Seed\EmailData;

use SQLite3;

class EmailSeeder {
    public static function seed() {
        $emails = EmailData::get();
        
        $db = new SQLite3(__DIR__ . '/../../db/waitlist.sqlite');
        \Schema::ensureSubmittedEmails($db);
        
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
