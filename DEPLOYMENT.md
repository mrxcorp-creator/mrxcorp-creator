# 🚀 VatanParvar Yaypan — Production Deployment

> **Domen:** `vatanparvaryaypan.uz`
> **Hosting:** cPanel (umumiy hosting yoki VPS)
> **Davomiyligi:** ~15 daqiqa

---

## 📋 Talablar

| Komponent | Minimum |
|-----------|---------|
| **PHP** | 8.0+ (tavsiya: 8.2) |
| **MySQL / MariaDB** | 5.7+ / 10.3+ |
| **PHP kengaytmalari** | `pdo_mysql`, `mbstring`, `gd`, `curl`, `json`, `openssl`, `fileinfo`, `zlib` |
| **Apache modullari** | `mod_rewrite`, `mod_headers`, `mod_expires`, `mod_deflate` |
| **SSL** | HTTPS sertifikat (cPanel'da AutoSSL bepul) |

---

## 🎯 1-bosqich — Domen va SSL

### 1.1. Domenni cPanel'ga ulash

cPanel → **Domains** → **vatanparvaryaypan.uz** asosiy domen sifatida tayinlanganligini tekshiring.

### 1.2. SSL sertifikat

cPanel → **SSL/TLS Status** → **Run AutoSSL** bosing.
Yoki: **SSL/TLS** → **Install and Manage SSL** orqali Let's Encrypt sertifikatini o'rnating.

### 1.3. Force HTTPS

cPanel → **Domains** → vatanparvaryaypan.uz qatorida **Force HTTPS Redirect** ni yoqing.

> 💡 Bu loyihaning `.htaccess` faylida ham HTTPS redirect bor, lekin cPanel darajasida yoqilsa tezroq ishlaydi.

---

## 🗄️ 2-bosqich — Ma'lumotlar bazasi

cPanel → **MySQL Databases**:

### 2.1. Baza yaratish

- **New Database**: `avtomaktab` → "Create Database"
- Yaratilgan to'liq nom: `wbefkccz_avtomaktab` (cPanel prefiks qo'shadi)

### 2.2. Foydalanuvchi yaratish

- **Add New User**:
  - Username: `avtomaktab` → to'liq: `wbefkccz_avtomaktab`
  - Password: **kuchli parol generate qiling va saqlang!**

### 2.3. Foydalanuvchini bazaga ulash

**Add User to Database** → User va DB ni tanlang → **ALL PRIVILEGES** belgilang → "Make Changes".

---

## 📁 3-bosqich — Fayllarni yuklash

### Variant A: Git orqali (tavsiya etiladi)

cPanel'da SSH ochiq bo'lsa:

```bash
cd ~/public_html
git clone https://github.com/mrxcorp-creator/mrxcorp-creator.git temp
cp -r temp/public_html/. .
cp -r temp/database ../database
rm -rf temp
```

### Variant B: ZIP orqali (cPanel File Manager)

1. GitHub'dan ZIP'ni yuklab oling: **Code → Download ZIP**
2. cPanel → **File Manager** → `public_html` papkasiga kiring
3. **Upload** orqali ZIP'ni yuklang
4. ZIP'ni o'ng tugma → **Extract**
5. `public_html/` ichidagi barcha fayllarni cPanel'ning `public_html/`'iga ko'chiring
6. `database/` papkasini cPanel'ning `home` papkasiga ko'chiring (saytdan tashqarida xavfsizroq)

### 3.1. Papka tuzilmasi to'g'riligini tekshirish

```
~/public_html/
├── index.php
├── install.php          ← buni biz ishlatamiz
├── 404.php / 403.php
├── config/
├── auth/
├── user/
├── admin/
├── api/
├── cron/
├── includes/
├── lang/
├── kesh/                ← yozish ruxsati 0755
├── uploads/             ← yozish ruxsati 0755
├── zaxira_nusxalari/    ← yozish ruxsati 0755
└── ...

~/database/
└── schema.sql
```

