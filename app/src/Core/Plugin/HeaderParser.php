<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Plugin;

/**
 * Strategy: detect plugin from a PHP file header.
 *
 * Performance: single fgets loop over first 32 lines (or until end-of-comment
 * token found), stops early. Typical hit: line 2-6. Zero regex backtracking.
 */
final class HeaderParser
{
    /** Max bytes to read for a header. 4 KB covers any realistic plugin banner. */
    private const READ_LIMIT = 4096;

    /** Header key => canonical field name in PluginBlueprint. */
    private const FIELDS = [
        'plugin name' => 'name',
        'plugin uri' => 'pluginUri',
        'description' => 'description',
        'version' => 'version',
        'author' => 'author',
        'author uri' => 'authorUri',
        'text domain' => 'textDomain',
        'domain path' => 'domainPath',
        'requires php' => 'requiresPhp',
        'requires at least' => 'requiresWp',
        'requires wp' => 'requiresWp',
        'license' => 'license',
        'license uri' => 'licenseUri',
        'tags' => 'tags',
        'namespace' => 'namespace',
    ];

    /**
     * Parse the leading doc-comment of a PHP file.
     *
     * @return array<string, string> canonical field => raw value
     */
    public function parse(string $file): array
    {
        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            return [];
        }

        $head = fread($handle, self::READ_LIMIT);
        fclose($handle);

        if ($head === false || $head === '') {
            return [];
        }

        // Restrict to the first doc-block.
        $close = strpos($head, '*/');
        if ($close === false) {
            $block = $head;
        } else {
            $block = substr($head, 0, $close);
        }

        $lower = strtolower($block);
        $out = [];

        foreach (self::FIELDS as $needle => $field) {
            $pos = strpos($lower, $needle . ':');
            if ($pos === false) {
                continue;
            }

            $lineStart = $pos;
            while ($lineStart > 0 && $block[$lineStart - 1] !== "\n") {
                $lineStart--;
            }

            $prefix = substr($block, $lineStart, $pos - $lineStart);
            if (!preg_match('/^[ \t*\/#]*$/', $prefix)) {
                continue;
            }

            $valueStart = $pos + strlen($needle) + 1;
            $lineEnd = strpos($block, "\n", $valueStart);
            $value = $lineEnd === false
                ? substr($block, $valueStart)
                : substr($block, $valueStart, $lineEnd - $valueStart);

            $out[$field] = trim($value, " \t\r\n\0\x0B");
        }

        return $out;
    }

    /**
     * @param array<string, string> $headers
     */
    public function toBlueprint(
        string $slug,
        string $pluginPath,
        string $entryFile,
        array $headers,
        string $source = 'header',
    ): PluginBlueprint {
        $tags = [];
        if (isset($headers['tags']) && $headers['tags'] !== '') {
            $tags = array_values(array_filter(array_map('trim', explode(',', $headers['tags']))));
        }

        return new PluginBlueprint(
            slug: $slug,
            name: $headers['name'] ?? $slug,
            version: $headers['version'] ?? '0.0.0',
            description: $headers['description'] ?? '',
            author: $headers['author'] ?? '',
            authorUri: $headers['authorUri'] ?? '',
            pluginUri: $headers['pluginUri'] ?? '',
            textDomain: $headers['textDomain'] ?? '',
            requiresPhp: $headers['requiresPhp'] ?? '',
            requiresWp: $headers['requiresWp'] ?? '',
            license: $headers['license'] ?? '',
            entryFile: $entryFile,
            path: $pluginPath,
            namespace: $headers['namespace'] ?? '',
            source: $source,
            requires: [],
            tags: $tags,
            hooks: [],
            isValid: ($headers['name'] ?? '') !== '',
        );
    }
}