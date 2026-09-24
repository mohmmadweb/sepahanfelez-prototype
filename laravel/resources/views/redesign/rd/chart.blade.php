{{--
    Price chart (features.chart). Real history comes from data-src; see the
    note at the top of rd/chart.js. $src, $id, $price, $prev, $key, $title, $sub.
--}}
@if($price)
@php $days = $days ?? 90; @endphp
<div class="chart" id="{{ $id }}" data-price="{{ $price }}" data-prev="{{ $prev ?: $price }}" data-key="{{ $key }}" data-days="{{ $days }}"
     data-src="{{ $src }}" data-sample="{{ config('redesign.chart.sample_series') ? '1' : '0' }}"
     data-note-real="تاریخچه‌ی واقعی ثبت قیمت در سپاهان فلز؛ هر پله یک تغییر قیمت ثبت‌شده است. قیمت قطعی سفارش تلفنی اعلام می‌شود."
     data-note-sample="برای این کالا هنوز تاریخچه‌ی کافی ثبت نشده؛ این سری نمایشی است و فقط دو نقطه‌ی آن (آخرین ثبت و قیمت روز) واقعی است.">
  <div class="chart-head"><div><h3>{{ $title }}</h3>@if(!empty($sub))<span class=dim>{{ $sub }}</span>@endif</div><div class="chart-ranges" role="group" aria-label="بازه">@include('rd.chart-ranges', ['id' => $id, 'days' => $days])</div></div>
  @include('rd.chart-panel', ['id' => $id])
  <div class="chart-svg"></div>
  <div class="chart-stats"></div>
  <p class="chart-note">آخرین قیمت ثبت‌شده <b class="num">{{ Rd::fmt($prev ?: $price) }}</b> ریال و قیمت روز <b class="num">{{ Rd::fmt($price) }}</b> ریال.</p>
</div>
@endif
