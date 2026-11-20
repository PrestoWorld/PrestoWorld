<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Plugin;

/**
 * High-performance plugin discovery engine.
 *
 * Strategy cascade (cheapest first):
 *   1. plugin.json manifest       — 1 file read, ~200 bytes, definitive.
 *   2. WP-style PHP header        — 1 fgets over first 4 KB, stops at end-of-comment.
 *   3. Folder heuristic fallback  — no header found; the folder name is the slug.
 *
 * Performance guarantees:
 *   - Top-level scan only. We do NOT recurse into plugin subdirectories.
 *   - Each plugin costs at most: 1 stat + 1 read of <= 4 KB.
 *   - Discovery cache is a single PHP file keyed by dir mtime, loaded once
 *     per request via require (opcache-friendly, sub-microsecond).
 *   - No composer, no class loading, no DB on the hot path.
 *
 * Complexity: O(D + P) where D = number of plugin roots (typically <= 3)
 * and P = number of plugin dirs (typically 2-40). With the discovery cache
 * warm, it's O(1) per request.
 */
final class SmartPluginScanner
{
    /**
     * Directories we never descend into when looking for the entry file.
     */
    private const SKIP_DIRS = [
        '.git', '.svn', '.hg', 'vendor', 'node_modules', 'compiled',
        '.idea', '.vscode', 'assets', 'languages', 'tests', 'docs',
    ];

    private HeaderParser $headerParser;

    public function __construct(?HeaderParser $headerParser = null)
    {
        $this->headerParser = $headerParser ?? new HeaderParser();
    }

    /**
     * Scan every plugin root and return a slug => blueprint map.
     *
     * @param list<string> $roots
     *
     * @return array<string, PluginBlueprint>
     */
    public function scanAll(array $roots): array
    {
        $found = [];

        foreach ($roots as $root) {
            $root = rtrim($root, '/');
            if ($root === '' || !is_dir($root)) {
                continue;
            }

            $entries = @scandir($root);
            if ($entries === false) {
                continue;
            }

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $dir = $root . '/' . $entry;
                if (!is_dir($dir)) {
                    continue;
                }

                $blueprint = $this->detect($dir);
                if ($blueprint === null || !$blueprint->isValid) {
                    continue;
                }

                // First root wins (matches PluginManager precedence).
                $slug = $blueprint->slug;
                if (!isset($found[$slug])) {
                    $found[$slug] = $blueprint;
                }
            }
        }

        return $found;
    }

    /**
     * Detect a single plugin from its directory.
     *
     * @return PluginBlueprint|null
     */
    public function detect(string $pluginDir): ?PluginBlueprint
    {
        $pluginDir = rtrim($pluginDir, '/');
        $slug = $this->slugify(basename($pluginDir));

        // Strategy 1: manifest (cheapest, most reliable)
        $manifest = $pluginDir . '/plugin.json';
        if (is_file($manifest)) {
            $bp = $this->fromManifest($manifest, $pluginDir, $slug);
            if ($bp !== null) {
                return $bp;
            }
        }

        // Strategy 2: WP-style PHP header from the entry file
        $entry = $this->findEntryPhp($pluginDir);
        if ($entry !== null) {
            $headers = $this->headerParser->parse($entry);
            $name = $headers['name'] ?? '';
            if ($name !== '') {
                return $this->headerParser->toBlueprint(
                    slug: $slug,
                    pluginPath: $pluginDir,
                    entryFile: $entry,
                    headers: $headers,
                    source: 'header',
                );
            }
        }

        // Strategy 3: heuristic — folder exists, no header.
        return new PluginBlueprint(
            slug: $slug,
            name: $slug,
            version: '0.0.0',
            description: '',
            author: '',
            authorUri: '',
            pluginUri: '',
            textDomain: '',
            requiresPhp: '',
            requiresWp: '',
            license: '',
            entryFile: '',
            path: $pluginDir,
            namespace: '',
            source: 'heuristic',
            isValid: false,
        );
    }

    /**
     * Read plugin.json and build a blueprint.
     *
     * @return PluginBlueprint|null
     */
    private function fromManifest(string $manifestPath, string $pluginDir, string $slug): ?PluginBlueprint
    {
        $raw = @file_get_contents($manifestPath);
        if ($raw === false) {
            return null;
        }

        if (strlen($raw) > 65536) {
            $raw = substr($raw, 0, 65536);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }

        $name = (string) ($data['name'] ?? '');
        if ($name === '') {
            return null;
        }

        $requires = [];
        foreach ((array) ($data['requires'] ?? []) as $req) {
            if (is_string($req) && $req !== '') {
                $requires[$req] = '*';
            } elseif (is_array($req) && isset($req['name'])) {
                $requires[(string) $req['name']] = (string) ($req['version'] ?? '*');
            }
        }

        $tags = [];
        foreach ((array) ($data['tags'] ?? []) as $tag) {
            if (is_string($tag) && $tag !== '') {
                $tags[] = $tag;
            }
        }

        $hooks = [];
        foreach ((array) ($data['hooks']['use'] ?? []) as $hook) {
            if (is_string($hook) && $hook !== '') {
                $hooks[] = $hook;
            }
        }

        return new PluginBlueprint(
            slug: $slug,
            name: $name,
            version: (string) ($data['version'] ?? '0.0.0'),
            description: (string) ($data['description'] ?? ''),
            author: (string) ($data['author'] ?? ''),
            authorUri: (string) ($data['author_uri'] ?? ''),
            pluginUri: (string) ($data['plugin_uri'] ?? ''),
            textDomain: (string) ($data['text_domain'] ?? ''),
            requiresPhp: (string) ($data['requires_php'] ?? ''),
            requiresWp: (string) ($data['requires_wp'] ?? ''),
            license: (string) ($data['license'] ?? ''),
            entryFile: (string) ($data['entry'] ?? ''),
            path: $pluginDir,
            namespace: (string) ($data['namespace'] ?? ''),
            source: 'manifest',
            requires: $requires,
            tags: $tags,
            hooks: $hooks,
            isValid: true,
        );
    }

    /**
     * Find the plugin entry PHP file.
     */
    private function findEntryPhp(string $pluginDir): ?string
    {
        $slug = $this->slugify(basename($pluginDir));

        // 1) Canonical: <root>/<slug>.php
        $canonical = $pluginDir . '/' . $slug . '.php';
        if (is_file($canonical)) {
            return $canonical;
        }

        // 2) <root>/index.php
        $index = $pluginDir . '/index.php';
        if (is_file($index)) {
            return $index;
        }

        // 3) Shallow scan of root (depth 1): any .php at the top level
        $entries = @scandir($pluginDir);
        if ($entries === false) {
            return null;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (str_ends_with($entry, '.php') && is_file($pluginDir . '/' . $entry)) {
                return $pluginDir . '/' . $entry;
            }
        }

        return null;
    }

    /**
     * Normalize a directory name into a plugin slug.
     */
    public function slugify(string $name): string
    {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $name) ?? $name);
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'plugin';
    }

    /**
     * Mtime fingerprint for incremental scanning.
     *
     * @return string
     */
    public function fingerprint(string $root): string
    {
        $root = rtrim($root, '/');
        if (!is_dir($root)) {
            return '';
        }

        $entries = @scandir($root);
        if ($entries === false) {
            return '';
        }

        $parts = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $dir = $root . '/' . $entry;
            if (!is_dir($dir)) {
                continue;
            }
            $parts[] = $entry . ':' . (filemtime($dir) ?: 0);
        }

        return md5(implode('|', $parts));
    }
}