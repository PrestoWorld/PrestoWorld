<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Domain\Enums;

enum SyncStatus: string
{
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function isFinished(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Đang chờ',
            self::RUNNING => 'Đang chạy',
            self::COMPLETED => 'Hoàn tất',
            self::FAILED => 'Thất bại',
            self::CANCELLED => 'Đã huỷ',
        };
    }
}
