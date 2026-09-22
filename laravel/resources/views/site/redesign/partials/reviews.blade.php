{{-- دیدگاه و امتیاز.
     دیدگاه‌های واقعی از product_comments تأییدشده می‌آیند و از همین حالا کار
     می‌کنند. ستاره فقط وقتی نشان داده می‌شود که ستون rating وجود داشته باشد؛
     در غیر این صورت ratingFor() مقدار null می‌دهد و بلوک امتیاز حذف می‌شود،
     چون امتیاز ساختگی از نبودِ امتیاز بدتر است. --}}
@php
    $reviews = \App\Support\Redesign::reviewsFor($category);
    $rating  = \App\Support\Redesign::ratingFor($category);
    $hasRatingCol = \App\Support\Redesign::hasRatingColumn();
    $samples = (! count($reviews) && \App\Support\Redesign::sampleReviewsAllowed())
        ? (config('redesign.sample_reviews.' . $category->slug) ?: [])
        : [];
@endphp
<section class="section" id="reviews">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>دیدگاه و امتیاز خریداران {{ $subject ?? $category->title }}</h2>
                <div class="sub">دیدگاه‌ها پس از تأیید کارشناس منتشر می‌شوند</div>
            </div>
        </div>
        <div class="reviews-grid">
            <div class="rsummary">
                @if($rating)
                    <div class="ravg">
                        <b class="num">{{ $rating['average'] }}</b><span>از ۵</span>
                        <span class="stars lg" aria-label="{{ $rating['average'] }} از ۵">
                            @for($i = 1; $i <= 5; $i++)<i class="{{ $i <= round($rating['average']) ? 'on' : '' }}">★</i>@endfor
                        </span>
                        <span class="dim">{{ $rating['count'] }} دیدگاه</span>
                    </div>
                @else
                    <p class="dim">امتیازدهی ستاره‌ای پس از افزودن ستون <code>rating</code> به جدول
                        <code>product_comments</code> فعال می‌شود. تا آن زمان فقط متن دیدگاه منتشر می‌شود.</p>
                @endif
            </div>

            <div class="rlist">
                @forelse($reviews as $c)
                    <article class="review">
                        <header>
                            <b>{{ $c->name ?: optional($c->user)->full_name ?: 'خریدار' }}</b>
                            @if($hasRatingCol && $c->rating)
                                <span class="stars sm">@for($i = 1; $i <= 5; $i++)<i class="{{ $i <= $c->rating ? 'on' : '' }}">★</i>@endfor</span>
                            @endif
                        </header>
                        <p>{{ $c->body }}</p>
                        @if($c->answer)
                            <p class="review-answer"><b>پاسخ سپاهان فلز:</b> {{ $c->answer }}</p>
                        @endif
                    </article>
                @empty
                    @forelse($samples as $s)
                        <article class="review">
                            <header><b>{{ $s[0] }}</b>
                                <span class="stars sm">@for($i = 1; $i <= 5; $i++)<i class="{{ $i <= $s[1] ? 'on' : '' }}">★</i>@endfor</span>
                                <span class="badge-sample" title="نمونه است و با اولین دیدگاه واقعی جایگزین می‌شود">نمونه</span></header>
                            <p>{{ $s[2] }}</p>
                        </article>
                    @empty
                        <p class="dim">هنوز دیدگاهی ثبت نشده است. اولین نفر باشید.</p>
                    @endforelse
                @endforelse
            </div>

            {{-- همان اندپوینت واقعی: POST /category/{category}/comment --}}
            <form class="rform" method="post" action="{{ route('category.comment', ['category' => $category->id]) }}">
                @csrf
                <h3>دیدگاه خود را بنویسید</h3>
                @if($hasRatingCol)
                    <div class="star-input" role="radiogroup" aria-label="امتیاز">
                        @foreach([1, 2, 3, 4, 5] as $s)
                            <input type="radio" name="rating" id="r{{ $category->id }}-{{ $s }}" value="{{ $s }}" {{ $s === 5 ? 'checked' : '' }}>
                            <label for="r{{ $category->id }}-{{ $s }}" title="{{ $s }}">★</label>
                        @endforeach
                    </div>
                @endif
                <div class="cform-grid">
                    @guest
                        <label class="ffield"><span>نام</span>
                            <input name="name" type="text" maxlength="40" value="{{ old('name') }}" required></label>
                        <label class="ffield"><span>شماره تماس (منتشر نمی‌شود)</span>
                            <input name="phone" type="tel" inputmode="numeric" maxlength="15" value="{{ old('phone') }}" required></label>
                    @endguest
                    <label class="ffield fsearch"><span>متن دیدگاه</span>
                        <textarea name="body" rows="3" maxlength="500" required>{{ old('body') }}</textarea></label>
                </div>
                @error('body')<small class="text-danger">{{ $message }}</small>@enderror
                <button class="btn btn-ghost" type="submit">ارسال دیدگاه</button>
            </form>
        </div>
    </div>
</section>
