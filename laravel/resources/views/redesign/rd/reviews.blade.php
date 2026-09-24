{{--
    Reviews of a category (features.reviews_block), live:
      · approved rows of product_comments, newest first
      · stars only when the `rating` column exists (database/sql) — no invented score
      · the form posts to the existing route category.comment
      · the prototype's sample reviews, badged «نمونه», only when there is no
        real comment and config('redesign.reviews.show_samples') is on
    $slug, optional $subject
--}}
@php
    $R = \App\Support\Redesign::class;
    $cat = $R::category($slug);
    $subj = $subject ?? (Rd::cat($slug)['title'] ?? $cat['title']);
    $real = $R::reviews($cat['id']);
    $hasRating = $R::hasRating();
    $items = [];
    foreach ($real as $cm) {
        $items[] = ['who' => $cm->name ?: optional($cm->user)->full_name ?: 'خریدار', 'score' => $hasRating ? (int) $cm->rating : 0,
                    'text' => $cm->body, 'answer' => $cm->answer, 'date' => $cm->created_at, 'sample' => false];
    }
    if (! $items && config('redesign.reviews.show_samples')) {
        foreach ((array) Rd::c('sample_reviews.' . $slug, []) as $s) {
            $items[] = ['who' => $s[0], 'score' => (int) $s[1], 'text' => $s[2], 'answer' => null, 'date' => null, 'sample' => true];
        }
    }
    $scored = array_values(array_filter($items, function ($i) { return $i['score'] > 0; }));
    $avg = $scored ? round(array_sum(array_column($scored, 'score')) / count($scored), 1) : 0;
    $uid = $cat['id'];
@endphp
<section class="section" id="reviews">
  <div class="container">
    <div class="section-head"><div><h2>دیدگاه و امتیاز خریداران {{ $subj }}</h2>
      <div class="sub">دیدگاه‌ها پس از تأیید کارشناس منتشر می‌شوند</div></div></div>
    <div class="reviews-grid">
      <div class="rsummary">
        @if($scored)
          <div class="ravg"><b class="num">{{ $avg }}</b><span>از ۵</span>{{ Rd::stars((int) round($avg), 'lg') }}<span class="dim">{{ Rd::fa(count($scored)) }} امتیاز</span></div>
          @foreach([5, 4, 3, 2, 1] as $s)
            @php $k = count(array_filter($scored, function ($i) use ($s) { return $i['score'] === $s; })); @endphp
            <div class="rdist"><span>{{ $s }}★</span><div class="rbar"><i style="width:{{ (int) (100 * $k / count($scored)) }}%"></i></div><span class="num">{{ $k }}</span></div>
          @endforeach
        @else
          <div class="ravg"><b class="num">{{ count($items) ?: '—' }}</b><span>دیدگاه</span></div>
        @endif
        @if($items && $items[0]['sample'])
          <p class="dim">این دیدگاه‌ها <b>نمونه</b> است و با ثبت نخستین دیدگاه واقعی کنار می‌رود.</p>
        @else
          <p class="dim">هر دیدگاه پیش از انتشار توسط کارشناس فروش خوانده می‌شود و در صورت نیاز پاسخ می‌گیرد.</p>
        @endif
      </div>
      <div class="rlist">
        @forelse($items as $it)
          <article class="review">
            <header><b>{{ $it['who'] }}</b>@if($it['score']){{ Rd::stars($it['score'], 'sm') }}@endif @if($it['sample'])<span class="badge-sample" title="دیدگاه نمونه">نمونه</span>@elseif($it['date'])<span class="dim">{{ Rd::jDate($it['date']) }}</span>@endif</header>
            <p>{{ $it['text'] }}</p>
            @if($it['answer'])<div class="answer"><b>پاسخ سپاهان فلز:</b> {{ $it['answer'] }}</div>@endif
          </article>
        @empty
          <p class="dim">هنوز دیدگاهی ثبت نشده است. اولین نفر باشید.</p>
        @endforelse
      </div>
      <form class="rform" method="post" action="{{ route('category.comment', $cat['id']) }}">
        @csrf
        <h3>دیدگاه خود را بنویسید</h3>
        @if($errors->any() && old('_form') === 'review')
          <div class="alert alert-err" role="alert">{{ Rd::icon('i-flat') }}<ul>@foreach($errors->all() as $er)<li>{{ $er }}</li>@endforeach</ul></div>
        @endif
        <input type="hidden" name="_form" value="review">
        @if($hasRating)
          <div class="star-input" role="radiogroup" aria-label="امتیاز">@foreach([1, 2, 3, 4, 5] as $s)<input type="radio" name="rating" id="r{{ $uid }}-{{ $s }}" value="{{ $s }}"@if((int) old('rating', 5) === $s) checked @endif><label for="r{{ $uid }}-{{ $s }}" title="{{ $s }}">★</label>@endforeach</div>
        @endif
        <div class="cform-grid">
          @guest
          <label class="ffield"><span>نام</span><input name="name" type="text" minlength="2" maxlength="50" required value="{{ old('name') }}"></label>
          <label class="ffield"><span>شماره تماس (منتشر نمی‌شود)</span><input name="phone" type="tel" inputmode="numeric" maxlength="15" required value="{{ old('phone') }}"></label>
          @endguest
          <label class="ffield fsearch"><span>متن دیدگاه</span><textarea name="body" rows="3" minlength="4" maxlength="500" required>{{ old('body') }}</textarea></label>
        </div>
        <button class="btn btn-ghost" type="submit">ارسال دیدگاه</button>
        <p class="dim">دیدگاه پس از تأیید منتشر می‌شود. شماره‌ی تماس فقط برای پاسخ کارشناس است.</p>
      </form>
    </div>
  </div>
</section>
