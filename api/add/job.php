<?php

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Api.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Jobs.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'Schema.php';

Session::init();
Api::begin();
Api::requireAuth();
Api::requireMethod('POST');
Api::requireCsrf();

try {
    $fields = Jobs::validateSubmission($_POST);

    if (!$fields['ok']) {
        Api::fail($fields['error'], 422);
    }

    $job = $fields['values'];

    $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data.sqlite');
    $db->enableExceptions(true);

    Schema::ensureJobs($db);
    Schema::migrateJobsVisibilityDefault($db);

    $jobId = Jobs::generateId($job['companyName'], $job['minimumSalary']);

    // visibility is set explicitly rather than left to the column default.
    // Databases created before the default was corrected still carry the
    // lowercase value that fails the column's own CHECK constraint, so naming
    // it here is what makes this endpoint work against an existing database as
    // well as a freshly created one.
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
    $db->close();

    Api::send(['status' => 'Job added successfully.', 'id' => $jobId], 201);
} catch (Throwable $e) {
    Api::serverError($e, 'add job');
}
