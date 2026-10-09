<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Filesystem;

/**
 * SshAdapter — replaces WP_Filesystem_SSH2.
 */
class SshAdapter extends Filesystem
{
    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        parent::__construct($args);
        $this->method = 'ssh2';
    }
}
