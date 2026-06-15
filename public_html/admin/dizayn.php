<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/dizayn.php');
    }
    $harakat = post('harakat');

    if ($harakat === 'logo_yukla' && !empty($_FILES['logo']['tmp_name'])) {
        $yangi = rasm_saqla($_FILES['logo'], 'dizayn', 400);
        if ($yangi) {
            $eski = sozlama('logo_yoli', '');
            if ($eski && is_file(UPLOAD_PATH . '/' . $eski)) @unlink(UPLOAD_PATH . '/' . $eski);
            sozlama_saqla('logo_yoli', $yangi);
            foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $kf) @unlink($kf);
            audit_yoz('logo_yangilandi', 'dizayn');
            flash_qoy('muvaffaqiyat', 'Logo yangilandi');
        } else {
            flash_qoy('xato', 'Rasm xato yoki noto\'g\'ri format');
        }
    }

    if ($harakat === 'banner_yukla' && !empty($_FILES['banner']['tmp_name'])) {
        $yangi = rasm_saqla($_FILES['banner'], 'dizayn', 1600);
        if ($yangi) {
            $eski = sozlama('banner_yoli', '');
            if ($eski && is_file(UPLOAD_PATH . '/' . $eski)) @unlink(UPLOAD_PATH . '/' . $eski);
            sozlama_saqla('banner_yoli', $yangi);
            foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $kf) @unlink($kf);
            audit_yoz('banner_yangilandi', 'dizayn');
            flash_qoy('muvaffaqiyat', 'Banner yangilandi');
        } else {
            flash_qoy('xato', 'Rasm xato yoki noto\'g\'ri format');
        }
    }

    if ($harakat === 'logo_tikla') {
        $eski = sozlama('logo_yoli', '');
        if ($eski && is_file(UPLOAD_PATH . '/' . $eski)) @unlink(UPLOAD_PATH . '/' . $eski);
        sozlama_saqla('logo_yoli', '');
        foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $kf) @unlink($kf);
        flash_qoy('muvaffaqiyat', 'Logo standart holatga qaytarildi');
    }

    if ($harakat === 'banner_tikla') {
        $eski = sozlama('banner_yoli', '');
        if ($eski && is_file(UPLOAD_PATH . '/' . $eski)) @unlink(UPLOAD_PATH . '/' . $eski);
        sozlama_saqla('banner_yoli', '');
        foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $kf) @unlink($kf);
        flash_qoy('muvaffaqiyat', 'Banner standart holatga qaytarildi');
    }

    if ($harakat === 'login_matn') {
        sozlama_saqla('login_chap_sarlavha', post('login_chap_sarlavha'));
        sozlama_saqla('login_chap_matn',     post('login_chap_matn'));
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
    }

    if ($harakat === 'himoya') {
        sozlama_saqla('himoya_anti_copy', post('himoya_anti_copy') === '1' ? '1' : '0');
        sozlama_saqla('himoya_devtools',  post('himoya_devtools') === '1' ? '1' : '0');
        sozlama_saqla('himoya_ip_block',  post('himoya_ip_block') === '1' ? '1' : '0');
        audit_yoz('himoya_sozlamalari', 'dizayn', null, [
            'anti_copy' => post('himoya_anti_copy'),
            'devtools'  => post('himoya_devtools'),
            'ip_block'  => post('himoya_ip_block'),
        ]);
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
    }

    if (in_array($harakat, ['slayd_yarat', 'slayd_yangila'], true)) {
        $id = (int) post('id');
        $sarlavha = post('sarlavha');
        $matn = post('matn');
        $tartib = (int) post('tartib');
        $holat = post('holat') === 'nofaol' ? 'nofaol' : 'faol';

        $rasm = $id ? db_qiymat('SELECT rasm FROM login_slaydlar WHERE id = ?', [$id]) : null;
        if (!empty($_FILES['rasm']['tmp_name'])) {
            $yangi = rasm_saqla($_FILES['rasm'], 'dizayn', 1200);
            if ($yangi) {
                if ($rasm && is_file(UPLOAD_PATH . '/' . $rasm)) @unlink(UPLOAD_PATH . '/' . $rasm);
                $rasm = $yangi;
            }
        }
        if (post('rasm_ochir') === '1' && $rasm) {
            if (is_file(UPLOAD_PATH . '/' . $rasm)) @unlink(UPLOAD_PATH . '/' . $rasm);
            $rasm = null;
        }

        if (!$sarlavha) {
            flash_qoy('xato', t('kerakli_maydon'));
        } elseif ($id) {
            db_bajar(
                'UPDATE login_slaydlar SET sarlavha=?, matn=?, rasm=?, tartib=?, holat=? WHERE id=?',
                [$sarlavha, $matn, $rasm, $tartib, $holat, $id]
            );
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        } else {
            db_bajar(
                'INSERT INTO login_slaydlar (sarlavha, matn, rasm, tartib, holat) VALUES (?, ?, ?, ?, ?)',
                [$sarlavha, $matn, $rasm, $tartib, $holat]
            );
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
    }

    if ($harakat === 'slayd_ochir') {
        $id = (int) post('id');
        $rasm = db_qiymat('SELECT rasm FROM login_slaydlar WHERE id = ?', [$id]);
        if ($rasm && is_file(UPLOAD_PATH . '/' . $rasm)) @unlink(UPLOAD_PATH . '/' . $rasm);
        db_bajar('DELETE FROM login_slaydlar WHERE id = ?', [$id]);
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
    }

    yonaltir(SAYT_URL . '/admin/dizayn.php');
}

