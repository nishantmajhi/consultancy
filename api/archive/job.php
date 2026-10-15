<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Api.php';
    Session::init();

    header("Content-Type: application/json");

    Api::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);
    $jobID = $_GET['jobID'] ?? null;
    $remarks = $input['remarks'] ?? null;

    if (!$jobID) {
        http_response_code(400);
        echo json_encode(["error" => "Job ID is required"]);
        exit;
    }

    try {
        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data.sqlite');

        $stmt = $db->prepare("UPDATE jobs SET visibility = 'Archived', remarks = :remarks WHERE id = :jobID");
        $stmt->bindValue(':jobID', $jobID, SQLITE3_TEXT);
        $stmt->bindValue(':remarks', $remarks, SQLITE3_TEXT);

        if ($stmt->execute()) {
            echo json_encode(["success" => "Job archived successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to archive job"]);
        }

        $db->close();
    } catch (Exception $e) {
        Api::serverError($e);
    }
