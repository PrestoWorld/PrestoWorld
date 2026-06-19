<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin\Exceptions;

class PluginException extends \RuntimeException
{
    public static function notFound(string $name): self
    {
        return new self("Plugin not found: {$name}");
    }

    public static function alreadyInstalled(string $name): self
    {
        return new self("Plugin already installed: {$name}");
    }

    public static function notInstalled(string $name): self
    {
        return new self("Plugin is not installed: {$name}");
    }

    public static function dependencyNotFound(string $plugin, string $dependency): self
    {
        return new self("Plugin '{$plugin}' requires '{$dependency}' which is not found");
    }

    public static function dependencyNotInstalled(string $plugin, string $dependency): self
    {
        return new self("Plugin '{$plugin}' requires '{$dependency}' which is not installed");
    }

    public static function versionMismatch(string $plugin, string $dependency, string $constraint, string $installed): self
    {
        return new self("Plugin '{$plugin}' requires '{$dependency}' version {$constraint}, but {$installed} is installed");
    }

    public static function circularDependency(string $plugin, string $chain): self
    {
        return new self("Circular dependency detected for plugin '{$plugin}': {$chain}");
    }

    public static function invalidManifest(string $plugin, string $error): self
    {
        return new self("Invalid manifest for plugin '{$plugin}': {$error}");
    }

    public static function hookNotDeclared(string $plugin, string $hook): self
    {
        return new self("Plugin '{$plugin}' tries to use undeclared hook '{$hook}'. Declare it in 'hooks.use' in plugin.json");
    }

    public static function hookAlreadyDeclared(string $plugin, string $hook): self
    {
        return new self("Hook '{$hook}' is already declared by another plugin. Plugin '{$plugin}' cannot redeclare it");
    }

    public static function repositoryFailed(string $repo, string $error): self
    {
        return new self("Plugin repository '{$repo}' failed: {$error}");
    }
}