### 3.2. Papka ruxsatlari (cPanel File Manager)

- **Fayllar**: `0644`
- **Papkalar**: `0755`
- **Yozish kerak bo'lgan papkalar**: `0755` (yoki ba'zi hostlarda `0775`)

```
kesh/
uploads/
uploads/avatars/
uploads/savollar/
zaxira_nusxalari/
config/
```

> ⚠️ **Hech qachon `0777` ishlatmang** — xavfli! Agar `0755` ishlamasa, hosting yordamiga murojaat qiling.

---

## ⚙️ 4-bosqich — O'rnatuvchini ishga tushirish

Brauzeringizda oching:

```
https://vatanparvaryaypan.uz/install.php
```

Avtomatik 5 bosqichli o'rnatuvchi sizni boshqaradi:

### Bosqich 1: Server tekshiruvi
- PHP versiyasi
- Kerakli kengaytmalar
- Yozish ruxsatlari

Agar ✗ belgisi bo'lsa — hosting yordamiga murojaat qiling.

### Bosqich 2: Ma'lumotlar bazasi

| Maydon | Qiymat |
|--------|--------|
| DB Host | `localhost` |
| DB nomi | `wbefkccz_avtomaktab` |
| DB foydalanuvchi | `wbefkccz_avtomaktab` |
| DB paroli | (2.2-bosqichda yaratgan parolingiz) |

### Bosqich 3: Admin akkaunt

Bosh dasturchi (developer) akkauntini yarating. Bu akkaunt to'liq boshqaruv huquqlariga ega bo'ladi.

| Maydon | Misol |
|--------|-------|
| Ism | Dilshod |
| Familiya | Karimov |
| Telefon | `+998901234567` |
| Parol | (kuchli, kamida 8 belgi) |

### Bosqich 4: Sayt sozlamalari

- **Sayt URL**: `https://vatanparvaryaypan.uz`
- **Aloqa telefoni**: o'zingiz uchun
- **Aloqa email**: `info@vatanparvaryaypan.uz`
- **Telegram (ixtiyoriy)**: keyin ham sozlash mumkin

### Bosqich 5: Yakunlash

✓ `config.local.php` yaratiladi
✓ `installed.lock` qo'yiladi
✓ Ma'lumotlar bazasi yaratiladi
✓ Default tariflar va demo bilet qo'shiladi

> ⚠️ **MUHIM**: Yakunlangach, **`install.php`** faylini darhol o'chiring (o'rnatuvchidagi tugma orqali yoki File Manager'dan).

---

## 💳 5-bosqich — To'lov tizimlarini sozlash

Saytga kiring → **Admin panel → Sozlamalar**.

### 5.1. Click

