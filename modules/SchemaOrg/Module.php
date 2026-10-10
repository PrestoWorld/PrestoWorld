<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\SchemaOrg;

use Witals\Framework\Module\ModuleInterface;
use Witals\Framework\Module\ModuleProvider;
use Witals\Framework\Contracts\Container\ContainerInterface;

class Module extends ModuleProvider implements ModuleInterface
{
    public function getName(): string
    {
        return 'schema-org';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDependencies(): array
    {
        // No hard dependencies, but could depend on schema module if we want to reuse.
        return [];
    }

    public function register(ContainerInterface $container): void
    {
        // Bind the schema registry as a shared service.
        $container->bind('schema-org.registry', function ($c) {
            return new SchemaRegistry();
        });

        // Bind the schema factory as a shared service.
        $container->bind('schema-org.factory', function ($c) {
            return new SchemaFactory();
        });
    }

    public function boot(ContainerInterface $container): void
    {
        // Optionally, you could auto-register some global schemas here.
        // For example, the website schema from config.
        // But we leave it to the developer to register schemas as needed.
    }
}