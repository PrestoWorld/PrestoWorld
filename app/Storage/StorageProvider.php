<?php

declare(strict_types=1);

namespace App\Storage;

interface StorageProvider
{
    public function upload(string $path, string $sourceFile): string;

    public function delete(string $path): bool;

    public function exists(string $path): bool;

    public function url(string $path): string;

    /**
     * @return array<array{path: string, size: int, mtime: int}>
     */
    public function list(string $prefix = ''): array;

    public function driverName(): string;
}
