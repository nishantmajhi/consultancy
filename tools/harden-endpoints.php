<?php

/**
 * One-off rewrite applied on 2026-09-05.
 *
 * Every endpoint under api/ carried its own copy of two patterns:
 *
 *   - an unauthorised branch that answered HTTP 200 with an error body, so a
 *     caller could not tell "not signed in" from success without parsing the
 *     payload;
 *   - a catch block that returned $e->getMessage() to the client, which for a
 *     SQLite failure means the query text and absolute server paths.
 *
 * This script replaces both with calls into utils/Api.php. It is idempotent and
 * reports what it changed. Kept in the repository as the record of a mechanical
 * change that touched twenty files at once; it can be deleted once that is no
 * longer interesting.
 *
 * Usage: php tools/harden-endpoints.php [--dry-run]
 */

declare(strict_types=1);

$root   = dirname(__DIR__);
$dryRun = in_array('--dry-run', $argv, true);

$files = glob($root . '/api/*/*.php') ?: [];
sort($files);

$sessionRequire = "require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';";
$apiRequire     = "require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Api.php';";

$changedFiles = 0;

foreach ($files as $file) {
    $original = file_get_contents($file);

    if ($original === false) {
        continue;
    }

    $updated = $original;

    // 1. Pull in the shared helper, once, next to the Session require.
    if (!str_contains($updated, "'Api.php'") && str_contains($updated, $sessionRequire)) {
        $updated = str_replace(
            $sessionRequire,
            $sessionRequire . PHP_EOL . '    ' . $apiRequire,
            $updated
        );
    }

    // 2. Unauthorised branch -> Api::requireAuth(), which answers 401.
    $updated = preg_replace(
        '/if \(!isset\(\$_SESSION\[.user_name.\]\)\) \{\s*'
        . 'echo json_encode\(\[.status. => .error., .message. => .Unauthorized access.\]\);\s*'
        . 'exit;\s*\}/s',
        'Api::requireAuth();',
        $updated
    ) ?? $updated;

    // 3. Stop describing internal failures to the caller.
    $updated = preg_replace(
        '/http_response_code\(500\);\s*'
        . 'echo json_encode\(\["error" => "Internal Server Error", "details" => \$e->getMessage\(\)\]\);/s',
        'Api::serverError($e);',
        $updated
    ) ?? $updated;

    if ($updated === $original) {
        continue;
    }

    $changedFiles++;
    echo ($dryRun ? 'would update  ' : 'updated  ') . substr($file, strlen($root) + 1) . PHP_EOL;

    if (!$dryRun) {
        file_put_contents($file, $updated);
    }
}

echo PHP_EOL . sprintf('%d file(s) %s.', $changedFiles, $dryRun ? 'would change' : 'changed') . PHP_EOL;
