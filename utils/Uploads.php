<?php

/**
 * File-upload validation and storage.
 *
 * Uploaded files are attacker-controlled in both content and name, and this
 * application serves its own upload directory over HTTP. Three rules follow,
 * and every public method here exists to enforce one of them:
 *
 *   1. The stored name is generated here, never taken from the request. A
 *      client filename may be "shell.php", may collide with another client's
 *      file, or may contain characters the filesystem treats specially.
 *   2. The extension must be on the allowlist AND the file's sniffed MIME type
 *      must be one that the extension legitimately maps to. Checking only one
 *      of the two is bypassable: an extension check alone accepts a PHP script
 *      called "x.jpg", and a MIME check alone accepts a valid GIF whose name
 *      ends in ".php".
 *   3. Size and count are capped here rather than relying on php.ini, so the
 *      limit is visible in code and identical across deployments.
 *
 * The pure decision logic is deliberately separated from the one method that
 * touches the filesystem, so the rules above can be tested without an HTTP
 * upload. See tests/UploadsTest.php.
 */
final class Uploads
{
    /** Largest single file accepted, in bytes. */
    public const MAX_FILE_BYTES = 5 * 1024 * 1024;

    /** Largest number of supporting documents accepted per client. */
    public const MAX_FILES = 10;

    /**
     * Extension allowlist, mapping each accepted extension to the MIME types a
     * genuine file with that extension may report. Anything not listed here is
     * rejected outright.
     */
    private const ALLOWED_TYPES = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
    ];

    /** Extensions acceptable where an image specifically is required. */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Lowercased extension of a client-supplied filename, without the dot.
     *
     * Only the final path segment is considered, so a name carrying directory
     * separators cannot smuggle one into the result.
     */
    public static function extensionOf(string $clientFilename): string
    {
        $base = basename(str_replace('\\', '/', $clientFilename));
        $dot  = strrpos($base, '.');

        if ($dot === false || $dot === strlen($base) - 1) {
            return '';
        }

        return strtolower(substr($base, $dot + 1));
    }

    /** True if the extension is accepted, optionally restricted to images. */
    public static function isAllowedExtension(string $extension, bool $imagesOnly = false): bool
    {
        $extension = strtolower($extension);

        if (!array_key_exists($extension, self::ALLOWED_TYPES)) {
            return false;
        }

        return !$imagesOnly || in_array($extension, self::IMAGE_EXTENSIONS, true);
    }

    /** True if a sniffed MIME type is one the extension may legitimately have. */
    public static function mimeMatchesExtension(string $extension, string $mimeType): bool
    {
        $extension = strtolower($extension);

        if (!array_key_exists($extension, self::ALLOWED_TYPES)) {
            return false;
        }

        return in_array(strtolower($mimeType), self::ALLOWED_TYPES[$extension], true);
    }

    /**
     * A storage name that owes nothing to the request: 32 random hex characters
     * plus the validated extension. Random rather than sequential so one client's
     * document URLs cannot be guessed from another's.
     */
    public static function generateStoredName(string $extension): string
    {
        return bin2hex(random_bytes(16)) . '.' . strtolower($extension);
    }

    /**
     * File signatures for every accepted type, as offset => magic bytes.
     *
     * WebP needs two checks because its header is a RIFF container: bytes 0-3
     * are "RIFF", the next four are the file length, and bytes 8-11 identify the
     * payload as "WEBP".
     */
    private const SIGNATURES = [
        'application/pdf' => [[0, "%PDF-"]],
        'image/png'       => [[0, "\x89PNG\r\n\x1a\n"]],
        'image/jpeg'      => [[0, "\xFF\xD8\xFF"]],
        'image/webp'      => [[0, 'RIFF'], [8, 'WEBP']],
    ];

    /**
     * Identify a file from its leading bytes.
     *
     * This is the authority for the types on the allowlist, rather than the
     * host's magic database, for two reasons. It is deterministic across
     * machines, and it does not depend on an extension being installed: PHP
     * without ext-fileinfo has no mime_content_type() at all, and calling a
     * function that does not exist is a fatal error that the @ operator cannot
     * suppress. Returning an empty string for anything unrecognised means an
     * unknown file fails the extension-agreement check and is rejected.
     */
    public static function sniffSignature(string $path): string
    {
        if ($path === '' || !is_file($path)) {
            return '';
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        $head = (string) fread($handle, 16);
        fclose($handle);

        foreach (self::SIGNATURES as $mimeType => $checks) {
            $matches = true;

            foreach ($checks as [$offset, $magic]) {
                if (substr($head, $offset, strlen($magic)) !== $magic) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                return $mimeType;
            }
        }

        return '';
    }

    /**
     * Sniff a file's MIME type from its contents.
     *
     * Separated out so tests can supply a known type instead of reading a file.
     * The signature check answers for every type this application accepts; the
     * platform detectors are consulted only so that a rejected file gets a more
     * accurate description in the log, and are skipped when unavailable.
     */
    public static function detectMimeType(string $path): string
    {
        $signature = self::sniffSignature($path);

        if ($signature !== '') {
            return $signature;
        }

        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo !== false) {
                $detected = @finfo_file($finfo, $path);
                finfo_close($finfo);

                if (is_string($detected) && $detected !== '') {
                    return $detected;
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($path);

            if (is_string($detected) && $detected !== '') {
                return $detected;
            }
        }

        return '';
    }

    /**
     * Decide whether one uploaded file may be stored.
     *
     * Takes the values rather than the raw $_FILES entry so the rules can be
     * exercised directly. Returns the validated extension on success.
     *
     * @return array{ok: bool, error: string, extension: string}
     */
    public static function check(
        string $clientFilename,
        int $sizeBytes,
        int $phpUploadError,
        string $mimeType,
        bool $imagesOnly = false
    ): array {
        $fail = static fn (string $message): array
            => ['ok' => false, 'error' => $message, 'extension' => ''];

        if ($phpUploadError !== UPLOAD_ERR_OK) {
            return $fail(self::describeUploadError($phpUploadError));
        }

        if ($sizeBytes <= 0) {
            return $fail('The file is empty.');
        }

        if ($sizeBytes > self::MAX_FILE_BYTES) {
            return $fail(sprintf(
                'The file is larger than the %d MB limit.',
                intdiv(self::MAX_FILE_BYTES, 1024 * 1024)
            ));
        }

        $extension = self::extensionOf($clientFilename);

        if ($extension === '') {
            return $fail('The file has no extension.');
        }

        if (!self::isAllowedExtension($extension, $imagesOnly)) {
            return $fail(sprintf('Files of type ".%s" are not accepted.', $extension));
        }

        if (!self::mimeMatchesExtension($extension, $mimeType)) {
            return $fail('The file contents do not match its extension.');
        }

        return ['ok' => true, 'error' => '', 'extension' => $extension];
    }

    /**
     * Validate and store one uploaded file.
     *
     * $mover exists so tests can substitute rename() for move_uploaded_file(),
     * which by design refuses any path that did not arrive via an HTTP upload.
     *
     * @param array{name: string, tmp_name: string, size: int, error: int} $file
     * @param callable(string, string): bool|null $mover
     * @return array{ok: bool, error: string, storedName: string}
     */
    public static function store(
        array $file,
        string $destinationDir,
        bool $imagesOnly = false,
        ?callable $mover = null
    ): array {
        $mover ??= static fn (string $from, string $to): bool => move_uploaded_file($from, $to);

        $verdict = self::check(
            (string) ($file['name'] ?? ''),
            (int) ($file['size'] ?? 0),
            (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE),
            self::detectMimeType((string) ($file['tmp_name'] ?? '')),
            $imagesOnly
        );

        if (!$verdict['ok']) {
            return ['ok' => false, 'error' => $verdict['error'], 'storedName' => ''];
        }

        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0755, true) && !is_dir($destinationDir)) {
            return ['ok' => false, 'error' => 'Could not create the upload directory.', 'storedName' => ''];
        }

        $storedName = self::generateStoredName($verdict['extension']);

        if (!$mover((string) $file['tmp_name'], rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $storedName)) {
            return ['ok' => false, 'error' => 'The file could not be saved.', 'storedName' => ''];
        }

        return ['ok' => true, 'error' => '', 'storedName' => $storedName];
    }

    /**
     * Turn PHP's column-major $_FILES entry for a multi-file field into one
     * array per file, which is the shape every other method here expects.
     *
     * @return list<array{name: string, tmp_name: string, size: int, error: int}>
     */
    public static function normaliseMultiple(array $field): array
    {
        if (!isset($field['name']) || !is_array($field['name'])) {
            return [];
        }

        $files = [];

        foreach (array_keys($field['name']) as $index) {
            $files[] = [
                'name'     => (string) ($field['name'][$index] ?? ''),
                'tmp_name' => (string) ($field['tmp_name'][$index] ?? ''),
                'size'     => (int) ($field['size'][$index] ?? 0),
                'error'    => (int) ($field['error'][$index] ?? UPLOAD_ERR_NO_FILE),
            ];
        }

        return $files;
    }

    /** Plain-language description of a PHP upload error constant. */
    public static function describeUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
            UPLOAD_ERR_PARTIAL                        => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE                        => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR                     => 'The server has no temporary folder configured.',
            UPLOAD_ERR_CANT_WRITE                     => 'The server could not write the file to disk.',
            UPLOAD_ERR_EXTENSION                      => 'A server extension blocked the upload.',
            default                                   => 'The file could not be uploaded.',
        };
    }
}
