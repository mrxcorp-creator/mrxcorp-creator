# 🚗 VatanParvar Yaypan

> O'zbekistonda avto maktab nazariyasiga onlayn tayyorgarlik platformasi

PHP 8.x + MySQL + Tailwind CDN + Alpine.js — frameworksiz, chaqmoqdek tez.

---

## 🐛 Hal qilingan xatolar (Bug Fixes)

| # | Fayl | Muammo | Yechim |
|---|------|--------|--------|
| 1 | `auth/forgot-password.php` | `telegram_yubor()` `funksiyalar.php` yuklanmay chaqirilgan | `funksiyalar.php` `header.php`dan **oldin** yuklanadi |
| 2 | `api/save_answer.php` | `qolgan_vaqt` har bir javobda yangilangan → vaqt noto'g'ri | `qolgan_vaqt` **o'zgartirilmaydi**, har doim `boshlangan` dan hisoblanadi |
| 3 | `user/test.php` | Timer JavaScript'da ham noto'g'ri | `qolgan = max(0, qolgan_vaqt - elapsed)` |
| 4 | `admin/fikrlar.php` | Faqat bitta til keshi tozalangan | `kesh_tozala()` — barcha til keshlari |
| 5 | `admin/sozlamalar.php` | Faqat bitta til keshi + `click_service_id` yo'q | `kesh_tozala()` + yangi sozlama qo'shildi |
| 6 | `user/profil.php` | `joriy_foydalanuvchi()` static keshi yangilanmagan | `joriy_foydalanuvchi(true)` |
| 7 | `user/payment.php` | Click URL'da `service_id` bo'sh | `click_service_id` sozlamadan o'qiladi |
| 8 | `bot.php` | `/backup` `require_once` → `exit` → bot javobi yuborilmagan | `shell_exec` bilan **asinxron** ishlatiladi |
| 9 | `config/auth.php` | `admin_bolish_kerak()` faqat `exit` qilgan | `403.php` include qilinadi |

---

## 📦 Loyiha tuzilmasi

```
avtotestpro/
├── public_html/
│   ├── index.php              ← Bosh sahifa (keshli)
│   ├── 403.php / 404.php      ← Branded xato sahifalari (YANGI)
│   ├── bot.php                ← Telegram bot webhook (bug fix)
│   ├── .htaccess              ← HTTPS, clean URL, HSTS, gzip
│   │
│   ├── config/
│   │   ├── config.php         ← Konstantalar (REJIM, yo'llar, limitlar)
│   │   ├── database.php       ← PDO singleton + yordamchilar
│   │   └── auth.php           ← Sessiya, til, auth funksiyalar
│   │
│   ├── includes/
│   │   ├── header.php         ← HTML head, Tailwind, Alpine, dizayn tizimi
│   │   ├── footer.php         ← Footer, global JS (apiPost, csrfToken)
│   │   ├── navbar.php         ← Scroll-aware navbar, dropdown
│   │   ├── security.php       ← CSRF, rate-limit, flash, kesh_tozala()
│   │   └── funksiyalar.php    ← pul(), sana(), telegram_yubor(), ...
│   │
│   ├── lang/                  ← 3 til: uz_latn, uz_cyrl, ru
│   ├── auth/                  ← login, register, logout, forgot-password
│   ├── user/                  ← dashboard, test, payment, profil, referal
│   ├── admin/                 ← 9 sahifa + layout (promokodlar YANGI)
│   ├── api/                   ← save_answer, click_webhook, payme_webhook
│   ├── cron/                  ← backup, obuna_eslatma, sozlash
│   ├── kesh/                  ← HTML kesh (til bo'yicha)
│   ├── uploads/               ← WebP rasmlar
│   └── zaxira_nusxalari/      ← .gz backup fayllar
│
└── database/
    └── schema.sql             ← 12 jadval + seed data
```

---

## 🚀 O'rnatish

1. `public_html/` ichidagi fayllarni serverga yuklang
2. `database/schema.sql` ni phpMyAdmin orqali import qiling
3. `config/database.php` da DB ma'lumotlarini kiriting
4. **Admin panel → Sozlamalar** → barcha kalitlarni to'ldiring
5. Telegram webhook o'rnating
6. Cron sozlang

**Default akkaunt:** `+998900000000` / `admin12345`

---

## 🎨 Dizayn tizimi

| Element | Qiymat |
|---------|--------|
| Asosiy fon | `#080D1A` |
| Aksent | `#3B82F6` |
| Yashil | `#22C55E` |
| Sariq | `#F59E0B` |
| Qizil | `#EF4444` |
| Shrift | Manrope (sarlavha) + Inter (matn) |
| Karta | Glassmorphism (blur + rgba) |

---

## 🔐 Xavfsizlik

- ✅ PDO Prepared Statements
- ✅ CSRF tokenlar barcha POST formalarida
- ✅ `e()` = `htmlspecialchars()` — XSS himoya
- ✅ Rate limit: 15 daqiqada 5 ta xato → blok
- ✅ BCRYPT (cost=12) parol hashing
- ✅ Sessiya regenerate kirish paytida
- ✅ 30 daqiqada sessiya ID regenerate
- ✅ HSTS, X-Frame-Options, nosniff sarlavhalari
- ✅ `.htaccess` — config/cron/zaxira papkalari bloklangan

---

## 💰 To'lov tizimlari

| Tizim | Webhook URL |
|-------|-------------|
| Click | `/api/click_webhook.php` |
| Payme | `/api/payme_webhook.php` |

---

## 🤖 Telegram Bot buyruqlari

**Foydalanuvchilar:** `/start <hash>`, `/obuna`, `/natijalar`, `/help`

**Admin:** `/admin` (inline), `/stat`, `/backup`

---

## 🌍 Tillar

`uz_latn` · `uz_cyrl` · `ru`

---

© 2026 VatanParvar Yaypan — [vatanparvaryaypan.uz](https://vatanparvaryaypan.uz)
