@php $u = Rd::c('sales_unit'); $e = \App\Support\Redesign::rep(null); @endphp
<section class="section alt" id="sales-unit">
  <div class="container">
    <div class="section-head"><div><h2>{{ $u['title'] }}</h2><div class="sub">{{ $u['lede'] }}</div></div></div>
    <div class="unit-grid">
      <div class="trustgrid unit-promise">
        @foreach($u['promise'] as $p)
          <div class="trustitem">{{ Rd::icon($p[0]) }}<div><div class="t">{{ $p[1] }}</div><div class="d">{{ $p[2] }}</div></div></div>
        @endforeach
      </div>
      <div class="unit-rep">
        <h3>{{ Rd::icon('i-user') }} کارشناس پاسخگو</h3>
        @include('rd.rep-card', ['e' => $e])
        <a class="btn btn-call btn-lg2 unit-call" href="tel:{{ Rd::phone() }}" data-track="call-unit">{{ Rd::icon('i-phone') }} تماس با واحد فروش <span class="num">{{ Rd::phoneShow() }}</span></a>
      </div>
    </div>
  </div>
</section>
