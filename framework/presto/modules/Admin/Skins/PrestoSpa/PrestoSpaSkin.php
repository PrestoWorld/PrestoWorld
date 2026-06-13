<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\Skins\PrestoSpa;

use PrestoWorld\Contracts\Admin\SkinInterface;
use Witals\Framework\Support\Assets\Contracts\AssetRegistryInterface;

class PrestoSpaSkin implements SkinInterface
{
    protected string $mountElement;

    public function __construct(
        protected AssetRegistryInterface $assets,
        string $mountElement = '#admin-app',
    ) {
        $this->mountElement = $mountElement;
    }

    public static function getManifest(): array
    {
        return [
            'name' => 'Presto SPA',
            'version' => '1.0.0',
            'description' => 'Single-page application admin skin with CSR rendering',
            'mode' => SkinInterface::MODE_CSR,
            'assets' => [
                'css' => ['admin-spa-css'],
                'js'  => ['admin-spa-js'],
            ],
        ];
    }

    public function getName(): string
    {
        return 'presto-spa';
    }

    public function getRenderMode(): string
    {
        return SkinInterface::MODE_CSR;
    }

    public function renderLayout(string $content, array $args = []): string
    {
        $title = $args['title'] ?? 'Admin';

        $this->assets->setContext('admin');
        $this->assets->setRenderMode(SkinInterface::MODE_CSR);

        $this->assets->enqueue('admin-spa-css');
        $this->assets->enqueue('admin-spa-js');

        $initialState = $args['initialState'] ?? [];
        $jsonState = !empty($initialState)
            ? json_encode($initialState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '{}';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            {$this->assets->renderCss()}
        </head>
        <body>
            <div id="admin-app">{$content}</div>
            <script>window.__INITIAL_STATE__ = {$jsonState};</script>
            {$this->assets->renderJs()}
        </body>
        </html>
        HTML;
    }

    public function renderComponent(string $component, array $props = []): string
    {
        return '';
    }

    public function getAssets(): array
    {
        return [
            'css' => ['admin-spa-css'],
            'js'  => ['admin-spa-js'],
        ];
    }
}
