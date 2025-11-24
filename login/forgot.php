<?php
    $env = parse_ini_file(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . '.env');
    $companyName = $env["COMPANY_NAME"];

    $htmlContent = file_get_contents(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'subscribe.html');
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($htmlContent);

    $titleTags = $dom->getElementsByTagName('title');
    $titleTextContent = 'Forgot Password | ' . $companyName;
    if ($titleTags->length > 0) {
        $titleTags->item(0)->nodeValue = $titleTextContent;
    } else {
        $head = $dom->getElementsByTagName('head')->item(0);
        if ($head) {
            $title = $dom->createElement('title', $titleTextContent);
            $head->appendChild($title);
        }
    }

    echo $dom->saveHTML();
