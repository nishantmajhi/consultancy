<?php
namespace Seeder;


require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'Schema.php';

use Schema;
require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Job.php';
use Seed\JobData;

use SQLite3;

class JobSeeder {
    public static function seed() {
        $jobs = JobData::get();
        
        $db = new SQLite3(__DIR__ . '/../../db/data.sqlite');
        \Schema::ensureJobs($db);

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
