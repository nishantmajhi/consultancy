<?php
    require __DIR__ . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Admin.php';
    require __DIR__ . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Client.php';
    require __DIR__ . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Email.php';
    require __DIR__ . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Job.php';
    require __DIR__ . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Notification.php';
    require __DIR__ . DIRECTORY_SEPARATOR . 'seeder' . DIRECTORY_SEPARATOR . 'Payment.php';

    use Seeder\AdminSeeder;
    use Seeder\EmailSeeder;
    use Seeder\ClientSeeder;
    use Seeder\JobSeeder;
    use Seeder\NotificationSeeder;
    use Seeder\PaymentSeeder;

    $isCLI = (php_sapi_name() === 'cli' || defined('STDIN'));

    if($isCLI) {
        echo "🌱 Initializing SQLite3 with test data...\n";
        echo AdminSeeder::seed();
        echo EmailSeeder::seed();
        echo ClientSeeder::seed();
        echo JobSeeder::seed();
        echo NotificationSeeder::seed();
        echo PaymentSeeder::seed();
    } else {
        echo "🌱 Initializing SQLite3 with test data...<br />";
        echo str_replace("\n", "<br />", AdminSeeder::seed());
        echo str_replace("\n", "<br />", EmailSeeder::seed());
        echo str_replace("\n", "<br />", ClientSeeder::seed());
        echo str_replace("\n", "<br />", JobSeeder::seed());
        echo str_replace("\n", "<br />", NotificationSeeder::seed());
        echo str_replace("\n", "<br />", PaymentSeeder::seed());
    }