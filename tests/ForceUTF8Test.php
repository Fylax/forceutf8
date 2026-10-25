<?php

declare(strict_types=1);

use ForceUTF8\Encoding;
use ForceUTF8\IconvOptions;
use PHPUnit\Framework\TestCase;

final class ForceUTF8Test extends TestCase
{
    private static function utf8Encode(string $string): string
    {
        return mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
    }

    /**
     * @testdox Source files must not use the same encoding before conversion.
     */
    public function testInputFilesDifferentEncoding(): void
    {
        $this->assertFileNotEquals(__DIR__ . '/data/test1.txt', __DIR__ . '/data/test1Latin.txt');
    }

    /**
     * @testdox Simple Encoding works.
     */
    public function testSimpleEncodingWorks(): void
    {
        $preEncodedFile = file_get_contents(__DIR__ . '/data/test1.txt');
        $this->assertIsString($preEncodedFile);

        $latinFile = file_get_contents(__DIR__ . '/data/test1Latin.txt');
        $this->assertIsString($latinFile);

        $libraryEncodedFile = Encoding::toUTF8($latinFile);
        $this->assertSame($libraryEncodedFile, $preEncodedFile);
    }

    /**
     * @testdox Russian text in UTF-8 remains intact in toUTF8().
     */
    public function testRussianTextWorks(): void
    {
        $russian = file_get_contents(__DIR__ . '/data/russian.txt');
        $this->assertIsString($russian);

        $this->assertSame($russian, Encoding::toUTF8($russian));
    }

    /**
     * @testdox Source arrays are different.
     */
    public function testMultipleInputFilesDifferentEncoding(): void
    {
        $arr1 = [
            file_get_contents(__DIR__ . '/data/test1Latin.txt'),
            file_get_contents(__DIR__ . '/data/test1.txt'),
            file_get_contents(__DIR__ . '/data/test1Latin.txt')
        ];
        $arr2 = [
            file_get_contents(__DIR__ . '/data/test1.txt'),
            file_get_contents(__DIR__ . '/data/test1.txt'),
            file_get_contents(__DIR__ . '/data/test1.txt')
        ];
        $this->assertNotSame($arr1, $arr2);
    }

    /**
     * @testdox Encoding of array works.
     */
    public function testMultipleInputFilesEncodingWorks(): void
    {
        $arr1 = [
            file_get_contents(__DIR__ . '/data/test1Latin.txt'),
            file_get_contents(__DIR__ . '/data/test1.txt'),
            file_get_contents(__DIR__ . '/data/test1Latin.txt')
        ];
        $arr2 = [
            file_get_contents(__DIR__ . '/data/test1.txt'),
            file_get_contents(__DIR__ . '/data/test1.txt'),
            file_get_contents(__DIR__ . '/data/test1.txt')
        ];
        $this->assertSame(Encoding::toUTF8($arr1), $arr2);
    }

    /**
     * @testdox fixUTF8() maintains UTF-8 string.
     */
    public function testMaintainsUtf8Strings(): void
    {
        $originalContent = file_get_contents(__DIR__ . '/data/test1.txt');
        $this->assertIsString($originalContent);

        $encodedContent = Encoding::fixUTF8($originalContent);
        $this->assertSame($encodedContent, $originalContent);
    }

    /**
     * @testdox An UTF-8 double encoded string differs from a correct UTF-8 string.
     */
    public function testDoubleEncodedStringDiffersFromOriginal(): void
    {
        $originalContent = file_get_contents(__DIR__ . '/data/test1.txt');
        $this->assertIsString($originalContent);

        $doubleEncoded = self::utf8Encode($originalContent);
        $this->assertNotSame($originalContent, $doubleEncoded);
    }

    /**
     * @testdox fixUTF8() reverts to UTF-8 a double encoded string.
     */
    public function testRevertDoubleEncodedString(): void
    {
        $originalContent = file_get_contents(__DIR__ . '/data/test1.txt');
        $this->assertIsString($originalContent);

        $doubleEncoded = self::utf8Encode($originalContent);
        $fixed = Encoding::fixUTF8($doubleEncoded);
        $this->assertSame($originalContent, $fixed);
    }

