<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Contexts;

use PrestoWorld\Context\AbstractContext;

/**
 * Header Navigation Context (sub-context of HeaderContext)
 *
 * Holds the main nav MenuItemContexts (top-level items with optional dropdown groups).
 * This is what gets rendered as the <nav><ul> in header.php.
 */
class HeaderNavContext extends AbstractContext
{
    public function getName(): string  { return 'header.nav'; }
    public function getLabel(): string { return 'Header Navigation'; }
}
