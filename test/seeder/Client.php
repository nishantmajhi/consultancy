<?php
namespace Seeder;

require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'seed' . DIRECTORY_SEPARATOR . 'Client.php';
use Seed\ClientData;

use SQLite3;

class ClientSeeder {
    public static function seed() {
        $clients = ClientData::get();
        
        $db = new SQLite3(__DIR__ . '/../../db/data.sqlite');
        $query = "CREATE TABLE IF NOT EXISTS clients (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            address TEXT NOT NULL,
            mobileNumber TEXT NOT NULL,
            email TEXT,
            gender TEXT DEFAULT 'Prefer Not Say' CHECK (gender IN ('Male', 'Female', 'Prefer Not Say')),
            bikeLicense TEXT DEFAULT 'no' CHECK (bikeLicense IN ('yes', 'no')),
            jobPreferences TEXT,
            documents TEXT,
            profilePic TEXT,
            visibility TEXT NOT NULL DEFAULT 'Normal' CHECK (visibility IN ('Normal', 'Archived', 'Deleted')),
            submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            remarks TEXT NULL
        )";
        $db->exec($query);

        $query = "INSERT OR IGNORE INTO clients (id, name, gender, address, mobileNumber, email, bikeLicense, jobPreferences, documents, profilePic) 
                VALUES (:id, :name, :gender, :address, :mobileNumber, :email, :bikeLicense, :jobPreferences, :documents, :profilePic)";
        $stmt = $db->prepare($query);

        $uploadBaseDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        $documentsDir  = $uploadBaseDir . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR;
        $profilePicDir = $uploadBaseDir . DIRECTORY_SEPARATOR . 'profilePic' . DIRECTORY_SEPARATOR;
        
        if (!file_exists($uploadBaseDir)) {
            mkdir($uploadBaseDir, 0777, true);
        }
        if (!file_exists($documentsDir)) {
            mkdir($documentsDir, 0777, true);
        }
        if (!file_exists($profilePicDir)) {
            mkdir($profilePicDir, 0777, true);
        }
        
        $documents = [];
        for($i=0 ; $i<12; $i++) {
            $documents[$i] = $uploadBaseDir . 'defaultDoc.png';
        }
        $documentsJSON = json_encode($documents);
        $profilePic = $uploadBaseDir . 'defaultUser.webp';
        
        $visibilityOptions = ['Normal', 'Archived', 'Deleted'];
        $applicationStates = ['Unknown', 'Applied', 'Interview', 'Accepted', 'Rejected'];
        $paymentStates = ['Unknown', 'Received', 'Not Paid', 'Cancelled'];

        foreach ($clients as $client) {
            $visibility = $visibilityOptions[array_rand($visibilityOptions)];
            $applicationState = $applicationStates[array_rand($applicationStates)];
            $paymentState = $paymentStates[array_rand($paymentStates)];

            $stmt->bindValue(':id', $client['id'], SQLITE3_TEXT);
            $stmt->bindValue(':name', $client['name'], SQLITE3_TEXT);
            $stmt->bindValue(':gender', $client['gender'], SQLITE3_TEXT);
            $stmt->bindValue(':address', $client['address'], SQLITE3_TEXT);
            $stmt->bindValue(':mobileNumber', $client['mobileNumber'], SQLITE3_TEXT);
            $stmt->bindValue(':email', $client['email'], SQLITE3_TEXT);
            $stmt->bindValue(':bikeLicense', $client['bikeLicense'], SQLITE3_TEXT);
            $stmt->bindValue(':jobPreferences', json_encode($client['jobPreferences']), SQLITE3_TEXT);
            $stmt->bindValue(':documents', $documentsJSON, SQLITE3_TEXT);
            $stmt->bindValue(':profilePic', $profilePic, SQLITE3_TEXT);
            $stmt->execute();
        }

        $db->close();
        return "✅ Clients seeded successfully.\n";
    }
}
