<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\Skins\PrestoModern;

use PrestoWorld\Contracts\Admin\SkinInterface;
use PrestoWorld\Contracts\Admin\AdminBar\AdminBarContext as AdminBarContextContract;
use Witals\Framework\Contracts\View\Factory as ViewFactory;

class PrestoModernSkin implements SkinInterface
{
    protected ViewFactory $view;
    protected string $namespace = 'presto-modern';

    protected ?AdminBarContextContract $adminBar = null;

    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
        $this->view->addNamespace($this->namespace, __DIR__ . '/views');
    }

    /**
     * Set admin bar context
     */
    public function setAdminBar(AdminBarContextContract $adminBar): void
    {
        $this->adminBar = $adminBar;
    }

    public static function getManifest(): array
    {
        return [
            'name' => 'Presto Modern',
            'version' => '1.0.0',
            'description' => 'Modern dark-themed admin skin with SSR rendering',
            'mode' => SkinInterface::MODE_SSR,
            'assets' => [
                'css' => ['/assets/presto/admin/modern.css'],
                'js'  => ['/assets/presto/admin/modern.js'],
            ],
        ];
    }

    public function getName(): string
    {
        return 'presto-modern';
    }

    public function getRenderMode(): string
    {
        return SkinInterface::MODE_SSR;
    }

    public function renderLayout(string $content, array $args = []): string
    {
        $adminBarHtml = '';
        if ($this->adminBar !== null) {
            $renderer = new \PrestoWorld\Modules\Admin\AdminBar\AdminBarRenderer($this->adminBar);
            $renderer->setTheme('dark');

            $user = $args['user'] ?? [];
            if (isset($args['initialState']['user'])) {
                $user = $args['initialState']['user'];
            }

            $adminBarHtml = $renderer->render($user);
        }

        return (string) $this->view->make("{$this->namespace}::layout", array_merge($args, [
            'context' => $content,
            'adminBar' => $adminBarHtml,
        ]));
    }

    public function renderComponent(string $component, array $props = []): string
    {
        return (string) $this->view->make("{$this->namespace}::components.{$component}", $props);
    }

    public function getAssets(): array
    {
        return [
            'css' => ['/assets/presto/admin/modern.css'],
            'js'  => ['/assets/presto/admin/modern.js'],
        ];
    }
}
