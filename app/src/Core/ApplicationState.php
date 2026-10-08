<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * ApplicationState — is_blog_installed (spec 10 §10.4).
 */
final class ApplicationState
{
    private function __construct()
    {
    }

    public static function installing(): bool
    {
        return (bool) Config::get('installing', false);
    }

    public static function setInstalling(bool $installing): void
    {
        Config::set('installing', $installing);
    }
}