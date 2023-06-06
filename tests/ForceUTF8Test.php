<?php
declare(strict_types=1);

use ForceUTF8\Encoding;
use PHPUnit\Framework\TestCase;

final class ForceUTF8Test extends TestCase
{
  /**
   * @testdox Source files must not use the same encoding before conversion.
   */
  public function testInputFilesDifferentEncoding()
  {
    $this->assertFileNotEquals(dirname(__FILE__) . "/data/test1.txt", dirname(__FILE__) . "/data/test1Latin.txt");
  }

  /**
   * @testdox Simple Encoding works.
   */
  public function testSimpleEncodingWorks() {
    $preEncodedFile = file_get_contents(dirname(__FILE__)."/data/test1.txt");
    $libraryEncodedFile = Encoding::toUTF8(file_get_contents(dirname(__FILE__)."/data/test1Latin.txt"));
    $this->assertSame($libraryEncodedFile, $preEncodedFile);
  }

  /**
   * @testdox Source arrays are different.
   */
  public function testMultipleInputFilesDifferentEncoding() {
    $arr1 = [
      file_get_contents(dirname(__FILE__) . "/data/test1Latin.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1Latin.txt")
    ];
    $arr2 = [
      file_get_contents(dirname(__FILE__) . "/data/test1.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1.txt")
    ];
    $this->assertNotSame($arr1, $arr2);
  }

  /**
   * @testdox Encoding of array works.
   */
  public function testMultipleInputFilesEncodingWorks() {
    $arr1 = array(
      file_get_contents(dirname(__FILE__) . "/data/test1Latin.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1Latin.txt")
    );
    $arr2 = array(
      file_get_contents(dirname(__FILE__) . "/data/test1.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1.txt")
    );
    $this->assertSame(Encoding::toUTF8($arr1), $arr2);
  }

  /**
   * @testdox fixUTF8() maintains UTF-8 string.
   */
  public function testMaintainsUtf8Strings() {
    $originalContent = file_get_contents(dirname(__FILE__) . "/data/test1.txt");
    $encodedContent = Encoding::fixUTF8($originalContent);
    $this->assertSame($encodedContent, $originalContent);
  }

  /**
   * @testdox An UTF-8 double encoded string differs from a correct UTF-8 string.
   */
  public function testDoubleEncodedStringDiffersFromOriginal() {
    $originalContent = file_get_contents(dirname(__FILE__) . "/data/test1.txt");
    $doubleEncoded = utf8_encode(file_get_contents(dirname(__FILE__) . "/data/test1.txt"));
    $this->assertNotSame($originalContent, $doubleEncoded);
  }

  /**
   * @testdox fixUTF8() reverts to UTF-8 a double encoded string.
   */
  public function testRevertDoubleEncodedString() {
    $originalContent = file_get_contents(dirname(__FILE__) . "/data/test1.txt");
    $doubleEncoded = utf8_encode(file_get_contents(dirname(__FILE__) . "/data/test1.txt"));
    $fixed = Encoding::fixUTF8($doubleEncoded);
    $this->assertSame($originalContent, $fixed);
  }

  /**
   * @testdox Source arrays are different (double encoded).
   */
  public function testDoubleEncodedMultipleStringsDiffersFromOriginal() {
    $originalContent = [
      utf8_encode(file_get_contents(dirname(__FILE__) . "/data/test1Latin.txt")),
      utf8_encode(file_get_contents(dirname(__FILE__) . "/data/test1.txt")),
      utf8_encode(file_get_contents(dirname(__FILE__) . "/data/test1Latin.txt"))
    ];
    $doubleEncoded = [
      file_get_contents(dirname(__FILE__) . "/data/test1.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1.txt")
    ];
    $this->assertNotSame($originalContent, $doubleEncoded);
  }

  /**
   * @testdox Fixing of double encoded array works.
   */
  public function testRevertDoubleEncodedMultipleStrings() {
    $originalContent = [
      file_get_contents(dirname(__FILE__) . "/data/test1.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1.txt"),
      file_get_contents(dirname(__FILE__) . "/data/test1.txt")
    ];
    $doubleEncoded = [
      utf8_encode(file_get_contents(dirname(__FILE__) . "/data/test1Latin.txt")),
      utf8_encode(file_get_contents(dirname(__FILE__) . "/data/test1.txt")),
      utf8_encode(file_get_contents(dirname(__FILE__) . "/data/test1Latin.txt"))
    ];
    $fixed = Encoding::fixUTF8($doubleEncoded);
    $this->assertSame($originalContent, $fixed);
  }

  public function testHardcodedStrings() {
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
}