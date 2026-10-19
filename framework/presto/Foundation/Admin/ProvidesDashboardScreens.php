<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Admin;

use PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenProviderInterface;
use PrestoWorld\Contracts\Admin\Screen\ScreenInterface;
use PrestoWorld\Modules\Admin\Screen\Screen;

/**
 * Trait for modules to easily provide dashboard screens with React component paths.
 * 
 * Usage in a Module class:
 * 
 * ```php
 * use PrestoWorld\Foundation\Admin\ProvidesDashboardScreens;
 * 
 * class Module extends WitalsModule {
 *     use ProvidesDashboardScreens;
 *     
 *     protected function getDashboardScreenDefinitions(): array {
 *         return [
 *             'my-screen' => [
 *                 'title' => 'My Screen',
 *                 'component' => 'MyScreen', // Maps to ../components/pages/MyScreen.tsx
 *                 'icon' => 'Box',
 *                 'position' => 10,
 *             ],
 *         ];
 *     }
 * }
 * ```
 */
trait ProvidesDashboardScreens
{
    abstract protected function getDashboardScreenDefinitions(): array;

    private ?DashboardScreenProviderInterface $dashboardScreenProvider = null;

    protected function getDashboardScreenProvider(): DashboardScreenProviderInterface
    {
        if ($this->dashboardScreenProvider === null) {
            $definitions = $this->getDashboardScreenDefinitions();
            $this->dashboardScreenProvider = new class($definitions) extends BaseDashboardScreenProvider {
                private array $definitions;

                public function __construct(array $definitions)
                {
                    $this->definitions = $definitions;
                }

                protected function getScreenDefinitions(): array
                {
                    return $this->definitions;
                }
            };
        }

        return $this->dashboardScreenProvider;
    }

    /**
     * Register this module's dashboard screens.
     * Call this from the module's boot() method.
     */
    protected function registerDashboardScreens(\Witals\Framework\Application $app): void
    {
        if (!$app->has(\PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenRegistryInterface::class)) {
            return;
        }

        $registry = $app->make(\PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenRegistryInterface::class);
        if (!$registry instanceof \PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenRegistryInterface) {
            return;
        }

        $registry->addProvider($this->getDashboardScreenProvider());
    }
}