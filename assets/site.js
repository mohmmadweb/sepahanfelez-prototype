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
        el.textContent = el.hasAttribute('data-live-short') ? shortF.format(now) : longF.format(now);
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

  function init() { liveDate(); globalSearch(); gallery(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
