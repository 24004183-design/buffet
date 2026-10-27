<?php
/**
 * Buffet Station - cấu hình + hàm dùng chung.
 * Mặc định dùng SQLite (tự tạo từ database/schema.sql). Muốn dùng MySQL: đặt DB_DRIVER = 'mysql'
 * và nhập thông tin kết nối bên dưới (đã import database/schema_mysql.sql).
 */
if (defined('APP_BOOT')) { return; }
define('APP_BOOT', true);

date_default_timezone_set('Asia/Ho_Chi_Minh');
mb_internal_encoding('UTF-8');

// ---------- Cấu hình ----------
const APP_NAME   = 'Buffet Station';
const HOTLINE    = '1900 6868';
const DB_DRIVER  = 'sqlite';              // 'sqlite' | 'mysql'
const DB_SQLITE  = __DIR__ . '/../database/buffet.sqlite';
const DB_HOST    = '127.0.0.1';
const DB_NAME    = 'buffet';
const DB_USER    = 'root';
const DB_PASS    = '';

const BANK_ID      = 'VCB';
const BANK_ACCOUNT = '0123456789';
const BANK_OWNER   = 'CONG TY BUFFET STATION';

const TIME_SLOTS = ['11:00', '12:00', '13:00', '17:30', '18:30', '19:30', '20:30'];
const METHOD_LABELS = ['bank' => 'Chuyển khoản QR', 'momo' => 'Ví MoMo', 'cash' => 'Trả tại quầy'];
const STATUS_LABELS = [
    'pending'   => ['Chờ xác nhận', 'warn'],
    'confirmed' => ['Đã xác nhận', 'info'],
    'completed' => ['Hoàn thành', 'ok'],
    'cancelled' => ['Đã huỷ', 'bad'],
];
const PAY_LABELS = [
    'unpaid'   => ['Chưa thanh toán', 'warn'],
    'paid'     => ['Đã thanh toán', 'ok'],
    'refunded' => ['Đã hoàn tiền', 'muted'],
];

// ---------- Session ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// ---------- Tiện ích ----------
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return number_format((float)$n, 0, ',', '.') . ' ₫'; }

// Cấu hình website có thể chỉnh trong trang Quản trị > Cấu hình website.
function ensure_settings_table(PDO $pdo): void {
    if (DB_DRIVER === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NOT NULL)");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (setting_key TEXT PRIMARY KEY, setting_value TEXT NOT NULL DEFAULT '')");
    }
    $defaults = [
        'site_name' => APP_NAME,
        'site_info' => 'Buffet nướng, lẩu & hải sản không giới hạn. Đặt vé online, tới là ăn.',
        'phone' => HOTLINE,
        'address' => '123 Đường Ẩm Thực, TP. Vĩnh Long',
        'souvenir_title' => 'Quà lưu niệm',
        'souvenir_info' => 'Khám phá các món quà nhỏ xinh dành tặng người thân và bạn bè.',
    ];
    $st = $pdo->prepare(DB_DRIVER === 'mysql' ? 'INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)' : 'INSERT OR IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
    foreach ($defaults as $key => $value) {
        try { $st->execute([$key, $value]); }
        catch (Throwable $e) {
            // MySQL không hỗ trợ cú pháp INSERT OR IGNORE.
            $check = $pdo->prepare('SELECT setting_key FROM site_settings WHERE setting_key=?');
            $check->execute([$key]);
            if (!$check->fetchColumn()) {
                $ins = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
                $ins->execute([$key, $value]);
            }
        }
    }
}
function app_setting(string $key, string $fallback = ''): string {
    try {
        $pdo = db();
        ensure_settings_table($pdo);
        $st = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key=?');
        $st->execute([$key]);
        $value = $st->fetchColumn();
        return $value === false ? $fallback : (string)$value;
    } catch (Throwable $e) {
        return $fallback;
    }
}
function app_name(): string { return app_setting('site_name', APP_NAME); }
function app_phone(): string { return app_setting('phone', HOTLINE); }

function base_path(): string {
    static $b = null;
    if ($b === null) {
        $doc  = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
        $root = realpath(__DIR__ . '/..') ?: '';
        $b = ($doc !== '' && strpos($root, $doc) === 0) ? str_replace('\\', '/', substr($root, strlen($doc))) : '';
        $b = rtrim($b, '/');
    }
    return $b;
}
function url(string $path = ''): string { return base_path() . '/' . ltrim($path, '/'); }
function asset(string $path): string {
    $f = __DIR__ . '/../assets/' . $path;
    return url('assets/' . $path) . (is_file($f) ? '?v=' . filemtime($f) : '');
}
function redirect(string $to): void { header('Location: ' . $to); exit; }

function safe_next($next): string {
    $next = (string)$next;
    if ($next === '' || $next[0] !== '/' || (isset($next[1]) && ($next[1] === '/' || $next[1] === '\\'))) { return ''; }
    return $next;
}

