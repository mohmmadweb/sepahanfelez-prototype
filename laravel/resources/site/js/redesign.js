/* ---------------------------------------------------------------------------
 * redesign.js — نمودار قیمت، ماشین‌حساب، جست‌وجوی سراسری و امتیاز ستاره‌ای
 * ---------------------------------------------------------------------------
 * تفاوت مهم با نسخه‌ی ایستای پروتوتایپ: نمودار اول داده‌ی واقعی را از اندپوینت
 * می‌خواند (Site\PriceController: /product/{id}/{tf} و
 * /category/chart/{id}/{tf} که روی جدول‌های chart_price و daily_avg_price
 * می‌نشینند). فقط اگر پاسخ کمتر از دو نقطه داشت و data-sample روشن بود، سری
 * نمایشی بین «آخرین قیمت ثبت‌شده» و «قیمت روز» کشیده می‌شود — و همین در
 * یادداشت زیر نمودار نوشته می‌شود تا کسی آن را با تاریخچه‌ی واقعی اشتباه
 * نگیرد.
 *
 * بدون وابستگی: نه jQuery، نه Chart.js. SVG درون‌خطی.
 * --------------------------------------------------------------------------- */

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
      // نقطه یا Date دارد (سری نمایشی) یا برچسب آماده از بک‌اند (تاریخ شمسی).
      var lbl = data[i].label ? fa(String(data[i].label).replace(/-/g, ' ')) : label(data[i].d);
      ticks += '<text x="' + x(i).toFixed(1) + '" y="' + (H - 10) + '" text-anchor="middle" font-size="11" fill="#59677A">' + lbl + '</text>';
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

  /* داده‌ی واقعی از بک‌اند.
   * پاسخ PriceController به شکل {"data":[{price, price_at}, ...]} است.
   * price_at رشته‌ی تاریخ شمسی («۱۸-شهریور-۱۴۰۵») است، پس برای محور از
   * ترتیب ردیف‌ها استفاده می‌کنیم و تاریخ را همان‌طور که آمده نشان می‌دهیم. */
  var RANGE = { 7: 'week', 30: 'month', 90: 'three_months', 365: 'year' };
  var cacheByUrl = {};

  function fetchSeries(endpoint, days, cb) {
    if (!endpoint || !window.fetch) { cb(null); return; }
    var url = endpoint.replace(/\/(week|month|three_months|year)$/, '/' + (RANGE[days] || 'month'));
    if (cacheByUrl[url]) { cb(cacheByUrl[url]); return; }
    fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) {
        var rows = j && j.data ? j.data : null;
        if (rows && rows.length) {
          rows = rows.map(function (r) { return { label: r.price_at, v: +r.price || 0 }; })
                     .filter(function (r) { return r.v > 0; });
        }
        cacheByUrl[url] = rows;
        cb(rows);
      })
      .catch(function () { cb(null); });
  }

  function setup(el) {
    var cur = +el.getAttribute('data-price') || 0, prev = +el.getAttribute('data-prev') || cur;
    if (!cur) return;
    var seed = hash(el.getAttribute('data-key') || el.id || 'x');
    var days = +el.getAttribute('data-days') || 30;
    var endpoint = el.getAttribute('data-endpoint') || '';
    var allowSample = el.getAttribute('data-sample') !== '0';
    var note = el.querySelector('[data-chart-note]');

    function say(text) { if (note) note.textContent = text; }

    function render(dd) {
      say('در حال خواندن تاریخچه‌ی قیمت…');
      fetchSeries(endpoint, dd, function (rows) {
        if (rows && rows.length >= 2) {
          draw(el, rows.map(function (r) { return { d: null, label: r.label, v: r.v }; }));
          say('برگرفته از تاریخچه‌ی واقعی ثبت قیمت‌ها (' + fa(rows.length) + ' نقطه).');
          return;
        }
        if (!allowSample) {
          el.querySelector('.chart-svg').innerHTML =
            '<p class="chart-empty">هنوز تاریخچه‌ی کافی برای رسم نمودار ثبت نشده است.</p>';
          if (el.querySelector('.chart-stats')) el.querySelector('.chart-stats').innerHTML = '';
          say('نمودار با ثبت دومین قیمت این کالا نمایش داده می‌شود.');
          return;
        }
        draw(el, series(prev, cur, dd, seed));
        say('برای این کالا هنوز کمتر از دو قیمت ثبت شده؛ نمودار زیر نمایشی است و از '
          + 'آخرین قیمت ثبت‌شده (' + fmt(prev) + ' ریال) تا قیمت روز (' + fmt(cur) + ' ریال) کشیده شده.');
      });
    }

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
      box.setAttribute('data-endpoint', tr.getAttribute('data-endpoint') || '');
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

/* ماشین‌حساب وزن و هزینه + امتیاز ستاره‌ای فرم دیدگاه.
 * بدون جاوااسکریپت: ماشین‌حساب با hidden پنهان می‌ماند و اعداد پایه (قیمت
 * هر مترمربع/کیلو) در متن صفحه هست؛ فرم دیدگاه هم رادیوهای معمولی دارد. */
(function () {
  'use strict';
  var FA = '۰۱۲۳۴۵۶۷۸۹';
  function fa(s) { return String(s).replace(/[0-9]/g, function (d) { return FA[+d]; }); }
  function fmt(n) { return fa(Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')); }
  function norm(s) { return String(s || '').replace(/[۰-۹]/g, function (d) { return FA.indexOf(d); }).replace(/[٫،]/g, '.').replace('/', '.'); }

  function calc(form) {
    var price = +form.getAttribute('data-price') || 0;          // ریال به واحد فروش
    var unit = form.getAttribute('data-unit') || '';
    var kgPerUnit = +form.getAttribute('data-kg-per-unit') || 0; // وزن هر واحد فروش (kg)
    var m2PerUnit = +form.getAttribute('data-m2-per-unit') || 0; // مساحت هر واحد فروش (m²)
    var freight = JSON.parse(form.getAttribute('data-freight') || '[]');
    var minTon = +form.getAttribute('data-freight-min') || 1;
    var qty = form.querySelector('[name=qty]'), mode = form.querySelector('[name=mode]'), dest = form.querySelector('[name=dest]');
    var pick = form.querySelector('[data-pick]');
    var out = form.querySelector('.calc-out'), basis = form.querySelector('.calc-basis');
    var baseTxt = basis ? basis.innerHTML : '';
    if (pick) pick.addEventListener('change', function () {
      var v = pick.value.split('|');
      price = +v[0] || price; kgPerUnit = +v[1] || 0; m2PerUnit = +v[2] || 0;
      if (basis) basis.innerHTML = baseTxt.replace(/<b>[^<]*<\/b>/, '<b>' + pick.options[pick.selectedIndex].text + '</b>')
        .replace(/<span class="num">[^<]*<\/span>/, '<span class="num">' + fmt(price) + '</span>');
      apply();
    });
    function apply() {
      var q = parseFloat(norm(qty.value)) || 0;
      var m = mode.value;                       // unit | m2 | kg
      var units = 0;
      if (m === 'unit') units = q;
      else if (m === 'm2' && m2PerUnit) units = q / m2PerUnit;
      else if (m === 'kg' && kgPerUnit) units = q / kgPerUnit;
      var cost = units * price;
      var kg = kgPerUnit ? units * kgPerUnit : 0;
      var m2 = m2PerUnit ? units * m2PerUnit : 0;
      var tons = kg / 1000;
      var rate = +dest.value || 0;
      var fr = rate ? Math.max(tons, minTon) * rate : 0;
      if (!q) { out.hidden = true; return; }
      out.hidden = false;
      out.innerHTML =
        '<div class="calc-row"><span>مقدار سفارش</span><b class="num">' + fa(units >= 100 ? Math.round(units) : +units.toFixed(1)) + ' ' + unit + '</b></div>' +
        (kg ? '<div class="calc-row"><span>وزن تقریبی بار</span><b class="num">' + fmt(kg) + ' کیلوگرم</b></div>' : '') +
        (m2 ? '<div class="calc-row"><span>سطح پوشش</span><b class="num">' + fa(+m2.toFixed(1)) + ' متر مربع</b></div>' : '') +
        '<div class="calc-row"><span>مبلغ کالا (مبنای روز)</span><b class="num">' + fmt(cost) + ' ریال</b></div>' +
        (rate ? '<div class="calc-row"><span>برآورد کرایه‌ی حمل' + (tons < minTon ? ' (کف ' + fa(minTon) + ' تن)' : '') + '</span><b class="num">' + fmt(fr) + ' ریال</b></div>' : '<div class="calc-row"><span>کرایه‌ی حمل</span><b>تحویل درب کارخانه / انبار</b></div>') +
        '<div class="calc-row total"><span>جمع برآورد</span><b class="num">' + fmt(cost + fr) + ' ریال</b></div>';
    }
    [qty, mode, dest].forEach(function (el) { el.addEventListener('input', apply); el.addEventListener('change', apply); });
    form.addEventListener('submit', function (e) { e.preventDefault(); apply(); });
    form.hidden = false; apply();
  }

  function stars() {
    Array.prototype.forEach.call(document.querySelectorAll('.star-input'), function (box) {
      var radios = box.querySelectorAll('input[type=radio]');
      function paint() {
        var v = 0; Array.prototype.forEach.call(radios, function (r) { if (r.checked) v = +r.value; });
        Array.prototype.forEach.call(box.querySelectorAll('label'), function (l, i) { l.classList.toggle('is-on', i < v); });
      }
      Array.prototype.forEach.call(radios, function (r) { r.addEventListener('change', paint); });
      paint();
    });
  }

  function init() {
    Array.prototype.forEach.call(document.querySelectorAll('form.calc[data-price]'), calc);
    stars();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

