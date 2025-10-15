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
        $query = "CREATE TABLE IF NOT EXISTS clients (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            address TEXT NOT NULL,
            mobileNumber TEXT NOT NULL,
            email TEXT,
            gender TEXT DEFAULT 'Prefer Not Say' CHECK (gender IN ('Male', 'Female', 'Prefer Not Say')),
            bikeLicense TEXT DEFAULT 'no' CHECK (bikeLicense IN ('yes', 'no')),
            jobPreferences TEXT,
            documents TEXT,
            profilePic TEXT,
            visibility TEXT NOT NULL DEFAULT 'Normal' CHECK (visibility IN ('Normal', 'Archived', 'Deleted')),
            submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            remarks TEXT NULL
        )";
        $db->exec($query);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $address = $_POST['address'] ?? '';
            $mobileNumber = $_POST['mobileNumber'] ?? '';
            $email = $_POST['email'] ?? '';
            $gender = $_POST['gender'] ?? 'Prefer Not Say';
            $bikeLicense = $_POST['bikeLicense'] ?? 'no';
            $jobPreferences = isset($_POST['jobPreferences']) ? json_encode($_POST['jobPreferences']) : '[]';

            $nameParts = explode(" ", $name);
            $initials = '';
            foreach ($nameParts as $part) {
                $initials .= strtolower(substr($part, 0, 1));
            }

            $timestamp = date("ymd-His");
            $customId = $initials . "_" . $timestamp;

            $uploadBaseDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $customId;
            $documentsDir  = $uploadBaseDir . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR;
            $profilePicDir = $uploadBaseDir . DIRECTORY_SEPARATOR . 'profilePic' . DIRECTORY_SEPARATOR;

            if (!file_exists($uploadBaseDir)) {
                mkdir($uploadBaseDir, 0777, true);
            }
            if (!file_exists($documentsDir)) {
                mkdir($documentsDir, 0777, true);
            }
            if (!file_exists($profilePicDir)) {
                mkdir($profilePicDir, 0777, true);
            }

            $documents = [];
            if (!empty($_FILES['files'])) {
                foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
                    $fileName = basename($_FILES['files']['name'][$key]);
                    $filePath = $documentsDir . $fileName;

                    if (move_uploaded_file($tmpName, $filePath)) {
                        $documents[] = $filePath;
                    }
                }
            }
            $documentsJSON = json_encode($documents);

            $profilePic = '';
            if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['profilePicture']['tmp_name'];
                $fileName = basename($_FILES['profilePicture']['name']);
                $fileType = mime_content_type($tmpName);

                if (strpos($fileType, 'image/') === 0) {
                    $targetPath = $profilePicDir . $fileName;
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $profilePic = $targetPath;
                    }
                }
            }

            $stmt = $db->prepare("INSERT INTO clients (id, name, address, mobileNumber, email, gender, bikeLicense, jobPreferences, documents, profilePic)
                                VALUES (:id, :name, :address, :mobileNumber, :email, :gender, :bikeLicense, :jobPreferences, :documents, :profilePic)");

            $stmt->bindValue(':id', $customId, SQLITE3_TEXT);
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':address', $address, SQLITE3_TEXT);
            $stmt->bindValue(':mobileNumber', $mobileNumber, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':gender', $gender, SQLITE3_TEXT);
            $stmt->bindValue(':bikeLicense', $bikeLicense, SQLITE3_TEXT);
            $stmt->bindValue(':jobPreferences', $jobPreferences, SQLITE3_TEXT);
            $stmt->bindValue(':documents', $documentsJSON, SQLITE3_TEXT);
            $stmt->bindValue(':profilePic', $profilePic, SQLITE3_TEXT);

            if ($stmt->execute()) {
                echo json_encode(["status" => "Client added successfully."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to add client."]);
            }
        }

        $db->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
    }
