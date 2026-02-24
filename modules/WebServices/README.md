# Web Services Module

Module này cung cấp các dịch vụ kỹ thuật cao cấp cho website, bao gồm bảo trì, tối ưu hóa và chuyển đổi nền tảng.

## Các dịch vụ cung cấp
- **Bảo trì Website**: Cập nhật định kỳ, kiểm tra lỗi và tối ưu database.
- **Tăng tốc Web & Google PageSpeed**: Tối ưu hóa hiệu suất, đạt điểm số cao trên các công cụ đo lường.
- **Diệt Virus & Mã độc**: Quét và làm sạch website bị tấn công, tăng cường bảo mật.
- **Chuyển đổi Vibe Code/Web AI**: Chuyển đổi mã nguồn từ các nền tảng AI/Vibe Code sang WordPress hoặc Laravel chuẩn.

## Tính năng chính
- **Quản lý thời hạn bảo hành**: Mỗi dịch vụ đi kèm với một khoảng thời hạn bảo hành (ví dụ: 30, 60, 90 ngày).
- **Liên kết Order & Invoice**: Các yêu cầu dịch vụ được liên kết trực tiếp với hệ thống đơn hàng và hóa đơn của Optilarity.
- **Định danh bằng Website URL**: Theo dõi dịch vụ theo từng tên miền cụ thể của khách hàng.

## API Endpoints
| Endpoint | Method | Mô tả |
| :--- | :--- | :--- |
| `/api/web-services` | `GET` | Liệt kê danh sách dịch vụ đang hoạt động. |
| `/api/web-services/{slug}` | `GET` | Xem chi tiết một dịch vụ. |
| `/api/web-services/request` | `POST` | Gửi yêu cầu dịch vụ (Email, Service Slug, Website URL, Notes). |
| `/api/web-services/seed` | `GET` | Khởi tạo dữ liệu mẫu cho danh mục dịch vụ. |

## Cấu trúc dữ liệu (Schema)
- `optilarity_web_services`: Lưu trữ danh mục dịch vụ.
- `optilarity_web_service_items`: Lưu trữ các gói dịch vụ khách hàng đã mua, bao gồm ngày hết hạn bảo hành và thông tin đơn hàng.
