{{--
    Price table of one category (tables.price_table).
    $slug; optional: $search (filter bar), $title (h2 with link), $guideLink, $note
--}}
@php
    $R = \App\Support\Redesign::class;
    $cat = $R::category($slug);
    $st = $R::stats($slug);
    $specs = $R::keySpecs($slug);
    $rows = $cat['rows'];
    $tid = 't-' . $R::slugify($slug);
    $anchor = 'c' . $cat['id'];
    $ctitle = $cat['title'];
    $at = $cat['last_update'];
    $today = $at ? Rd::isToday($at) : false;
@endphp
<section class="pt-card" id="pt-{{ $anchor }}">
  <header class="pt-head">
    <div class="pt-title">@if($title ?? true)<h2><a href="{{ Rd::path(Rd::uCat($slug)) }}">{{ $ctitle }}</a></h2>@endif<span class="pt-meta">{{ Rd::fa($st['n']) }} نوع کالا · واحد فروش: {{ $st['unit'] }}</span></div>
    <div class="pt-upd">{{ Rd::icon('i-clock') }} آخرین بروزرسانی: @if($today)<b>امروز</b> (<time data-live-short datetime="{{ Rd::iso($at) }}">{{ Rd::jShort($at) }}</time>)@elseif($at)<time datetime="{{ Rd::iso($at) }}">{{ Rd::jShort($at) }}</time>@else — @endif</div>
  </header>
  @if($search ?? false)
  <form class="tfilter slim" data-for="{{ $tid }}" hidden onsubmit="return false" aria-label="جست‌وجو در جدول">
    <div class="tfilter-row">
      <label class="ffield fsearch"><span>جست‌وجو</span>
        <input type="search" data-q placeholder="مثلاً: چشمه ۵/۵ یا مفتول ۳" autocomplete="off"></label>
      @foreach(array_slice($specs, 0, 3) as $n => $sp)
        @php
          $vals = [];
          foreach ($rows as $r) { $v = trim((string) ($r[$sp] ?? '')); if ($v !== '') { $vals[$v] = true; } }
          $vals = array_keys($vals);
          usort($vals, function ($a, $b) { return Rd::numKey($a) <=> Rd::numKey($b) ?: strcmp($a, $b); });
        @endphp
        @if(count($vals) >= 2)
          <label class="ffield"><span>{{ $R::shortHead($sp) }}</span><select data-spec="{{ $n }}"><option value="">همه</option>@foreach($vals as $v)<option value="{{ $v }}">{{ Rd::cleanVal($v) }}</option>@endforeach</select></label>
        @endif
      @endforeach
      <label class="ffield"><span>مرتب‌سازی</span>
        <select data-sort><option value="">پیش‌فرض</option><option value="asc">ارزان به گران</option><option value="desc">گران به ارزان</option></select></label>
    </div>
    <div class="tfilter-foot"><output data-count aria-live="polite"></output>
      <button type="button" class="btn btn-ghost btn-sm" data-reset>پاک‌کردن</button></div>
  </form>
  @endif
  <div class="pt-wrap">
  <table class="pt" id="{{ $tid }}">
    <caption class="vh">قیمت روز {{ $ctitle }}</caption>
    <thead><tr>
      <th scope="col" class="pt-name">نام کالا</th>
      @foreach($specs as $i => $x)<th scope="col" class="pt-spec{{ $i >= 2 ? ' pt-lo' : '' }}">{{ $R::shortHead($x) }}</th>@endforeach
      <th scope="col" class="pt-price">قیمت (ریال)</th>
      <th scope="col" class="pt-delta">نوسان</th>
      <th scope="col" class="pt-act"><span class="vh">استعلام</span></th>
    </tr></thead>
    <tbody>
    @foreach($rows as $r)
      @php $nm = Rd::cleanName($r['نام محصول']); @endphp
      <tr data-name="{{ $nm }}" data-price="{{ $r['_price'] }}" data-prev="{{ $r['_prev'] ?: $r['_price'] }}" data-src="/rd/chart/product/{{ $r['_id'] }}" @foreach($specs as $n => $sp) data-s{{ $n }}="{{ $r[$sp] ?? '' }}"@endforeach>
        <td class="pt-name">{{ Rd::link(Rd::prodUrl($slug, $r['_slug']), $nm) }}@if($r['_review'])<span class="review" title="{{ $r['_review'] }}">بازبینی</span>@endif</td>
        @foreach($specs as $i => $sp)<td class="pt-spec{{ $i >= 2 ? ' pt-lo' : '' }}" data-label="{{ $R::shortHead($sp) }}">{{ Rd::cleanVal($r[$sp] ?? '') }}</td>@endforeach
        <td class="pt-price" data-label="قیمت"><b class="num">{{ Rd::fmt($r['_price']) }}</b><span class="pt-u">ریال / {{ $r['واحد'] }}</span></td>
        <td class="pt-delta" data-label="نوسان">{{ Rd::delta($r['_prev'], $r['_price']) }}</td>
        <td class="pt-act"><button type="button" class="pt-chart" data-chart-row title="نمودار قیمت">{{ Rd::icon('i-chart') }}<span class="vh">نمودار قیمت {{ $nm }}</span></button><a class="pt-call" href="tel:{{ Rd::phone() }}" data-track="call-row" title="استعلام تلفنی">{{ Rd::icon('i-phone') }}<span class="vh">استعلام تلفنی {{ $nm }}</span></a></td>
      </tr>
    @endforeach
    </tbody>
  </table>
  </div>
  <footer class="pt-foot">@if($note ?? true)<span>قیمت‌ها به ریال و مبنای روز است؛ قیمت قطعی با تناژ و مقصد بار تلفنی اعلام می‌شود.</span>@endif @if($guideLink ?? true)<a href="{{ Rd::path(Rd::uCat($slug)) }}">راهنمای خرید و مشخصات کامل {{ Rd::icon('i-chev') }}</a>@endif</footer>
</section>
