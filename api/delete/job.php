<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Api.php';
    Session::init();

    header('Content-Type: application/json');

    Api::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed"]);
        exit;
    }

    if (!isset($_GET['jobID']) || empty($_GET['jobID'])) {
        http_response_code(400);
        echo json_encode(["error" => "Job ID is required"]);
        exit;
    }

    $jobID = $_GET['jobID'];

    try {
        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data.sqlite');
        
        $stmt = $db->prepare("SELECT id FROM jobs WHERE id = :jobID");
        $stmt->bindValue(':jobID', $jobID, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if (!$result->fetchArray(SQLITE3_ASSOC)) {
            http_response_code(404);
            echo json_encode(["error" => "Job not found"]);
            exit;
        }

        $stmt = $db->prepare("UPDATE jobs SET visibility = 'Deleted' WHERE id = :jobID");
        $stmt->bindValue(':jobID', $jobID, SQLITE3_TEXT);
        if ($stmt->execute()) {
            echo json_encode(["success" => "Job removed successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to remove the job."]);
        }    

        $db->close();
    } catch (Exception $e) {
        Api::serverError($e);
    }
