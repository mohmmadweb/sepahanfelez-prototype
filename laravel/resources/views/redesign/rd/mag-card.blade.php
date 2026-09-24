{{-- Article card (blog.card). $a = Redesign::articles() item; $size lg|md|sm --}}
@php
    $size = $size ?? 'md';
    $href = Rd::path($a['url']);
    $d = $a['description'];
@endphp
@php ob_start(); @endphp<span class="mag-meta">{{ Rd::icon('i-calendar') }}{{ $a['published'] ? Rd::jDate($a['published']) : '—' }}<i>·</i>{{ Rd::icon('i-clock') }}{{ Rd::fa($a['read_min']) }} دقیقه مطالعه</span>@php $meta = ob_get_clean(); @endphp
@php $chip = '<a class="chip" href="' . e(Rd::path(Rd::uBlogCat($a['cat_slug']))) . '">' . e($a['cat_title']) . '</a>'; @endphp
@php $img = '<img src="' . e(\App\Support\Redesign::articleImage($a)) . '" alt="' . e($a['title']) . '" loading="lazy" decoding="async">'; @endphp
@if($size === 'lg')
<article class="mag-feature">
  <a class="mag-img" href="{{ $href }}">{!! $img !!}</a>
  <div class="mag-txt">{!! $chip !!}<h2><a href="{{ $href }}">{{ $a['title'] }}</a></h2>
    <p>{{ mb_substr($d, 0, 170) }}{{ mb_strlen($d) > 170 ? '…' : '' }}</p>{!! $meta !!}</div>
</article>
@elseif($size === 'sm')
<article class="mag-sm">
  <a class="mag-img" href="{{ $href }}">{!! $img !!}</a>
  <div class="mag-txt">{!! $chip !!}<h3><a href="{{ $href }}">{{ $a['title'] }}</a></h3>{!! $meta !!}</div>
</article>
@else
<article class="mag-card">
  <a class="mag-img" href="{{ $href }}">{!! $img !!}</a>
  <div class="mag-txt">{!! $chip !!}<h3><a href="{{ $href }}">{{ $a['title'] }}</a></h3>
    <p>{{ mb_substr($d, 0, 120) }}{{ mb_strlen($d) > 120 ? '…' : '' }}</p>{!! $meta !!}</div>
</article>
@endif
