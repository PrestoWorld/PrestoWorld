<?php

declare(strict_types=1);

namespace App\Storage;

class CloudStorageManager
{
    private ?StorageProvider $provider = null;
    private string $driverName;
    private string $bucket;

    public function __construct()
    {
        $this->driverName = getenv('PW_STORAGE_DRIVER') ?: '';
        $this->bucket = getenv('PW_STORAGE_BUCKET') ?: '';
    }

    public function isEnabled(): bool
    {
        return $this->driverName !== '' && $this->bucket !== '';
    }

    public function provider(): StorageProvider
    {
        if ($this->provider === null) {
            $this->provider = $this->buildProvider();
        }
        return $this->provider;
    }

    public function driverName(): string
    {
        return $this->driverName;
    }

    private function buildProvider(): StorageProvider
    {
        $config = [
            'bucket' => $this->bucket,
            'region' => getenv('PW_STORAGE_REGION') ?: 'us-east-1',
            'access_key' => getenv('PW_STORAGE_ACCESS_KEY') ?: '',
            'secret_key' => getenv('PW_STORAGE_SECRET_KEY') ?: '',
            'endpoint' => getenv('PW_STORAGE_ENDPOINT') ?: '',
            'cdn_url' => getenv('PW_STORAGE_CDN_URL') ?: '',
            'provider' => $this->driverName,
            // BunnyCDN specific
            'storage_zone' => getenv('PW_STORAGE_STORAGE_ZONE') ?: $this->bucket,
            'api_key' => getenv('PW_STORAGE_API_KEY') ?: '',
            'api_url' => getenv('PW_STORAGE_API_URL') ?: '',
        ];

        return match ($this->driverName) {
            's3', 'backblaze', 'digitalocean' => new S3StorageProvider($config),
            'bunnycdn' => new BunnyCDNStorageProvider($config),
            'gcs' => new GCSStorageProvider($config),
            default => throw new \RuntimeException("Unsupported storage driver: {$this->driverName}"),
        };
    }
}
