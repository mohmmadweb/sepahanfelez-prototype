{{-- Pagination in the redesign's style: $paginator->links('rd.pagination') --}}
@if ($paginator->hasPages())
<nav class="pager" aria-label="صفحه‌ها">
  @if ($paginator->onFirstPage())
    <span class="off" aria-hidden="true">قبلی</span>
  @else
    <a href="{{ $paginator->previousPageUrl() }}" rel="prev">قبلی</a>
  @endif
  @foreach ($elements as $element)
    @if (is_string($element))
      <span class="off">{{ $element }}</span>
    @endif
    @if (is_array($element))
      @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())
          <span aria-current="page">{{ $page }}</span>
        @else
          <a href="{{ $url }}">{{ $page }}</a>
        @endif
      @endforeach
    @endif
  @endforeach
  @if ($paginator->hasMorePages())
    <a href="{{ $paginator->nextPageUrl() }}" rel="next">بعدی</a>
  @else
    <span class="off" aria-hidden="true">بعدی</span>
  @endif
</nav>
@endif
