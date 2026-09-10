<?php

declare(strict_types=1);

namespace App\Features\Reporting\Enums;

enum ExportFormat: string
{
    case Csv = 'csv';
    case Pdf = 'pdf';

    /**
     * @return list<string>
     */
    public static function supportedValues(): array
    {
        return [self::Csv->value];
    }
}
