<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Cycle\Database\DatabaseInterface;
use PrestoWorld\Contracts\Plugin\PluginStoreInterface;
use Witals\Framework\Support\Assets\Contracts\AssetTransformInterface;
use App\Storage\CloudStorageManager;

trait AdminControllerHelperTrait
{
    protected string $prefix;
    protected bool $wordPressMode;
    protected ?CloudStorageManager $cloud = null;

    protected function initHelpers(DatabaseInterface $db): void
    {
        $this->prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
        $this->wordPressMode = getenv('PW_CONTENT_DIR') !== false && $this->prefix === 'wp_';
    }

    protected function isWordPress(): bool
    {
        return $this->wordPressMode;
    }

    protected function cloud(): ?CloudStorageManager
    {
        if ($this->cloud === null) {
            $mgr = new CloudStorageManager();
            if ($mgr->isEnabled()) {
                $this->cloud = $mgr;
                $this->registerCloudRewriteRule($mgr);
            }
        }
        return $this->cloud;
    }

    protected function registerCloudRewriteRule(CloudStorageManager $mgr): void
    {
        try {
            $cdnUrl = rtrim(getenv('PW_STORAGE_CDN_URL') ?: '', '/');
            if ($cdnUrl === '') {
                return;
            }
            $transformer = app(AssetTransformInterface::class);
            $transformer->addRule(
                '#^/storage/uploads/(.+)$#',
                $cdnUrl . '/$1'
            );
        } catch (\Throwable) {
        }
    }

    protected function resolveMediaUrl(string $url): string
    {
        try {
            $transformer = app(AssetTransformInterface::class);
            $config = $this->getMediaTransformConfig();
            $transformed = $transformer->transformUrl(
                $url,
                $config['from'] ?? 'wp-content/uploads',
                $config['to'] ?? 'storage/uploads'
            );
            return $transformed !== '' ? $transformed : $url;
        } catch (\Throwable) {
            return $url;
        }
    }

    protected function getMediaTransformConfig(): array
    {
        $configPath = $this->getBasePath() . '/config/assets.php';
        if (!file_exists($configPath)) {
            return [];
        }
        $config = require $configPath;
        return $config['transforms'][0] ?? [];
    }

    protected function getBasePath(): string
    {
        static $base = null;
        if ($base === null) {
            $base = defined('PW_BASE_PATH') ? PW_BASE_PATH : getcwd();
        }
        return $base;
    }

    protected function formatDate(mixed $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d H:i');
        }
        if (is_string($date) && $date !== '') {
            return date('Y-m-d H:i', strtotime($date));
        }
        return date('Y-m-d H:i');
    }

    protected function relativeTime(mixed $datetime): string
    {
        $timestamp = $datetime instanceof \DateTimeInterface
            ? $datetime->getTimestamp()
            : (is_string($datetime) ? strtotime($datetime) : time());

        $diff = time() - $timestamp;

        return match (true) {
            $diff < 60 => 'Just now',
            $diff < 3600 => floor($diff / 60) . ' minutes ago',
            $diff < 86400 => floor($diff / 3600) . ' hours ago',
            $diff < 604800 => floor($diff / 8640) . ' days ago',
            default => date('M j', $timestamp),
        };
    }

    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'publish', 'published' => 'Published',
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            default => 'Draft',
        };
    }
}
