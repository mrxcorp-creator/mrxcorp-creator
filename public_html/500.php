<?php
/**
 * 500 — Server xatosi
 *
 * Bu fayl auth.php ni include QILMAYDI, chunki xato shu sababli yuz berishi mumkin.
 * Faqat oddiy HTML chiqaradi.
 */
http_response_code(500);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
?><!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>500 — Server xatosi · VatanParvar Yaypan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="text-9xl font-bold bg-gradient-to-r from-amber-400 to-rose-500 bg-clip-text text-transparent mb-4">
            500
        </div>
        <h1 class="text-3xl font-bold mb-3">Server xatosi</h1>
        <p class="text-slate-400 mb-8">
            Vaqtinchalik muammo yuz berdi. Iltimos, bir necha daqiqadan so'ng qayta urinib ko'ring.
            Agar muammo davom etsa, administrator bilan bog'laning.
        </p>
        <a href="/" class="inline-block px-6 py-3 rounded-xl bg-blue-500 hover:bg-blue-400 font-semibold transition">
            Bosh sahifa
        </a>
    </div>
</body>
</html>
