<nav class="mag-chips" aria-label="دسته‌های مجله">
  <a class="chip{{ $current === null ? ' is-on' : '' }}" href="/blog">همه‌ی مطالب</a>
  @foreach($cats as $s => $bc)<a class="chip{{ $current === $s ? ' is-on' : '' }}" href="{{ Rd::path(Rd::uBlogCat($s)) }}">{{ $bc['title'] }} <span class="num">({{ $bc['n'] }})</span></a>@endforeach
</nav>
