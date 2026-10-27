# Buffet Station – Website bán vé buffet

Website bán vé buffet bằng **PHP thuần + SQLite** (CSDL mô phỏng, không cần cài MySQL).

## Bảng giá
| Sản phẩm | Giá |
|---|---|
| Buffet Cơ Bản | 169.000₫ |
| Buffet Tiêu Chuẩn | 199.000₫ |
| Buffet Cao Cấp | 259.000₫ |
| Buffet VIP | 399.000₫ |
| Buffet Nước Uống (add-on) | 39.000₫ |

## Chạy thử
Yêu cầu: PHP 8.0+ (có extension `pdo_sqlite`, mặc định đã bật).

```bash
cd buffet
php -S localhost:8000
```
Mở http://localhost:8000. Lần chạy đầu tiên, file CSDL `database/buffet.sqlite` được **tự tạo** cùng dữ liệu mẫu.
Dùng XAMPP/Laragon: copy thư mục `buffet` vào `htdocs`/`www` rồi mở `http://localhost/buffet`.

## Tài khoản demo
- Admin: `admin@buffet.vn` / `admin123`
- Khách: `khach@demo.vn` / `123456`

## Tính năng
**Khách hàng:** trang chủ & bảng giá, đăng ký, đăng nhập, đặt vé (chọn số lượng, ngày, khung giờ, tính tiền trực tiếp), mã đơn tự sinh (VD `BF261015CE0F`), thanh toán (QR chuyển khoản / MoMo mô phỏng / trả tại quầy), xem lịch sử & chi tiết đơn, huỷ đơn chưa thanh toán.

**Admin** (`/admin`): dashboard doanh thu & thống kê, quản lý đơn (lọc, tìm kiếm, đổi trạng thái/thanh toán), quản lý vé/sản phẩm, quản lý người dùng, cấu hình tên website/thông tin/hotline/địa chỉ.

**Sản phẩm quà lưu niệm:** có thể thêm ảnh, giá, đường dẫn xem/mua và nhãn như “🔥 Siêu hot”, “🛍️ Mua nhiều”; sản phẩm được hiển thị thành khu riêng trên trang chủ. Đường dẫn mua hàng có thể là URL cửa hàng ngoài hoặc đường dẫn nội bộ.\n\n**Bảo mật:** mật khẩu băm `password_hash`, prepared statements (chống SQL injection), CSRF token, escape HTML (chống XSS), giá luôn tính lại ở server, phân quyền admin.

## Cấu trúc
```
buffet/
├── index.php  login.php  register.php  logout.php
├── checkout.php  order.php  orders.php
├── admin/  index.php  orders.php  products.php  users.php
├── includes/  config.php (CSDL + hàm chung)  header/footer/admin_header/admin_footer
├── assets/  style.css  app.js
└── database/  schema.sql (SQLite)  schema_mysql.sql (MySQL)  buffet.sqlite (tự tạo)
```

## Tuỳ chỉnh
- Tài khoản ngân hàng nhận QR: sửa `BANK_ID`, `BANK_ACCOUNT`, `BANK_OWNER` trong `includes/config.php`.
- Khung giờ: sửa hằng `TIME_SLOTS` trong `includes/config.php`.
- Muốn dùng MySQL: import `database/schema_mysql.sql` (tạo DB `buffet` + bảng + dữ liệu mẫu), rồi trong `includes/config.php` đặt `DB_DRIVER = 'mysql'` và nhập `DB_HOST/DB_NAME/DB_USER/DB_PASS`.
- Làm lại CSDL SQLite từ đầu: xoá file `database/buffet.sqlite`, lần chạy sau sẽ tự nạp lại `schema.sql`.
- Thanh toán MoMo/chuyển khoản hiện là **mô phỏng** (nút xác nhận); để chạy thật cần tích hợp cổng thanh toán và webhook.

## Quy tắc đặt vé
- Phải có ít nhất 1 vé buffet; số vé nước uống không vượt quá số vé buffet (kiểm tra cả ở trình duyệt và server).
- Ngày dùng bữa từ hôm nay đến 60 ngày tới; giá luôn lấy từ CSDL.


**Quản lý quà lưu niệm:** vào Quản trị → Quà lưu niệm để thêm/sửa/xóa sản phẩm, tải ảnh từ máy tính (JPG/PNG/WEBP/GIF tối đa 5 MB), đặt nhãn Siêu hot/Mua nhiều/Nổi bật, thêm link mua hàng và bật/tắt hiển thị.
