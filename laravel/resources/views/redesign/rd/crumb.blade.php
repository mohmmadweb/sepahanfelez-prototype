{{-- $items: [[label, href|null], ...] — the last one is the current page --}}
<nav class="crumb" aria-label="مسیر"><div class="container"><ol>
@foreach($items as $i => $it)
  @if($loop->last)
    <li aria-current="page">{{ $it[0] }}</li>
  @else
    <li><a href="{{ Rd::path($it[1]) }}">{{ $it[0] }}</a></li><li class="sep">/</li>
  @endif
@endforeach
</ol></div></nav>
