<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use App\Foundation\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * `/wp-json/wp/v2/{restBase}/{id}/revisions` — revision listing probe.
 *
 * `@wordpress/core-data`'s `getRevisions()` always fires when an entity is
 * opened (Styles, templates, posts). WordPress answers with the revision
 * list; PrestoWorld stores no revision history yet, so an empty collection
 * with `X-WP-Total: 0` is the faithful "no revisions" answer — a 404 would
 * surface as a console error on every editor open.
 */
class RevisionsController extends RestController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /**
     * GET — always an empty list until revision storage exists.
     *
     * Route params differ per parent (`global-styles/{id}` has no `restBase`),
     * so they are read from the path rather than injected: the container
     * fatals on unresolvable dependencies.
     */
    public function index(Request $request): Response
    {
        return $this->respond([], 200, ['X-WP-Total' => '0', 'X-WP-TotalPages' => '0']);
    }
}
