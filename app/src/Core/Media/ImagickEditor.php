<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Media;

/**
 * ImagickEditor — replaces WP_Image_Editor_Imagick.
 */
class ImagickEditor extends ImageEditor
{
    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        parent::__construct($args);
    }
}
