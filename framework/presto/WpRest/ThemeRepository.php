<?php

declare(strict_types=1);

namespace PrestoWorld\WpRest;

/**
 * File-based reader for the active WordPress block theme.
 *
 * Backs the `/wp-json/wp/v2/*` REST surface used by the ported Site Editor.
 * Everything is derived from the theme directory on disk:
 *
 *   templates/*.html   → wp_template records
 *   parts/*.html       → wp_template_part records
 *   theme.json         → settings/styles, customTemplates, templateParts
 *   styles/*.json      → global style variations
 *   patterns/*.php     → block patterns
 *   style.css          → theme identity (Name, Author, Version, ...)
 *
 * User edits override the theme file when present under the storage dir
 * (`storage/contexts/{slug}.html` for templates, `storage/contexts/parts/
 * {slug}.html` for parts) — the same path `Presto\Context\Loader` resolves
 * when rendering the live site, so a REST save immediately affects output.
 */
class ThemeRepository
{
    /** @var array<string, mixed>|null */
    private ?array $styleCss = null;

    /** @var array<string, mixed>|null */
    private ?array $themeJson = null;

    /** @var array<string, string>|null */
    private ?array $templateFiles = null;

    /** @var array<string, string>|null */
    private ?array $partFiles = null;

    /** @var list<array<string, mixed>>|null */
    private ?array $styleVariations = null;

    /** @var array<string, array<string, mixed>>|null */
    private ?array $userPartMeta = null;

    public function __construct(
        private readonly string $themeDir,
        private readonly string $stylesheet,
        private readonly string $storageDir,
    ) {
    }

    public function stylesheet(): string
    {
        return $this->stylesheet;
    }

    public function themeDir(): string
    {
        return $this->themeDir;
    }

    public function exists(): bool
    {
        return is_dir($this->themeDir);
    }

    /**
     * Parse the style.css header block (WP theme identity).
     *
     * @return array<string, mixed>
     */
    public function styleCss(): array
    {
        if ($this->styleCss !== null) {
            return $this->styleCss;
        }

        $headers = [
            // Canonical style.css header keys (spaces included — WP writes
            // `Theme Name:`, `Theme URI:` and friends).
            'Theme Name' => 'name',
            'Name' => 'name',
            'Theme URI' => 'theme_uri',
            'ThemeURI' => 'theme_uri',
            'Author' => 'author',
            'Author URI' => 'author_uri',
            'AuthorURI' => 'author_uri',
            'Description' => 'description',
            'Version' => 'version',
            'Requires PHP' => 'requires_php',
            'RequiresPHP' => 'requires_php',
            'Requires at least' => 'requires_wp',
            'RequiresWP' => 'requires_wp',
            'Tested up to' => 'tested_up_to',
            'License' => 'license',
            'License URI' => 'license_uri',
            'LicenseURI' => 'license_uri',
            'Text Domain' => 'text_domain',
            'TextDomain' => 'text_domain',
            'Domain Path' => 'domain_path',
            'DomainPath' => 'domain_path',
            'Tags' => 'tags',
        ];

        $parsed = [];
        $file = $this->themeDir . '/style.css';

        if (!is_file($file)) {
            $this->styleCss = $parsed;

            return $parsed;
        }

        $content = (string) file_get_contents($file);
        // Only the header comment block at the top of the file.
        if (preg_match('/\/\*(.*?)\*\//s', $content, $m) !== 1) {
            $this->styleCss = $parsed;

            return $parsed;
        }

        foreach (explode("\n", $m[1]) as $line) {
            if (preg_match('/^([A-Za-z][A-Za-z0-9 -]*):\s*(.*)$/', trim($line), $mm) !== 1) {
                continue;
            }
            $key = $headers[$mm[1]] ?? strtolower($mm[1]);
            $value = trim($mm[2]);
            $parsed[$key] = $key === 'tags'
                ? array_values(array_filter(array_map('trim', explode(',', $value))))
                : $value;
        }

        $this->styleCss = $parsed;

        return $parsed;
    }

    /**
     * Decoded theme.json (empty array when the theme has none).
     *
     * @return array<string, mixed>
     */
    public function themeJson(): array
    {
        if ($this->themeJson !== null) {
            return $this->themeJson;
        }

        $file = $this->themeDir . '/theme.json';
        if (!is_file($file)) {
            $this->themeJson = [];

            return [];
        }

        try {
            $decoded = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            $this->themeJson = is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            $this->themeJson = [];
        }

        return $this->themeJson;
    }

    /**
     * theme.json `customTemplates` entries.
     *
     * @return list<array<string, mixed>>
     */
    public function customTemplates(): array
    {
        $custom = $this->themeJson()['customTemplates'] ?? [];

        return is_array($custom) ? array_values($custom) : [];
    }

