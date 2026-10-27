<?php
require_once __DIR__ . '/includes/config.php';
$user = require_login();
$st = db()->prepare("SELECT o.*, (SELECT COALESCE(SUM(qty),0) FROM order_items WHERE order_id=o.id) AS qty
                     FROM orders o WHERE user_id=? ORDER BY o.id DESC");
$st->execute([$user['id']]);
$orders = $st->fetchAll();
$pageTitle = 'Đơn của tôi';
require __DIR__ . '/includes/header.php';
?>
<div class="container" style="margin:2rem auto 4rem">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
    <h2>Đơn hàng của tôi</h2>
    <a class="btn btn-primary" href="<?= url('checkout.php') ?>">+ Đặt vé mới</a>
  </div>
  <?php if (!$orders): ?>
    <div class="card empty"><p>Bạn chưa có đơn hàng nào.</p><a class="btn btn-primary" href="<?= url('checkout.php') ?>">Đặt vé ngay</a></div>
  <?php else: ?>
    <div class="table-wrap"><table class="tbl">
      <tr><th>Mã đơn</th><th>Ngày ăn</th><th>Số vé</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th></th></tr>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><b><?= e($o['code']) ?></b></td>
          <td><?= date('d/m/Y', strtotime($o['visit_date'])) ?> · <?= e($o['visit_time']) ?></td>
          <td><?= (int)$o['qty'] ?></td>
          <td><?= money($o['total']) ?></td>
          <td><?= badge(PAY_LABELS, $o['payment_status']) ?><br><span class="muted"><?= e(METHOD_LABELS[$o['payment_method']] ?? '') ?></span></td>
          <td><?= badge(STATUS_LABELS, $o['status']) ?></td>
          <td><a class="btn btn-sm btn-outline" href="<?= url('order.php?code=' . $o['code']) ?>">Chi tiết</a></td>
        </tr>
      <?php endforeach; ?>
    </table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
