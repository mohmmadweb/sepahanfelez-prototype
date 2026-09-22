{{-- نمودار قیمت.
     $endpoint آدرس واقعی داده است — /product/{id}/{tf} یا /category/chart/{id}/{tf}
     که Site\PriceController از قبل سرو می‌کند. اگر اندپوینت کمتر از دو نقطه
     برگرداند و redesign.chart.sample_series روشن باشد، سری نمایشی کشیده
     می‌شود و همین در یادداشت زیر نمودار نوشته می‌شود. --}}
<div class="chart"
     id="{{ $id }}"
     data-endpoint="{{ $endpoint ?? '' }}"
     data-price="{{ (int) $price }}"
     data-prev="{{ (int) ($prev ?: $price) }}"
     data-key="{{ $key ?? $id }}"
     data-sample="{{ config('redesign.chart.sample_series') ? '1' : '0' }}"
     data-days="{{ $days ?? config('redesign.chart.default_days', 30) }}">
    <div class="chart-head">
        <div>
            <h3>{{ $title }}</h3>
            @isset($sub)<span class="dim">{{ $sub }}</span>@endisset
        </div>
        <div class="chart-ranges" role="group" aria-label="بازه‌ی نمودار">
            <button type="button" data-range="7">هفتگی</button>
            <button type="button" data-range="30" class="is-on" aria-pressed="true">ماهانه</button>
            <button type="button" data-range="90">سه‌ماهه</button>
        </div>
    </div>
    <div class="chart-svg"></div>
    <div class="chart-stats"></div>
    <p class="chart-note" data-chart-note></p>
</div>
