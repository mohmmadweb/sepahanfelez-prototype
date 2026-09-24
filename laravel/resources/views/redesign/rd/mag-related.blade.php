@php $arts = \App\Support\Redesign::relatedArticles($slug, $n ?? 3); @endphp
@if($arts)
<section class="section mag-related" aria-labelledby="rel-h">
  <div class="container">
    <div class="section-head">
      <div><h2 id="rel-h">{{ $title ?? ('مقالات مرتبط با ' . (Rd::cat($slug)['title'] ?? \App\Support\Redesign::category($slug)['title'])) }}</h2><div class="sub">از مجله‌ی سپاهان فلز</div></div>
      <a href="/blog">همه‌ی مقالات {{ Rd::icon('i-chev') }}</a>
    </div>
    <div class="mag-grid mag-grid-3">@foreach($arts as $a)@include('rd.mag-card', ['a' => $a])@endforeach</div>
  </div>
</section>
@endif
