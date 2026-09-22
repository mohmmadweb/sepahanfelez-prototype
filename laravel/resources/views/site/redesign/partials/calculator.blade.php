{{-- ماشین‌حساب وزن، مبلغ و برآورد کرایه.
     وزن و مساحت هر واحد از Redesign::weightOf/areaOf می‌آید: ستون عددی اگر
     ساخته شده باشد، وگرنه از مقدار مشخصات خوانده می‌شود. --}}
@php
    $freight = \App\Support\Redesign::freight();
    $kg = \App\Support\Redesign::weightOf($product, $specValues ?? null);
    $m2 = \App\Support\Redesign::areaOf($product, $specValues ?? null);
@endphp
<section class="section alt" id="calculator">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>ماشین‌حساب وزن و هزینه</h2>
                <div class="sub">مقدار را وارد کنید تا وزن بار، مبلغ کالا و برآورد کرایه‌ی حمل را ببینید</div>
            </div>
        </div>
        <form class="calc"
              data-price="{{ (int) $product->price }}"
              data-unit="{{ $product->unit }}"
              data-kg-per-unit="{{ $kg ?: 0 }}"
              data-m2-per-unit="{{ $m2 ?: 0 }}"
              data-freight-min="{{ \App\Support\Redesign::freightMinTon() }}"
              hidden>
            <div class="calc-grid">
                <label class="ffield"><span>مقدار</span>
                    <input name="qty" type="text" inputmode="decimal" placeholder="مثلاً ۱۰" autocomplete="off"></label>
                <label class="ffield"><span>واحد</span>
                    <select name="mode">
                        <option value="unit">بر حسب {{ $product->unit }}</option>
                        @if($m2)<option value="m2">بر حسب متر مربع</option>@endif
                        @if($kg)<option value="kg">بر حسب کیلوگرم</option>@endif
                    </select></label>
                <label class="ffield"><span>مقصد بار</span>
                    <select name="dest">
                        @foreach($freight as $f)
                            <option value="{{ $f['rate'] }}">{{ $f['name'] }}@if($f['rate']) — {{ number_format($f['rate']) }} ریال/تن @endif</option>
                        @endforeach
                    </select></label>
            </div>
            <p class="calc-basis">مبنا: <b>{{ $product->title }}</b> —
                <span class="num">{{ number_format($product->price) }}</span> ریال / {{ $product->unit }}</p>
            <div class="calc-out" hidden></div>
            <p class="dim">مبلغ کالا با قیمت مبنای روز حساب می‌شود و کرایه‌ی حمل برآورد تقریبی است.
                قیمت و کرایه‌ی قطعی با تناژ و مقصد بار تلفنی اعلام می‌شود:
                <span class="num">{{ config('brand.phone_display') }}</span>.</p>
        </form>
    </div>
</section>
