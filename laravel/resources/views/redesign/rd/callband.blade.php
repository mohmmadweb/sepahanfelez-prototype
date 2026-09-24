<section class="callband" aria-labelledby="cb-h">
  <div class="container">
    <div>
      <h2 id="cb-h">قیمت قطعی خود را از کارشناسان ما بگیرید</h2>
      <p>جدول، قیمت مبنای روز است. برای تناژ بالا، بار مخلوط، تولید
         سفارشی یا تحویل در محل، کارشناس فروش قیمت نهایی و زمان تحویل را در کمتر
         از دو دقیقه اعلام می‌کند.</p>
      <p class="hours">{{ Rd::icon('i-clock') }} {{ Rd::c('hours') }}</p>
    </div>
    <div class="lines">
      <a class="line primary" href="tel:{{ Rd::phone() }}" data-track="call-band">
        <span class="label">استعلام قیمت و ثبت سفارش</span>
        <span class="number num">{{ Rd::phoneShow() }}</span>
        <span class="who">دفتر فروش — {{ Rd::c('phone.lines') }}</span>
      </a>
      <a class="line" href="https://wa.me/{{ Rd::wa() }}" data-track="whatsapp-band">
        <span class="label">واتساپ کارشناس فروش</span>
        <span class="number num">{{ Rd::waShow() }}</span>
        <span class="who">ارسال لیست و پیش‌فاکتور</span>
      </a>
    </div>
  </div>
</section>
