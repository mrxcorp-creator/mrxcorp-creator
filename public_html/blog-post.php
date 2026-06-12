<?php
/**
 * VatanParvar Yaypan — Bitta blog post sahifasi
 *
 * URL: /blog/{slug}
 * Router orqali: $_GET['slug'] yoki to'g'ridan-to'g'ri ?slug=...
 */
require_once __DIR__ . '/config/auth.php';

if ((int) sozlama('blog_aktiv', 1) === 0) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$slug = trim(olish('slug'));
if (!$slug) {
    yonaltir(SAYT_URL . '/blog');
}

$post = db_qator('SELECT * FROM bloglar WHERE slug = ? AND holat = "chop"', [$slug]);
if (!$post) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// Ko'rish hisoblagichi (oddiy session-based, takrorlanmasligi uchun)
sessiya_boshla();
$korilgan = $_SESSION['korilgan_postlar'] ?? [];
if (!in_array($post['id'], $korilgan, true)) {
    db_bajar('UPDATE bloglar SET koruv = koruv + 1 WHERE id = ?', [$post['id']]);
    $korilgan[] = $post['id'];
    $_SESSION['korilgan_postlar'] = $korilgan;
    $post['koruv']++;
}

// Tegishli postlar (xuddi shu kategoriyada, hozirgi post tashqari)
$tegishli = db_barcha(
    'SELECT * FROM bloglar WHERE kategoriya = ? AND id != ? AND holat = "chop" ORDER BY yaratilgan DESC LIMIT 3',
    [$post['kategoriya'], $post['id']]
);

// Muallif (agar bor bo'lsa)
$muallif = $post['muallif_id']
    ? db_qator('SELECT ism, familiya, avatar FROM foydalanuvchilar WHERE id = ?', [$post['muallif_id']])
    : null;

