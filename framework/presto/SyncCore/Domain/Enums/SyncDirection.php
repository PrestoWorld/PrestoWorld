<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Domain\Enums;

enum SyncDirection: string
{
    case INBOUND = 'inbound';
    case OUTBOUND = 'outbound';

    public function label(): string
    {
        return match ($this) {
            self::INBOUND => 'Nhanh.vn → Bookowl',
            self::OUTBOUND => 'Bookowl → Nhanh.vn',
        };
    }
}
