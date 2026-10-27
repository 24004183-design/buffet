</main>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <a class="brand" href="<?= e(url('index.php')) ?>"><span class="brand-mark">🔥</span><span><?= e(app_name()) ?></span></a>
      <p><?= e(app_setting('site_info', 'Buffet nướng, lẩu & hải sản không giới hạn. Đặt vé online, tới là ăn.')) ?></p>
    </div>
    <div>
      <h5>Khám phá</h5>
      <a href="<?= e(url('index.php#ve')) ?>">Bảng giá vé</a>
      <a href="<?= e(url('checkout.php')) ?>">Đặt vé</a>
      <a href="<?= e(url('orders.php')) ?>">Đơn của tôi</a>
    </div>
    <div>
      <h5>Liên hệ</h5>
      <span>📞 Hotline: <b><?= e(app_phone()) ?></b></span>
      <span>🕚 11:00 – 22:00 hằng ngày</span>
      <span>📍 <?= e(app_setting('address', '123 Đường Ẩm Thực, TP. Vĩnh Long')) ?></span>
    </div>
  </div>
  <div class="container footer-bottom">© <?= date('Y') ?> <?= e(app_name()) ?>. Giá đã gồm VAT.</div>
</footer>
<script src="<?= e(asset('app.js')) ?>" defer></script>
</body>
</html>
