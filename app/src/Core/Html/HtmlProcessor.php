<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Html;

/**
 * HtmlProcessor — replaces WP_HTML_Processor (spec 10 §10.5).
 */
class HtmlProcessor
{
    private string $html;

    /** @var list<array<string, mixed>> */
    private array $tokens;

    private int $cursor = 0;

    /** @var array<string, mixed>|null */
    private ?array $current = null;

    /** @var array<int, bool> */
    private array $modified = [];

    /** @var array<int, string> */
    private array $textOverrides = [];

    /** @var array<string, int> */
    private array $bookmarks = [];

    public function __construct(string $html = '')
    {
        $this->html = $html;
        $this->tokens = TagProcessor::tokenizeAst($this->html);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function next_tag(string $tagName = '', array $query = []): bool
    {
        $count = count($this->tokens);
        for ($i = $this->cursor; $i < $count; $i++) {
            $token = $this->tokens[$i];
            if (TagProcessor::tokenString($token, 'type') !== 'tag') {
                continue;
            }

            if ($tagName !== '' && strcasecmp($tagName, TagProcessor::tokenString($token, 'tagName')) !== 0) {
                continue;
            }

            if (!TagProcessor::queryMatches($token, $query)) {
                continue;
            }

            $this->cursor = $i + 1;
            $this->current = $this->makeCurrent($i, $token);

            return true;
        }

        $this->current = null;

        return false;
    }

    public function next_token(): bool
    {
        if ($this->cursor >= count($this->tokens)) {
            $this->current = null;

            return false;
        }

        $index = $this->cursor;
        $this->cursor = $index + 1;
        $this->current = $this->makeCurrent($index, $this->tokens[$index]);

        return true;
    }

    public function get_tag(): ?string
    {
        if ($this->current === null || ($this->current['type'] ?? null) !== 'tag') {
            return null;
        }

        $name = $this->current['name'] ?? null;

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function get_updated_html(): string
    {
        $out = '';
        foreach (array_keys($this->tokens) as $index) {
            if (!is_int($index)) {
                continue;
            }

            $out .= $this->tokenOutput($index);
        }

        return $out;
    }

    public function get_attribute(string $name): string|bool|null
    {
        $attributes = $this->currentAttributes();

        return $attributes[$name] ?? null;
    }

    public function set_attribute(string $name, string|bool $value): void
    {
        $index = $this->currentIndex();
        if ($index === null) {
            return;
        }

        $attributes = TagProcessor::tokenAttributes($this->tokens[$index]);
        $attributes[$name] = $value;
        $this->writeAttributes($index, $attributes);
    }

    public function remove_attribute(string $name): bool
    {
        $index = $this->currentIndex();
        if ($index === null) {
            return false;
        }

        $attributes = TagProcessor::tokenAttributes($this->tokens[$index]);
        if (!array_key_exists($name, $attributes)) {
            return false;
        }

        unset($attributes[$name]);
        $this->writeAttributes($index, $attributes);

        return true;
    }

    public function add_class(string $class): bool
    {
        $list = $this->get_class_list();
        if (in_array($class, $list, true)) {
            return false;
        }

        $list[] = $class;
        $this->set_attribute('class', implode(' ', $list));

        return true;
    }

    public function remove_class(string $class): bool
    {
        $list = $this->get_class_list();
        $index = array_search($class, $list, true);
        if ($index === false) {
            return false;
        }

        unset($list[$index]);
        $this->set_attribute('class', implode(' ', array_values($list)));

        return true;
    }

    public function get_modifiable_text(): string
    {
        if ($this->current === null) {
            return '';
        }

        $text = $this->current['text'] ?? null;

        return is_string($text) ? $text : '';
    }

    public function set_modifiable_text(string $text): void
    {
        $index = $this->currentIndex();
        if ($index === null) {
            return;
        }

        $this->textOverrides[$index] = $text;
        $this->modified[$index] = true;
        $this->current['text'] = $text;
    }

    /**
     * @return list<string>
     */
    public function get_breadcrumbs(): array
    {
        $limit = $this->cursor;
        if ($this->current !== null && is_int($this->current['index'] ?? null)) {
            $limit = $this->current['index'];
        }

        $stack = [];
        $void = [
            'area',
            'base',
            'br',
            'col',
            'embed',
            'hr',
            'img',
            'input',
            'link',
            'meta',
            'param',
            'source',
            'track',
            'wbr',
        ];

        foreach ($this->tokens as $index => $token) {
            if (!is_int($index) || $index >= $limit) {
                break;
            }

            if (TagProcessor::tokenString($token, 'type') !== 'tag') {
                continue;
            }

            $name = TagProcessor::tokenString($token, 'tagName');
            if (TagProcessor::tokenBool($token, 'selfClosing') || in_array($name, $void, true)) {
                continue;
            }

            if (TagProcessor::tokenBool($token, 'closing')) {
                for ($j = count($stack) - 1; $j >= 0; $j--) {
                    if ($stack[$j] === $name) {
                        array_splice($stack, $j);
                        break;
                    }
                }
            } else {
                $stack[] = $name;
            }
        }

        return $stack;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_lexical_updates(): array
    {
        $updates = [];
        foreach ($this->modified as $index => $flag) {
            if ($flag !== true) {
                continue;
            }

            $updates[] = [
                'index' => $index,
                'type' => TagProcessor::tokenString($this->tokens[$index], 'type'),
                'value' => $this->tokenOutput($index),
            ];
        }

        return $updates;
    }

    public function seek(int $offset): bool
    {
        $this->cursor = max(0, $offset);
        $this->current = null;

        return true;
    }

    public function set_bookmark(string $name): bool
    {
        $position = $this->cursor;
        if ($this->current !== null && is_int($this->current['index'] ?? null)) {
            $position = $this->current['index'];
        }

        $this->bookmarks[$name] = $position;

        return true;
    }

    public function release_bookmark(string $name): void
    {
        unset($this->bookmarks[$name]);
    }

    public function get_bookmark(string $name): ?int
    {
        return $this->bookmarks[$name] ?? null;
    }

    /**
     * @param array<string, mixed> $token
     * @return array<string, mixed>
     */
    private function makeCurrent(int $index, array $token): array
    {
        $type = TagProcessor::tokenString($token, 'type');
        $text = '';
        if ($type === 'text') {
            $text = $this->textOverrides[$index] ?? TagProcessor::tokenString($token, 'text');
        }

        return [
            'index' => $index,
            'type' => $type,
            'name' => TagProcessor::tokenString($token, 'tagName'),
            'attributes' => TagProcessor::tokenAttributes($token),
            'text' => $text,
            'start' => TagProcessor::tokenInt($token, 'offset'),
            'length' => TagProcessor::tokenInt($token, 'length'),
            'has_self_closing' => TagProcessor::tokenBool($token, 'selfClosing'),
            'closing' => TagProcessor::tokenBool($token, 'closing'),
        ];
    }

    private function currentIndex(): ?int
    {
        if ($this->current === null) {
            return null;
        }

        $index = $this->current['index'] ?? null;

        return is_int($index) ? $index : null;
    }

    /**
     * @return array<string, string|bool>
     */
    private function currentAttributes(): array
    {
        $index = $this->currentIndex();
        if ($index === null || !isset($this->tokens[$index])) {
            return [];
        }

        return TagProcessor::tokenAttributes($this->tokens[$index]);
    }

    /**
     * @param array<string, string|bool> $attributes
     */
    private function writeAttributes(int $index, array $attributes): void
    {
        $this->tokens[$index]['attributes'] = $attributes;
        $this->modified[$index] = true;
        if ($this->current !== null && ($this->current['index'] ?? null) === $index) {
            $this->current['attributes'] = $attributes;
        }
    }

    private function tokenOutput(int $index): string
    {
        $token = $this->tokens[$index];
        $type = TagProcessor::tokenString($token, 'type');

        if ($type === 'tag') {
            if (($this->modified[$index] ?? false) === true) {
                return TagProcessor::renderTag($token);
            }

            return TagProcessor::tokenString($token, 'raw');
        }

        if ($type === 'text') {
            return $this->textOverrides[$index] ?? TagProcessor::tokenString($token, 'raw');
        }

        return TagProcessor::tokenString($token, 'raw');
    }

    /**
     * @return list<string>
     */
    private function get_class_list(): array
    {
        $class = $this->get_attribute('class');
        if (!is_string($class) || trim($class) === '') {
            return [];
        }

        $parts = preg_split('/\s+/', trim($class));
        if ($parts === false) {
            return [];
        }

        $result = [];
        foreach ($parts as $part) {
            if ($part !== '') {
                $result[] = $part;
            }
        }

        return $result;
    }
}
