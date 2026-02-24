# Reminder Manager Module

Module này chịu trách nhiệm quét và gửi thông báo nhắc nhở về việc hết hạn dịch vụ và thời hạn bảo hành.

## Các loại nhắc nhở
- **License Key**: Nhắc nhở trước 7 ngày khi bản quyền hết hạn.
- **Tên miền (Domain)**: Nhắc nhở trước 30 ngày.
- **Hosting**: Nhắc nhở trước 15 ngày.
- **Web Services**: Nhắc nhở trước 3 ngày khi **thời hạn bảo hành** kết thúc.

## Kênh thông báo
Hệ thống sẽ tự động gửi thông báo qua các kênh mà người dùng đã thiết lập:
1.  **Email**: Kênh mặc định nếu người dùng chưa cấu hình gì thêm.
2.  **Telegram**: Gửi qua Chat ID đã kết nối.
3.  **SMS**: Gửi qua số điện thoại đã xác thực.

## Cơ chế chống lặp (Anti-Spam)
Để tránh làm phiền người dùng, mỗi sự kiện nhắc nhở (ví dụ: một tên miền cụ thể sắp hết hạn) sẽ chỉ được gửi tối đa **một lần mỗi 3 ngày**.

## Quản lý cấu hình thông báo
Người dùng có thể quản lý kênh nhận thông báo tại bảng `optilarity_customer_notification_settings`.

## Lệnh CLI (Automation)
Sử dụng lệnh sau để thực hiện quét và gửi thông báo (Nên thiết lập Cronjob hàng ngày):
```bash
php witals reminders:scan
```

## Cấu trúc dữ liệu
- `optilarity_reminder_logs`: Lưu lịch sử các nhắc nhở đã gửi.
- `optilarity_customer_notification_settings`: Lưu cấu hình kênh nhận tin của khách hàng.
