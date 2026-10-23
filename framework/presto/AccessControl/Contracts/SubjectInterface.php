<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Contracts;

/**
 * Đối tượng được kiểm tra quyền (subject/user).
 */
interface SubjectInterface
{
    public function getId(): string;

    public function getIdentifier(): string;

    /**
     * @return array<string, mixed> Thuộc tính của subject dùng cho ABAC/PBAC.
     */
    public function getAttributes(): array;
}
