# PrestoWorld Access Control Module

Hệ thống phân quyền dựa trên **Role-Based Access Control (RBAC)**, được thiết kế theo **pattern Strategy** để có thể chuyển đổi linh hoạt giữa các mô hình phân quyền khác nhau (ABAC, ACL, PBAC, DAC) thông qua plugin/module mà không cần sửa code core.

## Cấu trúc

```
framework/presto/AccessControl/
├── AccessControlManager.php          # Context/Strategy — quản lý mô hình phân quyền hiện hành
├── Module.php                        # Witals Module (auto-discover qua manifest.json)
├── manifest.json                     # Khai báo module, phụ thuộc admin
├── schema.json                       # 4 bảng RBAC (declarative schema)
├── helpers.php                       # access() helper global
├── Middleware/AccessCanMiddleware.php # Middleware bảo vệ route/controller
├── Contracts/
│   ├── AccessControlInterface.php    # can(), actions(), model(), version()
│   ├── AccessControlRepositoryInterface.php
│   ├── RoleManagerInterface.php
│   ├── PermissionManagerInterface.php
│   ├── SubjectInterface.php
│   ├── ResourceInterface.php
│   └── Strategy/
│       └── AccessControlStrategyInterface.php
├── Rbac/RbacStrategy.php             # Triển khai RBAC mặc định
├── Repository/DatabaseAccessControlRepository.php
└── Exception/
    ├── AccessControlException.php
    ├── InvalidModelException.php
    ├── RoleNotFoundException.php
    ├── PermissionNotFoundException.php
    └── SubjectNotFoundException.php
```

## Kiến trúc

### Strategy Pattern

`AccessControlManager` giữ một `AccessControlStrategyInterface` (mô hình phân quyền hiện hành) và delegate mọi yêu cầu `can()` / `actions()` về nó. Có thể **hot-swap** giữa các mô hình tại runtime:

```php
// RBAC (mặc định)
access()->switchModel('rbac');

// Chuyển sang ABAC (nếu plugin ABAC đã đăng ký)
access()->switchModel('abac');
```

Mỗi mô hình mới chỉ cần implement `AccessControlStrategyInterface` và đăng ký qua `registerModel('abac', AbacStrategy::class)`.

### RBAC (Role-Based Access Control)

Quyền được gán cho **Role**, Role được gán cho **User (subject)**.

```
User ──(assigned)──▶ Role ──(granted)──▶ Permission
```

Kiểm tra: user có role nào, role đó có quyền `action` trên `resource` không.

## Cài đặt

Module được auto-discover từ `framework/presto/AccessControl/manifest.json`. Đảm bảo module `admin` đã bật (là dependency).

## API chính

### Kiểm tra quyền

```php
$result = access()->can('user-123', 'posts.edit', $post);
// ['allowed' => true, 'reason' => "Được phép qua role 'editor'", 'model' => 'rbac']

if ($result['allowed']) {
    // cho phép
}
```

### Quản lý Role / Permission

```php
access()->createRole('moderator', 'Moderator', ['posts.edit', 'posts.delete']);
access()->assignRole('user-123', 'moderator');
access()->grantPermission('moderator', 'posts.publish');
access()->getSubjectRoles('user-123'); // ['moderator']
access()->hasRole('user-123', 'moderator'); // true
```

### Middleware bảo vệ route

```php
$router->middleware('access.can:posts.edit', AccessCanMiddleware::class);
```

Hoặc áp dụng cho route cụ thể với resource expression:

```php
new AccessCanMiddleware($app, 'posts.edit', 'post:{id}');
```

## Role mặc định (seed)

| Role | Mô tả | Quyền |
|------|-------|-------|
| `administrator` | Quản trị viên | `*` (toàn quyền) |
| `editor` | Biên tập viên | posts, pages, media |
| `author` | Tác giả | bài viết của riêng mình |
| `subscriber` | Người đăng ký | đọc công khai |
| `customer` | Khách hàng | đơn hàng, hồ sơ của mình |

## Chuyển đổi mô hình phân quyền

Vì thiết kế tách biệt Strategy, bạn có thể viết plugin/module mới để triển khai:

- **ABAC** (Attribute-Based Access Control): kiểm tra thuộc tính subject/resource/env
- **ACL** (Access Control List): gán quyền trực tiếp cho subject
- **PBAC** (Policy-Based Access Control): quy định dựa trên policy
- **DAC** (Discretionary Access Control): chủ sở hữu resource phân quyền

Plugin mới đăng ký model:

```php
$manager->registerModel('abac', \Acme\Abac\AbacStrategy::class);
access()->switchModel('abac');
```

## Testing

```bash
php vendor/bin/phpunit tests/Unit/AccessControl/RbacStrategyTest.php
```

## Lưu ý

- `composer.json` đã được thêm PSR-4 mapping `PrestoWorld\AccessControl\` → `framework/presto/AccessControl/`.
- `phpstan.neon` đã được sửa `excludePaths` cho entry không tồn tại (thêm `?`).
- Chưa commit: hãy review trước khi push.
