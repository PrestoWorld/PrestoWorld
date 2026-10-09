<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Html;

/**
 * TagProcessor — replaces WP_HTML_Tag_Processor (spec 10 §10.5).
 */
class TagProcessor
{
    private string $html;
    private int $pos = 0;

    /** @var array<string, mixed>|null */
    private ?array $current = null;

    /** @var list<array<string, mixed>> */
    private array $tokens;

    /** @var array<int, bool> */
    private array $modified = [];

    /** @var array<int, string> */
    private array $textOverrides = [];

    /** @var array<string, int> */
    private array $bookmarks = [];

    public function __construct(string $html = '')
    {
        $this->html = $html;
        $this->tokens = self::tokenizeAst($this->html);
    }

    /**
     * @param string|array<string, mixed> $query
     * @param array<string, mixed> $queryArgs
     */
    public function next_tag(string|array $query = '', array $queryArgs = []): bool
    {
        $tagName = '';
        if (is_array($query)) {
            $queryArgs = $query;
        } else {
            $tagName = $query;
        }

        $count = count($this->tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $this->tokens[$i];
            if (self::tokenString($token, 'type') !== 'tag') {
                continue;
            }

            $offset = self::tokenInt($token, 'offset');
            if ($offset < $this->pos) {
                continue;
            }

            if ($tagName !== '' && strcasecmp($tagName, self::tokenString($token, 'tagName')) !== 0) {
                continue;
            }

            if (!self::queryMatches($token, $queryArgs)) {
                continue;
            }

            $this->current = $this->makeCurrent($i, $token);
            $this->pos = $offset + self::tokenInt($token, 'length');

            return true;
        }

        $this->current = null;

        return false;
    }

    public function next_token(): bool
    {
        $count = count($this->tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $this->tokens[$i];
            $offset = self::tokenInt($token, 'offset');
            if ($offset < $this->pos) {
                continue;
            }

            $this->current = $this->makeCurrent($i, $token);
            $this->pos = $offset + max(1, self::tokenInt($token, 'length'));

            return true;
        }

        $this->current = null;

        return false;
    }

    public function get_tag(): ?string
    {
        if ($this->current === null || ($this->current['type'] ?? null) !== 'tag') {
            return null;
        }

        $name = $this->current['name'] ?? null;

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function is_tag_closer(): bool
    {
        return ($this->current['closing'] ?? false) === true;
    }

    public function get_token_type(): string
    {
        if ($this->current === null) {
            return 'text';
        }

        $type = $this->current['type'] ?? null;

        return is_string($type) && $type !== '' ? $type : 'text';
    }

    public function get_token_name(): ?string
    {
        return $this->get_tag();
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

        $attributes = self::tokenAttributes($this->tokens[$index]);
        $attributes[$name] = $value;
        $this->writeAttributes($index, $attributes);
    }

    public function remove_attribute(string $name): bool
    {
        $index = $this->currentIndex();
        if ($index === null) {
            return false;
        }

        $attributes = self::tokenAttributes($this->tokens[$index]);
        if (!array_key_exists($name, $attributes)) {
            return false;
        }

        unset($attributes[$name]);
        $this->writeAttributes($index, $attributes);

        return true;
    }

    public function has_class(string $class): bool
    {
        return in_array($class, $this->get_class_list(), true);
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

    /**
     * @return list<string>
     */
    public function get_class_list(): array
    {
        $class = $this->get_attribute('class');

        return self::splitClasses(is_string($class) ? $class : '');
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
                'type' => self::tokenString($this->tokens[$index], 'type'),
                'value' => $this->tokenOutput($index),
            ];
        }

        return $updates;
    }

    public function seek(int $offset): bool
    {
        $this->pos = max(0, $offset);
        $this->current = null;

        return true;
    }

