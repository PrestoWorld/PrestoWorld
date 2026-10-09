<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\Dashboard;

use PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenProviderInterface;
use PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenRegistryInterface;
use PrestoWorld\Contracts\Admin\Screen\ScreenInterface;

/**
 * Registry of admin screens that can be rendered by the dashboard SPA.
 *
 * Modules, plugins and themes register screens through registerScreen() or via
 * a provider. The WordPress-compatible action 'admin.screen.register' is fired
 * with this registry so plugins using add_action() can hook in too.
 */
class DashboardScreenRegistry implements DashboardScreenRegistryInterface
{
    /** @var array<string, ScreenInterface> */
    protected array $screens = [];

    /** @var DashboardScreenProviderInterface[] */
    protected array $providers = [];

    protected bool $providersLoaded = false;

    public function registerScreen(ScreenInterface $screen): void
    {
        $this->screens[$screen->getId()] = $screen;
    }

    public function removeScreen(string $id): void
    {
        unset($this->screens[$id]);
    }

    public function getScreens(): array
    {
        $this->loadProviders();

        return $this->sortScreens($this->screens);
    }

    public function getScreen(string $id): ?ScreenInterface
    {
        $this->loadProviders();

        return $this->screens[$id] ?? null;
    }

    public function toArray(): array
    {
        return array_map(
            fn(ScreenInterface $screen) => $screen->toArray(),
            $this->getScreens(),
        );
    }

    public function addProvider(DashboardScreenProviderInterface $provider): void
    {
        $this->providers[] = $provider;
        $this->providersLoaded = false;
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    protected function loadProviders(): void
    {
        if ($this->providersLoaded) {
            return;
        }

        $sorted = $this->sortProviders($this->providers);

        foreach ($sorted as $provider) {
            foreach ($provider->getScreens() as $screen) {
                if ($screen instanceof ScreenInterface) {
                    $this->screens[$screen->getId()] = $screen;
                }
            }
        }

        $this->providersLoaded = true;
    }

    /** @param array<string, ScreenInterface> $screens */
    protected function sortScreens(array $screens): array
    {
        usort($screens, fn(ScreenInterface $a, ScreenInterface $b) => $a->getPosition() <=> $b->getPosition());
        return $screens;
    }

    /** @param DashboardScreenProviderInterface[] $providers */
    protected function sortProviders(array $providers): array
    {
        usort($providers, fn(DashboardScreenProviderInterface $a, DashboardScreenProviderInterface $b) => $a->getPriority() <=> $b->getPriority());
        return $providers;
    }
}