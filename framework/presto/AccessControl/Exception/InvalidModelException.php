<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Exception;

class InvalidModelException extends AccessControlException
{
    public static function unsupported(string $model): self
    {
        return new self("Access control model '{$model}' is not supported.");
    }

    public static function notLoaded(string $model): self
    {
        return new self("Access control model '{$model}' is not loaded.");
    }
}
