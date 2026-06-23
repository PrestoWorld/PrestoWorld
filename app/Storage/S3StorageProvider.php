<?php

declare(strict_types=1);

namespace App\Storage;

class S3StorageProvider implements StorageProvider
{
    private string $bucket;
    private string $region;
    private string $accessKey;
    private string $secretKey;
    private string $endpoint;
    private string $cdnUrl;
    private string $provider;

    public function __construct(array $config)
    {
        $this->bucket = $config['bucket'] ?? '';
        $this->region = $config['region'] ?? 'us-east-1';
        $this->accessKey = $config['access_key'] ?? '';
        $this->secretKey = $config['secret_key'] ?? '';
        $this->endpoint = rtrim($config['endpoint'] ?? "https://{$this->bucket}.s3.{$this->region}.amazonaws.com", '/');
        $this->cdnUrl = rtrim($config['cdn_url'] ?? $this->endpoint, '/');
        $this->provider = $config['provider'] ?? 's3';
    }

    public function driverName(): string
    {
        return $this->provider;
    }

    public function upload(string $path, string $sourceFile): string
    {
        $content = file_get_contents($sourceFile);
        if ($content === false) {
            throw new \RuntimeException("Cannot read source file: {$sourceFile}");
        }
        $mime = mime_content_type($sourceFile) ?: 'application/octet-stream';
        $this->s3Request('PUT', $path, $content, [
            'Content-Type' => $mime,
            'x-amz-acl' => 'public-read',
        ]);
        return $this->url($path);
    }

    public function delete(string $path): bool
    {
        $this->s3Request('DELETE', $path);
        return true;
    }

    public function exists(string $path): bool
    {
        try {
            $this->s3Request('HEAD', $path);
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
        $query = http_build_query(['prefix' => $prefix, 'max-keys' => 1000]);
        $xml = $this->s3Request('GET', '', '', [], "?{$query}");
        $items = [];

        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('s3', 'http://s3.amazonaws.com/doc/2006-03-01/');

        foreach ($xpath->query('//s3:Contents') as $node) {
            $key = $xpath->query('s3:Key', $node)->item(0)?->textContent ?? '';
            $size = (int) ($xpath->query('s3:Size', $node)->item(0)?->textContent ?? 0);
            $mtime = strtotime($xpath->query('s3:LastModified', $node)->item(0)?->textContent ?? '');

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

    private function s3Request(string $method, string $path, string $body = '', array $headers = [], string $query = ''): string
    {
        $path = ltrim($path, '/');
        $url = "{$this->endpoint}/{$path}{$query}";

        $now = gmdate('Ymd\THis\Z');
        $date = substr($now, 0, 8);

        $defaultHeaders = [
            'Host' => parse_url($this->endpoint, PHP_URL_HOST),
            'x-amz-date' => $now,
            'x-amz-content-sha256' => $method === 'GET' || $method === 'DELETE' ? hash('sha256', '') : hash('sha256', $body),
        ];

        if ($method === 'PUT' && !isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/octet-stream';
        }

        $allHeaders = array_merge($defaultHeaders, $headers);
        ksort($allHeaders);

        // Canonical request
        $signedHeaders = implode(';', array_map('strtolower', array_keys($allHeaders)));
        $canonicalHeaders = '';
        foreach ($allHeaders as $k => $v) {
            $canonicalHeaders .= strtolower($k) . ':' . $v . "\n";
        }

        $uri = '/' . $path;
        $canonicalRequest = implode("\n", [
            $method,
            $uri,
            ltrim($query, '?'),
            $canonicalHeaders,
            $signedHeaders,
            $allHeaders['x-amz-content-sha256'],
        ]);

        // String to sign
        $scope = "{$date}/{$this->region}/s3/aws4_request";
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $now,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        // Signature
        $signingKey = $this->getSigningKey($this->secretKey, $date, $this->region, 's3');
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        $authorization = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $this->buildCurlHeaders($allHeaders, $authorization),
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
            throw new \RuntimeException("S3 {$method} {$path} failed: HTTP {$httpCode} - {$error}");
        }

        return $response !== false ? $response : '';
    }

    private function buildCurlHeaders(array $headers, string $authorization): array
    {
        $result = ["Authorization: {$authorization}"];
        foreach ($headers as $k => $v) {
            $result[] = "{$k}: {$v}";
        }
        return $result;
    }

    private function getSigningKey(string $key, string $date, string $region, string $service): string
    {
        $kDate = hash_hmac('sha256', $date, "AWS4{$key}", true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }
}
