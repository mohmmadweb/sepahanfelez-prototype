{{-- کارت مقاله. $a یک Article است. --}}
<article class="mag-card">
    <a class="mag-img" href="{{ route('blog.show', ['category' => $a->category->slug, 'article' => $a->slug]) }}">
        <img src="{{ $a->thumbnail_image() }}" alt="{{ $a->title }}" loading="lazy" decoding="async">
    </a>
    <div class="mag-txt">
        <a class="chip" href="{{ route('blog.category', ['category' => $a->category->slug]) }}">{{ $a->category->title }}</a>
        <h3><a href="{{ route('blog.show', ['category' => $a->category->slug, 'article' => $a->slug]) }}">{{ $a->title }}</a></h3>
        <p>{{ \Illuminate\Support\Str::limit($a->description, 110) }}</p>
        <div class="mag-meta">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            <span>{{ $a->created_at_without_time() }}</span>
            <i>·</i>
            <i class="bi bi-clock" aria-hidden="true"></i>
            <span>{{ \App\Support\Redesign::readingMinutes($a->body) }} دقیقه مطالعه</span>
        </div>
    </div>
</article>
