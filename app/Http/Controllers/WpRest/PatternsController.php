<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * `/wp-json/wp/v2/block-patterns/*` — pattern inserter data.
 *
 * Patterns come from the active theme's `patterns/*.php`, rendered to block
 * markup (see PatternRenderer). Categories are the union of a stable core set
 * plus every category a theme pattern actually declares, so no pattern can be
 * filtered out by a missing category.
 */
class PatternsController extends RestController
{
    /** GET /wp-json/wp/v2/block-patterns/patterns */
    public function patterns(Request $request): Response
    {
        return $this->fullCollection($this->themeRepository()->patterns());
    }

    /** GET /wp-json/wp/v2/block-patterns/categories */
    public function categories(Request $request): Response
    {
        $categories = [];
        foreach (self::categoryMap($this->themeRepository()->patterns()) as $slug => $label) {
            $categories[] = [
                'name' => $slug,
                'label' => $label,
                'description' => '',
            ];
        }

        return $this->respond($categories);
    }

    /**
     * slug => label for every category the endpoint offers.
     *
     * Also feeds `view-config`, whose `wp_pattern` sidebar declares one saved
     * view per category — same union as the endpoint, single source of truth.
     *
     * @param array<int, array<string, mixed>> $themePatterns Theme pattern records.
     * @return array<string, string>
     */
    public static function categoryMap(array $themePatterns): array
    {
        $map = [];
        foreach (self::coreCategories() as $slug => $entry) {
            $map[$slug] = $entry['label'];
        }

        foreach ($themePatterns as $pattern) {
            foreach ($pattern['categories'] as $slug) {
                $slug = (string) $slug;
                $map[$slug] ??= ucwords(str_replace(['-', '_'], ' ', $slug));
            }
        }

        return $map;
    }

    /**
     * @return array<string, array{name: string, label: string, description: string}>
     */
    private static function coreCategories(): array
    {
        $slugs = [
            'about', 'banner', 'buttons', 'call-to-action', 'columns', 'contact',
            'featured', 'footer', 'gallery', 'header', 'media', 'query', 'services',
            'text', 'testimonials',
        ];

        $categories = [];
        foreach ($slugs as $slug) {
            $categories[$slug] = [
                'name' => $slug,
                'label' => ucwords(str_replace(['-', '_'], ' ', $slug)),
                'description' => '',
            ];
        }

        return $categories;
    }
}
