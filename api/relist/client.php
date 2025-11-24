<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    Session::init();

    header('Content-Type: application/json');

    if (!isset($_SESSION['user_name'])) {
        echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed"]);
        exit;
    }

    if (!isset($_GET['uuid']) || empty($_GET['uuid'])) {
        http_response_code(400);
        echo json_encode(["error" => "UUID is required"]);
        exit;
    }

    $uuid = $_GET['uuid'];

    try {
        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data.sqlite');
        
        $stmt = $db->prepare("SELECT id FROM clients WHERE id = :uuid");
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if (!$result->fetchArray(SQLITE3_ASSOC)) {
            http_response_code(404);
            echo json_encode(["error" => "Client not found"]);
            exit;
        }

        $stmt = $db->prepare("UPDATE clients SET visibility = 'Normal' WHERE id = :uuid");
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        if ($stmt->execute()) {
            echo json_encode(["success" => "Client relisted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to relist the client."]);
        }    

        $db->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
    }

