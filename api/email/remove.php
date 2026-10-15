<?php

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Api.php';

Session::init();
Api::begin();

// This endpoint deletes from the waitlist and is reached from the admin area,
// but it previously had no authentication check at all: Session::init() started
// a session and nothing then asked who the caller was. Any visitor who knew the
// URL could remove any address from the waitlist.
Api::requireAuth();
Api::requireMethod('POST');
Api::requireCsrf();

try {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if ($email === false || $email === null) {
        Api::fail('Invalid email address.', 400);
    }

    $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'waitlist.sqlite');
    $db->enableExceptions(true);

    $stmt = $db->prepare('DELETE FROM submitted_emails WHERE email = :email');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->execute();

    $removed = $db->changes();
    $db->close();

    if ($removed === 0) {
        Api::fail('That address is not on the waitlist.', 404);
    }

    Api::send(['status' => 'Email removed successfully.']);
} catch (Throwable $e) {
    Api::serverError($e, 'remove waitlist email');
}
