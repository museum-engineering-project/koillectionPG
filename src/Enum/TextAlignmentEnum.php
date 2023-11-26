<?php

declare(strict_types=1);

namespace App\Enum;

class TextAlignmentEnum
{
    public const TEXT_ALIGN_LEFT = 'left';

    public const TEXT_ALIGN_CENTER = 'center';

    public const TEXT_ALIGNMENTS = [
        self::TEXT_ALIGN_LEFT => 'global.generate_label.textAlignment_left',
        self::TEXT_ALIGN_CENTER => 'global.generate_label.textAlignment_center'
    ];

    public static function getTextAlignments(): array
    {
        return self::TEXT_ALIGNMENTS;
    }
}
