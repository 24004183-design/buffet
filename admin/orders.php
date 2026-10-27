<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();
$pdo = db();

// ---- Cập nhật trạng thái (xử lý trước khi xuất HTML) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $pay = $_POST['payment_status'] ?? '';
    if ($id > 0 && isset(STATUS_LABELS[$status]) && isset(PAY_LABELS[$pay])) {
        $pdo->prepare('UPDATE orders SET status=?, payment_status=? WHERE id=?')->execute([$status, $pay, $id]);
        flash('Đã cập nhật đơn hàng.');
    }
    redirect($_POST['back'] ?? url('admin/orders.php'));
}

$q = trim($_GET['q'] ?? '');
$fs = $_GET['status'] ?? '';
$fp = $_GET['pay'] ?? '';
$fd = $_GET['date'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 12;

$where = []; $args = [];
if ($q !== '') { $where[] = '(code LIKE ? OR customer_name LIKE ? OR phone LIKE ?)'; $like = '%' . $q . '%'; array_push($args, $like, $like, $like); }
if (isset(STATUS_LABELS[$fs])) { $where[] = 'status=?'; $args[] = $fs; }
if (isset(PAY_LABELS[$fp])) { $where[] = 'payment_status=?'; $args[] = $fp; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fd)) { $where[] = 'visit_date=?'; $args[] = $fd; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM orders $w"); $cnt->execute($args);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);
$st = $pdo->prepare("SELECT o.*, (SELECT COALESCE(SUM(qty),0) FROM order_items WHERE order_id=o.id) AS qty
                     FROM orders o $w ORDER BY o.id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($args);
$orders = $st->fetchAll();

$qs = function (array $over = []) use ($q, $fs, $fp, $fd, $page) {
    return '?' . http_build_query(array_filter(array_merge(['q' => $q, 'status' => $fs, 'pay' => $fp, 'date' => $fd, 'page' => $page], $over), fn($v) => $v !== '' && $v !== null));
};
$self = $_SERVER['REQUEST_URI'] ?? url('admin/orders.php');

$adminPage = 'orders';
$pageTitle = 'Quản lý đơn hàng';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-top"><h2>Đơn hàng <span class="muted" style="font-size:1rem">(<?= $total ?>)</span></h2></div>

<form class="filters" method="get">
  <input class="input grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Tìm mã đơn, tên, số điện thoại…">
  <select class="input" name="status">
    <option value="">Mọi trạng thái</option>
    <?php foreach (STATUS_LABELS as $k => $l): ?><option value="<?= $k ?>" <?= $fs === $k ? 'selected' : '' ?>><?= e($l[0]) ?></option><?php endforeach; ?>
  </select>
  <select class="input" name="pay">
    <option value="">Mọi thanh toán</option>
    <?php foreach (PAY_LABELS as $k => $l): ?><option value="<?= $k ?>" <?= $fp === $k ? 'selected' : '' ?>><?= e($l[0]) ?></option><?php endforeach; ?>
  </select>
  <input class="input" type="date" name="date" value="<?= e($fd) ?>" title="Ngày ăn">
  <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
  <?php if ($where): ?><a class="btn btn-outline btn-sm" href="orders.php">Xoá lọc</a><?php endif; ?>
</form>

<?php if (!$orders): ?>
  <div class="card empty"><p>Không có đơn hàng phù hợp.</p></div>
<?php else: ?>
<div class="table-wrap"><table class="tbl">
  <tr><th>Mã đơn</th><th>Khách</th><th>Ngày ăn</th><th>Vé</th><th>Tổng</th><th>Phương thức</th><th>Cập nhật</th></tr>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><a href="<?= e(url('order.php?code=' . $o['code'])) ?>"><b><?= e($o['code']) ?></b></a><br><span class="muted" style="font-size:.8rem"><?= date('d/m H:i', strtotime($o['created_at'])) ?></span></td>
      <td><?= e($o['customer_name']) ?><br><span class="muted"><?= e($o['phone']) ?></span></td>
      <td><?= date('d/m/Y', strtotime($o['visit_date'])) ?><br><span class="muted"><?= e($o['visit_time']) ?></span></td>
      <td><?= (int)$o['qty'] ?></td>
      <td><b><?= money($o['total']) ?></b></td>
      <td><?= e(METHOD_LABELS[$o['payment_method']] ?? '') ?></td>
      <td>
        <form class="inline-form" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
          <input type="hidden" name="back" value="<?= e($self) ?>">
          <select class="input" name="status"><?php foreach (STATUS_LABELS as $k => $l): ?><option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($l[0]) ?></option><?php endforeach; ?></select>
          <select class="input" name="payment_status"><?php foreach (PAY_LABELS as $k => $l): ?><option value="<?= $k ?>" <?= $o['payment_status'] === $k ? 'selected' : '' ?>><?= e($l[0]) ?></option><?php endforeach; ?></select>
          <button class="btn btn-primary btn-sm" type="submit">Lưu</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php if ($pages > 1): ?>
  <div class="pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <?= $i === $page ? '<span class="cur">' . $i . '</span>' : '<a href="' . e($qs(['page' => $i])) . '">' . $i . '</a>' ?>
    <?php endfor; ?>
  </div>
<?php endif; endif; ?>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
