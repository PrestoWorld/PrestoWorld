<?php

declare(strict_types=1);

/**
 * Alias WP_* → PrestoWorld\Core\* (spec 10 §10.5). Chỉ alias target tồn tại.
 */
$map = \PrestoWorld\Core\Legacy\ClassShimMap::targets();

foreach ($map as $legacy => $target) {
    if (!class_exists($legacy, false) && class_exists($target)) {
        class_alias($target, $legacy);
    }
}