[Click merchant kabinetida](https://my.click.uz) yangi xizmat yarating va ma'lumotlarni kiriting:

- **Click Merchant ID** — Click panelida ko'rsatiladi
- **Click Service ID** — har xizmatga noyob
- **Click Secret** — Click panelida "Secret key"

**Webhook URL** (Click panelida sozlash):
```
https://vatanparvaryaypan.uz/api/click_webhook.php
```

### 5.2. Payme

[Payme business kabinetida](https://business.payme.uz) yangi merchant qo'shing:

- **Payme Merchant ID** — kabinetda "ID"
- **Payme Key** — kabinetda "Test/Production key"

**Endpoint URL** (Payme panelida sozlash):
```
https://vatanparvaryaypan.uz/api/payme_webhook.php
```

> 🧪 Avval **test rejimida** sinang, keyin production'ga o'ting.

---

## 🤖 6-bosqich — Telegram bot (ixtiyoriy)

### 6.1. Bot yaratish

Telegram'da [@BotFather](https://t.me/BotFather) ga kirib:
- `/newbot` → bot nomini kiriting → username: `vatanparvaryaypan_bot`
- BotFather **bot tokenini** beradi (saqlab oling)

### 6.2. Saytda sozlash

**Admin panel → Sozlamalar**:
- Telegram bot tokeni: `7XXXXXXXXX:AAH...`
- Telegram bot username: `vatanparvaryaypan_bot`
- Admin Telegram ID: o'zingizning Telegram ID (uni [@userinfobot](https://t.me/userinfobot) dan oling)

### 6.3. Webhook o'rnatish

Brauzerda:
```
https://vatanparvaryaypan.uz/cron/sozlash.php?kalit=CRON_KALITI&harakat=webhook_set
```

> `CRON_KALITI` — Admin panel → Sozlamalar → "Cron kaliti" maydonidagi qiymat (avtomatik yaratilgan).

---

## ⏰ 7-bosqich — Cron jobs

cPanel → **Cron Jobs** → **Add New Cron Job**:

### 7.1. Kunlik backup (har kuni 03:00 da)

- **Common Settings**: `Once per day`
- **Command**:
  ```
  /usr/local/bin/php /home/wbefkccz/public_html/cron/backup.php
  ```

> Yo'l aniq bo'lmasa: **cPanel → Terminal** → `which php` buyrug'i bilan toping.

### 7.2. Obuna eslatmasi (har kuni 09:00 da)

- **Minute**: `0`
- **Hour**: `9`
- **Day/Month/Weekday**: `*`
- **Command**:
  ```
  /usr/local/bin/php /home/wbefkccz/public_html/cron/obuna_eslatma.php
  ```

### 7.3. Cron muhitini tekshirish

```bash
/usr/local/bin/php -v
ls -la /home/wbefkccz/public_html/cron/
```

---

## 🔍 8-bosqich — Tekshiruv

### 8.1. Tizim diagnostikasi

Developer akkauntdan kiring → **Admin panel → Diagnostika** (`/check.php`).

Hammasi ✓ bo'lishi kerak:
- ✓ PHP 8.0+
- ✓ Barcha kengaytmalar
- ✓ Yozish ruxsatlari
- ✓ DB ulanish
- ✓ install.php olib tashlangan
- ✓ HTTPS yoqilgan

### 8.2. Funksional sinov

| Sinov | Kutilgan natija |
|-------|-----------------|
| Bosh sahifa: `/` | Yangi yorqin dizayn, tariflar |
| Ro'yxat: `/register` | Yangi foydalanuvchi yaratiladi |
| Kirish: `/login` | Sessiya boshlanadi |
| Test: `/test` | Bilet ro'yxati ko'rsatiladi |
| Demo bilet (№1) | Bepul ishlatiladi |
| To'lov: `/tolov` | Tariflar va Click/Payme tanlovi |
| Profil: `/profil` | Telegram ulanish havolasi |
| Admin: `/admin/` | Sidebar bilan panel |
| 404: `/yoqfayl` | 404 sahifa chiroyli |

### 8.3. Click/Payme test

- Test rejimida 1000 so'mlik to'lov yarating
- To'lov muvaffaqiyatli bo'lganda obuna avtomatik faollashishi kerak
- **Admin → To'lovlar** sahifasida ko'rinishi kerak
- Foydalanuvchining Telegram'iga bildirishnoma kelishi kerak

---

## 🛡️ 9-bosqich — Xavfsizlik checklist

| ✅ | Tekshiruv |
|---|-----------|
| ☐ | `install.php` o'chirilgan |
| ☐ | `config.local.php` git'ga commit qilinmagan |
| ☐ | DB paroli kuchli (12+ belgi, harf+raqam+belgi) |
| ☐ | Developer akkaunt paroli kuchli |
| ☐ | HTTPS yoqilgan va force redirect ishlamoqda |
| ☐ | Cron kaliti 32+ belgili (avto yaratilgan) |
| ☐ | `zaxira_nusxalari/` papkasi web orqali ochilmaydi (403) |
| ☐ | `config/` papkasi web orqali ochilmaydi (403) |
| ☐ | `database/` papkasi web orqali ochilmaydi |
| ☐ | Birinchi kunlik backup ishlangan va Telegram'ga yuborilgan |

---

## 🚨 Tez-tez uchraydigan muammolar

### "500 Internal Server Error"

- **Sabab 1**: PHP versiyasi mos emas
  - cPanel → **Select PHP Version** → 8.2 ni tanlang
  - Kerakli kengaytmalarni belgilang
- **Sabab 2**: `.htaccess` qoidasi qo'llab-quvvatlanmaydi
  - `~/public_html/zaxira_nusxalari/php_errors.log` ni tekshiring

### "Database connection failed"

- DB paroli noto'g'ri → `config/config.local.php` ni tekshiring
- DB foydalanuvchi bazaga ulanmagan → cPanel → MySQL Databases → "Add User to Database"

### "install.php infinite redirect"

- `config/installed.lock` mavjudligini tekshiring (yo'q bo'lsa, install.php bosqich 1 dan boshlanadi)
- Brauzer keshini tozalang (Ctrl+Shift+Delete)

### Rasmlar yuklamaydi

- `uploads/` papkasi yozish ruxsatini tekshiring (0755)
- `gd` PHP kengaytmasi yoqilganligini tekshiring
- cPanel → **PHP Version** → "Switch to PHP Options" → `upload_max_filesize` = 10M

### Telegram bot javob bermaydi

- Bot tokenini tekshiring
- Webhook o'rnatilganligini tekshiring: `/cron/sozlash.php?kalit=...&harakat=me`
- HTTPS sertifikat to'g'ri ishlayotganligini tekshiring (Telegram faqat HTTPS qabul qiladi)

### Click/Payme webhook qaytmaydi

- Click sandbox/production sozlamalarini tekshiring
- Webhook URL'ni qayta tekshiring (https://, oxirida slash bo'lmasin)
- `zaxira_nusxalari/php_errors.log` ni tekshiring

---

## 📊 10-bosqich — Optimallashtirish (ixtiyoriy)

### 10.1. OPcache yoqish

cPanel → **Select PHP Version** → "PHP Extensions" → `opcache` belgilang.

cPanel → **MultiPHP INI Editor** → vatanparvaryaypan.uz:
```ini
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
```

> Production'da `validate_timestamps=0` qo'ying — yangi kod yuklaganda **MultiPHP INI Editor**'dan opcache'ni qayta yoqing.

### 10.2. APCu yoqish (sozlamalar uchun)

cPanel → **Select PHP Version** → "PHP Extensions" → `apcu` belgilang.

### 10.3. Cloudflare CDN (tavsiya)

1. Cloudflare'da bepul akkaunt yarating
2. Domen NS'larini Cloudflare'niki bilan almashtiring
3. **Speed → Optimization → Auto Minify** yoqing
4. **Caching → Browser Cache TTL** = 1 month

---

## 🔄 11-bosqich — Yangilash

Sayt o'rnatilgandan keyin yangilashlar:

```bash
cd ~/public_html
git fetch origin
git checkout main
git pull
```

Yoki cPanel File Manager orqali yangi fayllarni yuklang. **`config.local.php` va `installed.lock`** fayllariga tegmang.

Schema o'zgarishlari bo'lsa: `database/schema.sql` ni qo'lda phpMyAdmin orqali bajaring.

---

## 📞 Yordam

- **Hosting muammolari**: hosting yordamiga murojaat qiling (cPanel → Support)
- **Sayt xatolari**: `zaxira_nusxalari/php_errors.log` faylini ko'ring
- **GitHub**: [Issues](https://github.com/mrxcorp-creator/mrxcorp-creator/issues)

---

✅ Sayt to'liq ishga tushirildi. **vatanparvaryaypan.uz** Internet'da!
