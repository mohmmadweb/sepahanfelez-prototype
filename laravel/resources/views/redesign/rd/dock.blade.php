<nav class="dock" aria-label="تماس سریع">
  <a class="primary" href="tel:{{ Rd::phone() }}" data-track="call-dock">
    <span class="l">تماس با کارشناس فروش</span>
    <span class="n num">{{ Rd::phoneShow() }}</span>
  </a>
  @if($wa = Rd::wa())<a href="{{ $wa }}">{{ Rd::icon('i-whatsapp') }}واتساپ</a>@endif
  <a href="/price">{{ Rd::icon('i-chart') }}قیمت‌ها</a>
</nav>
@include('rd.chart-dialog')
<script src="{{ Rd::asset('rd/table.js') }}" defer></script>
<script src="{{ Rd::asset('rd/hero.js') }}" defer></script>
<script src="{{ Rd::asset('rd/site.js') }}" defer></script>
<script src="{{ Rd::asset('rd/search.js') }}" defer></script>
<script src="{{ Rd::asset('rd/lightbox.js') }}" defer></script>
<script src="{{ Rd::asset('rd/chart.js') }}" defer></script>
<script src="{{ Rd::asset('rd/tools.js') }}" defer></script>
<script type="text/javascript">
  !function(){var i="fugB8i",d=document,g=d.createElement("script"),s="https://www.goftino.com/widget/"+i,l=localStorage.getItem("goftino_"+i);g.type="text/javascript",g.async=!0,g.src=l?s+"?o="+l:s;d.getElementsByTagName("head")[0].appendChild(g);}();
</script>
