<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();
$pdo = db();
$fields = [
    'site_name' => ['Tên website', 'text'],
    'site_info' => ['Thông tin giới thiệu ngắn', 'textarea'],
    'phone' => ['Số điện thoại / hotline', 'text'],
    'address' => ['Địa chỉ', 'text'],
    'souvenir_title' => ['Tiêu đề khu quà lưu niệm', 'text'],
    'souvenir_info' => ['Mô tả khu quà lưu niệm', 'textarea'],
];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($fields as $key => $meta) {
        $value = trim((string)($_POST[$key] ?? ''));
        if ($key === 'site_name' && $value === '') $errors[] = 'Tên website không được để trống.';
        if (mb_strlen($value) > 1000) $errors[] = $meta[0] . ' quá dài.';
    }
    if (!$errors) {
        $st = $pdo->prepare('UPDATE site_settings SET setting_value=? WHERE setting_key=?');
        foreach ($fields as $key => $meta) $st->execute([trim((string)($_POST[$key] ?? '')), $key]);
        flash('Đã lưu cấu hình website.');
        redirect(url('admin/settings.php'));
    }
}
$values = [];
foreach ($fields as $key => $meta) $values[$key] = $_POST[$key] ?? app_setting($key);
$adminPage = 'settings';
$pageTitle = 'Cấu hình website';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-top"><div><h2>Cấu hình website</h2><p class="muted">Thay đổi tên, thông tin liên hệ và phần giới thiệu ngay tại đây.</p></div></div>
<?php foreach ($errors as $err): ?><div class="alert alert-bad"><?= e($err) ?></div><?php endforeach; ?>
<div class="card settings-card">
  <form method="post">
    <?= csrf_field() ?>
    <?php foreach ($fields as $key => [$label, $type]): ?>
      <div class="field">
        <label for="<?= e($key) ?>"><?= e($label) ?></label>
        <?php if ($type === 'textarea'): ?>
          <textarea class="input" id="<?= e($key) ?>" name="<?= e($key) ?>" rows="3"><?= e($values[$key]) ?></textarea>
        <?php else: ?>
          <input class="input" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($values[$key]) ?>" required="<?= $key === 'site_name' ? 'required' : 'false' ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <button class="btn btn-primary" type="submit">Lưu cấu hình</button>
    <a class="btn btn-outline" href="<?= e(url('index.php')) ?>">Xem website</a>
  </form>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
