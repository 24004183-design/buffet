<?php
$pageTitle = 'Vé buffet & nước uống';
require __DIR__ . '/includes/header.php';
$tickets = db()->query("SELECT * FROM products WHERE active=1 AND type='ticket' ORDER BY sort_order, price")->fetchAll();
$drink = db()->query("SELECT * FROM products WHERE active=1 AND type='drink' ORDER BY sort_order LIMIT 1")->fetch();
$souvenirs = db()->query("SELECT * FROM products WHERE active=1 AND type='souvenir' ORDER BY sort_order, id")->fetchAll();
$siteInfo = app_setting('site_info', 'Buffet nướng, lẩu & hải sản không giới hạn. Đặt vé online, tới là ăn.');
?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="chip">🎉 Đặt vé online – tới là ăn</span>
      <h1>Ăn <em>thả ga</em> – nướng, lẩu & hải sản không giới hạn</h1>
      <p class="lead"><?= e($siteInfo) ?></p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="<?= url('checkout.php') ?>">Đặt vé ngay</a>
        <a class="btn btn-outline" href="#ve">Xem bảng giá</a>
      </div>
      <div class="hero-stats">
        <div><b>100+</b><span class="muted">món mỗi ngày</span></div>
        <div><b>4.8★</b><span class="muted">khách đánh giá</span></div>
        <div><b>120'</b><span class="muted">thời gian dùng bữa</span></div>
      </div>
    </div>
    <div class="hero-card">
      <div class="emoji-row">🥩🦐🍣🍢</div>
      <h3>Các gói vé</h3>
      <p class="muted">Giá đã gồm VAT, trẻ em dưới 1m cao miễn phí khi đi cùng người lớn.</p>
      <div class="price-chips">
        <?php foreach ($tickets as $t): ?><span class="chip"><?= e($t['name']) ?> · <?= number_format($t['price'] / 1000) ?>k</span><?php endforeach; ?>
        <?php if ($drink): ?><span class="chip">🥤 Nước uống · <?= number_format($drink['price'] / 1000) ?>k</span><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="section" id="ve">
  <div class="container">
    <div class="section-head">
      <h2>Chọn gói buffet của bạn</h2>
      <p class="muted">Bốn mức giá cho mọi nhu cầu – từ bữa ăn gia đình đến buổi tiệc sang trọng.</p>
    </div>
    <div class="grid-4">
      <?php foreach ($tickets as $t): ?>
        <article class="card ticket <?= $t['badge'] ? 'featured' : '' ?>">
          <?php if ($t['badge']): ?><span class="tag"><?= e($t['badge']) ?></span><?php endif; ?>
          <h3><?= e($t['name']) ?></h3>
          <p class="muted"><?= e($t['description']) ?></p>
          <div class="price"><?= number_format($t['price'], 0, ',', '.') ?>₫ <small>/ người</small></div>
          <ul>
            <?php foreach (array_filter(explode("\n", (string)$t['features'])) as $f): ?><li><?= e(trim($f)) ?></li><?php endforeach; ?>
          </ul>
          <a class="btn btn-primary btn-block" href="<?= url('checkout.php?add=' . (int)$t['id']) ?>">Đặt vé này</a>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if ($drink): ?>
    <div class="drink-banner">
      <div>
        <h3>🥤 <?= e($drink['name']) ?></h3>
        <p><?= e($drink['description']) ?></p>
      </div>
      <div style="text-align:right">
        <div class="drink-price"><?= number_format($drink['price'], 0, ',', '.') ?>₫ <small style="font-size:.9rem;font-weight:500">/ người</small></div>
        <a class="btn btn-primary btn-sm" href="<?= url('checkout.php?add=' . (int)$drink['id']) ?>">Thêm vào đơn</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($souvenirs): ?>
<section class="section souvenir-section" id="qua-luu-niem">
  <div class="container">
    <div class="section-head">
      <span class="chip">🎁 Quà tặng đặc biệt</span>
      <h2><?= e(app_setting('souvenir_title', 'Quà lưu niệm')) ?></h2>
      <p class="muted"><?= e(app_setting('souvenir_info', 'Khám phá các món quà nhỏ xinh dành tặng người thân và bạn bè.')) ?></p>
    </div>
    <div class="grid-4">
      <?php foreach ($souvenirs as $item): ?>
      <article class="card souvenir-card">
        <?php if (!empty($item['image_url'])): ?><img src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>" loading="lazy"><?php else: ?><div class="souvenir-placeholder">🎁</div><?php endif; ?>
        <?php if (!empty($item['badge'])): ?><span class="chip"><?= e($item['badge']) ?></span><?php endif; ?>
        <h3><?= e($item['name']) ?></h3>
        <p class="muted"><?= e($item['description']) ?></p>
        <strong class="souvenir-price"><?= money($item['price']) ?></strong>
        <?php if (!empty($item['product_url'])): ?><a class="btn btn-primary btn-block" href="<?= e($item['product_url']) ?>" <?= preg_match('~^https?://~i', $item['product_url']) ? 'target="_blank" rel="noopener"' : '' ?>>Xem / mua sản phẩm ↗</a><?php else: ?><span class="muted souvenir-note">Thêm đường dẫn mua hàng trong trang quản trị.</span><?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section" id="quy-trinh" style="background:#fff">
  <div class="container">
    <div class="section-head"><h2>Đặt vé chỉ với 4 bước</h2></div>
    <div class="grid-4 steps">
      <div class="step"><h4>Đăng ký / Đăng nhập</h4><p class="muted">Tạo tài khoản miễn phí để lưu lịch sử đơn hàng.</p></div>
      <div class="step"><h4>Chọn vé & giờ ăn</h4><p class="muted">Chọn gói, thêm nước uống, chọn ngày và khung giờ.</p></div>
      <div class="step"><h4>Thanh toán</h4><p class="muted">Chuyển khoản QR, MoMo hoặc trả tại quầy.</p></div>
      <div class="step"><h4>Nhận mã đơn</h4><p class="muted">Đưa mã đơn cho nhân viên và vào bàn ngay.</p></div>
    </div>
  </div>
</section>

<section class="section" id="faq">
  <div class="container">
    <div class="section-head"><h2>Câu hỏi thường gặp</h2></div>
    <div class="grid-3">
      <div class="card"><h4>Vé có hoàn huỷ được không?</h4><p class="muted">Bạn có thể huỷ đơn chưa thanh toán trong mục "Đơn của tôi". Đơn đã thanh toán vui lòng liên hệ hotline.</p></div>
      <div class="card"><h4>Mua nước uống riêng được không?</h4><p class="muted">Vé nước uống là add-on, đi kèm vé buffet và số lượng không vượt quá số vé buffet.</p></div>
      <div class="card"><h4>Thời gian dùng bữa?</h4><p class="muted">Mỗi lượt 120 phút. Vui lòng đến đúng khung giờ đã chọn để được giữ bàn.</p></div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
