<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use PrestoWorld\Gutenberg\WpTemplateHierarchy;
use PrestoWorld\WpRest\WpDefaults;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * `/wp-json/wp/v2/templates` — the Site Editor's template CRUD.
 *
 * Reads come from the active theme's `templates/*.html` (plus any site
 * override in `storage/contexts/{slug}.html`); saves write the override, which
 * is exactly the path `Presto\Context\Loader::resolveTemplatePath()` checks
 * first — so a saved template immediately drives the live site.
 *
 * Clients send update/delete as `POST` + `X-HTTP-Method-Override`, so a single
 * `{id}` route branches on that header.
 */
class TemplatesController extends RestController
{
    /** GET /wp-json/wp/v2/templates */
    public function index(Request $request): Response
    {
        $defaultTypes = WpDefaults::defaultTemplateTypes();
        $items = [];

        foreach ($this->themeRepository()->templateFiles() as $slug => $content) {
            $items[] = $this->buildTemplateRecord((string) $slug, $content, 'wp_template', $defaultTypes);
        }

        usort($items, static fn (array $a, array $b): int => strcmp($a['slug'], $b['slug']));

        return $this->fullCollection($items);
    }

    /** GET /wp-json/wp/v2/templates/lookup?slug=front-page */
    public function lookup(Request $request): Response
    {
        $slug = $this->scalarQuery($request, 'slug');
        if ($slug === '') {
            return $this->error('rest_missing_callback_param', 'Missing required parameter: slug.', 400);
        }

        $isCustom = filter_var($this->scalarQuery($request, 'is_custom', 'false'), FILTER_VALIDATE_BOOLEAN);
        $prefix = $this->scalarQuery($request, 'template_prefix');

        // WP: get_template_fallback() walks get_template_hierarchy() until a
        // candidate has content — a missing front-page.html resolves to
        // home/index rather than a miss (an empty object would leave the
        // editor without an entity to edit).
        foreach (WpTemplateHierarchy::for($slug, $isCustom, $prefix) as $candidate) {
            $content = $this->themeRepository()->templateContent($candidate);
            if ($content !== null && trim($content) !== '') {
                return $this->respond(
                    $this->buildTemplateRecord($candidate, $content, 'wp_template', WpDefaults::defaultTemplateTypes()),
                );
            }
        }

        // core-data treats an empty object as "not found" and has no error
        // handling, so a miss must be 200 {} rather than 404.
        return $this->emptyObject();
    }



    /** GET /wp-json/wp/v2/templates/{id} */
    public function show(Request $request, string $id): Response
    {
        ['slug' => $slug] = $this->splitId($id);

        $content = $this->themeRepository()->templateContent($slug);
        if ($content === null) {
            return $this->error('rest_post_not_found', 'Invalid template.', 404);
        }

        return $this->respond(
            $this->buildTemplateRecord($slug, $content, 'wp_template', WpDefaults::defaultTemplateTypes()),
        );
    }

    /** POST /wp-json/wp/v2/templates  (create) */
    public function create(Request $request): Response
    {
        $payload = $request->getJson();
        if (!is_array($payload)) {
            return $this->error('rest_invalid_param', 'Invalid JSON body.', 400);
        }

        $slug = $this->deriveSlug($payload);
        $content = $this->payloadContent($payload['content'] ?? '');

        if ($content === '') {
            return $this->error('rest_missing_param', 'Template content is required.', 400);
        }

        if (!$this->themeRepository()->writeTemplate($slug, $content)) {
            return $this->error('rest_cannot_create', 'Could not save the template.', 500);
        }

        $record = $this->buildTemplateRecord(
            $slug,
            $content,
            'wp_template',
            WpDefaults::defaultTemplateTypes(),
            hasOverride: true,
        );

        return $this->respond($record, 201);
    }

    /**
     * POST /wp-json/wp/v2/templates/{id}
     *
     * Branches on `X-HTTP-Method-Override` (PUT → update, DELETE → delete);
     * a plain POST is treated as an update.
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

    private function update(Request $request, string $id): Response
    {
        ['slug' => $slug] = $this->splitId($id);

        $payload = $request->getJson();
        if (!is_array($payload)) {
            return $this->error('rest_invalid_param', 'Invalid JSON body.', 400);
        }

        $existing = $this->themeRepository()->templateContent($slug);
        $content = $this->payloadContent($payload['content'] ?? '');

        if ($content === '') {
            $content = $existing ?? '';
        }

        if ($content === '') {
            return $this->error('rest_post_not_found', 'Invalid template.', 404);
        }

        if (!$this->themeRepository()->writeTemplate($slug, $content)) {
            return $this->error('rest_cannot_edit', 'Could not save the template.', 500);
        }

        return $this->respond(
            $this->buildTemplateRecord(
                $slug,
                $content,
                'wp_template',
                WpDefaults::defaultTemplateTypes(),
                hasOverride: true,
            ),
        );
    }

    private function destroy(string $id): Response
    {
        ['slug' => $slug] = $this->splitId($id);

        $previous = $this->themeRepository()->templateContent($slug);
        if ($previous === null) {
            return $this->error('rest_post_not_found', 'Invalid template.', 404);
        }

        $previousRecord = $this->buildTemplateRecord($slug, $previous, 'wp_template', WpDefaults::defaultTemplateTypes());

        // Removing the site override reverts to the theme file (which may not
        // exist — WP then reports the record as gone on the next read).
        $this->themeRepository()->deleteTemplate($slug);

        return $this->respond([
            'deleted' => true,
            'previous' => $previousRecord,
        ]);
    }
}
