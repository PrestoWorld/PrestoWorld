# License Manager Module Documentation

Module này quản lý bản quyền phần mềm (License) cho hệ thống Optilarity, hỗ trợ cả license nội bộ và license từ bên thứ ba (Envato, TemplateMonster).

## 1. Tính năng chính

- **Hỗ trợ đa dạng loại License**: Optilarity (nội bộ), Envato, TemplateMonster.
- **Chế độ hoạt động**:
    - `Strict Mode`: Một license chỉ dùng cho 01 domain duy nhất (Domain Pairing).
    - `Share Mode`: Hỗ trợ kích hoạt trên nhiều domain dựa trên `max_activations`.
- **Hỗ trợ Membership**: Người dùng có Membership active có thể sử dụng phần mềm mà không cần License Key.
- **Bảo mật RSA**: Mã hóa dữ liệu truyền tải giữa Client và Server bằng cặp khóa RSA (Public/Private key).
- **Tự động đăng ký**: Tự động đồng bộ và đăng ký license từ Envato khi người dùng nhập Purchase Code hợp lệ.

---

## 2. API Endpoints

### 2.1 Xác thực License (Verify)
Đây là endpoint duy nhất client cần quan tâm. Client có thể gửi dữ liệu thô (raw) trong lần đầu tiên để lấy Public Key, sau đó sử dụng Public Key cho các lần verify tiếp theo để bảo mật dữ liệu (`payload`).

- **URL**: `/api/license/verify`
- **Method**: `POST` hoặc `GET`
- **Tham số (Plain/Post/Query)**:
    - `license_key`: Mã bản quyền.
    - `email`: Email người dùng (Bắt buộc).
    - `domain`: Domain đang sử dụng.
    - `version`: Phiên bản phần mềm hiện tại.

- **Dữ liệu thô gửi lên (Trường hợp muốn bảo mật payload)**:
Gửi lên một field `payload` là chuỗi JSON (đã encrypt bằng Public Key nhận được từ lần verify trước và base64 encode).

- **Response**:
Mọi phản hồi từ endpoint này đều bao gồm `public_key` của server để client sử dụng cho lần liên lạc tiếp theo.
```json
{
  "success": true,
  "payload": "...",
  "public_key": "-----BEGIN PUBLIC KEY-----\n...",
  "timestamp": 1740446400
}
```

Dữ liệu sau khi decrypt từ `payload`:
```json
{
  "valid": true,
  "license_type": "envato",
  "license_mode": "strict",
  "expires_at": "2026-12-31",
  "can_update": true,
  "message": "Successfully verified"
}
```

---

## 3. Quy trình xác thực (Logic)

1. **Ưu tiên Membership**: Nếu Email cung cấp có một Membership `active` trong hệ thống, hệ thống sẽ bỏ qua việc kiểm tra License Key và trả về kết quả hợp lệ với mode `unlimited`.
2. **Kiểm tra License nội bộ**: Tìm kiếm `license_key` trong database.
    - Kiểm tra `email` hợp lệ.
    - Kiểm tra trạng thái (`active`).
    - Kiểm tra ngày hết hạn (`expires_at`).
    - Kiểm tra Domain (`activated_domains`):
        - Nếu `Strict`: Phải trùng domain đầu tiên đã kích hoạt.
        - Nếu `Share`: Số lượng domain không vượt quá `max_activations`.
3. **Giải quyết qua bên thứ ba (Envato)**: Nếu không thấy key nội bộ:
    - Gọi API Envato để kiểm tra Purchase Code.
    - Nếu hợp lệ: Tự động lưu license vào database Optilarity, liên kết với Email và Domain của người dùng.
4. **Cập nhật dữ liệu**: Lưu lại `last_verified_at` và danh sách domain đã kích hoạt.

---

## 4. Quản trị (Dashboard)

Truy cập `/dashboard/licenses` để quản lý:
- Issue License mới thủ công.
- Thay đổi `max_activations` cho Share License.
- Thu hồi (`Revoke`) hoặc tạm ngưng (`Suspend`) license.
- Theo dõi danh sách các domain đã kích hoạt cho từng key.

---

## 5. Cấu hình hệ thống

- **RSA Keys**: Khóa Private Key sẽ được tạo duy nhất một lần cho mỗi license và lưu trực tiếp vào database (`optilarity_licenses.private_key`). Public Key sẽ được trích xuất (derive) từ Private Key và gửi về cho client trong mỗi lần phản hồi API Verify.
- **Envato API**: Cấu hình `ENVATO_API_TOKEN` trong file `.env` để sử dụng tính năng xác thực Envato.
