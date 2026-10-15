<?php
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Csrf.php';
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

    // Removing a waitlist address is a plain form-encoded POST, which a page on
    // another origin can send without a preflight. The endpoint therefore checks
    // a CSRF token, and the token reaches removeEmail.js through this meta tag.
    // The tag renders nothing, so the page looks exactly as it did.
    $htmlContent = str_replace(
        '</head>',
        sprintf('  <meta name="csrf-token" content="%s" />%s  </head>', htmlspecialchars(Csrf::token(), ENT_QUOTES), PHP_EOL),
        $htmlContent
    );

    echo $htmlContent;

