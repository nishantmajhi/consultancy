<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Jobs.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'Schema.php';

/*
 * End-to-end cover for adding a job, exercised against a temporary database.
 *
 * The unit tests check the validator and the schema separately. This one runs
 * the same sequence api/add/job.php runs — validate the form, build an
 * identifier, insert, read back — because the bug that made this feature
 * unusable lived in the join between those steps rather than in any one of
 * them: the validator was fine, the insert statement was fine, and the column
 * default they met at made every insert fail.
 */

/** Run the sequence api/add/job.php performs, minus the HTTP layer. */
function runAddJobFlow(SQLite3 $db, array $post): array
{
    $fields = Jobs::validateSubmission($post);

    if (!$fields['ok']) {
        return ['ok' => false, 'error' => $fields['error'], 'id' => ''];
    }

    $job   = $fields['values'];
    $jobId = Jobs::generateId($job['companyName'], $job['minimumSalary']);

    $stmt = $db->prepare(
        'INSERT INTO jobs (id, companyName, address, mobileNumber, position, minimumSalary, deadline, skillPreferences, visibility)
         VALUES (:id, :companyName, :address, :mobileNumber, :position, :minimumSalary, :deadline, :skillPreferences, :visibility)'
    );

    $stmt->bindValue(':id', $jobId, SQLITE3_TEXT);
    $stmt->bindValue(':companyName', $job['companyName'], SQLITE3_TEXT);
    $stmt->bindValue(':address', $job['address'], SQLITE3_TEXT);
    $stmt->bindValue(':mobileNumber', $job['mobileNumber'], SQLITE3_TEXT);
    $stmt->bindValue(':position', $job['position'], SQLITE3_TEXT);
    $stmt->bindValue(':minimumSalary', $job['minimumSalary'], SQLITE3_INTEGER);
    $stmt->bindValue(':deadline', $job['deadline'], SQLITE3_TEXT);
    $stmt->bindValue(':skillPreferences', json_encode($job['skillPreferences']), SQLITE3_TEXT);
    $stmt->bindValue(':visibility', 'Normal', SQLITE3_TEXT);
    $stmt->execute();

    return ['ok' => true, 'error' => '', 'id' => $jobId];
}

test('adding a job stores a row that the listing query can find', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);
    Schema::ensureJobs($db);

    $result = runAddJobFlow($db, [
        'name'             => 'Everest Traders',
        'address'          => 'Biratnagar',
        'mobileNumber'     => '+977 98-1234 5678',
        'position'         => 'Delivery Rider',
        'minimumSalary'    => '25,000',
        'deadline'         => '2026-12-31',
        'skillPreferences' => ['Riding', 'Riding', ' Navigation '],
    ]);

    assertTrue($result['ok'], 'the submission is accepted');

    $row = $db->querySingle(
        sprintf("SELECT * FROM jobs WHERE id = '%s'", SQLite3::escapeString($result['id'])),
        true
    );

    assertSame('Everest Traders', $row['companyName'], 'the company name is stored');
    assertSame('9779812345678', $row['mobileNumber'], 'the number is stored as digits');
    assertSame(25000, $row['minimumSalary'], 'the salary is stored as an integer');
    assertSame('Normal', $row['visibility'], 'the row is visible to the listing query');
    assertSame('["Riding","Navigation"]', $row['skillPreferences'], 'skills are deduplicated and trimmed');

    // The listing endpoint selects on visibility, so a row stored with the
    // wrong value would be invisible even if the insert had succeeded.
    $visible = $db->querySingle("SELECT COUNT(*) FROM jobs WHERE visibility = 'Normal'");
    assertSame(1, $visible, 'the new job appears in the normal listing');

    $db->close();
});

test('adding a job works against a database created before the fix', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);

    // Exactly the table an existing deployment already has on disk.
    $db->exec(
        "CREATE TABLE jobs (
            id TEXT PRIMARY KEY,
            companyName TEXT NOT NULL,
            address TEXT NOT NULL,
            mobileNumber TEXT NOT NULL,
            position TEXT NOT NULL,
            minimumSalary INTEGER NOT NULL,
            deadline TEXT,
            skillPreferences TEXT,
            visibility TEXT CHECK(visibility IN ('Normal', 'Archived', 'Deleted')) DEFAULT 'normal',
            submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    );

    // Naming visibility explicitly is what makes this work without a migration.
    $result = runAddJobFlow($db, [
        'name'          => 'Old Deployment Co',
        'address'       => 'Biratnagar',
        'mobileNumber'  => '9812345678',
        'position'      => 'Porter',
        'minimumSalary' => '15000',
    ]);

    assertTrue($result['ok'], 'the insert succeeds on the old schema too');
    assertSame(1, $db->querySingle("SELECT COUNT(*) FROM jobs WHERE visibility = 'Normal'"), 'the row is visible');

    $db->close();
});

test('an invalid job submission is refused before it reaches the database', function (): void {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);
    Schema::ensureJobs($db);

    $result = runAddJobFlow($db, [
        'name'          => '',
        'address'       => 'Biratnagar',
        'mobileNumber'  => '9812345678',
        'position'      => 'Porter',
        'minimumSalary' => '15000',
    ]);

    assertFalse($result['ok'], 'the submission is refused');
    assertSame('A company name is required.', $result['error'], 'the message names the field');
    assertSame(0, $db->querySingle('SELECT COUNT(*) FROM jobs'), 'nothing was written');

    $db->close();
});
