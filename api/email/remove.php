<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    Session::init();

    header('Content-Type: application/json');

    try {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        if (!$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email address.']);
            exit;
        }

        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'waitlist.sqlite');

        $stmt = $db->prepare("DELETE FROM submitted_emails WHERE email = :email");  
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "Email removed successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to remove email."]);
        }

        $db->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
    }
