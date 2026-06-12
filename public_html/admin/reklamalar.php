<?php
/**
 * VatanParvar Yaypan — Admin: Reklamalar boshqaruvi
 *
 * Joylashuvlar:
 *   - bosh_yuqori    — bosh sahifada hero ostida
 *   - bosh_pastki    — bosh sahifada CTA dan oldin
 *   - user_yon       — foydalanuvchi panelida
 *   - test_oraligi   — test sahifasi savollari oralig'ida
 *   - sidebar        — universal yon panel
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

$dizayn_dir = UPLOAD_PATH . '/dizayn';
if (!is_dir($dizayn_dir)) {
    @mkdir($dizayn_dir, 0755, true);
}

$JOYLASHUVLAR = [
    'bosh_yuqori'  => 'Bosh sahifa — yuqori',
    'bosh_pastki'  => 'Bosh sahifa — pastki',
    'user_yon'     => 'Foydalanuvchi paneli (yon)',
    'test_oraligi' => 'Test sahifasi (savollar oralig\'ida)',
    'sidebar'      => 'Yon panel (universal)',
];

// ----- POST harakatlar -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/reklamalar.php');
    }

    $harakat = post('harakat');
    $id = (int) post('id');

    // ----- Yaratish/Tahrirlash -----
    if (in_array($harakat, ['yaratish', 'tahrirlash'], true)) {
        $nomi      = trim((string) post('nomi'));
        $havola    = trim((string) post('havola'));
        $havola_yangi_oyna = post('havola_yangi_oyna') === '1' ? 1 : 0;
        $joylashuv = post('joylashuv');
        $boshlanish= post('boshlanish') ?: null;
        $tugash    = post('tugash') ?: null;
        $tartib    = (int) post('tartib');
        $holat     = post('holat') === 'nofaol' ? 'nofaol' : 'faol';

        if (!array_key_exists($joylashuv, $JOYLASHUVLAR)) {
            $joylashuv = 'bosh_yuqori';
        }

        // Rasm
        $rasm = $id ? db_qiymat('SELECT rasm FROM reklamalar WHERE id = ?', [$id]) : null;
        if (!empty($_FILES['rasm']['tmp_name'])) {
            $yangi = rasm_saqla($_FILES['rasm'], 'dizayn', 1600);
            if ($yangi) {
                if ($rasm && is_file($dizayn_dir . '/' . $rasm) && $rasm !== $yangi) {
                    @unlink($dizayn_dir . '/' . $rasm);
                }
                $rasm = $yangi;
            }
        }

        if (!$nomi || !$rasm) {
            flash_qoy('xato', t('kerakli_maydon'));
        } elseif ($id) {
            db_bajar(
                'UPDATE reklamalar SET nomi=?, rasm=?, havola=?, havola_yangi_oyna=?,
                                       joylashuv=?, boshlanish=?, tugash=?, tartib=?, holat=?
                 WHERE id=?',
                [$nomi, $rasm, $havola ?: null, $havola_yangi_oyna,
                 $joylashuv, $boshlanish, $tugash, $tartib, $holat, $id]
            );
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        } else {
            db_bajar(
                'INSERT INTO reklamalar (nomi, rasm, havola, havola_yangi_oyna,
                                         joylashuv, boshlanish, tugash, tartib, holat)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$nomi, $rasm, $havola ?: null, $havola_yangi_oyna,
                 $joylashuv, $boshlanish, $tugash, $tartib, $holat]
            );
            flash_qoy('muvaffaqiyat', 'Reklama qo\'shildi.');
        }
        // Bosh sahifa keshini tozalash
        foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $fayl) @unlink($fayl);
        yonaltir(SAYT_URL . '/admin/reklamalar.php');
    }

    // ----- O'chirish -----
    if ($harakat === 'ochirish' && $id) {
        $rasm = db_qiymat('SELECT rasm FROM reklamalar WHERE id = ?', [$id]);
        if ($rasm && is_file($dizayn_dir . '/' . $rasm)) {
            @unlink($dizayn_dir . '/' . $rasm);
        }
        db_bajar('DELETE FROM reklamalar WHERE id = ?', [$id]);
        foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $fayl) @unlink($fayl);
        flash_qoy('muvaffaqiyat', 'Reklama o\'chirildi.');
        yonaltir(SAYT_URL . '/admin/reklamalar.php');
    }

    // ----- Holat almashtirish -----
    if ($harakat === 'holat_alma' && $id) {
        $hozir = db_qiymat('SELECT holat FROM reklamalar WHERE id = ?', [$id]);
        $yangi = $hozir === 'faol' ? 'nofaol' : 'faol';
        db_bajar('UPDATE reklamalar SET holat = ? WHERE id = ?', [$yangi, $id]);
        foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $fayl) @unlink($fayl);
        yonaltir(SAYT_URL . '/admin/reklamalar.php');
    }
}

// ----- Tahrirlanayotgan reklama -----
$tahrir_id = (int) olish('tahrir');
$tahrir = $tahrir_id ? db_qator('SELECT * FROM reklamalar WHERE id = ?', [$tahrir_id]) : null;

// ----- Ro'yxat -----
$royxat = db_barcha('SELECT * FROM reklamalar ORDER BY joylashuv, tartib, id DESC');

$admin_sahifa = 'reklamalar';
$sahifa_sarlavha = 'Reklamalar';
require_once __DIR__ . '/_layout.php';
?>

<div class="space-y-6">

    <!-- ============================================================
         YARATISH / TAHRIRLASH FORMASI
         ============================================================ -->
    <details class="glass-card-premium p-0" <?= $tahrir ? 'open' : '' ?>>
        <summary class="cursor-pointer p-5 flex items-center justify-between">
            <span class="font-display text-lg text-app flex items-center gap-2">
                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <?= $tahrir ? 'Reklamani tahrirlash' : 'Yangi reklama qo\'shish' ?>
            </span>
            <span class="text-app-2 text-sm">▾</span>
        </summary>

        <form method="POST" enctype="multipart/form-data" class="px-5 pb-5 grid sm:grid-cols-2 gap-4 border-t border-app pt-5">
            <?= csrf_input() ?>
            <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
            <?php if ($tahrir): ?>
                <input type="hidden" name="id" value="<?= (int)$tahrir['id'] ?>">
            <?php endif; ?>

            <div class="sm:col-span-2">
                <label class="field-label">Reklama nomi (faqat admin uchun) *</label>
                <input name="nomi" required value="<?= e($tahrir['nomi'] ?? '') ?>" class="field" placeholder="Masalan: 2025 yangi yil aksiyasi">
            </div>

            <div class="sm:col-span-2">
                <label class="field-label">Rasm <?= $tahrir ? '(o\'zgartirish ixtiyoriy)' : '*' ?></label>
                <input type="file" name="rasm" accept="image/png,image/jpeg,image/webp" <?= $tahrir ? '' : 'required' ?>
                       class="field file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:cursor-pointer file:bg-blue-500/20 file:text-app file:font-medium">
                <?php if ($tahrir && $tahrir['rasm']): ?>
                    <img src="<?= e(SAYT_URL) ?>/uploads/dizayn/<?= e($tahrir['rasm']) ?>" class="mt-3 max-h-32 rounded-lg border border-app">
                <?php endif; ?>
            </div>

            <div>
                <label class="field-label">Joylashuv *</label>
                <select name="joylashuv" required class="field">
                    <?php foreach ($JOYLASHUVLAR as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($tahrir['joylashuv'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="field-label">Tartib (kichik son — yuqorida)</label>
                <input type="number" name="tartib" value="<?= (int)($tahrir['tartib'] ?? 0) ?>" class="field">
            </div>

            <div class="sm:col-span-2">
                <label class="field-label">Havola URL (ixtiyoriy)</label>
                <input type="url" name="havola" value="<?= e($tahrir['havola'] ?? '') ?>"
                       placeholder="https://example.com" class="field">
            </div>

            <div>
                <label class="field-label">Boshlanish sanasi</label>
                <input type="date" name="boshlanish" value="<?= e($tahrir['boshlanish'] ?? '') ?>" class="field">
            </div>

            <div>
                <label class="field-label">Tugash sanasi</label>
                <input type="date" name="tugash" value="<?= e($tahrir['tugash'] ?? '') ?>" class="field">
            </div>

            <div class="flex items-center gap-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="havola_yangi_oyna" value="1" <?= ($tahrir['havola_yangi_oyna'] ?? 1) ? 'checked' : '' ?> class="w-4 h-4 rounded">
                    <span class="text-sm text-app">Yangi oynada ochish</span>
                </label>
            </div>

            <div>
                <label class="field-label">Holat</label>
                <select name="holat" class="field">
                    <option value="faol"   <?= ($tahrir['holat'] ?? 'faol') === 'faol' ? 'selected' : '' ?>>Faol</option>
                    <option value="nofaol" <?= ($tahrir['holat'] ?? '') === 'nofaol' ? 'selected' : '' ?>>Nofaol</option>
                </select>
            </div>

            <div class="sm:col-span-2 flex gap-2">
                <button type="submit" class="btn-primary">
                    <?= $tahrir ? 'Yangilash' : 'Qo\'shish' ?>
                </button>
                <?php if ($tahrir): ?>
                    <a href="<?= e(SAYT_URL) ?>/admin/reklamalar.php" class="btn-ghost">Bekor qilish</a>
                <?php endif; ?>
            </div>
        </form>
    </details>

    <!-- ============================================================
         RO'YXAT
         ============================================================ -->
    <div class="glass-card p-6">
        <h2 class="font-display text-lg text-app mb-5 flex items-center justify-between">
            <span>Reklamalar ro'yxati</span>
            <span class="text-sm text-app-2 font-normal"><?= count($royxat) ?> ta</span>
        </h2>

        <?php if (empty($royxat)): ?>
            <div class="py-16 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4 opacity-50"
                     style="background: var(--bg-elevated);">
                    <svg class="w-8 h-8 text-app-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6Z"/></svg>
                </div>
                <p class="text-app-2">Hali reklama qo'shilmagan</p>
                <p class="text-app-2 text-sm mt-1">Yuqoridagi forma orqali yarating</p>
            </div>
        <?php else: ?>
            <div class="grid md:grid-cols-2 gap-4">
                <?php foreach ($royxat as $r):
                    $bugun = date('Y-m-d');
                    $faol_sana = (!$r['boshlanish'] || $r['boshlanish'] <= $bugun) &&
                                 (!$r['tugash'] || $r['tugash'] >= $bugun);
                    $hozir_korinadi = $r['holat'] === 'faol' && $faol_sana;
                ?>
                    <div class="glass-card p-4 fade-up <?= $r['holat'] === 'nofaol' ? 'opacity-60' : '' ?>">
                        <div class="flex items-start gap-3">
                            <img src="<?= e(SAYT_URL) ?>/uploads/dizayn/<?= e($r['rasm']) ?>"
                                 class="w-24 h-24 rounded-xl object-cover border border-app flex-shrink-0">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <h3 class="font-display text-app truncate"><?= e($r['nomi']) ?></h3>
                                    <?php if ($hozir_korinadi): ?>
                                        <span class="badge badge-success">Hozir ko'rinadi</span>
                                    <?php elseif ($r['holat'] === 'nofaol'): ?>
                                        <span class="badge badge-error">Nofaol</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Sana o'tgan/kelmagan</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-app-2 mb-1"><?= e($JOYLASHUVLAR[$r['joylashuv']] ?? $r['joylashuv']) ?></p>
                                <?php if ($r['havola']): ?>
                                    <a href="<?= e($r['havola']) ?>" target="_blank" class="text-xs text-accent hover:underline truncate block">
                                        <?= e(mb_substr($r['havola'], 0, 50)) ?><?= mb_strlen($r['havola']) > 50 ? '…' : '' ?>
                                    </a>
                                <?php endif; ?>
                                <div class="flex gap-3 text-xs text-app-2 mt-2">
                                    <span title="Ko'rishlar">👁️ <?= (int)$r['korish_soni'] ?></span>
                                    <span title="Bosishlar">🖱️ <?= (int)$r['bosish_soni'] ?></span>
                                    <?php if ($r['korish_soni'] > 0): ?>
                                        <span title="CTR">📈 <?= number_format($r['bosish_soni'] / $r['korish_soni'] * 100, 1) ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($r['boshlanish'] || $r['tugash']): ?>
                                    <div class="text-xs text-muted-app mt-1">
                                        <?= $r['boshlanish'] ? sana($r['boshlanish'], 'd.m.Y') : '∞' ?>
                                        →
                                        <?= $r['tugash'] ? sana($r['tugash'], 'd.m.Y') : '∞' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex gap-2 mt-3 pt-3 border-t border-app">
                            <a href="?tahrir=<?= (int)$r['id'] ?>" class="btn-ghost text-xs py-1.5 px-3">Tahrirlash</a>

                            <form method="POST" class="inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="harakat" value="holat_alma">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn-ghost text-xs py-1.5 px-3">
                                    <?= $r['holat'] === 'faol' ? 'O\'chirish (vaqtincha)' : 'Yoqish' ?>
                                </button>
                            </form>

                            <form method="POST" class="inline" onsubmit="return confirm('Reklamani butunlay o\'chirasizmi?')">
                                <?= csrf_input() ?>
                                <input type="hidden" name="harakat" value="ochirish">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn-danger text-xs py-1.5 px-3">O'chirish</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Joylashuv qaerda ekanligi haqida ma'lumot -->
    <div class="glass-card p-5 text-sm">
        <h3 class="font-display text-app mb-2">📍 Joylashuvlar qaerda ko'rinadi?</h3>
        <ul class="text-app-2 space-y-1.5">
            <li><strong class="text-app">Bosh sahifa — yuqori:</strong> Hero (sarlavha) ostida</li>
            <li><strong class="text-app">Bosh sahifa — pastki:</strong> Tariflar va fikrlar oralig'ida</li>
            <li><strong class="text-app">Foydalanuvchi paneli (yon):</strong> Dashboard yon panelida</li>
            <li><strong class="text-app">Test sahifasi:</strong> Test natijalari sahifasida</li>
            <li><strong class="text-app">Yon panel (universal):</strong> Reserved future</li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
