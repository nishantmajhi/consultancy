<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Api.php';
    Session::init();

    header('Content-Type: application/json');

    Api::requireAuth();

    try {
        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data.sqlite');
        $query = 'SELECT * FROM jobs WHERE visibility="Normal"';
        $result = $db->query($query);

        $jobs = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $row['preferredSkills'] = json_decode($row['skillPreferences'], true);
            unset($row['skillPreferences']);
            $jobs[] = $row;
        }

        echo json_encode(['jobs' => $jobs]);
        $db->close();
    } catch (Exception $e) {
        Api::serverError($e);
    }
