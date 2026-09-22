<section class="callband" aria-labelledby="cb-h">
    <div class="container">
        <div>
            <h2 id="cb-h">قیمت قطعی خود را از کارشناسان ما بگیرید</h2>
            <p>جدول، قیمت مبنای روز است. برای تناژ بالا، بار مخلوط، تولید سفارشی یا تحویل در محل،
                کارشناس فروش قیمت نهایی و زمان تحویل را در کمتر از دو دقیقه اعلام می‌کند.</p>
            @isset($info)
                @if($info && $info->work_time)
                    <p class="hours"><i class="bi bi-clock" aria-hidden="true"></i> {{ $info->work_time }}</p>
                @endif
            @endisset
        </div>
        <div class="lines">
            <a class="line primary" href="tel:{{ config('brand.phone') }}" data-track="call-band">
                <span class="label">استعلام قیمت و ثبت سفارش</span>
                <span class="number num">{{ config('brand.phone_display') }}</span>
                <span class="who">دفتر فروش کارخانه</span></a>
            @if($wa = config('brand.whatsapp'))
                <a class="line" href="{{ $wa }}" rel="noopener" target="_blank" data-track="whatsapp-band">
                    <span class="label">واتساپ کارشناس فروش</span>
                    <span class="who">ارسال لیست و پیش‌فاکتور</span></a>
            @endif
        </div>
    </div>
</section>
