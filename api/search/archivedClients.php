<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Api.php';
    Session::init();

    header('Content-Type: application/json');

    Api::requireAuth();

    try {
        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data.sqlite');
        $sql = 'SELECT * FROM clients WHERE visibility="Archived"';
        $result = $db->query($sql);
        
        $clients = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $row['jobPreferences'] = json_decode($row['jobPreferences'], true);
            $clients[] = $row;
        }
        
        require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'FRAS.php';
        $query = $_GET['client'] ?? '';
        
        $search = new FRAS($query, $clients);
        $clients = $search->look('name');

        echo json_encode(["clients" => $clients]);
        $db->close();
    } catch (Exception $e) {
        Api::serverError($e);
    }
