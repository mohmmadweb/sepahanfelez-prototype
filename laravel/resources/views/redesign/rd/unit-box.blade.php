{{--
    «واحد فروش» box beside charts — replaces the named expert of the prototype.
    Nothing in the panel stores sales people, so no name is invented: it is the
    office line and hours from admin → اطلاعات تماس.
--}}
@php $hours = \App\Support\Site::hours(); $wa = Rd::wa(); @endphp
<aside class="experts" aria-label="واحد فروش">
  <h3>{{ Rd::icon('i-user') }} {{ $title ?? 'واحد فروش' }}</h3>
  <div class="expert-links" style="margin-top:var(--s3)">
    <a class="expert-tel" href="tel:{{ Rd::phone() }}" data-track="call-expert"><span class="num">{{ Rd::phoneShow() }}</span></a>
    @if($wa)<a class="expert-wa" href="{{ $wa }}" rel="noopener">{{ Rd::icon('i-whatsapp') }} واتساپ</a>@endif
  </div>
  @if($hours)<p class="dim">{{ $hours }}</p>@endif
  <p class="dim">قیمت قطعی، موجودی و زمان تحویل همین کالا را در تماس با کارشناس فروش بپرسید.</p>
</aside>
