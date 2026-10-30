# PrestoWorld Access Control Core Feature

Hệ thống phân quyền dựa trên **Role-Based Access Control (RBAC)**, được thiết kế theo **pattern Strategy** để có thể chuyển đổi linh hoạt giữa các mô hình phân quyền khác nhau (ABAC, ACL, PBAC, DAC) thông qua plugin/module mà không cần sửa code core.

## Cấu trúc

```
framework/presto/AccessControl/
├── AccessControlManager.php          # Context/Strategy — quản lý mô hình phân quyền hiện hành
├── AccessControlServiceProvider.php  # Bind service, tạo schema và khởi tạo RBAC
├── schema.json                       # Khai báo các bảng Access Control
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

## Khởi tạo

Access Control là core feature và được đăng ký trực tiếp trong
`App\Foundation\Application`. Khi application khởi động, service provider đồng bộ
`schema.json` và tạo các role mặc định. Core RBAC chỉ tạo bốn bảng theo
`PW_TABLE_PREFIX` (mặc định `pw_`): `pw_acl_permissions`, `pw_acl_roles`,
`pw_acl_role_permissions` và `pw_acl_subject_roles`.
Không cần khai báo module hoặc chạy WP-CLI.

### Nguồn cấu hình table prefix

Access Control không tự đọc `getenv('PW_TABLE_PREFIX')`. Prefix được lấy qua
singleton `App\Config\ConfigManager::getTablePrefix()`, để schema sync,
repository RBAC và thao tác gán role dùng chung một nguồn cấu hình trong suốt
vòng đời application.

`ConfigManager` dùng `ConfigReaderRegistry` để hỗ trợ cả hai nguồn:

- Có `wp-config.php`: `WordPressConfigReader` lấy `$table_prefix` và ánh xạ
  sang `PW_TABLE_PREFIX`. Giá trị `PW_TABLE_PREFIX` được publish từ `.env`/
  process environment có thể override giá trị WordPress.
- Không có WordPress config: `EnvConfigReader` đọc `PW_TABLE_PREFIX` từ
  process environment, `$_ENV` hoặc `$_SERVER`.
- Nếu không nguồn nào khai báo prefix, dùng mặc định `pw_`.

Có thể đọc prefix trong code qua container dùng chung:

```php
$prefix = app(\App\Config\ConfigManager::class)->getTablePrefix();
```

Installer publish prefix được chọn vào process environment, rồi xóa cache của
config reader và config repository trước khi sync schema. Nhờ vậy singleton
đọc lại giá trị mới, thay vì giữ prefix đã cache trước lúc cài đặt. Những luồng
khởi tạo khác nên dùng `ConfigManager::getTablePrefix()` thay vì đọc
`getenv('PW_TABLE_PREFIX')` trực tiếp.

Các bảng Access Control không có prefix cũ (ví dụ `acl_roles`) được giữ nguyên;
core không tự chuyển dữ liệu. Sau khi đổi cấu hình prefix, cần chủ động chuyển
dữ liệu cũ sang các bảng có prefix trước khi dùng lại các role đã gán.

Sau khi cập nhật từ phiên bản từng discover Access Control như module, chạy
`php witals cache:clear --modules` một lần để xóa metadata discovery cũ.

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
| `root` | Quản trị hệ thống và BookPress | `*` (toàn quyền) |
| `administrator` | Quản trị viên | `*` (toàn quyền) |
| `editor` | Biên tập viên | posts, pages, media |
| `author` | Tác giả | bài viết của riêng mình |
| `subscriber` | Người đăng ký | đọc công khai |
| `customer` | Khách hàng | đơn hàng, hồ sơ của mình |

User đầu tiên được tạo trong quá trình cài đặt hoặc bằng lệnh seed tự động
được gán `root`. Có thể gán thủ công bằng
`access()->assignRole($userId, 'root')`. BookPress kiểm tra role này theo
`user_id` của token trong `<PW_TABLE_PREFIX>acl_subject_roles`; không dùng
`pw_users.role` để cấp quyền truy cập site.

Mỗi permission mới tạo trong RBAC cũng tự động được cấp cho role `root`.
Các bảng mở rộng dành cho module, feature và group không được tạo hoặc seed
theo mặc định.

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

- `composer.json` ánh xạ namespace `PrestoWorld\AccessControl\` tới `framework/presto/AccessControl/`.
- `phpstan.neon` đã được sửa `excludePaths` cho entry không tồn tại (thêm `?`).
- Chưa commit: hãy review trước khi push.
