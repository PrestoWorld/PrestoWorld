<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Plugin;

/**
 * Immutable value object for a discovered plugin.
 *
 * Designed for speed: readonly promoted properties, no magic, no lazy init.
 * One instance per plugin per request — created from a single 512-byte read.
 */
final readonly class PluginBlueprint
{
    /**
     * @param list<string>          $tags
     * @param array<string, string> $requires    plugin slug => version constraint
     * @param list<string>          $hooks
     * @param string                $entryFile   relative to plugin root
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $version,
        public string $description,
        public string $author,
        public string $authorUri,
        public string $pluginUri,
        public string $textDomain,
        public string $requiresPhp,
        public string $requiresWp,
        public string $license,
        public string $entryFile,
        public string $path,
        public string $namespace,
        public string $source,
        public array $requires = [],
        public array $tags = [],
        public array $hooks = [],
        public bool $isValid = true,
    ) {}

    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'author' => $this->author,
            'author_uri' => $this->authorUri,
            'plugin_uri' => $this->pluginUri,
            'text_domain' => $this->textDomain,
            'requires_php' => $this->requiresPhp,
            'requires_wp' => $this->requiresWp,
            'license' => $this->license,
            'entry_file' => $this->entryFile,
            'path' => $this->path,
            'namespace' => $this->namespace,
            'source' => $this->source,
            'requires' => $this->requires,
            'tags' => $this->tags,
            'hooks' => $this->hooks,
        ];
    }
}