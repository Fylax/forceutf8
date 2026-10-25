<?php

declare(strict_types=1);

/**
 * @license  Revised BSD
 */

namespace ForceUTF8;

use function array_keys;
use function array_values;
use function chr;
use function extension_loaded;
use function function_exists;
use function is_array;
use function is_string;
use function ord;
use function preg_replace;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtoupper;
use function substr;

class Encoding
{
    protected const WIN1252_TO_UTF8 = [
        128 => "\xe2\x82\xac",
        130 => "\xe2\x80\x9a",
        131 => "\xc6\x92",
        132 => "\xe2\x80\x9e",
        133 => "\xe2\x80\xa6",
        134 => "\xe2\x80\xa0",
        135 => "\xe2\x80\xa1",
        136 => "\xcb\x86",
        137 => "\xe2\x80\xb0",
        138 => "\xc5\xa0",
        139 => "\xe2\x80\xb9",
        140 => "\xc5\x92",
        142 => "\xc5\xbd",
        145 => "\xe2\x80\x98",
        146 => "\xe2\x80\x99",
        147 => "\xe2\x80\x9c",
        148 => "\xe2\x80\x9d",
        149 => "\xe2\x80\xa2",
        150 => "\xe2\x80\x93",
        151 => "\xe2\x80\x94",
        152 => "\xcb\x9c",
        153 => "\xe2\x84\xa2",
        154 => "\xc5\xa1",
        155 => "\xe2\x80\xba",
        156 => "\xc5\x93",
        158 => "\xc5\xbe",
        159 => "\xc5\xb8"
    ];

    protected const BROKEN_UTF8_TO_UTF8 = [
        "\xc2\x80" => "\xe2\x82\xac",
        "\xc2\x82" => "\xe2\x80\x9a",
        "\xc2\x83" => "\xc6\x92",
        "\xc2\x84" => "\xe2\x80\x9e",
        "\xc2\x85" => "\xe2\x80\xa6",
        "\xc2\x86" => "\xe2\x80\xa0",
        "\xc2\x87" => "\xe2\x80\xa1",
        "\xc2\x88" => "\xcb\x86",
        "\xc2\x89" => "\xe2\x80\xb0",
        "\xc2\x8a" => "\xc5\xa0",
        "\xc2\x8b" => "\xe2\x80\xb9",
        "\xc2\x8c" => "\xc5\x92",
        "\xc2\x8e" => "\xc5\xbd",
        "\xc2\x91" => "\xe2\x80\x98",
        "\xc2\x92" => "\xe2\x80\x99",
        "\xc2\x93" => "\xe2\x80\x9c",
        "\xc2\x94" => "\xe2\x80\x9d",
        "\xc2\x95" => "\xe2\x80\xa2",
        "\xc2\x96" => "\xe2\x80\x93",
        "\xc2\x97" => "\xe2\x80\x94",
        "\xc2\x98" => "\xcb\x9c",
        "\xc2\x99" => "\xe2\x84\xa2",
        "\xc2\x9a" => "\xc5\xa1",
        "\xc2\x9b" => "\xe2\x80\xba",
        "\xc2\x9c" => "\xc5\x93",
        "\xc2\x9e" => "\xc5\xbe",
        "\xc2\x9f" => "\xc5\xb8"
    ];

    protected const UTF8_TO_WIN1252 = [
        "\xe2\x82\xac" => "\x80",
        "\xe2\x80\x9a" => "\x82",
        "\xc6\x92" => "\x83",
        "\xe2\x80\x9e" => "\x84",
        "\xe2\x80\xa6" => "\x85",
        "\xe2\x80\xa0" => "\x86",
        "\xe2\x80\xa1" => "\x87",
        "\xcb\x86" => "\x88",
        "\xe2\x80\xb0" => "\x89",
        "\xc5\xa0" => "\x8a",
        "\xe2\x80\xb9" => "\x8b",
        "\xc5\x92" => "\x8c",
        "\xc5\xbd" => "\x8e",
        "\xe2\x80\x98" => "\x91",
        "\xe2\x80\x99" => "\x92",
        "\xe2\x80\x9c" => "\x93",
        "\xe2\x80\x9d" => "\x94",
        "\xe2\x80\xa2" => "\x95",
        "\xe2\x80\x93" => "\x96",
        "\xe2\x80\x94" => "\x97",
        "\xcb\x9c" => "\x98",
        "\xe2\x84\xa2" => "\x99",
        "\xc5\xa1" => "\x9a",
        "\xe2\x80\xba" => "\x9b",
        "\xc5\x93" => "\x9c",
        "\xc5\xbe" => "\x9e",
        "\xc5\xb8" => "\x9f"
    ];

