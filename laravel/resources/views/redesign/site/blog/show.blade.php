{{--
    Article (blog.build_article), plus the live comment thread the old site had.
    Controller: Site\BlogController@show → $category, $article (model), $comments.
--}}
@extends('rd.layout')
@php
    $R = \App\Support\Redesign::class;
    $a = $R::articleArray((int) $article->id) ?: [
        'id' => (int) $article->id, 'title' => $article->title, 'slug' => $article->slug, 'description' => (string) $article->description,
        'image' => null, 'cat_slug' => $category->slug, 'cat_title' => $category->title, 'published' => optional($article->created_at)->toDateString(),
        'modified' => optional($article->updated_at)->toDateString(), 'read_min' => 1, 'excerpt' => '', 'tags' => [], 'url' => Rd::uArticle($category->slug, $article->slug)];
    [$bodyHtml, $toc] = $R::toc((string) $article->body);
    $prods = $R::relatedCategories($a);
    $rel = $R::sameCatArticles($a);
    $hero = $a['image'] ?: (($prods && ($ph = $R::photos($prods[0]))) ? $ph[0] : null);
    $t = trim($a['title']);
    $full = $t . ' | مجله ' . \App\Support\Brand::name();
    $pageTitle = $article->meta_title ?: (mb_strlen($full) <= 60 ? $full : (mb_strlen($t) <= 60 ? $t : Rd::cut($t, 57)));
    $desc = $article->meta_description ?: Rd::cut($a['description'] ?: ($t . ' — مجله‌ی ' . \App\Support\Brand::name() . '.'));
    $crumbs = [['خانه', '/'], ['مجله', '/blog'], [$a['cat_title'], Rd::uBlogCat($a['cat_slug'])], [$t, null]];
