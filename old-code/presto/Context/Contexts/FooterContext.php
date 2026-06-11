<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Contexts;

use PrestoWorld\Context\AbstractContext;

/**
 * Footer Menu Context
 *
 * Holds all footer column groups and their links.
 * Each MenuItemContext registered here can itself carry children (the column links).
 *
 * Sub-contexts per column:
 *   'footer.solutions'     → Solutions column links
 *   'footer.company'       → Company column links
 *   'footer.payment'       → Payment methods badges
 */
class FooterContext extends AbstractContext
{
    public function getName(): string  { return 'footer'; }
    public function getLabel(): string { return 'Site Footer'; }
}
