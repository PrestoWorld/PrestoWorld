<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Items;

/**
 * A renderable page section (hero, features, pricing, CTA, highlights…)
 *
 * Modules register SectionContexts into HomeSectionContext or any page-level context.
 * The `$callback` is a callable that returns the section's HTML string.
 */
class SectionContext extends AbstractContextItem
{
    public function __construct(
        string          $id,
        protected string $label    = '',
        int             $priority  = 10,
        bool            $visible   = true,
        protected mixed $callback  = null,  // callable(array $data): string
        protected array $data      = [],    // default data merged into callback call
    ) {
        parent::__construct($id, $priority, $visible);
    }

    /**
     * Render the section, merging any extra request-time data.
     *
     * @param array $requestData  e.g. ['request' => $request, 'posts' => $posts]
     */
    public function render(array $requestData = []): string
    {
        if ($this->callback !== null && is_callable($this->callback)) {
            // Context resolve() provides the array format that legacy controllers expect via 'array $section'
            $payload = array_merge($this->data, $requestData, [
                'section' => $this->resolve(),
                'item'    => $this,
                'id'      => $this->id
            ]);
            
            if (function_exists('app')) {
                return (string) app()->call($this->callback, $payload);
            }

            return (string) ($this->callback)($payload);
        }
        return '';
    }

    public function resolve(): array
    {
        return array_merge($this->baseResolve(), [
            'label'       => $this->label,
            'has_callback'=> $this->callback !== null,
            'data'        => $this->data,
        ]);
    }
}
