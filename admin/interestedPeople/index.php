<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    include dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'CORS.php';
    Session::init();
    
    if (!isset($_SESSION['user_name'])) {
        $currentUrl = urlencode($_SERVER['REQUEST_URI']);
        header("Location: ../../login/?redirect=$currentUrl");
        exit();
    }

    $env = parse_ini_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');
    $companyName = $env["COMPANY_NAME"];

    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'UI.php';
    $asideContents = UI::getAsideContents();
    $headerContents = UI::getHeaderContents("List Potential Clients");
    
    $htmlContent = str_replace(['{{COMPANY_NAME}}', '{{ASIDE_CONTENTS}}', '{{HEADER_CONTENTS}}'], [$companyName, $asideContents, $headerContents], file_get_contents('interestedPeople.html'));
    
    echo $htmlContent;

