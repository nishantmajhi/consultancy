<?php

/**
 * One-off rewrite applied on 2026-09-05.
 *
 * Each seeder carried its own CREATE TABLE statement, which is how the jobs
 * table came to be declared twice with different definitions: the seeder's copy
 * included a remarks column, the endpoint's did not, and both shared the
 * unusable DEFAULT 'normal' that made adding a job impossible. Whichever ran
 * first decided the shape of the database.
 *
 * This replaces those inline statements with calls into db/Schema.php so there
 * is one definition per table. Kept as the record of the change; safe to delete.
 *
 * Usage: php tools/seeders-use-schema.php [--dry-run]
 */

declare(strict_types=1);

$root   = dirname(__DIR__);
$dryRun = in_array('--dry-run', $argv, true);

/** seeder file => the Schema method that now owns its tables */
$replacements = [
    'Admin.php'        => 'ensureCredentials',
    'Client.php'       => 'ensureClients',
    'Email.php'        => 'ensureSubmittedEmails',
    'Job.php'          => 'ensureJobs',
    'Notification.php' => 'ensureNotifications',
    'Payment.php'      => 'ensurePaymentsAndStatus',
];

$changed = 0;

foreach ($replacements as $fileName => $method) {
    $path     = $root . '/database/seeders/' . $fileName;
    $original = file_get_contents($path);

    if ($original === false) {
        echo 'missing  ' . $fileName . PHP_EOL;
        continue;
    }

    $updated = $original;

    // Pull in the shared definitions, once, after the namespace declaration.
    if (!str_contains($updated, 'Schema.php')) {
        $updated = preg_replace(
            '/^(namespace Seeder;\s*\n)/m',
            "$1\nrequire_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'Schema.php';\n\nuse Schema;\n",
            $updated,
            1
        ) ?? $updated;
    }

    // Replace an inline CREATE TABLE (assigned to $query then exec'd, or exec'd
    // directly) with the shared call.
    $updated = preg_replace(
        '/\$query = "CREATE TABLE IF NOT EXISTS.*?";\s*\n\s*\$db->exec\(\$query\);/s',
        '\\Schema::' . $method . '($db);',
        $updated
    ) ?? $updated;

    $updated = preg_replace(
        '/\$db->exec\("CREATE TABLE IF NOT EXISTS.*?"\);/s',
        '\\Schema::' . $method . '($db);',
        $updated
    ) ?? $updated;

    if ($updated === $original) {
        echo 'unchanged  ' . $fileName . PHP_EOL;
        continue;
    }

    $changed++;
    echo ($dryRun ? 'would update  ' : 'updated  ') . $fileName . PHP_EOL;

    if (!$dryRun) {
        file_put_contents($path, $updated);
    }
}

echo PHP_EOL . sprintf('%d file(s) %s.', $changed, $dryRun ? 'would change' : 'changed') . PHP_EOL;
