<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Clients.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Text.php';

/**
 * Validation and identifier rules for job records.
 *
 * Mirrors utils/Clients.php: the table's NOT NULL columns were being satisfied
 * by empty strings, and minimumSalary was taken straight from the request, so a
 * non-numeric value was bound as an integer and silently became zero.
 */
final class Jobs
{
    public const MAX_COMPANY_NAME_LENGTH = 120;
    public const MAX_ADDRESS_LENGTH      = 255;
    public const MAX_POSITION_LENGTH     = 120;

    /** Highest salary accepted, guarding against a mistyped extra digit. */
    public const MAX_SALARY = 100_000_000;

    /**
     * Check one submitted job form.
     *
     * @param array<string, mixed> $post
     * @return array{ok: bool, error: string, values: array<string, mixed>}
     */
    public static function validateSubmission(array $post): array
    {
        $fail = static fn (string $message): array
            => ['ok' => false, 'error' => $message, 'values' => []];

        // The form posts the company under "name", which is why this reads a
        // different key from the column it populates.
        $companyName = trim((string) ($post['name'] ?? ''));

        if ($companyName === '') {
            return $fail('A company name is required.');
        }

        if (Text::length($companyName) > self::MAX_COMPANY_NAME_LENGTH) {
            return $fail(sprintf('The company name may be at most %d characters.', self::MAX_COMPANY_NAME_LENGTH));
        }

        $address = trim((string) ($post['address'] ?? ''));

        if ($address === '') {
            return $fail('An address is required.');
        }

        if (Text::length($address) > self::MAX_ADDRESS_LENGTH) {
            return $fail(sprintf('The address may be at most %d characters.', self::MAX_ADDRESS_LENGTH));
        }

        $mobileNumber = Clients::normaliseMobileNumber((string) ($post['mobileNumber'] ?? ''));

        if ($mobileNumber === null) {
            return $fail('A mobile number of 7 to 15 digits is required.');
        }

        $position = trim((string) ($post['position'] ?? ''));

        if ($position === '') {
            return $fail('A position is required.');
        }

        if (Text::length($position) > self::MAX_POSITION_LENGTH) {
            return $fail(sprintf('The position may be at most %d characters.', self::MAX_POSITION_LENGTH));
        }

        $minimumSalary = self::normaliseSalary($post['minimumSalary'] ?? null);

        if ($minimumSalary === null) {
            return $fail('The minimum salary must be a whole number of 0 or more.');
        }

        $deadline = trim((string) ($post['deadline'] ?? ''));

        if ($deadline !== '' && !self::isValidDate($deadline)) {
            return $fail('The deadline must be a date in YYYY-MM-DD form.');
        }

        return [
            'ok'    => true,
            'error' => '',
            'values' => [
                'companyName'      => $companyName,
                'address'          => $address,
                'mobileNumber'     => $mobileNumber,
                'position'         => $position,
                'minimumSalary'    => $minimumSalary,
                'deadline'         => $deadline,
                'skillPreferences' => Clients::normaliseJobPreferences($post['skillPreferences'] ?? null),
            ],
        ];
    }

    /**
     * Coerce a submitted salary to a non-negative integer, or null if it is not
     * one. Accepts digit separators people type, such as "25,000".
     */
    public static function normaliseSalary(mixed $raw): ?int
    {
        if (is_int($raw)) {
            return $raw >= 0 && $raw <= self::MAX_SALARY ? $raw : null;
        }

        if (!is_string($raw) && !is_float($raw)) {
            return null;
        }

        $cleaned = str_replace([',', ' ', '_'], '', trim((string) $raw));

        if ($cleaned === '' || preg_match('/^\d+$/', $cleaned) !== 1) {
            return null;
        }

        $value = (int) $cleaned;

        return $value <= self::MAX_SALARY ? $value : null;
    }

    /** Whether a string is a real calendar date in YYYY-MM-DD form. */
    public static function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    /**
     * Build a job identifier: company initials, salary in thousands, timestamp,
     * and a random suffix that keeps two jobs added in the same second apart.
     */
    public static function generateId(
        string $companyName,
        int $minimumSalary,
        ?string $timestamp = null,
        ?string $suffix = null
    ): string {
        $initials = '';

        foreach (preg_split('/\s+/', trim($companyName)) ?: [] as $part) {
            if ($part === '') {
                continue;
            }

            $first = Text::firstCharacter($part);

            if (preg_match('/^[\p{L}\p{N}]$/u', $first) === 1) {
                $initials .= Text::lowerCharacter($first);
            }
        }

        if ($initials === '') {
            $initials = 'job';
        }

        $timestamp ??= date('ymd-His');
        $suffix    ??= bin2hex(random_bytes(2));

        return $initials . '_' . intdiv($minimumSalary, 1000) . '_' . $timestamp . '-' . $suffix;
    }
}
