@php
    $cat = \App\Support\Redesign::category($slug);
    $specs = array_values(array_filter($cat['specs'], function ($x) { return $x !== 'محل بارگیری'; }));
    $ctitle = $cat['title'];
@endphp
<div class="tablescroll">
  <table class="spectable">
    <caption>مشخصات فنی کامل — {{ Rd::fa(count($cat['rows'])) }} نوع {{ $ctitle }}</caption>
    <thead><tr><th>نام کالا</th>@foreach($specs as $x)<th>{{ $x }}</th>@endforeach</tr></thead>
    <tbody>
    @foreach($cat['rows'] as $r)
      <tr><td>{{ Rd::link(Rd::prodUrl($slug, $r['_slug']), Rd::cleanName($r['نام محصول'])) }}</td>@foreach($specs as $x)<td class="num">{{ Rd::cleanVal($r[$x] ?? '') }}</td>@endforeach</tr>
    @endforeach
    </tbody>
  </table>
</div>
