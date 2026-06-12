# Ubuntu'da O'rnatish

VatanParvar Yaypan loyihasini Ubuntu (yoki Debian) tizimida ishga tushirish.

## ⚡ Tezkor o'rnatish (1 buyruq)

```bash
git clone https://github.com/mrxcorp-creator/mrxcorp-creator.git ~/vatanparvar
cd ~/vatanparvar
git checkout lotin-kirill-avto-translit
bash setup.sh
```

Skript sizdan **5 ta savol** so'raydi va qolganini avtomatik qiladi:

1. **Sayt URL** (default: `http://localhost:8080`)
2. **MySQL bazasi nomi** (default: `vatanparvar`)
3. **MySQL foydalanuvchisi** (default: `vp_user`)
4. **MySQL paroli** (Enter — avtomatik kuchli parol)
5. **MySQL ROOT paroli** (DB yaratish uchun)
6. **Rejim** — `production` yoki `development`

So'ngra skript:
- ✅ PHP, MySQL va kerakli paketlarni o'rnatadi
- ✅ MySQL bazasini va foydalanuvchini yaratadi
- ✅ Schema va 3 ta migration'ni yuklaydi
- ✅ `config.php` va `database.php` ni siz bergan parametrlar bilan yangilaydi
- ✅ Papka ruxsatlarini sozlaydi
- ✅ Keshni tozalaydi
- ✅ Cron xavfsizlik kalitini yaratadi
- ✅ Telegram bot tokenini sozlash imkonini beradi (ixtiyoriy)
- ✅ PHP serverni ishga tushiradi

## 🔑 Birinchi kirish

Skript tugagandan keyin **http://localhost:8080** ga o'ting:

| | |
|---|---|
| **Telefon** | `+998 90 000 00 00` |
| **Parol** | `admin12345` |
| **Rol** | Developer |

⚠️ **Birinchi kirgandan keyin** profil sahifasidan parolni o'zgartiring!

## 🛠️ Qo'lda o'rnatish

Avtomatik skript ishlamasa, qo'lda quyidagicha:

### 1. Paketlarni o'rnatish

```bash
sudo apt update
sudo apt install -y php php-cli php-mysql php-mbstring \
    php-xml php-curl php-gd php-zip php-intl \
    mysql-server git
```

### 2. Loyihani klon qilish

```bash
git clone https://github.com/mrxcorp-creator/mrxcorp-creator.git ~/vatanparvar
cd ~/vatanparvar
git checkout lotin-kirill-avto-translit
```

### 3. MySQL bazasini sozlash

```bash
sudo mysql <<SQL
CREATE DATABASE vatanparvar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'vp_user'@'localhost' IDENTIFIED BY 'KUCHLI_PAROL';
GRANT ALL ON vatanparvar.* TO 'vp_user'@'localhost';
FLUSH PRIVILEGES;
SQL

# Schema va migration'lar
mysql -u vp_user -p vatanparvar < database/schema.sql
mysql -u vp_user -p vatanparvar < database/migration_2_til_qollab_quvvatlash.sql
mysql -u vp_user -p vatanparvar < database/migration_3_dizayn_reklama.sql
```

### 4. Config fayllarni tahrirlash

`public_html/config/config.php`:
```php
define('REJIM', 'development');                    // yoki 'production'
define('SAYT_URL', 'http://localhost:8080');       // sizning URL
```

`public_html/config/database.php`:
```php
define('DB_NAME', 'vatanparvar');
define('DB_USER', 'vp_user');
define('DB_PASS', 'KUCHLI_PAROL');
```

### 5. Papka ruxsatlari

```bash
mkdir -p public_html/{kesh,uploads/{avatars,savollar,dizayn},zaxira_nusxalari}
chmod -R 755 public_html
```

### 6. Serverni ishga tushirish

**A. PHP built-in (lokal sinov uchun):**
```bash
cd public_html
php -S 0.0.0.0:8080 router.php
```

**B. Apache (production):**
```bash
sudo apt install apache2 libapache2-mod-php
sudo a2enmod rewrite headers

sudo nano /etc/apache2/sites-available/vatanparvar.conf
# Quyidagini kiriting:
```

```apache
<VirtualHost *:80>
    ServerName vatanparvaryaypan.uz
    DocumentRoot /home/USER/vatanparvar/public_html

    <Directory /home/USER/vatanparvar/public_html>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/vatanparvar_error.log
    CustomLog ${APACHE_LOG_DIR}/vatanparvar_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite vatanparvar.conf
sudo systemctl reload apache2
```

