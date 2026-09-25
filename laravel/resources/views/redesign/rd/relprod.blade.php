@php
    $R = \App\Support\Redesign::class;
    $st = $R::stats($slug);
    $ph = $R::cover($slug);
    $title = $R::category($slug)['title'];
@endphp
<a class="relprod" href="{{ Rd::path(Rd::uCat($slug)) }}">
  <span class="relprod-img">@if($ph)<img src="{{ $R::thumb($ph) }}" alt="{{ $title }}" loading="lazy">@endif</span>
  <span class="relprod-txt"><b>{{ $title }}</b><span>{{ Rd::fa($st['n']) }} نوع کالا · هر {{ $st['unit'] }}</span><span class="relprod-pr">@if($st['min'] != $st['max'])از <b class="num">{{ Rd::fmt($st['min']) }}</b> تا <b class="num">{{ Rd::fmt($st['max']) }}</b> ریال@else<b class="num">{{ Rd::fmt($st['min']) }}</b> ریال@endif</span></span>
  {{ Rd::icon('i-chev') }}
</a>
