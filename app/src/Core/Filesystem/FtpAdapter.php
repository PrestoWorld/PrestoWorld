<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Filesystem;

/**
 * FtpAdapter — replaces WP_Filesystem_FTPext.
 */
class FtpAdapter extends Filesystem
{
    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        parent::__construct($args);
        $this->method = 'ftpext';
    }
}
