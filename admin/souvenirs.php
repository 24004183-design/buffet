<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();
$pdo = db();
$errors = [];
$uploadDir = __DIR__ . '/../uploads/souvenirs';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'toggle' && $id > 0) {
        $pdo->prepare("UPDATE products SET active = 1 - active WHERE id=? AND type='souvenir'")->execute([$id]);
        flash('Đã cập nhật trạng thái quà lưu niệm.');
        redirect(url('admin/souvenirs.php'));
    }
    if ($action === 'delete' && $id > 0) {
        $st = $pdo->prepare("SELECT image_url FROM products WHERE id=? AND type='souvenir'");
        $st->execute([$id]); $old = $st->fetch();
        if ($old) {
            $pdo->prepare("DELETE FROM products WHERE id=? AND type='souvenir'")->execute([$id]);
            $image = (string)($old['image_url'] ?? '');
            if ($image !== '' && str_starts_with($image, 'uploads/souvenirs/')) {
                $path = __DIR__ . '/../' . $image;
                if (is_file($path)) @unlink($path);
            }
            flash('Đã xóa quà lưu niệm.');
        }
        redirect(url('admin/souvenirs.php'));
    }
    if ($action === 'save') {
        $name = trim((string)($_POST['name'] ?? ''));
        $price = (int)($_POST['price'] ?? 0);
        $description = trim((string)($_POST['description'] ?? ''));
        $badge = trim((string)($_POST['badge'] ?? ''));
        $productUrl = trim((string)($_POST['product_url'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;
        $imageUrl = trim((string)($_POST['current_image'] ?? ''));

        if (mb_strlen($name) < 2) $errors[] = 'Tên sản phẩm phải có ít nhất 2 ký tự.';
        if ($price < 0 || $price > 100000000) $errors[] = 'Giá sản phẩm không hợp lệ.';
        if ($productUrl !== '' && !preg_match('~^(https?://|/)~i', $productUrl)) $errors[] = 'Đường dẫn mua hàng phải bắt đầu bằng http://, https:// hoặc /. ';

        if (!empty($_FILES['image_file']['name'])) {
            $file = $_FILES['image_file'];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = 'Tải ảnh lên thất bại. Hãy thử ảnh nhỏ hơn.';
            } elseif (($file['size'] ?? 0) > 5 * 1024 * 1024) {
                $errors[] = 'Ảnh tối đa 5 MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', 'image/gif'=>'gif'];
                if (!isset($allowed[$mime])) {
                    $errors[] = 'Chỉ nhận ảnh JPG, PNG, WEBP hoặc GIF.';
                } else {
                    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
                    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                        $errors[] = 'Không lưu được ảnh. Kiểm tra quyền ghi thư mục uploads.';
                    } else {
                        $imageUrl = 'uploads/souvenirs/' . $filename;
                    }
                }
            }
        }

        if (!$errors) {
            if ($id > 0) {
                $pdo->prepare("UPDATE products SET name=?,type='souvenir',price=?,description=?,features='',badge=?,image_url=?,product_url=?,sort_order=?,active=? WHERE id=?")
                    ->execute([$name, $price, $description, $badge, $imageUrl, $productUrl, $sortOrder, $active, $id]);
                flash('Đã cập nhật quà lưu niệm.');
            } else {
                $pdo->prepare("INSERT INTO products (name,type,price,description,features,badge,image_url,product_url,sort_order,active) VALUES (?,'souvenir',?,?, '',?,?,?,?,?)")
                    ->execute([$name, $price, $description, $badge, $imageUrl, $productUrl, $sortOrder, $active]);
                flash('Đã thêm quà lưu niệm.');
            }
            redirect(url('admin/souvenirs.php'));
        }
        $edit = ['id'=>$id, 'name'=>$name, 'price'=>$price, 'description'=>$description, 'badge'=>$badge, 'image_url'=>$imageUrl, 'product_url'=>$productUrl, 'sort_order'=>$sortOrder, 'active'=>$active];
    }
}

$products = $pdo->query("SELECT * FROM products WHERE type='souvenir' ORDER BY sort_order, id DESC")->fetchAll();
if (!isset($edit)) {
    $edit = ['id'=>0, 'name'=>'', 'price'=>0, 'description'=>'', 'badge'=>'', 'image_url'=>'', 'product_url'=>'', 'sort_order'=>count($products)+1, 'active'=>1];
    $editId = (int)($_GET['edit'] ?? 0);
    if ($editId > 0) {
        $st = $pdo->prepare("SELECT * FROM products WHERE id=? AND type='souvenir'"); $st->execute([$editId]);
        $row = $st->fetch(); if ($row) $edit = $row;
    }
}
$adminPage = 'souvenirs';
$pageTitle = 'Quản lý quà lưu niệm';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-top">
  <div><h2>🎁 Quản lý quà lưu niệm</h2><p class="muted">Thêm ảnh, chỉnh giá, gắn nhãn Siêu hot / Mua nhiều và đưa sản phẩm lên trang chủ.</p></div>
  <a class="btn btn-primary btn-sm" href="souvenirs.php#form">+ Thêm quà lưu niệm</a>