try {
    $slaydlar = db_barcha('SELECT * FROM login_slaydlar ORDER BY tartib, id');
} catch (Throwable $e) {
    $slaydlar = [];
}
$tahrir_slayd = olish('tahrir') ? db_qator('SELECT * FROM login_slaydlar WHERE id = ?', [(int) olish('tahrir')]) : null;

$logo_yoli = sozlama('logo_yoli', '');
$banner_yoli = sozlama('banner_yoli', '');

$admin_sahifa = 'dizayn';
$sahifa_sarlavha = 'Dizayn boshqaruvi';
require_once __DIR__ . '/_layout.php';
?>

<div x-data="{tab: 'logo'}" class="space-y-6">
    <div class="glass p-1.5 inline-flex flex-wrap gap-1">
        <button @click="tab = 'logo'" :class="tab === 'logo' ? 'grad-bg text-white' : 'hover:bg-white/5'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition">🖼️ Logo</button>
        <button @click="tab = 'banner'" :class="tab === 'banner' ? 'grad-bg text-white' : 'hover:bg-white/5'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition">🎨 Banner</button>
        <button @click="tab = 'slaydlar'" :class="tab === 'slaydlar' ? 'grad-bg text-white' : 'hover:bg-white/5'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition">📑 Login slaydlari</button>
        <button @click="tab = 'matn'" :class="tab === 'matn' ? 'grad-bg text-white' : 'hover:bg-white/5'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition">📝 Matnlar</button>
        <button @click="tab = 'himoya'" :class="tab === 'himoya' ? 'grad-bg text-white' : 'hover:bg-white/5'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition">🛡️ Himoya</button>
    </div>

    <div x-show="tab === 'logo'" class="glass p-6 fade-up">
        <h2 class="text-xl font-display font-bold mb-4">🖼️ Sayt logosi</h2>
        <p class="text-sm text-muted mb-4">Tavsiya: PNG/SVG/WebP, kvadrat (1:1), kamida 200×200px.</p>

        <div class="grid sm:grid-cols-2 gap-6">
            <div>
                <label class="field-label">Hozirgi logo</label>
                <div class="aspect-square w-32 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center overflow-hidden">
                    <?php if ($logo_yoli && is_file(UPLOAD_PATH . '/' . $logo_yoli)): ?>
                        <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($logo_yoli) ?>" alt="Logo" class="max-w-full max-h-full">
                    <?php else: ?>
                        <img src="<?= e(SAYT_URL) ?>/assets/img/logo-mark.svg" alt="Logo (default)" class="max-w-full max-h-full p-3">
                    <?php endif; ?>
                </div>
                <?php if ($logo_yoli): ?>
                    <p class="text-xs text-success mt-2">✓ Custom logo qo'yilgan</p>
                <?php else: ?>
                    <p class="text-xs text-muted mt-2">Standart SVG logo ishlatilmoqda</p>
                <?php endif; ?>
            </div>

            <div>
                <form method="POST" enctype="multipart/form-data" class="space-y-3">
                    <?= csrf_input() ?>
                    <input type="hidden" name="harakat" value="logo_yukla">
                    <label class="field-label">Yangi logo yuklash</label>
                    <input type="file" name="logo" accept="image/*" required
                           class="field file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-violet/20 file:text-violet">
                    <button type="submit" class="btn btn-primary w-full">📤 Yuklash</button>
                </form>

                <?php if ($logo_yoli): ?>
                    <form method="POST" class="mt-3" onsubmit="return confirm('Standart logoga qaytarilsinmi?')">
                        <?= csrf_input() ?>
                        <input type="hidden" name="harakat" value="logo_tikla">
                        <button type="submit" class="btn btn-ghost w-full text-sm">↺ Standart logoga qaytarish</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div x-show="tab === 'banner'" class="glass p-6 fade-up" x-cloak>
        <h2 class="text-xl font-display font-bold mb-4">🎨 Bosh sahifa banner</h2>
        <p class="text-sm text-muted mb-4">Tavsiya: 1600×800px (16:8), JPG yoki WebP. OG image sifatida ham ishlatiladi.</p>

        <?php if ($banner_yoli && is_file(UPLOAD_PATH . '/' . $banner_yoli)): ?>
            <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($banner_yoli) ?>" alt="Banner" class="w-full max-w-2xl rounded-2xl mb-4">
            <p class="text-xs text-success mb-4">✓ Custom banner qo'yilgan</p>
        <?php else: ?>
            <img src="<?= e(SAYT_URL) ?>/assets/img/banner.svg" alt="Banner (default)" class="w-full max-w-2xl rounded-2xl mb-4">
            <p class="text-xs text-muted mb-4">Standart SVG banner ishlatilmoqda</p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-3 max-w-md">
            <?= csrf_input() ?>
            <input type="hidden" name="harakat" value="banner_yukla">
            <label class="field-label">Yangi banner yuklash</label>
            <input type="file" name="banner" accept="image/*" required
                   class="field file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-violet/20 file:text-violet">
            <button type="submit" class="btn btn-primary">📤 Yuklash</button>
        </form>

        <?php if ($banner_yoli): ?>
            <form method="POST" class="mt-3 max-w-md" onsubmit="return confirm('Standart bannerga qaytarilsinmi?')">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="banner_tikla">
                <button type="submit" class="btn btn-ghost text-sm">↺ Standart bannerga qaytarish</button>
            </form>
        <?php endif; ?>
    </div>

    <div x-show="tab === 'slaydlar'" class="space-y-4 fade-up" x-cloak>
        <form method="POST" enctype="multipart/form-data" class="glass p-5"
              x-data="{open: <?= $tahrir_slayd ? 'true' : 'false' ?>}">
            <?= csrf_input() ?>
            <input type="hidden" name="harakat" value="<?= $tahrir_slayd ? 'slayd_yangila' : 'slayd_yarat' ?>">
            <input type="hidden" name="id" value="<?= (int) ($tahrir_slayd['id'] ?? 0) ?>">

            <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                <h2 class="font-display font-bold text-lg">
                    <?= $tahrir_slayd ? '✏️ Slayd tahrirlash' : '➕ Yangi slayd qo\'shish' ?>
                </h2>
                <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>

            <div x-show="open" x-transition class="grid sm:grid-cols-2 gap-4 mt-4">
                <div class="sm:col-span-2">
                    <label class="field-label">Sarlavha *</label>
                    <input name="sarlavha" required value="<?= e($tahrir_slayd['sarlavha'] ?? '') ?>" class="field">
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label">Matn</label>
                    <textarea name="matn" rows="3" class="field"><?= e($tahrir_slayd['matn'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="field-label">Rasm (ixtiyoriy, 1200px gacha siqiladi)</label>
                    <input type="file" name="rasm" accept="image/*"
                           class="field file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-violet/20 file:text-violet">
                    <?php if (!empty($tahrir_slayd['rasm'])): ?>
                        <div class="mt-2 flex items-center gap-3">
                            <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($tahrir_slayd['rasm']) ?>" class="h-16 rounded-lg">
                            <label class="text-xs text-danger flex items-center gap-1"><input type="checkbox" name="rasm_ochir" value="1"> Rasmni o'chirish</label>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Tartib</label>
                    <input type="number" name="tartib" value="<?= (int) ($tahrir_slayd['tartib'] ?? 0) ?>" class="field">
                </div>
                <div>
                    <label class="field-label">Holat</label>
                    <select name="holat" class="field">
                        <option value="faol" <?= ($tahrir_slayd['holat'] ?? 'faol') === 'faol' ? 'selected' : '' ?>>Faol</option>
                        <option value="nofaol" <?= ($tahrir_slayd['holat'] ?? '') === 'nofaol' ? 'selected' : '' ?>>Nofaol</option>
                    </select>
                </div>
                <div class="sm:col-span-2 flex gap-3">
                    <button type="submit" class="btn btn-primary"><?= e(t('saqlash')) ?></button>
                    <?php if ($tahrir_slayd): ?>
                        <a href="?" class="btn btn-ghost"><?= e(t('bekor_qilish')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach ($slaydlar as $s): ?>
                <div class="glass p-5 <?= $s['holat'] === 'nofaol' ? 'opacity-60' : '' ?>">
                    <?php if (!empty($s['rasm']) && is_file(UPLOAD_PATH . '/' . $s['rasm'])): ?>
                        <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($s['rasm']) ?>" class="w-full h-40 object-cover rounded-xl mb-3">
                    <?php else: ?>
                        <div class="w-full h-40 grad-bg-soft rounded-xl mb-3 flex items-center justify-center text-4xl opacity-50">📷</div>
                    <?php endif; ?>
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-display font-bold"><?= e($s['sarlavha']) ?></h3>
                        <span class="chip text-xs !py-0.5 !px-2 <?= $s['holat'] === 'faol' ? 'bg-success/15 text-success border-success/30' : 'bg-danger/15 text-danger border-danger/30' ?>">
                            <?= e($s['holat']) ?>
                        </span>
                    </div>
                    <?php if (!empty($s['matn'])): ?>
                        <p class="text-sm text-muted mb-3 line-clamp-2"><?= e($s['matn']) ?></p>
                    <?php endif; ?>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-muted">Tartib: <?= (int) $s['tartib'] ?></span>
                        <div class="flex gap-3">
                            <a href="?tahrir=<?= (int) $s['id'] ?>" class="text-amber hover:underline"><?= e(t('tahrirlash')) ?></a>
                            <form method="POST" class="inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                <?= csrf_input() ?>
                                <input type="hidden" name="harakat" value="slayd_ochir">
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                <button class="text-danger hover:underline"><?= e(t('ochirish')) ?></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($slaydlar)): ?>
            <div class="glass p-12 text-center text-muted text-sm">
                <p>Hali slaydlar yo'q. Yuqoridagi formadan qo'shing.</p>
            </div>
        <?php endif; ?>
    </div>

    <form x-show="tab === 'matn'" method="POST" class="glass p-6 fade-up max-w-2xl" x-cloak>
        <?= csrf_input() ?>
        <input type="hidden" name="harakat" value="login_matn">

        <h2 class="text-xl font-display font-bold mb-4">📝 Login sahifa matnlari</h2>
        <p class="text-sm text-muted mb-5">Login va Register sahifalarining chap tomonidagi yuqori matnlar.</p>

        <div class="space-y-4">
            <div>
                <label class="field-label">Logo yonidagi sayt nomi</label>
                <input name="login_chap_sarlavha" class="field"
                       value="<?= e(sozlama('login_chap_sarlavha', t('sayt_nomi'))) ?>">
            </div>
            <div>
                <label class="field-label">Brand shiori (ixtiyoriy)</label>
                <input name="login_chap_matn" class="field"
                       value="<?= e(sozlama('login_chap_matn', t('sayt_shior'))) ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-5"><?= e(t('saqlash')) ?></button>
    </form>

    <form x-show="tab === 'himoya'" method="POST" class="glass p-6 fade-up max-w-2xl" x-cloak>
        <?= csrf_input() ?>
        <input type="hidden" name="harakat" value="himoya">

        <h2 class="text-xl font-display font-bold mb-2">🛡️ Kontent himoyasi</h2>
        <p class="text-sm text-muted mb-5">Foydalanuvchilar uchun. Admin va developer akkauntlar uchun bu cheklovlar avtomatik o'chiriladi.</p>

        <div class="space-y-4">
            <label class="flex items-start gap-3 p-3 rounded-xl bg-white/3 cursor-pointer">
                <input type="checkbox" name="himoya_anti_copy" value="1" <?= sozlama('himoya_anti_copy', 1) ? 'checked' : '' ?>
                       class="w-5 h-5 mt-0.5 rounded border-white/20 bg-white/5 text-violet">
                <div>
                    <div class="font-medium">Anti-copy himoyasi</div>
                    <div class="text-xs text-muted">Matn tanlash, nusxa olish, drag&drop bloklanadi. Right-click menyu o'chiriladi.</div>
                </div>
            </label>

            <label class="flex items-start gap-3 p-3 rounded-xl bg-white/3 cursor-pointer">
                <input type="checkbox" name="himoya_devtools" value="1" <?= sozlama('himoya_devtools', 1) ? 'checked' : '' ?>
                       class="w-5 h-5 mt-0.5 rounded border-white/20 bg-white/5 text-violet">
                <div>
                    <div class="font-medium">DevTools (F12) ogohlantirish</div>
                    <div class="text-xs text-muted">F12, Ctrl+U, Ctrl+Shift+I bloklanadi va urinishlar qayd qilinadi.</div>
                </div>
            </label>

            <label class="flex items-start gap-3 p-3 rounded-xl bg-amber/5 border border-amber/20 cursor-pointer">
                <input type="checkbox" name="himoya_ip_block" value="1" <?= sozlama('himoya_ip_block', 0) ? 'checked' : '' ?>
                       class="w-5 h-5 mt-0.5 rounded border-amber/30 bg-amber/5 text-amber">
                <div>
                    <div class="font-medium text-amber">⚠️ IP bloklash (xavfli)</div>
                    <div class="text-xs text-muted">5 daqiqada 15+ urinish bo'lsa, IP 1 soatga bloklanadi. Bunda foydalanuvchi noto'g'ri tanish bo'lib bloklanishi mumkin.</div>
                </div>
            </label>
        </div>

        <button type="submit" class="btn btn-primary mt-5"><?= e(t('saqlash')) ?></button>
    </form>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