    /**
     * @return list<string>
     */
    public function get_breadcrumbs(): array
    {
        $limit = $this->pos;
        if ($this->current !== null && is_int($this->current['start'] ?? null)) {
            $limit = $this->current['start'];
        }

        $stack = [];
        $void = self::voidElements();
        foreach ($this->tokens as $token) {
            if (self::tokenString($token, 'type') !== 'tag') {
                continue;
            }

            if (self::tokenInt($token, 'offset') >= $limit) {
                break;
            }

            $name = self::tokenString($token, 'tagName');
            if (self::tokenBool($token, 'selfClosing') || in_array($name, $void, true)) {
                continue;
            }

            if (self::tokenBool($token, 'closing')) {
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

    public function set_bookmark(string $name): bool
    {
        $offset = $this->pos;
        if ($this->current !== null && is_int($this->current['start'] ?? null)) {
            $offset = $this->current['start'];
        }

        $this->bookmarks[$name] = $offset;

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
        $type = self::tokenString($token, 'type');
        $text = '';
        if ($type === 'text') {
            $text = $this->textOverrides[$index] ?? self::tokenString($token, 'text');
        }

        return [
            'index' => $index,
            'type' => $type,
            'name' => self::tokenString($token, 'tagName'),
            'attributes' => self::tokenAttributes($token),
            'text' => $text,
            'start' => self::tokenInt($token, 'offset'),
            'length' => self::tokenInt($token, 'length'),
            'has_self_closing' => self::tokenBool($token, 'selfClosing'),
            'closing' => self::tokenBool($token, 'closing'),
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

        return self::tokenAttributes($this->tokens[$index]);
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
        $type = self::tokenString($token, 'type');

        if ($type === 'tag') {
            if (($this->modified[$index] ?? false) === true) {
                return self::renderTag($token);
            }

            return self::tokenString($token, 'raw');
        }

        if ($type === 'text') {
            return $this->textOverrides[$index] ?? self::tokenString($token, 'raw');
        }

        return self::tokenString($token, 'raw');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function tokenizeAst(string $html): array
    {
        /** @var list<array<string, mixed>> $tokens */
        $tokens = [];
        $length = strlen($html);
        $offset = 0;

        while ($offset < $length) {
            $lt = strpos($html, '<', $offset);
            if ($lt === false) {
                $chunk = substr($html, $offset);
                $tokens[] = self::makeToken('text', '', [], false, false, $chunk, $offset, $chunk);
                break;
            }

            if ($lt > $offset) {
                $chunk = substr($html, $offset, $lt - $offset);
                $tokens[] = self::makeToken('text', '', [], false, false, $chunk, $offset, $chunk);
            }

            $rest = substr($html, $lt);

            if (str_starts_with($rest, '<!--')) {
                $end = strpos($html, '-->', $lt + 4);
                $endPos = $end === false ? $length : $end + 3;
                $raw = substr($html, $lt, $endPos - $lt);
                $tokens[] = self::makeToken('comment', '', [], false, false, $raw, $lt);
                $offset = $endPos;
                continue;
            }

            if (str_starts_with($rest, '<!') || str_starts_with($rest, '<?')) {
                $end = strpos($html, '>', $lt);
                $endPos = $end === false ? $length : $end + 1;
                $raw = substr($html, $lt, $endPos - $lt);
                $tokens[] = self::makeToken('comment', '', [], false, false, $raw, $lt);
                $offset = $endPos;
                continue;
            }

            if (preg_match(self::tagPattern(), $rest, $m) === 1) {
                $raw = $m[0];
                $closing = ($m[1] ?? '') === '/';
                $tagName = strtolower($m[2] ?? '');
                $selfClosing = str_ends_with(rtrim($raw), '/>') || in_array($tagName, self::voidElements(), true);
                $tokens[] = self::makeToken(
                    'tag',
                    $tagName,
                    self::parseAttributes($m[3] ?? ''),
                    $selfClosing,
                    $closing,
                    $raw,
                    $lt
                );
                $offset = $lt + strlen($raw);

                if (!$closing && !$selfClosing && ($tagName === 'script' || $tagName === 'style')) {
                    $closePos = stripos($html, '</' . $tagName, $offset);
                    if ($closePos !== false) {
                        if ($closePos > $offset) {
                            $chunk = substr($html, $offset, $closePos - $offset);
                            $tokens[] = self::makeToken('text', '', [], false, false, $chunk, $offset, $chunk);
                        }
                        $offset = $closePos;
                    }
                }
                continue;
            }

            $chunk = substr($html, $lt, 1);
            $tokens[] = self::makeToken('text', '', [], false, false, $chunk, $lt, $chunk);
            $offset = $lt + 1;
        }

        return $tokens;
    }

    /**
     * @param array<mixed, mixed> $token
     * @param array<string, mixed> $query
     */
    public static function queryMatches(array $token, array $query): bool
    {
        if ($query === []) {
            return true;
        }

        if (self::tokenString($token, 'type') !== 'tag') {
            return false;
        }

        $tagName = self::tokenString($token, 'tagName');
        $attributes = self::tokenAttributes($token);

        foreach ($query as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if ($key === 'tag_name' || $key === 'tag') {
                if (!self::matchStringOp($tagName, $value, true)) {
                    return false;
                }
                continue;
            }

            if ($key === 'class') {
                $classes = self::splitClasses(isset($attributes['class']) && is_string($attributes['class']) ? $attributes['class'] : '');
                if (is_array($value)) {
                    $needle = $value['contains'] ?? null;
                    if (is_string($needle) && !in_array($needle, $classes, true)) {
                        return false;
                    }
                } elseif (is_string($value) && !in_array($value, $classes, true)) {
                    return false;
                }
                continue;
            }

            if (!array_key_exists($key, $attributes)) {
                return false;
            }

            if (is_string($value) && $value !== '') {
                $attribute = $attributes[$key];
                if (!is_string($attribute) || !str_contains($attribute, $value)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $token
     */
    public static function renderTag(array $token): string
    {
        $name = self::tokenString($token, 'tagName');
        if (self::tokenBool($token, 'closing')) {
            return '</' . $name . '>';
        }

        $out = '<' . $name;
        foreach (self::tokenAttributes($token) as $attribute => $value) {
            if (is_string($value)) {
                $out .= ' ' . $attribute . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
            } else {
                $out .= ' ' . $attribute;
            }
        }

        if (self::tokenBool($token, 'selfClosing')) {
            $out .= ' />';
        } else {
            $out .= '>';
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $token
     */
    public static function tokenString(array $token, string $key): string
    {
        $value = $token[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    /**
     * @param array<string, mixed> $token
     */
    public static function tokenInt(array $token, string $key): int
    {
        $value = $token[$key] ?? null;

        return is_int($value) ? $value : 0;
    }

    /**
     * @param array<string, mixed> $token
     */
    public static function tokenBool(array $token, string $key): bool
    {
        return ($token[$key] ?? false) === true;
    }

    /**
     * @param array<string, mixed> $token
     * @return array<string, string|bool>
     */
    public static function tokenAttributes(array $token): array
    {
        $attributes = $token['attributes'] ?? null;
        if (!is_array($attributes)) {
            return [];
        }

        $result = [];
        foreach ($attributes as $key => $value) {
            if (is_string($key) && (is_string($value) || is_bool($value))) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private static function voidElements(): array
    {
        return [
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
    }

    /**
     * @return list<string>
     */
    private static function splitClasses(string $class): array
    {
        if (trim($class) === '') {
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

    private static function matchStringOp(string $subject, mixed $value, bool $caseInsensitive): bool
    {
        if (is_string($value)) {
            return $caseInsensitive ? strcasecmp($subject, $value) === 0 : $subject === $value;
        }

        if (is_array($value)) {
            $contains = $value['contains'] ?? null;
            if (is_string($contains)) {
                return $caseInsensitive ? stripos($subject, $contains) !== false : str_contains($subject, $contains);
            }
        }

        return true;
    }

    private static function tagPattern(): string
    {
        return '/^<\s*(\/)?([a-zA-Z0-9:_-]+)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/s';
    }

    /**
     * @return array<string, string|bool>
     */
    private static function parseAttributes(string $raw): array
    {
        $attributes = [];
        if ($raw === '') {
            return $attributes;
        }

        $matched = preg_match_all(
            '/([a-zA-Z0-9:_-]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?/',
            $raw,
            $matches,
            PREG_SET_ORDER
        );

        if ($matched === false || $matched === 0) {
            return $attributes;
        }

        /** @var list<array<int, string>> $matches */
        foreach ($matches as $match) {
            $name = strtolower($match[1] ?? '');
            if ($name === '') {
                continue;
            }

            $value = $match[2] ?? '';
            if ($value === '') {
                $value = $match[3] ?? '';
            }
            if ($value === '') {
                $value = $match[4] ?? '';
            }

            $attributes[$name] = $value === '' ? true : $value;
        }

        return $attributes;
    }

    /**
     * @param array<mixed, mixed> $attributes
     * @return array<string, mixed>
     */
    private static function makeToken(
        string $type,
        string $tagName,
        array $attributes,
        bool $selfClosing,
        bool $closing,
        string $raw,
        int $offset,
        string $text = ''
    ): array {
        return [
            'type' => $type,
            'tagName' => $tagName,
            'attributes' => $attributes,
            'selfClosing' => $selfClosing,
            'closing' => $closing,
            'raw' => $raw,
            'offset' => $offset,
            'length' => strlen($raw),
            'text' => $text,
        ];
    }
}
