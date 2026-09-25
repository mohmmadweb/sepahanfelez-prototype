{{-- Call band. Phone, hours and WhatsApp from admin → اطلاعات تماس / شبکه‌های اجتماعی --}}
@php $hours = \App\Support\Site::hours(); $wa = Rd::wa(); $waShow = Rd::waShow(); @endphp
<section class="callband" aria-labelledby="cb-h">
  <div class="container">
    <div>
      <h2 id="cb-h">قیمت قطعی خود را از کارشناسان ما بگیرید</h2>
      <p>قیمت جدول مبنای روز است. قیمت نهایی و زمان تحویل با تناژ و مقصد بار، در تماس با کارشناس فروش اعلام می‌شود.</p>
      @if($hours)<p class="hours">{{ Rd::icon('i-clock') }} {{ $hours }}</p>@endif
    </div>
    <div class="lines">
      <a class="line primary" href="tel:{{ Rd::phone() }}" data-track="call-band">
        <span class="label">استعلام قیمت و ثبت سفارش</span>
        <span class="number num">{{ Rd::phoneShow() }}</span>
        <span class="who">دفتر فروش</span>
      </a>
      @if($wa && $waShow)
      <a class="line" href="{{ $wa }}" data-track="whatsapp-band">
        <span class="label">واتساپ کارشناس فروش</span>
        <span class="number num">{{ $waShow }}</span>
        <span class="who">ارسال لیست و پیش‌فاکتور</span>
      </a>
      @endif
    </div>
  </div>
</section>
