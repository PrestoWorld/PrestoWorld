<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Exception;

class SubjectNotFoundException extends AccessControlException
{
    public static function for(string $subject): self
    {
        return new self("Subject '{$subject}' not found.");
    }
}
