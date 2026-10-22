<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use PrestoWorld\WpRest\WpDefaults;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * `/wp-json/wp/v2/types` and `/wp-json/wp/v2/taxonomies`.
 *
 * Both are keyed objects (not lists): `loadPostTypeEntities()` builds the
 * editor's entity configs with `Object.entries( postTypes ?? {} )`, so an
 * array here would silently produce no entities at all.
 */
class TypesController extends RestController
{
    /** GET /wp-json/wp/v2/types */
    public function types(Request $request): Response
    {
        return $this->respond((object) WpDefaults::postTypes());
    }

    /** GET /wp-json/wp/v2/types/{slug} */
    public function type(Request $request, string $slug): Response
    {
        $types = WpDefaults::postTypes();
        $key = rawurldecode($slug);

        if (!isset($types[$key])) {
            return $this->error('rest_type_invalid', 'The requested type does not exist.', 404);
        }

        return $this->respond($types[$key]);
    }

    /** GET /wp-json/wp/v2/taxonomies */
    public function taxonomies(Request $request): Response
    {
        return $this->respond((object) WpDefaults::taxonomies());
    }

    /** GET /wp-json/wp/v2/taxonomies/{slug} */
    public function taxonomy(Request $request, string $slug): Response
    {
        $taxonomies = WpDefaults::taxonomies();
        $key = rawurldecode($slug);

        if (!isset($taxonomies[$key])) {
            return $this->error('rest_taxonomy_invalid', 'The requested taxonomy does not exist.', 404);
        }

        return $this->respond($taxonomies[$key]);
    }
}
