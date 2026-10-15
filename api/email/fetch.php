<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Api.php';
    Session::init();

    header('Content-Type: application/json');

    Api::requireAuth();

    try {
        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'waitlist.sqlite');

        $query = "SELECT email, submittedDate FROM submitted_emails ORDER BY submittedDate DESC";
        $result = $db->query($query);

        $emails = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $emails[] = $row;
        }

        echo json_encode(["emails" => $emails]);
        $db->close();
    } catch (Exception $e) {
        Api::serverError($e);
    }