    /**
     * @testdox Source arrays are different (double encoded).
     */
    public function testDoubleEncodedMultipleStringsDiffersFromOriginal(): void
    {
        $test1Latin = file_get_contents(__DIR__ . '/data/test1Latin.txt');
        $test1 = file_get_contents(__DIR__ . '/data/test1.txt');
        $this->assertIsString($test1Latin);
        $this->assertIsString($test1);

        $originalContent = [
            self::utf8Encode($test1Latin),
            self::utf8Encode($test1),
            self::utf8Encode($test1Latin)
        ];
        $doubleEncoded = [
            $test1,
            $test1,
            $test1
        ];
        $this->assertNotSame($originalContent, $doubleEncoded);
    }

    /**
     * @testdox Fixing of double encoded array works.
     */
    public function testRevertDoubleEncodedMultipleStrings(): void
    {
        $test1Latin = file_get_contents(__DIR__ . '/data/test1Latin.txt');
        $test1 = file_get_contents(__DIR__ . '/data/test1.txt');
        $this->assertIsString($test1Latin);
        $this->assertIsString($test1);

        $originalContent = [
            $test1,
            $test1,
            $test1
        ];
        $doubleEncoded = [
            self::utf8Encode($test1Latin),
            self::utf8Encode($test1),
            self::utf8Encode($test1Latin)
        ];
        $fixed = Encoding::fixUTF8($doubleEncoded);
        $this->assertSame($originalContent, $fixed);
    }

    public function testHardcodedStrings(): void
    {
        $this->assertSame(
            "Fédération Camerounaise de Football\n",
            Encoding::fixUTF8("FÃÂ©dération Camerounaise de Football\n")
        );
        $this->assertSame(
            "Fédération Camerounaise de Football\n",
            Encoding::fixUTF8("FÃ©dÃ©ration Camerounaise de Football\n")
        );
        $this->assertSame(
            "Fédération Camerounaise de Football\n",
            Encoding::fixUTF8("FÃÂ©dÃÂ©ration Camerounaise de Football\n")
        );
        $this->assertSame(
            "Fédération Camerounaise de Football\n",
            Encoding::fixUTF8("FÃÂÂÂÂ©dÃÂÂÂÂ©ration Camerounaise de Football\n")
        );
    }

    /**
     * @testdox normalizeEncoding maps aliases to ISO-8859-1 or UTF-8 and falls back gracefully.
     */
    public function testNormalizeEncoding(): void
    {
        $this->assertSame('ISO-8859-1', Encoding::normalizeEncoding('ISO-8859-1'));
        $this->assertSame('ISO-8859-1', Encoding::normalizeEncoding('ISO88591'));
        $this->assertSame('ISO-8859-1', Encoding::normalizeEncoding('ISO8859'));
        $this->assertSame('ISO-8859-1', Encoding::normalizeEncoding('ISO'));
        $this->assertSame('ISO-8859-1', Encoding::normalizeEncoding('latin1'));
        $this->assertSame('ISO-8859-1', Encoding::normalizeEncoding('LATIN'));
        $this->assertSame('ISO-8859-1', Encoding::normalizeEncoding('WIN1252'));
        $this->assertSame('ISO-8859-1', Encoding::normalizeEncoding('WINDOWS-1252'));
        $this->assertSame('UTF-8', Encoding::normalizeEncoding('UTF-8'));
        $this->assertSame('UTF-8', Encoding::normalizeEncoding('UTF8'));
        $this->assertSame('UTF-8', Encoding::normalizeEncoding('UTF'));
        $this->assertSame('UTF-8', Encoding::normalizeEncoding('unknown-encoding'));
    }

    /**
     * @testdox encode() correctly converts to ISO-8859-1 or UTF-8 based on label.
     */
    public function testEncode(): void
    {
        $utf8 = 'Hírek';
        $latin1 = "H\xedrek";

        $this->assertSame($latin1, Encoding::encode('LATIN1', $utf8));
        $this->assertSame($utf8, Encoding::encode('UTF-8', $latin1));

        $array = ['a' => $utf8, 'b' => [$utf8]];
        $expected = ['a' => $latin1, 'b' => [$latin1]];
        $this->assertSame($expected, Encoding::encode('LATIN1', $array));
        $this->assertSame($array, Encoding::encode('UTF-8', $expected));
    }

