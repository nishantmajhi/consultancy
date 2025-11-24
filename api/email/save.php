<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    Session::init();

    if (
        !isset($_POST['csrf_token']) || 
        !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token validation failed.']);
        exit();
    }

    header('Content-Type: application/json');

    try {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        if (!$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email address.']);
            exit;
        }

        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'waitlist.sqlite');
        
        $query = "CREATE TABLE IF NOT EXISTS submitted_emails (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            submittedDate TEXT NOT NULL
        )";
        $db->exec($query);
        
        $stmt = $db->prepare("
        INSERT INTO submitted_emails (email, submittedDate)
        VALUES (:email, :submittedDate)
        ON CONFLICT(email) DO UPDATE SET submittedDate = :submittedDate
        ");
        
        $submittedDate = date('Y-m-d H:i:s');
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':submittedDate', $submittedDate, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "Email submitted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to add client."]);
        }

        $db->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
    }
