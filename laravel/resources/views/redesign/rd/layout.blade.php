{{--
    The page shell of the redesign — common.page_shell() of the prototype:
    head · utilbar · masthead · main nav · crumb · <main> · footer · dock.

    A page sets:
      @section('title')        <title>
      @section('description')  meta description
      @section('robots')       default "index, follow"
      @section('canonical')    path, e.g. /price (made absolute here)
      @section('nav')          key of the current main-nav item
      @section('crumbs')       @include('rd.crumb', ['items' => [...]])
      @section('jsonld')       {!! Rd::graph(...) !!}
      @section('content')
      @section('script')       page-only scripts, after the shared ones
    and may set $showAddr = false for the footer.
--}}
@php
    $canonical = trim($__env->yieldContent('canonical'));
    $canonicalUrl = $canonical !== '' ? (preg_match('~^https?://~', $canonical) ? $canonical : Rd::site(Rd::path($canonical))) : Rd::site(Rd::path('/' . ltrim(rawurldecode(request()->path()), '/')));
    // @section('x', $value) escapes on the way in; decode so {{ }} below escapes once.
    $title = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES, 'UTF-8') ?: \App\Support\Brand::name();
    $desc = html_entity_decode(trim($__env->yieldContent('description')), ENT_QUOTES, 'UTF-8');
    $robots = trim($__env->yieldContent('robots')) ?: 'index, follow';
@endphp
<!doctype html>
<html dir="rtl" lang="fa">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta name="theme-color" content="#B42332">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title }}</title>
<meta name="description" content="{{ $desc }}">
<link rel="preload" as="font" type="font/woff2" href="/rd/fonts/Estedad-Regular.woff2" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="/rd/fonts/Estedad-Black.woff2" crossorigin>
{{-- favicon: admin → تنظیمات عمومی (the old theme's own partial) --}}
@include('partials.favicon')
<meta property="og:locale" content="fa_IR">
<meta property="og:site_name" content="{{ \App\Support\Brand::name() }}">
<meta property="og:image" content="{{ \App\Support\Brand::ogImage() }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="{{ Rd::asset('rd/app.css') }}">
@yield('jsonld')
@if($gaId = config('brand.analytics.ga4'))
<link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $gaId }}');</script>
@endif
@if($ahrefsKey = config('brand.analytics.ahrefs'))
<script src="https://analytics.ahrefs.com/analytics.js" data-key="{{ $ahrefsKey }}" async></script>
@endif
</head>
<body data-price-url="/price">
@include('rd.icons')
<a class="skip" href="#main">رفتن به محتوای اصلی</a>
@include('rd.utilbar')
@include('rd.masthead')
@include('rd.mainnav', ['current' => trim($__env->yieldContent('nav'))])
@yield('crumbs')
<main id="main" tabindex="-1">
@yield('content')
</main>
@include('rd.footer', ['showAddr' => $showAddr ?? true])
@include('rd.dock')
@include('rd.flash')
@yield('script')
</body>
</html>
