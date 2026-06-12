#!/bin/bash
# ============================================================
# VatanParvar Yaypan — Ubuntu uchun avtomatik o'rnatish skripti
# ============================================================
# Foydalanish:
#   bash setup.sh
#
# Skript quyidagilarni qiladi:
#   1. PHP, MySQL va kerakli paketlarni o'rnatadi
#   2. MySQL bazasi va foydalanuvchi yaratadi
#   3. Schema va migration'larni ishga tushiradi
#   4. config fayllarini avtomatik to'ldiradi
#   5. Papka ruxsatlarini sozlaydi
#   6. PHP built-in serverni ishga tushiradi
# ============================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

ok()    { echo -e "${GREEN}[\xe2\x9c\x93]${NC} $1"; }
xato()  { echo -e "${RED}[\xe2\x9c\x97]${NC} $1"; exit 1; }
info()  { echo -e "${BLUE}[i]${NC} $1"; }
ogoh()  { echo -e "${YELLOW}[!]${NC} $1"; }

clear
echo -e "${BLUE}"
cat <<'BANNER'
  ============================================================
     VatanParvar Yaypan -- Ubuntu Avtomatik O'rnatish v1.0
  ============================================================
BANNER
echo -e "${NC}"

# ============================================================
# 1. KERAKLI PAKETLAR
# ============================================================
info "1-bosqich: Tizim paketlarini tekshirish..."

if ! command -v php > /dev/null 2>&1; then
    ogoh "PHP topilmadi. O'rnatish kerak..."
    sudo apt update
    sudo apt install -y php php-cli php-mysql php-mbstring php-xml \
                        php-curl php-gd php-zip php-intl
    ok "PHP o'rnatildi"
