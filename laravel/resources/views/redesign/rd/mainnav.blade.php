@php
    $cur = $current ?? '';
    $a = function ($href, $label, $key, $cls = '') use ($cur) {
        return '<a href="' . e(Rd::path($href)) . '"' . ($cur !== '' && $cur === $key ? ' aria-current="page"' : '')
            . ($cls ? ' class="' . $cls . '"' : '') . '>' . e($label) . '</a>';
    };
    $links = [$a('/price', 'قیمت لحظه‌ای', 'price', 'nav-price')];
    foreach (\App\Support\Redesign::catalog() as $slug => $cat) {
        $links[] = $a(Rd::uCat($slug), $cat['title'], $slug);
    }
    // On desktop these three sit in the top bar (rd.utilbar), so the categories fit one row.
    $links[] = '<span class="spacer nav-aux"></span>';
    $links[] = $a('/blog', 'مجله', 'blog', 'nav-aux');
    $links[] = $a('/about', 'درباره ما', 'about', 'nav-aux');
    $links[] = $a('/contact', 'تماس با ما', 'contact', 'nav-aux');
@endphp
<nav class="mainnav" aria-label="منوی اصلی">
  <div class="container">
    <div class="nav-scroll">{!! implode('', $links) !!}</div>
  </div>
</nav>
<div class="msearch">
  <div class="container">
    <div class="search">
      <label class="vh" for="q-m">جست‌وجو در کل سایت</label>
      <input id="q-m" data-site-search type="search"
             placeholder="جست‌وجو در کالاها، مجله و صفحه‌ها">
      <button type="button" aria-label="جست‌وجو">{{ Rd::icon('i-search') }}</button>
    </div>
  </div>
</div>
