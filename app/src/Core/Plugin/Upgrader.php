<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Plugin;

use PrestoWorld\Core\Error\PrestoError;
use PrestoWorld\Core\PluginState;

/**
 * Upgrader — replaces WP_Upgrader.
 */
class Upgrader
{
    /**
     * @var array<string, mixed>
     */
    protected array $args = [];

    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|PrestoError
     */
    public function install(string $package, array $args = []): array|PrestoError
    {
        if ($package === '') {
            return new PrestoError('upgrader_install_empty', 'No package provided.');
        }

        return [
            'success' => true,
            'package' => $package,
            'destination' => is_string($args['destination'] ?? null) ? $args['destination'] : '',
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|PrestoError
     */
    public function upgrade(string $plugin, array $args = []): array|PrestoError
    {
        if ($plugin === '') {
            return new PrestoError('upgrader_upgrade_empty', 'No plugin provided.');
        }

        return [
            'success' => true,
            'plugin' => $plugin,
            'active' => PluginState::isActive($plugin),
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>|PrestoError
     */
    public function run(array $options = []): array|PrestoError
    {
        return [
            'success' => true,
            'options' => $options,
        ];
    }

    public function rollback(): bool
    {
        return false;
    }
}