function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'][] = ['m' => $msg, 't' => $type]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function badge(array $labels, string $key): string {
    [$text, $cls] = $labels[$key] ?? [$key, 'muted'];
    return '<span class="badge badge-' . e($cls) . '">' . e($text) . '</span>';
}

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $ok = isset($_POST['_csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['_csrf']);
    if (!$ok) { http_response_code(419); exit('Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.'); }
}

// ---------- CSDL ----------
function db(): PDO {
    static $pdo = null;
    if ($pdo) { return $pdo; }
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    if (DB_DRIVER === 'mysql') {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opts);
    } else {
        $isNew = !is_file(DB_SQLITE) || filesize(DB_SQLITE) === 0;
        $pdo = new PDO('sqlite:' . DB_SQLITE, null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
        if ($isNew) { init_db($pdo); }
    }
    ensure_settings_table($pdo);
    // Bổ sung cột ảnh/đường dẫn cho CSDL đã tạo từ phiên bản cũ.
    try { $pdo->query('SELECT image_url, product_url FROM products LIMIT 1'); }
    catch (Throwable $e) {
        if (DB_DRIVER === 'sqlite') {
            try { $pdo->exec("ALTER TABLE products ADD COLUMN image_url TEXT NOT NULL DEFAULT ''"); } catch (Throwable $ignored) {}
            try { $pdo->exec("ALTER TABLE products ADD COLUMN product_url TEXT NOT NULL DEFAULT ''"); } catch (Throwable $ignored) {}
        }
    }
    // Nâng CHECK constraint của SQLite cũ để chấp nhận loại quà lưu niệm, giữ nguyên dữ liệu hiện có.
    if (DB_DRIVER === 'sqlite') {
        $schema = (string)$pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='products'")->fetchColumn();
        if (strpos($schema, "'souvenir'") === false) {
            $pdo->exec('PRAGMA foreign_keys = OFF');
            try {
                $pdo->exec("CREATE TABLE products_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    type TEXT NOT NULL DEFAULT 'ticket' CHECK (type IN ('ticket','drink','souvenir')),
                    price INTEGER NOT NULL CHECK (price >= 0),
                    description TEXT NOT NULL DEFAULT '',
                    features TEXT NOT NULL DEFAULT '',
                    badge TEXT NOT NULL DEFAULT '',
                    image_url TEXT NOT NULL DEFAULT '',
                    product_url TEXT NOT NULL DEFAULT '',
                    sort_order INTEGER NOT NULL DEFAULT 0,
                    active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0,1)),
                    created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
                )");
                $pdo->exec('INSERT INTO products_new (id,name,type,price,description,features,badge,image_url,product_url,sort_order,active,created_at) SELECT id,name,type,price,description,features,badge,image_url,product_url,sort_order,active,created_at FROM products');
                $pdo->exec('DROP TABLE products');
                $pdo->exec('ALTER TABLE products_new RENAME TO products');
                $pdo->exec('CREATE INDEX IF NOT EXISTS idx_products_list ON products(active, type, sort_order)');
            } finally { $pdo->exec('PRAGMA foreign_keys = ON'); }
        }
    }
    return $pdo;
}
function init_db(PDO $pdo): void {
    $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
    $pdo->exec($sql);
}

// ---------- Xác thực ----------
function current_user(): ?array {
    static $cache = false;
    if ($cache !== false) { return $cache; }
    $cache = null;
    if (!empty($_SESSION['uid'])) {
        $st = db()->prepare('SELECT * FROM users WHERE id=?');
        $st->execute([(int)$_SESSION['uid']]);
        $cache = $st->fetch() ?: null;
    }
    return $cache;
}
function require_login(): array {
    $u = current_user();
    if (!$u) {
        flash('Vui lòng đăng nhập để tiếp tục.', 'info');
        redirect(url('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/')));
    }
    return $u;
}
function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') { http_response_code(403); exit('Bạn không có quyền truy cập khu vực này.'); }
    return $u;
}

// ---------- Đơn hàng ----------
function order_items(int $orderId): array {
    $st = db()->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id');
    $st->execute([$orderId]);
    return $st->fetchAll();
}

/**
 * Tạo đơn: giá luôn lấy từ CSDL (không tin dữ liệu từ client).
 * Quy tắc: phải có ít nhất 1 vé buffet; số vé nước uống không vượt quá số vé buffet.
 * @param array<int,int> $qty product_id => số lượng
 */
function create_order(PDO $pdo, array $info, array $qty): string {
    $qty = array_filter($qty, fn($q) => $q > 0);
    if (!$qty) { throw new RuntimeException('Vui lòng chọn ít nhất 1 vé.'); }

    $in = implode(',', array_fill(0, count($qty), '?'));
    $st = $pdo->prepare("SELECT * FROM products WHERE active=1 AND id IN ($in)");
    $st->execute(array_keys($qty));
    $rows = $st->fetchAll();
    if (count($rows) !== count($qty)) { throw new RuntimeException('Có sản phẩm không còn được bán. Vui lòng tải lại trang.'); }

    $tickets = 0; $drinks = 0; $total = 0;
    foreach ($rows as $r) {
        $q = $qty[$r['id']];
        if ($r['type'] === 'drink') { $drinks += $q; } else { $tickets += $q; }
        $total += (int)$r['price'] * $q;
    }
    if ($tickets < 1) { throw new RuntimeException('Cần có ít nhất 1 vé buffet (vé nước uống là add-on).'); }
    if ($drinks > $tickets) { throw new RuntimeException('Số vé nước uống không được vượt quá số vé buffet.'); }

    $pdo->beginTransaction();
    try {
        do {
            $code = 'BF' . date('ymd') . strtoupper(bin2hex(random_bytes(2)));
            $chk = $pdo->prepare('SELECT 1 FROM orders WHERE code=?');
            $chk->execute([$code]);
        } while ($chk->fetch());

        $pdo->prepare('INSERT INTO orders (code,user_id,customer_name,phone,email,visit_date,visit_time,note,payment_method,total)
                       VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([$code, $info['user_id'], $info['name'], $info['phone'], $info['email'],
                       $info['date'], $info['time'], $info['note'], $info['method'], $total]);
        $oid = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO order_items (order_id,product_id,name,price,qty) VALUES (?,?,?,?,?)');
        foreach ($rows as $r) { $ins->execute([$oid, $r['id'], $r['name'], $r['price'], $qty[$r['id']]]); }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw new RuntimeException('Không thể tạo đơn, vui lòng thử lại.');
    }
    return $code;
}
