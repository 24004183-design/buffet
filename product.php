<?php
require_once __DIR__ . '/includes/config.php';
$id = max(0, (int)($_GET['id'] ?? 0));
$st = db()->prepare("SELECT * FROM products WHERE id=? AND type='souvenir' AND active=1 LIMIT 1");
$st->execute([$id]);
$product = $st->fetch();
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy sản phẩm';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="card"><h2>Không tìm thấy sản phẩm</h2><p class="muted">Sản phẩm không tồn tại hoặc hiện đang ẩn.</p><a class="btn btn-primary" href="' . e(url('index.php#qua-luu-niem')) . '">Quay lại quà lưu niệm</a></div></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$pageTitle = $product['name'];
require __DIR__ . '/includes/header.php';
$image = (string)($product['image_url'] ?? '');
$imageSrc = $image === '' ? '' : (preg_match('~^https?://~i', $image) ? $image : url($image));
?>
<section class="section product-detail-section">
  <div class="container">
    <a class="product-back" href="<?= e(url('index.php#qua-luu-niem')) ?>">← Quay lại quà lưu niệm</a>
    <div class="product-detail card">
      <div class="product-detail-image">
        <?php if ($imageSrc): ?><img src="<?= e($imageSrc) ?>" alt="<?= e($product['name']) ?>"><?php else: ?><div class="souvenir-placeholder">🎁</div><?php endif; ?>
      </div>
      <div class="product-detail-content">
        <?php if (!empty($product['badge'])): ?><span class="chip"><?= e($product['badge']) ?></span><?php endif; ?>
        <h1><?= e($product['name']) ?></h1>
        <div class="product-detail-price"><?= money($product['price']) ?></div>
        <p class="product-detail-description"><?= nl2br(e($product['description'] ?: 'Sản phẩm quà lưu niệm đặc biệt dành cho bạn.')) ?></p>
        <div class="product-detail-actions">
          <a class="btn btn-primary" href="tel:<?= e(preg_replace('/[^0-9+]/', '', app_phone())) ?>">Mua ngay · Gọi <?= e(app_phone()) ?></a>
          <a class="btn btn-outline" href="<?= e(url('index.php#qua-luu-niem')) ?>">Xem sản phẩm khác</a>
        </div>
        <p class="muted product-detail-note">Liên hệ hotline để xác nhận hàng, phí giao hàng và cách thanh toán.</p>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
