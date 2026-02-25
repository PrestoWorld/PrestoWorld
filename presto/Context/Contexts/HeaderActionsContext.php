<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Contexts;

use PrestoWorld\Context\AbstractContext;

/**
 * Header Actions Context (sub-context of HeaderContext)
 *
 * Holds items rendered in the right side of the header:
 * AvatarContext, lang-switcher, CTA buttons, notification bells, etc.
 */
class HeaderActionsContext extends AbstractContext
{
    public function getName(): string  { return 'header.actions'; }
    public function getLabel(): string { return 'Header Actions'; }
}
