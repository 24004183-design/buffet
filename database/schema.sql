-- ============================================================
-- Buffet Station - CSDL SQLite (đầy đủ: bảng, chỉ mục, view, dữ liệu mẫu)
-- Tự động được nạp khi chạy lần đầu (includes/config.php -> init_db()).
-- Có thể chạy thủ công:  sqlite3 database/buffet.sqlite < database/schema.sql
-- ============================================================
PRAGMA foreign_keys = ON;

-- ---------- Người dùng ----------
CREATE TABLE IF NOT EXISTS users (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL,
    email       TEXT    NOT NULL UNIQUE,
    phone       TEXT,
    password    TEXT    NOT NULL,                       -- password_hash()
    role        TEXT    NOT NULL DEFAULT 'customer' CHECK (role IN ('customer','admin')),
    created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
);

-- ---------- Sản phẩm: vé buffet & nước uống ----------
CREATE TABLE IF NOT EXISTS products (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL,
    type        TEXT    NOT NULL DEFAULT 'ticket' CHECK (type IN ('ticket','drink','souvenir')),
    price       INTEGER NOT NULL CHECK (price >= 0),    -- VND
    description TEXT    NOT NULL DEFAULT '',
    features    TEXT    NOT NULL DEFAULT '',            -- mỗi dòng một tính năng
    badge       TEXT    NOT NULL DEFAULT '',            -- nhãn nổi bật: Siêu hot / Mua nhiều / ...
    image_url   TEXT    NOT NULL DEFAULT '',            -- URL ảnh sản phẩm
    product_url TEXT    NOT NULL DEFAULT '',            -- đường dẫn mua/xem sản phẩm
    sort_order  INTEGER NOT NULL DEFAULT 0,
    active      INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0,1)),
    created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
);

-- ---------- Đơn hàng ----------
CREATE TABLE IF NOT EXISTS orders (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    code           TEXT    NOT NULL UNIQUE,             -- VD: BF2610279A3F
    user_id        INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    customer_name  TEXT    NOT NULL,
    phone          TEXT    NOT NULL,
    email          TEXT    NOT NULL DEFAULT '',
    visit_date     TEXT    NOT NULL,                    -- YYYY-MM-DD
    visit_time     TEXT    NOT NULL,                    -- HH:MM
    note           TEXT    NOT NULL DEFAULT '',
    payment_method TEXT    NOT NULL DEFAULT 'bank' CHECK (payment_method IN ('bank','momo','cash')),
    payment_status TEXT    NOT NULL DEFAULT 'unpaid'  CHECK (payment_status IN ('unpaid','paid','refunded')),
    status         TEXT    NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','confirmed','completed','cancelled')),
    total          INTEGER NOT NULL DEFAULT 0 CHECK (total >= 0),
    created_at     TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
);

-- ---------- Chi tiết đơn (lưu snapshot tên + giá tại thời điểm đặt) ----------
CREATE TABLE IF NOT EXISTS order_items (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id    INTEGER NOT NULL REFERENCES orders(id)   ON DELETE CASCADE,
    product_id  INTEGER          REFERENCES products(id) ON DELETE SET NULL,
    name        TEXT    NOT NULL,
    price       INTEGER NOT NULL CHECK (price >= 0),
    qty         INTEGER NOT NULL CHECK (qty > 0)
);

-- ---------- Chỉ mục ----------
CREATE INDEX IF NOT EXISTS idx_orders_user    ON orders(user_id);
CREATE INDEX IF NOT EXISTS idx_orders_visit   ON orders(visit_date, visit_time);
CREATE INDEX IF NOT EXISTS idx_orders_status  ON orders(status, payment_status);
CREATE INDEX IF NOT EXISTS idx_items_order    ON order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_products_list  ON products(active, type, sort_order);

-- ---------- View thống kê doanh thu theo ngày (dashboard admin) ----------
CREATE VIEW IF NOT EXISTS v_daily_revenue AS
SELECT date(created_at)                                   AS day,
       COUNT(*)                                           AS orders,
       SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) AS revenue
FROM orders
WHERE status <> 'cancelled'
GROUP BY date(created_at);

-- ============================================================
-- DỮ LIỆU MẪU
-- ============================================================

