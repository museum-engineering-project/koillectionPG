<?php

declare(strict_types=1);

namespace App\Enum;

class OrientationEnum
{
    public const ORIENTATION_VERTICAL = 'vertical';

    public const ORIENTATION_HORIZONTAL = 'horizontal';

    public const ORIENTATIONS = [
        self::ORIENTATION_VERTICAL => self::ORIENTATION_VERTICAL,
        self::ORIENTATION_HORIZONTAL => self::ORIENTATION_HORIZONTAL
    ];

    public static function getOrientations(): array
    {
        return self::ORIENTATIONS;
    }
}
