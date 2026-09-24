@if($e)
<div class="expert{{ ($compact ?? false) ? ' compact' : '' }}">
  <img src="{{ $e['photo'] ?? '/rd/experts/expert-2.svg' }}" alt="{{ $e['name'] }} — {{ $e['role'] }}" width="72" height="72" loading="lazy" decoding="async">
  <div class="expert-t">
    <b>{{ $e['name'] }}</b><span>{{ $e['role'] }}</span>
    <div class="expert-links">
      <a class="expert-tel" href="tel:{{ Rd::phone() }},{{ $e['ext'] }}" data-track="call-expert"><span class="num">{{ Rd::phoneShow() }}</span> <em>داخلی <b class="num">{{ $e['ext'] }}</b></em></a>
      <a class="expert-wa" href="https://wa.me/{{ Rd::wa() }}?text={{ rawurlencode('سلام، درباره‌ی قیمت سؤال دارم.') }}" rel="noopener">{{ Rd::icon('i-whatsapp') }} واتساپ</a>
    </div>
    <span class="dim">{{ $e['hours'] ?? '' }}</span>
  </div>
</div>
@endif
