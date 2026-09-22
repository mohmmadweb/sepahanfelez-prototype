{{-- کارشناسان فروش.
     $slug اختیاری: اگر بیاید، کارشناسِ همان دسته می‌آید.
     منبع: جدول sales_reps اگر ساخته شده باشد، وگرنه config/redesign.php --}}
@php
    $experts = \App\Support\Redesign::expertsFor($slug ?? null);
    $wa = config('brand.whatsapp');
@endphp
@if(count($experts))
    <aside class="experts" aria-label="{{ $title ?? 'کارشناسان فروش' }}">
        <h3><i class="bi bi-person-badge" aria-hidden="true"></i> {{ $title ?? 'کارشناسان فروش' }}</h3>
        @foreach($experts as $e)
            @include('site.redesign.partials.expert-card', ['e' => $e, 'wa' => $wa])
        @endforeach
        <p class="dim">شماره‌ی دفتر را بگیرید و داخلی کارشناس را وارد کنید.</p>
    </aside>
@endif
