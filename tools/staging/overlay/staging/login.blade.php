<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title>ورود مدیر (نسخه‌ی آزمایشی)</title>
<style>body{font-family:Tahoma,sans-serif;background:#f3f5f8;display:grid;place-items:center;min-height:100vh;margin:0}
form{background:#fff;padding:32px;border-radius:12px;box-shadow:0 4px 20px #0001;width:min(360px,90vw)}
h1{font-size:18px;margin:0 0 6px}p{color:#667;font-size:13px;margin:0 0 18px;line-height:1.8}
input,button{width:100%;box-sizing:border-box;padding:12px;font:inherit;border-radius:8px;border:1px solid #ccd}
button{margin-top:12px;background:#1d4ed8;color:#fff;border:0;cursor:pointer}.e{color:#b91c1c;font-size:13px;margin-top:10px}</style></head>
<body><form method="post" action="/_staging/login">@csrf
<h1>ورود به پنل مدیریت</h1><p>این نسخه‌ی آزمایشی است و پیامک ارسال نمی‌کند؛ ورود مدیر با رمز است. هیچ تغییری اینجا روی سایت اصلی اثر ندارد.</p>
<input type="password" name="password" placeholder="رمز" autofocus required>
<button>ورود</button>@if($error)<div class="e">{{ $error }}</div>@endif</form></body></html>
