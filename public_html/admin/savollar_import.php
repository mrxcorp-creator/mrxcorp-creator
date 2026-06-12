<?php
/**
 * Admin — Savollarni CSV orqali ko'p miqdorda import qilish
 *
 * CSV format (vergul yoki nuqta-vergul ajratuvchi, UTF-8):
 *   bilet_raqam;matn;variant_a;variant_b;variant_c;variant_d;togri_javob;izoh
 *
 * Birinchi qator — sarlavha (header), o'tkazib yuboriladi.
 *
 * Validatsiya:
 *  - bilet_raqam mavjud bo'lishi kerak
 *  - matn, variant_a, variant_b, togri_javob — majburiy
 *  - togri_javob — a/b/c/d
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

$natija = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/savollar_import.php');
    }

    if (!empty($_FILES['fayl']['tmp_name'])) {
        $natija = csv_import_qilish($_FILES['fayl']['tmp_name'], $f['id']);
    } else {
        flash_qoy('xato', 'Fayl tanlanmagan');
        yonaltir(SAYT_URL . '/admin/savollar_import.php');
    }
}

function csv_import_qilish(string $fayl_yoli, int $admin_id): array {
    $natija = [
        'jami'      => 0,
        'qoshildi'  => 0,
        'yangilandi'=> 0,
        'xato'      => 0,
        'xatolar'   => [],
    ];

    $fp = fopen($fayl_yoli, 'r');
    if (!$fp) {
        $natija['xatolar'][] = 'Fayl ochilmadi';
        return $natija;
    }

    // BOM ni o'tkazib yuborish
    $bom = fread($fp, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($fp);
    }

    // Ajratuvchi avtomatik aniqlash
    $birinchi_satr = fgets($fp);
    rewind($fp);
    $ajratuvchi = (substr_count($birinchi_satr, ';') > substr_count($birinchi_satr, ',')) ? ';' : ',';

    // BOM ni qaytadan o'tkazish
    $bom = fread($fp, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($fp);
    }

    // Sarlavhani o'tkazib yuboramiz
    $sarlavha = fgetcsv($fp, 0, $ajratuvchi);
    if (!$sarlavha) {
        $natija['xatolar'][] = 'Fayl bo\'sh yoki noto\'g\'ri formatda';
        fclose($fp);
        return $natija;
    }

    $satr_no = 1;
    while (($row = fgetcsv($fp, 0, $ajratuvchi)) !== false) {
        $satr_no++;
        $natija['jami']++;

        if (count($row) < 7) {
            $natija['xato']++;
            $natija['xatolar'][] = "Satr {$satr_no}: ustunlar yetarli emas (kerak 7-8, bor " . count($row) . ")";
            continue;
        }

        [$bilet_raqam, $matn, $a, $b, $c, $d, $togri] = array_map('trim', $row);
        $izoh = isset($row[7]) ? trim($row[7]) : '';

        $bilet_raqam = (int) $bilet_raqam;
        if (!$bilet_raqam) {
            $natija['xato']++;
            $natija['xatolar'][] = "Satr {$satr_no}: bilet_raqam noto'g'ri";
            continue;
        }
        if (!$matn || !$a || !$b) {
            $natija['xato']++;
            $natija['xatolar'][] = "Satr {$satr_no}: matn yoki variantlar bo'sh";
            continue;
        }
        $togri = strtolower($togri);
        if (!in_array($togri, ['a', 'b', 'c', 'd'], true)) {
            $natija['xato']++;
            $natija['xatolar'][] = "Satr {$satr_no}: togri_javob — a/b/c/d bo'lishi kerak (siz: '{$togri}')";
            continue;
        }

        // Bilet topish yoki yaratish
        $bilet_id = (int) db_qiymat('SELECT id FROM biletlar WHERE raqam = ?', [$bilet_raqam]);
        if (!$bilet_id) {
            $bilet_id = db_bajar(
                'INSERT INTO biletlar (raqam, nomi, tur, holat) VALUES (?, ?, "pullik", "faol")',
                [$bilet_raqam, "Bilet №{$bilet_raqam}"]
            );
        }

        // Savol allaqachon bormi (matn bo'yicha) — yangilash
        $bor = (int) db_qiymat(
            'SELECT id FROM savollar WHERE bilet_id = ? AND matn = ? LIMIT 1',
            [$bilet_id, $matn]
        );

        if ($bor) {
            db_bajar(
                'UPDATE savollar SET variant_a=?, variant_b=?, variant_c=?, variant_d=?, togri_javob=?, izoh=? WHERE id=?',
                [$a, $b, $c ?: null, $d ?: null, $togri, $izoh ?: null, $bor]
            );
            $natija['yangilandi']++;
        } else {
            db_bajar(
                'INSERT INTO savollar (bilet_id, matn, variant_a, variant_b, variant_c, variant_d, togri_javob, izoh)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$bilet_id, $matn, $a, $b, $c ?: null, $d ?: null, $togri, $izoh ?: null]
            );
            $natija['qoshildi']++;
        }
    }
    fclose($fp);

    audit_yoz($admin_id, 'savollar_import', 'savollar', null,
        "Jami: {$natija['jami']}, Qo'shildi: {$natija['qoshildi']}, Yangilandi: {$natija['yangilandi']}, Xato: {$natija['xato']}");

    return $natija;
}

$admin_sahifa = 'savollar';
$sahifa_sarlavha = 'Savollar — Bulk Import';
require_once __DIR__ . '/_layout.php';
?>

<a href="<?= e(SAYT_URL) ?>/admin/savollar.php" class="inline-flex items-center gap-1 text-brand-muted hover:text-sky-600 text-sm mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    Savollar boshqaruviga qaytish
</a>

<!-- Hero -->
<div class="glass-card p-6 mb-6 fade-up bg-gradient-to-br from-sky-50 to-blue-50">
    <div class="flex items-start gap-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-3xl shadow-soft flex-shrink-0">📥</div>
        <div>
            <h1 class="font-display font-bold text-2xl text-brand-text mb-1">CSV / Excel Import</h1>
            <p class="text-brand-muted text-sm">Yuzlab savollarni bir vaqtda yuklash uchun jadvalni CSV faylga eksport qiling va shu yerga yuklang.</p>
        </div>
    </div>
</div>

<?php if ($natija): ?>
    <!-- Natija -->
    <div class="glass-card p-6 mb-6 fade-up <?= $natija['xato'] > 0 ? 'border-amber-300' : 'border-emerald-300' ?>">
        <h2 class="font-display font-bold text-lg text-brand-text mb-4 flex items-center gap-2">
            <?= $natija['xato'] > 0 ? '⚠️' : '✅' ?> Import natijasi
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <div class="p-4 rounded-xl bg-sky-50 text-center">
                <div class="text-2xl font-display font-bold text-sky-700"><?= $natija['jami'] ?></div>
                <div class="text-xs text-sky-600 uppercase">Jami satr</div>
            </div>
            <div class="p-4 rounded-xl bg-emerald-50 text-center">
                <div class="text-2xl font-display font-bold text-emerald-700"><?= $natija['qoshildi'] ?></div>
                <div class="text-xs text-emerald-600 uppercase">Qo'shildi</div>
            </div>
            <div class="p-4 rounded-xl bg-amber-50 text-center">
                <div class="text-2xl font-display font-bold text-amber-700"><?= $natija['yangilandi'] ?></div>
                <div class="text-xs text-amber-600 uppercase">Yangilandi</div>
            </div>
            <div class="p-4 rounded-xl bg-rose-50 text-center">
                <div class="text-2xl font-display font-bold text-rose-700"><?= $natija['xato'] ?></div>
                <div class="text-xs text-rose-600 uppercase">Xato</div>
            </div>
        </div>

        <?php if (!empty($natija['xatolar'])): ?>
            <details class="text-sm mt-3">
                <summary class="cursor-pointer text-rose-600 font-semibold">Xato satrlar (<?= count($natija['xatolar']) ?>)</summary>
                <ul class="mt-2 space-y-1 max-h-60 overflow-y-auto">
                    <?php foreach ($natija['xatolar'] as $x): ?>
                        <li class="p-2 rounded bg-rose-50 text-rose-700 text-xs"><?= e($x) ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Yuklash formasi -->
<form method="POST" enctype="multipart/form-data" class="glass-card p-6 mb-6 fade-up">
    <?= csrf_input() ?>
    <h2 class="font-display font-bold text-lg text-brand-text mb-4">Faylni yuklash</h2>

    <label class="field-label">CSV fayl (UTF-8 kodlash)</label>
    <input type="file" name="fayl" accept=".csv,text/csv" required
           class="field file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-sky-100 file:text-sky-700 file:font-semibold file:cursor-pointer">

    <div class="mt-4 p-4 rounded-xl bg-sky-50/50 border border-sky-200 text-sm text-brand-body">
        <strong class="text-sky-700">Tavsiyalar:</strong>
        <ul class="list-disc ml-5 mt-2 space-y-1 text-xs">
            <li>Excel'dan saqlash: <strong>Save As → CSV UTF-8</strong></li>
            <li>Birinchi qator — sarlavha bo'lishi shart</li>
            <li>Ajratuvchi: vergul (<code>,</code>) yoki nuqta-vergul (<code>;</code>) — avtomatik aniqlanadi</li>
            <li>Bir xil <code>matn</code> bilan savol mavjud bo'lsa — yangilanadi</li>
            <li>Yangi <code>bilet_raqam</code> bo'lsa — bilet avtomatik yaratiladi</li>
        </ul>
    </div>

    <button type="submit" class="btn-primary mt-5 w-full sm:w-auto">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        Faylni yuklash
    </button>
</form>

<!-- Format namunasi -->
<div class="glass-card p-6 fade-up">
    <h2 class="font-display font-bold text-lg text-brand-text mb-4">📋 CSV format namunasi</h2>

    <div class="overflow-x-auto">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-sky-100 text-sky-900">
                    <th class="border border-brand-border p-2 font-semibold">bilet_raqam</th>
                    <th class="border border-brand-border p-2 font-semibold">matn</th>
                    <th class="border border-brand-border p-2 font-semibold">variant_a</th>
                    <th class="border border-brand-border p-2 font-semibold">variant_b</th>
                    <th class="border border-brand-border p-2 font-semibold">variant_c</th>
                    <th class="border border-brand-border p-2 font-semibold">variant_d</th>
                    <th class="border border-brand-border p-2 font-semibold">togri_javob</th>
                    <th class="border border-brand-border p-2 font-semibold">izoh</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-brand-border p-2">1</td>
                    <td class="border border-brand-border p-2">Yo'l harakati nima?</td>
                    <td class="border border-brand-border p-2">Avtomobillar harakati</td>
                    <td class="border border-brand-border p-2">Yo'lda barcha qatnashuvchilar harakati</td>
                    <td class="border border-brand-border p-2">Faqat piyodalar</td>
                    <td class="border border-brand-border p-2">Faqat velosipedchilar</td>
                    <td class="border border-brand-border p-2 bg-emerald-50 font-bold">b</td>
                    <td class="border border-brand-border p-2">YHQ moddasi 1.2</td>
                </tr>
                <tr class="bg-sky-50/30">
                    <td class="border border-brand-border p-2">1</td>
                    <td class="border border-brand-border p-2">Bosh yo'l qaysi belgi bilan ko'rsatiladi?</td>
                    <td class="border border-brand-border p-2">Sariq romb</td>
                    <td class="border border-brand-border p-2">Qizil uchburchak</td>
                    <td class="border border-brand-border p-2">Ko'k doira</td>
                    <td class="border border-brand-border p-2"></td>
                    <td class="border border-brand-border p-2 bg-emerald-50 font-bold">a</td>
                    <td class="border border-brand-border p-2"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <a download="savollar_namuna.csv"
       href="data:text/csv;charset=utf-8,%EF%BB%BFbilet_raqam;matn;variant_a;variant_b;variant_c;variant_d;togri_javob;izoh%0A1;Yo'l harakati qoidalariga binoan, qaysi belgi xavf belgilarini bildiradi?;Uchburchak qizil hoshiyali;Doira shaklidagi ko'k;To'rtburchak yashil;Sakkiztomon qizil;a;Xavf belgilari uchburchak shaklda%0A1;Bosh yo'l qaysi belgi bilan ko'rsatiladi?;Sariq romb;Qizil uchburchak;Ko'k doira;;a;%0A"
       class="btn-ghost mt-4 text-sm inline-flex">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Namuna CSV yuklab olish
    </a>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
