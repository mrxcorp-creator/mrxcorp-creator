<?php
/**
 * AvtoTest Pro — Tizimdan chiqish
 */
require_once __DIR__ . '/../config/auth.php';

// CSRF tekshiruvi (GET orqali himoyalanish)
// Oddiy GET logout ham qabul qilinadi (UX uchun), lekin CSRF token bo'lsa tekshiramiz
if (isset($_POST['csrf_token']) && !csrf_tekshir(post('csrf_token'))) {
    http_response_code(403);
    exit('Forbidden');
}

tizimdan_chiqish();
yonaltir(SAYT_URL . '/login');
