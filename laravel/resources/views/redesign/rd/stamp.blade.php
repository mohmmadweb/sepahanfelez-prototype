{{--
    «بروزرسانی» stamp (common.stamp). $at is the newest prices.price_at.
    The prototype always said «امروز» and site.js rewrote the date to the
    visitor's today. That is only true here when the last import *was* today,
    so the live-date hook is attached only then; otherwise the real date shows.
--}}
@php
    $at = $at ?? \App\Support\Redesign::lastUpdate();
    $today = $at ? Rd::isToday($at) : true;
    $when = $at ?: now();
@endphp
@if($short ?? false)
<span class="stamp"><span class="dot"></span>بروزرسانی: <time @if($today) data-live-date @endif datetime="{{ Rd::iso($when) }}">{!! Rd::jDate($when, false, true) !!}</time></span>
@else
<span class="stamp"><span class="dot"></span>بروزرسانی: {{ $today ? 'امروز ' : '' }}<time @if($today) data-live-date @endif datetime="{{ Rd::iso($when) }}">{!! Rd::jDate($when, false, true) !!}</time> — ساعت {{ Rd::updateTime() }}</span>
@endif
