{{--
    Reviews of a category: approved rows of product_comments, managed in
    admin → دیدگاه‌های دسته‌بندی (approve, answer). The form posts to the
    existing route category.comment. No sample or invented reviews.
    $slug, optional $subject
--}}
@php
    $R = \App\Support\Redesign::class;
    $cat = $R::category($slug);
    $subj = $subject ?? $cat['title'];
    $real = $R::reviews($cat['id']);
@endphp
<section class="section" id="reviews">
  <div class="container">
    <div class="section-head"><div><h2>دیدگاه خریداران {{ $subj }}</h2>
      <div class="sub">دیدگاه‌ها پس از تأیید کارشناس منتشر می‌شوند</div></div></div>
    <div class="comments">
      <div class="rlist">
        @forelse($real as $cm)
          <article class="review">
            <header><b>{{ $cm->name ?: optional($cm->user)->full_name ?: 'خریدار' }}</b><span class="dim">{{ Rd::jDate($cm->created_at) }}</span></header>
            <p>{{ $cm->body }}</p>
            @if($cm->answer)<div class="answer"><b>پاسخ {{ \App\Support\Brand::name() }}:</b> {{ $cm->answer }}</div>@endif
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
        <div class="cform-grid">
          @guest
          <label class="ffield"><span>نام</span><input name="name" type="text" minlength="2" maxlength="50" required value="{{ old('name') }}"></label>
          <label class="ffield"><span>شماره تماس (منتشر نمی‌شود)</span><input name="phone" type="tel" inputmode="numeric" maxlength="15" required value="{{ old('phone') }}"></label>
          @endguest
          <label class="ffield fsearch"><span>متن دیدگاه</span><textarea name="body" rows="3" minlength="4" maxlength="500" required>{{ old('body') }}</textarea></label>
        </div>
        <button class="btn btn-ghost" type="submit">ارسال دیدگاه</button>
      </form>
    </div>
  </div>
</section>
