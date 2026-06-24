<?php

declare(strict_types=1);

namespace App\Storage;

class BunnyCDNStorageProvider implements StorageProvider
{
    private string $apiUrl;
    private string $storageZone;
    private string $apiKey;
    private string $cdnUrl;

    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 200;

    public function __construct(array $config)
    {
        $this->storageZone = $config['storage_zone'] ?? '';
        $this->apiKey = $config['api_key'] ?? '';
        $this->cdnUrl = rtrim($config['cdn_url'] ?? "https://{$this->storageZone}.b-cdn.net", '/');
        $this->apiUrl = rtrim(
            $config['api_url'] ?? "https://storage.bunnycdn.com/{$this->storageZone}",
            '/'
        );

        if ($this->storageZone === '') {
            throw new \InvalidArgumentException('BunnyCDN storage zone is required');
        }
        if ($this->apiKey === '') {
            throw new \InvalidArgumentException('BunnyCDN API key is required');
        }
    }

    public function driverName(): string
    {
        return 'bunnycdn';
    }

    public function upload(string $path, string $sourceFile): string
    {
        $this->validatePath($path);

        if (!file_exists($sourceFile) || !is_readable($sourceFile)) {
            throw StorageException::fileNotFound($sourceFile);
        }

        $content = file_get_contents($sourceFile);
        if ($content === false) {
            throw StorageException::uploadFailed($path, "Cannot read source file: {$sourceFile}");
        }

        $this->bunnyRequest('PUT', $path, $content);
        return $this->url($path);
    }

    public function delete(string $path): bool
    {
        $this->validatePath($path);
        $this->bunnyRequest('DELETE', $path);
        return true;
    }

    public function exists(string $path): bool
    {
        $this->validatePath($path);
        try {
            $this->bunnyRequest('HEAD', $path);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function url(string $path): string
    {
        $this->validatePath($path);
        return "{$this->cdnUrl}/{$path}";
    }

    public function list(string $prefix = ''): array
    {
        $dir = dirname($prefix);
        $json = $this->bunnyRequest('GET', $dir !== '.' ? $dir . '/' : '');
        $items = [];

        if (!is_array($json)) {
            return $items;
        }

        foreach ($json as $entry) {
            if (($entry['IsDirectory'] ?? false)) {
                continue;
            }
            $objectName = $entry['ObjectName'] ?? '';
            $fullPath = ($dir !== '.' ? $dir . '/' : '') . $objectName;

            if ($prefix === '' || str_starts_with($fullPath, $prefix)) {
                $items[] = [
                    'path' => $fullPath,
                    'size' => (int) ($entry['Length'] ?? 0),
                    'mtime' => strtotime($entry['LastChanged'] ?? '') ?: 0,
                ];
            }
        }

        return $items;
    }

    private function bunnyRequest(string $method, string $path, string $body = ''): mixed
    {
        $attempt = 0;

        while (true) {
            $attempt++;
            try {
                return $this->doBunnyRequest($method, $path, $body);
            } catch (\Throwable $e) {
                if ($attempt >= self::MAX_RETRIES || $this->isNonRetryable($e)) {
                    throw $e;
                }
                usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
            }
        }
    }

    private function doBunnyRequest(string $method, string $path, string $body = ''): mixed
    {
        $path = ltrim($path, '/');
        $url = "{$this->apiUrl}/{$path}";

        $ch = curl_init();
        $headers = [
            "AccessKey: {$this->apiKey}",
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method === 'PUT' && $body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, ['Content-Type: application/octet-stream']));
        }

        if ($method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            $reason = $error ?: "HTTP {$httpCode}";
            if ($httpCode === 401 || $httpCode === 403) {
                throw StorageException::authenticationFailed('BunnyCDN');
            }
            throw new StorageException("BunnyCDN {$method} {$path} failed: {$reason}", $httpCode);
        }

        if ($method === 'GET' && $response !== false && $response !== '') {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $response;
    }

    private function isNonRetryable(\Throwable $e): bool
    {
        if ($e instanceof StorageException) {
            return in_array($e->getCode(), [400, 401, 403, 404, 405], true);
        }
        return false;
    }

    private function validatePath(string $path): void
    {
        if ($path === '') {
            throw new \InvalidArgumentException('Storage path must not be empty');
        }
        if (str_contains($path, '..')) {
            throw new \InvalidArgumentException('Storage path must not contain parent directory references');
        }
    }
}
