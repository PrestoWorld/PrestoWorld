<?php

declare(strict_types=1);

namespace PrestoWorld\Theme;

enum ThemeType: string
{
    case NATIVE = 'native';
    case GUTENBERG = 'gutenberg';
    case LEGACY = 'legacy';
}
