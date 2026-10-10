<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\SchemaOrg;

abstract class Schema
{
    protected array $attributes = [];
    protected string $type;

    public function __construct(string $type)
    {
        $this->type = $this->normalizeType($type);
        // Always set @context
        $this->set('@context', 'https://schema.org');
    }

    protected function normalizeType(string $type): string
    {
        // Allow using fully qualified type or just the simple name
        if (strpos($type, '://') !== false) {
            return $type;
        }
        // Assume it's a simple name, prepend https://schema.org/
        return 'https://schema.org/' . $type;
    }

    public function set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function remove(string $key): void
    {
        unset($this->attributes[$key]);
    }

    public function toArray(): array
    {
        $this->validate();

        $array = [
            '@type' => $this->type,
        ] + $this->attributes;

        // Ensure @context is first (optional but nice)
        if (isset($this->attributes['@context'])) {
            $array = [
                '@context' => $this->attributes['@context'],
            ] + $array;
        }

        return $array;
    }

    public function toJsonLd(int $options = 0): string
    {
        // JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT (if desired)
        $defaultOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $json = json_encode($this->toArray(), $options | $defaultOptions);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode Schema.org JSON-LD: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Validate the schema. Throw \InvalidArgumentException on failure.
     */
    abstract protected function validate(): void;
}