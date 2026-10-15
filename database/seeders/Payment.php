<?php
namespace Seeder;


require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'Schema.php';

use Schema;
require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Client.php';
use Seed\ClientData;

require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Job.php';
use Seed\JobData;

use SQLite3;

class PaymentSeeder {
    public static function seed() {
        $clients = ClientData::get();
        $jobs = JobData::get();
        
        $db = new SQLite3(__DIR__ . '/../../db/data.sqlite');
        \Schema::ensurePaymentsAndStatus($db);

        $stmt = $db->prepare("INSERT OR IGNORE INTO payments_and_status (UUID, jobID, applicationState, paymentState)
                            VALUES (:UUID, :jobID, :applicationState, :paymentState)");

        $applicationStates = ['Unknown', 'Applied', 'Interview', 'Accepted', 'Rejected'];
        $paymentStates = ['Unknown', 'Received', 'Not Paid', 'Cancelled'];

        foreach ($clients as $client) {
            $matchedJob = null;

            foreach ($client['jobPreferences'] as $preference) {
                $preferenceLower = strtolower($preference);

                foreach ($jobs as $job) {
                    $skillsLower = array_map('strtolower', $job['preferredSkills']);

                    foreach ($skillsLower as $skill) {
                        if (strpos($skill, $preferenceLower) !== false || strpos($preferenceLower, $skill) !== false) {
                            $matchedJob = $job;
                            break 3;
                        }
                    }
                }
            }

            if (!$matchedJob) {
                $matchedJob = $jobs[array_rand($jobs)];
            }

            $stmt->bindValue(':UUID', $client['id'], SQLITE3_TEXT);
            $stmt->bindValue(':jobID', $matchedJob['id'], SQLITE3_TEXT);
            $stmt->bindValue(':applicationState', $applicationStates[array_rand($applicationStates)], SQLITE3_TEXT);
            $stmt->bindValue(':paymentState', $paymentStates[array_rand($paymentStates)], SQLITE3_TEXT);

            $stmt->execute();
        }

        $db->close();
        return "✅ Payments & Status seeded successfully.\n";
    }
}