else
    PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
    ok "PHP $PHP_VER mavjud"

    PHP_KERAK=("pdo_mysql" "mbstring" "gd" "curl" "json")
    YOQ=()
    for ext in "${PHP_KERAK[@]}"; do
        if ! php -m | grep -qi "^$ext$"; then
            YOQ+=("$ext")
        fi
    done
    if [ ${#YOQ[@]} -gt 0 ]; then
        ogoh "Yetishmaydigan PHP kengaytmalar: ${YOQ[*]}"
        for ext in "${YOQ[@]}"; do
            sudo apt install -y "php-$ext" 2>/dev/null || true
        done
    fi
fi

if ! command -v mysql > /dev/null 2>&1; then
    ogoh "MySQL topilmadi. O'rnatish kerak..."
    sudo apt update
    sudo apt install -y mysql-server
    ok "MySQL o'rnatildi"
else
    ok "MySQL mavjud"
fi

if ! command -v git > /dev/null 2>&1; then
    sudo apt install -y git
fi

echo

# ============================================================
# 2. KONFIGURATSIYA SO'ROVLARI
# ============================================================
info "2-bosqich: Konfiguratsiya parametrlari"
echo

read -p "Sayt URL (default: http://localhost:8080): " SAYT_URL
SAYT_URL=${SAYT_URL:-http://localhost:8080}

read -p "MySQL bazasi nomi (default: vatanparvar): " DB_NAME
DB_NAME=${DB_NAME:-vatanparvar}

read -p "MySQL foydalanuvchisi (default: vp_user): " DB_USER
DB_USER=${DB_USER:-vp_user}

read -sp "MySQL paroli (Enter -- avto kuchli parol): " DB_PASS
echo
if [ -z "$DB_PASS" ]; then
    DB_PASS=$(openssl rand -base64 16 2>/dev/null || head -c 16 /dev/urandom | base64)
    DB_PASS=${DB_PASS//[^a-zA-Z0-9]/}
    info "Avtomatik parol: $DB_PASS"
fi

read -sp "MySQL ROOT paroli (DB yaratish uchun, bo'sh -- sudo bilan): " ROOT_PASS
echo

read -p "Rejim -- production / development (default: development): " REJIM
REJIM=${REJIM:-development}

echo

# ============================================================
# 3. MYSQL BAZA
# ============================================================
info "3-bosqich: MySQL bazasini yaratish..."

if [ -n "$ROOT_PASS" ]; then
    MYSQL_CMD="mysql -u root -p$ROOT_PASS"
else
    MYSQL_CMD="sudo mysql"
fi

$MYSQL_CMD <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

if [ $? -eq 0 ]; then
    ok "Baza '$DB_NAME' yaratildi va '$DB_USER' foydalanuvchisi qo'shildi"
else
    xato "MySQL bazasi yaratilmadi. Root parolini tekshiring."
fi

# ============================================================
# 4. SCHEMA VA MIGRATIONS
# ============================================================
info "4-bosqich: Schema va migration'larni ishga tushirish..."

mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/schema.sql && \
    ok "Schema yuklandi"

for migration in database/migration_*.sql; do
    if [ -f "$migration" ]; then
        ALAYNI=$(basename "$migration")
        mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$migration" 2>/dev/null && \
            ok "$ALAYNI yuklandi" || ogoh "$ALAYNI: ba'zi ALTER'lar o'tib ketdi (mavjud ustunlar)"
    fi
done

# ============================================================
# 5. CONFIG FAYLLARI
# ============================================================
info "5-bosqich: config fayllarini yangilash..."

sed -i.bak \
    -e "s|define('REJIM', '[^']*');|define('REJIM', '$REJIM');|" \
    -e "s|define('SAYT_URL', '[^']*');|define('SAYT_URL', '$SAYT_URL');|" \
    public_html/config/config.php
ok "config.php yangilandi"

sed -i.bak \
    -e "s|define('DB_NAME', '[^']*');|define('DB_NAME', '$DB_NAME');|" \
    -e "s|define('DB_USER', '[^']*');|define('DB_USER', '$DB_USER');|" \
    -e "s|define('DB_PASS', '[^']*');|define('DB_PASS', '$DB_PASS');|" \
    public_html/config/database.php
ok "database.php yangilandi"

rm -f public_html/config/*.bak

# ============================================================
# 6. PAPKA RUXSATLARI
# ============================================================
info "6-bosqich: Papka ruxsatlarini sozlash..."

for dir in kesh uploads uploads/avatars uploads/savollar uploads/dizayn zaxira_nusxalari; do
    mkdir -p "public_html/$dir"
    chmod 755 "public_html/$dir"
done
ok "Papka ruxsatlari sozlandi"

# ============================================================
# 7. KESH VA CRON KALIT
# ============================================================
rm -f public_html/kesh/indeks_*.html
ok "Kesh tozalandi"

CRON_KEY=$(openssl rand -hex 16 2>/dev/null || head -c 16 /dev/urandom | xxd -p)
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<SQL 2>/dev/null
INSERT INTO sozlamalar (kalit, qiymat, tavsif) VALUES
('cron_kalit', '$CRON_KEY', 'Cron URL xavfsizlik kaliti')
ON DUPLICATE KEY UPDATE qiymat = '$CRON_KEY';
SQL
ok "Cron kaliti o'rnatildi"

# ============================================================
# 8. MA'LUMOT
# ============================================================
echo
echo -e "${GREEN}"
cat <<'OKBAN'
  ============================================================
                          TAYYOR!
  ============================================================
OKBAN
echo -e "${NC}"

cat <<INFO

  O'RNATISH MA'LUMOTLARI:
  -----------------------------------------------------------
    Sayt URL:       $SAYT_URL
    Baza nomi:      $DB_NAME
    Baza user:      $DB_USER
    Baza paroli:    $DB_PASS
    Cron kaliti:    $CRON_KEY
    Rejim:          $REJIM

  DEVELOPER AKKAUNTI:
  -----------------------------------------------------------
    Telefon:        +998900000000
    Parol:          admin12345
    (Birinchi kirgandan keyin parolni o'zgartiring!)

INFO

# ----- Bot sozlash -----
echo
read -p "Telegram bot tokenini hozir kiritmoqchimisiz? (h/Y) " setupbot
if [[ "$setupbot" =~ ^[Yy]$ ]]; then
    read -p "  Bot tokeni: " BOT_TOKEN
    read -p "  Bot username (@bot_user): " BOT_USERNAME
    read -p "  Admin Telegram ID: " ADMIN_TG_ID

    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<SQL
UPDATE sozlamalar SET qiymat='$BOT_TOKEN'    WHERE kalit='telegram_bot_token';
UPDATE sozlamalar SET qiymat='$BOT_USERNAME' WHERE kalit='telegram_bot_username';
UPDATE sozlamalar SET qiymat='$ADMIN_TG_ID'  WHERE kalit='telegram_admin_id';
SQL
    ok "Bot sozlamalari saqlandi"
fi

# ----- Serverni ishga tushirish -----
echo
read -p "PHP built-in serverni hozir ishga tushiraylikmi? (Y/n) " runserver
if [[ ! "$runserver" =~ ^[Nn]$ ]]; then
    PORT=$(echo "$SAYT_URL" | grep -oE ':[0-9]+' | tr -d ':')
    PORT=${PORT:-8080}

    info "Server ishga tushirilmoqda: $SAYT_URL"
    info "To'xtatish uchun: Ctrl+C"
    echo

    cd public_html
    php -S "0.0.0.0:$PORT" router.php
fi
