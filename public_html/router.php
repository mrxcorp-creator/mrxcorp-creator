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
$uri_trimmed = rtrim($uri, '/');

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
if (isset($routes[$uri_trimmed])) {
    $_SERVER['SCRIPT_NAME'] = $routes[$uri_trimmed];
    require __DIR__ . $routes[$uri_trimmed];
    return true;
}

// ----- Statik fayllar (CSS, JS, rasm va h.k.) — server o'zi yetkazib beradi -----
if (preg_match('/\.(?:png|jpe?g|gif|webp|svg|css|js|ico|txt|xml|map|woff2?|ttf|eot)$/i', $uri)) {
    return false;
}

// ----- Papkaning index.php fayli (masalan /admin/ -> /admin/index.php) -----
$papka_yoli = __DIR__ . $uri_trimmed;
if (is_dir($papka_yoli) && file_exists($papka_yoli . '/index.php')) {
    $_SERVER['SCRIPT_NAME'] = $uri_trimmed . '/index.php';
    require $papka_yoli . '/index.php';
    return true;
}

// ----- Mavjud PHP fayl bo'lsa, uni ishlatish -----
if (file_exists($papka_yoli) && !is_dir($papka_yoli)) {
    return false; // PHP serverning standart handler'iga topshirish
}

// ----- Topilmagan — 404 -----
http_response_code(404);
require __DIR__ . '/404.php';
return true;
