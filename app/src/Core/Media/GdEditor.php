<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Media;

/**
 * GdEditor — replaces WP_Image_Editor_GD.
 */
class GdEditor extends ImageEditor
{
    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        parent::__construct($args);
    }
}
