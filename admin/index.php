<?php
$adminPage = 'dashboard';
$pageTitle = 'Tổng quan';
require __DIR__ . '/../includes/admin_header.php';
$pdo = db();

$revenue   = (int)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid' AND status<>'cancelled'")->fetchColumn();
$orderCnt  = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$customers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$tickets   = (int)$pdo->query("SELECT COALESCE(SUM(i.qty),0) FROM order_items i JOIN orders o ON o.id=i.order_id JOIN products p ON p.id=i.product_id WHERE o.status<>'cancelled' AND p.type='ticket'")->fetchColumn();

// Doanh thu 14 ngày gần nhất
$days = [];
for ($i = 13; $i >= 0; $i--) { $days[date('Y-m-d', strtotime("-$i day"))] = 0; }
$st = $pdo->prepare("SELECT substr(created_at,1,10) AS d, SUM(total) AS s FROM orders WHERE payment_status='paid' AND status<>'cancelled' AND substr(created_at,1,10) >= ? GROUP BY substr(created_at,1,10)");
if (DB_DRIVER === 'mysql') {
    $st = $pdo->prepare("SELECT DATE(created_at) AS d, SUM(total) AS s FROM orders WHERE payment_status='paid' AND status<>'cancelled' AND DATE(created_at) >= ? GROUP BY DATE(created_at)");
}
$st->execute([array_key_first($days)]);
foreach ($st->fetchAll() as $r) { if (isset($days[$r['d']])) { $days[$r['d']] = (int)$r['s']; } }
$maxDay = max(1, max($days));

$top = $pdo->query("SELECT i.name, SUM(i.qty) AS q, SUM(i.qty*i.price) AS s FROM order_items i JOIN orders o ON o.id=i.order_id
                    WHERE o.status<>'cancelled' GROUP BY i.name ORDER BY q DESC LIMIT 5")->fetchAll();
$recent = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 8")->fetchAll();
?>
<div class="admin-top"><h2>Tổng quan</h2><a class="btn btn-outline btn-sm" href="<?= e(url('admin/orders.php')) ?>">Xem tất cả đơn →</a></div>

<div class="stats">
  <div class="stat"><i>💰</i><span>Doanh thu đã thu</span><b><?= money($revenue) ?></b></div>
  <div class="stat"><i>🧾</i><span>Tổng đơn hàng</span><b><?= $orderCnt ?></b></div>
  <div class="stat"><i>⏳</i><span>Chờ xác nhận</span><b><?= $pending ?></b></div>
  <div class="stat"><i>🎟️</i><span>Vé buffet đã bán</span><b><?= $tickets ?></b></div>
</div>

<div class="panel-grid">
  <div class="card">
    <h3>Doanh thu 14 ngày gần nhất</h3>
    <div class="bars">
      <?php foreach ($days as $d => $v): ?>
        <div class="bar" title="<?= e(date('d/m', strtotime($d)) . ': ' . money($v)) ?>">
          <em><?= $v > 0 ? e(number_format($v / 1000) . 'k') : '' ?></em>
          <div style="height:<?= $v > 0 ? max(3, round($v / $maxDay * 140)) : 3 ?>px;<?= $v === 0 ? 'opacity:.25' : '' ?>"></div>
          <small><?= e(date('d/m', strtotime($d))) ?></small>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <h3>Bán chạy nhất</h3>
    <?php if (!$top): ?><p class="muted">Chưa có dữ liệu.</p><?php endif; ?>
    <?php foreach ($top as $t): ?>
      <div class="sum-line"><span><?= e($t['name']) ?> <small>× <?= (int)$t['q'] ?></small></span><b><?= money($t['s']) ?></b></div>
    <?php endforeach; ?>
    <p class="muted" style="margin:1rem 0 0;font-size:.85rem"><?= $customers ?> khách hàng đã đăng ký.</p>
  </div>
</div>

<h3>Đơn mới nhất</h3>
<div class="table-wrap"><table class="tbl">
  <tr><th>Mã đơn</th><th>Khách</th><th>Ngày ăn</th><th>Tổng</th><th>Thanh toán</th><th>Trạng thái</th></tr>
  <?php foreach ($recent as $o): ?>
    <tr>
      <td><a href="<?= e(url('order.php?code=' . $o['code'])) ?>"><b><?= e($o['code']) ?></b></a></td>
      <td><?= e($o['customer_name']) ?><br><span class="muted"><?= e($o['phone']) ?></span></td>
      <td><?= date('d/m/Y', strtotime($o['visit_date'])) ?> · <?= e($o['visit_time']) ?></td>
      <td><?= money($o['total']) ?></td>
      <td><?= badge(PAY_LABELS, $o['payment_status']) ?></td>
      <td><?= badge(STATUS_LABELS, $o['status']) ?></td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
