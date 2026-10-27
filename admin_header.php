<?php
require_once __DIR__ . '/config.php';
$_admin = require_admin();
$adminPage = $adminPage ?? '';
$_items = [
    'dashboard' => ['index.php',    '📊', 'Tổng quan'],
    'orders'    => ['orders.php',   '🧾', 'Đơn hàng'],
    'products'  => ['products.php', '🎟️', 'Vé & giá'],
    'users'     => ['users.php',    '👥', 'Người dùng'],
    'settings'  => ['settings.php', '⚙️', 'Cấu hình website'],
];
?><!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title><?= e(($pageTitle ?? 'Quản trị') . ' · ' . app_name()) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('style.css')) ?>">
</head>
<body class="admin-body">
<div class="admin">
  <aside class="admin-side">
    <a class="brand" href="<?= e(url('admin/index.php')) ?>"><span class="brand-mark">🔥</span><span><?= e(app_name()) ?></span></a>
    <?php foreach ($_items as $k => [$file, $ico, $label]): ?>
      <a class="item <?= $adminPage === $k ? 'active' : '' ?>" href="<?= e(url('admin/' . $file)) ?>"><span><?= $ico ?></span><?= e($label) ?></a>
    <?php endforeach; ?>
    <div class="spacer"></div>
    <a class="item" href="<?= e(url('index.php')) ?>"><span>🌐</span>Xem website</a>
    <a class="item" href="<?= e(url('logout.php')) ?>"><span>🚪</span>Đăng xuất</a>
  </aside>
  <div class="admin-main">
    <?php foreach (flashes() as $_f): ?><div class="alert alert-<?= e($_f['t']) ?>"><?= e($_f['m']) ?></div><?php endforeach; ?>
