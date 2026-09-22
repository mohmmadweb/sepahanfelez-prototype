/* نمودار تاریخچه‌ی قیمت — SVG درون‌خطی، بدون کتابخانه.
 *
 * داده از کجا می‌آید: در نسخه‌ی ایستا فقط دو نقطه‌ی واقعی داریم (آخرین قیمت
 * ثبت‌شده و قیمت روز). بین این دو، یک سری روزانه‌ی «نمونه» با گام‌های کوچک
 * ساخته می‌شود تا شکل نمودار و کنترل‌های آن دیده شود؛ زیر نمودار صریحاً
 * نوشته می‌شود که سری نمایشی است. با اتصال به بک‌اند، همین نمودار از
 * اندپوینت /product/{id}/{timeframe} (جدول chart_price) و
 * /category/chart/{id}/{timeframe} (daily_avg_price) تغذیه می‌شود و تولید
 * نمونه حذف می‌شود.
 */
(function () {
  'use strict';
  var FA = '۰۱۲۳۴۵۶۷۸۹';
  function fa(s) { return String(s).replace(/[0-9]/g, function (d) { return FA[+d]; }); }
  function fmt(n) { return fa(Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')); }
  function hash(s) { var h = 2166136261; for (var i = 0; i < s.length; i++) { h ^= s.charCodeAt(i); h = Math.imul(h, 16777619); } return h >>> 0; }
  function rng(seed) { return function () { seed = (seed * 1664525 + 1013904223) >>> 0; return seed / 4294967296; }; }

  // تقویم شمسی برای برچسب محور — همان الگوریتم site.js
  function toJalali(g) {
    var gy = g.getFullYear(), gm = g.getMonth() + 1, gd = g.getDate();
    var gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    var jy = gy <= 1600 ? 0 : 979; gy -= gy <= 1600 ? 621 : 1600;
    var gy2 = gm > 2 ? gy + 1 : gy;
    var days = 365 * gy + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) - 80 + gd + gdm[gm - 1];
    jy += 33 * Math.floor(days / 12053); days %= 12053;
    jy += 4 * Math.floor(days / 1461); days %= 1461;
    if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
    var jm = days < 186 ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
    var jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);
    return [jy, jm, jd];
  }
  var MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
  function label(d) { var j = toJalali(d); return fa(j[2]) + ' ' + MONTHS[j[1] - 1]; }

  function series(prev, cur, days, seed) {
    // مسیر نرم از prev به cur با نوسان‌های کوچک؛ نقطه‌ی آخر دقیقاً cur است
    var r = rng(seed), out = [], today = new Date();
    if (!prev) prev = cur;
    var steps = Math.max(2, Math.floor(days / 7));       // قیمت کارخانه هفتگی عوض می‌شود
    var levels = [], v = prev;
    for (var s = 0; s < steps; s++) {
      var t = s / (steps - 1);
      var target = prev + (cur - prev) * t;
      var noise = (r() - 0.5) * Math.abs(cur - prev || cur * 0.02) * 0.25;
      v = s === steps - 1 ? cur : Math.round((target + noise) / 1000) * 1000;
      levels.push(v);
    }
    for (var i = days - 1; i >= 0; i--) {
      var d = new Date(today); d.setDate(today.getDate() - i);
      var idx = Math.min(steps - 1, Math.floor((days - 1 - i) / 7));
      out.push({ d: d, v: levels[idx] });
    }
    return out;
  }

  function draw(el, data) {
    var W = 640, H = 240, L = 46, R = 46, T = 18, B = 34;
    var vs = data.map(function (p) { return p.v; });
    var min = Math.min.apply(null, vs), max = Math.max.apply(null, vs);
    if (max === min) { max = min * 1.02; min = min * 0.98; }
    var pad = (max - min) * 0.12; min -= pad; max += pad;
    var x = function (i) { return L + (W - L - R) * i / (data.length - 1); };
    var y = function (v) { return T + (H - T - B) * (1 - (v - min) / (max - min)); };
    var pts = data.map(function (p, i) { return x(i).toFixed(1) + ',' + y(p.v).toFixed(1); });
    var line = 'M' + pts.join(' L');
    var area = line + ' L' + x(data.length - 1).toFixed(1) + ',' + (H - B) + ' L' + x(0).toFixed(1) + ',' + (H - B) + ' Z';
    var last = data[data.length - 1], first = data[0];
    var up = last.v >= first.v, col = up ? '#0A6E44' : '#B42332';
    var grid = '';
    for (var g = 0; g < 4; g++) {
      var gv = min + (max - min) * g / 3, gy = y(gv);
      grid += '<line x1="' + L + '" x2="' + (W - R) + '" y1="' + gy.toFixed(1) + '" y2="' + gy.toFixed(1) + '" stroke="#DCE3EB" stroke-dasharray="3 4"/>' +
              '<text x="' + (W - R + 4) + '" y="' + (gy - 4).toFixed(1) + '" text-anchor="start" font-size="11" fill="#59677A">' + fmt(gv) + '</text>';
    }
    var ticks = '';
    var n = Math.min(6, data.length);
    for (var k = 0; k < n; k++) {
      var i = Math.round((data.length - 1) * k / (n - 1));
      ticks += '<text x="' + x(i).toFixed(1) + '" y="' + (H - 10) + '" text-anchor="middle" font-size="11" fill="#59677A">' + label(data[i].d) + '</text>';
    }
    var id = 'g' + hash(el.id || Math.random().toString()).toString(36);
    el.querySelector('.chart-svg').innerHTML =
      '<svg viewBox="0 0 ' + W + ' ' + H + '" role="img" aria-label="نمودار قیمت" direction="ltr">' +
      '<defs><linearGradient id="' + id + '" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="' + col + '" stop-opacity=".22"/><stop offset="1" stop-color="' + col + '" stop-opacity="0"/></linearGradient></defs>' +
      grid + '<path d="' + area + '" fill="url(#' + id + ')"/>' +
      '<path d="' + line + '" fill="none" stroke="' + col + '" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>' +
      '<circle cx="' + x(data.length - 1).toFixed(1) + '" cy="' + y(last.v).toFixed(1) + '" r="5" fill="#fff" stroke="' + col + '" stroke-width="2.5"/>' +
      ticks + '</svg>';
    var lo = Math.min.apply(null, vs), hi = Math.max.apply(null, vs);
    var chg = first.v ? (last.v - first.v) / first.v * 100 : 0;
    var stats = el.querySelector('.chart-stats');
    if (stats) stats.innerHTML =
      '<span><i>کمترین</i><b class="num">' + fmt(lo) + '</b></span>' +
      '<span><i>بیشترین</i><b class="num">' + fmt(hi) + '</b></span>' +
      '<span><i>تغییر بازه</i><b class="num ' + (chg >= 0 ? 'up' : 'down') + '">' + (chg >= 0 ? '+' : '') + fa(chg.toFixed(1)) + '٪</b></span>';
  }

  function setup(el) {
    var cur = +el.getAttribute('data-price') || 0, prev = +el.getAttribute('data-prev') || cur;
    if (!cur) return;
    var seed = hash(el.getAttribute('data-key') || el.id || 'x');
    var days = +el.getAttribute('data-days') || 30;
    function render(dd) { draw(el, series(prev, cur, dd, seed)); }
    var btns = el.querySelectorAll('[data-range]');
    Array.prototype.forEach.call(btns, function (b) {
      b.addEventListener('click', function () {
        Array.prototype.forEach.call(btns, function (x) { x.classList.remove('is-on'); x.setAttribute('aria-pressed', 'false'); });
        b.classList.add('is-on'); b.setAttribute('aria-pressed', 'true');
        render(+b.getAttribute('data-range'));
      });
    });
    render(days);
  }

  // مودال نمودار برای ردیف‌های جدول
  function modal() {
    var dlg = document.getElementById('chart-dialog');
    if (!dlg) return;
    var box = dlg.querySelector('.chart'), ttl = dlg.querySelector('.chart-title');
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-chart-row]');
      if (!b) return;
      e.preventDefault();
      var tr = b.closest('tr');
      var name = tr.getAttribute('data-name') || '';
      box.setAttribute('data-price', tr.getAttribute('data-price') || '0');
      box.setAttribute('data-prev', tr.getAttribute('data-prev') || tr.getAttribute('data-price') || '0');
      box.setAttribute('data-key', name);
      ttl.textContent = 'نمودار قیمت ' + name.replace(/[0-9]/g, function (d) { return FA[+d]; });
      var first = box.querySelector('[data-range]');
      Array.prototype.forEach.call(box.querySelectorAll('[data-range]'), function (x) { x.classList.remove('is-on'); });
      if (first) first.classList.add('is-on');
      setup(box);
      if (typeof dlg.showModal === 'function') dlg.showModal(); else dlg.setAttribute('open', '');
    });
    dlg.addEventListener('click', function (e) { if (e.target === dlg || e.target.closest('[data-close]')) dlg.close ? dlg.close() : dlg.removeAttribute('open'); });
  }

  function init() {
    Array.prototype.forEach.call(document.querySelectorAll('.chart[data-price]'), setup);
    modal();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
