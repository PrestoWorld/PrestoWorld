<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * `/wp-json/wp-block-editor/v1/*` — editor bootstrap data.
 *
 * `core-data` resolves these with **no error handling**: a 404 leaves the
 * `editorSettings`/`editorAssets` selectors unresolved and surfaces as an
 * unhandled rejection in the console, so both endpoints answer with a usable
 * object instead.
 */
class BlockEditorController extends RestController
{
    /** GET /wp-json/wp-block-editor/v1/settings */
    public function settings(Request $request): Response
    {
        $settingsJson = $this->themeRepository()->themeJson();

        return $this->respond([
            'canPublish' => true,
            'storageSupport' => true,
            'codeEditingEnabled' => true,
            'richEditingEnabled' => true,
            'autosaveInterval' => 60,
            'availableTemplates' => (object) [],
            'allowedBlockTypes' => true,
            'allowedMimeTypes' => (object) [],
            'maxUploadFileSize' => 0,
            'isRTL' => false,
            'locale' => 'en_US',
            'userLocale' => 'en_US',
            'postsPerPage' => 10,
            'imageDefaultSize' => 'large',
            'imageSizes' => [],
            'imageDirname' => 'uploads',
            'fluidLayoutSizeBreakpoints' => [],
            'siteIcon' => 0,
            'siteLogo' => 0,
            'siteTitle' => $this->siteTitle(),
            'hasReusableBlocks' => true,
            'postLock' => [
                'enabled' => false,
                'interval' => 150,
                'refreshInterval' => 10,
            ],
            'blockCategories' => $this->blockCategories(),
            'defaultTemplateTypes' => \PrestoWorld\WpRest\WpDefaults::defaultTemplateTypes(),
            'defaultTemplatePartAreas' => \PrestoWorld\WpRest\WpDefaults::defaultTemplatePartAreas(),
            'styles' => $settingsJson['styles'] ?? (object) [],
        ]);
    }

    /** GET /wp-json/wp-block-editor/v1/assets */
    public function assets(Request $request): Response
    {
        return $this->respond((object) []);
    }

    /** GET /wp-json/wp-block-editor/v1/export  (site export — not boot data) */
    public function export(Request $request): Response
    {
        return $this->error('rest_cannot_export', 'Site export is not available on this installation.', 501);
    }

    /**
     * @return list<array{name: string, title: string, icon: string}>
     */
    private function blockCategories(): array
    {
        $names = [
            ['text', 'Text', 'text'],
            ['media', 'Media', 'format-image'],
            ['design', 'Design', 'layout'],
            ['widgets', 'Widgets', 'widgets'],
            ['theme', 'Theme', 'archive'],
            ['embed', 'Embeds', 'embed-generic'],
        ];

        return array_map(
            static fn (array $entry): array => ['name' => $entry[0], 'title' => $entry[1], 'icon' => $entry[2]],
            $names,
        );
    }

    private function siteTitle(): string
    {
        $path = $this->app->basePath('storage/settings.json');
        if (is_file($path)) {
            try {
                $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($data)) {
                    $title = (string) ($data['title'] ?? ($data['blogname'] ?? ''));
                    if ($title !== '') {
                        return $title;
                    }
                }
            } catch (\JsonException) {
            }
        }

        return (string) $this->app->config('app.name', '');
    }
}
