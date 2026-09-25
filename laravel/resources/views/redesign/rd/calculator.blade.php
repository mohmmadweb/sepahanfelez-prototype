{{--
    Weight and cost calculator. Every number comes from the product rows:
    price (admin → قیمت) and the weight/size specs (admin → مشخصات فنی).
    There is no freight in the panel, so there is no freight estimate.
    Category page: $row null → a picker over the category's rows.
--}}
@php
    $A = \App\Support\RedesignAnalysis::class;
    $cat = \App\Support\Redesign::category($slug);
    $good = array_values(array_filter($cat['rows'], function ($r) { return ! $r['_review'] && $r['_price']; }));
    $picker = ! isset($row) || ! $row;
    if ($picker) { $row = $good[0] ?? null; }
    if ($row) {
        $p = $row['_price']; $unit = $row['واحد'];
        $kg = $A::kg($slug, $row); $m2 = $A::m2($slug, $row);
        $modes = [['unit', 'بر حسب ' . $unit]];
        if ($m2) { $modes[] = ['m2', 'بر حسب متر مربع']; }
        if ($kg) { $modes[] = ['kg', 'بر حسب کیلوگرم']; }
        $parts = [];
        if ($kg && $unit !== 'کیلوگرم') { $parts[] = 'وزن هر ' . $unit . ': ' . Rd::fa(Rd::g($kg)) . ' کیلوگرم'; }
        if ($m2 && $unit !== 'مترمربع') { $parts[] = 'سطح هر ' . $unit . ': ' . Rd::fa(Rd::g(round($m2, 2))) . ' متر مربع'; }
        if ($unit === 'کیلوگرم' && $m2) { $parts[] = 'هر کیلوگرم ≈ ' . Rd::fa(Rd::g(round($m2, 2))) . ' متر مربع'; }
    }
@endphp
@if($row && ($kg || $m2))
<section class="section alt" id="calculator">
  <div class="container">
    <div class="section-head"><div><h2>ماشین‌حساب وزن و هزینه</h2>
      <div class="sub">مقدار را وارد کنید تا وزن بار و مبلغ کالا را ببینید</div></div></div>
    <form class="calc" data-price="{{ $p }}" data-unit="{{ $unit }}" data-kg-per-unit="{{ $kg ?: 0 }}" data-m2-per-unit="{{ $m2 ?: 0 }}" hidden>
      <div class="calc-grid">
        @if($picker)
        <label class="ffield"><span>نوع کالا</span><select name="pick" data-pick>
          @foreach($good as $r)<option value="{{ $r['_price'] }}|{{ $A::kg($slug, $r) }}|{{ $A::m2($slug, $r) }}"@if($r['_id'] === $row['_id']) selected @endif>{{ Rd::cleanName($r['نام محصول']) }}</option>@endforeach
        </select></label>
        @endif
        <label class="ffield"><span>مقدار</span><input name="qty" type="text" inputmode="decimal" placeholder="مثلاً ۱۰" autocomplete="off"></label>
        <label class="ffield"><span>واحد</span><select name="mode">@foreach($modes as $m)<option value="{{ $m[0] }}">{{ $m[1] }}</option>@endforeach</select></label>
      </div>
      <p class="calc-basis">مبنا: <b>{{ Rd::cleanName($row['نام محصول']) }}</b> — <span class="num">{{ Rd::fmt($p) }}</span> ریال / {{ $unit }}{{ $parts ? ' · ' . implode(' · ', $parts) : '' }}</p>
      <div class="calc-out" hidden></div>
      <p class="dim">مبلغ با قیمت مبنای روز حساب می‌شود. هزینه‌ی حمل و قیمت قطعی با تناژ و مقصد بار تلفنی اعلام می‌شود: <span class="num">{{ Rd::phoneShow() }}</span>.</p>
    </form>
  </div>
</section>
@endif
