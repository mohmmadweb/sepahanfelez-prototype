# پچ ۲ — دارایی‌ها و بیلد

## `webpack.mix.js`

دو خط اضافه، کنار خطوط موجود:

```js
mix.js("resources/site/js/redesign.js"     ,  "files/js/redesign.js");
```

و در فایل `resources/site/scss/main.scss` این خط به انتهای ایمپورت‌ها:

```scss
@import "redesign";
```

سپس `npm run prod`.

> `redesign.js` هیچ وابستگی‌ای ندارد (نه jQuery، نه Chart.js)، پس لازم نیست
> در `mix.extract` بیاید و به `vendor.js` دست نمی‌زند.

## عکس کارشناسان

چهار فایل موقت در `public_html/assets/experts/` کپی شوند:
`expert-1.svg` تا `expert-4.svg` (از پوشه‌ی `assets/experts/` پروتوتایپ).
با عکس واقعی جایگزین می‌شوند؛ نسبت ۱:۱ و حداقل ۱۶۰ پیکسل.

## master layout

هیچ تغییری لازم **نیست**:
- گفتینو از قبل در `master.blade.php` هست (همان شناسه‌ی `fugB8i`).
- `Asset::url()` نسخه‌بندی را انجام می‌دهد.
- آیکون‌ها از Bootstrap Icons می‌آیند که از قبل لود می‌شود.

تنها نکته: `build/trim-icon-css.js` آیکون‌های بی‌استفاده را حذف می‌کند و
نسخه‌ی جدید چهار آیکون تازه به کار می‌برد —
`bi-graph-up`، `bi-person-badge`، `bi-calendar3`، `bi-caret-up-fill`.
اگر در فهرست نگه‌داشته‌شده‌ی آن اسکریپت نیستند، اضافه شوند.
