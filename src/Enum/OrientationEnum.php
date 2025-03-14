<?php

declare(strict_types=1);

namespace App\Enum;

class OrientationEnum
{
    public const ORIENTATION_VERTICAL = 'vertical';

    public const ORIENTATION_HORIZONTAL = 'horizontal';

    public const ORIENTATIONS = [
        self::ORIENTATION_VERTICAL => 'global.generate_label.orientation.vertical',
        self::ORIENTATION_HORIZONTAL => 'global.generate_label.orientation.horizontal'
    ];

    public static function getOrientations(): array
    {
        return self::ORIENTATIONS;
    }
}
