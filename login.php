<?php
require_once __DIR__ . '/includes/config.php';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '');
if (current_user()) { redirect($next ?: url('index.php')); }

$error = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $st = db()->prepare('SELECT * FROM users WHERE email=?');
    $st->execute([$email]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        flash('Đăng nhập thành công.');
        redirect($next ?: ($u['role'] === 'admin' ? url('admin/index.php') : url('index.php')));
    }
    $error = 'Email hoặc mật khẩu không đúng.';
}
$pageTitle = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="form-wrap card">
    <h2>Đăng nhập</h2>
    <p class="muted">Chào mừng bạn quay lại!</p>
    <?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <div class="field"><label>Email</label><input class="input" type="email" name="email" value="<?= e($email) ?>" required autofocus></div>
      <div class="field"><label>Mật khẩu</label><input class="input" type="password" name="password" required></div>
      <button class="btn btn-primary btn-block" type="submit">Đăng nhập</button>
    </form>
    <p class="muted" style="text-align:center;margin-top:1rem">Chưa có tài khoản? <a href="<?= url('register.php') ?>">Đăng ký</a></p>
    <div class="alert alert-info" style="font-size:.85rem">
      <b>Tài khoản demo</b><br>
      Admin: admin@buffet.vn / admin123<br>
      Khách: khach@demo.vn / 123456
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