    /**
     * @testdox toUTF8() properly converts invalid 2-byte lead bytes (0xC0 and 0xC1).
     */
    public function testToUTF8ConvertsInvalidLeadBytesC0AndC1(): void
    {
        // In ISO-8859-1, 0xC0 is 'À' and 0xAB is '«'.
        // 0xC0 is never a valid UTF-8 lead byte (RFC 3629).
        $input = "\xc0\xab";
        $converted = Encoding::toUTF8($input);

        $this->assertTrue(mb_check_encoding($converted, 'UTF-8'));
        $this->assertSame("\xc3\x80\xc2\xab", $converted);

        // 0xC1 is 'Á'
        $input2 = "\xc1\xab";
        $converted2 = Encoding::toUTF8($input2);

        $this->assertTrue(mb_check_encoding($converted2, 'UTF-8'));
        $this->assertSame("\xc3\x81\xc2\xab", $converted2);

        // Invalid 2-byte continuation (ASCII following lead byte)
        $invalid2 = "\xc2 ";
        $this->assertSame("\xc3\x82 ", Encoding::toUTF8($invalid2));
    }

    /**
     * @testdox toUTF8() handles all 3-byte and 4-byte UTF-8 sequences and boundaries.
     */
    public function testToUTF8MultibyteSequences(): void
    {
        // 3-byte valid sequences:
        // E0 + A0-BF
        $e0 = "\xe0\xa0\x80"; // U+0800
        $this->assertSame($e0, Encoding::toUTF8($e0));

        // ED + 80-9F
        $ed = "\xed\x80\x80"; // U+D000
        $this->assertSame($ed, Encoding::toUTF8($ed));

        // E1-EC, EE-EF
        $chinese = "中文"; // \xe4\xb8\xad\xe6\x96\x87
        $this->assertSame($chinese, Encoding::toUTF8($chinese));

        // 3-byte invalid overlong (E0 + 80-9F) and surrogate (ED + A0-BF)
        $overlong3 = "\xe0\x80\x80";
        $convertedOverlong3 = Encoding::toUTF8($overlong3);
        $this->assertTrue(mb_check_encoding($convertedOverlong3, 'UTF-8'));

        $surrogate = "\xed\xa0\x80";
        $convertedSurrogate = Encoding::toUTF8($surrogate);
        $this->assertTrue(mb_check_encoding($convertedSurrogate, 'UTF-8'));

        $invalid3Cont = "\xe1 \x80";
        $this->assertSame("\xc3\xa1 \xe2\x82\xac", Encoding::toUTF8($invalid3Cont));

        // 4-byte valid sequences:
        // F0 + 90-BF
        $f0 = "\xf0\x90\x80\x80"; // U+10000
        $this->assertSame($f0, Encoding::toUTF8($f0));

        // F1-F3
        $f1 = "\xf1\x80\x80\x80";
        $this->assertSame($f1, Encoding::toUTF8($f1));

        // F4 + 80-8F
        $f4 = "\xf4\x80\x80\x80"; // U+100000
        $this->assertSame($f4, Encoding::toUTF8($f4));

        // 4-byte invalid sequences:
        $invalidF0 = "\xf0\x80\x80\x80";
        $this->assertTrue(mb_check_encoding(Encoding::toUTF8($invalidF0), 'UTF-8'));

        $invalidF4 = "\xf4\x90\x80\x80";
        $this->assertTrue(mb_check_encoding(Encoding::toUTF8($invalidF4), 'UTF-8'));

        $invalid4Cont = "\xf1 \x80\x80";
        $this->assertTrue(mb_check_encoding(Encoding::toUTF8($invalid4Cont), 'UTF-8'));

        // 0xF5 is 'õ' in ISO-8859-1, never valid in UTF-8
        $f5 = "\xf5\x80\x80\x80";
        $convertedF5 = Encoding::toUTF8($f5);
        $this->assertTrue(mb_check_encoding($convertedF5, 'UTF-8'));
        $this->assertStringStartsWith("\xc3\xb5", $convertedF5);
    }

    /**
     * @testdox toUTF8() correctly converts Windows-1252 and Latin1 continuation bytes.
     */
    public function testToUTF8Windows1252AndLatin1Bytes(): void
    {
        // 0x80 is Euro sign in Win1252
        $this->assertSame("€", Encoding::toUTF8("\x80"));
        // 0x95 is Bullet in Win1252
        $this->assertSame("•", Encoding::toUTF8("\x95"));
        // 0xA0 is Non-breaking space in Latin1 (not in WIN1252_TO_UTF8 array)
        $this->assertSame("\xc2\xa0", Encoding::toUTF8("\xa0"));
        // 0xA9 is Copyright sign
        $this->assertSame("©", Encoding::toUTF8("\xa9"));
    }

