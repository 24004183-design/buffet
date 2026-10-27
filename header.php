<?php
require_once __DIR__ . '/config.php';
$_u = current_user();
$_cur = basename($_SERVER['SCRIPT_NAME'] ?? '');
$_nav = function (string $file, string $label) use ($_cur) {
    return '<a href="' . e(url($file)) . '"' . ($_cur === $file ? ' class="active"' : '') . '>' . e($label) . '</a>';
};
?><!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($pageTitle ?? app_name()) . ' · ' . app_name()) ?></title>
  <meta name="description" content="Đặt vé buffet nướng, lẩu & hải sản online. Giá từ 169.000₫, thêm buffet nước uống 39.000₫.">
  <meta name="theme-color" content="#ff5a36">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E🔥%3C/text%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('style.css')) ?>">
</head>
<body>
<header class="site-header" id="top">
  <div class="container nav">
    <a class="brand" href="<?= e(url('index.php')) ?>"><span class="brand-mark">🔥</span><span><?= e(app_name()) ?></span></a>
    <button class="nav-toggle" type="button" aria-label="Mở menu" aria-expanded="false" data-nav-toggle><span></span><span></span><span></span></button>
    <nav class="nav-links" data-nav>
      <?= $_nav('index.php', 'Trang chủ') ?>
      <a href="<?= e(url('index.php#ve')) ?>">Bảng giá</a>
      <a href="<?= e(url('index.php#quy-trinh')) ?>">Quy trình</a>
      <a href="<?= e(url('index.php#faq')) ?>">Hỏi đáp</a>
      <?php if ($_u): ?>
        <?= $_nav('orders.php', 'Đơn của tôi') ?>
        <?php if ($_u['role'] === 'admin'): ?><a href="<?= e(url('admin/index.php')) ?>">Quản trị</a><?php endif; ?>
        <span class="nav-user" title="<?= e($_u['email']) ?>"><span class="avatar"><?= e(mb_strtoupper(mb_substr($_u['name'], 0, 1))) ?></span><?= e($_u['name']) ?></span>
        <a class="btn btn-outline btn-sm" href="<?= e(url('logout.php')) ?>">Đăng xuất</a>
      <?php else: ?>
        <a href="<?= e(url('login.php')) ?>">Đăng nhập</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('register.php')) ?>">Đăng ký</a>
      <?php endif; ?>
      <a class="btn btn-primary btn-sm" href="<?= e(url('checkout.php')) ?>">Đặt vé ngay</a>
    </nav>
  </div>
</header>
<?php $_fl = flashes(); if ($_fl): ?>
<div class="container toasts" data-toasts>
  <?php foreach ($_fl as $_f): ?><div class="alert alert-<?= e($_f['t']) ?>" role="status"><?= e($_f['m']) ?><button type="button" class="alert-x" aria-label="Đóng" data-close>×</button></div><?php endforeach; ?>
</div>
<?php endif; ?>
<main>
