<?php
    require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    include dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'CORS.php';
    Session::init();
    
    if (!isset($_SESSION['user_name'])) {
        $currentUrl = urlencode($_SERVER['REQUEST_URI']);
        header("Location: ../login/?redirect=$currentUrl");
        exit();
    }

    $env = parse_ini_file(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . '.env');
    $companyName = $env["COMPANY_NAME"];
    
    $htmlContent = str_replace('{{COMPANY_NAME}}', $companyName, file_get_contents('states.html'));
    
    echo $htmlContent;