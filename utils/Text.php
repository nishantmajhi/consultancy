<?php

/**
 * UTF-8 string helpers that do not require ext-mbstring.
 *
 * The application handles Nepali names, so string length and first-character
 * extraction have to count characters rather than bytes: strlen("सीता") is 12,
 * and substr($name, 0, 1) returns a third of a character. mbstring answers
 * both correctly and the Dockerfile installs it, but a PHP build without it is
 * common enough that calling mb_strlen() unguarded turns a length check into a
 * fatal error. PCRE's /u modifier is always available and gives the same
 * answers, so it is used as the fallback.
 */
final class Text
{
    /** Number of characters, not bytes. */
    public static function length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        $count = preg_match_all('/./us', $value);

        // preg_match_all returns false on malformed UTF-8; falling back to the
        // byte count keeps a length limit enforced rather than skipped.
        return $count === false ? strlen($value) : $count;
    }

    /** The first character, or an empty string. */
    public static function firstCharacter(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, 1, 'UTF-8');
        }

        return preg_match('/^./us', $value, $matches) === 1 ? $matches[0] : '';
    }

    /**
     * Lowercase a single character.
     *
     * Only ASCII is transformed. Every other script this application sees is
     * unicameral, Devanagari included, so leaving those characters untouched is
     * the correct result rather than an approximation.
     */
    public static function lowerCharacter(string $character): string
    {
        if ($character === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z]$/', $character) === 1) {
            return strtolower($character);
        }

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($character, 'UTF-8');
        }

        return $character;
    }
}
