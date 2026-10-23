<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Contracts;

/**
 * Đối tượng tài nguyên được kiểm tra.
 */
interface ResourceInterface
{
    public function getId(): string;

    public function getType(): string;

    /**
     * @return array<string, mixed> Thuộc tính của resource dùng cho ABAC/PBAC.
     */
    public function getAttributes(): array;
}
