<?php

declare(strict_types=1);

namespace Tests\Unit\Storage;

use App\Storage\BunnyCDNStorageProvider;
use App\Storage\GCSStorageProvider;
use App\Storage\S3SdkStorageProvider;
use App\Storage\S3StorageProvider;
use App\Storage\CloudStorageManager;
use App\Storage\StorageException;
use App\Storage\StorageProvider;
use PHPUnit\Framework\TestCase;

class StorageProviderTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        $this->tmpFile = sys_get_temp_dir() . '/storage_test_' . uniqid() . '.txt';
        file_put_contents($this->tmpFile, 'test content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function test_s3_provider_throws_on_empty_bucket(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('S3 bucket is required');
        new S3StorageProvider(['bucket' => '']);
    }

    public function test_s3_provider_throws_on_empty_credentials(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('S3 access key and secret key are required');
        new S3StorageProvider(['bucket' => 'my-bucket', 'access_key' => '', 'secret_key' => '']);
    }

    public function test_s3_provider_driver_name(): void
    {
        $p = new S3StorageProvider(['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's']);
        $this->assertSame('s3', $p->driverName());
    }

    public function test_s3_provider_custom_provider_name(): void
    {
        $p = new S3StorageProvider(['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's', 'provider' => 'backblaze']);
        $this->assertSame('backblaze', $p->driverName());
    }

    public function test_s3_provider_url(): void
    {
        $p = new S3StorageProvider(['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's']);
        $this->assertStringContainsString('/path/to/file', $p->url('path/to/file'));
    }

    public function test_s3_provider_url_with_cdn(): void
    {
        $p = new S3StorageProvider([
            'bucket' => 'b',
            'access_key' => 'k',
            'secret_key' => 's',
            'cdn_url' => 'https://cdn.example.com',
        ]);
        $this->assertSame('https://cdn.example.com/foo/bar', $p->url('foo/bar'));
    }

    public function test_s3_provider_upload_throws_on_missing_file(): void
    {
        $p = new S3StorageProvider(['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's']);
        $this->expectException(StorageException::class);
        $this->expectExceptionCode(StorageException::FILE_NOT_FOUND);
        $p->upload('test.txt', '/nonexistent/file.txt');
    }

    public function test_s3_provider_delete_throws_on_empty_path(): void
    {
        $p = new S3StorageProvider(['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's']);
        $this->expectException(\InvalidArgumentException::class);
        $p->delete('');
    }

    public function test_s3_provider_delete_throws_on_parent_ref(): void
    {
        $p = new S3StorageProvider(['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's']);
        $this->expectException(\InvalidArgumentException::class);
        $p->delete('path/../to/file');
    }

// BunnyCDN

    public function test_bunnycdn_provider_throws_on_empty_storage_zone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BunnyCDN storage zone is required');
        new BunnyCDNStorageProvider(['storage_zone' => '', 'api_key' => 'k']);
    }

    public function test_bunnycdn_provider_throws_on_empty_api_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BunnyCDN API key is required');
        new BunnyCDNStorageProvider(['storage_zone' => 'z', 'api_key' => '']);
    }

    public function test_bunnycdn_provider_driver_name(): void
    {
        $p = new BunnyCDNStorageProvider(['storage_zone' => 'z', 'api_key' => 'k']);
        $this->assertSame('bunnycdn', $p->driverName());
    }

    public function test_bunnycdn_provider_url(): void
    {
        $p = new BunnyCDNStorageProvider(['storage_zone' => 'z', 'api_key' => 'k']);
        $this->assertStringContainsString('/path/to/file', $p->url('path/to/file'));
    }

    public function test_bunnycdn_provider_upload_throws_on_missing_file(): void
    {
        $p = new BunnyCDNStorageProvider(['storage_zone' => 'z', 'api_key' => 'k']);
        $this->expectException(StorageException::class);
        $this->expectExceptionCode(StorageException::FILE_NOT_FOUND);
        $p->upload('test.txt', '/nonexistent/file.txt');
    }

    public function test_bunnycdn_provider_delete_throws_on_empty_path(): void
    {
        $p = new BunnyCDNStorageProvider(['storage_zone' => 'z', 'api_key' => 'k']);
        $this->expectException(\InvalidArgumentException::class);
        $p->delete('');
    }

    public function test_bunnycdn_provider_delete_throws_on_parent_ref(): void
    {
        $p = new BunnyCDNStorageProvider(['storage_zone' => 'z', 'api_key' => 'k']);
        $this->expectException(\InvalidArgumentException::class);
        $p->delete('path/../to/file');
    }

// GCS

    public function test_gcs_provider_throws_on_empty_bucket(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GCS bucket is required');
        new GCSStorageProvider(['bucket' => '', 'access_key' => 'k', 'secret_key' => 's']);
    }

    public function test_gcs_provider_throws_on_empty_credentials(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GCS HMAC access key and secret are required');
        new GCSStorageProvider(['bucket' => 'b', 'access_key' => '', 'secret_key' => '']);
    }

    public function test_gcs_provider_driver_name(): void
    {
        $p = new GCSStorageProvider(['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's']);
        $this->assertSame('gcs', $p->driverName());
    }

    public function test_gcs_provider_url(): void
    {
        $p = new GCSStorageProvider(['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's']);
        $this->assertStringContainsString('/path/to/file', $p->url('path/to/file'));
    }

    public function test_gcs_provider_upload_throws_on_missing_file(): void
    {
        $p = new GCSStorageProvider(['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's']);
        $this->expectException(StorageException::class);
        $this->expectExceptionCode(StorageException::FILE_NOT_FOUND);
        $p->upload('test.txt', '/nonexistent/file.txt');
    }

    public function test_gcs_provider_delete_throws_on_empty_path(): void
    {
        $p = new GCSStorageProvider(['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's']);
        $this->expectException(\InvalidArgumentException::class);
        $p->delete('');
    }

// S3 SDK

    public function test_s3_sdk_provider_is_available(): void
    {
        $this->assertFalse(S3SdkStorageProvider::isAvailable());
    }

    public function test_s3_sdk_provider_throws_on_empty_bucket(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('S3 bucket is required');
        new S3SdkStorageProvider(['bucket' => '']);
    }

    public function test_s3_sdk_provider_driver_name(): void
    {
        $p = new S3SdkStorageProvider(['bucket' => 'b']);
        $this->assertSame('s3', $p->driverName());
    }

    public function test_s3_sdk_provider_url(): void
    {
        $p = new S3SdkStorageProvider(['bucket' => 'b']);
        $this->assertStringContainsString('/path/to/file', $p->url('path/to/file'));
    }

    public function test_s3_sdk_provider_upload_throws_on_missing_file(): void
    {
        $p = new S3SdkStorageProvider(['bucket' => 'b']);
        $this->expectException(StorageException::class);
        $this->expectExceptionCode(StorageException::FILE_NOT_FOUND);
        $p->upload('test.txt', '/nonexistent/file.txt');
    }

    public function test_s3_sdk_provider_client_throws_without_sdk(): void
    {
        $p = new S3SdkStorageProvider(['bucket' => 'b']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AWS SDK not installed');
        $p->upload('test.txt', $this->tmpFile);
    }

// CloudStorageManager

    public function test_cloud_storage_manager_disabled_when_no_env(): void
    {
        $m = new CloudStorageManager();
        $this->assertFalse($m->isEnabled());
    }

// StorageException

    public function test_storage_exception_constants(): void
    {
        $this->assertSame(1, StorageException::FILE_NOT_FOUND);
        $this->assertSame(2, StorageException::UPLOAD_FAILED);
        $this->assertSame(3, StorageException::DELETE_FAILED);
        $this->assertSame(4, StorageException::CONNECTION_FAILED);
        $this->assertSame(5, StorageException::AUTHENTICATION_FAILED);
        $this->assertSame(6, StorageException::QUOTA_EXCEEDED);
    }

    public function test_storage_exception_factories(): void
    {
        $e = StorageException::fileNotFound('/path/file');
        $this->assertSame(StorageException::FILE_NOT_FOUND, $e->getCode());
        $this->assertStringContainsString('/path/file', $e->getMessage());

        $e = StorageException::uploadFailed('x', 'reason');
        $this->assertSame(StorageException::UPLOAD_FAILED, $e->getCode());
        $this->assertStringContainsString('reason', $e->getMessage());

        $e = StorageException::connectionFailed('s3', 'timeout');
        $this->assertSame(StorageException::CONNECTION_FAILED, $e->getCode());

        $e = StorageException::authenticationFailed('gcs');
        $this->assertSame(StorageException::AUTHENTICATION_FAILED, $e->getCode());
    }

// Interface compliance (all providers must implement StorageProvider)

    /** @return iterable<array{class-string<StorageProvider>, array<string, string>}> */
    public static function providerClasses(): iterable
    {
        yield 'S3' => [S3StorageProvider::class, ['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's']];
        yield 'BunnyCDN' => [BunnyCDNStorageProvider::class, ['storage_zone' => 'z', 'api_key' => 'k']];
        yield 'GCS' => [GCSStorageProvider::class, ['bucket' => 'b', 'access_key' => 'k', 'secret_key' => 's']];
        yield 'S3 SDK' => [S3SdkStorageProvider::class, ['bucket' => 'b']];
    }

    /** @dataProvider providerClasses */
    public function test_provider_implements_interface(string $class, array $config): void
    {
        $provider = new $class($config);
        $this->assertInstanceOf(StorageProvider::class, $provider);
    }

    /** @dataProvider providerClasses */
    public function test_provider_has_driver_name(string $class, array $config): void
    {
        $provider = new $class($config);
        $this->assertNotEmpty($provider->driverName());
    }

    /** @dataProvider providerClasses */
    public function test_provider_generates_url(string $class, array $config): void
    {
        $provider = new $class($config);
        $url = $provider->url('some/path/file.txt');
        $this->assertStringContainsString('some/path/file.txt', $url);
    }

    /** @dataProvider providerClasses */
    public function test_provider_throws_on_missing_file_upload(string $class, array $config): void
    {
        $provider = new $class($config);
        $this->expectException(StorageException::class);
        $this->expectExceptionCode(StorageException::FILE_NOT_FOUND);
        $provider->upload('test.txt', '/nonexistent/file.txt');
    }
}
