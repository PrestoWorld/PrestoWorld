<?php

declare(strict_types=1);

namespace App\Support;

use Witals\Framework\Support\ServiceProvider as BaseServiceProvider;

abstract class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
    }

    public function dependencies(): array
    {
        return [];
    }

    public function shouldLoad(): bool
    {
        return true;
    }

    protected function singleton(string $abstract, $concrete = null): void
    {
        $this->app->singleton($abstract, $concrete);
    }

    protected function instance(string $abstract, $instance): void
    {
        $this->app->instance($abstract, $instance);
    }

    protected function bind(string $abstract, $concrete = null): void
    {
        $this->app->bind($abstract, $concrete);
    }

    protected function config(string $key, $default = null)
    {
        return $this->app->config($key, $default);
    }
}