    protected const EQUIVALENCES = [
        'ISO88591' => 'ISO-8859-1',
        'ISO8859' => 'ISO-8859-1',
        'ISO' => 'ISO-8859-1',
        'LATIN1' => 'ISO-8859-1',
        'LATIN' => 'ISO-8859-1',
        'UTF8' => 'UTF-8',
        'UTF' => 'UTF-8',
        'WIN1252' => 'ISO-8859-1',
        'WINDOWS1252' => 'ISO-8859-1'
    ];

    /**
     * Function \ForceUTF8\Encoding::toUTF8
     * This function leaves UTF8 characters alone, while converting almost all non-UTF8 to UTF8.
     * It assumes that the encoding of the original string is either Windows-1252 or ISO 8859-1.
     *
     * @param string|array<mixed> $text Any string or array of strings.
     * @return ($text is string ? string : array<mixed>) The same string or array, UTF8 encoded.
     */
    public static function toUTF8(string|array $text): string|array
    {
        if (is_array($text)) {
            foreach ($text as $k => $v) {
                $text[$k] = is_string($v) || is_array($v) ? self::toUTF8($v) : $v;
            }
            return $text;
        }

        $max = self::strlen($text);
        $buf = '';

        for ($i = 0; $i < $max; $i++) {
            $c1 = $text[$i];

            if ($c1 >= "\xc0") { // 2, 3, or 4 byte sequence, or byte requiring conversion
                $c2 = $i + 1 >= $max ? "\x00" : $text[$i + 1];
                $c3 = $i + 2 >= $max ? "\x00" : $text[$i + 2];
                $c4 = $i + 3 >= $max ? "\x00" : $text[$i + 3];

                if ($c1 >= "\xc2" && $c1 <= "\xdf") { // Valid 2-byte UTF-8 sequence
                    if ($c2 >= "\x80" && $c2 <= "\xbf") {
                        $buf .= $c1 . $c2;
                        $i++;
                        continue;
                    }
                } elseif ($c1 >= "\xe0" && $c1 <= "\xef") { // Valid 3-byte UTF-8 sequence
                    $valid3 = match ($c1) {
                        "\xe0" => $c2 >= "\xa0" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf",
                        "\xed" => $c2 >= "\x80" && $c2 <= "\x9f" && $c3 >= "\x80" && $c3 <= "\xbf",
                        default => $c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf",
                    };
                    if ($valid3) {
                        $buf .= $c1 . $c2 . $c3;
                        $i += 2;
                        continue;
                    }
                } elseif ($c1 >= "\xf0" && $c1 <= "\xf4") { // Valid 4-byte UTF-8 sequence
                    $valid4 = match ($c1) {
                        "\xf0" => $c2 >= "\x90" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" && $c4 >= "\x80" && $c4 <= "\xbf",
                        "\xf4" => $c2 >= "\x80" && $c2 <= "\x8f" && $c3 >= "\x80" && $c3 <= "\xbf" && $c4 >= "\x80" && $c4 <= "\xbf",
                        default => $c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" && $c4 >= "\x80" && $c4 <= "\xbf",
                    };
                    if ($valid4) {
                        $buf .= $c1 . $c2 . $c3 . $c4;
                        $i += 3;
                        continue;
                    }
                }

                // Not valid UTF-8 lead or continuation: convert single byte (0xC0-0xFF)
                $buf .= chr(0xC0 | (ord($c1) >> 6)) . chr(0x80 | (ord($c1) & 0x3F));
            } elseif ($c1 >= "\x80") { // 0x80 to 0xBF: Windows-1252 or Latin1 continuation
                $buf .= self::WIN1252_TO_UTF8[ord($c1)]
                    ?? (chr(0xC0 | (ord($c1) >> 6)) . chr(0x80 | (ord($c1) & 0x3F)));
            } else { // 0x00 to 0x7F: ASCII
                $buf .= $c1;
            }
        }

        return $buf;
    }

    /**
     * @param string|array<mixed> $text
     * @param IconvOptions $option
     * @return ($text is string ? string|false : array<mixed>)
     */
    public static function toWin1252(string|array $text, IconvOptions $option = IconvOptions::WITHOUT_ICONV): string|array|false
    {
        if (is_array($text)) {
            foreach ($text as $k => $v) {
                $text[$k] = is_string($v) || is_array($v) ? self::toWin1252($v, $option) : $v;
            }
            return $text;
        }

        return static::utf8_decode($text, $option);
    }

