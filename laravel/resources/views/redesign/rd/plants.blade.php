<div class="plants plants-3">
@foreach(Rd::c('plants', []) as $pl)
<figure class="plant">
  <video controls preload="none" playsinline muted poster="{{ $pl['poster'] }}" aria-label="ویدئوی هوایی {{ $pl['title'] }}">
    <source src="{{ $pl['video'] }}" type="video/mp4">
    <img src="{{ $pl['poster'] }}" alt="{{ $pl['alt'] }}" loading="lazy">
  </video>
  <figcaption><b>@if($pl['link'])<a href="{{ $pl['link'] }}" target="_blank" rel="noopener">{{ $pl['title'] }} {{ Rd::icon('i-external') }}</a>@else{{ $pl['title'] }}@endif</b><span>{{ $pl['sub'] }}</span></figcaption>
</figure>
@endforeach
</div>
