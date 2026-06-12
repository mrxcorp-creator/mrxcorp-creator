<?php
/**
 * Admin — Dizayn boshqaruvi
 *
 * Logo, banner, sayt matni va boshqa vizual sozlamalar.
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/dizayn.php');
    }

    $harakat = post('harakat');

    // ----- Rasm yuklash (logo/banner) -----
    if (in_array($harakat, ['logo_yuklash', 'banner_yuklash'], true)) {
        $kalit = $harakat === 'logo_yuklash' ? 'logo_url' : 'banner_url';
        $papka = $harakat === 'logo_yuklash' ? 'dizayn' : 'dizayn';
        $maks  = $harakat === 'logo_yuklash' ? 400 : 1600;

        if (!empty($_FILES['rasm']['tmp_name'])) {
            // Eski rasmni o'chirish
            $eski = sozlama($kalit);
            if ($eski && is_file(UPLOAD_PATH . '/' . $eski)) {
                @unlink(UPLOAD_PATH . '/' . $eski);
            }
            $yangi = rasm_saqla($_FILES['rasm'], $papka, $maks);
            if ($yangi) {
                sozlama_saqla($kalit, $yangi);
                flash_qoy('muvaffaqiyat', 'Rasm muvaffaqiyatli yuklandi');
            } else {
                flash_qoy('xato', 'Rasm formati noto\'g\'ri yoki juda katta');
            }
        }

        // Keshni tozalash
        @array_map('unlink', glob(CACHE_PATH . '/indeks_*.html') ?: []);
        yonaltir(SAYT_URL . '/admin/dizayn.php');
    }

    // ----- Rasmni o'chirish -----
    if ($harakat === 'rasm_ochir') {
        $kalit = post('kalit');
        if (in_array($kalit, ['logo_url', 'banner_url'], true)) {
            $eski = sozlama($kalit);
            if ($eski && is_file(UPLOAD_PATH . '/' . $eski)) {
                @unlink(UPLOAD_PATH . '/' . $eski);
            }
            sozlama_saqla($kalit, '');
            @array_map('unlink', glob(CACHE_PATH . '/indeks_*.html') ?: []);
            flash_qoy('muvaffaqiyat', 'Rasm o\'chirildi');
        }
        yonaltir(SAYT_URL . '/admin/dizayn.php');
    }

    // ----- Matn sozlamalarini saqlash -----
    if ($harakat === 'matn') {
        $maydonlar = [
            'sayt_nomi', 'sayt_shior', 'about_matn',
            'aloqa_telefon', 'aloqa_email', 'manzil', 'ish_vaqti',
            'telegram_kanal', 'telegram_link', 'instagram_link', 'youtube_link',
            'xarita_url', 'hero_video_url', 'blog_aktiv',
        ];
        foreach ($maydonlar as $m) {
            if (isset($_POST[$m])) {
                sozlama_saqla($m, trim((string) $_POST[$m]));
            }
        }
        @array_map('unlink', glob(CACHE_PATH . '/indeks_*.html') ?: []);
        flash_qoy('muvaffaqiyat', 'Sozlamalar saqlandi');
        yonaltir(SAYT_URL . '/admin/dizayn.php');
    }
}

$logo_url = sozlama('logo_url');
$banner_url = sozlama('banner_url');

$admin_sahifa = 'dizayn';
$sahifa_sarlavha = 'Dizayn va matn';
require_once __DIR__ . '/_layout.php';
?>

<!-- ============================================================
     LOGO YUKLASH
     ============================================================ -->
<div class="grid lg:grid-cols-2 gap-5 mb-6">

    <div class="glass-card p-6 fade-up">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-100 to-blue-100 text-sky-600 flex items-center justify-center text-2xl">
                🎨
            </div>
            <div>
                <h2 class="font-display font-bold text-lg text-brand-text">Logo</h2>
                <p class="text-xs text-brand-muted">Sayt logotipi (kvadrat formatda yaxshi ko'rinadi, 400x400px)</p>
            </div>
        </div>

        <?php if ($logo_url && is_file(UPLOAD_PATH . '/' . $logo_url)): ?>
            <div class="mb-4 p-6 rounded-xl bg-gradient-to-br from-sky-50 to-white border border-brand-border flex items-center justify-center">
                <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($logo_url) ?>" alt="Logo" class="max-h-32 max-w-full">
            </div>
            <form method="POST" class="mb-3">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="rasm_ochir">
                <input type="hidden" name="kalit" value="logo_url">
                <button class="btn-danger text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Logoni o'chirish
                </button>
            </form>
        <?php else: ?>
            <div class="mb-4 p-12 rounded-xl bg-sky-50/50 border-2 border-dashed border-sky-200 text-center">
                <div class="text-5xl mb-2">🖼️</div>
                <p class="text-sm text-brand-muted">Logo hali yuklanmagan</p>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_input() ?>
            <input type="hidden" name="harakat" value="logo_yuklash">

            <label class="field-label">Yangi logo yuklash</label>
            <input type="file" name="rasm" accept="image/png,image/jpeg,image/webp,image/svg+xml" required
                   class="field file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-sky-100 file:text-sky-700 file:font-semibold file:cursor-pointer">

            <div class="flex items-start gap-2 p-3 rounded-lg bg-blue-50/50 text-xs text-brand-body">
                <svg class="w-4 h-4 text-sky-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <strong>Tavsiya:</strong> PNG yoki SVG, kvadrat shaklda, 400x400px hajm. Avtomatik WebP'ga konvertatsiya qilinadi.
                </div>
            </div>

            <button type="submit" class="btn-primary w-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Logoni yuklash
            </button>
        </form>
    </div>

    <!-- ============================================================
         BANNER YUKLASH
         ============================================================ -->
    <div class="glass-card p-6 fade-up">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-100 to-purple-100 text-violet-600 flex items-center justify-center text-2xl">
                🌅
            </div>
            <div>
                <h2 class="font-display font-bold text-lg text-brand-text">Bosh sahifa banneri</h2>
                <p class="text-xs text-brand-muted">Hero qismida ko'rinadigan rasm (1600x900px tavsiya etiladi)</p>
            </div>
        </div>

        <?php if ($banner_url && is_file(UPLOAD_PATH . '/' . $banner_url)): ?>
            <div class="mb-4 rounded-xl overflow-hidden border border-brand-border">
                <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($banner_url) ?>" alt="Banner" class="w-full h-auto">
            </div>
            <form method="POST" class="mb-3">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="rasm_ochir">
                <input type="hidden" name="kalit" value="banner_url">
                <button class="btn-danger text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Bannerni o'chirish
                </button>
            </form>
        <?php else: ?>
            <div class="mb-4 p-12 rounded-xl bg-violet-50/50 border-2 border-dashed border-violet-200 text-center">
                <div class="text-5xl mb-2">🎨</div>
                <p class="text-sm text-brand-muted mb-1">Banner hali yuklanmagan</p>
                <p class="text-xs text-brand-light">Banner yo'q bo'lsa, dizayn illustratsiyasi avtomatik ko'rinadi</p>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_input() ?>
            <input type="hidden" name="harakat" value="banner_yuklash">

            <label class="field-label">Yangi banner yuklash</label>
            <input type="file" name="rasm" accept="image/png,image/jpeg,image/webp" required
                   class="field file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-violet-100 file:text-violet-700 file:font-semibold file:cursor-pointer">

            <div class="flex items-start gap-2 p-3 rounded-lg bg-violet-50/50 text-xs text-brand-body">
                <svg class="w-4 h-4 text-violet-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <strong>Tavsiya:</strong> JPG/WebP, 1600x900px (16:9), 500KB gacha. Avtomatik kichraytiriladi va WebP'ga konvertatsiya qilinadi.
                </div>
            </div>

            <button type="submit" class="btn-primary w-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Bannerni yuklash
            </button>
        </form>
    </div>
</div>

<!-- ============================================================
     SAYT MATNLARI VA ALOQA
     ============================================================ -->
<form method="POST" class="space-y-5">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="matn">

    <!-- Sayt asosiy matni -->
    <div class="glass-card p-6 fade-up">
        <h2 class="font-display font-bold text-lg text-brand-text mb-5 flex items-center gap-2">
            <span>📝</span> Sayt asosiy matni
        </h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="field-label">Sayt nomi</label>
                <input name="sayt_nomi" value="<?= e(sozlama('sayt_nomi')) ?>" class="field" placeholder="VatanParvar Yaypan">
            </div>
            <div>
                <label class="field-label">Bosh sahifa shiori</label>
                <input name="sayt_shior" value="<?= e(sozlama('sayt_shior')) ?>" class="field" placeholder="Avto maktab nazariyasiga eng tezkor tayyorgarlik">
            </div>
            <div class="sm:col-span-2">
                <label class="field-label">Sayt haqida (hero qismi tagidagi matn)</label>
                <textarea name="about_matn" rows="3" class="field"><?= e(sozlama('about_matn')) ?></textarea>
            </div>
        </div>
    </div>

    <!-- Aloqa ma'lumotlari -->
    <div class="glass-card p-6 fade-up">
        <h2 class="font-display font-bold text-lg text-brand-text mb-5 flex items-center gap-2">
            <span>📞</span> Aloqa ma'lumotlari
        </h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="field-label">Telefon raqami</label>
                <input name="aloqa_telefon" value="<?= e(sozlama('aloqa_telefon')) ?>" class="field" placeholder="+998 90 123 45 67">
            </div>
            <div>
                <label class="field-label">Email</label>
                <input name="aloqa_email" type="email" value="<?= e(sozlama('aloqa_email')) ?>" class="field" placeholder="info@vatanparvaryaypan.uz">
            </div>
            <div>
                <label class="field-label">Manzil</label>
                <input name="manzil" value="<?= e(sozlama('manzil')) ?>" class="field" placeholder="Yaypan shahri, Farg'ona viloyati">
            </div>
            <div>
                <label class="field-label">Ish vaqti</label>
                <input name="ish_vaqti" value="<?= e(sozlama('ish_vaqti')) ?>" class="field" placeholder="Du-Sha 09:00 - 18:00">
            </div>
            <div class="sm:col-span-2">
                <label class="field-label">Xarita iframe URL (Google Maps embed)
                    <span class="text-xs text-brand-muted">— Google maps'dan "Embed map" havolasi</span>
                </label>
                <input name="xarita_url" value="<?= e(sozlama('xarita_url')) ?>" class="field font-mono text-xs"
                       placeholder="https://www.google.com/maps/embed?pb=...">
            </div>
        </div>
    </div>

    <!-- Ijtimoiy tarmoqlar -->
    <div class="glass-card p-6 fade-up">
        <h2 class="font-display font-bold text-lg text-brand-text mb-5 flex items-center gap-2">
            <span>🌐</span> Ijtimoiy tarmoqlar
        </h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="field-label flex items-center gap-2">
                    <svg class="w-4 h-4 text-sky-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0z"/></svg>
                    Telegram kanal
                </label>
                <input name="telegram_kanal" value="<?= e(sozlama('telegram_kanal')) ?>" class="field" placeholder="https://t.me/vatanparvaryaypan">
            </div>
            <div>
                <label class="field-label flex items-center gap-2">
                    <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0z"/></svg>
                    Telegram aloqa (bot yoki shaxsiy)
                </label>
                <input name="telegram_link" value="<?= e(sozlama('telegram_link')) ?>" class="field" placeholder="https://t.me/admin_username">
            </div>
            <div>
                <label class="field-label flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
                    Instagram
                </label>
                <input name="instagram_link" value="<?= e(sozlama('instagram_link')) ?>" class="field" placeholder="https://instagram.com/vatanparvaryaypan">
            </div>
            <div>
                <label class="field-label flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    YouTube
                </label>
                <input name="youtube_link" value="<?= e(sozlama('youtube_link')) ?>" class="field" placeholder="https://youtube.com/@vatanparvaryaypan">
            </div>
        </div>
    </div>

    <!-- Boshqa sozlamalar -->
    <div class="glass-card p-6 fade-up">
        <h2 class="font-display font-bold text-lg text-brand-text mb-5 flex items-center gap-2">
            <span>⚙️</span> Boshqa sozlamalar
        </h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="field-label">Hero video URL (ixtiyoriy, YouTube embed)</label>
                <input name="hero_video_url" value="<?= e(sozlama('hero_video_url')) ?>" class="field font-mono text-xs"
                       placeholder="https://www.youtube.com/embed/VIDEO_ID">
            </div>
            <div>
                <label class="field-label">Blog bo'limini ko'rsatish</label>
                <select name="blog_aktiv" class="field">
                    <option value="1" <?= (int) sozlama('blog_aktiv', 1) === 1 ? 'selected' : '' ?>>Ha — yoqilgan</option>
                    <option value="0" <?= (int) sozlama('blog_aktiv', 1) === 0 ? 'selected' : '' ?>>Yo'q — yashirilgan</option>
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn-primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Barcha sozlamalarni saqlash
    </button>
</form>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
