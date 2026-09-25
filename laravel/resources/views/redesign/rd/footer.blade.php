{{--
    Footer. Every value from the panel:
      admin → اطلاعات تماس   : about (the text under the heading), phone, email, addresses
      admin → شبکه‌های اجتماعی: the icons (active ones only)
--}}
@php
    $S = \App\Support\Site::class;
    $about = $S::footerText(); $email = $S::email(); $wa = $S::whatsapp(); $waShow = $S::whatsappShow();
@endphp
<footer class="site">
  <div class="container">
    <div class="fgrid fgrid-3">
      <div class="fcol-call">
        <h3>تماس با {{ \App\Support\Brand::name() }}</h3>
        @if($about)<div class="fwho">{!! $about !!}</div>@endif
        <a class="fcall" href="tel:{{ Rd::phone() }}" data-track="call-footer">
          <span class="label">دفتر فروش</span>
          <span class="number num">{{ Rd::phoneShow() }}</span>
        </a>
        @if($wa && $waShow)<p class="fmob">واتساپ: <a href="{{ $wa }}" class="num">{{ $waShow }}</a></p>@endif
        @if($socials = $S::socials())
        <div class="socials">
          @foreach($socials as $s)
            <a href="{{ $s['url'] }}" aria-label="{{ $s['title'] }}" title="{{ $s['title'] }}" rel="noopener" target="_blank">{{ Rd::icon($s['icon']) }}</a>
          @endforeach
        </div>
        @endif
        @if($email)<a class="fmail" href="mailto:{{ $email }}">{{ Rd::icon('i-mail') }}{{ $email }}</a>@endif
      </div>
      <div class="fcol-links"><h3>دسترسی سریع</h3><ul class="flinks">
        @foreach([['/price', 'قیمت لحظه‌ای'], ['/category/', 'دسته‌های محصول'], ['/blog', 'مجله'], ['/about', 'درباره ما'], ['/contact', 'تماس با ما']] as $l)
          <li><a href="{{ $l[0] }}">{{ $l[1] }}</a></li>
        @endforeach
      </ul></div>
      @if(($showAddr ?? true) && ($addresses = $S::addresses()))
      <div class="fcol-addr"><h3>نشانی‌ها</h3>
        <ul class="faddr">
          @foreach($addresses as $ad)
            <li>@if($ad[0])<span class="a-t">{{ $ad[0] }}</span>@endif{{ $ad[1] }}</li>
          @endforeach
        </ul></div>
      @endif
    </div>
    <p class="copyright">© {{ \App\Support\Brand::name() }}</p>
  </div>
</footer>
