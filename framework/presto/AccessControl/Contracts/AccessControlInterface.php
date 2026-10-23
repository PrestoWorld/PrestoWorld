<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Contracts;

/**
 * Mô hình phân quyền cơ bản. Mỗi mô hình (RBAC, ABAC, ACL, PBAC, DAC)
 * triển khai interface này. AccessControlManager giữ một strategy instance
 * và có thể hot-swap giữa các mô hình khi cần.
 */
interface AccessControlInterface
{
    /**
     * Kiểm tra subject (user) có quyền action trên resource hay không.
     *
     * @param array<string, mixed> $context Thông tin ngữ cảnh (request, env, …)
     * @return array{allowed: bool, reason?: string, model?: string}
     */
    public function can(string $subject, string $action, mixed $resource = null, array $context = []): array;

    /**
     * Lấy danh sách action mà subject được phép thực hiện trên resource.
     *
     * @param array<string, mixed> $context
     * @return array<string, bool> Map action => allowed
     */
    public function actions(string $subject, mixed $resource = null, array $context = []): array;

    /**
     * Tên mô hình phân quyền (rbac, abac, acl, pbac, dac).
     */
    public function model(): string;

    /**
     * Version của mô hình.
     */
    public function version(): string;
}
