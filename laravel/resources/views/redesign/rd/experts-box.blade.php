@php $e = \App\Support\Redesign::rep($slug ?? null); @endphp
@if($e)
<aside class="experts" aria-label="واحد فروش">
  <h3>{{ Rd::icon('i-user') }} {{ $title ?? 'کارشناس مسئول این دسته' }}</h3>
  @include('rd.rep-card', ['e' => $e])
  <p class="dim">شماره‌ی دفتر را بگیرید و داخلی را وارد کنید تا مستقیم به کارشناس همین محصول وصل شوید.</p>
</aside>
@endif
