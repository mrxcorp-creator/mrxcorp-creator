<?php
/**
 * VatanParvar Yaypan — Reklama bosish (click tracking)
 *
 * URL: /api/reklama_bosish.php?id=NN&u=https%3A%2F%2F...
 *
 * Reklama bosilganda bosish_soni'ni 1 ga oshiradi va foydalanuvchini
 * havola URL'iga yo'naltiradi.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

$id      = (int) ($_GET['id'] ?? 0);
$havola  = (string) ($_GET['u'] ?? '');

if ($id) {
    reklama_bosildi($id);
}

// Havola URL'i bo'lsa unga yo'naltiramiz, aks holda bosh sahifaga
if ($havola && (str_starts_with($havola, 'http://') || str_starts_with($havola, 'https://'))) {
    header('Location: ' . $havola, true, 302);
    exit;
}

header('Location: ' . SAYT_URL . '/', true, 302);
exit;
