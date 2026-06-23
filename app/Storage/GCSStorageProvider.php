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
    }

    public function driverName(): string
    {
        return 'gcs';
    }

    public function upload(string $path, string $sourceFile): string
    {
        $content = file_get_contents($sourceFile);
        if ($content === false) {
            throw new \RuntimeException("Cannot read source file: {$sourceFile}");
        }
        $mime = mime_content_type($sourceFile) ?: 'application/octet-stream';
        $this->gcsRequest('PUT', $path, $content, $mime);
        return $this->url($path);
    }

    public function delete(string $path): bool
    {
        $this->gcsRequest('DELETE', $path);
        return true;
    }

    public function exists(string $path): bool
    {
        try {
            $this->gcsRequest('HEAD', $path);
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
        $path = ltrim($path, '/');
        $url = "{$this->endpoint}/{$path}{$query}";

        $headers = [
            'Host' => parse_url($this->endpoint, PHP_URL_HOST),
            'Date' => gmdate('D, d M Y H:i:s \G\M\T'),
            'Content-Type' => $contentType ?: 'application/octet-stream',
        ];

        if ($method === 'PUT' && $body !== '') {
            $headers['Content-Length'] = (string) strlen($body);
            $headers['Content-MD5'] = base64_encode(md5($body, true));
        }

        // Google Cloud Storage HMAC signature (signed by header)
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
            throw new \RuntimeException("GCS {$method} {$path} failed: HTTP {$httpCode} - {$error}");
        }

        return $response !== false ? $response : '';
    }

    private function buildHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $k => $v) {
            $result[] = "{$k}: {$v}";
        }
        return $result;
    }
}