-- Tài khoản demo: admin@buffet.vn / admin123  -  khach@demo.vn / 123456
INSERT OR IGNORE INTO users (id, name, email, phone, password, role) VALUES
 (1, 'Quản trị viên', 'admin@buffet.vn', '0900000001', '$2y$10$MBuyFA5P6RvSck6lV9yThedVxQHt65bdCWlGe.FJJhchkphAU.YYe', 'admin'),
 (2, 'Nguyễn Văn Khách', 'khach@demo.vn', '0912345678', '$2y$10$9FsCmt2H/9t3M3iMFvFk3O.0wYNfQtsF8MoKc0v/.JCw8EpYISqim', 'customer');

INSERT OR IGNORE INTO products (id, name, type, price, description, features, badge, sort_order, active) VALUES
 (1, 'Buffet Cơ Bản',     'ticket', 169000, 'Lựa chọn tiết kiệm cho bữa ăn gia đình, bạn bè.',
  'Hơn 60 món nướng & lẩu' || char(10) || 'Rau củ, nấm, topping tự chọn' || char(10) || 'Kem & tráng miệng cơ bản', '', 1, 1),
 (2, 'Buffet Tiêu Chuẩn', 'ticket', 199000, 'Đa dạng hơn với bò, heo, gà và hải sản cơ bản.',
  'Hơn 80 món nướng & lẩu' || char(10) || 'Bò Mỹ, ba chỉ, gà các loại' || char(10) || 'Tôm, mực, cá viên', 'Phổ biến', 2, 1),
 (3, 'Buffet Cao Cấp',    'ticket', 259000, 'Hải sản tươi sống và thịt bò thượng hạng.',
  'Hơn 100 món nướng & lẩu' || char(10) || 'Bò Wagyu, sườn non, hàu sữa' || char(10) || 'Tôm sú, ghẹ, sushi & sashimi', 'Được yêu thích', 3, 1),
 (4, 'Buffet VIP',        'ticket', 399000, 'Trải nghiệm sang trọng với phòng riêng và món đặc biệt.',
  'Toàn bộ thực đơn Cao Cấp' || char(10) || 'Cua hoàng đế, bào ngư, tôm hùm mini' || char(10) || 'Khu vực VIP & phục vụ riêng', 'Sang trọng', 4, 1),
 (5, 'Buffet Nước Uống',  'drink',   39000, 'Uống thả ga: trà đào, nước ngọt, nước ép, trà sữa.',
  'Hơn 15 loại thức uống' || char(10) || 'Refill không giới hạn', '', 5, 1);

-- Đơn mẫu (để dashboard admin có dữ liệu)
INSERT OR IGNORE INTO orders (id, code, user_id, customer_name, phone, email, visit_date, visit_time, note, payment_method, payment_status, status, total, created_at) VALUES
 (1, 'BF2610011A2B', 2, 'Nguyễn Văn Khách', '0912345678', 'khach@demo.vn', date('now','-20 day','localtime'), '18:30', 'Sinh nhật bạn',     'bank', 'paid',   'completed', 536000, datetime('now','-24 day','localtime')),
 (2, 'BF2610057C3D', 2, 'Nguyễn Văn Khách', '0912345678', 'khach@demo.vn', date('now','-9 day','localtime'),  '19:00', '',                  'momo', 'paid',   'completed', 798000, datetime('now','-12 day','localtime')),
 (3, 'BF2610153E4F', 2, 'Nguyễn Văn Khách', '0912345678', 'khach@demo.vn', date('now','+2 day','localtime'),  '18:30', 'Có 1 trẻ em',      'cash', 'unpaid', 'pending',   398000, datetime('now','-1 day','localtime')),
 (4, 'BF2610205A6B', 2, 'Nguyễn Văn Khách', '0912345678', 'khach@demo.vn', date('now','-3 day','localtime'),  '12:00', '',                  'bank', 'unpaid', 'cancelled', 199000, datetime('now','-5 day','localtime'));

INSERT OR IGNORE INTO order_items (order_id, product_id, name, price, qty) VALUES
 (1, 3, 'Buffet Cao Cấp',    259000, 1),
 (1, 2, 'Buffet Tiêu Chuẩn', 199000, 1),
 (1, 5, 'Buffet Nước Uống',   39000, 2),
 (2, 4, 'Buffet VIP',        399000, 2),
 (3, 2, 'Buffet Tiêu Chuẩn', 199000, 2),
 (4, 2, 'Buffet Tiêu Chuẩn', 199000, 1);
