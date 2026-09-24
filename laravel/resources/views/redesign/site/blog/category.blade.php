{{-- /blog/{category} (blog.build_blog_category). Controller: Site\BlogController@category → $category, $articles (paginator). --}}
@extends('rd.layout')
@php
    $R = \App\Support\Redesign::class;
    $slug = $category->slug;
    $cats = $R::blogCats();
    $intro = Rd::c('blog_cat_intro.' . $slug) ?: $category->meta_description;
    $list = [];
    foreach ($articles as $m) { if ($x = $R::articleArray((int) $m->id)) { $list[] = $x; } }
    $total = method_exists($articles, 'total') ? $articles->total() : count($list);
@endphp
@section('title', $category->meta_title ?: ('مقالات ' . $category->title . ' | مجله سپاهان فلز'))
@section('description', Rd::cut($intro ?: ('همه‌ی مقالات دسته‌ی ' . $category->title . ' در مجله‌ی سپاهان فلز.')))
@section('canonical', Rd::uBlogCat($slug) . ($articles->currentPage() > 1 ? '?page=' . $articles->currentPage() : ''))
@section('nav', 'blog')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['مجله', '/blog'], [$category->title, null]]])@endsection
@section('jsonld'){{ Rd::graph(Rd::organization(), Rd::website(), Rd::page('CollectionPage', Rd::uBlogCat($slug), 'مقالات ' . $category->title), Rd::breadcrumb([['خانه', '/'], ['مجله', '/blog'], [$category->title, null]])) }}@endsection
@section('content')
  <section class="section">
    <div class="container">
      <div class="mag-head">
        <div><h1>مقالات {{ $category->title }}</h1>
          <p class="lede">{{ $intro }} ({{ Rd::fa($total) }} مقاله)</p></div>
        @include('site.blog.chips', ['current' => $slug, 'cats' => $cats])
      </div>
      <div class="mag-grid">@foreach($list as $a)@include('rd.mag-card', ['a' => $a])@endforeach</div>
      {{ $articles->links('rd.pagination') }}
    </div>
  </section>
  @if($total < 4)
  @php $pool = array_slice(array_values(array_filter($R::articles(), function ($a) use ($slug) { return $a['cat_slug'] !== $slug; })), 0, 6); @endphp
  @if($pool)
  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>مطالب دیگر مجله</h2>
        <div class="sub">از دسته‌های دیگر مجله‌ی سپاهان فلز</div></div>
        <a href="/blog">همه‌ی مقالات {{ Rd::icon('i-chev') }}</a></div>
      <div class="mag-grid mag-grid-3">@foreach($pool as $a)@include('rd.mag-card', ['a' => $a])@endforeach</div>
    </div>
  </section>
  @endif
  @endif
@include('rd.callband')
@endsection
