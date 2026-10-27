<?php
require_once __DIR__ . '/includes/config.php';
$user = require_login();
$pdo = db();

$st = $pdo->prepare('SELECT * FROM orders WHERE code=?');
$st->execute([strtoupper(trim($_GET['code'] ?? ''))]);
$order = $st->fetch();
if (!$order || ($order['user_id'] != $user['id'] && $user['role'] !== 'admin')) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy đơn';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty"><h2>Không tìm thấy đơn hàng</h2><a class="btn btn-primary" href="' . url('orders.php') . '">Về đơn của tôi</a></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'pay' && $order['payment_status'] === 'unpaid' && $order['status'] !== 'cancelled' && $order['payment_method'] !== 'cash') {
        // Mô phỏng cổng thanh toán: đánh dấu đã thanh toán
        $pdo->prepare("UPDATE orders SET payment_status='paid', status='confirmed' WHERE id=?")->execute([$order['id']]);
        flash('Thanh toán thành công (mô phỏng). Đơn đã được xác nhận!');
    } elseif ($action === 'cancel' && $order['payment_status'] === 'unpaid' && $order['status'] === 'pending') {
        $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=?")->execute([$order['id']]);
        flash('Đã huỷ đơn hàng.', 'warn');
    }
    redirect(url('order.php?code=' . $order['code']));
}

$items = order_items((int)$order['id']);
$qrUrl = 'https://img.vietqr.io/image/' . BANK_ID . '-' . BANK_ACCOUNT . '-compact2.png?amount=' . (int)$order['total']
       . '&addInfo=' . urlencode($order['code']) . '&accountName=' . urlencode(BANK_OWNER);
$pageTitle = 'Đơn ' . $order['code'];
require __DIR__ . '/includes/header.php';
?>
<div class="container" style="margin:2rem auto 4rem">
  <div class="checkout">
    <div style="display:grid;gap:1.2rem">
      <div class="card">
        <span class="muted">Mã đơn hàng</span>
        <div class="order-code"><?= e($order['code']) ?></div>
        <p><?= badge(STATUS_LABELS, $order['status']) ?> <?= badge(PAY_LABELS, $order['payment_status']) ?></p>
        <p class="muted">Vui lòng đưa mã đơn này cho nhân viên khi đến quán.</p>
        <div class="row2">
          <div><span class="muted">Người nhận vé</span><br><b><?= e($order['customer_name']) ?></b><br><?= e($order['phone']) ?></div>
          <div><span class="muted">Thời gian</span><br><b><?= date('d/m/Y', strtotime($order['visit_date'])) ?> – <?= e($order['visit_time']) ?></b><br>Đặt lúc <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
        </div>
        <?php if ($order['note']): ?><p><span class="muted">Ghi chú:</span> <?= e($order['note']) ?></p><?php endif; ?>
      </div>

      <div class="card">
        <h3>Chi tiết vé</h3>
        <div class="table-wrap"><table class="tbl">
          <tr><th>Sản phẩm</th><th>Đơn giá</th><th>SL</th><th>Thành tiền</th></tr>
          <?php foreach ($items as $it): ?>
            <tr><td><?= e($it['name']) ?></td><td><?= money($it['price']) ?></td><td><?= (int)$it['qty'] ?></td><td><?= money($it['price'] * $it['qty']) ?></td></tr>
          <?php endforeach; ?>
          <tr><td colspan="3"><b>Tổng cộng</b></td><td><b><?= money($order['total']) ?></b></td></tr>
        </table></div>
      </div>
    </div>

    <aside class="card summary">
      <h3>Thanh toán</h3>
      <p>Phương thức: <b><?= e(METHOD_LABELS[$order['payment_method']] ?? $order['payment_method']) ?></b></p>
      <?php if ($order['status'] === 'cancelled'): ?>
        <div class="alert alert-bad">Đơn hàng đã bị huỷ.</div>
      <?php elseif ($order['payment_status'] === 'paid'): ?>
        <div class="alert alert-ok">✅ Đã thanh toán <?= money($order['total']) ?>. Hẹn gặp bạn tại quán!</div>
      <?php elseif ($order['payment_method'] === 'cash'): ?>
        <div class="alert alert-info">Bạn sẽ thanh toán <b><?= money($order['total']) ?></b> tại quầy khi đến quán. Đơn sẽ được nhân viên xác nhận.</div>
      <?php elseif ($order['payment_method'] === 'bank'): ?>
        <div class="qr-box">
          <img src="<?= e($qrUrl) ?>" alt="QR chuyển khoản" onerror="this.style.display='none'">
          <p class="muted" style="font-size:.9rem">Ngân hàng <b><?= e(BANK_ID) ?></b> · STK <b><?= e(BANK_ACCOUNT) ?></b><br><?= e(BANK_OWNER) ?><br>Số tiền <b><?= money($order['total']) ?></b><br>Nội dung: <b><?= e($order['code']) ?></b></p>
        </div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="pay">
          <button class="btn btn-primary btn-block" type="submit">Tôi đã chuyển khoản (mô phỏng)</button></form>
      <?php else: ?>
        <div class="alert alert-info">Mô phỏng ví MoMo: nhấn nút dưới đây để giả lập thanh toán <b><?= money($order['total']) ?></b>.</div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="pay">
          <button class="btn btn-primary btn-block" type="submit">Thanh toán bằng MoMo (mô phỏng)</button></form>
      <?php endif; ?>

      <?php if ($order['payment_status'] === 'unpaid' && $order['status'] === 'pending'): ?>
        <form method="post" data-confirm="Bạn chắc chắn muốn huỷ đơn này?" style="margin-top:.8rem"><?= csrf_field() ?><input type="hidden" name="action" value="cancel">
          <button class="btn btn-outline btn-block" type="submit">Huỷ đơn</button></form>
      <?php endif; ?>
      <p style="margin-top:1rem"><a href="<?= url('orders.php') ?>">← Tất cả đơn của tôi</a></p>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
