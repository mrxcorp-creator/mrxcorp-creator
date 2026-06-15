# 🚗 VatanParvar Yaypan

> Avto maktab nazariyasiga onlayn tayyorgarlik platformasi
> **vatanparvaryaypan.uz**

PHP 8.x + MySQL + Tailwind + Alpine.js asosida qurilgan, frameworksiz, yengil va tezkor SaaS platforma.

---

## 🎨 Yangi yorqin dizayn

- **Asosiy fon**: `#070B14` (chuqur cyber)
- **Brand gradient**: `#06B6D4 → #8B5CF6 → #EC4899` (cyan → violet → pink)
- **Glassmorphism** kartalar, **aurora** orqa fon, **ring-grad** ramkalar
- **2 til**: O'zbek (Lotin) va Ўзбек (Кирилл)

---

## 📦 Tuzilma

```
public_html/
├── index.php               Bosh sahifa (1 soatlik kesh)
├── 404.php / 403.php       Xato sahifalari
├── bot.php                 Telegram bot vebhuk
├── .htaccess               Apache (HTTPS, chiroyli URL'lar, xavfsizlik)
├── robots.txt / sitemap.xml
│
├── assets/img/             Logo, banner, OG cover (SVG)
│
├── config/
│   ├── config.php          Global konstantalar (config.local.php avtomatik yuklanadi)
│   ├── config.local.example.php  Lokal sirlarni saqlash uchun namuna
│   ├── database.php        PDO + db_qator/db_barcha/db_bajar/db_qiymat
│   └── auth.php            Sessiya, t() tarjima, joriy_foydalanuvchi
│
├── includes/
│   ├── header.php          Tailwind + Alpine + dizayn tizimi
│   ├── footer.php          Pastki panel + til tanlash
│   ├── navbar.php          Universal navbar (logo bilan)
│   ├── security.php        CSRF, rate-limit, telefon validatsiya
│   └── funksiyalar.php     pul(), sana(), telegram_yubor(), rasm_saqla()
│
├── lang/                   uz_latn.php, uz_cyrl.php
├── auth/                   login, register, forgot-password, logout (POST + CSRF)
├── user/                   index, test, payment, profil, referal
├── admin/                  Sidebar layout + 8 ta CRUD sahifa
├── api/                    save_answer, fikr_qoshish, click_webhook, payme_webhook
├── cron/                   backup (mysqldump --defaults-extra-file), obuna_eslatma, sozlash
├── kesh/                   Bosh sahifa keshi (til-bo'yicha)
├── uploads/                WebP rasmlar va avatarlar
└── zaxira_nusxalari/       DB backup + error_log
```

---

## 🚀 O'rnatish

### 1. Fayllarni yuklash

`public_html/` ichidagi barcha fayllarni saytingizning **public_html/** papkasiga yuklang.

### 2. Lokal sozlama

`public_html/config/` papkasiga `config.local.php` yarating (namuna: `config.local.example.php`):

```php
<?php
define('REJIM', 'production');
define('SAYT_URL', 'https://vatanparvaryaypan.uz');
define('DB_HOST', 'localhost');
define('DB_NAME', 'wbefkccz_avtomaktab');
define('DB_USER', 'wbefkccz_avtomaktab');
define('DB_PASS', 'BU_YERGA_PAROLNI_QO_YING');
```

> ⚠️ `config.local.php` **git'ga commit qilinmaydi** (`.gitignore`'da yopilgan). DB parolingizni hech qachon git'ga qo'ymang.

### 3. Ma'lumotlar bazasi

phpMyAdmin → DB nomi `wbefkccz_avtomaktab` → Import → `database/schema.sql`

### 4. Default akkaunt

- **Telefon**: `+998900000000`
- **Parol**: `admin12345`

> ⚠️ Birinchi kirgandan so'ng Profil → Parolni o'zgartirish orqali parolni o'zgartiring.

### 5. Sozlamalar (admin panel)

Saytga kiring → **Admin panel → Sozlamalar**:

- Sayt nomi va shiori
- Aloqa telefoni va email
- Telegram bot tokeni va admin ID
- Click `merchant_id` + `service_id` + `secret`
- Payme `merchant_id` + `key`
- Cron kalit (32+ belgi)

### 6. Telegram vebhuk

```
https://SIZNINGSAYT.uz/cron/sozlash.php?kalit=CRON_KALIT&harakat=webhook_set
```

### 7. Cron (cPanel → Cron Jobs)

```bash
0 3 * * * /usr/bin/php /home/USER/public_html/cron/backup.php
0 9 * * * /usr/bin/php /home/USER/public_html/cron/obuna_eslatma.php
```

---

## 🔐 Xavfsizlik

| Mexanizm | Holat |
|----------|-------|
| PDO Prepared Statements | ✅ |
| CSRF token (POST + AJAX) | ✅ |
| XSS himoya (`e()`) | ✅ |
| BCRYPT parol | ✅ |
| Rate limit (15 daq / 5 ta urinish) | ✅ |
| Sessiya regenerate kirgandan keyin | ✅ |
| HTTPS majburiy + HSTS-ready headers | ✅ |
| Cookie HttpOnly + Secure + SameSite | ✅ |
| Logout endi POST + CSRF | ✅ |
| Telefon validatsiyasi `9XXXXXXXX` aniq | ✅ |
| `.htaccess` himoyasi (config, zaxira) | ✅ |
| DB parol git'da YO'Q (config.local.php) | ✅ |
| Admin sozlamalar allowlist | ✅ |
| Mysqldump `--defaults-extra-file` (process list'da parol yo'q) | ✅ |

---

## 💰 To'lov

- **Click webhook**: `https://SIZNINGSAYT.uz/api/click_webhook.php`
- **Payme webhook**: `https://SIZNINGSAYT.uz/api/payme_webhook.php`

---

## 🤖 Telegram bot

**Foydalanuvchilar**: `/start <hash>`, `/help`, `/obuna`, `/natijalar`
**Admin**: `/admin`, `/stat`, `/backup`

---

## 🌍 Tillar

| Til | Kalit |
|-----|-------|
| O'zbek (Lotin) | `uz_latn` |
| Ўзбек (Кирилл) | `uz_cyrl` |

`<?= t('kalit_nomi') ?>` orqali ishlatiladi.

---

## ⚡ Tezlik

- Bosh sahifa **1 soatlik kesh** (til-bo'yicha)
- Brauzer keshi (rasm 1 oy, CSS/JS 1 hafta)
- Gzip siqish (HTML, CSS, JS, SVG)
- Rasmlar avtomatik **WebP** + 800px gacha kichraytirish
- DB indexlar barcha asosiy ustunlarda
- Cron: `kirish_urinishlar` jadvali tunda tozalanadi

---

## 🎯 Rol tizimi

| Huquq | Developer | Admin | User |
|-------|:--:|:--:|:--:|
| Server xususiyati | ✅ | ❌ | ❌ |
| Rol o'zgartirish | ✅ | ❌ | ❌ |
| Sayt sozlamalari | ✅ | ✅ | ❌ |
| Bilet/savol CRUD | ✅ | ✅ | ❌ |
| To'lov tasdiqlash | ✅ | ✅ | ❌ |
| Test ishlash | ❌ | ❌ | ✅ |

---

© 2026 VatanParvar Yaypan
