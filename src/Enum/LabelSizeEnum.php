<?php

declare(strict_types=1);

namespace App\Enum;

class LabelSizeEnum
{
    public const SIZE_A4 = 'A4';

    public const SIZE_A5 = 'A5';

    public const SIZE_A6 = 'A6';

    public const SIZE_A7 = 'A7';

    public const LABEL_SIZES = [
        self::SIZE_A4 => self::SIZE_A4,
        self::SIZE_A5 => self::SIZE_A5,
        self::SIZE_A6 => self::SIZE_A6,
        self::SIZE_A7 => self::SIZE_A7
    ];

    public static function getLabelSizes(): array
    {
        return self::LABEL_SIZES;
    }
}
