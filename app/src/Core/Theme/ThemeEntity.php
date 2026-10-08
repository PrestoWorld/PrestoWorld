<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Theme;

/**
 * ThemeEntity — giá trị đại diện cho một theme (spec 10 §10.5).
 */
class ThemeEntity
{
    public function __construct(
        public readonly string $name,
        public readonly string $stylesheet,
        public readonly string $template,
        public readonly string $directory,
        public readonly string $uri,
        public readonly string $version = '1.0.0',
        public readonly string $description = '',
    ) {
    }

    public function get(string $field, mixed $default = null): mixed
    {
        return match ($field) {
            'Name', 'name' => $this->name,
            'Template', 'template' => $this->template,
            'Stylesheet', 'stylesheet' => $this->stylesheet,
            'Version', 'version' => $this->version,
            'Description', 'description' => $this->description,
            default => $default,
        };
    }

    public function getDirectory(string $path = ''): string
    {
        return $path === '' ? $this->directory : $this->directory . '/' . ltrim($path, '/');
    }

    public function getTemplateDirectory(string $path = ''): string
    {
        return $this->getDirectory($path);
    }
}