<?php
/**
 * Admin — Blog postlari CRUD
 *
 * Yaratish, tahrirlash, o'chirish + rasm yuklash + holatni boshqarish.
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

// ============================================================
// Slug yaratuvchi yordamchi
// ============================================================
function slug_yarat(string $matn): string {
    // Kirill -> Lotin
    $jadval = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'j',
        'з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
        'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'x','ц'=>'ts',
        'ч'=>'ch','ш'=>'sh','щ'=>'sh','ъ'=>'','ы'=>'i','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
        'ў'=>"o'",'қ'=>'q','ғ'=>"g'",'ҳ'=>'h',
        // Lotin maxsus
        "o'"=>"o'",'g\''=>"g'",
    ];
    $matn = mb_strtolower($matn);
    $matn = strtr($matn, $jadval);
    $matn = preg_replace('/[^a-z0-9\-_]+/', '-', $matn);
    $matn = trim($matn, '-');
    return mb_substr($matn, 0, 180);
}

// ============================================================
// HARAKATLAR
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/bloglar.php');
    }

    $harakat = post('harakat');
    $id = (int) post('id');

    // ----- Yaratish/Tahrirlash -----
    if (in_array($harakat, ['yaratish', 'tahrirlash'], true)) {
        $sarlavha = trim(post('sarlavha'));
        $qisqa = trim(post('qisqa'));
        $matn = post('matn');
        $kategoriya = trim(post('kategoriya')) ?: 'Umumiy';
        $holat = post('holat') === 'qoralama' ? 'qoralama' : 'chop';
        $seo = trim(post('seo_keyword'));
        $slug = trim(post('slug')) ?: slug_yarat($sarlavha);

        if (!$sarlavha || !$matn) {
            flash_qoy('xato', t('kerakli_maydon'));
            yonaltir(SAYT_URL . '/admin/bloglar.php');
        }

        // Slug unikalligini ta'minlash
        $bor = db_qiymat('SELECT id FROM bloglar WHERE slug = ? AND id != ?', [$slug, $id]);
        if ($bor) {
            $slug .= '-' . substr(md5(time()), 0, 6);
        }

        // Rasm
        $rasm = $id ? db_qiymat('SELECT rasm FROM bloglar WHERE id = ?', [$id]) : null;
        if (!empty($_FILES['rasm']['tmp_name'])) {
            $yangi = rasm_saqla($_FILES['rasm'], 'blog', 1200);
            if ($yangi) {
                // Eski rasm o'chiriladi
                if ($rasm && is_file(UPLOAD_PATH . '/' . $rasm)) {
                    @unlink(UPLOAD_PATH . '/' . $rasm);
                }
                $rasm = $yangi;
            }
        }
        if (post('rasm_ochir') === '1') {
            if ($rasm && is_file(UPLOAD_PATH . '/' . $rasm)) {
                @unlink(UPLOAD_PATH . '/' . $rasm);
            }
            $rasm = null;
        }

        if ($id) {
            db_bajar(
                'UPDATE bloglar SET slug=?, sarlavha=?, qisqa=?, matn=?, rasm=?, kategoriya=?, holat=?, seo_keyword=?
                 WHERE id=?',
                [$slug, $sarlavha, $qisqa, $matn, $rasm, $kategoriya, $holat, $seo, $id]
            );
            flash_qoy('muvaffaqiyat', 'Post yangilandi');
        } else {
            db_bajar(
                'INSERT INTO bloglar (slug, sarlavha, qisqa, matn, rasm, muallif_id, kategoriya, holat, seo_keyword)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$slug, $sarlavha, $qisqa, $matn, $rasm, $f['id'], $kategoriya, $holat, $seo]
            );
            flash_qoy('muvaffaqiyat', 'Post yaratildi');
        }

        // Bosh sahifa keshini tozalash (yangi blog ko'rinishi uchun)
        @array_map('unlink', glob(CACHE_PATH . '/indeks_*.html') ?: []);

        yonaltir(SAYT_URL . '/admin/bloglar.php');
    }

    // ----- O'chirish -----
    if ($harakat === 'ochirish') {
        $rasm = db_qiymat('SELECT rasm FROM bloglar WHERE id = ?', [$id]);
        if ($rasm && is_file(UPLOAD_PATH . '/' . $rasm)) {
            @unlink(UPLOAD_PATH . '/' . $rasm);
        }
        db_bajar('DELETE FROM bloglar WHERE id = ?', [$id]);
        @array_map('unlink', glob(CACHE_PATH . '/indeks_*.html') ?: []);
        flash_qoy('muvaffaqiyat', 'Post o\'chirildi');
        yonaltir(SAYT_URL . '/admin/bloglar.php');
    }

    // ----- Holatni tezkor o'zgartirish -----
    if ($harakat === 'holat') {
        $yangi_holat = post('yangi_holat') === 'qoralama' ? 'qoralama' : 'chop';
        db_bajar('UPDATE bloglar SET holat = ? WHERE id = ?', [$yangi_holat, $id]);
        @array_map('unlink', glob(CACHE_PATH . '/indeks_*.html') ?: []);
        flash_qoy('muvaffaqiyat', 'Holat o\'zgartirildi');
        yonaltir(SAYT_URL . '/admin/bloglar.php');
    }
}

// ============================================================
// Tahrirlash uchun post
// ============================================================
$tahrir = null;
if (olish('tahrir')) {
    $tahrir = db_qator('SELECT * FROM bloglar WHERE id = ?', [(int) olish('tahrir')]);
}

// ============================================================
// Filterlar va ro'yxat
// ============================================================
$qidiruv = trim(olish('q'));
$holat_filt = olish('holat');
$shartlar = [];
$params = [];
if ($qidiruv) {
    $shartlar[] = '(sarlavha LIKE ? OR slug LIKE ?)';
    $params[] = '%' . $qidiruv . '%';
    $params[] = '%' . $qidiruv . '%';
}
if ($holat_filt && in_array($holat_filt, ['chop', 'qoralama'], true)) {
    $shartlar[] = 'holat = ?';
    $params[] = $holat_filt;
}
$where = $shartlar ? ' WHERE ' . implode(' AND ', $shartlar) : '';

$jami = (int) db_qiymat("SELECT COUNT(*) FROM bloglar $where", $params);
$bloglar = db_barcha(
    "SELECT * FROM bloglar $where ORDER BY yaratilgan DESC LIMIT 50",
    $params
);

// ============================================================
// Layout
// ============================================================
$admin_sahifa = 'bloglar';
$sahifa_sarlavha = 'Bloglar';
require_once __DIR__ . '/_layout.php';
?>

<!-- Forma -->
<form method="POST" enctype="multipart/form-data"
      class="glass-card p-6 mb-6 fade-up"
      x-data="{ open: <?= $tahrir ? 'true' : 'false' ?>, sarlavha: '<?= e($tahrir['sarlavha'] ?? '') ?>', autoSlug: <?= $tahrir ? 'false' : 'true' ?> }">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
    <input type="hidden" name="id" value="<?= (int) ($tahrir['id'] ?? 0) ?>">

    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h2 class="font-display font-bold text-lg text-brand-text">
            <?php if ($tahrir): ?>
                ✏️ Postni tahrirlash: <span class="text-sky-600"><?= e(mb_substr($tahrir['sarlavha'], 0, 50)) ?></span>
            <?php else: ?>
                ➕ Yangi blog post yaratish
            <?php endif; ?>
        </h2>
        <svg class="w-5 h-5 text-brand-muted transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>

    <div x-show="open" x-transition class="mt-5 space-y-4">

        <div class="grid lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <label class="field-label">Sarlavha <span class="text-rose-500">*</span></label>
                <input name="sarlavha" required x-model="sarlavha" class="field" placeholder="Maqola sarlavhasi">
            </div>
            <div>
                <label class="field-label">Kategoriya</label>
                <input name="kategoriya" value="<?= e($tahrir['kategoriya'] ?? 'Umumiy') ?>" class="field" placeholder="Maslahatlar">
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <label class="field-label">URL slug
                    <span class="text-xs text-brand-muted ml-1">(masalan: <code class="bg-sky-50 px-1 rounded">imtihon-maslahatlar</code>)</span>
                </label>
                <input name="slug"
                       value="<?= e($tahrir['slug'] ?? '') ?>"
                       @input="autoSlug = false"
                       :placeholder="autoSlug ? slugify(sarlavha) : 'maqola-slug'"
                       class="field font-mono text-sm">
            </div>
            <div>
                <label class="field-label">Holat</label>
                <select name="holat" class="field">
                    <option value="chop" <?= ($tahrir['holat'] ?? 'chop') === 'chop' ? 'selected' : '' ?>>Chop etilgan</option>
                    <option value="qoralama" <?= ($tahrir['holat'] ?? '') === 'qoralama' ? 'selected' : '' ?>>Qoralama</option>
                </select>
            </div>
        </div>

        <div>
            <label class="field-label">Qisqa tavsif (preview va meta description uchun)</label>
            <textarea name="qisqa" rows="2" class="field" maxlength="300" placeholder="Maqola haqida 1-2 jumla..."><?= e($tahrir['qisqa'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="field-label">Asosiy matn (HTML qo'llab-quvvatlanadi) <span class="text-rose-500">*</span></label>
            <textarea name="matn" id="matn-textarea" required rows="14" class="field font-mono text-sm" placeholder="<p>Matn shu yerga...</p><h2>Sarlavha</h2><p>Yana matn...</p>"><?= e($tahrir['matn'] ?? '') ?></textarea>
            <div class="mt-2 flex flex-wrap gap-1.5 text-xs">
                <button type="button" onclick="qoshish('<h2>Sarlavha</h2>')" class="px-2 py-1 rounded bg-sky-50 text-sky-700 hover:bg-sky-100 font-mono">H2</button>
                <button type="button" onclick="qoshish('<h3>Kichik sarlavha</h3>')" class="px-2 py-1 rounded bg-sky-50 text-sky-700 hover:bg-sky-100 font-mono">H3</button>
                <button type="button" onclick="qoshish('<p>matn</p>')" class="px-2 py-1 rounded bg-sky-50 text-sky-700 hover:bg-sky-100 font-mono">P</button>
                <button type="button" onclick="qoshish('<strong>qalin</strong>')" class="px-2 py-1 rounded bg-sky-50 text-sky-700 hover:bg-sky-100 font-mono"><b>B</b></button>
                <button type="button" onclick="qoshish('<a href=&quot;#&quot;>havola</a>')" class="px-2 py-1 rounded bg-sky-50 text-sky-700 hover:bg-sky-100 font-mono">Link</button>
                <button type="button" onclick="qoshish('<ul><li>1</li><li>2</li></ul>')" class="px-2 py-1 rounded bg-sky-50 text-sky-700 hover:bg-sky-100 font-mono">UL</button>
                <button type="button" onclick="qoshish('<blockquote>Iqtibos</blockquote>')" class="px-2 py-1 rounded bg-sky-50 text-sky-700 hover:bg-sky-100 font-mono">Quote</button>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="field-label">Bosh rasm (1200px gacha avtomatik kichraytiriladi)</label>
                <input type="file" name="rasm" accept="image/*"
                       class="field file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-sky-100 file:text-sky-700 file:font-semibold file:cursor-pointer">
                <?php if (!empty($tahrir['rasm'])): ?>
                    <div class="mt-3 flex items-center gap-3">
                        <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($tahrir['rasm']) ?>" class="h-20 rounded-lg shadow-soft">
                        <label class="text-xs text-rose-600 flex items-center gap-1">
                            <input type="checkbox" name="rasm_ochir" value="1">
                            Rasmni o'chirish
                        </label>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <label class="field-label">SEO kalit so'zlar (vergul bilan)</label>
                <input name="seo_keyword" value="<?= e($tahrir['seo_keyword'] ?? '') ?>"
                       class="field" placeholder="avto, imtihon, qoidalar">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <?= e(t('saqlash')) ?>
            </button>
            <?php if ($tahrir): ?>
                <a href="<?= e(SAYT_URL) ?>/blog/<?= e($tahrir['slug']) ?>" target="_blank" class="btn-ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    Ko'rib chiqish
                </a>
                <a href="?" class="btn-ghost">Bekor qilish</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
function qoshish(matn) {
    const t = document.getElementById('matn-textarea');
    const start = t.selectionStart;
    t.value = t.value.substring(0, start) + matn + t.value.substring(t.selectionEnd);
    t.focus();
    t.selectionEnd = start + matn.length;
}

// Sarlavhadan slug yaratish (preview)
function slugify(s) {
    const tr = {'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'yo','ж':'j','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'x','ц':'ts','ч':'ch','ш':'sh','щ':'sh','ъ':'','ы':'i','ь':'','э':'e','ю':'yu','я':'ya','ў':"o'",'қ':'q','ғ':"g'",'ҳ':'h'};
    return s.toLowerCase().split('').map(c => tr[c] || c).join('').replace(/[^a-z0-9\-_]+/g, '-').replace(/^-|-$/g, '').slice(0, 180);
}
</script>

<!-- Filtrlar va ro'yxat -->
<form method="GET" class="grid sm:grid-cols-3 gap-3 mb-4">
    <input name="q" value="<?= e($qidiruv) ?>" placeholder="Sarlavha yoki slug bo'yicha qidirish..." class="field sm:col-span-2">
    <select name="holat" onchange="this.form.submit()" class="field">
        <option value="">Barcha holatlar</option>
        <option value="chop" <?= $holat_filt === 'chop' ? 'selected' : '' ?>>Chop etilgan</option>
        <option value="qoralama" <?= $holat_filt === 'qoralama' ? 'selected' : '' ?>>Qoralama</option>
    </select>
</form>

<div class="glass-card p-5 fade-up">
    <h2 class="font-display font-bold text-lg text-brand-text mb-4">Bloglar (<?= $jami ?>)</h2>

    <?php if (empty($bloglar)): ?>
        <div class="py-12 text-center text-brand-muted">
            <div class="text-5xl mb-3">📝</div>
            <p>Hozircha postlar yo'q. Yuqoridagi formadan birinchisini yarating!</p>
        </div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($bloglar as $b): ?>
                <div class="border border-brand-border rounded-xl overflow-hidden hover:shadow-soft transition group">
                    <?php if ($b['rasm'] && is_file(UPLOAD_PATH . '/' . $b['rasm'])): ?>
                        <div class="aspect-video overflow-hidden bg-sky-50">
                            <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($b['rasm']) ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                    <?php else: ?>
                        <div class="aspect-video bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-5xl text-white/80">📰</div>
                    <?php endif; ?>
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2 text-xs">
                            <?php if ($b['holat'] === 'chop'): ?>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-semibold">✓ Chop etilgan</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-semibold">📝 Qoralama</span>
                            <?php endif; ?>
                            <span class="text-brand-muted">·</span>
                            <span class="text-brand-muted"><?= (int)$b['koruv'] ?> 👁</span>
                        </div>
                        <h3 class="font-display font-bold text-sm text-brand-text mb-1 line-clamp-2"><?= e($b['sarlavha']) ?></h3>
                        <div class="text-xs text-brand-muted mb-3 line-clamp-1">
                            <span class="font-mono">/<?= e($b['slug']) ?></span>
                        </div>
                        <div class="flex items-center justify-between gap-2 text-xs pt-3 border-t border-brand-border">
                            <span class="text-brand-muted"><?= e(vaqt_oldin($b['yaratilgan'])) ?></span>
                            <div class="flex gap-1">
                                <a href="<?= e(SAYT_URL) ?>/blog/<?= e($b['slug']) ?>" target="_blank"
                                   class="p-1.5 rounded-lg hover:bg-sky-100 text-sky-600" title="Ko'rib chiqish">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"/></svg>
                                </a>
                                <a href="?tahrir=<?= (int)$b['id'] ?>"
                                   class="p-1.5 rounded-lg hover:bg-amber-100 text-amber-600" title="Tahrirlash">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('Postni o\'chirish — ortga qaytarib bo\'lmaydi. Davom etilsinmi?')">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="ochirish">
                                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                    <button class="p-1.5 rounded-lg hover:bg-rose-100 text-rose-600" title="O'chirish">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
