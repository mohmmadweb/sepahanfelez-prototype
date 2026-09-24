@php
    $R = \App\Support\Redesign::class;
    $specs = array_slice($R::keySpecs($slug), 0, 2);
    $others = array_slice(array_values(array_filter($R::category($slug)['rows'], function ($r) use ($row) { return $r['_id'] !== $row['_id']; })), 0, $n ?? 8);
@endphp
<div class="pt-wrap sib"><table class="pt pt-mini"><thead><tr><th>نام کالا</th>@foreach($specs as $x)<th>{{ $R::shortHead($x) }}</th>@endforeach<th>قیمت (ریال)</th></tr></thead><tbody>
@foreach($others as $r)
<tr><td class="pt-name"><a href="{{ Rd::path(Rd::uProd($slug, $r['_slug'])) }}">{{ Rd::cleanName($r['نام محصول']) }}</a></td>@foreach($specs as $x)<td data-label="{{ $R::shortHead($x) }}">{{ Rd::cleanVal($r[$x] ?? '') }}</td>@endforeach<td class="pt-price" data-label="قیمت (ریال)"><b class="num">{{ Rd::fmt($r['_price']) }}</b></td></tr>
@endforeach
</tbody></table></div>
