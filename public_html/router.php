<?php
/**
 * VatanParvar Yaypan — PHP built-in server uchun router
 * ------------------------------------------------------------
 * Bu fayl Apache .htaccess o'rnini bosadi (faqat lokal development
 * uchun). PHP built-in server `.htaccess`-ni o'qimaydi, shu sababli
 * chiroyli URL'lar va statik fayllarni qo'lda boshqarish kerak.
 *
 * Ishga tushirish:
 *   cd public_html
 *   php -S localhost:8080 router.php
 *
 * yoki:
 *   php -S 0.0.0.0:8080 -t public_html public_html/router.php
 *
 * Keyin brauzerda: http://localhost:8080
 *
 * Eslatma: bu fayl FAQAT localhost development uchun. Production
 * serverda Apache .htaccess'ni ishlatadi.
 */

// ============================================================
// 1. Statik fayllar (rasm, css, js, font, ...) - to'g'ridan-to'g'ri
// ============================================================
$url    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$fayl   = __DIR__ . $url;

// Agar haqiqiy fayl bo'lsa va PHP fayli bo'lmasa — `false` qaytaramiz,
// shunda built-in server o'zi xizmat qiladi.
if ($url !== '/' && file_exists($fayl) && !is_dir($fayl)) {
    // PHP fayllarini esa router orqali emas, to'g'ridan-to'g'ri ishga tushuramiz
    $kengaytma = strtolower(pathinfo($fayl, PATHINFO_EXTENSION));
    if ($kengaytma === 'php') {
        require $fayl;
        return true;
    }
    return false; // statik fayllar uchun built-in server javob beradi
}

// ============================================================
// 2. Yo'l (route) qoidalari — .htaccess bilan moslangan
// ============================================================
$routes = [
    // Auth
    '#^/login/?$#'           => '/auth/login.php',
    '#^/register/?$#'        => '/auth/register.php',
    '#^/forgot-password/?$#' => '/auth/forgot-password.php',
    '#^/logout/?$#'          => '/auth/logout.php',

    // User
    '#^/dashboard/?$#'       => '/user/index.php',
    '#^/test/?$#'            => '/user/test.php',
    '#^/profil/?$#'          => '/user/profil.php',
    '#^/tolov/?$#'           => '/user/payment.php',
    '#^/referal/?$#'         => '/user/referal.php',

    // Admin (papkali)
    '#^/admin/?$#'           => '/admin/index.php',
];

foreach ($routes as $regex => $maqsad) {
    if (preg_match($regex, $url)) {
        require __DIR__ . $maqsad;
        return true;
    }
}

// ============================================================
// 3. Bosh sahifa
// ============================================================
if ($url === '/' || $url === '') {
    require __DIR__ . '/index.php';
    return true;
}

// ============================================================
// 4. Maxfiy papkalarni bloklash (.htaccess bilan moslangan)
// ============================================================
foreach (['/config/', '/cron/', '/zaxira_nusxalari/'] as $maxfiy) {
    if (str_starts_with($url, $maxfiy)) {
        http_response_code(403);
        require __DIR__ . '/403.php';
        return true;
    }
}

// ============================================================
// 5. Hech narsa topilmadi — 404
// ============================================================
http_response_code(404);
require __DIR__ . '/404.php';
return true;
