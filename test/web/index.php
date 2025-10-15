<?php
    require dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Admin.php';
    require dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Client.php';
    require dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Email.php';
    require dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Job.php';
    require dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Notification.php';
    require dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Payment.php';

    use Seeder\AdminSeeder;
    use Seeder\EmailSeeder;
    use Seeder\ClientSeeder;
    use Seeder\JobSeeder;
    use Seeder\NotificationSeeder;
    use Seeder\PaymentSeeder;

    $status = "🌱 Initializing SQLite3 with test data...\n";
    $status .= AdminSeeder::seed();
    $status .= EmailSeeder::seed();
    $status .= ClientSeeder::seed();
    $status .= JobSeeder::seed();
    $status .= NotificationSeeder::seed();
    $status .= PaymentSeeder::seed();

    $html = '';
    try {
        $html = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR .'message.html');
        if ($html === false) {
            throw new Exception("Failed to read message.html");
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $fragment = $dom->createDocumentFragment();
            $safeStatus = '<div>' . str_replace("\n", "<br />", htmlspecialchars($status)) . '</div>';
            $fragment->appendXML($safeStatus);
            $body->appendChild($fragment);
        }

        echo $dom->saveHTML();
    } catch (Exception $e) {
        echo "Error loading message.html: " . $e->getMessage();
    }
