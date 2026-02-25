<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Contexts;

use PrestoWorld\Context\AbstractContext;

/**
 * Home Section Context
 *
 * Holds all sections of the modular home page.
 * Modules register SectionContexts (Hero, Features, Pricing, etc.) here.
 */
class HomeSectionContext extends AbstractContext
{
    public function getName(): string  { return 'home.sections'; }
    public function getLabel(): string { return 'Home Page Sections'; }
}
