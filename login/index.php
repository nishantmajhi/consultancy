<?php
  require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
  Session::init();

  if (isset($_SESSION['user_name']) && !isset($_GET['redirect'])) {
    header('Location: ../admin/');
    exit();
  } elseif (isset($_SESSION['user_name']) && isset($_GET['redirect'])) {
    $redirect = urldecode($_GET['redirect']);
    header("Location: $redirect");
    exit();
  }

  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }

  if ($_SERVER['REQUEST_METHOD'] != 'GET' && !file_exists(__DIR__ . '/../db/login.sqlite')) {
    die('Invalid request!');
  }
  if ($_SERVER['REQUEST_METHOD'] == 'GET' && !file_exists(__DIR__ . '/../db/login.sqlite')) {
    die('No registered user found!');
  }

    $env = parse_ini_file(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . '.env');
    $companyName = $env["COMPANY_NAME"];
  
  if ($_SERVER['REQUEST_METHOD'] == 'GET' && file_exists(__DIR__ . '/../db/login.sqlite')) {
    $html = str_replace('{{COMPANY_NAME}}', $companyName, file_get_contents('login.html'));

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);


    $form = $dom->getElementsByTagName('form')->item(0);
    if ($form) {
      $input = $dom->createElement('input');
      $input->setAttribute('type', 'hidden');
      $input->setAttribute('name', 'csrf_token');
      $input->setAttribute('value', htmlspecialchars($_SESSION['csrf_token']));
      $form->appendChild($input);
    }

    echo $dom->saveHTML();
    exit();
  }

  if ($_SERVER['REQUEST_METHOD'] == 'POST' && file_exists(__DIR__ . '/../db/login.sqlite') && !isset($_SESSION['user_name'])) {
    if (!isset($_POST['csrf_token']) || ($_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
      die('CSRF token validation failed!');
    }

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $conn = new SQLite3(__DIR__ . '/../db/login.sqlite');
    $query = "SELECT `username`, `hashed_secret` FROM `credentials` WHERE `username` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row !== false && password_verify($password, $row['hashed_secret'])) {
      $_SESSION['user_name'] = $row['username'];
      session_regenerate_id(true);
      if (isset($_GET['redirect'])) {
        $redirect = urldecode($_GET['redirect']);
        header("Location: $redirect");
      } else {
        header("Location: ../admin/");
      }
      exit();
    } else {
      $_SESSION['error'] = "Invalid username or password";
      header("Location: index.php");
      exit();
    }
  }
