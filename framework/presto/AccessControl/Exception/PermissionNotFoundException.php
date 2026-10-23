<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Exception;

class PermissionNotFoundException extends AccessControlException
{
    public static function for(string $slug): self
    {
        return new self("Permission '{$slug}' not found.");
    }
}