    /**
     * theme.json `templateParts` entries, keyed by part slug.
     *
     * @return array<string, array<string, mixed>>
     */
    public function templatePartsMeta(): array
    {
        $parts = $this->themeJson()['templateParts'] ?? [];
        if (!is_array($parts)) {
            return [];
        }

        $keyed = [];
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['name'])) {
                $keyed[(string) $part['name']] = $part;
            }
        }

        return $keyed;
    }

    /**
     * Part metadata merged from theme.json (defaults) + user overrides in
     * `storage/contexts/parts/meta.json`.
     *
     * @return array<string, array<string, mixed>>
     */
    public function partMeta(): array
    {
        return $this->userPartMetaStorage() + $this->templatePartsMeta();
    }

    /**
     * Persist `{area, title}` for a template part created or edited by the site.
     *
     * @param array<string, mixed> $meta
     */
    public function savePartMeta(string $slug, array $meta): void
    {
        $stored = $this->userPartMetaStorage();
        $existing = $stored[$slug] ?? [];

        foreach (['area', 'title'] as $key) {
            if (isset($meta[$key]) && $meta[$key] !== '' && $meta[$key] !== null) {
                $existing[$key] = is_array($meta[$key])
                    ? (string) ($meta[$key]['raw'] ?? $meta[$key]['rendered'] ?? '')
                    : (string) $meta[$key];
            }
        }

        if ($existing === []) {
            return;
        }

        $existing['name'] = $slug;
        $stored[$slug] = $existing;

        $dir = $this->storageDir . '/parts';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $dir . '/meta.json',
            json_encode($stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );

        $this->userPartMeta = null;
        \PrestoWorld\Core\Cache\CacheInvalidation::onContentChange('part-meta');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function userPartMetaStorage(): array
    {
        if ($this->userPartMeta !== null) {
            return $this->userPartMeta;
        }

        $path = $this->storageDir . '/parts/meta.json';
        if (!is_file($path)) {
            $this->userPartMeta = [];

            return [];
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $this->userPartMeta = is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            $this->userPartMeta = [];
        }

        return $this->userPartMeta;
    }

    /**
     * Template slugs available on disk (theme file OR user override).
     *
     * @return array<string, string> slug => html content
     */
    public function templateFiles(): array
    {
        if ($this->templateFiles !== null) {
            return $this->templateFiles;
        }

        $files = [];

        // 1. Theme defaults.
        foreach (glob($this->themeDir . '/templates/*.html') ?: [] as $path) {
            $slug = basename($path, '.html');
            $files[$slug] = (string) file_get_contents($path);
        }

        // 2. User overrides win (same path the live renderer resolves).
        foreach (glob($this->storageDir . '/*.html') ?: [] as $path) {
            $slug = basename($path, '.html');
            // Only template-shaped overrides; `parts/` lives in a subdir.
            $files[$slug] = (string) file_get_contents($path);
        }

        $this->templateFiles = $files;

        return $files;
    }

    /**
     * Template part slugs available on disk.
     *
     * @return array<string, string> slug => html content
     */
    public function partFiles(): array
    {
        if ($this->partFiles !== null) {
            return $this->partFiles;
        }

        $files = [];

        foreach (glob($this->themeDir . '/parts/*.html') ?: [] as $path) {
            $slug = basename($path, '.html');
            $files[$slug] = (string) file_get_contents($path);
        }

        foreach (glob($this->storageDir . '/parts/*.html') ?: [] as $path) {
            $slug = basename($path, '.html');
            $files[$slug] = (string) file_get_contents($path);
        }

        $this->partFiles = $files;

        return $files;
    }

    /**
     * Global style variations from `styles/*.json` (top level only).
     *
     * @return list<array<string, mixed>>
     */
    public function styleVariations(): array
    {
        if ($this->styleVariations !== null) {
            return $this->styleVariations;
        }

        $variations = [];

        foreach (glob($this->themeDir . '/styles/*.json') ?: [] as $path) {
            try {
                $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                continue;
            }

            if (!is_array($decoded)) {
                continue;
            }

            if (!isset($decoded['title'])) {
                $decoded['title'] = ucfirst(str_replace(['-', '_'], ' ', basename($path, '.json')));
            }

            $decoded['title'] = is_array($decoded['title'])
                ? ($decoded['title']['raw'] ?? $decoded['title']['rendered'] ?? '')
                : $decoded['title'];

            $variations[] = $decoded;
        }

        $this->styleVariations = $variations;

        return $variations;
    }

    /**
     * Block patterns registered by the theme's `patterns/*.php` files.
     *
     * @return list<array<string, mixed>>
     */
    public function patterns(): array
    {
        $patterns = [];
        $themeDir = $this->themeDir;
        $themeUrl = '/content/themes/' . $this->stylesheet;

        foreach (glob($this->themeDir . '/patterns/*.php') ?: [] as $path) {
            $slug = basename($path, '.php');
            $source = (string) file_get_contents($path);
            $meta = $this->parsePatternHeader($source);

            // Patterns are PHP files with inline translation calls — render
            // them to real block markup instead of handing over source.
            $content = PatternRenderer::render($path, $themeDir, $themeUrl);

            $patterns[] = [
                'name' => $this->stylesheet . '/' . $slug,
                'title' => $meta['title'] ?? $slug,
                'content' => $content,
                'description' => $meta['description'] ?? '',
                'categories' => isset($meta['categories'])
                    ? array_values(array_filter(array_map('trim', explode(',', $meta['categories']))))
                    : [],
                'keywords' => isset($meta['keywords'])
                    ? array_values(array_filter(array_map('trim', explode(',', $meta['keywords']))))
                    : [],
                'blockTypes' => isset($meta['blockTypes'])
                    ? array_values(array_filter(array_map('trim', explode(',', $meta['blockTypes']))))
                    : [],
                'viewportWidth' => isset($meta['viewportWidth']) ? (int) $meta['viewportWidth'] : null,
                'inserter' => ($meta['inserter'] ?? 'yes') !== 'no',
                'source' => 'theme',
                'type' => 'pattern',
            ];
        }

        return $patterns;
    }

    /**
     * Read a single template's content, preferring the user override.
     */
    public function templateContent(string $slug): ?string
    {
        $files = $this->templateFiles();

        return $files[$slug] ?? null;
    }

    public function partContent(string $slug): ?string
    {
        $files = $this->partFiles();

        return $files[$slug] ?? null;
    }

    /**
     * Persist a template override so the live renderer picks it up.
     */
    public function writeTemplate(string $slug, string $content): bool
    {
        if (!$this->isSafeSlug($slug)) {
            return false;
        }

        $dir = $this->storageDir;
        if (!is_dir($dir) && mkdir($dir, 0755, true) === false && !is_dir($dir)) {
            return false;
        }

        $written = file_put_contents($dir . '/' . $slug . '.html', $content) !== false;
        if ($written) {
            \PrestoWorld\Core\Cache\CacheInvalidation::onContentChange('template');
        }

        return $written;
    }

    /**
     * Persist a template part override.
     */
    public function writePart(string $slug, string $content): bool
    {
        if (!$this->isSafeSlug($slug)) {
            return false;
        }

        $dir = $this->storageDir . '/parts';
        if (!is_dir($dir) && mkdir($dir, 0755, true) === false && !is_dir($dir)) {
            return false;
        }

        $written = file_put_contents($dir . '/' . $slug . '.html', $content) !== false;
        if ($written) {
            \PrestoWorld\Core\Cache\CacheInvalidation::onContentChange('part');
        }

        return $written;
    }

    public function deleteTemplate(string $slug): bool
    {
        if (!$this->isSafeSlug($slug)) {
            return false;
        }

        $path = $this->storageDir . '/' . $slug . '.html';
        $gone = !is_file($path) || unlink($path);
        if ($gone) {
            \PrestoWorld\Core\Cache\CacheInvalidation::onContentChange('template-delete');
        }

        return $gone;
    }

    public function deletePart(string $slug): bool
    {
        if (!$this->isSafeSlug($slug)) {
            return false;
        }

        $path = $this->storageDir . '/parts/' . $slug . '.html';
        $gone = !is_file($path) || unlink($path);
        if ($gone) {
            \PrestoWorld\Core\Cache\CacheInvalidation::onContentChange('part-delete');
        }

        return $gone;
    }

    /**
     * Whether a template/part exists as an override in storage.
     */
    public function hasTemplateOverride(string $slug): bool
    {
        return $this->isSafeSlug($slug) && is_file($this->storageDir . '/' . $slug . '.html');
    }

    public function hasPartOverride(string $slug): bool
    {
        return $this->isSafeSlug($slug) && is_file($this->storageDir . '/parts/' . $slug . '.html');
    }

    private function isSafeSlug(string $slug): bool
    {
        return $slug !== ''
            && preg_match('/^[A-Za-z0-9_-]+$/', $slug) === 1
            && !str_contains($slug, '..');
    }

    /**
     * Parse the leading docblock of a theme pattern file.
     *
     * @return array<string, string>
     */
    private function parsePatternHeader(string $content): array
    {
        if (preg_match('/\/\*\s*(.*?)\s*\*\//s', $content, $m) !== 1) {
            return [];
        }

        $meta = [];
        foreach (explode("\n", $m[1]) as $line) {
            $line = preg_replace('/^\s*\*\s?/', '', trim($line));
            if (preg_match('/^(Title|Description|Slug|Categories|Keywords|Block Types|Viewport Width|Inserter):\s*(.*)$/i', trim($line), $mm) === 1) {
                $key = strtolower(str_replace(' ', '_', $mm[1]));
                $meta[$key] = trim($mm[2]);
            }
        }

        if (isset($meta['block_types'])) {
            $meta['blockTypes'] = $meta['block_types'];
            unset($meta['block_types']);
        }
        if (isset($meta['viewport_width'])) {
            $meta['viewportWidth'] = $meta['viewport_width'];
            unset($meta['viewport_width']);
        }

        return $meta;
    }
}
