<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Domain\Enums;

enum RecordStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';
}
