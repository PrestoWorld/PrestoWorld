# Witals Architecture: Base Modules & Core Framework

Tài liệu này hướng dẫn cách sử dụng các thành phần cốt lõi (Base Modules) và kiến trúc Framework để phát triển các tính năng (Implemented Modules) một cách nhanh chóng, đồng bộ và hiệu năng cao.

---

## 🏗️ Tổng quan Kiến trúc

Hệ thống được chia làm 3 lớp chính:
1.  **Core Framework (`vendor/witals/framework`)**: Chứa logic xử lý cốt lõi, Interface và Traits. Đây là "bộ não" của hệ thống.
2.  **PrestoWorld Framework (`presto/`)**: Chứa logic nghiệp vụ dùng chung cấp cao như Ecommerce, Payment, Admin UI.
3.  **Based Modules (`modules/Ecommerce`, `modules/Shared...`)**: Chứa các bảng cơ sở dữ liệu (Database Schemas) dùng chung. Dạng "Internal", không có UI/Actions riêng.
4.  **Implemented Modules (`modules/WebsiteTemplates`, `modules/Hosting`...)**: Các module trực tiếp cung cấp tính năng cho người dùng, kế thừa và sử dụng các tính năng từ các lớp trên.

---

## 📦 1. Multilingual CRUD System

Cung cấp khả năng quản lý dữ liệu đa ngôn ngữ với hiệu suất cao (O(1)) bằng cách lưu trữ các bản dịch trong các cột JSON.

### Cách sử dụng:
Trong Controller của bạn, hãy kế thừa `CrudController` và khai báo các trường cần dịch:

```php
namespace Modules\MyModule\Controllers;

use Witals\Framework\Database\Crud\CrudController;

class MyController extends CrudController
{
    protected string $table = 'my_table_name';
    protected array $translatableFields = ['name', 'description']; // Các cột JSON chứa đa ngôn ngữ
}
```

**Tính năng tự động:**
- `index()`: Trả về danh sách dữ liệu đã được tự động dịch theo ngôn ngữ hiện tại (`locale`).
- `show($id)`: Trả về một bản ghi duy nhất đã được dịch.

---

## 🔍 2. SEO Module

Chuẩn hóa việc quản lý Meta Tags (Title, Description, OpenGraph) cho toàn bộ website.

### Cách sử dụng:
Thêm `isSeoable = true` vào Controller để tự động xử lý metadata:

```php
class MyController extends CrudController
{
    protected bool $isSeoable = true;
    
    // Hệ thống sẽ tự tìm cột 'seo_metadata' trong bảng để render.
}
```

**Sử dụng thủ công:**
Bạn có thể sử dụng `SeoManager` (alias: `seo`) trong code:
```php
app('seo')->set([
    'title' => 'Trang chủ',
    'description' => 'Mô tả trang web'
]);
```

---

## 💳 3. Ecommerce & Payment System

Module `Ecommerce` dựa trên `Payment System` (Omnipay) của PrestoWorld để xử lý thanh toán và quản lý đơn hàng tập trung.

### Cấu trúc dữ liệu:
Tất cả các module bán hàng đều dùng chung bảng tại `modules/Ecommerce/schema.json`:
- `orders`: Lưu trữ đơn hàng.
- `invoices`: Lưu trữ hóa đơn.

### Đăng ký Module với Ecommerce:
Để một module có thể bán được (ví dụ: Templates), bạn cần đăng ký nó vào `ecommerce.registry` trong `ServiceProvider`:

```php
public function boot(): void
{
    if ($this->app->has('ecommerce.registry')) {
        $this->app->make('ecommerce.registry')->register('template', TemplateController::class, [
            'name' => 'Website Templates',
            'icon' => 'layout'
        ]);
    }
}
```

### Xử lý hành động mua hàng:
Sử dụng `HasPurchaseAction` trait trong Controller để có ngay phương thức `purchase()` chuẩn hóa:

```php
use PrestoWorld\Ecommerce\Traits\HasPurchaseAction;

class MyController extends CrudController
{
    use HasPurchaseAction;
    
    protected string $buyableType = 'template'; // Định danh loại sản phẩm

    protected function resolveItem(string $id) {
        // Trả về đối tượng implement BuyableInterface
    }
}
```

---

## 🏦 4. Payment Gateway (Omnipay Integration)

Hệ thống thanh toán được xây dựng trên top của `league/omnipay`, hỗ trợ PayPal và Stripe.

### Cách gọi thanh toán:
```php
$gateway = app('payment')->gateway('paypal');
$response = $gateway->purchase([
    'amount' => '10.00',
    'currency' => 'USD',
    'returnUrl' => '...',
    'cancelUrl' => '...',
])->send();

if ($response->isRedirect()) {
    $response->redirect();
}
```

---

## 🛠️ Quy trình tạo Module mới sử dụng Base System

1.  **Tạo Module**: `php witals make:module MyFeature`.
2.  **Kế thừa CRUD**: Chỉnh sửa Controller để kế thừa `CrudController`.
3.  **Thêm SEO/Buy Action**: Thêm các Traits tương ứng theo nhu cầu.
4.  **Đăng ký Registry**: Nếu là sản phẩm bán được, đăng ký trong `ServiceProvider`.
5.  **Sync Schema**: Chạy `php witals schema:sync` để cập nhật database từ các schema tập trung.

---
*Tài liệu này được cập nhật vào: 2026-02-25 (PrestoWorld Refactor)*
