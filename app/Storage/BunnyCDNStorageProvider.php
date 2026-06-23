<?php

declare(strict_types=1);

namespace App\Storage;

class BunnyCDNStorageProvider implements StorageProvider
{
    private string $apiUrl;
    private string $storageZone;
    private string $apiKey;
    private string $cdnUrl;

    public function __construct(array $config)
    {
        $this->storageZone = $config['storage_zone'] ?? '';
        $this->apiKey = $config['api_key'] ?? '';
        $this->cdnUrl = rtrim($config['cdn_url'] ?? "https://{$this->storageZone}.b-cdn.net", '/');
        $this->apiUrl = rtrim(
            $config['api_url'] ?? "https://storage.bunnycdn.com/{$this->storageZone}",
            '/'
        );
    }

    public function driverName(): string
    {
        return 'bunnycdn';
    }

    public function upload(string $path, string $sourceFile): string
    {
        $content = file_get_contents($sourceFile);
        if ($content === false) {
            throw new \RuntimeException("Cannot read source file: {$sourceFile}");
        }
        $this->bunnyRequest('PUT', $path, $content);
        return $this->url($path);
    }

    public function delete(string $path): bool
    {
        $this->bunnyRequest('DELETE', $path);
        return true;
    }

    public function exists(string $path): bool
    {
        try {
            $this->bunnyRequest('HEAD', $path);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function url(string $path): string
    {
        return "{$this->cdnUrl}/{$path}";
    }

    public function list(string $prefix = ''): array
    {
        $dir = dirname($prefix);
        $json = $this->bunnyRequest('GET', $dir !== '.' ? $dir . '/' : '');
        $items = [];

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
            throw new \RuntimeException("BunnyCDN {$method} {$path} failed: HTTP {$httpCode} - {$error}");
        }

        if ($method === 'GET' && $response !== false && $response !== '') {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $response;
    }
}