</div>
<?php foreach ($errors as $err): ?><div class="alert alert-bad"><?= e($err) ?></div><?php endforeach; ?>
<div class="souvenir-admin-stats"><div class="card"><span class="muted">Tổng sản phẩm</span><strong><?= count($products) ?></strong></div><div class="card"><span class="muted">Đang hiển thị</span><strong><?= count(array_filter($products, fn($p)=>(int)$p['active']===1)) ?></strong></div><div class="card"><span class="muted">Đang ẩn</span><strong><?= count(array_filter($products, fn($p)=>(int)$p['active']!==1)) ?></strong></div></div>
<div class="table-wrap"><table class="tbl">
<tr><th>Ảnh</th><th>Sản phẩm</th><th>Giá</th><th>Nhãn</th><th>Trạng thái</th><th>Thao tác</th></tr>
<?php if (!$products): ?><tr><td colspan="6" class="muted" style="text-align:center;padding:2rem">Chưa có quà lưu niệm. Bấm “Thêm quà lưu niệm” để tạo sản phẩm đầu tiên.</td></tr><?php endif; ?>
<?php foreach ($products as $p): ?><tr>
<td><?php if (!empty($p['image_url'])): ?><img src="<?= e(preg_match('~^https?://~i', $p['image_url']) ? $p['image_url'] : url($p['image_url'])) ?>" alt="" class="souvenir-admin-thumb"><?php else: ?><div class="souvenir-admin-thumb souvenir-admin-noimg">🎁</div><?php endif; ?></td>
<td><b><?= e($p['name']) ?></b><div class="muted" style="font-size:.85rem"><?= e($p['description']) ?></div><?php if (!empty($p['product_url'])): ?><a href="<?= e($p['product_url']) ?>" target="_blank" rel="noopener">Mở link mua hàng ↗</a><?php endif; ?></td>
<td><b><?= money($p['price']) ?></b></td><td><?= $p['badge'] ? '<span class="chip">'.e($p['badge']).'</span>' : '<span class="muted">—</span>' ?></td>
<td><?= $p['active'] ? '<span class="badge badge-ok">Đang bán</span>' : '<span class="badge badge-muted">Đã ẩn</span>' ?></td>
<td><div class="souvenir-actions"><a class="btn btn-outline btn-sm" href="souvenirs.php?edit=<?= (int)$p['id'] ?>#form">Sửa</a><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-outline btn-sm" type="submit"><?= $p['active'] ? 'Ẩn' : 'Hiện' ?></button></form><form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Xóa</button></form></div></td>
</tr><?php endforeach; ?>
</table></div>
<div class="card" id="form" style="margin-top:1.5rem">
<h3><?= $edit['id'] ? 'Sửa quà lưu niệm #' . (int)$edit['id'] : 'Thêm quà lưu niệm mới' ?></h3>
<form method="post" enctype="multipart/form-data">
<?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><input type="hidden" name="current_image" value="<?= e($edit['image_url'] ?? '') ?>">
<div class="row2"><div class="field"><label>Tên sản phẩm *</label><input class="input" name="name" value="<?= e($edit['name']) ?>" required></div><div class="field"><label>Giá bán (₫) *</label><input class="input" type="number" name="price" min="0" step="1000" value="<?= (int)$edit['price'] ?>" required></div></div>
<div class="field"><label>Ảnh sản phẩm (JPG, PNG, WEBP, GIF — tối đa 5 MB)</label><input class="input" type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif"><?php if (!empty($edit['image_url'])): ?><small class="muted">Đã có ảnh. Chọn ảnh mới nếu muốn thay.</small><?php endif; ?></div>
<div class="row2"><div class="field"><label>Nhãn sản phẩm</label><select class="input" name="badge"><option value="" <?= empty($edit['badge'])?'selected':'' ?>>Không có nhãn</option><?php foreach (['🔥 Siêu hot','🛍️ Mua nhiều','⭐ Nổi bật','🎁 Quà tặng'] as $badge): ?><option value="<?= e($badge) ?>" <?= ($edit['badge'] ?? '')===$badge?'selected':'' ?>><?= e($badge) ?></option><?php endforeach; ?><?php if (!empty($edit['badge']) && !in_array($edit['badge'], ['🔥 Siêu hot','🛍️ Mua nhiều','⭐ Nổi bật','🎁 Quà tặng'], true)): ?><option selected value="<?= e($edit['badge']) ?>"><?= e($edit['badge']) ?></option><?php endif; ?></select></div><div class="field"><label>Đường dẫn trang mua hàng (tuỳ chọn)</label><input class="input" name="product_url" value="<?= e($edit['product_url'] ?? '') ?>" placeholder="https://shop.vn/san-pham hoặc /san-pham/abc"></div></div>
<div class="field"><label>Mô tả ngắn</label><textarea class="input" name="description" rows="3"><?= e($edit['description']) ?></textarea></div>
<div class="row2"><div class="field"><label>Thứ tự hiển thị</label><input class="input" type="number" name="sort_order" value="<?= (int)$edit['sort_order'] ?>"></div><div class="field toggle-row" style="align-self:end;padding-bottom:.8rem"><input type="checkbox" id="souvenir-active" name="active" value="1" <?= $edit['active'] ? 'checked' : '' ?> style="accent-color:var(--brand);width:18px;height:18px"><label for="souvenir-active" style="margin:0">Hiển thị / đang bán</label></div></div>
<button class="btn btn-primary" type="submit">Lưu quà lưu niệm</button><?php if ($edit['id']): ?> <a class="btn btn-outline" href="souvenirs.php">Hủy sửa</a><?php endif; ?>
</form></div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
