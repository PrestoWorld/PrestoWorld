<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * `/wp-json/wp/v2/themes` — the Site Editor's boot gate.
 *
 * The editor requests `?context=edit&status=active` and only proceeds when the
 * record reports `is_block_theme: true`, so this exposes exactly the active
 * theme as a single-element collection.
 */
class ThemesController extends RestController
{
    /** GET /wp-json/wp/v2/themes */
    public function index(Request $request): Response
    {
        $status = $this->scalarQuery($request, 'status');
        if ($status !== '' && $status !== 'active') {
            return $this->fullCollection([]);
        }

        return $this->fullCollection([$this->buildThemeRecord()]);
    }

    /** GET /wp-json/wp/v2/themes/{stylesheet} */
    public function show(Request $request, string $stylesheet): Response
    {
        $record = $this->buildThemeRecord();

        if (rawurldecode($stylesheet) !== $record['stylesheet']) {
            return $this->error('rest_theme_not_found', 'No theme found with that stylesheet.', 404);
        }

        return $this->respond($record);
    }
}
