<?php

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Api.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Uploads.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Clients.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'Schema.php';

Session::init();
Api::begin();
Api::requireAuth();
Api::requireMethod('POST');
Api::requireCsrf();

$appRoot = dirname(__DIR__, 2);

/**
 * Files are written before the row exists, so anything already on disk has to
 * be removed if the insert fails. Without this a rejected submission leaves
 * orphaned documents in the upload directory with nothing referencing them.
 */
$writtenFiles = [];

try {
    $fields = Clients::validateSubmission($_POST);

    if (!$fields['ok']) {
        Api::fail($fields['error'], 422);
    }

    $client = $fields['values'];

    $documentFiles = Uploads::normaliseMultiple($_FILES['files'] ?? []);

    if (count($documentFiles) > Uploads::MAX_FILES) {
        Api::fail(sprintf('At most %d documents may be uploaded.', Uploads::MAX_FILES), 422);
    }

    if ($documentFiles === []) {
        Api::fail('At least one supporting document is required.', 422);
    }

    if (!isset($_FILES['profilePicture']) || (int) $_FILES['profilePicture']['error'] === UPLOAD_ERR_NO_FILE) {
        Api::fail('A profile picture is required.', 422);
    }

    $clientId = Clients::generateId($client['name']);

    // Paths are stored relative to the application root so they survive the
    // application being moved or served from a subdirectory. The previous
    // version stored absolute filesystem paths, which leaked the server's
    // directory layout to the browser and could never be used as a URL.
    $documentsRelative  = 'uploads/' . $clientId . '/documents';
    $profilePicRelative = 'uploads/' . $clientId . '/profilePic';

    $documentsDir  = $appRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $documentsRelative);
    $profilePicDir = $appRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $profilePicRelative);

    $documentPaths = [];

    foreach ($documentFiles as $file) {
        $stored = Uploads::store($file, $documentsDir);

        if (!$stored['ok']) {
            throw new RuntimeException($stored['error'], 422);
        }

        $writtenFiles[] = $documentsDir . DIRECTORY_SEPARATOR . $stored['storedName'];
        $documentPaths[] = $documentsRelative . '/' . $stored['storedName'];
    }

    $storedPicture = Uploads::store($_FILES['profilePicture'], $profilePicDir, true);

    if (!$storedPicture['ok']) {
        throw new RuntimeException($storedPicture['error'], 422);
    }

    $writtenFiles[] = $profilePicDir . DIRECTORY_SEPARATOR . $storedPicture['storedName'];
    $profilePicPath = $profilePicRelative . '/' . $storedPicture['storedName'];

    $db = new SQLite3($appRoot . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data.sqlite');
    $db->enableExceptions(true);
    Schema::ensureClients($db);

    $stmt = $db->prepare(
        'INSERT INTO clients (id, name, address, mobileNumber, email, gender, bikeLicense, jobPreferences, documents, profilePic)
         VALUES (:id, :name, :address, :mobileNumber, :email, :gender, :bikeLicense, :jobPreferences, :documents, :profilePic)'
    );

    $stmt->bindValue(':id', $clientId, SQLITE3_TEXT);
    $stmt->bindValue(':name', $client['name'], SQLITE3_TEXT);
    $stmt->bindValue(':address', $client['address'], SQLITE3_TEXT);
    $stmt->bindValue(':mobileNumber', $client['mobileNumber'], SQLITE3_TEXT);
    $stmt->bindValue(':email', $client['email'], SQLITE3_TEXT);
    $stmt->bindValue(':gender', $client['gender'], SQLITE3_TEXT);
    $stmt->bindValue(':bikeLicense', $client['bikeLicense'], SQLITE3_TEXT);
    $stmt->bindValue(':jobPreferences', json_encode($client['jobPreferences']), SQLITE3_TEXT);
    $stmt->bindValue(':documents', json_encode($documentPaths), SQLITE3_TEXT);
    $stmt->bindValue(':profilePic', $profilePicPath, SQLITE3_TEXT);

    $stmt->execute();
    $db->close();

    Api::send(['status' => 'Client added successfully.', 'id' => $clientId], 201);
} catch (RuntimeException $e) {
    Clients::discardFiles($writtenFiles);
    Api::fail($e->getMessage(), $e->getCode() === 422 ? 422 : 500);
} catch (Throwable $e) {
    Clients::discardFiles($writtenFiles);
    Api::serverError($e, 'add client');
}
