<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme;

class StyleParser
{
    private ?array $cache = null;

    private string $path;

    private int $mtime = 0;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function parse(): array
    {
        if (!file_exists($this->path)) {
            return [];
        }

        $currentMtime = filemtime($this->path);

        if ($this->cache !== null && $this->mtime === $currentMtime) {
            return $this->cache;
        }

        $content = file_get_contents($this->path);
        $this->cache = $this->parseHeader($content);
        $this->mtime = $currentMtime;

        return $this->cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->parse();
        return $data[$key] ?? $default;
    }

    public function name(): string
    {
        return $this->get('Theme Name', 'Unknown');
    }

    public function version(): string
    {
        return $this->get('Version', '1.0');
    }

    public function description(): string
    {
        return $this->get('Description', '');
    }

    public function textDomain(): string
    {
        return $this->get('Text Domain', '');
    }

    public function tags(): array
    {
        $tags = $this->get('Tags', '');

        if ($tags === '' || $tags === null) {
            return [];
        }

        return array_map('trim', explode(',', $tags));
    }

    public function css(): string
    {
        if (!file_exists($this->path)) {
            return '';
        }

        $content = file_get_contents($this->path);
        $pos = strpos($content, '*/');

        if ($pos === false) {
            return $content;
        }

        return trim(substr($content, $pos + 2));
    }

    private function parseHeader(string $content): array
    {
        $headers = [
            'Theme Name',
            'Theme URI',
            'Author',
            'Author URI',
            'Description',
            'Version',
            'Requires at least',
            'Tested up to',
            'Requires PHP',
            'Text Domain',
            'License',
            'License URI',
            'Tags',
            'Template',
        ];

        $data = [];

        foreach ($headers as $header) {
            if (preg_match('/^[ \t\/*#]*' . preg_quote($header, '/') . ':[ \t]*(.+?)(\*\/|\n|\r|$)/mi', $content, $match)) {
                $data[$header] = trim($match[1]);
            }
        }

        return $data;
    }
}
