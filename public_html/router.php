<?php
/**
 * VatanParvar Yaypan — PHP Built-in Server uchun Router
 * ------------------------------------------------------------
 * .htaccess o'rnida ishlovchi router (faqat lokal rivojlanish uchun).
 *
 * Ishga tushirish:
 *   cd public_html && php -S localhost:8080 router.php
 */

// ----- URL ni olish -----
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// ----- Chiroyli URL'lar (.htaccess RewriteRule kabi) -----
$routes = [
    ''                 => '/index.php',
    '/login'           => '/auth/login.php',
    '/register'        => '/auth/register.php',
    '/forgot-password' => '/auth/forgot-password.php',
    '/logout'          => '/auth/logout.php',
    '/dashboard'       => '/user/index.php',
    '/test'            => '/user/test.php',
    '/profil'          => '/user/profil.php',
    '/tolov'           => '/user/payment.php',
    '/referal'         => '/user/referal.php',
];

// ----- Tegishli marshrutni topish -----
if (isset($routes[$uri])) {
    $_SERVER['SCRIPT_NAME'] = $routes[$uri];
    require __DIR__ . $routes[$uri];
    return true;
}

// ----- Statik fayllar (CSS, JS, rasm va h.k.) — server o'zi yetkazib beradi -----
if (preg_match('/\.(?:png|jpe?g|gif|webp|svg|css|js|ico|txt|xml|map|woff2?|ttf|eot)$/i', $uri)) {
    return false;
}

// ----- Mavjud PHP/HTML fayl bo'lsa, uni ishlatish -----
$fayl_yoli = __DIR__ . $uri;
if (file_exists($fayl_yoli) && !is_dir($fayl_yoli)) {
    return false; // PHP serverning standart handler'iga topshirish
}

// ----- Topilmagan — 404 -----
http_response_code(404);
require __DIR__ . '/404.php';
return true;
