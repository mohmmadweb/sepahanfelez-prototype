{{--
    Error pages in the new design. Laravel renders errors::<code>; the provider
    puts this directory first in the `errors` namespace.

    An error page must not be able to fail itself, so nothing here touches the
    database: the main nav and the stamp read the catalogue, which needs a
    working connection — a 500 caused by the database would 500 again here.
    So these pages use a minimal chrome of their own.
--}}
<!doctype html>
<html dir="rtl" lang="fa">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{{ $title }} | سپاهان فلز</title>
<link rel="icon" href="/rd/brand/favicon.ico" sizes="any">
<link rel="stylesheet" href="/rd/app.css">
</head>
<body>
@include('rd.icons')
<header class="masthead">
  <div class="container">
    <a class="brand" href="/">
      <img class="mark" src="/rd/brand/mark-88.png" width="44" height="44" alt="نشان سپاهان فلز" loading="eager">
      <span><span class="name">سپاهان فلز</span><br><span class="sub">فروشگاه اینترنتی صنایع مفتولی طلوع سپاهان</span></span>
    </a>
    <a class="callbox" href="tel:{{ Rd::phone() }}">
      <span class="icon">{{ Rd::icon('i-phone') }}</span>
      <span><span class="label">مشاوره و استعلام قیمت</span><span class="number num">{{ Rd::phoneShow() }}</span></span>
    </a>
  </div>
</header>
<main id="main">
  <section class="section">
    <div class="container errpage">
      <div class="code num">{{ Rd::fa($code) }}</div>
      <h1>{{ $title }}</h1>
      <p>{{ $text }}</p>
      <div class="factions">
        <a class="btn btn-lg" href="/price">قیمت لحظه‌ای</a>
        <a class="btn btn-ghost btn-lg2" href="/">صفحه‌ی اصلی</a>
        <a class="btn btn-call btn-lg2" href="tel:{{ Rd::phone() }}">{{ Rd::icon('i-phone') }} <span class="num">{{ Rd::phoneShow() }}</span></a>
      </div>
    </div>
  </section>
</main>
</body>
</html>
