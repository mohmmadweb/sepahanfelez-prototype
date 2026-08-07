#!/usr/bin/env bash
# ارسال پروتوتایپ به گیت‌هاب — پس از اینکه مخزن خالی را روی GitHub ساختید.
#
#   ./push.sh                       ← نام پیش‌فرض: sepahanfelez-prototype
#   ./push.sh نام-دلخواه-مخزن
#
# از کلید SSH اکانت mohmmadweb استفاده می‌کند (github-mohmmadweb در ~/.ssh/config).

set -euo pipefail
REPO="${1:-sepahanfelez-prototype}"
OWNER="mohmmadweb"
REMOTE="git@github-mohmmadweb:${OWNER}/${REPO}.git"

cd "$(dirname "$0")"

echo "▸ بررسی دسترسی SSH…"
# گیت‌هاب برای اتصال SSH همیشه خروجی ۱ می‌دهد؛ با `set -o pipefail` این
# باعث می‌شد بررسی درست، شکست‌خورده تفسیر شود. خروجی را جدا می‌گیریم.
AUTH="$(ssh -o BatchMode=yes -T git@github-mohmmadweb 2>&1 || true)"
if ! printf '%s' "$AUTH" | grep -q "Hi ${OWNER}"; then
  echo "✗ کلید SSH اکانت ${OWNER} جواب نداد." >&2
  exit 1
fi
echo "  ✓ احراز هویت به‌عنوان ${OWNER}"

echo "▸ بررسی وجود مخزن ${OWNER}/${REPO}…"
if ! git ls-remote "$REMOTE" >/dev/null 2>&1; then
  cat >&2 <<MSG

✗ مخزن ${OWNER}/${REPO} پیدا نشد یا خالی نیست.

  ابتدا آن را بسازید:
    https://github.com/new
    Owner: ${OWNER}
    Repository name: ${REPO}
    Public
    ⚠️ هیچ‌کدام از README / .gitignore / license را تیک نزنید

  بعد دوباره همین اسکریپت را اجرا کنید.
MSG
  exit 1
fi
echo "  ✓ مخزن در دسترس است"

git remote remove origin 2>/dev/null || true
git remote add origin "$REMOTE"

echo "▸ ارسال شاخه main…"
git push -u origin main

cat <<MSG

✓ کد روی گیت‌هاب رفت: https://github.com/${OWNER}/${REPO}

  دو گام باقی مانده — هر دو در مرورگر:

  ۱) فعال‌کردن GitHub Pages
     https://github.com/${OWNER}/${REPO}/settings/pages
     Source: Deploy from a branch
     Branch: main   /  (root)     → Save
     در فیلد Custom domain بنویسید: sepahanfelez.lenzit.ir
     (فایل CNAME از قبل در مخزن هست، معمولاً خودش پر می‌شود)

  ۲) رکورد DNS در Cloudflare (دامنه lenzit.ir)
     Type    : CNAME
     Name    : sepahanfelez
     Target  : ${OWNER}.github.io
     Proxy   : DNS only  ← ابری خاکستری، نه نارنجی
     TTL     : Auto

  چند دقیقه بعد:  https://sepahanfelez.lenzit.ir
MSG
