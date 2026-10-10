<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\SchemaOrg;

class Renderer
{
    /**
     * Generate WebSite schema
     */
    public static function website(string $url, string $name, ?string $potentialAction = null): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'url' => $url,
            'name' => $name,
        ];

        if ($potentialAction) {
            $schema['potentialAction'] = $potentialAction;
        }

        return $schema;
    }

    /**
     * Generate Organization schema
     */
    public static function organization(string $name, string $url, ?string $logo = null, ?array $sameAs = null): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => $url,
        ];

        if ($logo) {
            $schema['logo'] = $logo;
        }

        if ($sameAs) {
            $schema['sameAs'] = $sameAs;
        }

        return $schema;
    }

    /**
     * Generate BreadcrumbList schema
     */
    public static function breadcrumbList(array $items): array
    {
        $listItems = [];
        foreach ($items as $index => $item) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'] ?? '',
                'item' => $item['url'] ?? '',
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];
    }

    /**
     * Generate FAQPage schema
     */
    public static function faqPage(array $questions): array
    {
        $mainEntity = [];
        foreach ($questions as $q) {
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $q['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $q['answer'],
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];
    }

    /**
     * Add Core Web Vitals script (optional)
     * This could be used to inject a script that measures vitals and sends to analytics.
     * For now, just return a placeholder.
     */
    public static function coreWebVitalsScript(): string
    {
        // In a real implementation, you might return a script tag to load web-vitals library.
        return '<!-- Core Web Vitals script placeholder -->';
    }
}