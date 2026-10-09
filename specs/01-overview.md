# 01. Overview - Bối Cảnh & Cơ Hội

## 1.1 Vấn Đề Của WordPress Hiện Nay

### Sự Phân Mảnh Hệ Sinh Thái

**Cuộc chiến nội bộ (2024)**:
- WordPress.org (Mã nguồn mở) vs WordPress.com (Dịch vụ thương mại) vs WP Engine
- Vụ kiện giữa Automattic (Matt Mullenweg) và WP Engine (9/2024)
- Hệ quả: 159 nhân sự rời Automattic, chặn truy cập API cho WP Engine
- **Vấn đề pháp lý**: Matt Mullenweg gọi WP Engine là "ung thư của WordPress"

**Trình dựng trang (Page Builders)**:
- Gutenberg (Block Editor): UX thô, chưa đủ mượt
- Divi, Bricks, Oxygen: quá nhiều lựa chọn không tương thích
- Nếu đổi page builder → phải làm lại từ đầu

**Plugin/Theme ecosystem**:
- Hàng chục nghìn plugin cho cùng một tính năng
- Chất lượng không đồng đều, nhiều plugin bị bỏ hoang
- Mô hình Freemium: chi phí duy trì cao (~SaaS đóng)

### Vấn Đề Kỹ Thuật

**WordPress Core chậm chạp**:
- PHP-FPM Request-Response cycle lãng phí tài nguyên
- Mỗi request → boot lại toàn bộ PHP
- Không có persistent connections
- Global variables gây memory leaks

**Không có tiêu chuẩn**:
- Plugin/Theme không có manifest chuẩn
- Hooks (add_action/add_filter) lỏng lẻo, khó debug
- CSS/JS enqueue bừa bãi → load 50+ files cho 1 trang đơn giản

**node_modules nightmare**:
- Hàng chục nghìn file nhỏ → hết Inode trước khi hết dung lượng
- Dependency hell → 1 plugin lỗi → toàn hệ thống sập
- I/O overhead → copy/xóa thư mục tốn hàng chục phút

---

## 1.2 Cơ Hội Cho PrestoWorld

### Nhu Cầu Thị Trường

**Community disillusionment**:
- Người dùng lo ngại về sự độc quyền của Matt Mullenweg
- Hosting providers sợ bị đưa vào "danh sách đen"
- Developer chán nản với sự phân mảnh và chậm chạp

**Migration needs**:
- User muốn giữ plugins yêu thích nhưng chạy nhanh hơn
- Agency muốn quản lý nhiều site mà không tốn tài nguyên
- Developer muốn modern DX nhưng không muốn học React/Vue

### Định Vị PrestoWorld

**Mission Statement**:
> Build a WordPress-compatible runtime that is:
> - **Fast**: Sub-30ms response times
> - **Modern**: Built on Spiral Framework + RoadRunner
> - **Compatible**: Full support for wp.org plugins
> - **Freedom**: MIT license, community-driven

**Target Audience**:
1. **Developers**: Muốn PHP-first DX, không cần JS framework
2. **Agencies**: Quản lý nhiều sites hiệu quả cao
3. **Hosting Providers**: Cung cấp giải pháp "Presto-optimized"
4. **Enterprise**: Cần PostgreSQL, security, scalability

---

## 1.3 Triết Lý Thiết Kế

### "No-Overengineering" Principle

**Công nghệ cao phục vụ sự đơn giản**:
- Không dùng Virtual DOM khi PHP Template đủ tốt
- HTML delivered at the speed of Go
- Return to web roots: HTML + PHP Performance

**Ví dụ thực tế**:
- User không cần SPA phức tạp → chỉ cần web nhanh
- Developer không cần React → chỉ cần PHP controls
- Hosting không cần MySQL cho legacy → SQLite đủ tốt

### "Ease for Developer" Focus

**PHP-First Development**:
- Define widgets via PHP classes
- No JavaScript framework required
- Type-safe (PHP 8.3 + TypeScript TSX)
- Automatic Hook discovery

**Code-First Controls**:
```php
$widget->add_control(
    (new ColorControl('bg_color', 'Màu nền', '#ffffff'))
        ->withSettings(['alpha' => true])
);
```

### "Bridge, Not a Wall" Philosophy

**Gentle Migration**:
- Không ép user phải bỏ hết plugin cũ
- Cung cấp Sandbox cho wp.org plugins
- Dần dần hướng user sang Native Presto plugins

**Dual Database Strategy**:
- PostgreSQL cho Core (clean, fast)
- SQLite for Legacy (isolated, portable)

---

## 1.4 Tên Thương Hiệu & Định Vị

### PrestoWorld (PW)

**Ý nghĩa**:
- **Presto**: Tốc độ tức thì (nghịch lý với "Press" - in ấn chậm)
- **World**: Hệ sinh thái toàn cầu (nghịch lý với "Word" - chữ đơn lẻ)
- **PW**: Viết tắt đảo ngược của WP → đối trọng trực tiếp

**Slogan**:
> "The Power of WordPress, without the Press."

**Legal Safety**:
- Không dùng "WordPress" trong tên chính thức
- Gọi là "WordPress-Compatible Runtime"
- Clean-room implementation → MIT license hợp lệ

---

## 1.5 Mô Hình Kinh Doanh

### Self-Managed
- File binary/Docker image
- Deploy trên VPS bất kỳ
- Full control cho power users
- RoadRunner worker pool management

### SaaS
- Cloud-native deployment
- Zero-config auto-scaling
- Managed PostgreSQL
- Edge distribution via Cloudflare

**Pricing Strategy**:
- Self-managed: Free (Open Source)
- SaaS: Subscription based on resources (CPU/RAM)
- Enterprise: Custom solutions + support

---

## 1.6 Đối Thủ So Sánh

| Tiêu chí | WordPress Truyền Thống | PrestoWorld (PW) |
|----------|----------------------|------------------|
| **Runtime** | PHP-FPM (stateless) | RoadRunner + Traditional PHP-FPM (Witals) |
| **Framework** | Core (20-year legacy) | Witals (kế thừa Spiral + Cycle ORM) |
| **Database** | MySQL only | PostgreSQL + SQLite |
| **Frontend** | Gutenberg/React | Gutenberg Fork + SolidJS Dashboard |
| **Plugin System** | Hooks (loose) | Events + IoC (strict) |
| **Performance** | 500ms+ TTFB | <30ms TTFB |
| **Inode Usage** | Hàng chục nghìn files | <1000 files |
| **License** | GPLv2 | MIT |
| **Marketplace** | wp.org only | wp.org + Native Store |

---

## 1.7 Thành Công Cần Thể Đo Lường

### Technical Metrics
- **Response Time**: <30ms (P50), <50ms (P95)
- **Core Web Vitals**: LCP <2.5s, INP <200ms, CLS <0.1
- **Concurrent Users**: 10,000+ on single VPS (RoadRunner)
- **Memory Usage**: <512MB for 100+ plugins (SQLite + lazy-load)

### Business Metrics
- **Migration Time**: <5 minutes from WordPress to PrestoWorld
- **Plugin Compatibility**: >95% of top 1000 wp.org plugins
- **User Satisfaction**: >4.5/5 stars
- **Churn Rate**: <5% (giảm nhờ classic dashboard switch)

### Community Metrics
- **GitHub Stars**: 10,000+ (launch)
- **Contributors**: 100+ (Year 1)
- **Forum Activity**: 1000+ posts/month
- **Native Plugins**: 50+ (Year 1)
