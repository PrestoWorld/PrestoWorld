<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme;

class TemplateHierarchy
{
    private array $cache = [];

    public function resolve(string $template, array $post = []): array
    {
        $cacheKey = $template . '|' . ($post['post_type'] ?? '') . '|' . ($post['ID'] ?? '');

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $candidates = match ($template) {
            'home', 'index' => $this->forHome(),
            'singular', 'single' => $this->forSingular($post),
            'page' => $this->forPage($post),
            'archive' => $this->forArchive($post),
            'search' => $this->forSearch(),
            '404' => $this->for404(),
            default => $this->forCustom($template),
        };

        $candidates = array_values(array_unique(array_filter($candidates)));
        $this->cache[$cacheKey] = $candidates;

        return $candidates;
    }

    public function resolveTemplateSlug(string $template, array $post = []): string
    {
        $candidates = $this->resolve($template, $post);

        return $candidates[0] ?? 'index';
    }

    private function forHome(): array
    {
        return ['home', 'index'];
    }

    private function forSingular(array $post): array
    {
        $candidates = ['singular'];

        if (isset($post['post_type'])) {
            $candidates[] = 'single-' . $post['post_type'];
        }

        if (isset($post['ID'])) {
            $candidates[] = 'single-' . $post['ID'];
        }

        $candidates[] = 'single';
        $candidates[] = 'index';

        return $candidates;
    }

    private function forPage(array $post): array
    {
        $candidates = [];

        if (isset($post['post_name'])) {
            $candidates[] = 'page-' . $post['post_name'];
        }

        if (isset($post['ID'])) {
            $candidates[] = 'page-' . $post['ID'];
        }

        $candidates[] = 'page';
        $candidates[] = 'singular';
        $candidates[] = 'index';

        return $candidates;
    }

    private function forArchive(array $post): array
    {
        $candidates = ['archive'];

        if (isset($post['post_type'])) {
            $candidates[] = 'archive-' . $post['post_type'];
        }

        $candidates[] = 'index';

        return $candidates;
    }

    private function forSearch(): array
    {
        return ['search', 'index'];
    }

    private function for404(): array
    {
        return ['404', 'index'];
    }

    private function forCustom(string $template): array
    {
        return [$template, 'index'];
    }
}