$sahifa_sarlavha = $post['sarlavha'] . ' — ' . t('sayt_nomi');
$sahifa_tavsif = $post['qisqa'] ?: mb_substr(strip_tags($post['matn']), 0, 160);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    /* Blog matni uchun maxsus stillar */
    .post-matn h1, .post-matn h2, .post-matn h3, .post-matn h4 {
        margin-top: 2rem; margin-bottom: 1rem; color: #0F172A;
    }
    .post-matn h2 { font-size: 1.75rem; font-weight: 700; }
    .post-matn h3 { font-size: 1.4rem; font-weight: 700; }
    .post-matn h4 { font-size: 1.15rem; font-weight: 600; }
    .post-matn p { margin: 1rem 0; line-height: 1.8; color: #334155; }
    .post-matn a { color: #0EA5E9; text-decoration: underline; }
    .post-matn a:hover { color: #0284C7; }
    .post-matn ul, .post-matn ol { margin: 1rem 0; padding-left: 1.5rem; }
    .post-matn li { margin: .35rem 0; line-height: 1.7; color: #334155; }
    .post-matn ul li { list-style: disc; }
    .post-matn ol li { list-style: decimal; }
    .post-matn img { max-width: 100%; border-radius: 1rem; margin: 1.5rem 0; box-shadow: 0 4px 16px -4px rgba(15,23,42,.1); }
    .post-matn blockquote {
        border-left: 4px solid #0EA5E9;
        padding: .5rem 1.25rem;
        margin: 1.5rem 0;
        background: #F0F9FF;
        border-radius: 0 .75rem .75rem 0;
        color: #0F172A;
        font-style: italic;
    }
    .post-matn code {
        background: #F1F5F9;
        padding: .15rem .4rem;
        border-radius: .35rem;
        font-size: .9em;
        color: #0F172A;
    }
    .post-matn pre {
        background: #0F172A; color: #F8FAFC;
        padding: 1rem; border-radius: .75rem;
        overflow-x: auto; margin: 1.5rem 0;
    }
    .post-matn hr {
        border: 0; border-top: 1px solid #E2E8F0; margin: 2rem 0;
    }
    .post-matn strong { color: #0F172A; font-weight: 700; }
</style>

<article class="max-w-4xl mx-auto px-4 py-10">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-brand-muted mb-6 fade-up" aria-label="Breadcrumb">
        <a href="<?= e(SAYT_URL) ?>" class="hover:text-sky-600 transition">Bosh</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="<?= e(SAYT_URL) ?>/blog" class="hover:text-sky-600 transition">Blog</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-brand-text font-medium truncate"><?= e(mb_substr($post['sarlavha'], 0, 50)) ?></span>
    </nav>

    <!-- Sarlavha bloki -->
    <header class="mb-8 fade-up">
        <div class="flex items-center gap-2 mb-4 text-sm">
            <a href="<?= e(SAYT_URL) ?>/blog?kat=<?= urlencode($post['kategoriya']) ?>"
               class="px-3 py-1 rounded-full bg-sky-100 text-sky-700 font-semibold hover:bg-sky-200 transition">
                <?= e($post['kategoriya']) ?>
            </a>
            <span class="text-brand-light">·</span>
            <span class="text-brand-muted"><?= e(sana($post['yaratilgan'], 'd.m.Y')) ?></span>
            <span class="text-brand-light">·</span>
            <span class="text-brand-muted flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"/></svg>
                <?= (int)$post['koruv'] ?>
            </span>
        </div>

        <h1 class="text-3xl md:text-5xl font-display font-bold text-brand-text mb-4 leading-tight">
            <?= e($post['sarlavha']) ?>
        </h1>

        <?php if ($post['qisqa']): ?>
            <p class="text-lg text-brand-muted leading-relaxed"><?= e($post['qisqa']) ?></p>
        <?php endif; ?>

        <?php if ($muallif): ?>
            <div class="flex items-center gap-3 mt-6 pt-6 border-t border-brand-border">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center font-bold text-white shadow-soft">
                    <?php if ($muallif['avatar'] && is_file(UPLOAD_PATH . '/' . $muallif['avatar'])): ?>
                        <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($muallif['avatar']) ?>" alt="" class="w-full h-full rounded-full object-cover">
                    <?php else: ?>
                        <?= e(mb_strtoupper(mb_substr($muallif['ism'], 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="font-semibold text-brand-text"><?= e($muallif['ism']) ?> <?= e($muallif['familiya'] ?? '') ?></div>
                    <div class="text-xs text-brand-muted">Muallif</div>
                </div>
            </div>
        <?php endif; ?>
    </header>

    <!-- Bosh rasm -->
    <?php if ($post['rasm'] && is_file(UPLOAD_PATH . '/' . $post['rasm'])): ?>
        <div class="rounded-2xl overflow-hidden mb-8 shadow-medium fade-up">
            <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($post['rasm']) ?>"
                 alt="<?= e($post['sarlavha']) ?>"
                 class="w-full h-auto">
        </div>
    <?php endif; ?>

    <!-- Matn -->
    <div class="glass-card p-6 md:p-10 fade-up">
        <div class="post-matn">
            <?= $post['matn'] // HTML — admin'da kiritiladi ?>
        </div>
    </div>

    <!-- Ulashish -->
    <div class="mt-8 glass-card p-5 flex flex-wrap items-center gap-3" data-animate>
        <span class="font-semibold text-brand-text">Ulashing:</span>
        <?php
            $url_encoded = urlencode(SAYT_URL . '/blog/' . $post['slug']);
            $title_encoded = urlencode($post['sarlavha']);
        ?>
        <a target="_blank" rel="noopener" href="https://t.me/share/url?url=<?= $url_encoded ?>&text=<?= $title_encoded ?>"
           class="flex items-center gap-2 px-4 py-2 rounded-xl bg-sky-50 text-sky-700 hover:bg-sky-100 transition text-sm font-semibold">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0z"/></svg>
            Telegram
        </a>
        <a target="_blank" rel="noopener" href="https://wa.me/?text=<?= $title_encoded ?>%20<?= $url_encoded ?>"
           class="flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition text-sm font-semibold">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            WhatsApp
        </a>
        <button type="button"
                onclick="navigator.clipboard.writeText('<?= e(SAYT_URL . '/blog/' . $post['slug']) ?>'); this.innerText='✓ Nusxa olindi!'; setTimeout(() => this.innerText='Havola nusxa olish', 2000);"
                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-violet-50 text-violet-700 hover:bg-violet-100 transition text-sm font-semibold">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
            Havola nusxa olish
        </button>
    </div>

    <!-- Tegishli postlar -->
    <?php if (!empty($tegishli)): ?>
        <section class="mt-12">
            <h2 class="text-2xl md:text-3xl font-display font-bold text-brand-text mb-6">Tegishli maqolalar</h2>
            <div class="grid sm:grid-cols-3 gap-5">
                <?php foreach ($tegishli as $i => $t): ?>
                    <a href="<?= e(SAYT_URL) ?>/blog/<?= e($t['slug']) ?>" class="glass-card glass-card-hover overflow-hidden group" data-animate style="animation-delay: <?= 0.05 * $i ?>s">
                        <?php if ($t['rasm'] && is_file(UPLOAD_PATH . '/' . $t['rasm'])): ?>
                            <div class="aspect-video overflow-hidden bg-sky-50">
                                <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($t['rasm']) ?>" alt="<?= e($t['sarlavha']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </div>
                        <?php else: ?>
                            <div class="aspect-video bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-5xl text-white/80">📰</div>
                        <?php endif; ?>
                        <div class="p-4">
                            <div class="text-xs text-brand-muted mb-1"><?= e(vaqt_oldin($t['yaratilgan'])) ?></div>
                            <h3 class="font-display font-bold text-base text-brand-text line-clamp-2 group-hover:text-sky-600 transition"><?= e($t['sarlavha']) ?></h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Pastki CTA -->
    <div class="mt-12 glass-card p-8 text-center bg-gradient-to-br from-sky-50 to-blue-50" data-animate>
        <h3 class="font-display font-bold text-2xl text-brand-text mb-2">Maqola yoqdimi?</h3>
        <p class="text-brand-muted mb-5">Bizning platformani sinab ko'ring va imtihonga tayyorlaning</p>
        <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary">
            Bepul ro'yxatdan o'tish
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
    </div>
</article>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
