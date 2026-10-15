<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Text.php';

/**
 * Validation and identifier rules for client records.
 *
 * The clients table declares NOT NULL columns and CHECK constraints on gender
 * and bikeLicense, but the endpoint used to pass whatever arrived straight
 * through: an empty name satisfied NOT NULL because it was an empty string
 * rather than null, and an out-of-range gender surfaced as a bare SQLite
 * constraint failure reported to the user as "Failed to add client". The rules
 * are stated here instead, ahead of the database, so a bad submission produces
 * a message naming the field.
 *
 * Everything in this class is a pure function of its arguments, which is what
 * makes tests/ClientsTest.php possible without a database or a web server.
 */
final class Clients
{
    public const GENDERS       = ['Male', 'Female', 'Prefer Not Say'];
    public const BIKE_LICENSES = ['yes', 'no'];

    public const MAX_NAME_LENGTH    = 120;
    public const MAX_ADDRESS_LENGTH = 255;

    /**
     * Check one submitted client form.
     *
     * @param array<string, mixed> $post
     * @return array{ok: bool, error: string, values: array<string, mixed>}
     */
    public static function validateSubmission(array $post): array
    {
        $fail = static fn (string $message): array
            => ['ok' => false, 'error' => $message, 'values' => []];

        $name = trim((string) ($post['name'] ?? ''));

        if ($name === '') {
            return $fail('A name is required.');
        }

        if (Text::length($name) > self::MAX_NAME_LENGTH) {
            return $fail(sprintf('The name may be at most %d characters.', self::MAX_NAME_LENGTH));
        }

        $address = trim((string) ($post['address'] ?? ''));

        if ($address === '') {
            return $fail('An address is required.');
        }

        if (Text::length($address) > self::MAX_ADDRESS_LENGTH) {
            return $fail(sprintf('The address may be at most %d characters.', self::MAX_ADDRESS_LENGTH));
        }

        $mobileNumber = self::normaliseMobileNumber((string) ($post['mobileNumber'] ?? ''));

        if ($mobileNumber === null) {
            return $fail('A mobile number of 7 to 15 digits is required.');
        }

        $email = trim((string) ($post['email'] ?? ''));

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $fail('The email address is not valid.');
        }

        $gender = (string) ($post['gender'] ?? 'Prefer Not Say');

        if (!in_array($gender, self::GENDERS, true)) {
            return $fail('The gender value is not one of the accepted options.');
        }

        $bikeLicense = (string) ($post['bikeLicense'] ?? 'no');

        if (!in_array($bikeLicense, self::BIKE_LICENSES, true)) {
            return $fail('The bike licence value must be "yes" or "no".');
        }

        $jobPreferences = self::normaliseJobPreferences($post['jobPreferences'] ?? null);

        if ($jobPreferences === []) {
            return $fail('At least one job preference is required.');
        }

        return [
            'ok'    => true,
            'error' => '',
            'values' => [
                'name'           => $name,
                'address'        => $address,
                'mobileNumber'   => $mobileNumber,
                'email'          => $email,
                'gender'         => $gender,
                'bikeLicense'    => $bikeLicense,
                'jobPreferences' => $jobPreferences,
            ],
        ];
    }

    /**
     * Reduce a mobile number to digits, or null if it cannot be one.
     *
     * Spaces, dashes, brackets and a leading "+" are accepted because people
     * type them; anything else means the field was not a phone number.
     */
    public static function normaliseMobileNumber(string $raw): ?string
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/[^0-9+()\s-]/', $trimmed) === 1) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }

    /**
     * Coerce the submitted job preferences into a list of non-empty strings.
     *
     * The form posts jobPreferences[], so PHP delivers an array; a request made
     * by hand may send a bare string, and the previous code would json_encode
     * that into a JSON string rather than the array every reader expects.
     *
     * @return list<string>
     */
    public static function normaliseJobPreferences(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = [$raw];
        }

        if (!is_array($raw)) {
            return [];
        }

        $preferences = [];

        foreach ($raw as $preference) {
            if (!is_scalar($preference)) {
                continue;
            }

            $preference = trim((string) $preference);

            if ($preference !== '' && !in_array($preference, $preferences, true)) {
                $preferences[] = $preference;
            }
        }

        return $preferences;
    }

    /**
     * Build a client identifier: initials, a timestamp, and a random suffix.
     *
     * The suffix is what makes it unique. Initials plus a one-second timestamp
     * collide whenever two people with the same initials are added in the same
     * second, and because the identifier is the primary key that collision
     * surfaced as a failed insert after the files had already been written.
     */
    public static function generateId(string $name, ?string $timestamp = null, ?string $suffix = null): string
    {
        $initials = '';

        foreach (preg_split('/\s+/', trim($name)) ?: [] as $part) {
            if ($part === '') {
                continue;
            }

            $first = Text::firstCharacter($part);

            // Only letters and digits reach the identifier; a name containing
            // punctuation must not put a path separator or a quote into it.
            if (preg_match('/^[\p{L}\p{N}]$/u', $first) === 1) {
                $initials .= Text::lowerCharacter($first);
            }
        }

        if ($initials === '') {
            $initials = 'client';
        }

        $timestamp ??= date('ymd-His');
        $suffix    ??= bin2hex(random_bytes(2));

        return $initials . '_' . $timestamp . '-' . $suffix;
    }

    /**
     * Remove files written during a submission that then failed.
     *
     * @param list<string> $paths
     */
    public static function discardFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                @unlink($path);
            }
        }
    }
}
