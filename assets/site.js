/* رفتارهای کوچک سایت — همه «بهبود تدریجی»؛ بدون جاوااسکریپت هم صفحه سالم است.
 *
 *  ۱. تاریخ بروزرسانی: در زمان ساخت نوشته می‌شود و اینجا با تاریخ روزِ
 *     بازدید (تقویم شمسی مرورگر) جایگزین می‌شود تا «امروز» همیشه امروز باشد.
 *  ۲. جست‌وجوی سراسری صفحه‌ی قیمت: یک کادر، همه‌ی جدول‌ها.
 *  ۳. گالری صفحه‌ی محصول: کلیک روی بندانگشتی، تصویر اصلی را عوض می‌کند.
 */
(function () {
  'use strict';
  var FA = '۰۱۲۳۴۵۶۷۸۹', AR = '٠١٢٣٤٥٦٧٨٩';

  function faDigits(s) { return String(s).replace(/[0-9]/g, function (d) { return FA[+d]; }); }

  /* ---- ۱. تاریخ زنده ---- */
  function liveDate() {
    var els = document.querySelectorAll('[data-live-date],[data-live-short]');
    if (!els.length || !window.Intl || !Intl.DateTimeFormat) return;
    try {
      var now = new Date();
      var longF = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { day: 'numeric', month: 'long', year: 'numeric' });
      var shortF = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { day: '2-digit', month: '2-digit', year: 'numeric' });
      var iso = now.toISOString().slice(0, 10);
      for (var i = 0; i < els.length; i++) {
        var el = els[i];
        var txt = el.hasAttribute('data-live-short') ? shortF.format(now) : longF.format(now);
        // «۳۱ شهریور ۱۴۰۵» در متن RTL کنار آیکن و متن فارسی، با bidi
        // جابه‌جا می‌شد و روز و سال به هم می‌چسبیدند («شهریور ۳۱۱۴۰۵»).
        // هر عدد را جداگانه isolate می‌کنیم تا سرِ جای خودش بماند.
        el.innerHTML = '';
        txt.split(/(\s+)/).forEach(function (part) {
          if (/[۰-۹0-9]/.test(part)) {
            var b = document.createElement('bdi');
            b.textContent = part;
            el.appendChild(b);
          } else {
            el.appendChild(document.createTextNode(part));
          }
        });
        el.setAttribute('datetime', iso);
      }
    } catch (e) { /* مرورگر قدیمی: تاریخِ زمان ساخت می‌ماند */ }
  }

  /* ---- ۲. جست‌وجوی سراسری ---- */
  function normalise(s) {
    s = String(s == null ? '' : s);
    var out = '';
    for (var i = 0; i < s.length; i++) {
      var ch = s[i], k = FA.indexOf(ch);
      if (k < 0) k = AR.indexOf(ch);
      out += k >= 0 ? String(k) : ch;
    }
    return out.replace(/‌/g, ' ').replace(/[٫،]/g, '/').replace(/\./g, '/')
              .replace(/\s+/g, ' ').trim().toLowerCase();
  }

  function globalSearch() {
    var input = document.querySelector('[data-global-search]');
    if (!input) return;
    var count = document.querySelector('[data-global-count]');
    var cards = Array.prototype.slice.call(document.querySelectorAll('.pt-card:not(.ch-card)'));
    var rows = [];
    cards.forEach(function (card) {
      var trs = card.querySelectorAll('table.pt tbody tr');
      Array.prototype.forEach.call(trs, function (tr) {
        tr._hay = normalise(tr.getAttribute('data-name') + ' ' + tr.textContent);
        rows.push(tr);
      });
    });
    var total = rows.length;
    function apply() {
      var terms = normalise(input.value).split(' ').filter(Boolean);
      var shown = 0;
      cards.forEach(function (card) {
        var vis = 0;
        var trs = card.querySelectorAll('table.pt tbody tr');
        Array.prototype.forEach.call(trs, function (tr) {
          var ok = true;
          for (var i = 0; i < terms.length && ok; i++) if (tr._hay.indexOf(terms[i]) < 0) ok = false;
          tr.hidden = !ok; if (ok) vis++;
        });
        card.hidden = terms.length > 0 && vis === 0;
        shown += vis;
      });
      if (count) count.textContent = terms.length ? faDigits(shown) + ' کالا از ' + faDigits(total) : '';
      var changes = document.querySelector('.ch-card');
      if (changes) changes.hidden = terms.length > 0;
    }
    input.addEventListener('input', apply);
    // جست‌وجوی سرصفحه به اینجا می‌فرستد: /price/?q=…
    try {
      var q = new URLSearchParams(location.search).get('q');
      if (q) { input.value = q; apply(); input.scrollIntoView({ block: 'center' }); }
    } catch (e) {}
  }

  /* ---- ۴. جست‌وجوی سرصفحه → صفحه‌ی قیمت ---- */
  function headerSearch() {
    var box = document.querySelector('.masthead .search');
    if (!box) return;
    var input = box.querySelector('input'), btn = box.querySelector('button');
    function go() {
      var v = (input.value || '').trim();
      location.href = '/price/' + (v ? '?q=' + encodeURIComponent(v) : '');
    }
    if (btn) btn.addEventListener('click', go);
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); go(); } });
  }

  /* ---- ۳. گالری محصول ---- */
  function gallery() {
    var main = document.getElementById('gmain');
    if (!main) return;
    var thumbs = document.querySelectorAll('.gallery-thumbs .thumb');
    Array.prototype.forEach.call(thumbs, function (b) {
      b.addEventListener('click', function () {
        main.src = b.getAttribute('data-src');
        Array.prototype.forEach.call(thumbs, function (x) { x.classList.remove('is-on'); });
        b.classList.add('is-on');
      });
    });
  }

  function init() { liveDate(); globalSearch(); gallery(); headerSearch(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
