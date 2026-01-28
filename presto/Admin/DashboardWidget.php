<?php

declare(strict_types=1);

namespace PrestoWorld\Admin;

class DashboardWidget
{
    public function __construct(
        public string $widgetId,
        public string $widgetName,
        public mixed $callback,
        public mixed $controlCallback = null,
        public array $callbackArgs = [],
        public string $context = 'normal',
        public string $priority = 'core'
    ) {}
}
