<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Theme;

enum ThemeType: string
{
    case CLASSIC = 'classic';
    case BLOCK = 'block';

    public function label(): string
    {
        return match ($this) {
            self::CLASSIC => 'Classic Theme (PHP templates)',
            self::BLOCK => 'Block Theme (FSE / theme.json)',
        };
    }
}
