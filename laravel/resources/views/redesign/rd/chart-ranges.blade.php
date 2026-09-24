@php $days = $days ?? 90; @endphp
@foreach(Rd::c('chart_ranges', []) as $r)<button type="button" data-range="{{ $r[0] }}" aria-pressed="{{ $r[0] == $days ? 'true' : 'false' }}" class="{{ $r[0] == $days ? 'is-on' : '' }}">{{ $r[1] }}</button>@endforeach<button type="button" class="cr-toggle" data-cr-toggle aria-expanded="false" aria-controls="cr-{{ $id }}">بازه‌ی دلخواه</button>
