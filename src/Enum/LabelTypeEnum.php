<?php

declare(strict_types=1);

namespace App\Enum;

class LabelTypeEnum
{
    public const LABEL_TYPE_STANDARD = 'standard';

    public const LABEL_TYPE_TABLE = 'table';

    public const LABEL_TYPES = [
        self::LABEL_TYPE_STANDARD => 'global.generate_label.labelType.standard',
        self::LABEL_TYPE_TABLE => 'global.generate_label.labelType.table'
    ];

    public static function getLabelTypes(): array
    {
        return self::LABEL_TYPES;
    }
}