@endphp
@section('title', $pageTitle)
@section('description', $desc)
@section('robots', \App\Support\Brand::robots($article->index_by_crawler, $article->slug))
@section('canonical', $a['url'])
@section('nav', 'blog')
@section('crumbs')@include('rd.crumb', ['items' => $crumbs])@endsection
@section('jsonld'){{ Rd::graph(Rd::organization(), Rd::website(), ['@type' => 'Article', '@id' => Rd::site($a['url']) . '#article', 'headline' => mb_substr($t, 0, 110), 'description' => $desc, 'url' => Rd::site($a['url']), 'inLanguage' => 'fa-IR', 'datePublished' => $a['published'], 'dateModified' => $a['modified'] ?: $a['published'], 'author' => ['@id' => Rd::orgId()], 'publisher' => ['@id' => Rd::orgId()], 'image' => $a['image'] ? Rd::site($a['image']) : null, 'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => Rd::site($a['url'])], 'articleSection' => $a['cat_title']], Rd::breadcrumb($crumbs)) }}@endsection
@section('content')
  <article class="post">
    <header class="post-head">
      <div class="container narrow">
        <a class="chip" href="{{ Rd::path(Rd::uBlogCat($a['cat_slug'])) }}">{{ $a['cat_title'] }}</a>
        <h1>{{ $t }}</h1>
        @if($a['description'])<p class="post-lede">{{ $a['description'] }}</p>@endif
        <div class="post-meta">
          {{-- A person's name only when the admin gave that user an author bio (users → description);
               otherwise the shop, as on the old theme, which never printed staff names. --}}
          @php $au = $article->user; $byline = ($au && trim(strip_tags((string) $au->description)) !== '' && $au->full_name) ? $au->full_name : 'کارشناسان ' . \App\Support\Brand::name(); @endphp
          <span>{{ Rd::icon('i-user') }} {{ $byline }}</span>
          @if($a['published'])<span>{{ Rd::icon('i-calendar') }}<time datetime="{{ $a['published'] }}">{{ Rd::jDate($a['published']) }}</time></span>@endif
          <span>{{ Rd::icon('i-clock') }} {{ Rd::fa($a['read_min']) }} دقیقه مطالعه</span>
        </div>
      </div>
    </header>
    @if($hero)<div class="container narrow"><figure class="post-hero"><img src="{{ $hero }}" alt="{{ $t }}" loading="eager" decoding="async"></figure></div>@endif
    <div class="container post-grid">
      <div class="post-body article-body main-text">{!! $bodyHtml !!}
        <div class="post-tags">@foreach($a['tags'] as $tag)<span class="tag">{{ Rd::icon('i-tag') }}{{ $tag }}</span>@endforeach</div>
      </div>
      <aside class="post-side">
        @if($toc)
        <nav class="toc" aria-label="فهرست مطلب"><h3>{{ Rd::icon('i-list') }} در این مقاله</h3><ol>@foreach($toc as $it)<li><a href="#{{ $it[0] }}">{{ $it[1] }}</a></li>@endforeach</ol></nav>
        @endif
        @if($prods)
        <div class="side-box"><h3>{{ Rd::icon('i-box') }} محصول مرتبط</h3>@foreach($prods as $k)@include('rd.relprod', ['slug' => $k])@endforeach
          <a class="side-more" href="/price">جدول کامل قیمت لحظه‌ای {{ Rd::icon('i-chev') }}</a></div>
        @endif
        <a class="side-call" href="tel:{{ Rd::phone() }}" data-track="call-article">
          {{ Rd::icon('i-phone') }}<span><span class="l">مشاوره و استعلام قیمت</span><span class="n num">{{ Rd::phoneShow() }}</span></span></a>
      </aside>
    </div>
  </article>

  <section class="section" id="comments">
    <div class="container">
      <div class="section-head"><div><h2>دیدگاه‌ها</h2><div class="sub">دیدگاه‌ها پس از تأیید منتشر می‌شوند</div></div></div>
      <div class="comments">
        <div class="rlist">
          @forelse($comments as $cm)
            <article class="review">
              <header><b>{{ $cm->name ?: optional($cm->user)->full_name ?: 'خواننده' }}</b><span class="dim">{{ Rd::jDate($cm->created_at) }}</span></header>
              <p>{{ $cm->body }}</p>
              @if($cm->answer)<div class="answer"><b>پاسخ {{ \App\Support\Brand::name() }}:</b> {{ $cm->answer }}</div>@endif
            </article>
          @empty
            <p class="dim">هنوز دیدگاهی برای این مقاله ثبت نشده است.</p>
          @endforelse
        </div>
        <form class="rform" method="post" action="{{ route('article.comment', $article->id) }}">
          @csrf
          <h3>دیدگاه یا پرسش خود را بنویسید</h3>
          @if($errors->any())
            <div class="alert alert-err" role="alert">{{ Rd::icon('i-flat') }}<ul>@foreach($errors->all() as $er)<li>{{ $er }}</li>@endforeach</ul></div>
          @endif
          <div class="cform-grid">
            @guest
            <label class="ffield"><span>نام</span><input name="name" type="text" minlength="2" maxlength="30" required value="{{ old('name') }}"></label>
            <label class="ffield"><span>شماره تماس (منتشر نمی‌شود)</span><input name="phone" type="tel" inputmode="numeric" maxlength="15" required value="{{ old('phone') }}"></label>
            @endguest
            <label class="ffield fsearch"><span>متن دیدگاه</span><textarea name="body" rows="4" minlength="4" maxlength="500" required>{{ old('body') }}</textarea></label>
          </div>
          <button class="btn btn-ghost" type="submit">ارسال دیدگاه</button>
        </form>
      </div>
    </div>
  </section>

  @if($rel)
  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>مقالات مرتبط</h2><div class="sub">از دسته‌ی {{ $a['cat_title'] }}</div></div>
        <a href="/blog">همه‌ی مقالات {{ Rd::icon('i-chev') }}</a></div>
      <div class="mag-grid mag-grid-3">@foreach($rel as $x)@include('rd.mag-card', ['a' => $x])@endforeach</div>
    </div>
  </section>
  @endif
@include('rd.callband')
@endsection
