{{--
    Overrides partials.brand-mark while the redesign is on. Its only callers
    left are the admin panel's sidebar (width 120) and mobile header (width 44):
    the site's own screens use the new masthead.

    The original draws the tall shield artwork (216×404) at the given width, so
    at 120px it is ~224px tall inside the sidebar's 65px brand bar: it spills
    over the menu search box and is cut off. Here the square mark is sized to
    the bar, and the wide call gets the shop's name beside it, like the site.
--}}
@php $markWidth = (int) ($width ?? 130); $wide = $markWidth >= 100; $px = $wide ? 38 : 34; @endphp
<span class="brand-mark {{ $class ?? '' }}" style="display:inline-flex;align-items:center;gap:10px;line-height:1.2">
  <img src="/rd/brand/mark-88.png" srcset="/rd/brand/mark-88.png 2x, /rd/brand/mark-132.png 3x"
       width="{{ $px }}" height="{{ $px }}" style="width:{{ $px }}px;height:{{ $px }}px;border-radius:8px;background:#fff;padding:3px"
       alt="{{ \App\Support\Site::companyName() ?: \App\Support\Brand::name() }}">
  @if($wide)<span style="color:#fff;font-weight:700;font-size:15px;white-space:nowrap">{{ \App\Support\Brand::name() }}</span>@endif
</span>
