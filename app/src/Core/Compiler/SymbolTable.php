<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler;

/**
 * Symbol table plugin-wide — kết quả Phase A (§10.2.1 Stage 3).
 *
 * Chứa toàn bộ function/class do plugin khai báo + ánh xạ
 * file → class sinh ra theo quy tắc §10.6.2.
 */
final class SymbolTable
{
    /** @var array<string, FunctionSymbol> key = lowercase function name */
    private array $functions;

    /** @var array<string, ClassSymbol> key = lowercase class name */
    private array $classes;

    /** @var array<string, string> relative file → generated class name (KHÔNG có namespace) */
    private array $fileClassMap = [];

    /**
     * @param array<string, FunctionSymbol> $functions
     * @param array<string, ClassSymbol> $classes
     */
    public function __construct(
        private readonly string $slug,
        private readonly string $type,
        array $functions = [],
        array $classes = [],
    ) {
        $this->functions = [];
        foreach ($functions as $key => $symbol) {
            $this->functions[strtolower($key)] = $symbol;
        }

        $this->classes = [];
        foreach ($classes as $key => $symbol) {
            $this->classes[strtolower($key)] = $symbol;
        }

        $this->buildFileClassMap();
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function type(): string
    {
        return $this->type;
    }

    /**
     * Namespace sinh ra theo slug (§10.6.2): Plugins\ContactForm7 | Themes\MyTheme.
     */
    public function namespace(): string
    {
        $prefix = $this->type === 'theme' ? 'Themes' : 'Plugins';

        return $prefix . '\\' . self::studly($this->slug);
    }

    /**
     * @return array<string, FunctionSymbol>
     */
    public function functions(): array
    {
        return $this->functions;
    }

    /**
     * @return array<string, ClassSymbol>
     */
    public function classes(): array
    {
        return $this->classes;
    }

    public function functionFor(string $name): ?FunctionSymbol
    {
        return $this->functions[strtolower($name)] ?? null;
    }

    public function classFor(string $name): ?ClassSymbol
    {
        return $this->classes[strtolower($name)] ?? null;
    }

    /**
     * Plugin tự định nghĩa function này? (pluggable override — Pass 2 phải skip)
     */
    public function hasPluginFunction(string $name): bool
    {
        return isset($this->functions[strtolower($name)]);
    }

    public function isPluginClass(string $name): bool
    {
        return isset($this->classes[strtolower($name)]);
    }

    /**
     * Tên class sinh ra cho file (đã resolve va chạm, §10.6.2).
     */
    public function classNameFor(string $relativeFile): string
    {
        return $this->fileClassMap[$relativeFile] ?? self::studly(pathinfo($relativeFile, PATHINFO_FILENAME));
    }

    /**
     * FQCN đầy đủ của class sinh ra cho file.
     */
    public function fqcnFor(string $relativeFile): string
    {
        return $this->namespace() . '\\' . $this->classNameFor($relativeFile);
    }

    /**
     * Tên method tĩnh cho function: camelCase, strip prefix theo slug (§10.6.3).
     *
     * vd: myplugin_format_price → formatPrice (slug=myplugin),
     *     cf7_validate_email → validateEmail (initials của contact-form-7).
     */
    public function methodFor(FunctionSymbol $fn): string
    {
        $name = $fn->name;
        $lower = strtolower($name);

        foreach ($this->prefixCandidates() as $prefix) {
            if ($prefix !== '' && str_starts_with($lower, $prefix . '_')) {
                $name = substr($name, strlen($prefix) + 1);
                break;
            }
        }

        return self::camelCase($name);
    }

    /**
     * Call-site target: FQCN::method (§10.6.4).
     */
    public function targetFor(FunctionSymbol $fn): string
    {
        return $this->fqcnFor($fn->file) . '::' . $this->methodFor($fn);
    }

    /**
     * Danh sách prefix có thể bị strip: slug, slug không separator, initials.
     *
     * @return list<string>
     */
    private function prefixCandidates(): array
    {
        $slug = strtolower($this->slug);
        $compact = str_replace('-', '', $slug);
        $initials = '';
        foreach (explode('-', $slug) as $segment) {
            $initials .= $segment;
        }

        $candidates = [$slug, $compact, $initials, 'wp' . $initials];

        return array_values(array_unique(array_filter($candidates)));
    }

    private function buildFileClassMap(): void
    {
        $files = [];
        foreach ($this->functions as $symbol) {
            $files[$symbol->file] = true;
        }
        ksort($files);

        $used = [];
        foreach (array_keys($files) as $file) {
            $base = self::studly(pathinfo($file, PATHINFO_FILENAME));
            if ($base === '') {
                $base = 'File';
            }

            $name = $base;
            $suffix = 2;
            while (isset($used[strtolower($name)])) {
                $name = $base . $suffix;
                $suffix++;
            }

            $used[strtolower($name)] = true;
            $this->fileClassMap[$file] = $name;
        }
    }

    public static function studly(string $value): string
    {
        $parts = preg_split('/[-_\s]+/', $value) ?: [];
        $out = '';
        foreach ($parts as $part) {
            if ($part !== '') {
                $out .= ucfirst($part);
            }
        }

        return $out;
    }

    public static function camelCase(string $value): string
    {
        $parts = preg_split('/_+/', $value) ?: [];
        $out = '';
        foreach ($parts as $part) {
            if ($part !== '') {
                $out .= ucfirst($part);
            }
        }

        if ($out === '') {
            return $value;
        }

        return lcfirst($out);
    }
}
