<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * `/wp-json/wp/v2/global-styles/*` — theme presets and the site's own styles.
 *
 *   GET  /global-styles/themes/{stylesheet}              → theme.json base
 *   GET  /global-styles/themes/{stylesheet}/variations   → styles/*.json
 *   GET  /global-styles/{id}                             → site override (id 1)
 *   POST /global-styles/{id}                             → save site override
 *
 * The theme record's `_links['wp:user-global-styles']` points at id `1`, which
 * is the numeric id core-data extracts to fetch the user record.
 */
class GlobalStylesController extends RestController
{
    /** GET /wp-json/wp/v2/global-styles/themes/{stylesheet} */
    public function baseStyles(Request $request, string $stylesheet): Response
    {
        if (rawurldecode($stylesheet) !== $this->themeRepository()->stylesheet()) {
            return $this->error('rest_theme_not_found', 'No theme found with that stylesheet.', 404);
        }

        $themeJson = $this->themeRepository()->themeJson();

        return $this->respond([
            'version' => $themeJson['version'] ?? 3,
            'settings' => $themeJson['settings'] ?? (object) [],
            'styles' => $themeJson['styles'] ?? (object) [],
            '_links' => (object) [],
        ]);
    }

    /** GET /wp-json/wp/v2/global-styles/themes/{stylesheet}/variations */
    public function variations(Request $request, string $stylesheet): Response
    {
        if (rawurldecode($stylesheet) !== $this->themeRepository()->stylesheet()) {
            return $this->error('rest_theme_not_found', 'No theme found with that stylesheet.', 404);
        }

        $items = $this->themeRepository()->styleVariations();

        foreach ($items as $i => $item) {
            if (!isset($item['title'])) {
                $items[$i]['title'] = 'Variation ' . ($i + 1);
            }
        }

        return $this->fullCollection($items);
    }

    /** GET /wp-json/wp/v2/global-styles/{id} */
    public function show(Request $request, string $id): Response
    {
        if ($this->splitId($id)['slug'] !== '1') {
            return $this->error('rest_global_styles_not_found', 'No global styles found with that id.', 404);
        }

        return $this->respond($this->buildUserGlobalStyles());
    }

    /**
     * POST /wp-json/wp/v2/global-styles/{id}  (PUT arrives as POST + override)
     */
    public function mutate(Request $request, string $id): Response
    {
        if ($this->splitId($id)['slug'] !== '1') {
            return $this->error('rest_global_styles_not_found', 'No global styles found with that id.', 404);
        }

        $override = $this->methodOverride($request);
        if ($override === 'DELETE') {
            return $this->error('rest_cannot_delete', 'Global styles cannot be deleted.', 400);
        }

        $payload = $request->getJson();
        if (!is_array($payload) || $payload === []) {
            return $this->error('rest_invalid_param', 'Invalid JSON body.', 400);
        }

        $stored = [];
        $path = $this->app->basePath('storage/global-styles.json');
        if (is_file($path)) {
            try {
                $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $stored = $decoded;
                }
            } catch (\JsonException) {
                $stored = [];
            }
        }

        foreach (['version', 'settings', 'styles', 'title'] as $key) {
            if (array_key_exists($key, $payload)) {
                $stored[$key] = $payload[$key];
            }
        }

        $stored['isGlobalStylesUserThemeJSON'] = true;
        $stored['id'] = 1;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $path,
            json_encode($stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );

        \PrestoWorld\Core\Cache\CacheInvalidation::onContentChange('global-styles');

        return $this->respond($this->buildUserGlobalStyles());
    }
}
