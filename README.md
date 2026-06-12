# 🚗 VatanParvar Yaypan

> Avto maktab nazariyasiga onlayn tayyorgarlik platformasi
> **vatanparvaryaypan.uz**

PHP 8.x + MySQL + Tailwind CDN + Alpine.js bilan qurilgan, frameworksiz, chaqmoqdek tez ishlovchi mukammal platforma.

---

## 📦 Loyiha tuzilmasi

```
vatanparvar/
├── public_html/                ← Web root (saytda ko'rinadigan papka)
│   ├── index.php               ← Bosh sahifa (1 soatlik kesh)
│   ├── 404.php / 403.php       ← Xato sahifalari
│   ├── bot.php                 ← Telegram bot vebhuk
│   ├── .htaccess               ← Apache (HTTPS, chiroyli URL'lar, xavfsizlik)
│   ├── robots.txt / sitemap.xml
│   │
│   ├── config/                 ← (himoyalangan) Konfiguratsiya
│   │   ├── database.php        ← PDO ulanish + db_qator/db_barcha/...
│   │   ├── config.php          ← Global konstantalar
│   │   └── auth.php            ← Sessiya middleware, t() tarjima
│   │
│   ├── includes/
│   │   ├── header.php          ← Tailwind+Alpine CDN, glassmorphism
│   │   ├── footer.php          ← Til tanlash, anti-copy JS
│   │   ├── navbar.php          ← Univesal navbar
│   │   ├── security.php        ← CSRF, rate-limit, telefon
│   │   └── funksiyalar.php     ← pul(), sana(), telegram_yubor()
│   │
│   ├── lang/                   ← Tarjimalar (3 til)
│   │   ├── uz_latn.php  uz_cyrl.php  ru.php
│   │
│   ├── auth/                   ← Login/register/forgot/logout
│   ├── user/                   ← Dashboard, test, payment, profil, referal
│   ├── admin/                  ← 8 ta admin sahifasi + sidebar layout
│   ├── api/                    ← AJAX va to'lov vebhuklari
│   ├── cron/                   ← (himoyalangan) Backup, eslatma
│   ├── kesh/                   ← Bosh sahifa keshi
│   ├── uploads/                ← WebP rasmlar va avatarlar
│   └── zaxira_nusxalari/       ← (himoyalangan) DB backup
│
├── database/
│   └── schema.sql              ← MySQL sxema + boshlang'ich ma'lumot
│
└── README.md                   ← Bu fayl
```

---

## 🚀 O'RNATISH (Xost-1000)

### 1. Faylni serverga yuklash

