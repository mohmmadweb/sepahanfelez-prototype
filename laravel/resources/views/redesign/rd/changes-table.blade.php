@php $items = \App\Support\Redesign::movers($n ?? 10); @endphp
@if($items)
<section class="pt-card ch-card" id="changes">
  <header class="pt-head">
    <div class="pt-title"><h2>آخرین تغییرات قیمت</h2><span class="pt-meta">{{ Rd::fa(count($items)) }} کالا با بیشترین تغییر نسبت به آخرین قیمت ثبت‌شده</span></div>
    @include('rd.stampchips')
  </header>
  <div class="pt-wrap">
  <table class="pt ch">
    <caption class="vh">آخرین تغییرات قیمت محصولات</caption>
    <thead><tr><th scope="col"><span class="vh">جهت</span></th><th scope="col">نام کالا</th><th scope="col">قیمت لحظه‌ای</th><th scope="col">نوسان</th><th scope="col">مقدار تغییر نسبت به آخرین ثبت</th><th scope="col"><span class="vh">نمودار</span></th></tr></thead>
    <tbody>
    @foreach($items as $it)
      @php [$pct, $slug, $r] = $it; $p = $r['_price']; $d = $r['_prev']; $up = $p >= $d; $cls = $up ? 'up' : 'down'; $nm = Rd::cleanName($r['نام محصول']); @endphp
      <tr data-name="{{ $nm }}" data-price="{{ $p }}" data-prev="{{ $d }}" data-src="/rd/chart/product/{{ $r['_id'] }}">
        <td class="ch-dir {{ $cls }}">{{ Rd::icon($up ? 'i-up' : 'i-down') }}</td>
        <td class="ch-name"><a href="{{ Rd::path(Rd::uProd($slug, $r['_slug'])) }}">{{ $nm }}</a><span class="ch-cat">{{ Rd::cat($slug)['title'] ?? \App\Support\Redesign::category($slug)['title'] }} · {{ $r['واحد'] }}</span></td>
        <td class="ch-price" data-label="قیمت لحظه‌ای"><b class="num">{{ Rd::fmt($p) }}</b> <span class="riyal">ریال</span></td>
        <td class="ch-pct" data-label="نوسان">{{ Rd::delta($d, $p) }}</td>
        <td class="ch-diff" data-label="تغییر نسبت به آخرین ثبت"><span class="num">{{ Rd::fmt(abs($p - $d)) }}</span> ریال <span class="{{ $cls }}">{{ $up ? 'افزایش' : 'کاهش' }}</span></td>
        <td class="pt-act"><button type="button" class="pt-chart" data-chart-row title="نمودار قیمت">{{ Rd::icon('i-chart') }}<span class="vh">نمودار</span></button></td>
      </tr>
    @endforeach
    </tbody>
  </table>
  </div>
</section>
@endif
