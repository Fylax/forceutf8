<?php

declare(strict_types=1);

namespace ForceUTF8;

enum IconvOptions: string
{
    case ICONV_TRANSLIT = 'TRANSLIT';
    case ICONV_IGNORE = 'IGNORE';
    case WITHOUT_ICONV = '';
}
