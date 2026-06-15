<?php
/**
 * VatanParvar Yaypan — Tizimdan chiqish
 */
require_once __DIR__ . '/../config/auth.php';

// CSRF tekshiruvi (GET orqali himoyalanish)
// Oddiy GET logout ham qabul qilinadi (UX uchun), lekin CSRF token bo'lsa tekshiramiz
// post() funksiyasiz xavfsiz tekshiruv
$_posted_token = trim($_POST['csrf_token'] ?? '');
if ($_posted_token !== '' && !csrf_tekshir($_posted_token)) {
    http_response_code(403);
    exit('Forbidden');
}

tizimdan_chiqish();
yonaltir(SAYT_URL . '/login');
