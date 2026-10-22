<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use PrestoWorld\WpRest\WpDefaults;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * `/wp-json/wp/v2/template-parts` — header/footer/sidebar/etc. parts.
 *
 * Sources: the active theme's `parts/*.html` plus site overrides under
 * `storage/contexts/parts/`. `area` metadata comes from theme.json, falling
 * back to `storage/contexts/parts/meta.json` for parts the site creates.
 */
class TemplatePartsController extends RestController
{
    /** GET /wp-json/wp/v2/template-parts */
    public function index(Request $request): Response
    {
        return $this->fullCollection($this->items());
    }

    /** GET /wp-json/wp/v2/template-parts/lookup?slug=header */
    public function lookup(Request $request): Response
    {
        $slug = (string) $request->query('slug', '');
        if ($slug === '') {
            return $this->error('rest_missing_callback_param', 'Missing required parameter: slug.', 400);
        }

        $content = $this->themeRepository()->partContent($slug);
        if ($content === null) {
            return $this->emptyObject();
        }

        return $this->respond($this->record($slug, $content));
    }

    /** GET /wp-json/wp/v2/template-parts/{id} */
    public function show(Request $request, string $id): Response
    {
        ['slug' => $slug] = $this->splitId($id);

        $content = $this->themeRepository()->partContent($slug);
        if ($content === null) {
            return $this->error('rest_post_not_found', 'Invalid template part.', 404);
        }

        return $this->respond($this->record($slug, $content));
    }

    /** POST /wp-json/wp/v2/template-parts  (create) */
    public function create(Request $request): Response
    {
        $payload = $request->getJson();
        if (!is_array($payload)) {
            return $this->error('rest_invalid_param', 'Invalid JSON body.', 400);
        }

        $slug = $this->deriveSlug($payload);
        $content = $this->payloadContent($payload['content'] ?? '');

        if ($content === '') {
            return $this->error('rest_missing_param', 'Template part content is required.', 400);
        }

        if (!$this->themeRepository()->writePart($slug, $content)) {
            return $this->error('rest_cannot_create', 'Could not save the template part.', 500);
        }

        $this->themeRepository()->savePartMeta($slug, [
            'area' => $this->payloadString($payload['area'] ?? ''),
            'title' => $this->payloadString($payload['title'] ?? ''),
        ]);

        return $this->respond($this->record($slug, $content, hasOverride: true), 201);
    }

    /**
     * POST /wp-json/wp/v2/template-parts/{id}
     *
     * Branches on `X-HTTP-Method-Override` (PUT → update, DELETE → delete).
     */
    public function mutate(Request $request, string $id): Response
    {
        $override = $this->methodOverride($request);

        if ($override === 'DELETE') {
            return $this->destroy($id);
        }

        return $this->update($request, $id);
    }

    // ---------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    private function items(): array
    {
        $items = [];

        foreach ($this->themeRepository()->partFiles() as $slug => $content) {
            $items[] = $this->record((string) $slug, $content);
        }

        usort($items, static fn (array $a, array $b): int => strcmp($a['slug'], $b['slug']));

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function record(string $slug, string $content, ?bool $hasOverride = null): array
    {
        return $this->buildTemplateRecord(
            $slug,
            $content,
            'wp_template_part',
            WpDefaults::defaultTemplateTypes(),
            $this->themeRepository()->partMeta(),
            $hasOverride,
        );
    }

    private function update(Request $request, string $id): Response
    {
        ['slug' => $slug] = $this->splitId($id);

        $payload = $request->getJson();
        if (!is_array($payload)) {
            return $this->error('rest_invalid_param', 'Invalid JSON body.', 400);
        }

        $existing = $this->themeRepository()->partContent($slug);
        $content = $this->payloadContent($payload['content'] ?? '');

        if ($content === '') {
            $content = $existing ?? '';
        }

        if ($content === '') {
            return $this->error('rest_post_not_found', 'Invalid template part.', 404);
        }

        if (!$this->themeRepository()->writePart($slug, $content)) {
            return $this->error('rest_cannot_edit', 'Could not save the template part.', 500);
        }

        if (isset($payload['area']) || isset($payload['title'])) {
            $this->themeRepository()->savePartMeta($slug, [
                'area' => $this->payloadString($payload['area'] ?? ''),
                'title' => $this->payloadString($payload['title'] ?? ''),
            ]);
        }

        return $this->respond($this->record($slug, $content, hasOverride: true));
    }

    private function destroy(string $id): Response
    {
        ['slug' => $slug] = $this->splitId($id);

        $previous = $this->themeRepository()->partContent($slug);
        if ($previous === null) {
            return $this->error('rest_post_not_found', 'Invalid template part.', 404);
        }

        $previousRecord = $this->record($slug, $previous);

        $this->themeRepository()->deletePart($slug);

        return $this->respond([
            'deleted' => true,
            'previous' => $previousRecord,
        ]);
    }
}
