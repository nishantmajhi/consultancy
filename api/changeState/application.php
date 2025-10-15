<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    Session::init();

    header("Content-Type: application/json");

    if (!isset($_SESSION['user_name'])) {
        echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);

    $state = $input['state'] ?? null;
    $id = $_GET['id'] ?? null;

    if (!$state || !$id) {
        http_response_code(400);
        echo json_encode(["error" => "'state' in body and 'id' in query string are both required"]);
        exit;
    }

    try {
        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data.sqlite');

        $stmt = $db->prepare("UPDATE payments_and_status SET applicationState = :state WHERE id = :id");
        $stmt->bindValue(':state', $state, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

        if ($stmt->execute()) {
            echo json_encode(["success" => "State changed successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to change the state"]);
        }

        $db->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
    }
