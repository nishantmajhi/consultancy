<?php
require_once './auth/Session.php';
Session::init();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$env = parse_ini_file('.env');
$companyName = $env["COMPANY_NAME"];

$htmlContent = str_replace('{{COMPANY_NAME}}', $companyName, file_get_contents('home.html'));

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

$companyWords = explode(' ', trim($companyName));
$firstName = htmlspecialchars(array_shift($companyWords));
$lastName = htmlspecialchars(implode(' ', $companyWords));

$firstNameSpan = $dom->getElementById('first_name');
if ($firstNameSpan) {
    $firstNameSpan->nodeValue = $firstName;
}

$lastNameSpan = $dom->getElementById('last_name');
if ($lastNameSpan) {
    $lastNameSpan->nodeValue = $lastName;
}

$form = $dom->getElementsByTagName('form')->item(0);
if ($form) {
    $input = $dom->createElement('input');
    $input->setAttribute('type', 'hidden');
    $input->setAttribute('name', 'csrf_token');
    $input->setAttribute('value', htmlspecialchars($_SESSION['csrf_token']));
    $form->appendChild($input);
}

echo $dom->saveHTML();
