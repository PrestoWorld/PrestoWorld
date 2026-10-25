<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Domain\Enums;

enum SyncTrigger: string
{
    case MANUAL = 'manual';
    case SCHEDULE = 'schedule';
    case WEBHOOK = 'webhook';
    case CLI = 'cli';
}
