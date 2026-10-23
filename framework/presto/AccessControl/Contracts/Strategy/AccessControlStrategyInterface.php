<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Contracts\Strategy;

use PrestoWorld\AccessControl\Contracts\AccessControlInterface;
use PrestoWorld\AccessControl\Contracts\AccessControlRepositoryInterface;
use PrestoWorld\AccessControl\Contracts\PermissionManagerInterface;
use PrestoWorld\AccessControl\Contracts\RoleManagerInterface;

/**
 * Mỗi mô hình phân quyền (RBAC, ABAC, ACL, PBAC, DAC) triển khai interface này.
 * AccessControlManager giữ một strategy và delegate mọi can/crash về nó.
 */
interface AccessControlStrategyInterface extends AccessControlInterface, RoleManagerInterface, PermissionManagerInterface
{
    /**
     * Khởi tạo strategy từ container + repository.
     */
    public function initialize(): void;

    /**
     * Kiểm tra strategy đã sẵn sàng chưa.
     */
    public function isReady(): bool;

    /**
     * Tên mô hình phân quyền.
     */
    public function modelName(): string;

    /**
     * Inject repository lưu trữ dữ liệu phân quyền.
     */
    public function setRepository(AccessControlRepositoryInterface $repository): void;
}
