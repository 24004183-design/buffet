<?php
require_once __DIR__ . '/includes/config.php';
if (current_user()) { redirect(url('index.php')); }

$errors = [];
$f = ['name' => '', 'email' => '', 'phone' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f['name']  = trim($_POST['name'] ?? '');
    $f['email'] = strtolower(trim($_POST['email'] ?? ''));
    $f['phone'] = preg_replace('/\s+/', '', $_POST['phone'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');

    if (mb_strlen($f['name']) < 2) { $errors[] = 'Vui lòng nhập họ tên.'; }
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email không hợp lệ.'; }
    if (!preg_match('/^(0|\+84)\d{9,10}$/', $f['phone'])) { $errors[] = 'Số điện thoại không hợp lệ.'; }
    if (strlen($pass) < 6) { $errors[] = 'Mật khẩu cần ít nhất 6 ký tự.'; }
    if ($pass !== $pass2) { $errors[] = 'Mật khẩu nhập lại không khớp.'; }

    if (!$errors) {
        $chk = db()->prepare('SELECT 1 FROM users WHERE email=?');
        $chk->execute([$f['email']]);
        if ($chk->fetch()) {
            $errors[] = 'Email này đã được đăng ký.';
        } else {
            db()->prepare("INSERT INTO users (name,email,phone,password,role) VALUES (?,?,?,?, 'customer')")
                ->execute([$f['name'], $f['email'], $f['phone'], password_hash($pass, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)db()->lastInsertId();
            flash('Đăng ký thành công. Chào mừng bạn đến với ' . APP_NAME . '!');
            redirect(url('checkout.php'));
        }
    }
}
$pageTitle = 'Đăng ký';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="form-wrap card">
    <h2>Tạo tài khoản</h2>
    <p class="muted">Miễn phí, chỉ mất 30 giây.</p>
    <?php foreach ($errors as $err): ?><div class="alert alert-bad"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="field"><label>Họ và tên</label><input class="input" name="name" value="<?= e($f['name']) ?>" required autofocus></div>
      <div class="row2">
        <div class="field"><label>Email</label><input class="input" type="email" name="email" value="<?= e($f['email']) ?>" required></div>
        <div class="field"><label>Số điện thoại</label><input class="input" name="phone" value="<?= e($f['phone']) ?>" required></div>
      </div>
      <div class="row2">
        <div class="field"><label>Mật khẩu</label><input class="input" type="password" name="password" minlength="6" required></div>
        <div class="field"><label>Nhập lại mật khẩu</label><input class="input" type="password" name="password2" minlength="6" required></div>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Đăng ký</button>
    </form>
    <p class="muted" style="text-align:center;margin:1rem 0 0">Đã có tài khoản? <a href="<?= e(url('login.php')) ?>">Đăng nhập</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
