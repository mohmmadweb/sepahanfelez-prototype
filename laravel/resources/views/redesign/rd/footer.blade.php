<footer class="site">
  <div class="container">
    <div class="fgrid fgrid-3">
      <div class="fcol-call">
        <h3>تماس با سپاهان فلز</h3>
        <p class="fwho">فروشگاه اینترنتی کارخانه‌ی
          <b>{{ Rd::c('factory.name') }}</b> — {{ Rd::c('basket_claim') }}</p>
        <a class="fcall" href="tel:{{ Rd::phone() }}" data-track="call-footer">
          <span class="label">دفتر فروش — {{ Rd::c('phone.lines') }}</span>
          <span class="number num">{{ Rd::phoneShow() }}</span>
        </a>
        <p class="fmob">واتساپ و شبکه‌های اجتماعی:
          <a href="https://wa.me/{{ Rd::wa() }}" class="num">{{ Rd::waShow() }}</a></p>
        <div class="socials">
          @foreach(Rd::c('socials', []) as $s)
            <a href="{{ $s[2] }}" aria-label="{{ $s[1] }} سپاهان فلز" rel="noopener" target="_blank">{{ Rd::icon($s[0]) }}</a>
          @endforeach
        </div>
        <a class="fmail" href="mailto:{{ Rd::c('email') }}">{{ Rd::icon('i-mail') }}{{ Rd::c('email') }}</a>
      </div>
      <div class="fcol-links"><h3>دسترسی سریع</h3><ul class="flinks">
        @foreach([['/price', 'قیمت لحظه‌ای'], ['/category/', 'دسته‌های محصول'], ['/blog', 'مجله سپاهان فلز'], ['/about', 'درباره کارخانه'], ['/contact', 'تماس با ما']] as $l)
          <li><a href="{{ $l[0] }}">{{ $l[1] }}</a></li>
        @endforeach
      </ul></div>
      @if($showAddr ?? true)
      <div class="fcol-addr"><h3>کارخانه و دفتر فروش</h3>
        <ul class="faddr">
          @foreach(Rd::c('addresses', []) as $ad)
            <li><span class="a-t">{{ $ad[0] }}</span>{{ $ad[1] }}</li>
          @endforeach
        </ul></div>
      @endif
    </div>
    <p class="copyright">فروشگاه اینترنتی صنایع مفتولی طلوع سپاهان</p>
  </div>
</footer>
