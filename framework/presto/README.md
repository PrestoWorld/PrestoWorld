# PrestoWorld Business Logic (`framework/presto/`)

`framework/presto/` là nơi chứa toàn bộ **business logic** của PrestoWorld, không bao gồm framework core. Đây là tầng trung gian giữa `App\` (project-specific) và `Witals\Framework\` (core framework).

## Cấu trúc

```
framework/presto/
├── Foundation/         — Config, service providers riêng của PrestoWorld
│   ├── Config/         —   Cấu hình mặc định
│   └── Providers/      —   Service providers
│
└── modules/            — Feature modules (manually loaded by ModuleManager)
    ├── Gutenberg/      —   Block renderer, theme.json, pattern registry
    ├── Schema/         —   Post types, repositories, state manager
    └── Search/         —   PW_Query, search engine integration
```

## Quy tắc

1. **Module tự quản**: mỗi module có `manifest.json`, `Module.php`, tự đăng ký routes/providers.
2. **Không phụ thuộc ngang**: module này không gọi trực tiếp module kia; dùng container binding hoặc events.
3. **Namespace**: `PrestoWorld\Modules\<ModuleName>\` map vào thư mục module.
4. **Test**: unit test cho business logic đặt trong `tests/` của project root.

## Khi nào thêm module mới

- Tính năng có logic riêng, có thể bật/tắt độc lập → tạo module mới trong `modules/`
- Tính năng quá nhỏ hoặc gắn với 1 controller cụ thể → viết trong `App\Http\`

## Khác biệt với `app/`

| `app/` (App\)                         | `framework/presto/` (PrestoWorld\)         |
|---------------------------------------|-------------------------------------------|
| Project-specific, không tái dùng được | Có thể tái dùng giữa các dự án PrestoWorld |
| Controllers, middleware, routes       | Feature modules, business services         |
| Phụ thuộc vào PrestoWorld + Witals    | Chỉ phụ thuộc vào Witals Framework         |
