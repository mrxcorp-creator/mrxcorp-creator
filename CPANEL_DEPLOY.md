# 🚀 cPanel'ga joylash yo'riqnomasi

VatanParvar Yaypan loyihasini cPanel hostingga joylash bo'yicha to'liq qo'llanma.

---

## 📋 Talablar

- **PHP**: 8.0 yoki undan yuqori
- **MySQL/MariaDB**: 5.7+ / 10.3+
- **Kerakli PHP kengaytmalari**: `pdo_mysql`, `mbstring`, `gd`, `curl`, `openssl`, `json`, `fileinfo`
- **Apache** mod_rewrite, mod_headers, mod_deflate yoqilgan
- **HTTPS** sertifikati (Let's Encrypt yoki cPanel'ning bepul SSL'i)

---

## 1️⃣ Loyihani yuklash

### Variant A — File Manager orqali

1. cPanel'ga kiring → **File Manager**
2. `public_html/` papkasiga kiring
3. **Upload** bosing va `public_html/` ichidagi BARCHA fayllarni yuklang
   - ⚠️ `public_html/` papkasini emas, ichidagi fayllarni yuklang!
4. `database/schema.sql` ni alohida saqlab qo'ying (bazaga import uchun)

### Variant B — Git orqali (tavsiya etamiz)

cPanel **Git Version Control** orqali:
```
Repository URL:  https://github.com/mrxcorp-creator/mrxcorp-creator.git
Branch:          main (yoki kerakli branch)
Repository Path: /home/USERNAME/repos/mrxcorp-creator
```

So'ng `public_html/`'dagi mavjud fayllarni o'chirib, repodan symlink qiling yoki public_html'ni o'zgartiring.

> ⚠️ Repodagi `public_html/` ni cPanel'dagi `public_html/` ga moslang. Eng oddiy yo'l: repo ichidagi `public_html/*` ni `~/public_html/` ichiga ko'chirish.

---

## 2️⃣ Ma'lumotlar bazasini yaratish

cPanel → **MySQL Databases**:

1. **Yangi DB yarating**: `USERNAME_avtomaktab` (cPanel prefiksini avto qo'shadi)
2. **Yangi user yarating**: `USERNAME_avtoadmin` + kuchli parol
3. Userni DBga **ALL PRIVILEGES** bilan biriktiring
4. DB ma'lumotlarini eslab qoling — install vaqtida kerak bo'ladi

> ⚠️ Manual SQL import qilish **shart emas** — `install.php` o'zi import qiladi.

---

## 3️⃣ O'rnatish ustasini ishga tushirish

Brauzeringizda oching:

```
https://sizning-domeningiz.uz/install.php
```

Va 6 ta qadamdan o'ting:

| # | Qadam | Tafsilotlar |
|---|-------|-------------|
| 1 | **Talablar** | PHP versiyasi, kengaytmalar, papka ruxsatlari avto-tekshiriladi |
| 2 | **Baza** | cPanel'da yaratgan DB host (`localhost`), nom, user, parol |
| 3 | **Schema** | `database/schema.sql` avto-import (yoki o'zingizning .sql faylingizni yuklang) |
| 4 | **Admin** | Birinchi developer akkaunti — ism, telefon, parol (8+ belgi) |
| 5 | **Sozlamalar** | Sayt URL, telegram bot, click/payme — keyin ham qo'shsa bo'ladi |
| 6 | **Yakunlash** | `install.lock` yaratiladi, sahifa qulflanadi |

> ⚠️ Yakunlanishi bilan **`install.php` faylini File Manager'dan o'chirib tashlang** (eng yaxshi himoya).

---

## 4️⃣ Papka ruxsatlarini sozlash

File Manager → quyidagi papkalarning **chmod** ni tekshiring:

| Papka | Ruxsat |
|-------|--------|
| `kesh/` | 755 (yoki 775) |
| `loglar/` | 755 (yoki 775) |
| `uploads/` | 755 |
| `uploads/avatars/` | 755 |
| `uploads/savollar/` | 755 |
| `zaxira_nusxalari/` | 755 |
| `config/` | 755 |
| `config/database.php` | 644 |

Hamma `.php` fayllar — **644**, papkalar — **755**.

---

## 5️⃣ Cron Jobs (cPanel → Cron Jobs)

### Kunlik tozalash (eski loglar, sessiyalar, obunalar)
```
0 3 * * *  /usr/local/bin/php /home/USERNAME/public_html/cron/log_tozalash.php
```

### Obuna eslatmalari (har 12 soat)
```
0 */12 * * *  /usr/local/bin/php /home/USERNAME/public_html/cron/obuna_eslatma.php
```

### DB zaxira (haftada 1 marta)
```
0 4 * * 0  /usr/local/bin/php /home/USERNAME/public_html/cron/backup.php
```

> 💡 `cron_kalit` sozlamasi xak.php → Sozlamalar tab'idan ko'rishingiz mumkin (yoki DB'da `sozlamalar` jadvalida).

---

## 6️⃣ SSL (HTTPS) sertifikati

cPanel → **SSL/TLS Status** → AutoSSL'ni yoqing yoki Let's Encrypt'ni o'rnating.

`.htaccess` HTTPS'ni avto-majbur qiladi (lekin `install.php` istisno).

---

## 7️⃣ Telegram bot vebhukini sozlash

Sozlamalar tugagandan so'ng:

1. Browserda oching:
   ```
   https://sizning-domen.uz/cron/sozlash.php?kalit=CRON_KALIT_QIYMATI&harakat=webhook_set
   ```
2. Javob `"ok":true` chiqsa — vebhuk o'rnatildi
3. Botingizga yozib sinab ko'ring

> Telegram bot tokenini xak.php → Sozlamalar tab'idan tekshirishingiz mumkin.

---

## 8️⃣ Monitoring panelni ochish

```
https://sizning-domen.uz/xak.php
```

Kirish:
- **Foydalanuvchi**: developer akkaunt (install vaqtida yaratdingiz)
- **Parol**: install vaqtida ko'rsatilgan `xak.php paroli`

Bu yerda ko'rasiz:
- 📊 Server holati (CPU, memory, disk, PHP versiyasi)
- 📜 Loglar (xato/kirish/xavfsizlik)
- ⚠️ Hujum urinishlari va ularning manbalari
- 🚫 Bloklangan IP'lar — qo'lda blok/yechish
- 📈 Yuklama grafiki (oxirgi 24 soat)
- 🗂️ Muhim fayllar integriteti (md5)
- ⚙️ Sayt sozlamalari

---

## 9️⃣ Xavfsizlik tekshiruvlari

`xak.php` → **Umumiy** tab'idagi "Xavfsizlik holati" bo'limi avto-tekshiradi:

- ✅ install.lock fayli mavjud
- ✅ HTTPS yoqilgan
- ✅ config/ bloklangan
- ✅ loglar/ bloklangan
- ✅ display_errors o'chiq
- ✅ X-Frame-Options yuborilmoqda
- ✅ Sessiya cookie HttpOnly + SameSite
- ✅ PHP versiyasi 8.0+

Hammasi yashil bo'lishi kerak.

---

## 🔧 Ko'p uchraydigan muammolar

### `403 Forbidden` xatosi
- `.htaccess` to'g'ri yuklanmaganmi?
- `mod_rewrite` yoqilganmi? (cPanel'da odatda yoqilgan)
- Fayl ruxsatlari to'g'rimi? (`.htaccess` — 644)

### `500 Internal Server Error`
- `loglar/php_errors.log` ni tekshiring
- yoki `xak.php → Loglar → xato` tab'iga qarang

### "DB ulanish xatosi"
- `config/database.php` dagi DB nomi/user/parol to'g'rimi?
- cPanel MySQL'da **user DB ga biriktirilganmi**?
- DB host `localhost` (odatda) yoki cPanel'dagi maxsus host?

### Sessiyalar saqlanmayapti
- `kesh/` papkasiga yozish ruxsati bormi? (755)
- cPanel'da PHP `session.save_path` to'g'rimi?

### Telegram bot ishlamayapti
- Vebhuk o'rnatilganmi? `cron/sozlash.php?kalit=...&harakat=me`
- HTTPS sertifikat to'g'rimi? Telegram faqat valid SSL bilan ishlaydi.

---

## 📞 Qo'llab-quvvatlash

Muammo bo'lsa:
1. `xak.php → Loglar → xato` tab'iga qarang
2. Aniq xato matnini va qadamlarni yozing
3. Issue oching: https://github.com/mrxcorp-creator/mrxcorp-creator/issues

---

## ✅ O'rnatishdan keyingi tekshiruv ro'yxati

- [ ] `https://domen.uz/` — bosh sahifa ochiladimi?
- [ ] `https://domen.uz/login` — login sahifasi?
- [ ] `https://domen.uz/register` — yangi user yaratib ko'ring
- [ ] `https://domen.uz/admin/` — developer bilan kira olasizmi?
- [ ] `https://domen.uz/xak.php` — monitoring ochiladimi?
- [ ] `https://domen.uz/install.php` — **403** qaytaradimi (yaxshi!)
- [ ] `https://domen.uz/config/database.php` — **403** qaytaradimi
- [ ] `https://domen.uz/loglar/` — **403** qaytaradimi
- [ ] HTTPS yashil qulf ko'rsatadimi
- [ ] `xak.php → Umumiy` — xavfsizlik holati barcha yashilmi
- [ ] Cron jobs sozlanganmi
- [ ] Telegram bot vebhuki o'rnatilganmi (agar ishlatadigan bo'lsangiz)

Hammasi tayyor bo'lsa, sayt to'liq production-ready! 🎉
