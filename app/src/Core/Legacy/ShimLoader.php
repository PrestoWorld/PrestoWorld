<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Legacy;

/**
 * ShimLoader (spec 10 §10.7.1) — nạp shim functions/classes theo nhóm.
 *
 * Tối ưu OpCache: chỉ require đúng nhóm cần. Nhóm cấp cao (escaping, options,
 * formatting...) delegate qua các facades PWE Core; nhóm 'hooks'/'compat' chứa
 * định nghĩa hàm global thật sự (wp-compatibility).
 */
final class ShimLoader
{
    /** @var array<string, string> */
    private const GROUPS = [
        'hooks' => 'wp-compatibility.php',
        'compat' => 'wp-shims.php',
        'escaping' => 'wp-shims.php',
        'options' => 'wp-shims.php',
        'formatting' => 'wp-shims.php',
        'url' => 'wp-shims.php',
        'users' => 'wp-shims.php',
        'all' => 'all',
    ];

    /** @var list<string> */
    private array $loaded = [];

    private bool $autoloadRegistered = false;

    public function __construct(private readonly string $directory = '')
    {
    }

    public function loadGroup(string $group): bool
    {
        $file = self::GROUPS[$group] ?? null;
        if ($file === null) {
            return false;
        }

        if ($file === 'all') {
            foreach (array_unique(self::GROUPS) as $groupFile) {
                if ($groupFile !== 'all') {
                    $this->loadFile($groupFile);
                }
            }
            $this->loaded[] = 'all';
            return true;
        }

        if (!$this->loadFile($file)) {
            return false;
        }

        if (!in_array($group, $this->loaded, true)) {
            $this->loaded[] = $group;
        }

        return true;
    }

    /**
     * @param list<string> $groups
     */
    public function loadGroups(array $groups): void
    {
        foreach ($groups as $group) {
            $this->loadGroup($group);
        }
    }

    /** @return list<string> */
    public function loadedGroups(): array
    {
        return $this->loaded;
    }

    /**
     * Đăng ký autoload cho class shim (spec 10 §10.5 mode 's').
     * PW class thật được autoload qua Composer; map legacy-name ở đây chỉ để
     * hỗ trợ mã chưa compile (Mode B) resolve tên WP_*.
     */
    public function registerAutoload(): void
    {
        if ($this->autoloadRegistered) {
            return;
        }

        $this->autoloadRegistered = true;

        spl_autoload_register(static function (string $class): void {
            $map = ClassShimMap::resolve($class);
            if ($map !== null) {
                require_once $map;
            }
        });
    }

    private function loadFile(string $fileName): bool
    {
        $directories = $this->directory !== '' ? [$this->directory] : self::shimDirectories();

        foreach ($directories as $dir) {
            $path = rtrim($dir, '/') . '/' . $fileName;
            if (is_file($path)) {
                require_once $path;
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function shimDirectories(): array
    {
        return [
            dirname(__DIR__, 3) . '/app/src/Core/Legacy',
            dirname(__DIR__) . '/Legacy',
        ];
    }
}