<?php

declare(strict_types=1);

namespace App\Enum;

class TextAlignmentEnum
{
    public const TEXT_ALIGN_LEFT = 'left';

    public const TEXT_ALIGN_CENTER = 'center';

    public const TEXT_ALIGNMENTS = [
        self::TEXT_ALIGN_LEFT => self::TEXT_ALIGN_LEFT,
        self::TEXT_ALIGN_CENTER => self::TEXT_ALIGN_CENTER
    ];

    public static function getTextAlignments(): array
    {
        return self::TEXT_ALIGNMENTS;
    }
}
