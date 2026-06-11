<?php

declare(strict_types=1);

namespace PrestoWorld\Admin;

use PrestoWorld\Admin\Contracts\DashboardDriver;
use Witals\Framework\Application;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class AdminManager
{
    protected Application $app;
    protected array $drivers = [];
    protected ?DashboardDriver $activeDriver = null;
    protected LoggerInterface $logger;

    public function __construct(Application $app, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    /**
     * Register a new dashboard driver strategy.
     */
    public function registerDriver(string $name, string $driverClass): void
    {
        $this->drivers[$name] = $driverClass;
    }

    /**
     * Resolve the active dashboard driver based on configuration and priority.
     * 
     * Priority:
     * 1. Explicit config (ADMIN_DRIVER)
     * 2. WordPress Bridge (if available and supported)
     * 3. Presto Native (Fallback)
     */
    public function resolveActiveDriver(): DashboardDriver
    {
        if ($this->activeDriver) {
            return $this->activeDriver;
        }

        // 1. Check explicit configuration
        $configDriver = env('ADMIN_DRIVER');
        if ($configDriver && isset($this->drivers[$configDriver])) {
            $this->logger->info("AdminManager: Resolved driver from config: {driver}", ['driver' => $configDriver]);
            return $this->activeDriver = $this->makeDriver($configDriver);
        }

        // 2. Check for WordPress Bridge preference (implicit priority)
        if (isset($this->drivers['wordpress'])) {
            $wpDriver = $this->makeDriver('wordpress');
            // If WP bridge is supported (installed) and we haven't explicitly asked for Presto
            if ($wpDriver->isSupported()) {
                $this->logger->info("AdminManager: Resolved driver: wordpress");
                return $this->activeDriver = $wpDriver;
            }
        }

        // 3. Fallback to Presto Native
        if (isset($this->drivers['presto'])) {
            $this->logger->info("AdminManager: Resolved driver: presto");
            return $this->activeDriver = $this->makeDriver('presto');
        }

        throw new \RuntimeException("No available Admin Dashboard drivers found.");
    }

    protected function makeDriver(string $name): DashboardDriver
    {
        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Admin driver [{$name}] is not registered.");
        }

        $class = $this->drivers[$name];
        return $this->app->make($class);
    }
}
