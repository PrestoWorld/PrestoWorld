<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Items;

/**
 * A dashboard widget (stat card, chart, table, custom block…)
 *
 * Modules register WidgetContexts into the DashboardContext.
 * For complex widgets, provide a `$callback` that returns rendered HTML.
 */
class WidgetContext extends AbstractContextItem
{
    public function __construct(
        string           $id,
        protected string $label      = '',
        protected string $icon       = '',
        protected string $value      = '',
        protected string $trend      = '',
        protected string $trendClass = '',   // 'trend-up', 'trend-down'
        protected string $cssClass   = '',   // 'danger', 'success', etc.
        protected string $type       = 'stat', // 'stat' | 'chart' | 'table' | 'custom'
        int              $priority   = 10,
        bool             $visible    = true,
        protected mixed  $callback   = null, // callable(WidgetContext $self, array $data): string
        protected array  $extra      = [],
    ) {
        parent::__construct($id, $priority, $visible);
    }

    public function getType(): string { return $this->type; }

    /**
     * Render custom widgets via the optional callable.
     */
    public function render(array $data = []): string
    {
        if ($this->callback !== null && is_callable($this->callback)) {
            if (function_exists('app')) {
                return (string) app()->call($this->callback, array_merge(['widget' => $this], $data));
            }
            return (string) ($this->callback)($this, $data);
        }
        return '';
    }

    public function resolve(): array
    {
        return array_merge($this->baseResolve(), [
            'label'       => $this->label,
            'icon'        => $this->icon,
            'value'       => $this->value,
            'trend'       => $this->trend,
            'trend_class' => $this->trendClass,
            'css_class'   => $this->cssClass,
            'type'        => $this->type,
            'has_callback'=> $this->callback !== null,
            'extra'       => $this->extra,
        ]);
    }
}
