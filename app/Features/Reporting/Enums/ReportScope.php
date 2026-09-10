<?php

declare(strict_types=1);

namespace App\Features\Reporting\Enums;

enum ReportScope: string
{
    case Workspace = 'workspace';
    case Platform = 'platform';
}
