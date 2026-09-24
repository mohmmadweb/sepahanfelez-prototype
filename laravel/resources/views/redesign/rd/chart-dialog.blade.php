<dialog id="chart-dialog" class="chart-dialog" aria-label="نمودار قیمت">
  <div class="chart-dialog-in">
    <button type="button" class="chart-close" data-close aria-label="بستن">×</button>
    <div class="chart" id="chart-modal" data-price="0" data-prev="0" data-key="" data-days="90"
         data-sample="{{ config('redesign.chart.sample_series') ? '1' : '0' }}"
         data-note-real="تاریخچه‌ی واقعی ثبت قیمت؛ هر پله یک تغییر قیمت ثبت‌شده است."
         data-note-sample="تاریخچه‌ی کافی ثبت نشده؛ سری نمایشی است و فقط آخرین ثبت و قیمت روز واقعی‌اند.">
      <div class="chart-head"><div class="chart-title"></div>
        <div class="chart-ranges" role="group" aria-label="بازه">
          @include('rd.chart-ranges', ['id' => 'chart-modal', 'days' => 90])</div></div>
      @include('rd.chart-panel', ['id' => 'chart-modal'])
      <div class="chart-svg"></div><div class="chart-stats"></div>
      <p class="chart-note"></p>
    </div>
  </div>
</dialog>
