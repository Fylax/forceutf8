<?php

declare(strict_types=1);

namespace ForceUTF8;

enum IconvOptions
{
    case ICONV_TRANSLIT;
    case ICONV_IGNORE;
    case WITHOUT_ICONV;

    /**
     * @deprecated Allow transition from
     */
    public static function fromString(string $value): IconvOptions
    {
        if ($value === 'TRANSLIT') {
            return self::ICONV_TRANSLIT;
        }
        if ($value === 'IGNORE') {
            return self::ICONV_IGNORE;
        }
        if ($value === '') {
            return self::WITHOUT_ICONV;
        }
        throw new \InvalidArgumentException('Invalid option');
    }
}
