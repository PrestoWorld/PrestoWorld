<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Contexts;

use PrestoWorld\Context\AbstractContext;

/**
 * Portal Menu Context
 *
 * Holds navigation links for the Customer Portal.
 * Items like 'My Services', 'Invoices', 'Settings' are registered here.
 */
class PortalMenuContext extends AbstractContext
{
    public function getName(): string  { return 'portal.menu'; }
    public function getLabel(): string { return 'Customer Portal Menu'; }
}
