<?php

declare(strict_types=1);

namespace App\Storage;

class S3SdkStorageProvider implements StorageProvider
{
    private string $bucket;
    private string $region;
    private string $endpoint;
    private string $cdnUrl;
    private string $provider;
    private ?object $client = null;

    public function __construct(array $config)
    {
        $this->bucket = $config['bucket'] ?? '';
        $this->region = $config['region'] ?? 'us-east-1';
        $this->endpoint = rtrim($config['endpoint'] ?? "https://{$this->bucket}.s3.{$this->region}.amazonaws.com", '/');
        $this->cdnUrl = rtrim($config['cdn_url'] ?? $this->endpoint, '/');
        $this->provider = $config['provider'] ?? 's3';

        if ($this->bucket === '') {
            throw new \InvalidArgumentException('S3 bucket is required');
        }
    }

    public function driverName(): string
    {
        return $this->provider;
    }

    public static function isAvailable(): bool
    {
        return class_exists(\Aws\S3\S3Client::class);
    }

    private function client(): object
    {
        if ($this->client === null) {
            if (!self::isAvailable()) {
                throw new \RuntimeException(
                    'AWS SDK not installed. Run: composer require aws/aws-sdk-php'
                );
            }
            $this->client = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $this->region,
                'endpoint' => $this->endpoint,
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => getenv('PW_STORAGE_ACCESS_KEY') ?: '',
                    'secret' => getenv('PW_STORAGE_SECRET_KEY') ?: '',
                ],
            ]);
        }
        return $this->client;
    }

    public function upload(string $path, string $sourceFile): string
    {
        if (!file_exists($sourceFile) || !is_readable($sourceFile)) {
            throw StorageException::fileNotFound($sourceFile);
        }

        $mime = mime_content_type($sourceFile) ?: 'application/octet-stream';

        $this->client()->putObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
            'SourceFile' => $sourceFile,
            'ContentType' => $mime,
            'ACL' => 'public-read',
        ]);

        return $this->url($path);
    }

    public function delete(string $path): bool
    {
        $this->client()->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);
        return true;
    }

    public function exists(string $path): bool
    {
        return $this->client()->doesObjectExist($this->bucket, $path);
    }

    public function url(string $path): string
    {
        return "{$this->cdnUrl}/{$path}";
    }

    public function list(string $prefix = ''): array
    {
        $items = [];
        $continuationToken = null;

        do {
            $args = [
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
                'MaxKeys' => 1000,
            ];

            if ($continuationToken !== null) {
                $args['ContinuationToken'] = $continuationToken;
            }

            $result = $this->client()->listObjectsV2($args);

            foreach ($result['Contents'] ?? [] as $object) {
                $key = $object['Key'] ?? '';
                if ($key !== '' && !str_ends_with($key, '/')) {
                    $items[] = [
                        'path' => $key,
                        'size' => (int) ($object['Size'] ?? 0),
                        'mtime' => ($object['LastModified'] ?? null) instanceof \DateTimeInterface
                            ? $object['LastModified']->getTimestamp()
                            : 0,
                    ];
                }
            }

            $continuationToken = $result['NextContinuationToken'] ?? null;
        } while ($continuationToken !== null);

        return $items;
    }
}
