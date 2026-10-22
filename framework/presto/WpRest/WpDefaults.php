<?php

declare(strict_types=1);

namespace PrestoWorld\WpRest;

/**
 * App-facing alias for the WP-derived reference data.
 *
 * The implementation lives in `prestoworld/gutenberg-core`
 * (`PrestoWorld\Gutenberg\WpDefaults`) because it mirrors WordPress core
 * values and must stay inside the GPL package — app code only references it.
 */
class WpDefaults extends \PrestoWorld\Gutenberg\WpDefaults
{
}
