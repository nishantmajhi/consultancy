<?php
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    $env = parse_ini_file(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . '.env');
    $allowed_origin = $env["DOMAIN_NAME"];

    if ($origin === $allowed_origin) {
        header("Access-Control-Allow-Origin: $allowed_origin");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
    }
    else if ($origin === 'localhost') {
        header("Access-Control-Allow-Origin: localhost");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
    }
