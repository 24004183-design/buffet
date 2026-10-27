<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();
$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'toggle' && $id > 0) {
        $pdo->prepare('UPDATE products SET active = 1 - active WHERE id=?')->execute([$id]);
        flash('Đã đổi trạng thái hiển thị.');
        redirect(url('admin/products.php'));
    }
    if ($action === 'save') {
        $d = [
            'name' => trim($_POST['name'] ?? ''),
            'type' => in_array(($_POST['type'] ?? ''), ['drink','souvenir'], true) ? (string)$_POST['type'] : 'ticket',
            'price' => (int)($_POST['price'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
            'features' => trim(str_replace("\r", '', $_POST['features'] ?? '')),
            'badge' => trim($_POST['badge'] ?? ''),
            'image_url' => trim($_POST['image_url'] ?? ''),
            'product_url' => trim($_POST['product_url'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
        if (mb_strlen($d['name']) < 2) { $errors[] = 'Tên sản phẩm quá ngắn.'; }
        if ($d['image_url'] !== '' && !filter_var($d['image_url'], FILTER_VALIDATE_URL)) { $errors[] = 'Đường dẫn ảnh phải là URL hợp lệ.'; }
        if ($d['product_url'] !== '' && !preg_match('~^(https?://|/)~i', $d['product_url'])) { $errors[] = 'Đường dẫn sản phẩm phải bắt đầu bằng http://, https:// hoặc /. '; }
        if ($d['price'] < 0 || $d['price'] > 100000000) { $errors[] = 'Giá không hợp lệ.'; }
        if (!$errors) {
            if ($id > 0) {
                $pdo->prepare('UPDATE products SET name=?,type=?,price=?,description=?,features=?,badge=?,image_url=?,product_url=?,sort_order=?,active=? WHERE id=?')
                    ->execute([...array_values($d), $id]);
                flash('Đã cập nhật sản phẩm.');
            } else {
                $pdo->prepare('INSERT INTO products (name,type,price,description,features,badge,image_url,product_url,sort_order,active) VALUES (?,?,?,?,?,?,?,?,?,?)')
                    ->execute(array_values($d));
                flash('Đã thêm sản phẩm mới.');
            }
            redirect(url('admin/products.php'));
        }
        $edit = $d + ['id' => $id];
    }
}

$products = $pdo->query('SELECT * FROM products ORDER BY sort_order, price')->fetchAll();
if (!isset($edit)) {
    $editId = (int)($_GET['edit'] ?? 0);
    $edit = ['id' => 0, 'name' => '', 'type' => 'ticket', 'price' => 0, 'description' => '', 'features' => '', 'badge' => '', 'image_url' => '', 'product_url' => '', 'sort_order' => count($products) + 1, 'active' => 1];
    if ($editId > 0) { foreach ($products as $p) { if ((int)$p['id'] === $editId) { $edit = $p; } } }
}

$adminPage = 'products';
$pageTitle = 'Vé & giá';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-top"><h2>Vé & bảng giá</h2><a class="btn btn-primary btn-sm" href="products.php#form">+ Thêm sản phẩm</a></div>
<?php foreach ($errors as $err): ?><div class="alert alert-bad"><?= e($err) ?></div><?php endforeach; ?>

<div class="table-wrap"><table class="tbl">
  <tr><th>#</th><th>Tên</th><th>Loại</th><th>Giá</th><th>Nhãn</th><th>Hiển thị</th><th></th></tr>
  <?php foreach ($products as $p): ?>
    <tr>
      <td><?= (int)$p['sort_order'] ?></td>
      <td><?php if (!empty($p['image_url'])): ?><img src="<?= e($p['image_url']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:10px;vertical-align:middle;margin-right:8px"><?php endif; ?><b><?= e($p['name']) ?></b><br><span class="muted" style="font-size:.85rem"><?= e($p['description']) ?></span><?php if (!empty($p['product_url'])): ?><br><a href="<?= e($p['product_url']) ?>" target="_blank" rel="noopener">Mở đường dẫn sản phẩm ↗</a><?php endif; ?></td>
      <td><?= $p['type'] === 'drink' ? '🥤 Nước uống' : ($p['type'] === 'souvenir' ? '🎁 Quà lưu niệm' : '🎟️ Vé buffet') ?></td>
      <td><b><?= money($p['price']) ?></b></td>
      <td><?= $p['badge'] ? '<span class="chip">' . e($p['badge']) . '</span>' : '<span class="muted">—</span>' ?></td>
      <td><?= $p['active'] ? '<span class="badge badge-ok">Đang bán</span>' : '<span class="badge badge-muted">Đã ẩn</span>' ?></td>
      <td style="white-space:nowrap">
        <a class="btn btn-outline btn-sm" href="products.php?edit=<?= (int)$p['id'] ?>#form">Sửa</a>
        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button class="btn btn-sm <?= $p['active'] ? 'btn-danger' : 'btn-outline' ?>" type="submit"><?= $p['active'] ? 'Ẩn' : 'Hiện' ?></button></form>
      </td>
    </tr>
  <?php endforeach; ?>
</table></div>

<div class="card" id="form" style="margin-top:1.4rem">
  <h3><?= $edit['id'] ? 'Sửa sản phẩm #' . (int)$edit['id'] : 'Thêm sản phẩm mới' ?></h3>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <div class="row2">
      <div class="field"><label>Tên sản phẩm</label><input class="input" name="name" value="<?= e($edit['name']) ?>" required></div>
      <div class="field"><label>Giá (₫)</label><input class="input" type="number" name="price" min="0" step="1000" value="<?= (int)$edit['price'] ?>" required></div>
    </div>
    <div class="row2">
      <div class="field"><label>Loại</label>
        <select class="input" name="type"><option value="ticket" <?= $edit['type'] === 'ticket' ? 'selected' : '' ?>>Vé buffet</option><option value="drink" <?= $edit['type'] === 'drink' ? 'selected' : '' ?>>Nước uống (add-on)</option><option value="souvenir" <?= $edit['type'] === 'souvenir' ? 'selected' : '' ?>>Quà lưu niệm</option></select></div>
      <div class="field"><label>Nhãn nổi bật (tuỳ chọn)</label><input class="input" name="badge" value="<?= e($edit['badge']) ?>" placeholder="VD: 🔥 Siêu hot hoặc 🛍️ Mua nhiều"></div>
    </div>
    <div class="row2"><div class="field"><label>URL ảnh sản phẩm</label><input class="input" type="url" name="image_url" value="<?= e($edit['image_url'] ?? '') ?>" placeholder="https://.../anh-san-pham.jpg"></div><div class="field"><label>Đường dẫn xem/mua sản phẩm</label><input class="input" name="product_url" value="<?= e($edit['product_url'] ?? '') ?>" placeholder="https://shop... hoặc /san-pham/qua-luu-niem"></div></div>
    <div class="field"><label>Mô tả ngắn</label><input class="input" name="description" value="<?= e($edit['description']) ?>"></div>
    <div class="field"><label>Quyền lợi (mỗi dòng một ý)</label><textarea class="input" name="features" rows="4"><?= e($edit['features']) ?></textarea></div>
    <div class="row2">
      <div class="field"><label>Thứ tự hiển thị</label><input class="input" type="number" name="sort_order" value="<?= (int)$edit['sort_order'] ?>"></div>
      <div class="field toggle-row" style="align-self:end;padding-bottom:.8rem"><input type="checkbox" id="act" name="active" value="1" <?= $edit['active'] ? 'checked' : '' ?> style="accent-color:var(--brand);width:18px;height:18px"><label for="act" style="margin:0">Đang bán</label></div>
    </div>
    <button class="btn btn-primary" type="submit">Lưu sản phẩm</button>
    <?php if ($edit['id']): ?><a class="btn btn-outline" href="products.php">Huỷ</a><?php endif; ?>
  </form>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
