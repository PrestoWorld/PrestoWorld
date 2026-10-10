<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\SchemaOrg;

use PrestoWorld\Modules\SchemaOrg\Schema;

class SchemaRegistry
{
    /**
     * @var Schema[]
     */
    private array $schemas = [];

    /**
     * Register a schema with a key.
     */
    public function register(string $key, Schema $schema): void
    {
        $this->schemas[$key] = $schema;
    }

    /**
     * Get a registered schema by key.
     */
    public function get(string $key): ?Schema
    {
        return $this->schemas[$key] ?? null;
    }

    /**
     * Remove a schema by key.
     */
    public function remove(string $key): void
    {
        unset($this->schemas[$key]);
    }

    /**
     * Render a specific schema as JSON-LD script tag.
     *
     * @param string $key
     * @param int $jsonOptions Options passed to json_encode (default: JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
     * @return string|null The script tag or null if schema not found.
     */
    public function render(string $key, int $jsonOptions = 0): ?string
    {
        $schema = $this->get($key);
        if (!$schema) {
            return null;
        }
        $json = $schema->toJsonLd($jsonOptions);
        return sprintf('<script type="application/ld+json">%s</script>', $json);
    }

    /**
     * Render all registered schemas as JSON-LD script tags.
     *
     * @param int $jsonOptions
     * @return string Concatenated script tags.
     */
    public function renderAll(int $jsonOptions = 0): string
    {
        $html = '';
        foreach ($this->schemas as $key => $schema) {
            $json = $schema->toJsonLd($jsonOptions);
            $html .= sprintf('<script type="application/ld+json">%s</script>' . PHP_EOL, $json);
        }
        return $html;
    }
}