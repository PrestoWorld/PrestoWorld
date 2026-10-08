<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler;

/**
 * Stage 1 pipeline (§10.2.1): quét thư mục plugin/theme.
 */
final class PluginScanner
{
    private const SKIP_DIRS = ['.git', '.svn', '.hg', 'vendor', 'node_modules', 'compiled', '.idea', '.vscode'];

    /**
     * Danh sách file PHP tương đối, đã sort — dùng chung cho report (§10.9.1).
     *
     * @return list<string>
     */
    public function scan(string $sourceDir): array
    {
        $sourceDir = rtrim($sourceDir, '/');
        if (!is_dir($sourceDir)) {
            throw new CompilerException("Source directory not found: {$sourceDir}");
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        /** @var \SplFileInfo $info */
        foreach ($iterator as $info) {
            if (!$info->isFile() || strtolower($info->getExtension()) !== 'php') {
                continue;
            }

            $absolute = $info->getPathname();
            if ($this->shouldSkip($sourceDir, $absolute)) {
                continue;
            }

            $files[] = ltrim(substr($absolute, strlen($sourceDir)), '/');
        }

        sort($files);

        return $files;
    }

    /**
     * Slug sinh namespace theo §10.6.2 — basename thư mục, chuẩn hóa.
     */
    public function slug(string $sourceDir): string
    {
        $base = basename(rtrim($sourceDir, '/'));
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $base) ?? $base);
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'plugin';
    }

    private function shouldSkip(string $sourceDir, string $absolute): bool
    {
        $relative = substr($absolute, strlen($sourceDir) + 1);
        $parts = explode('/', str_replace('\\', '/', $relative));

        foreach (array_slice($parts, 0, -1) as $dir) {
            if (in_array($dir, self::SKIP_DIRS, true)) {
                return true;
            }
        }

        return false;
    }
}
