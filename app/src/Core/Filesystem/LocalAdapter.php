<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Filesystem;

/**
 * LocalAdapter — replaces WP_Filesystem_Direct.
 */
class LocalAdapter extends Filesystem
{
    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        parent::__construct($args);
        $this->method = 'direct';
    }
}
