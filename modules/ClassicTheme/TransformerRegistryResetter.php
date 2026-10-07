<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme;

use Witals\Framework\Contracts\ResettableInterface;

/**
 * Bridges the static TransformerRegistry into the framework's ResettableInterface lifecycle.
 *
 * Register this as a singleton in a PrestoWorld service provider so the framework
 * Kernel can automatically call reset() on it between requests — without any
 * project-specific class names leaking into the framework layer.
 *
 * Example (in a ServiceProvider):
 *
 *   $this->app->singleton(TransformerRegistryResetter::class);
 */
final class TransformerRegistryResetter implements ResettableInterface
{
    public function reset(): void
    {
        TransformerRegistry::reset();
    }
}
