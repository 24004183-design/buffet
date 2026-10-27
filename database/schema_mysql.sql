-- ============================================================
-- Buffet Station - CSDL MySQL / MariaDB (đầy đủ: bảng, khoá ngoại, chỉ mục, view, dữ liệu mẫu)
-- Cách dùng:  mysql -u root -p < database/schema_mysql.sql
-- Sau đó đổi chuỗi kết nối trong includes/config.php (hằng DB_DRIVER = 'mysql').
-- ============================================================
CREATE DATABASE IF NOT EXISTS buffet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE buffet;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW  IF EXISTS v_daily_revenue;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(190) NOT NULL UNIQUE,
    phone       VARCHAR(20)  NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    type        ENUM('ticket','drink') NOT NULL DEFAULT 'ticket',
    price       INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    features    TEXT NOT NULL,
    badge       VARCHAR(40)  NOT NULL DEFAULT '',
    image_url   VARCHAR(1000) NOT NULL DEFAULT '',
    product_url VARCHAR(1000) NOT NULL DEFAULT '',
    sort_order  INT NOT NULL DEFAULT 0,
    active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_products_list (active, type, sort_order)
) ENGINE=InnoDB;

CREATE TABLE orders (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code           VARCHAR(20)  NOT NULL UNIQUE,
    user_id        INT UNSIGNED NOT NULL,
    customer_name  VARCHAR(120) NOT NULL,
    phone          VARCHAR(20)  NOT NULL,
    email          VARCHAR(190) NOT NULL DEFAULT '',
    visit_date     DATE NOT NULL,
    visit_time     CHAR(5) NOT NULL,
    note           VARCHAR(500) NOT NULL DEFAULT '',
    payment_method ENUM('bank','momo','cash') NOT NULL DEFAULT 'bank',
    payment_status ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
    status         ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    total          INT UNSIGNED NOT NULL DEFAULT 0,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_orders_user (user_id),
    KEY idx_orders_visit (visit_date, visit_time),
    KEY idx_orders_status (status, payment_status),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NULL,
    name        VARCHAR(120) NOT NULL,
    price       INT UNSIGNED NOT NULL,
    qty         INT UNSIGNED NOT NULL,
    KEY idx_items_order (order_id),
    CONSTRAINT fk_items_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE VIEW v_daily_revenue AS
SELECT DATE(created_at) AS day,
       COUNT(*)         AS orders,
       SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) AS revenue
FROM orders
WHERE status <> 'cancelled'
GROUP BY DATE(created_at);

-- ---------- Dữ liệu mẫu ----------
-- admin@buffet.vn / admin123  -  khach@demo.vn / 123456
INSERT INTO users (id, name, email, phone, password, role) VALUES
 (1, 'Quản trị viên', 'admin@buffet.vn', '0900000001', '$2y$10$MBuyFA5P6RvSck6lV9yThedVxQHt65bdCWlGe.FJJhchkphAU.YYe', 'admin'),
 (2, 'Nguyễn Văn Khách', 'khach@demo.vn', '0912345678', '$2y$10$9FsCmt2H/9t3M3iMFvFk3O.0wYNfQtsF8MoKc0v/.JCw8EpYISqim', 'customer');

INSERT INTO products (id, name, type, price, description, features, badge, sort_order, active) VALUES
 (1, 'Buffet Cơ Bản',     'ticket', 169000, 'Lựa chọn tiết kiệm cho bữa ăn gia đình, bạn bè.',
  CONCAT('Hơn 60 món nướng & lẩu', CHAR(10), 'Rau củ, nấm, topping tự chọn', CHAR(10), 'Kem & tráng miệng cơ bản'), '', 1, 1),
 (2, 'Buffet Tiêu Chuẩn', 'ticket', 199000, 'Đa dạng hơn với bò, heo, gà và hải sản cơ bản.',
  CONCAT('Hơn 80 món nướng & lẩu', CHAR(10), 'Bò Mỹ, ba chỉ, gà các loại', CHAR(10), 'Tôm, mực, cá viên'), 'Phổ biến', 2, 1),
 (3, 'Buffet Cao Cấp',    'ticket', 259000, 'Hải sản tươi sống và thịt bò thượng hạng.',
  CONCAT('Hơn 100 món nướng & lẩu', CHAR(10), 'Bò Wagyu, sườn non, hàu sữa', CHAR(10), 'Tôm sú, ghẹ, sushi & sashimi'), 'Được yêu thích', 3, 1),
 (4, 'Buffet VIP',        'ticket', 399000, 'Trải nghiệm sang trọng với phòng riêng và món đặc biệt.',
  CONCAT('Toàn bộ thực đơn Cao Cấp', CHAR(10), 'Cua hoàng đế, bào ngư, tôm hùm mini', CHAR(10), 'Khu vực VIP & phục vụ riêng'), 'Sang trọng', 4, 1),
 (5, 'Buffet Nước Uống',  'drink',   39000, 'Uống thả ga: trà đào, nước ngọt, nước ép, trà sữa.',
  CONCAT('Hơn 15 loại thức uống', CHAR(10), 'Refill không giới hạn'), '', 5, 1);

INSERT INTO orders (id, code, user_id, customer_name, phone, email, visit_date, visit_time, note, payment_method, payment_status, status, total, created_at) VALUES
 (1, 'BF2610011A2B', 2, 'Nguyễn Văn Khách', '0912345678', 'khach@demo.vn', CURDATE() - INTERVAL 20 DAY, '18:30', 'Sinh nhật bạn', 'bank', 'paid',   'completed', 536000, NOW() - INTERVAL 24 DAY),
 (2, 'BF2610057C3D', 2, 'Nguyễn Văn Khách', '0912345678', 'khach@demo.vn', CURDATE() - INTERVAL 9 DAY,  '19:00', '',              'momo', 'paid',   'completed', 798000, NOW() - INTERVAL 12 DAY),
 (3, 'BF2610153E4F', 2, 'Nguyễn Văn Khách', '0912345678', 'khach@demo.vn', CURDATE() + INTERVAL 2 DAY,  '18:30', 'Có 1 trẻ em',   'cash', 'unpaid', 'pending',   398000, NOW() - INTERVAL 1 DAY),
 (4, 'BF2610205A6B', 2, 'Nguyễn Văn Khách', '0912345678', 'khach@demo.vn', CURDATE() - INTERVAL 3 DAY,  '12:00', '',              'bank', 'unpaid', 'cancelled', 199000, NOW() - INTERVAL 5 DAY);

INSERT INTO order_items (order_id, product_id, name, price, qty) VALUES
 (1, 3, 'Buffet Cao Cấp',    259000, 1),
 (1, 2, 'Buffet Tiêu Chuẩn', 199000, 1),
 (1, 5, 'Buffet Nước Uống',   39000, 2),
 (2, 4, 'Buffet VIP',        399000, 2),
 (3, 2, 'Buffet Tiêu Chuẩn', 199000, 2),
 (4, 2, 'Buffet Tiêu Chuẩn', 199000, 1);
