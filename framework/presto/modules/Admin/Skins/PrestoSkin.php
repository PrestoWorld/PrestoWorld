<?php

declare(strict_types=1);

namespace PrestoWorld\Admin\Skins;

use PrestoWorld\Contracts\Admin\SkinInterface;
use Witals\Framework\Contracts\View\Factory as ViewFactory;

class PrestoSkin implements SkinInterface
{
    protected ViewFactory $view;
    protected string $namespace = 'presto-admin';

    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
    }

    public function getName(): string
    {
        return 'presto-modern';
    }

    public function renderLayout(string $content, array $args = []): string
    {
        return (string) $this->view->make("{$this->namespace}::layout", array_merge($args, [
            'context' => $content
        ]));
    }

    public function renderComponent(string $component, array $props = []): string
    {
        return (string) $this->view->make("{$this->namespace}::components.{$component}", $props);
    }

    public function getAssets(): array
    {
        return [
            'css' => [
                '/assets/presto/admin/modern.css',
            ],
            'js' => [
                '/assets/presto/admin/modern.js',
            ],
        ];
    }
}
