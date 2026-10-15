<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    include dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'CORS.php';
    Session::init();

    if (!isset($_SESSION['user_name'])) {
        $currentUrl = urlencode($_SERVER['REQUEST_URI']);
        header("Location: ../../login/?redirect=$currentUrl");
        exit();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    $env = parse_ini_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');
    $companyName = $env["COMPANY_NAME"];

    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'UI.php';
    $asideContents = UI::getAsideContents();
    $headerContents = UI::getHeaderContents("Add Job");
    
    $htmlContent = str_replace(['{{COMPANY_NAME}}', '{{ASIDE_CONTENTS}}', '{{HEADER_CONTENTS}}'], [$companyName, $asideContents, $headerContents], file_get_contents('addJob.html'));

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $form = $dom->getElementsByTagName('form')->item(0);
    if ($form) {
        $input = $dom->createElement('input');
        $input->setAttribute('type', 'hidden');
        $input->setAttribute('name', 'csrf_token');
        $input->setAttribute('value', htmlspecialchars($_SESSION['csrf_token']));
        $form->appendChild($input);
    }

    echo $dom->saveHTML();
