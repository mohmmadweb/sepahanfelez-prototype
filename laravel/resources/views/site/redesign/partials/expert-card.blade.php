{{-- یک کارشناس. $e می‌تواند از config بیاید یا از ردیف sales_reps. --}}
<div class="expert">
    <img src="{{ \App\Support\Asset::url(ltrim($e['photo'], '/')) }}" alt="{{ $e['name'] }}"
         width="64" height="64" loading="lazy" decoding="async">
    <div class="expert-t">
        <b>{{ $e['name'] }}</b><span>{{ $e['role'] }}</span>
        <div class="expert-links">
            {{-- tel با داخلی: مرورگر موبایل «,» را مکث می‌فهمد و داخلی را خودش می‌گیرد --}}
            <a class="expert-tel" href="tel:{{ config('brand.phone') }},{{ $e['ext'] }}" data-track="call-expert">
                <span class="num">{{ config('brand.phone_display') }}</span>
                <em>داخلی <b class="num">{{ $e['ext'] }}</b></em></a>
            @if($wa = ($wa ?? config('brand.whatsapp')))
                <a class="expert-wa" href="{{ $wa }}" rel="noopener" target="_blank">
                    <i class="bi bi-whatsapp" aria-hidden="true"></i> واتساپ</a>
            @endif
        </div>
        <span class="dim">{{ $e['hours'] }}</span>
    </div>
</div>