    /**
     * @param string|array<mixed> $text
     * @param IconvOptions $option
     * @return ($text is string ? string|false : array<mixed>)
     */
    public static function toISO8859(string|array $text, IconvOptions $option = IconvOptions::WITHOUT_ICONV): string|array|false
    {
        return self::toWin1252($text, $option);
    }

    /**
     * @param string|array<mixed> $text
     * @param IconvOptions $option
     * @return ($text is string ? string|false : array<mixed>)
     */
    public static function toLatin1(string|array $text, IconvOptions $option = IconvOptions::WITHOUT_ICONV): string|array|false
    {
        return self::toWin1252($text, $option);
    }

    /**
     * @param string|array<mixed> $text
     * @param IconvOptions $option
     * @return ($text is string ? string|false : array<mixed>)
     */
    public static function fixUTF8(string|array $text, IconvOptions $option = IconvOptions::WITHOUT_ICONV): string|array|false
    {
        if (is_array($text)) {
            foreach ($text as $k => $v) {
                $text[$k] = is_string($v) || is_array($v) ? self::fixUTF8($v, $option) : $v;
            }
            return $text;
        }

        $last = '';
        while ($last !== $text) {
            $last = $text;
            $decoded = static::utf8_decode($text, $option);
            if ($decoded === false) {
                return false;
            }
            $text = self::toUTF8($decoded);
        }

        $decoded = static::utf8_decode($text, $option);
        if ($decoded === false) {
            return false;
        }

        return self::toUTF8($decoded);
    }

    /**
     * @param string|array<mixed> $text
     * @return ($text is string ? string : array<mixed>)
     */
    public static function UTF8FixWin1252Chars(string|array $text): string|array
    {
        if (is_array($text)) {
            foreach ($text as $k => $v) {
                $text[$k] = is_string($v) || is_array($v) ? self::UTF8FixWin1252Chars($v) : $v;
            }
            return $text;
        }

        return str_replace(
            array_keys(self::BROKEN_UTF8_TO_UTF8),
            array_values(self::BROKEN_UTF8_TO_UTF8),
            $text
        );
    }

    public static function removeBOM(string $str = ''): string
    {
        return str_starts_with($str, "\xef\xbb\xbf") ? substr($str, 3) : $str;
    }

    protected static function strlen(string $text): int
    {
        return strlen($text);
    }

    public static function normalizeEncoding(string $encodingLabel): string
    {
        $encoding = preg_replace('/[^a-zA-Z0-9\s]/', '', strtoupper($encodingLabel)) ?? '';
        return self::EQUIVALENCES[$encoding] ?? 'UTF-8';
    }

    /**
     * @param string $encodingLabel
     * @param string|array<mixed> $text
     * @return ($text is string ? string|false : array<mixed>)
     */
    public static function encode(string $encodingLabel, string|array $text): string|array|false
    {
        $normalized = self::normalizeEncoding($encodingLabel);
        return $normalized === 'ISO-8859-1'
            ? self::toLatin1($text)
            : self::toUTF8($text);
    }

    /**
     * @param string $text
     * @param IconvOptions $option
     * @return string|false
     */
    protected static function utf8_decode(string $text, IconvOptions $option = IconvOptions::WITHOUT_ICONV): string|false
    {
        if ($option !== IconvOptions::WITHOUT_ICONV && function_exists('iconv')) {
            $iconvOption = match ($option) {
                IconvOptions::ICONV_TRANSLIT => '//TRANSLIT',
                IconvOptions::ICONV_IGNORE => '//IGNORE',
            };
            return \iconv(
                from_encoding: 'UTF-8',
                to_encoding: "Windows-1252{$iconvOption}",
                string: $text
            );
        }

        $text = str_replace(
            array_keys(self::UTF8_TO_WIN1252),
            array_values(self::UTF8_TO_WIN1252),
            self::toUTF8($text)
        );

        if (function_exists('mb_convert_encoding')) {
            return \mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        }

        if (extension_loaded('intl')) {
            return \UConverter::transcode($text, 'ISO-8859-1', 'UTF-8');
        }

        return function_exists('iconv')
            ? \iconv(from_encoding: 'UTF-8', to_encoding: 'ISO-8859-1//IGNORE', string: $text)
            : false;
    }
}
