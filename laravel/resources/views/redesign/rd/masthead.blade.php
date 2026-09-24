<header class="masthead">
  <div class="container">
    <a class="brand" href="/">
      <img class="mark" src="/rd/brand/mark-88.png"
           srcset="/rd/brand/mark-88.png 2x, /rd/brand/mark-132.png 3x"
           width="44" height="44" alt="نشان سپاهان فلز" loading="eager" decoding="async">
      <span><span class="name">سپاهان فلز</span><br><span class="sub">فروشگاه اینترنتی صنایع مفتولی طلوع سپاهان</span></span>
    </a>
    <div class="search">
      <label class="vh" for="q">جست‌وجو در کل سایت</label>
      <input id="q" type="search" placeholder="جست‌وجو در کالاها، مجله و صفحه‌های سایت">
      <button type="button" aria-label="جستجو">{{ Rd::icon('i-search') }}</button>
    </div>
    <a class="callbox" href="tel:{{ Rd::phone() }}" data-track="call-header">
      <span class="icon">{{ Rd::icon('i-phone') }}</span>
      <span>
        <span class="label">مشاوره و استعلام قیمت</span>
        <span class="number num">{{ Rd::phoneShow() }}</span>
        <span class="hint">۱۰ خط ویژه — پاسخگویی همین حالا</span>
      </span>
    </a>
  </div>
</header>
