<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Exception;

class RoleNotFoundException extends AccessControlException
{
    public static function for(string $slug): self
    {
        return new self("Role '{$slug}' not found.");
    }
}
