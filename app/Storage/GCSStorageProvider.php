<?php

declare(strict_types=1);

namespace App\Storage;

class GCSStorageProvider implements StorageProvider
{
    private string $bucket;
    private string $accessKey;
    private string $secretKey;
    private string $endpoint;
    private string $cdnUrl;

    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 200;

    public function __construct(array $config)
    {
        $this->bucket = $config['bucket'] ?? '';
        $this->accessKey = $config['access_key'] ?? '';
        $this->secretKey = $config['secret_key'] ?? '';
        $this->cdnUrl = rtrim($config['cdn_url'] ?? "https://storage.googleapis.com/{$this->bucket}", '/');
        $this->endpoint = rtrim(
            $config['endpoint'] ?? "https://storage.googleapis.com/{$this->bucket}",
            '/'
        );

        if ($this->bucket === '') {
            throw new \InvalidArgumentException('GCS bucket is required');
        }
        if ($this->accessKey === '' || $this->secretKey === '') {
            throw new \InvalidArgumentException('GCS HMAC access key and secret are required');
        }
    }

    public function driverName(): string
    {
        return 'gcs';
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

        $mime = mime_content_type($sourceFile) ?: 'application/octet-stream';
        $this->gcsRequest('PUT', $path, $content, $mime);
        return $this->url($path);
    }

    public function delete(string $path): bool
    {
        $this->validatePath($path);
        $this->gcsRequest('DELETE', $path);
        return true;
    }

    public function exists(string $path): bool
    {
        $this->validatePath($path);
        try {
            $this->gcsRequest('HEAD', $path);
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
        $query = http_build_query(['prefix' => $prefix]);
        $xml = $this->gcsRequest('GET', '', '', '', "?{$query}");
        $items = [];

        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('gcs', 'http://doc.s3.amazonaws.com/2006-03-01/');

        foreach ($xpath->query('//gcs:Contents') as $node) {
            $key = $xpath->query('gcs:Key', $node)->item(0)?->textContent ?? '';
            $size = (int) ($xpath->query('gcs:Size', $node)->item(0)?->textContent ?? 0);
            $mtime = strtotime($xpath->query('gcs:LastModified', $node)->item(0)?->textContent ?? '');

            if ($key !== '' && !str_ends_with($key, '/')) {
                $items[] = [
                    'path' => $key,
                    'size' => $size,
                    'mtime' => $mtime ?: 0,
                ];
            }
        }

        return $items;
    }

    private function gcsRequest(string $method, string $path, string $body = '', string $contentType = '', string $query = ''): string
    {
        $attempt = 0;

        while (true) {
            $attempt++;
            try {
                return $this->doGcsRequest($method, $path, $body, $contentType, $query);
            } catch (\Throwable $e) {
                if ($attempt >= self::MAX_RETRIES || $this->isNonRetryable($e)) {
                    throw $e;
                }
                usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
            }
        }
    }

    private function doGcsRequest(string $method, string $path, string $body = '', string $contentType = '', string $query = ''): string
    {
        $path = ltrim($path, '/');
        $url = "{$this->endpoint}/{$path}{$query}";

        $headers = [
            'Host' => parse_url($this->endpoint, PHP_URL_HOST) ?: throw StorageException::connectionFailed('gcs', 'Invalid endpoint URL'),
            'Date' => gmdate('D, d M Y H:i:s \G\M\T'),
            'Content-Type' => $contentType ?: 'application/octet-stream',
        ];

        if ($method === 'PUT' && $body !== '') {
            $headers['Content-Length'] = (string) strlen($body);
            $headers['Content-MD5'] = base64_encode(md5($body, true));
        }

        $canonicalHeaders = '';
        foreach (['Content-MD5', 'Content-Type', 'Date'] as $h) {
            $canonicalHeaders .= ($headers[$h] ?? '') . "\n";
        }

        $canonicalExtension = '';
        $stringToSign = "{$method}\n{$canonicalHeaders}{$canonicalExtension}/{$this->bucket}/{$path}";

        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true));
        $headers['Authorization'] = "GOOG1 {$this->accessKey}:{$signature}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $this->buildHeaders($headers),
        ]);

        if ($method === 'PUT' && $body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
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
            if ($httpCode === 403) {
                throw StorageException::authenticationFailed('GCS');
            }
            throw new StorageException("GCS {$method} {$path} failed: {$reason}", $httpCode);
        }

        return $response !== false ? $response : '';
    }

    private function isNonRetryable(\Throwable $e): bool
    {
        if ($e instanceof StorageException) {
            return in_array($e->getCode(), [400, 401, 403, 404, 405], true);
        }
        return false;
    }

    private function buildHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $k => $v) {
            $result[] = "{$k}: {$v}";
        }
        return $result;
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
