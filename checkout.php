<?php
require_once __DIR__ . '/includes/config.php';
$user = require_login();
$pdo = db();
$products = $pdo->query('SELECT * FROM products WHERE active=1 ORDER BY sort_order, price')->fetchAll();

$errors = [];
$qty = [];
foreach ($products as $p) { $qty[$p['id']] = 0; }
if (isset($_GET['add']) && isset($qty[(int)$_GET['add']])) {
    $qty[(int)$_GET['add']] = 1;
    // Thêm nước uống mà chưa có vé thì tự thêm 1 vé rẻ nhất
    $first = $products[0] ?? null;
    foreach ($products as $p) { if ($p['id'] == (int)$_GET['add'] && $p['type'] === 'drink' && $first) { $qty[$first['id']] = 1; } }
}
$f = [
    'name' => $user['name'], 'phone' => $user['phone'] ?? '', 'email' => $user['email'],
    'date' => date('Y-m-d'), 'time' => '18:30', 'note' => '', 'method' => 'bank',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($products as $p) { $qty[$p['id']] = max(0, min(50, (int)($_POST['qty'][$p['id']] ?? 0))); }
    $f['name'] = trim($_POST['name'] ?? '');
    $f['phone'] = preg_replace('/\s+/', '', $_POST['phone'] ?? '');
    $f['email'] = trim($_POST['email'] ?? '');
    $f['date'] = $_POST['date'] ?? '';
    $f['time'] = $_POST['time'] ?? '';
    $f['note'] = trim($_POST['note'] ?? '');
    $f['method'] = $_POST['method'] ?? '';

    if (mb_strlen($f['name']) < 2) { $errors[] = 'Vui lòng nhập họ tên người nhận vé.'; }
    if (!preg_match('/^(0|\+84)\d{9,10}$/', $f['phone'])) { $errors[] = 'Số điện thoại không hợp lệ.'; }
    if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email không hợp lệ.'; }
    $d = DateTime::createFromFormat('Y-m-d', $f['date']);
    if (!$d || $d->format('Y-m-d') !== $f['date'] || $f['date'] < date('Y-m-d') || $f['date'] > date('Y-m-d', strtotime('+60 day'))) {
        $errors[] = 'Ngày dùng bữa phải từ hôm nay đến 60 ngày tới.';
    }
    if (!in_array($f['time'], TIME_SLOTS, true)) { $errors[] = 'Vui lòng chọn khung giờ.'; }
    if (!isset(METHOD_LABELS[$f['method']])) { $errors[] = 'Vui lòng chọn phương thức thanh toán.'; }

    if (!$errors) {
        try {
            $code = create_order($pdo, [
                'user_id' => $user['id'], 'name' => $f['name'], 'phone' => $f['phone'], 'email' => $f['email'],
                'date' => $f['date'], 'time' => $f['time'], 'note' => $f['note'], 'method' => $f['method'],
            ], $qty);
            flash('Đặt vé thành công! Mã đơn của bạn: ' . $code);
            redirect(url('order.php?code=' . $code));
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}
$pageTitle = 'Đặt vé';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
  <h2 style="margin-top:2rem">Đặt vé buffet</h2>
  <?php foreach ($errors as $err): ?><div class="alert alert-bad"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post" class="checkout" data-checkout novalidate>
    <?= csrf_field() ?>
    <div style="display:grid;gap:1.2rem">
      <div class="card">
        <h3>1. Chọn vé</h3>
        <?php foreach ($products as $p): ?>
          <div class="item-row">
            <div>
              <h4><?= e($p['name']) ?> <?= $p['type'] === 'drink' ? '🥤' : '' ?></h4>
              <span class="muted"><?= money($p['price']) ?> / người</span>
            </div>
            <div class="stepper">
              <button type="button" data-dec>−</button>
              <input class="qty" type="number" min="0" max="50" name="qty[<?= (int)$p['id'] ?>]" value="<?= (int)$qty[$p['id']] ?>"
                     data-price="<?= (int)$p['price'] ?>" data-name="<?= e($p['name']) ?>" data-type="<?= e($p['type']) ?>">
              <button type="button" data-inc>+</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <h3>2. Thông tin & thời gian</h3>
        <div class="row2">
          <div class="field"><label>Họ tên người nhận vé</label><input class="input" name="name" value="<?= e($f['name']) ?>" required></div>
          <div class="field"><label>Số điện thoại</label><input class="input" name="phone" value="<?= e($f['phone']) ?>" required></div>
        </div>
        <div class="field"><label>Email (không bắt buộc)</label><input class="input" type="email" name="email" value="<?= e($f['email']) ?>"></div>
        <div class="row2">
          <div class="field"><label>Ngày dùng bữa</label>
            <input class="input" type="date" name="date" value="<?= e($f['date']) ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+60 day')) ?>" required></div>
          <div class="field"><label>Khung giờ</label>
            <select class="input" name="time">
              <?php foreach (TIME_SLOTS as $t): ?><option value="<?= $t ?>" <?= $f['time'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
            </select></div>
        </div>
        <div class="field"><label>Ghi chú</label><textarea class="input" name="note" rows="2" placeholder="VD: có trẻ em, kỷ niệm sinh nhật..."><?= e($f['note']) ?></textarea></div>
      </div>

      <div class="card">
        <h3>3. Phương thức thanh toán</h3>
        <div class="pay-options">
          <?php foreach (METHOD_LABELS as $k => $label): ?>
            <label class="pay-opt"><input type="radio" name="method" value="<?= $k ?>" <?= $f['method'] === $k ? 'checked' : '' ?>> <?= e($label) ?></label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <aside class="card summary">
      <h3>Tóm tắt đơn hàng</h3>
      <div id="sum-lines"></div>
      <div class="sum-line sum-total"><span>Tổng cộng</span><span id="sum-total">0 ₫</span></div>
      <div class="alert alert-warn" id="sum-warn" style="display:none"></div>
      <button class="btn btn-primary btn-block" type="submit" style="margin-top:1rem">Xác nhận đặt vé</button>
      <p class="muted" style="font-size:.85rem;margin-bottom:0">Giá đã gồm VAT. Bạn sẽ nhận mã đơn ngay sau khi đặt.</p>
    </aside>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
