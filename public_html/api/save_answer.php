<?php
/**
 * VatanParvar Yaypan — Test javoblarini saqlash (AJAX)
 *
 * Ikki rejim qo'llab-quvvatlanadi:
 *   - "mashq" (oddiy test) — bilet bo'yicha, vaqt limiti yengil
 *   - "imtihon" (real exam simulator) — qat'iy qoidalar:
 *       * 2 ta xato qilishga ruxsat (3-chi → fail)
 *       * Javobdan keyin to'g'ri javob va izohni qaytaramiz
 *       * Tugatishda otdimi=1/0 hisoblanadi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_javob(['ok' => false, 'xato' => 'POST kerak'], 405);
}
if (!csrf_tekshir(post('csrf_token'))) {
    json_javob(['ok' => false, 'xato' => t('csrf_xato')], 403);
}

$f = joriy_foydalanuvchi();
if (!$f) {
    json_javob(['ok' => false, 'xato' => t('avval_kiring')], 401);
}

$natija_id = (int) post('natija_id');
$natija = db_qator(
    'SELECT * FROM natijalar WHERE id = ? AND foydalanuvchi_id = ?',
    [$natija_id, $f['id']]
);
if (!$natija) {
    json_javob(['ok' => false, 'xato' => t('malumot_yoq')], 404);
}
if ($natija['holat'] !== 'davom') {
    json_javob(['ok' => false, 'xato' => 'Test allaqachon yakunlangan'], 400);
}

$is_imtihon = ($natija['tur'] ?? 'mashq') === 'imtihon';
$IMTIHON_XATO_LIMIT = 2;

$javoblar = json_decode($natija['javoblar_json'] ?? '{}', true) ?: [];

$boshlangan = strtotime($natija['boshlangan']);
$qolgan = (int) $natija['qolgan_vaqt'] - (time() - $boshlangan);
if ($qolgan < 0) $qolgan = 0;

// ============================================================
// REJIM 1: Testni tugatish
// ============================================================
if (post('tugatish') === '1' || $qolgan === 0) {

    // Imtihon — savollar ID'lari natija->izoh ichida
    if ($is_imtihon) {
        $savol_idlar = json_decode($natija['izoh'] ?? '[]', true) ?: [];
        if ($savol_idlar) {
            $placeholders = implode(',', array_fill(0, count($savol_idlar), '?'));
            $savollar = db_barcha(
                "SELECT id, togri_javob FROM savollar WHERE id IN ($placeholders)",
                $savol_idlar
            );
        } else {
            $savollar = [];
        }
    } else {
        $savollar = db_barcha(
            'SELECT id, togri_javob FROM savollar WHERE bilet_id = ?',
            [$natija['bilet_id']]
        );
    }

    $togri = 0; $xato = 0;
    foreach ($savollar as $s) {
        if (isset($javoblar[$s['id']])) {
            if ($javoblar[$s['id']] === $s['togri_javob']) $togri++;
            else $xato++;
        }
    }

    // Imtihon — o'tdi/o'tmadi
    $otdimi = null;
    if ($is_imtihon) {
        $vaqt_tugadi = post('vaqt_tugadi') === '1';
        // Pass: 2 ta yoki kamroq xato VA vaqt tugamagan VA barcha savollarga javob bergan
        $otdimi = (!$vaqt_tugadi && $xato <= $IMTIHON_XATO_LIMIT && count($javoblar) === count($savollar)) ? 1 : 0;
    }

    $holat = $qolgan === 0 || post('vaqt_tugadi') === '1' ? 'vaqt_tugadi' : 'tugagan';

    db_bajar(
        'UPDATE natijalar
         SET togri_son = ?, xato_son = ?, umumiy_son = ?, qolgan_vaqt = 0,
             holat = ?, tugagan = NOW(), otdimi = ?
         WHERE id = ?',
        [$togri, $xato, count($savollar), $holat, $otdimi, $natija_id]
    );

    // Yutuqlarni tekshirish
    $yangi_yutuqlar = yutuqlar_tekshir($f['id']);

    // Imtihondan o'tgan bo'lsa Telegram'ga ham
    if ($is_imtihon && $otdimi && $f['telegram_id']) {
        telegram_yubor($f['telegram_id'],
            "🎉 <b>Tabriklaymiz!</b>\n\nSiz imtihondan muvaffaqiyatli o'tdingiz!\n" .
            "✅ {$togri} to'g'ri / ❌ {$xato} xato\n\nReal imtihonga to'liq tayyorsiz! 🚗");

        bildirishnoma_yarat(
            $f['id'],
            "Imtihondan o'tdingiz! 🎓",
            "Tabriklaymiz! Siz {$togri} ta to'g'ri javob berdingiz. Real imtihonga tayyorsiz.",
            '/imtihon?nat=' . $natija_id,
            'muvaffaqiyat',
            '🎉'
        );
    }

    json_javob([
        'ok' => true,
        'tugadi' => true,
        'togri' => $togri,
        'xato' => $xato,
        'umumiy' => count($savollar),
        'otdimi' => $otdimi,
        'yangi_yutuqlar' => array_map(fn($y) => [
            'nomi' => $y['nomi'], 'tavsif' => $y['tavsif'], 'ikon' => $y['ikon']
        ], $yangi_yutuqlar),
    ]);
}

// ============================================================
// REJIM 2: Bitta javobni saqlash
// ============================================================
$savol_id = (int) post('savol_id');
$variant = post('variant');

if (!$savol_id || !in_array($variant, ['a', 'b', 'c', 'd'], true)) {
    json_javob(['ok' => false, 'xato' => 'Noto\'g\'ri parametrlar'], 400);
}

// Imtihon — savol ID natija->izoh massivida bormi?
if ($is_imtihon) {
    $savol_idlar = json_decode($natija['izoh'] ?? '[]', true) ?: [];
    if (!in_array($savol_id, $savol_idlar, true)) {
        json_javob(['ok' => false, 'xato' => 'Noto\'g\'ri savol'], 400);
    }
} else {
    $tegishli = db_qiymat(
        'SELECT 1 FROM savollar WHERE id = ? AND bilet_id = ?',
        [$savol_id, $natija['bilet_id']]
    );
    if (!$tegishli) {
        json_javob(['ok' => false, 'xato' => 'Noto\'g\'ri savol'], 400);
    }
}

// Bir savolga ikki marta javob berishni cheklash (imtihonda)
if ($is_imtihon && isset($javoblar[$savol_id])) {
    json_javob(['ok' => false, 'xato' => 'Bu savolga avval javob bergansiz'], 400);
}

$javoblar[$savol_id] = $variant;

db_bajar(
    'UPDATE natijalar SET javoblar_json = ?, qolgan_vaqt = ? WHERE id = ?',
    [json_encode($javoblar, JSON_UNESCAPED_UNICODE), $qolgan, $natija_id]
);

// Imtihon rejimida — to'g'ri javobni va izohni darhol qaytaramiz
$javob_data = [
    'ok' => true,
    'qolgan_vaqt' => $qolgan,
    'javoblar_soni' => count($javoblar),
];

if ($is_imtihon) {
    $savol_data = db_qator('SELECT togri_javob, izoh FROM savollar WHERE id = ?', [$savol_id]);
    if ($savol_data) {
        $javob_data['togri_javob'] = $savol_data['togri_javob'];
        $javob_data['izoh'] = $savol_data['izoh'];
    }
}

json_javob($javob_data);
