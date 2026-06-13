#!/usr/bin/env bash
# ============================================================
#  VatanParvar Yaypan — Lokalda ishga tushirish skripti
#  Foydalanish:
#       bash run.sh
#       PORT=9000 bash run.sh         # boshqa port
#       SKIP_DB=1 bash run.sh         # DB import-ni o'tkazib yuborish
# ============================================================
set -euo pipefail

# ---------- Sozlamalar (config/database.php bilan mos) ----------
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$SCRIPT_DIR"
WEB_ROOT="$PROJECT_ROOT/public_html"
SCHEMA="$PROJECT_ROOT/database/schema.sql"

DB_NAME="wbefkccz_avtomaktab"
DB_USER="wbefkccz_avtomaktab"
DB_PASS="FrHCuXUP6RfY4XnzDGBw"

PORT="${PORT:-8000}"
HOST="${HOST:-127.0.0.1}"

# ---------- Ranglar ----------
G="\033[0;32m"; R="\033[0;31m"; Y="\033[1;33m"; B="\033[0;34m"; N="\033[0m"
info() { echo -e "${B}[INFO]${N} $*"; }
ok()   { echo -e "${G}[OK]${N}   $*"; }
warn() { echo -e "${Y}[WARN]${N} $*"; }
err()  { echo -e "${R}[ERR]${N}  $*" >&2; }

echo
echo "════════════════════════════════════════════════════════════"
echo "  VatanParvar Yaypan — Lokal ishga tushirish"
echo "════════════════════════════════════════════════════════════"

# ---------- 1) PHP ----------
if ! command -v php >/dev/null 2>&1; then
    err "PHP topilmadi. O'rnatish uchun:"
    echo "    sudo apt update"
    echo "    sudo apt install -y php-cli php-mysql php-mbstring php-xml php-curl php-gd"
    exit 1
fi
ok "PHP topildi: $(php -r 'echo PHP_VERSION;')"

# Kerakli kengaytmalar
MISSING_EXT=""
for ext in pdo_mysql mbstring; do
    if ! php -m | grep -iqx "$ext"; then
        MISSING_EXT="$MISSING_EXT $ext"
    fi
done
if [ -n "$MISSING_EXT" ]; then
    err "PHP kengaytmalari yo'q:$MISSING_EXT"
    echo "    sudo apt install -y php-mysql php-mbstring php-xml php-curl php-gd"
    exit 1
fi
ok "Kerakli PHP kengaytmalari mavjud (pdo_mysql, mbstring)"

# ---------- 2) MySQL / DB yaratish ----------
if [ "${SKIP_DB:-0}" != "1" ]; then
    if ! command -v mysql >/dev/null 2>&1; then
        warn "mysql klienti topilmadi. O'rnatish:"
        echo "    sudo apt install -y mariadb-server"
        echo "(Yoki SKIP_DB=1 bilan ishga tushiring)"
        exit 1
    fi

    info "MySQL ga sudo orqali ulanish..."
    if ! sudo mysql -e "SELECT 1" >/dev/null 2>&1; then
        err "MySQL/MariaDB ga ulanib bo'lmadi. Servis ishlayaptimi?"
        echo "    sudo systemctl status mariadb     # yoki mysql"
        echo "    sudo systemctl start  mariadb"
        exit 1
    fi
    ok "MySQL ga ulanildi"

    info "Baza va foydalanuvchini yaratish: $DB_NAME"
    sudo mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL
    ok "Baza tayyor (DB: $DB_NAME, user: $DB_USER)"

    if [ -f "$SCHEMA" ]; then
        info "Schema import qilinmoqda: $SCHEMA"
        sudo mysql "$DB_NAME" < "$SCHEMA"
        ok "Schema muvaffaqiyatli import qilindi"
    else
        warn "$SCHEMA topilmadi — import o'tkazib yuborildi"
    fi
else
    warn "SKIP_DB=1 — bazani sozlash bosqichi o'tkazib yuborildi"
fi

# ---------- 3) Papka ruxsatlari ----------
mkdir -p "$WEB_ROOT/kesh" "$WEB_ROOT/uploads/avatars" \
         "$WEB_ROOT/uploads/savollar" "$WEB_ROOT/zaxira_nusxalari"
chmod -R 0755 "$WEB_ROOT/kesh" "$WEB_ROOT/uploads" "$WEB_ROOT/zaxira_nusxalari" || true
ok "Yozish uchun papkalar tayyorlandi"

# ---------- 4) router.php (.htaccess emulatsiyasi) ----------
ROUTER="$WEB_ROOT/router.php"
cat > "$ROUTER" <<'PHP'
<?php
/**
 * PHP -S uchun router.
 * .htaccess dagi chiroyli URL'lar va papka bloklarini emulatsiya qiladi.
 */
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = __DIR__ . $uri;

// Statik fayllar (rasm/css/js) — built-in server o'zi beradi
if ($uri !== '/' && file_exists($path) && !is_dir($path)
    && pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
    return false;
}

// Maxfiy papkalarni bloklash
if (preg_match('#^/(config|cron|zaxira_nusxalari)(/|$)#', $uri)) {
    http_response_code(403);
    require __DIR__ . '/403.php';
    return true;
}

// Chiroyli URL xaritalari
$xaritalar = [
    '#^/login/?$#'           => '/auth/login.php',
    '#^/register/?$#'        => '/auth/register.php',
    '#^/forgot-password/?$#' => '/auth/forgot-password.php',
    '#^/logout/?$#'          => '/auth/logout.php',
    '#^/dashboard/?$#'       => '/user/index.php',
    '#^/test/?$#'            => '/user/test.php',
    '#^/profil/?$#'          => '/user/profil.php',
    '#^/tolov/?$#'           => '/user/payment.php',
    '#^/referal/?$#'         => '/user/referal.php',
];
foreach ($xaritalar as $regex => $hedef) {
    if (preg_match($regex, $uri)) {
        require __DIR__ . $hedef;
        return true;
    }
}

// Bosh sahifa yoki katalog
if ($uri === '/' || is_dir($path)) {
    $idx = rtrim($path, '/') . '/index.php';
    if (file_exists($idx)) { require $idx; return true; }
}

// Aniq .php fayl
if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
    require $path;
    return true;
}

http_response_code(404);
require __DIR__ . '/404.php';
return true;
PHP
ok "router.php tayyor: $ROUTER"

# ---------- 5) Server ----------
echo
echo "════════════════════════════════════════════════════════════"
echo -e "  ${G}Server ishga tushdi${N}"
echo -e "  URL:     ${B}http://$HOST:$PORT${N}"
echo -e "  Login:   +998900000000  /  admin12345"
echo -e "  To'xtatish: Ctrl+C"
echo "════════════════════════════════════════════════════════════"
echo

cd "$WEB_ROOT"
exec php -S "$HOST:$PORT" router.php
