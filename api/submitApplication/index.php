<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    Session::init();

    header('Content-Type: application/json');

    if (!isset($_SESSION['user_name'])) {
        echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
        exit;
    }

    $uuid = $_GET['uuid'] ?? null;
    $jobID = $_GET['jobID'] ?? null;

    if (!$uuid || !$jobID) {
        echo json_encode(["success" => false, "message" => "Missing uuid or jobID"]);
        exit;
    }

    try {
        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data.sqlite');

        $stmt = $db->prepare('INSERT INTO payments_and_status (UUID, jobID, applicationState, paymentState)
                                VALUES (:uuid, :jobID, "Applied", "Unknown")');
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $stmt->bindValue(':jobID', $jobID, SQLITE3_TEXT);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Submitted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to submit."]);
        }

        $db->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
    }
