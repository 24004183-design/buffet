<?php
require_once __DIR__ . '/../includes/config.php';
$me = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'customer';
    if ($id === (int)$me['id']) {
        flash('Bạn không thể đổi quyền của chính mình.', 'warn');
    } elseif ($id > 0) {
        $pdo->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role, $id]);
        flash('Đã cập nhật phân quyền.');
    }
    redirect(url('admin/users.php'));
}

$q = trim($_GET['q'] ?? '');
$args = []; $w = '';
if ($q !== '') { $w = 'WHERE u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?'; $args = ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%']; }
$st = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS orders,
                            (SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=u.id AND payment_status='paid' AND status<>'cancelled') AS spent
                     FROM users u $w ORDER BY u.id DESC");
$st->execute($args);
$users = $st->fetchAll();

$adminPage = 'users';
$pageTitle = 'Người dùng';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-top"><h2>Người dùng <span class="muted" style="font-size:1rem">(<?= count($users) ?>)</span></h2></div>
<form class="filters" method="get">
  <input class="input grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Tìm tên, email, số điện thoại…">
  <button class="btn btn-primary btn-sm" type="submit">Tìm</button>
  <?php if ($q !== ''): ?><a class="btn btn-outline btn-sm" href="users.php">Xoá lọc</a><?php endif; ?>
</form>
<div class="table-wrap"><table class="tbl">
  <tr><th>Người dùng</th><th>Điện thoại</th><th>Ngày tạo</th><th>Đơn</th><th>Đã chi</th><th>Quyền</th></tr>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><div class="toggle-row"><span class="avatar"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span><div><b><?= e($u['name']) ?></b><br><span class="muted" style="font-size:.85rem"><?= e($u['email']) ?></span></div></div></td>
      <td><?= e($u['phone'] ?? '') ?></td>
      <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
      <td><?= (int)$u['orders'] ?></td>
      <td><?= money($u['spent']) ?></td>
      <td>
        <?php if ((int)$u['id'] === (int)$me['id']): ?>
          <span class="badge badge-info">Quản trị (bạn)</span>
        <?php else: ?>
          <form class="inline-form" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <select class="input" name="role"><option value="customer" <?= $u['role'] === 'customer' ? 'selected' : '' ?>>Khách hàng</option><option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Quản trị</option></select>
            <button class="btn btn-outline btn-sm" type="submit">Lưu</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