`public_html/` papkasi ichidagi BARCHA fayllarni saytingizning **public_html/** papkasiga yuklang (FTP yoki cPanel File Manager orqali).

### 2. Ma'lumotlar bazasini yaratish

**phpMyAdmin** orqali yangi DB yarating: `wbefkccz_avtomaktab`

So'ngra `database/schema.sql` faylini import qiling.

### 3. DB ulanishini tekshirish

`config/database.php` ichida quyidagi qiymatlar to'g'ri ekanligini tekshiring:
```php
DB_HOST = 'localhost'
DB_NAME = 'wbefkccz_avtomaktab'
DB_USER = 'wbefkccz_avtomaktab'
DB_PASS = 'FrHCuXUP6RfY4XnzDGBw'
```

### 4. Default akkaunt

Default developer akkaunt:
- **Telefon:** `+998900000000`
- **Parol:** `admin12345`

⚠️ **Birinchi kirgandan so'ng parolni o'zgartiring!**

### 5. Sozlamalar (admin panel orqali)

Saytga kiring → **Admin panel → Sozlamalar** bo'limidan:
- Sayt nomi va shiori
- Aloqa telefoni va email
- **Telegram bot tokeni** (BotFather'dan oling)
- **Telegram admin ID** (admin uchun bildirishnoma)
- Click va Payme parametrlari
- Cron kalit (zaxira uchun)

### 6. Telegram bot vebhukini o'rnatish

Sozlamalardan token va kalit kiritgandan so'ng:
```
https://vatanparvaryaypan.uz/cron/sozlash.php?kalit=KALIT&harakat=webhook_set
```

### 7. Cron sozlash (cPanel Cron Jobs)

```bash
# Tungi backup (har kuni 03:00)
0 3 * * * /usr/bin/php /home/USER/public_html/cron/backup.php

# Obuna eslatma (har kuni 09:00)
0 9 * * * /usr/bin/php /home/USER/public_html/cron/obuna_eslatma.php
```

### 8. Papkalar ruxsatini berish

```
chmod 755 kesh/
chmod 755 uploads/
chmod 755 zaxira_nusxalari/
```

---

## 🎨 DIZAYN TIZIMI

| Element | Qiymat |
|---------|--------|
| Asosiy fon | `#0A0F1E` (qora-ko'k) |
| Aksent | `#3B82F6` (neon ko'k) |
| Yashil (to'g'ri) | `#22C55E` |
| Qizil (xato) | `#EF4444` |
| Asosiy matn | `#F4F4FF` |
| Sirpangan matn | `#8A99B8` |

**Kartalar:** Glassmorphism (rgba(255,255,255,0.05) + blur(16px))
**Shrift:** Manrope (sarlavhalar) + Inter (matn)

---

## 🔐 XAVFSIZLIK

- ✅ PDO Prepared Statements (SQL injection yo'q)
- ✅ CSRF tokenlar barcha POST formalarda
- ✅ XSS himoya — `htmlspecialchars()` va `e()` funksiyasi
- ✅ Rate limit — 15 daqiqada 5 ta xato login → IP block
- ✅ BCRYPT parol hashing
- ✅ Sessiya regenerate (kirgandan so'ng)
- ✅ `.htaccess` — config/cron/zaxira papkalari yopiq
- ✅ Anti-copy + F12 himoya (test sahifasida)

---

## 💰 TO'LOV TIZIMI

### Click webhook URL:
```
https://vatanparvaryaypan.uz/api/click_webhook.php
```

### Payme webhook URL:
```
https://vatanparvaryaypan.uz/api/payme_webhook.php
```

Sozlamalardan **merchant_id** va **secret/key** kiritish kerak.

---

## 🤖 TELEGRAM BOT BUYRUQLARI

**Foydalanuvchilar uchun:**
- `/start <hash>` — akkauntni botga ulash
- `/help` — yordam
- `/obuna` — obuna holati
- `/natijalar` — oxirgi natijalar

**Adminlar uchun:**
- `/admin` — admin panel (inline tugmalar)
- `/stat` — statistika
- `/backup` — DB zaxirasini olish

---

## 🎯 ROL TIZIMI

| Huquq | Developer | Admin | User |
|-------|:---------:|:-----:|:----:|
| Server xususiyati | ✅ | ❌ | ❌ |
| Admin qo'shish | ✅ | ❌ | ❌ |
| Sayt sozlamalari | ✅ | ✅ | ❌ |
| Bilet/savol CRUD | ✅ | ✅ | ❌ |
| To'lovlarni tasdiqlash | ✅ | ✅ | ❌ |
| Test ishlash | ❌ | ❌ | ✅ |
| Obuna olish | ❌ | ❌ | ✅ |

---

## 📊 DB JADVALLAR

12 ta jadval: `foydalanuvchilar`, `tariflar`, `obunalar`, `biletlar`, `savollar`, `natijalar`, `tolovlar`, `promo_kodlar`, `referallar`, `sozlamalar`, `fikrlar`, `kirish_urinishlar`

---

## 🌍 KO'P TILLI TIZIM

3 til qo'llab-quvvatlanadi:
- 🇺🇿 O'zbek (Lotin) — `uz_latn`
- 🇺🇿 Ўзбек (Кирилл) — `uz_cyrl`
- 🇷🇺 Русский — `ru`

Foydalanish: `<?= t('kalit_nomi') ?>`

---

## 📈 ISHLASH TEZLIGI

- Bosh sahifa **1 soatlik kesh** (faqat mehmonlar uchun)
- Rasmlar avtomatik **WebP** + 800px gacha kichraytirish
- Brauzer keshi (1 oy)
- Gzip siqish
- Tailwind va Alpine **CDN** orqali (parallel yuklanish)

---

## 🛠️ FAYLLAR LISENZIYASI

Faqat **VatanParvar Yaypan** loyihasi uchun yaratildi.
© 2026 VatanParvar Yaypan
