<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    Session::init();

    header('Content-Type: application/json');

    if (!isset($_SESSION['user_name'])) {
        echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
        exit;
    }

    if (
        !isset($_POST['csrf_token']) || 
        !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token validation failed.']);
        exit();
    }

    try {
        $db = new SQLite3(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data.sqlite');
        $query = "CREATE TABLE IF NOT EXISTS jobs (
            id TEXT PRIMARY KEY,
            companyName TEXT NOT NULL,
            address TEXT NOT NULL,
            mobileNumber TEXT NOT NULL,
            position TEXT NOT NULL,
            minimumSalary INTEGER NOT NULL,
            deadline TEXT,
            skillPreferences TEXT,
            visibility TEXT CHECK(visibility IN ('Normal', 'Archived', 'Deleted')) DEFAULT 'normal',
            submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($query);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $companyName = trim($_POST['name'] ?? '');
            $address = $_POST['address'] ?? '';
            $mobileNumber = $_POST['mobileNumber'] ?? '';
            $position = $_POST['position'] ?? '';
            $minimumSalary = $_POST['minimumSalary'] ?? 0;
            $deadline = $_POST['deadline'] ?? '';
            $skillPreferences = isset($_POST['skillPreferences']) ? json_encode($_POST['skillPreferences']) : '[]';

            $nameParts = explode(" ", $companyName);
            $initials = '';

            foreach ($nameParts as $part) {
                $initials .= strtolower(substr($part, 0, 1));
            }

            $timestamp = date("ymd-His");
            $customId = $initials . "_" . (int)($minimumSalary/1000) . "_" . $timestamp;

            $stmt = $db->prepare("INSERT INTO jobs (
                id, companyName, address, mobileNumber, position, minimumSalary, deadline, skillPreferences
            ) VALUES (
                :id, :companyName, :address, :mobileNumber, :position, :minimumSalary, :deadline, :skillPreferences
            )");

            $stmt->bindValue(':id', $customId, SQLITE3_TEXT);
            $stmt->bindValue(':companyName', $companyName, SQLITE3_TEXT);
            $stmt->bindValue(':address', $address, SQLITE3_TEXT);
            $stmt->bindValue(':mobileNumber', $mobileNumber, SQLITE3_TEXT);
            $stmt->bindValue(':position', $position, SQLITE3_TEXT);
            $stmt->bindValue(':minimumSalary', $minimumSalary, SQLITE3_INTEGER);
            $stmt->bindValue(':deadline', $deadline, SQLITE3_TEXT);
            $stmt->bindValue(':skillPreferences', $skillPreferences, SQLITE3_TEXT);

            if ($stmt->execute()) {
                echo json_encode(["status" => "Job added successfully."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to add job."]);
            }
        }

        $db->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
    }
