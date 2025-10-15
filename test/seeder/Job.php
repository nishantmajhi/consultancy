<?php
namespace Seeder;

require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'seed' . DIRECTORY_SEPARATOR . 'Job.php';
use Seed\JobData;

use SQLite3;

class JobSeeder {
    public static function seed() {
        $jobs = JobData::get();
        
        $db = new SQLite3(__DIR__ . '/../../db/data.sqlite');
        $query = "CREATE TABLE IF NOT EXISTS jobs (
            id TEXT PRIMARY KEY,
            companyName TEXT NOT NULL,
            address TEXT NOT NULL,
            mobileNumber TEXT NOT NULL,
            position TEXT NOT NULL,
            minimumSalary INTEGER NOT NULL,
            deadline TEXT,
            skillPreferences TEXT,
            visibility TEXT CHECK(visibility IN ('Normal', 'Archived', 'Deleted')) DEFAULT 'normal',
            submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            remarks TEXT NULL
        )";
        $db->exec($query);

        $query = "INSERT OR IGNORE INTO jobs (id, companyName, address, mobileNumber, position, minimumSalary, deadline, skillPreferences, visibility) 
                VALUES (:id, :companyName, :address, :mobileNumber, :position, :minimumSalary, :deadline, :skillPreferences, :visibility)";
        $stmt = $db->prepare($query);

        
        $visibilityOptions = ['Normal', 'Archived', 'Deleted'];

        foreach ($jobs as $job) {
            $visibility = $visibilityOptions[array_rand($visibilityOptions)];
            
            $stmt->bindValue(':id', $job['id'], SQLITE3_TEXT);
            $stmt->bindValue(':companyName', $job['organization'], SQLITE3_TEXT);
            $stmt->bindValue(':address', $job['address'], SQLITE3_TEXT);
            $stmt->bindValue(':mobileNumber', $job['mobile'], SQLITE3_TEXT);
            $stmt->bindValue(':position', $job['position'], SQLITE3_TEXT);
            $stmt->bindValue(':minimumSalary', $job['salary'], SQLITE3_INTEGER);
            $stmt->bindValue(':deadline', $job['deadline'], SQLITE3_TEXT);
            $stmt->bindValue(':skillPreferences', json_encode($job['preferredSkills']), SQLITE3_TEXT);
            $stmt->bindValue(':visibility', $visibility, SQLITE3_TEXT);
            $stmt->execute();
        }

        $db->close();
        return "✅ Jobs seeded successfully.\n";
    }
}
