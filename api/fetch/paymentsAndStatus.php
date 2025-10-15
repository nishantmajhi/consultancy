<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    Session::init();

    header('Content-Type: application/json');

    if (!isset($_SESSION['user_name'])) {
        echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
        exit;
    }

    try {
        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data.sqlite');
        $query = '
            SELECT
                payments_and_status.id,
                clients.name,
                jobs.companyName,
                jobs.position,
                payments_and_status.paymentState,
                payments_and_status.applicationState
            FROM 
                payments_and_status
            JOIN 
                clients ON payments_and_status.UUID = clients.id
            JOIN 
                jobs ON payments_and_status.jobID = jobs.id
        ';
        $result = $db->query($query);

        $states = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $states[] = $row;
        }

        echo json_encode(["states" => $states]);
        $db->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
    }