**C. Nginx (production):**

`/etc/nginx/sites-available/vatanparvar`:
```nginx
server {
    listen 80;
    server_name vatanparvaryaypan.uz;
    root /home/USER/vatanparvar/public_html;
    index index.php;

    # Chiroyli URL'lar
    location = /login           { rewrite ^ /auth/login.php           last; }
    location = /register        { rewrite ^ /auth/register.php        last; }
    location = /forgot-password { rewrite ^ /auth/forgot-password.php last; }
    location = /logout          { rewrite ^ /auth/logout.php          last; }
    location = /dashboard       { rewrite ^ /user/index.php           last; }
    location = /test            { rewrite ^ /user/test.php            last; }
    location = /profil          { rewrite ^ /user/profil.php          last; }
    location = /tolov           { rewrite ^ /user/payment.php         last; }
    location = /referal         { rewrite ^ /user/referal.php         last; }

    # Maxfiy papkalar
    location ~ ^/(config|cron|zaxira_nusxalari)/ { return 403; }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    # Statik fayllar uchun keshlash
    location ~* \.(jpg|jpeg|png|gif|webp|svg|css|js|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
sudo apt install nginx php-fpm
sudo ln -s /etc/nginx/sites-available/vatanparvar /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 🔄 Yangilash

Loyiha yangilanganda:

```bash
cd ~/vatanparvar
git stash                                          # lokal o'zgarishlarni saqlash
git fetch origin
git checkout lotin-kirill-avto-translit            # yoki main branch
git pull
git stash pop                                      # qaytarish

# Migration'lar (yangi bo'lsa)
mysql -u vp_user -p vatanparvar < database/migration_*.sql 2>/dev/null

# Keshni tozalash
rm -f public_html/kesh/indeks_*.html

# Serverni qayta yuklash (Apache)
sudo systemctl reload apache2
```

## ❓ Tez-tez muammolar

### 1. `Server vaqtinchalik mavjud emas`

DB ulanishi noto'g'ri. `public_html/config/database.php` da DB_USER, DB_PASS, DB_NAME ni tekshiring.

### 2. `Forbidden` admin papkasiga kirayotganda

`.htaccess`-dagi RewriteRule ishlamayapti. Apache: `sudo a2enmod rewrite && sudo systemctl reload apache2`.

### 3. Rasm yuklanmayapti

```bash
chmod -R 755 public_html/uploads
sudo chown -R www-data:www-data public_html/uploads  # Apache/Nginx uchun
```

### 4. Kesh yangilanmayapti

```bash
rm -f public_html/kesh/indeks_*.html
```

### 5. `php -S` ishlamayapti — port band

```bash
# Boshqa portni ishlatish:
php -S 0.0.0.0:9000 router.php

# Yoki avval portni bo'shatish:
sudo lsof -i :8080
sudo kill -9 PID
```

### 6. MySQL'ga ulanmayapti — `Access denied`

```bash
sudo mysql
# MySQL prompt'ida:
ALTER USER 'vp_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'YANGI_PAROL';
FLUSH PRIVILEGES;
EXIT;
```

## 📦 Talablar

| Komponent | Versiya |
|---|---|
| PHP | 8.0+ (8.2 tavsiya) |
| MySQL | 5.7+ yoki MariaDB 10.3+ |
| Apache | 2.4+ (mod_rewrite kerak) |
| Nginx | 1.18+ (PHP-FPM bilan) |

**PHP kengaytmalar:** `pdo_mysql`, `mbstring`, `gd`, `curl`, `json`, `zip`, `intl`

## 🔒 Production xavfsizlik

Production serverga chiqarishdan oldin:

1. **Parollarni o'zgartiring** (DB, developer akkaunt)
2. `REJIM = 'production'` qiling — xatolar yashiriladi
3. **HTTPS** o'rnating (Let's Encrypt: `sudo certbot --apache`)
4. **DB credentials**'ni `.env` fayliga ko'chiring (kelajak yaxshilash)
5. `sudo chown -R www-data:www-data public_html/{kesh,uploads,zaxira_nusxalari}`
6. **Cron job** o'rnating obuna eslatma uchun:
   ```bash
   crontab -e
   # Qo'shing:
   0 9 * * * curl -s "https://your-domain.uz/cron/obuna_eslatma.php?kalit=CRON_KALITI"
   ```

## 🆘 Yordam

Muammo bo'lsa, GitHub Issues da yozing yoki menga aytib bering!
