<?php
/**
 * VatanParvar Yaypan — Imtihon rejimi (Real Exam Simulator)
 *
 * Real imtihon qoidalari:
 *  - Aniq 20 ta savol (random tanlanadi)
 *  - 25 daqiqa vaqt
 *  - Maksimal 2 ta xato qilishga ruxsat (3-xato → fail)
 *  - Vaqt tugasa avtomatik fail
 *
 * URL'lar:
 *   /imtihon          — boshlash sahifasi
 *   /imtihon?bosh=1   — yangi imtihon yaratish
 *   /imtihon?nat=ID   — natija ko'rish
 */
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$IMTIHON_VAQTI = 25 * 60;
$IMTIHON_SAVOL = 20;
$IMTIHON_XATO_LIMIT = 2;

// ============================================================
// REJIM 1: Natija ko'rish
// ============================================================
$nat_id = (int) olish('nat');
if ($nat_id) {
    $nat = db_qator(
        'SELECT * FROM natijalar WHERE id = ? AND foydalanuvchi_id = ? AND tur = "imtihon"',
        [$nat_id, $f['id']]
    );
    if (!$nat) {
        flash_qoy('xato', t('malumot_yoq'));
        yonaltir(SAYT_URL . '/imtihon');
    }
    $javoblar = json_decode($nat['javoblar_json'] ?? '{}', true) ?: [];
    $savol_idlar = json_decode($nat['izoh'] ?? '[]', true) ?: [];
    $savollar = [];
    if ($savol_idlar) {
        $placeholders = implode(',', array_fill(0, count($savol_idlar), '?'));
        $savollar = db_barcha(
            "SELECT * FROM savollar WHERE id IN ($placeholders) ORDER BY FIELD(id, $placeholders)",
            array_merge($savol_idlar, $savol_idlar)
        );
    }
    $otdimi = (int) ($nat['otdimi'] ?? 0);
    $xato_son = (int) $nat['xato_son'];

    $sahifa_sarlavha = $otdimi ? "Imtihondan o'tdingiz! 🎉" : "Imtihondan o'tmadingiz";
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
    require __DIR__ . '/_imtihon_natija.php';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ============================================================
// REJIM 2: Imtihon boshlash / davom etish
// ============================================================
if (olish('bosh') === '1') {
    $aktiv_obuna = obuna_faolmi($f['id']);
    if (!$aktiv_obuna && $f['rol'] === 'user') {
        flash_qoy('xato', 'Imtihon rejimi pullik. Avval tarif sotib oling.');
        yonaltir(SAYT_URL . '/tolov');
    }

    $davom = db_qator(
        'SELECT * FROM natijalar WHERE foydalanuvchi_id = ? AND tur = "imtihon" AND holat = "davom"
         ORDER BY id DESC LIMIT 1',
        [$f['id']]
    );

    if (!$davom) {
        $savol_idlar_data = db_barcha(
            'SELECT id FROM savollar ORDER BY RAND() LIMIT ?',
            [$IMTIHON_SAVOL]
        );
        if (count($savol_idlar_data) < $IMTIHON_SAVOL) {
            flash_qoy('xato', 'Imtihon uchun yetarli savol yo\'q (' . count($savol_idlar_data) . ' < ' . $IMTIHON_SAVOL . ')');
            yonaltir(SAYT_URL . '/imtihon');
        }
        $savol_idlar = array_column($savol_idlar_data, 'id');
        $bilet_id = (int) db_qiymat('SELECT id FROM biletlar WHERE holat = "faol" ORDER BY id LIMIT 1');

        // Agar faol bilet yo'q bo'lsa, savollar mavjud bo'lgan istalgan biletni olamiz
        if (!$bilet_id) {
            $bilet_id = (int) db_qiymat(
                'SELECT bilet_id FROM savollar WHERE id = ? LIMIT 1',
                [$savol_idlar[0]]
            );
        }
        if (!$bilet_id) {
            flash_qoy('xato', 'Imtihon uchun bilet topilmadi. Avval admin panelidan biletlar yarating.');
            yonaltir(SAYT_URL . '/imtihon');
        }

        $yangi_id = db_bajar(
            'INSERT INTO natijalar (foydalanuvchi_id, bilet_id, tur, javoblar_json, umumiy_son, qolgan_vaqt, izoh)
             VALUES (?, ?, "imtihon", "{}", ?, ?, ?)',
            [$f['id'], $bilet_id, $IMTIHON_SAVOL, $IMTIHON_VAQTI, json_encode($savol_idlar)]
        );
        $davom = db_qator('SELECT * FROM natijalar WHERE id = ?', [$yangi_id]);
    }

    $savol_idlar = json_decode($davom['izoh'] ?? '[]', true) ?: [];
    if (!$savol_idlar) {
        flash_qoy('xato', 'Imtihon ma\'lumotlari buzilgan');
        yonaltir(SAYT_URL . '/imtihon');
    }
    $placeholders = implode(',', array_fill(0, count($savol_idlar), '?'));
    $savollar = db_barcha(
        "SELECT id, matn, rasm, variant_a, variant_b, variant_c, variant_d
         FROM savollar WHERE id IN ($placeholders) ORDER BY FIELD(id, $placeholders)",
        array_merge($savol_idlar, $savol_idlar)
    );
    $javoblar = json_decode($davom['javoblar_json'] ?? '{}', true) ?: [];

    $sahifa_sarlavha = "Imtihon rejimi";
    $body_class = 'imtihon-page no-select';
    require_once __DIR__ . '/../includes/header.php';
    require __DIR__ . '/_imtihon_oyna.php';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ============================================================
// REJIM 3: Boshlang'ich sahifa
// ============================================================
$oxirgi = db_barcha(
    'SELECT * FROM natijalar WHERE foydalanuvchi_id = ? AND tur = "imtihon" AND holat = "tugagan"
     ORDER BY tugagan DESC LIMIT 5',
    [$f['id']]
);
$jami = (int) db_qiymat(
    'SELECT COUNT(*) FROM natijalar WHERE foydalanuvchi_id = ? AND tur = "imtihon" AND holat = "tugagan"',
    [$f['id']]
);
$otgan = (int) db_qiymat(
    'SELECT COUNT(*) FROM natijalar WHERE foydalanuvchi_id = ? AND tur = "imtihon" AND otdimi = 1',
    [$f['id']]
);
$tayyorlik_foiz = $jami > 0 ? round($otgan / $jami * 100) : 0;
$aktiv_obuna = obuna_faolmi($f['id']);

$sahifa_sarlavha = "Imtihon rejimi";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require __DIR__ . '/_imtihon_bosh.php';
require_once __DIR__ . '/../includes/footer.php';
