# Presto Admin Skin Manager

Hệ thống **Admin Skin Manager** cho phép tách biệt hoàn toàn logic nghiệp vụ của trang Admin khỏi lớp giao diện (UI). Bạn có thể thay đổi toàn bộ "bề ngoài" của trang Admin (từ cấu trúc HTML, CSS đến JavaScript) mà không cần can thiệp vào các Controller hay các lớp xử lý dữ liệu.

## 1. Kiến trúc tổng quan

Kiến trúc dựa trên 3 thành phần chính:
- **Contracts**: Các giao diện chuẩn (`SkinInterface`) nằm tại `PrestoWorld\Contracts\Admin`.
- **Skin Implementations**: Các lớp thực thi cụ thể cho từng loại giao diện (ví dụ: `WordPressSkin`, `PrestoSkin`).
- **Skin Manager**: Quản lý việc đăng ký và kích hoạt các Skin hiện có.

## 2. Các thành phần chính

### SkinInterface
Mọi Skin phải thực thi `PrestoWorld\Contracts\Admin\SkinInterface`. Giao diện này yêu cầu các phương thức:
- `getName()`: Trả về identifier duy nhất cho skin.
- `renderLayout($content, $args)`: Bao bọc nội dung vào khung giao diện chính (sidebar, header).
- `renderComponent($name, $props)`: Render một component nhỏ (table, card, button).
- `getAssets()`: Danh sách file CSS/JS cần thiết cho skin.

### Skin Manager
Dùng để quản lý các Skin trong ứng dụng:

```php
use PrestoWorld\Admin\UI\SkinManager;

$manager = new SkinManager();
$manager->registerSkin(new WordPressSkin($viewFactory));
$manager->registerSkin(new PrestoSkin($viewFactory));

// Chuyển đổi giao diện
$manager->setActiveSkin('wordpress-classic');
```

## 3. Sử dụng Template Engine (Stempler)

Giao diện không được viết cứng trong PHP mà phải thông qua hệ thống View của Witals sử dụng **Stempler**. 

Cấu trúc thư mục template khuyến nghị:
```text
templates/admin/skin-name/
├── layout.dark.php
└── components/
    ├── table.dark.php
    └── card.dark.php
```

### Ví dụ về Layout Template (`layout.dark.php`):
```html
<div class="admin-wrapper">
    <aside>${sidebar}</aside>
    <main>
        <h1>${title}</h1>
        <div class="content">${context}</div>
    </main>
</div>
```

## 4. Tương thích với WordPress Bridge

`wp-bridge` cung cấp các lớp như `WP_List_Table` đã được thiết kế để tự động tương thích với Skin Manager. Khi bạn gọi `$table->display()`, nó sẽ tự động dùng Skin đang active để render:

```php
$table = new UserListTable();
$table->set_skin($skinManager->getActiveSkin());
$table->prepare_items();
$table->display(); // Render ra HTML tương ứng với skin đang dùng
```

## 5. Hướng dẫn tạo Skin mới

1. **Implement Interface**: Tạo Class mới thực thi `SkinInterface`.
2. **Tạo Templates**: Tạo các file `.dark.php` trong thư mục template.
3. **Đăng ký**: Gọi `$skinManager->registerSkin()` trong một Service Provider.
4. **Cấu hình View Namespace**: Đảm bảo `ViewManager` đã được add đường dẫn tới template của skin mới.

---
*Tài liệu này thuộc hệ thống PrestoWorld Framework.*
