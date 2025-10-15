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

    echo "🌱 Initializing SQLite3 with test data...\n";
    echo AdminSeeder::seed();
    echo EmailSeeder::seed();
    echo ClientSeeder::seed();
    echo JobSeeder::seed();
    echo NotificationSeeder::seed();
    echo PaymentSeeder::seed();
