{{--
    «بروزرسانی» stamp: date AND time of the newest prices row — i.e. of the
    last save in admin → قیمت / اکسل. No promised time from a file.
--}}
@php
    $at = $at ?? \App\Support\Redesign::lastUpdate();
    $today = $at ? Rd::isToday($at) : false;
    $time = $at ? Rd::updatedAt($at) : '';
@endphp
@if($at)
@if($short ?? false)
<span class="stamp"><span class="dot"></span>بروزرسانی: <time datetime="{{ Rd::iso($at) }}">{!! Rd::jDate($at, false, true) !!}</time></span>
@else
<span class="stamp"><span class="dot"></span>بروزرسانی: {{ $today ? 'امروز ' : '' }}<time datetime="{{ Rd::iso($at) }}">{!! Rd::jDate($at, false, true) !!}</time>@if($time) — ساعت {{ $time }}@endif</span>
@endif
@endif
