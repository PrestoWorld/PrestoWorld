<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Contexts;

use PrestoWorld\Context\AbstractContext;

/**
 * Header Context
 *
 * Holds all placeholders for the site header:
 *  - Navigation menu items (top-level + dropdown groups)
 *  - Header action area items (avatar, lang-switcher, CTA buttons…)
 *
 * Modules register into sub-zones via the ContextRegistry:
 *   - 'header.nav'     → main navigation MenuItemContexts
 *   - 'header.actions' → right-side action items (AvatarContext, etc.)
 */
class HeaderContext extends AbstractContext
{
    public function getName(): string  { return 'header'; }
    public function getLabel(): string { return 'Site Header'; }
}
