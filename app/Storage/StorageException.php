<?php

declare(strict_types=1);

namespace App\Storage;

class StorageException extends \RuntimeException
{
    public const FILE_NOT_FOUND = 1;
    public const UPLOAD_FAILED = 2;
    public const DELETE_FAILED = 3;
    public const CONNECTION_FAILED = 4;
    public const AUTHENTICATION_FAILED = 5;
    public const QUOTA_EXCEEDED = 6;

    public static function fileNotFound(string $path): self
    {
        return new self("File not found: {$path}", self::FILE_NOT_FOUND);
    }

    public static function uploadFailed(string $path, string $reason): self
    {
        return new self("Upload failed for {$path}: {$reason}", self::UPLOAD_FAILED);
    }

    public static function connectionFailed(string $driver, string $reason): self
    {
        return new self("Connection to {$driver} failed: {$reason}", self::CONNECTION_FAILED);
    }

    public static function authenticationFailed(string $driver): self
    {
        return new self("Authentication failed for {$driver}", self::AUTHENTICATION_FAILED);
    }
}
