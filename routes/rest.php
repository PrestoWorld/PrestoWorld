<?php

declare(strict_types=1);

use App\Http\Routing\Contracts\RouterInterface;
use App\Http\Controllers\WpRest\BlockEditorController;
use App\Http\Controllers\WpRest\ContentController;
use App\Http\Controllers\WpRest\GlobalStylesController;
use App\Http\Controllers\WpRest\OptionsController;
use App\Http\Controllers\WpRest\PatternsController;
use App\Http\Controllers\WpRest\RevisionsController;
use App\Http\Controllers\WpRest\RootController;
use App\Http\Controllers\WpRest\TemplatePartsController;
use App\Http\Controllers\WpRest\TemplatesController;
use App\Http\Controllers\WpRest\ThemesController;
use App\Http\Controllers\WpRest\TypesController;

/**
 * WordPress-compatible REST surface for the Site Editor.
 *
 * Every path the editor's `apiFetch` builds is prefixed with `/wp-json/`
 * (created by `apiFetch.createRootURLMiddleware('/wp-json/')`), and the
 * `wp/v2` segment matches core-data's default namespace so no fork of the
 * upstream packages is needed.
 *
 * @var RouterInterface $router
 */

// --- discovery / site options ----------------------------------------

$router->get('/wp-json', [RootController::class, 'index']);

$router->get('/wp-json/wp/v2/settings', [RootController::class, 'settings']);
$router->post('/wp-json/wp/v2/settings', [RootController::class, 'saveSettings']);
$router->get('/wp-json/wp/v2/view-config', [RootController::class, 'viewConfig']);
$router->get('/wp-json/wp/v2/users', [RootController::class, 'users']);
$router->get('/wp-json/wp/v2/users/me', [RootController::class, 'me']);

// --- boot gate --------------------------------------------------------

$router->get('/wp-json/wp/v2/themes', [ThemesController::class, 'index']);
$router->get('/wp-json/wp/v2/themes/{stylesheet}', [ThemesController::class, 'show']);

// --- entity configs (post types / taxonomies drive entity registration) -

$router->get('/wp-json/wp/v2/types', [TypesController::class, 'types']);
$router->get('/wp-json/wp/v2/types/{slug}', [TypesController::class, 'type']);
$router->get('/wp-json/wp/v2/taxonomies', [TypesController::class, 'taxonomies']);
$router->get('/wp-json/wp/v2/taxonomies/{slug}', [TypesController::class, 'taxonomy']);

// --- templates (the Site Editor's primary entity) ---------------------
// Ids are `stylesheet//slug` (a literal double slash), so the `{id}` group
// swallows everything to the end of the path — the editor never percent-
// encodes the slashes.

$router->get('/wp-json/wp/v2/templates', [TemplatesController::class, 'index']);
$router->get('/wp-json/wp/v2/templates/lookup', [TemplatesController::class, 'lookup']);
// Registered before `{id::.*}`: equal prefix + stable sort keeps this branch
// first, so `…/templates/<id>/revisions` resolves here instead of id-eats-all.
$router->get('/wp-json/wp/v2/templates/{id::.+}/revisions', [RevisionsController::class, 'index']);
$router->get('/wp-json/wp/v2/templates/{id::.*}', [TemplatesController::class, 'show']);
$router->post('/wp-json/wp/v2/templates', [TemplatesController::class, 'create']);
$router->post('/wp-json/wp/v2/templates/{id::.*}', [TemplatesController::class, 'mutate']);

// --- template parts ---------------------------------------------------

$router->get('/wp-json/wp/v2/template-parts', [TemplatePartsController::class, 'index']);
$router->get('/wp-json/wp/v2/template-parts/lookup', [TemplatePartsController::class, 'lookup']);
$router->get('/wp-json/wp/v2/template-parts/{id::.+}/revisions', [RevisionsController::class, 'index']);
$router->get('/wp-json/wp/v2/template-parts/{id::.*}', [TemplatePartsController::class, 'show']);
$router->post('/wp-json/wp/v2/template-parts', [TemplatePartsController::class, 'create']);
$router->post('/wp-json/wp/v2/template-parts/{id::.*}', [TemplatePartsController::class, 'mutate']);

// --- global styles (theme presets + site override) --------------------

$router->get('/wp-json/wp/v2/global-styles/{id}/revisions', [RevisionsController::class, 'index']);
$router->get('/wp-json/wp/v2/global-styles/{id}', [GlobalStylesController::class, 'show']);
$router->post('/wp-json/wp/v2/global-styles/{id}', [GlobalStylesController::class, 'mutate']);
$router->get('/wp-json/wp/v2/global-styles/themes/{stylesheet}', [GlobalStylesController::class, 'baseStyles']);
$router->get('/wp-json/wp/v2/global-styles/themes/{stylesheet}/variations', [GlobalStylesController::class, 'variations']);

// --- block patterns ---------------------------------------------------

$router->get('/wp-json/wp/v2/block-patterns/patterns', [PatternsController::class, 'patterns']);
$router->get('/wp-json/wp/v2/block-patterns/categories', [PatternsController::class, 'categories']);

// --- block editor bootstrap data --------------------------------------

$router->get('/wp-json/wp-block-editor/v1/settings', [BlockEditorController::class, 'settings']);
$router->get('/wp-json/wp-block-editor/v1/assets', [BlockEditorController::class, 'assets']);
$router->get('/wp-json/wp-block-editor/v1/export', [BlockEditorController::class, 'export']);
$router->get('/wp-json/wp-block-editor/v1/navigation-fallback', [BlockEditorController::class, 'navigationFallback']);

// --- content entities (posts, pages, blocks, navigation, media) -------
// Registered after the static routes above: static paths win, and dynamic
// groups are ordered longest-prefix-first, so `templates/{id}` never falls
// through to this catch-all.

$router->get('/wp-json/wp/v2/{restBase}/{id}/revisions', [RevisionsController::class, 'index']);
$router->get('/wp-json/wp/v2/{restBase}', [ContentController::class, 'index']);
$router->get('/wp-json/wp/v2/{restBase}/{id}', [ContentController::class, 'show']);

// --- capability probes ------------------------------------------------
// `canUser()` sends OPTIONS and reads the Allow header; kept last so every
// concrete route above is considered first.

$router->options('/wp-json/{restPath::.+}', [OptionsController::class, 'handle']);
