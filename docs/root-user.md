# Gán role root cho tài khoản BookPress

Role `root` thuộc Access Control, được lưu trong
`<PW_TABLE_PREFIX>acl_roles` và gán cho tài khoản qua
`<PW_TABLE_PREFIX>acl_subject_roles`. Không dùng `pw_users.role` để cấp quyền
root.

Access Control lấy prefix từ singleton `App\Config\ConfigManager`, tương thích
với `$table_prefix` trong `wp-config.php` và `PW_TABLE_PREFIX` từ môi trường.
Nếu không cấu hình, prefix mặc định là `pw_`. Vì vậy tên bảng thực tế ví dụ là
`pw_acl_subject_roles`; thay `pw_` theo prefix đang dùng khi chạy SQL thủ công.

Khi core Access Control khởi động, hệ thống tự tạo role `root` nếu chưa có,
với quyền `*`. Trong lần cài đặt đầu tiên, user đầu tiên trong `<prefix>users`
được tự động gán role `root`. Nếu dùng lệnh seed để tạo user đầu tiên, lệnh
cũng tự gán role này.

Để cấp quyền cho một tài khoản hiện có, lấy `id` của tài khoản trong
`<prefix>users` rồi gán role đó làm subject trong ACL. Subject phải khớp với
`user_id` trong token đăng nhập.

## Gán role root

Nên thực hiện qua Access Control API trong code ứng dụng:

```php
access()->assignRole((string) $userId, 'root');
```

Trong đó `$userId` phải là cùng giá trị với `user_id` trong token đăng nhập
(thông thường là `id` của bản ghi trong `pw_users`). API sẽ ghi assignment vào
`<PW_TABLE_PREFIX>acl_subject_roles`. Có thể xác minh role đã được gán:

```php
access()->getSubjectRoles((string) $userId); // ['root', ...]
access()->hasRole((string) $userId, 'root'); // true
```

Có thể kiểm tra bằng SQL:

```sql
SELECT subject, role, created_at
FROM pw_acl_subject_roles
WHERE subject = '<USER_ID>' AND role = 'root';
```

Hoặc gán trực tiếp trong SQL nếu cần:

```sql
INSERT INTO pw_acl_subject_roles (subject, role, created_at)
SELECT CAST(users.id AS CHAR), 'root', CURRENT_TIMESTAMP
FROM pw_users AS users
WHERE users.email = 'root@example.com'
  AND NOT EXISTS (
      SELECT 1
      FROM pw_acl_subject_roles
      WHERE subject = CAST(users.id AS CHAR)
        AND role = 'root'
  );
```

Nếu cấu hình prefix khác `pw_`, thay `pw_users` và `pw_acl_subject_roles` bằng
tên bảng tương ứng. Khi chạy trong ứng dụng, đọc prefix thống nhất bằng:

```php
$prefix = app(\App\Config\ConfigManager::class)->getTablePrefix();
```

Installer sẽ làm mới cache config sau khi lưu prefix mới để các dịch vụ đang
dùng singleton nhận cùng giá trị trước khi tạo schema và gán role.

## Gán permission cho user

RBAC không gán permission trực tiếp cho user. Cấp permission cho role, sau đó
gán role đó cho user. Ví dụ tạo role chuyên xuất bản sách:

```php
$access = access();
$userId = (string) $user['id'];

if ($access->getPermission('books.publish') === null) {
    $access->createPermission(
        'books.publish',
        'Publish books',
        'Cho phép xuất bản sách',
        'books',
    );
}

if ($access->getRole('book-publisher') === null) {
    $access->createRole('book-publisher', 'Book Publisher');
}

$access->grantPermission('book-publisher', 'books.publish');
$access->assignRole($userId, 'book-publisher');
$access->can($userId, 'books.publish'); // ['allowed' => true, ...]
```

Nếu quyền chỉ dành cho một user, tạo role riêng cho user đó thay vì dùng chung
`book-publisher`. Thu hồi permission bằng
`$access->revokePermission($role, 'books.publish')`; thu hồi role khỏi user bằng
`$access->unassignRole($userId, $role)`.

## Xác minh quyền BookPress

Sau khi gán role, đăng nhập bằng tài khoản đó và truy cập dashboard hoặc API
của BookPress site mà user không sở hữu. BookPress kiểm tra role `root` từ
Access Control theo subject `user_id` trong token; middleware tra ACL trực tiếp
nên việc gán role có hiệu lực mà không cần role claim trong token. Giá trị
`pw_users.role` không tự cấp quyền này.

Chỉ gán `root` cho tài khoản vận hành đáng tin cậy vì role này có quyền `*`
trong Access Control và bỏ qua kiểm tra ownership của BookPress site.
