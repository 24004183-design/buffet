(function () {
  'use strict';
  var fmt = function (n) { return new Intl.NumberFormat('vi-VN').format(n) + ' ₫'; };

  // Menu mobile
  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Thông báo: đóng tay + tự ẩn
  document.querySelectorAll('[data-toasts] .alert').forEach(function (el) {
    var hide = function () { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(function () { el.remove(); }, 300); };
    var x = el.querySelector('[data-close]');
    if (x) { x.addEventListener('click', hide); }
    setTimeout(hide, 7000);
  });

  // Xác nhận trước khi submit (form[data-confirm])
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!window.confirm(f.getAttribute('data-confirm'))) { e.preventDefault(); } });
  });

  // Trang đặt vé: stepper + tóm tắt đơn hàng
  var form = document.querySelector('form[data-checkout]');
  if (form) {
    var inputs = Array.prototype.slice.call(form.querySelectorAll('input.qty'));
    var lines = document.getElementById('sum-lines');
    var totalEl = document.getElementById('sum-total');
    var warn = document.getElementById('sum-warn');
    var submit = form.querySelector('button[type=submit]');

    var clamp = function (inp) {
      var v = parseInt(inp.value, 10);
      if (isNaN(v) || v < 0) { v = 0; }
      if (v > 50) { v = 50; }
      inp.value = v;
      return v;
    };

    var render = function () {
      var total = 0, tickets = 0, drinks = 0, html = '';
      inputs.forEach(function (inp) {
        var q = clamp(inp), price = parseInt(inp.dataset.price, 10) || 0;
        if (q > 0) {
          total += q * price;
          if (inp.dataset.type === 'drink') { drinks += q; } else { tickets += q; }
          var name = document.createElement('span');
          name.textContent = inp.dataset.name;
          html += '<div class="sum-line"><span>' + name.innerHTML + ' <small>× ' + q + '</small></span><span>' + fmt(q * price) + '</span></div>';
        }
      });
      lines.innerHTML = html || '<div class="sum-empty">Chưa chọn vé nào. Hãy thêm ít nhất 1 vé buffet.</div>';
      totalEl.textContent = fmt(total);

      var msg = '';
      if (drinks > 0 && tickets === 0) { msg = 'Vé nước uống cần đi kèm ít nhất 1 vé buffet.'; }
      else if (drinks > tickets) { msg = 'Số vé nước uống không được vượt quá số vé buffet (' + tickets + ').'; }
      warn.textContent = msg;
      warn.style.display = msg ? 'block' : 'none';
      var bad = tickets === 0 || !!msg;
      submit.disabled = bad;
      submit.style.opacity = bad ? '.5' : '';
      submit.style.cursor = bad ? 'not-allowed' : '';
    };

    form.addEventListener('click', function (e) {
      var inc = e.target.closest('[data-inc]'), dec = e.target.closest('[data-dec]');
      if (!inc && !dec) { return; }
      var inp = (inc || dec).parentElement.querySelector('input.qty');
      inp.value = (parseInt(inp.value, 10) || 0) + (inc ? 1 : -1);
      render();
    });
    inputs.forEach(function (inp) { inp.addEventListener('input', render); inp.addEventListener('change', render); });
    render();
  }
})();