    /**
     * @testdox toWin1252(), toISO8859(), and toLatin1() work with strings and arrays.
     */
    public function testToLatin1AndAliases(): void
    {
        $utf8 = 'Hírek';
        $latin1 = "H\xedrek";

        $this->assertSame($latin1, Encoding::toWin1252($utf8));
        $this->assertSame($latin1, Encoding::toISO8859($utf8));
        $this->assertSame($latin1, Encoding::toLatin1($utf8));

        $array = ['a' => $utf8, 'b' => [$utf8], 'c' => 123];
        $expected = ['a' => $latin1, 'b' => [$latin1], 'c' => 123];

        $this->assertSame($expected, Encoding::toWin1252($array));
        $this->assertSame($expected, Encoding::toISO8859($array));
        $this->assertSame($expected, Encoding::toLatin1($array));
    }

    /**
     * @testdox toUTF8() and fixUTF8() support recursive nested arrays.
     */
    public function testNestedArrays(): void
    {
        $input = [
            'level1' => [
                'level2' => "H\xedrek",
                'mixed' => ["F\xe9d\xe9ration", 42, true, null]
            ]
        ];

        $expected = [
            'level1' => [
                'level2' => 'Hírek',
                'mixed' => ['Fédération', 42, true, null]
            ]
        ];

        $this->assertSame($expected, Encoding::toUTF8($input));
        $this->assertSame($expected, Encoding::fixUTF8($input));
    }

    /**
     * @testdox removeBOM() removes UTF-8 BOM only when present.
     */
    public function testRemoveBOM(): void
    {
        $withBOM = "\xef\xbb\xbfHello World";
        $this->assertSame('Hello World', Encoding::removeBOM($withBOM));

        $withoutBOM = 'Hello World';
        $this->assertSame('Hello World', Encoding::removeBOM($withoutBOM));

        $this->assertSame('', Encoding::removeBOM(''));
    }

    /**
     * @testdox IconvOptions backed enum functions and exception handling.
     */
    public function testIconvOptions(): void
    {
        $this->assertSame(IconvOptions::ICONV_TRANSLIT, IconvOptions::from('TRANSLIT'));
        $this->assertSame(IconvOptions::ICONV_IGNORE, IconvOptions::from('IGNORE'));
        $this->assertSame(IconvOptions::WITHOUT_ICONV, IconvOptions::from(''));
        $this->assertSame('TRANSLIT', IconvOptions::ICONV_TRANSLIT->value);
        $this->assertNull(IconvOptions::tryFrom('INVALID'));

        $this->expectException(\ValueError::class);
        IconvOptions::from('INVALID');
    }

    /**
     * @testdox UTF8FixWin1252Chars fixes broken UTF-8 byte sequences.
     */
    public function testUTF8FixWin1252Chars(): void
    {
        // 0xC2 0x80 is broken UTF-8 for Win1252 Euro 0x80 (should be U+20AC \xe2\x82\xac)
        $broken = "\xc2\x80";
        $fixed = Encoding::UTF8FixWin1252Chars($broken);
        $this->assertSame("\xe2\x82\xac", $fixed);

        $arrayBroken = ['test' => ["\xc2\x80"], 'num' => 123];
        $this->assertSame(['test' => ["\xe2\x82\xac"], 'num' => 123], Encoding::UTF8FixWin1252Chars($arrayBroken));
    }

    /**
     * @testdox Win1252 characters are handled properly in fixUTF8 and toWin1252.
     */
    public function testWin1252Conversion(): void
    {
        $str = "FÃÂ©dération Camerounaise—de—Football\n";
        // WITHOUT_ICONV breaks U+2014 to '?' as documented
        $broken = Encoding::fixUTF8($str, IconvOptions::WITHOUT_ICONV);
        $this->assertSame("Fédération Camerounaise?de?Football\n", $broken);

        if (function_exists('iconv')) {
            $preserved = Encoding::fixUTF8($str, IconvOptions::ICONV_IGNORE);
            $this->assertSame("Fédération Camerounaise—de—Football\n", $preserved);

            $translit = Encoding::fixUTF8($str, IconvOptions::ICONV_TRANSLIT);
            $this->assertSame("Fédération Camerounaise—de—Football\n", $translit);

            $win1252 = Encoding::toWin1252("—", IconvOptions::ICONV_TRANSLIT);
            $this->assertSame("\x97", $win1252);
        }
    }
}
