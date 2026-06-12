<?php
/**
 * VatanParvar Yaypan — Admin: Dizayn (logo + banner + favicon)
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

// uploads/dizayn papkasini yaratish (agar yo'q bo'lsa)
$dizayn_dir = UPLOAD_PATH . '/dizayn';
if (!is_dir($dizayn_dir)) {
    @mkdir($dizayn_dir, 0755, true);
}

// ----- POST harakatlar -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/dizayn.php');
    }

    $harakat = post('harakat');

    // ----- Logo yuklash -----
    if ($harakat === 'logo' && !empty($_FILES['logo']['tmp_name'])) {
        $yangi = rasm_saqla($_FILES['logo'], 'dizayn', 256);
        if ($yangi) {
            // Eski logo'ni o'chirish
            $eski = sozlama('sayt_logo', '');
            if ($eski && is_file($dizayn_dir . '/' . $eski) && $eski !== $yangi) {
                @unlink($dizayn_dir . '/' . $eski);
            }
            sozlama_saqla('sayt_logo', $yangi);
            flash_qoy('muvaffaqiyat', 'Logo yangilandi!');
        } else {
            flash_qoy('xato', 'Faylni yuklab bo\'lmadi.');
        }
    }

    // ----- Logoni o'chirish -----
    if ($harakat === 'logo_ochir') {
        $eski = sozlama('sayt_logo', '');
        if ($eski && is_file($dizayn_dir . '/' . $eski)) {
            @unlink($dizayn_dir . '/' . $eski);
        }
        sozlama_saqla('sayt_logo', '');
        flash_qoy('muvaffaqiyat', 'Logo olib tashlandi.');
    }

    // ----- Banner yuklash -----
    if ($harakat === 'banner' && !empty($_FILES['banner']['tmp_name'])) {
        $yangi = rasm_saqla($_FILES['banner'], 'dizayn', 1920);
        if ($yangi) {
            $eski = sozlama('bosh_banner', '');
            if ($eski && is_file($dizayn_dir . '/' . $eski) && $eski !== $yangi) {
                @unlink($dizayn_dir . '/' . $eski);
            }
            sozlama_saqla('bosh_banner', $yangi);
            flash_qoy('muvaffaqiyat', 'Banner yangilandi!');
        } else {
            flash_qoy('xato', 'Bannerni yuklab bo\'lmadi.');
        }
    }

    // ----- Banner sozlamalari -----
    if ($harakat === 'banner_sozlamalar') {
        sozlama_saqla('bosh_banner_aktiv', post('bosh_banner_aktiv') === '1' ? '1' : '0');
        sozlama_saqla('bosh_banner_havola', trim((string) post('bosh_banner_havola')));

        // Bosh sahifa keshini tozalash
        foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $fayl) @unlink($fayl);
        flash_qoy('muvaffaqiyat', 'Banner sozlamalari saqlandi.');
    }

    if ($harakat === 'banner_ochir') {
        $eski = sozlama('bosh_banner', '');
        if ($eski && is_file($dizayn_dir . '/' . $eski)) {
            @unlink($dizayn_dir . '/' . $eski);
        }
        sozlama_saqla('bosh_banner', '');
        sozlama_saqla('bosh_banner_aktiv', '0');
        flash_qoy('muvaffaqiyat', 'Banner olib tashlandi.');
    }

    // ----- Favicon -----
    if ($harakat === 'favicon' && !empty($_FILES['favicon']['tmp_name'])) {
        // Favicon kichikroq, alohida
        $tmp = $_FILES['favicon']['tmp_name'];
        $kengaytma = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
        if (in_array($kengaytma, ['ico', 'png', 'svg'], true)) {
            $yangi_nom = 'favicon_' . bin2hex(random_bytes(4)) . '.' . $kengaytma;
            if (move_uploaded_file($tmp, $dizayn_dir . '/' . $yangi_nom)) {
                $eski = sozlama('sayt_favicon', '');
                if ($eski && is_file($dizayn_dir . '/' . $eski)) {
                    @unlink($dizayn_dir . '/' . $eski);
                }
                sozlama_saqla('sayt_favicon', $yangi_nom);
                flash_qoy('muvaffaqiyat', 'Favicon yangilandi!');
            }
        } else {
            flash_qoy('xato', 'Faqat .ico, .png yoki .svg fayllar.');
        }
    }

    foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $fayl) @unlink($fayl);
    yonaltir(SAYT_URL . '/admin/dizayn.php');
}

$logo     = sozlama('sayt_logo', '');
$favicon  = sozlama('sayt_favicon', '');
$banner   = sozlama('bosh_banner', '');
$banner_aktiv = sozlama('bosh_banner_aktiv', '0') === '1';
$banner_havola = sozlama('bosh_banner_havola', '');

$admin_sahifa = 'dizayn';
$sahifa_sarlavha = 'Dizayn — Logo, Banner, Favicon';
require_once __DIR__ . '/_layout.php';
?>

<div class="max-w-5xl space-y-6">

    <p class="text-app-2 text-sm">
        Sayt logosini, bosh sahifa bannerini va favicon'ni shu yerdan boshqaring. Yuklangan fayllar avtomatik <code class="text-app">.webp</code> formatga o'tkaziladi.
    </p>

    <!-- ============================================================
         LOGO
         ============================================================ -->
    <div class="glass-card-premium p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-xl font-display text-app flex items-center gap-2">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    Logo
                </h2>
                <p class="text-sm text-app-2 mt-1">Tavsiya etilgan o'lcham: 256×256 px, kvadrat shaklda</p>
            </div>
        </div>

        <div class="flex flex-wrap items-start gap-6">
            <!-- Hozirgi ko'rinish -->
            <div class="flex flex-col items-center gap-2">
                <div class="w-32 h-32 rounded-3xl flex items-center justify-center overflow-hidden shadow-xl"
                     style="background: <?= $logo ? 'var(--bg-elevated)' : 'var(--gradient-primary)' ?>; box-shadow: 0 16px 40px var(--accent-glow);">
                    <?php if ($logo && is_file($dizayn_dir . '/' . $logo)): ?>
                        <img src="<?= e(SAYT_URL) ?>/uploads/dizayn/<?= e($logo) ?>?v=<?= time() ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-white text-5xl font-display font-extrabold">V</span>
                    <?php endif; ?>
                </div>
                <span class="text-xs text-muted-app">Hozirgi logo</span>
            </div>

            <div class="flex-1 min-w-[260px] space-y-4">
                <!-- Yangi logo yuklash -->
                <form method="POST" enctype="multipart/form-data" class="space-y-3">
                    <?= csrf_input() ?>
                    <input type="hidden" name="harakat" value="logo">

                    <div>
                        <label class="field-label">Yangi logo yuklash</label>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" required
                               class="field file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:cursor-pointer file:bg-blue-500/20 file:text-app file:font-medium">
                    </div>

                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Logoni yuklash
                    </button>
                </form>

                <?php if ($logo): ?>
                    <form method="POST" onsubmit="return confirm('Logoni o\'chirish?')">
                        <?= csrf_input() ?>
                        <input type="hidden" name="harakat" value="logo_ochir">
                        <button type="submit" class="btn-danger text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            Olib tashlash
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================
         BANNER
         ============================================================ -->
    <div class="glass-card-premium p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-xl font-display text-app flex items-center gap-2">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                    Bosh sahifa banneri
                </h2>
                <p class="text-sm text-app-2 mt-1">Tavsiya etilgan o'lcham: 1920×600 px, panoramik (16:5 nisbat)</p>
            </div>
        </div>

        <?php if ($banner && is_file($dizayn_dir . '/' . $banner)): ?>
            <div class="rounded-2xl overflow-hidden mb-4 border border-app">
                <img src="<?= e(SAYT_URL) ?>/uploads/dizayn/<?= e($banner) ?>?v=<?= time() ?>" class="w-full h-auto">
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-3 mb-4">
            <?= csrf_input() ?>
            <input type="hidden" name="harakat" value="banner">
            <div>
                <label class="field-label">Yangi banner yuklash</label>
                <input type="file" name="banner" accept="image/png,image/jpeg,image/webp" required
                       class="field file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:cursor-pointer file:bg-blue-500/20 file:text-app file:font-medium">
            </div>
            <button type="submit" class="btn-primary">Bannerni yuklash</button>
        </form>

        <?php if ($banner): ?>
            <hr class="divider my-4">
            <form method="POST" class="space-y-4">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="banner_sozlamalar">

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Banner havolasi (ixtiyoriy)</label>
                        <input type="url" name="bosh_banner_havola"
                               value="<?= e($banner_havola) ?>"
                               placeholder="https://..."
                               class="field">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="bosh_banner_aktiv" value="1" <?= $banner_aktiv ? 'checked' : '' ?>
                                   class="w-5 h-5 rounded">
                            <span class="text-sm text-app">Bosh sahifada bannerni ko'rsatish</span>
                        </label>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">Sozlamalarni saqlash</button>
                </div>
            </form>

            <form method="POST" class="mt-3" onsubmit="return confirm('Bannerni o\'chirish?')">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="banner_ochir">
                <button type="submit" class="btn-danger text-xs">Bannerni olib tashlash</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- ============================================================
         FAVICON
         ============================================================ -->
    <div class="glass-card-premium p-6">
        <h2 class="text-xl font-display text-app flex items-center gap-2 mb-1">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25"/></svg>
            Favicon (brauzer ikonasi)
        </h2>
        <p class="text-sm text-app-2 mb-5">Tavsiya: 32×32 yoki 64×64 px. Format: <code>.ico</code>, <code>.png</code>, <code>.svg</code></p>

        <div class="flex items-center gap-4">
            <?php if ($favicon && is_file($dizayn_dir . '/' . $favicon)): ?>
                <img src="<?= e(SAYT_URL) ?>/uploads/dizayn/<?= e($favicon) ?>?v=<?= time() ?>" class="w-12 h-12 rounded-lg border border-app">
            <?php else: ?>
                <div class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl font-bold border border-app"
                     style="background: var(--gradient-primary);">V</div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="flex-1 flex gap-2">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="favicon">
                <input type="file" name="favicon" accept=".ico,.png,.svg" required
                       class="field flex-1 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:cursor-pointer file:bg-blue-500/20 file:text-app">
                <button type="submit" class="btn-primary">Yuklash</button>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
