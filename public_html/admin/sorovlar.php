<?php
/**
 * Admin — Aloqa formasidan kelgan so'rovlar
 *
 * - So'rovlar ro'yxati (yangi/ko'rilgan/javoblangan)
 * - Bitta so'rovni ko'rish va admin javob qoldirish
 * - O'chirish
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/sorovlar.php');
    }
    $harakat = post('harakat');
    $id = (int) post('id');
    $sorov = $id ? db_qator('SELECT * FROM sorovlar WHERE id = ?', [$id]) : null;

    // ----- Holat o'zgartirish -----
    if ($sorov && $harakat === 'holat') {
        $yangi = post('yangi_holat');
        if (in_array($yangi, ['yangi', 'korilgan', 'javoblangan'], true)) {
            db_bajar('UPDATE sorovlar SET holat = ? WHERE id = ?', [$yangi, $id]);
            flash_qoy('muvaffaqiyat', 'Holat yangilandi');
        }
    }

    // ----- Admin javob saqlash -----
    if ($sorov && $harakat === 'javob') {
        $javob = trim(post('admin_javob'));
        db_bajar(
            'UPDATE sorovlar SET admin_javob = ?, holat = "javoblangan" WHERE id = ?',
            [$javob, $id]
        );
        flash_qoy('muvaffaqiyat', 'Javob saqlandi');

        // Foydalanuvchi telefoniga Telegram orqali javob yuborish (agar akkaunti bo'lsa)
        $foyd = db_qator('SELECT telegram_id, ism FROM foydalanuvchilar WHERE telefon = ?', [$sorov['telefon']]);
        if ($foyd && $foyd['telegram_id'] && $javob) {
            $matn = "📩 <b>Sizning so'rovingizga javob:</b>\n\n";
            $matn .= "<i>" . htmlspecialchars($sorov['xabar'], ENT_QUOTES) . "</i>\n\n";
            $matn .= "💬 <b>Javob:</b>\n" . htmlspecialchars($javob, ENT_QUOTES);
            telegram_yubor($foyd['telegram_id'], $matn);
        }
    }

    // ----- O'chirish -----
    if ($sorov && $harakat === 'ochirish') {
        db_bajar('DELETE FROM sorovlar WHERE id = ?', [$id]);
        flash_qoy('muvaffaqiyat', 'So\'rov o\'chirildi');
    }

    // ----- Ko'rish (avtomatik holatga o'tkazish) -----
    if ($sorov && $harakat === 'kor') {
        if ($sorov['holat'] === 'yangi') {
            db_bajar('UPDATE sorovlar SET holat = "korilgan" WHERE id = ?', [$id]);
        }
    }

    yonaltir(SAYT_URL . '/admin/sorovlar.php' . (olish('id') ? '?id=' . (int) olish('id') : ''));
}

// Bitta so'rovni ko'rish
$tanlangan = null;
if (olish('id')) {
    $tanlangan = db_qator('SELECT * FROM sorovlar WHERE id = ?', [(int) olish('id')]);
    // Yangi -> ko'rilgan ga avtomatik
    if ($tanlangan && $tanlangan['holat'] === 'yangi') {
        db_bajar('UPDATE sorovlar SET holat = "korilgan" WHERE id = ?', [$tanlangan['id']]);
        $tanlangan['holat'] = 'korilgan';
    }
}

// Filterlar
$holat_filt = olish('holat');
$qidiruv = trim(olish('q'));
$shartlar = [];
$params = [];
if ($holat_filt && in_array($holat_filt, ['yangi', 'korilgan', 'javoblangan'], true)) {
    $shartlar[] = 'holat = ?';
    $params[] = $holat_filt;
}
if ($qidiruv) {
    $shartlar[] = '(ism LIKE ? OR telefon LIKE ? OR xabar LIKE ?)';
    $params[] = '%' . $qidiruv . '%';
    $params[] = '%' . $qidiruv . '%';
    $params[] = '%' . $qidiruv . '%';
}
$where = $shartlar ? ' WHERE ' . implode(' AND ', $shartlar) : '';

$royxat = db_barcha("SELECT * FROM sorovlar $where ORDER BY yaratilgan DESC LIMIT 100", $params);

// Statistika
$stat = [
    'jami'        => (int) db_qiymat('SELECT COUNT(*) FROM sorovlar'),
    'yangi'       => (int) db_qiymat('SELECT COUNT(*) FROM sorovlar WHERE holat = "yangi"'),
    'korilgan'    => (int) db_qiymat('SELECT COUNT(*) FROM sorovlar WHERE holat = "korilgan"'),
    'javoblangan' => (int) db_qiymat('SELECT COUNT(*) FROM sorovlar WHERE holat = "javoblangan"'),
];

$admin_sahifa = 'sorovlar';
$sahifa_sarlavha = "So'rovlar";
require_once __DIR__ . '/_layout.php';
?>

<!-- Statistika kartalari -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    <a href="?" class="glass-card p-4 hover:shadow-soft transition <?= !$holat_filt ? 'ring-2 ring-sky-400' : '' ?>">
        <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">Jami</div>
        <div class="text-2xl font-display font-bold text-brand-text"><?= $stat['jami'] ?></div>
    </a>
    <a href="?holat=yangi" class="glass-card p-4 hover:shadow-soft transition <?= $holat_filt === 'yangi' ? 'ring-2 ring-rose-400' : '' ?>">
        <div class="text-xs text-rose-600 uppercase tracking-wider mb-1 flex items-center gap-1.5">
            <span class="relative flex h-2 w-2">
                <span class="<?= $stat['yangi'] ? 'animate-ping' : '' ?> absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
            </span>
            Yangi
        </div>
        <div class="text-2xl font-display font-bold text-rose-600"><?= $stat['yangi'] ?></div>
    </a>
    <a href="?holat=korilgan" class="glass-card p-4 hover:shadow-soft transition <?= $holat_filt === 'korilgan' ? 'ring-2 ring-amber-400' : '' ?>">
        <div class="text-xs text-amber-600 uppercase tracking-wider mb-1">Ko'rilgan</div>
        <div class="text-2xl font-display font-bold text-amber-600"><?= $stat['korilgan'] ?></div>
    </a>
    <a href="?holat=javoblangan" class="glass-card p-4 hover:shadow-soft transition <?= $holat_filt === 'javoblangan' ? 'ring-2 ring-emerald-400' : '' ?>">
        <div class="text-xs text-emerald-600 uppercase tracking-wider mb-1">Javoblangan</div>
        <div class="text-2xl font-display font-bold text-emerald-600"><?= $stat['javoblangan'] ?></div>
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-5">

    <!-- Chap: ro'yxat -->
    <div class="lg:col-span-1">
        <form method="GET" class="mb-3">
            <?php if ($holat_filt): ?>
                <input type="hidden" name="holat" value="<?= e($holat_filt) ?>">
            <?php endif; ?>
            <div class="relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-brand-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input name="q" value="<?= e($qidiruv) ?>" placeholder="Qidirish..." class="field pl-11 text-sm">
            </div>
        </form>

        <div class="space-y-2 max-h-[700px] overflow-y-auto pr-2">
            <?php if (empty($royxat)): ?>
                <div class="glass-card p-8 text-center text-brand-muted text-sm">
                    <div class="text-4xl mb-2">📭</div>
                    Hozircha so'rov yo'q
                </div>
            <?php else: ?>
                <?php foreach ($royxat as $r):
                    $aktiv = $tanlangan && $tanlangan['id'] === $r['id'];
                    $rang_class = match ($r['holat']) {
                        'yangi'       => 'border-rose-300 bg-rose-50',
                        'korilgan'    => 'border-amber-300 bg-amber-50/40',
                        'javoblangan' => 'border-emerald-300 bg-emerald-50/40',
                        default       => 'border-brand-border'
                    };
                ?>
                    <a href="?id=<?= (int)$r['id'] ?><?= $holat_filt ? '&holat=' . urlencode($holat_filt) : '' ?><?= $qidiruv ? '&q=' . urlencode($qidiruv) : '' ?>"
                       class="block p-3 rounded-xl border-l-4 transition hover:shadow-soft <?= $rang_class ?> <?= $aktiv ? 'ring-2 ring-sky-500 shadow-soft' : '' ?>">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <strong class="text-brand-text text-sm truncate"><?= e($r['ism']) ?></strong>
                            <?php if ($r['holat'] === 'yangi'): ?>
                                <span class="px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-bold uppercase animate-pulse-soft">Yangi</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-brand-muted font-mono mb-1.5"><?= e($r['telefon']) ?></div>
                        <p class="text-xs text-brand-body line-clamp-2 mb-2"><?= e($r['xabar']) ?></p>
                        <div class="flex items-center justify-between text-[11px] text-brand-muted">
                            <?php if ($r['mavzu']): ?>
                                <span class="px-1.5 py-0.5 rounded bg-white text-sky-700 font-semibold"><?= e($r['mavzu']) ?></span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <span><?= e(vaqt_oldin($r['yaratilgan'])) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- O'ng: Tanlangan so'rovni ko'rish -->
    <div class="lg:col-span-2">
        <?php if ($tanlangan): ?>
            <div class="glass-card p-6 fade-up">
                <!-- Sarlavha -->
                <div class="flex items-start justify-between mb-5 pb-5 border-b border-brand-border">
                    <div>
                        <h2 class="text-2xl font-display font-bold text-brand-text mb-1"><?= e($tanlangan['ism']) ?></h2>
                        <div class="flex items-center gap-3 text-sm text-brand-muted">
                            <a href="tel:<?= e($tanlangan['telefon']) ?>" class="font-mono hover:text-sky-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <?= e($tanlangan['telefon']) ?>
                            </a>
                            <?php if ($tanlangan['email']): ?>
                                <a href="mailto:<?= e($tanlangan['email']) ?>" class="hover:text-sky-600 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <?= e($tanlangan['email']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase
                        <?= match ($tanlangan['holat']) {
                            'yangi'       => 'bg-rose-100 text-rose-700',
                            'korilgan'    => 'bg-amber-100 text-amber-700',
                            'javoblangan' => 'bg-emerald-100 text-emerald-700',
                        } ?>">
                        <?= e($tanlangan['holat']) ?>
                    </span>
                </div>

                <!-- Ma'lumotlar -->
                <div class="grid sm:grid-cols-2 gap-4 mb-5 text-sm">
                    <?php if ($tanlangan['mavzu']): ?>
                        <div>
                            <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">Mavzu</div>
                            <div class="font-semibold text-brand-text"><?= e($tanlangan['mavzu']) ?></div>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">Sana</div>
                        <div class="font-semibold text-brand-text"><?= e(sana($tanlangan['yaratilgan'], 'd.m.Y H:i')) ?></div>
                    </div>
                    <?php if ($tanlangan['ip']): ?>
                        <div>
                            <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">IP</div>
                            <div class="font-mono text-brand-text"><?= e($tanlangan['ip']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Xabar -->
                <div class="mb-6">
                    <div class="text-xs text-brand-muted uppercase tracking-wider mb-2">Xabar matni</div>
                    <div class="p-4 rounded-xl bg-sky-50/50 border border-brand-border whitespace-pre-wrap text-brand-body leading-relaxed">
                        <?= e($tanlangan['xabar']) ?>
                    </div>
                </div>

                <!-- Admin javob formasi -->
                <form method="POST" class="mb-5">
                    <?= csrf_input() ?>
                    <input type="hidden" name="harakat" value="javob">
                    <input type="hidden" name="id" value="<?= (int)$tanlangan['id'] ?>">

                    <label class="field-label">Admin javobi
                        <span class="text-xs text-brand-muted ml-2">(agar foydalanuvchi Telegram'ga ulangan bo'lsa, javob avtomatik yuboriladi)</span>
                    </label>
                    <textarea name="admin_javob" rows="5" class="field" placeholder="Foydalanuvchiga yozadigan javobingiz..."><?= e($tanlangan['admin_javob'] ?? '') ?></textarea>

                    <div class="flex flex-wrap gap-3 mt-4">
                        <button type="submit" class="btn-primary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Javob saqlash
                        </button>
                        <a href="tel:<?= e($tanlangan['telefon']) ?>" class="btn-ghost">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            Qo'ng'iroq qilish
                        </a>
                    </div>
                </form>

                <!-- Qo'shimcha amallar -->
                <div class="flex flex-wrap gap-2 pt-5 border-t border-brand-border">
                    <?php if ($tanlangan['holat'] !== 'yangi'): ?>
                        <form method="POST" class="inline">
                            <?= csrf_input() ?>
                            <input type="hidden" name="harakat" value="holat">
                            <input type="hidden" name="id" value="<?= (int)$tanlangan['id'] ?>">
                            <input type="hidden" name="yangi_holat" value="yangi">
                            <button class="text-xs px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 font-semibold">
                                ⚠ Yangi deb belgilash
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" class="inline" onsubmit="return confirm('So\'rovni o\'chirish — ortga qaytarib bo\'lmaydi. Davom etilsinmi?')">
                        <?= csrf_input() ?>
                        <input type="hidden" name="harakat" value="ochirish">
                        <input type="hidden" name="id" value="<?= (int)$tanlangan['id'] ?>">
                        <button class="btn-danger text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            O'chirish
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="glass-card p-16 text-center">
                <div class="text-6xl mb-4">📩</div>
                <h3 class="font-display font-bold text-xl text-brand-text mb-2">So'rovni tanlang</h3>
                <p class="text-brand-muted">Chap tomondan ro'yxatdan kerakli so'rovni tanlang</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
