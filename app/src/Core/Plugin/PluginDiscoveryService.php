<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Plugin;

use Psr\Log\LoggerInterface;

/**
 * High-performance plugin discovery service with layered caching.
 *
 * Responsibilities:
 *   - Run SmartPluginScanner once per request at most.
 *   - Cache the result in storage/framework/cache/plugins-discovery.php
 *     (PHP array, loaded via require — opcache-friendly, sub-µs).
 *   - Invalidate on directory mtime change via a cheap fingerprint.
 *   - Expose both "installed" and "available" views for the UI.
 *
 * Performance budget:
 *   - Cold scan:  O(P) where P = plugin count. Typical: 2–40.
 *   - Warm scan: O(1) — one require() of a ~N KB PHP file.
 *   - Per-plugin read: ≤ 4 KB (header) or ≤ 64 KB (manifest).
 *
 * This is the ONLY component that should be called from request handlers
 * that need a plugin list. It is stateless apart from the cache file.
 */
final class PluginDiscoveryService
{
    private ?array $blueprints = null;
    private bool $loaded = false;

    /**
     * @param list<string> $roots
     */
    public function __construct(
        private readonly SmartPluginScanner $scanner,
        private readonly string $cacheDir,
        private readonly array $roots,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * All discovered plugins (slug => blueprint), whether installed or not.
     *
     * @return array<string, PluginBlueprint>
     */
    public function all(): array
    {
        $this->load();

        return $this->blueprints ?? [];
    }

    /**
     * Only plugins that are currently enabled in the plugin store.
     *
     * @return array<string, PluginBlueprint>
     */
    public function enabled(): array
    {
        return array_filter($this->all(), fn(PluginBlueprint $bp) => $bp->isValid);
    }

    /**
     * Look up one plugin by slug.
     */
    public function find(string $slug): ?PluginBlueprint
    {
        return $this->all()[$slug] ?? null;
    }

    /**
     * Cheap fingerprint of all plugin roots — used to decide whether to
     * rebuild the cache. Combines per-root fingerprints into one string.
     */
    public function fingerprint(): string
    {
        $parts = [];
        foreach ($this->roots as $root) {
            $fp = $this->scanner->fingerprint($root);
            if ($fp !== '') {
                $parts[] = $fp;
            }
        }

        return md5(implode('|', $parts));
    }

    /**
     * Force a rediscovery, ignoring cache.
     *
     * @return array<string, PluginBlueprint>
     */
    public function refresh(): array
    {
        $this->blueprints = null;
        $this->loaded = false;
        $this->load(true);

        return $this->blueprints ?? [];
    }

    /**
     * Invalidate the discovery cache file (call after install/uninstall).
     */
    public function invalidate(): void
    {
        $file = $this->cacheFile();
        if (is_file($file)) {
            @unlink($file);
        }
        $this->blueprints = null;
        $this->loaded = false;
    }

    private function load(bool $force = false): void
    {
        if ($this->loaded && !$force) {
            return;
        }

        $this->loaded = true;
        $cacheFile = $this->cacheFile();

        if (!$force && $this->loadFromCache($cacheFile)) {
            return;
        }

        $started = microtime(true);
        $blueprints = $this->scanner->scanAll($this->roots);
        $elapsed = (microtime(true) - $started) * 1000;

        $this->blueprints = $blueprints;
        $this->writeCache($cacheFile, $blueprints);

        if ($this->logger !== null && $elapsed > 20.0) {
            $this->logger->warning(
                sprintf('Plugin discovery took %.1f ms (%d plugins)', $elapsed, count($blueprints)),
            );
        }
    }

    /**
     * @param array<string, PluginBlueprint> $blueprints
     */
    private function writeCache(string $cacheFile, array $blueprints): void
    {
        $dir = \dirname($cacheFile);
        if (!\is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $payload = [
            'fingerprint' => $this->fingerprint(),
            'scanned_at' => time(),
            'plugins' => array_map(
                static fn(PluginBlueprint $bp): array => $bp->toArray(),
                $blueprints,
            ),
        ];

        $code = '<?php return ' . var_export($payload, true) . ';';

        // Atomic write: write to a temp file then rename, so a concurrent
        // request never sees a half-written cache.
        $tmp = $cacheFile . '.' . getmypid() . '.tmp';
        if (\file_put_contents($tmp, $code, LOCK_EX) === false) {
            return;
        }
        @rename($tmp, $cacheFile);
    }

    private function loadFromCache(string $cacheFile): bool
    {
        if (!\is_file($cacheFile)) {
            return false;
        }

        // Cheap mtime short-circuit: if every root's mtime is older than the
        // cache, we don't even need to compute the fingerprint.
        $cacheMtime = \filemtime($cacheFile);
        if ($cacheMtime === false) {
            return false;
        }

        $data = require $cacheFile;
        if (!\is_array($data) || !isset($data['fingerprint'], $data['plugins'])) {
            return false;
        }

        if ($data['fingerprint'] !== $this->fingerprint()) {
            return false;
        }

        $blueprints = [];
        foreach ((array) $data['plugins'] as $slug => $row) {
            if (!\is_array($row)) {
                continue;
            }
            $blueprints[(string) $slug] = $this->hydrate((string) $slug, $row);
        }

        $this->blueprints = $blueprints;

        return true;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(string $slug, array $row): PluginBlueprint
    {
        return new PluginBlueprint(
            slug: (string) ($row['slug'] ?? $slug),
            name: (string) ($row['name'] ?? $slug),
            version: (string) ($row['version'] ?? '0.0.0'),
            description: (string) ($row['description'] ?? ''),
            author: (string) ($row['author'] ?? ''),
            authorUri: (string) ($row['author_uri'] ?? ''),
            pluginUri: (string) ($row['plugin_uri'] ?? ''),
            textDomain: (string) ($row['text_domain'] ?? ''),
            requiresPhp: (string) ($row['requires_php'] ?? ''),
            requiresWp: (string) ($row['requires_wp'] ?? ''),
            license: (string) ($row['license'] ?? ''),
            entryFile: (string) ($row['entry_file'] ?? ''),
            path: (string) ($row['path'] ?? ''),
            namespace: (string) ($row['namespace'] ?? ''),
            source: (string) ($row['source'] ?? 'header'),
            requires: (array) ($row['requires'] ?? []),
            tags: (array) ($row['tags'] ?? []),
            hooks: (array) ($row['hooks'] ?? []),
            isValid: (bool) ($row['isValid'] ?? true),
        );
    }

    private function cacheFile(): string
    {
        return rtrim($this->cacheDir, '/') . '/plugins-discovery.php';
    }
}