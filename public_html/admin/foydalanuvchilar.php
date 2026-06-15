<?php
/**
 * VatanParvar Yaypan — Foydalanuvchilar boshqaruvi
 * FIX: N+1 query → subquery bilan bitta so'rovga keltirildi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

/* ── POST amallar ────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/foydalanuvchilar.php');
    }

    $harakat = post('harakat');
    $id      = (int) post('id');
    $maqsad  = $id ? db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$id]) : null;

    if ($maqsad) {

        // Holat o'zgartirish
        if ($harakat === 'block') {
            $yangi = $maqsad['holat'] === 'faol' ? 'bloklangan' : 'faol';
            db_bajar('UPDATE foydalanuvchilar SET holat = ? WHERE id = ?', [$yangi, $id]);
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }

        // Rol o'zgartirish (faqat developer)
        if ($harakat === 'rol' && $f['rol'] === 'developer') {
            $rol = post('rol');
            if (in_array($rol, ['user','admin','developer'], true) && $id !== $f['id']) {
                db_bajar('UPDATE foydalanuvchilar SET rol = ? WHERE id = ?', [$rol, $id]);
                flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
            }
        }

        // Bonus qo'shish
        if ($harakat === 'bonus') {
            $bonus = (float) post('bonus');
            if ($bonus > 0) {
                db_bajar(
                    'UPDATE foydalanuvchilar SET bonus_balans = bonus_balans + ? WHERE id = ?',
                    [$bonus, $id]
                );
                flash_qoy('muvaffaqiyat', 'Bonus qo\'shildi: ' . pul($bonus));
            }
        }

        // Manual obuna berish
        if ($harakat === 'obuna') {
            $tarif_id = (int) post('tarif_id');
            $tarif    = $tarif_id
                ? db_qator('SELECT * FROM tariflar WHERE id = ?', [$tarif_id])
                : null;

            if ($tarif) {
                $kun = match ($tarif['tur']) {
                    'kun'   => (int) $tarif['qiymat'],
                    'oy'    => (int) $tarif['qiymat'] * 30,
                    default => 365,
                };
                db_bajar(
                    'INSERT INTO obunalar (foydalanuvchi_id, tarif_id, boshlanish, tugash, holat)
                     VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
                    [$id, $tarif_id, $kun]
                );
                db_bajar(
                    'INSERT INTO tolovlar
                     (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                     VALUES (?, ?, ?, "manual", "muvaffaqiyatli", ?)',
                    [$id, $tarif_id, $tarif['narx'], 'Admin: ' . $f['ism']]
                );
                if ($maqsad['telegram_id']) {
                    telegram_yubor(
                        (int) $maqsad['telegram_id'],
                        "🎉 <b>Sizga obuna sovg'a qilindi!</b>\n"
                        . "Tarif: <b>{$tarif['nomi']}</b>"
                    );
                }
                flash_qoy('muvaffaqiyat', "Obuna qo'shildi: {$tarif['nomi']}");
            }
        }
    }

    yonaltir(SAYT_URL . '/admin/foydalanuvchilar.php?' . http_build_query([
        'q'   => olish('q'),
        'rol' => olish('rol'),
        'p'   => olish('p'),
    ]));
}

/* ── Filtrlar ────────────────────────────────────────────── */
$qidiruv  = olish('q');
$rol_filt = olish('rol');
$shartlar = [];
$params   = [];

if ($qidiruv) {
    $shartlar[] = '(fo.ism LIKE ? OR fo.familiya LIKE ? OR fo.telefon LIKE ?)';
    array_push($params, "%{$qidiruv}%", "%{$qidiruv}%", "%{$qidiruv}%");
}
if ($rol_filt && in_array($rol_filt, ['user','admin','developer'], true)) {
    $shartlar[] = 'fo.rol = ?';
    $params[]   = $rol_filt;
}
$where = $shartlar ? 'WHERE ' . implode(' AND ', $shartlar) : '';

$jami   = (int) db_qiymat("SELECT COUNT(*) FROM foydalanuvchilar fo {$where}", $params);
$sahifa = max(1, (int) olish('p'));
$limit  = 25;
$offset = ($sahifa - 1) * $limit;

/*
 * N+1 FIX: Foydalanuvchilar + obuna holati + test soni bitta so'rovda.
 * Avvalgi kod: foreach ichida har foydalanuvchi uchun alohida DB so'rovi.
 * Yangi kod  : subquery bilan hammasi bitta SQL-da.
 */
$royxat = db_barcha(
    "SELECT fo.*,
        (SELECT COUNT(*) FROM obunalar
         WHERE foydalanuvchi_id = fo.id
           AND holat = 'faol'
           AND tugash > NOW()
        ) AS obuna_faol,
        (SELECT COUNT(*) FROM natijalar
         WHERE foydalanuvchi_id = fo.id
           AND holat = 'tugagan'
        ) AS test_son
     FROM foydalanuvchilar fo
     {$where}
     ORDER BY fo.id DESC
     LIMIT {$limit} OFFSET {$offset}",
    $params
);

$tariflar = db_barcha(
    'SELECT id, nomi, narx FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx'
);

$admin_sahifa    = 'foydalanuvchilar';
$sahifa_sarlavha = 'Foydalanuvchilar';
require_once __DIR__ . '/_layout.php';
?>

<!-- ── Filtrlar ────────────────────────────────────────── -->
<form method="GET"
      class="glass-card p-4 mb-5 grid sm:grid-cols-12 gap-3 items-end fade-up">
    <div class="sm:col-span-7">
        <label class="field-label">Qidiruv</label>
        <input name="q" value="<?= e($qidiruv) ?>"
               placeholder="Ism, familiya yoki telefon..."
               class="field">
    </div>
    <div class="sm:col-span-3">
        <label class="field-label">Rol</label>
        <select name="rol" onchange="this.form.submit()" class="field">
            <option value="">Barchasi</option>
            <?php foreach (['user'=>'User','admin'=>'Admin','developer'=>'Developer'] as $v=>$n): ?>
            <option value="<?= $v ?>" <?= $rol_filt === $v ? 'selected' : '' ?>>
                <?= $n ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="sm:col-span-2">
        <button type="submit" class="btn btn-primary w-full btn-sm">
            🔍 Qidirish
        </button>
    </div>
</form>

<!-- ── Jadval ──────────────────────────────────────────── -->
<div class="glass-card overflow-hidden fade-up">

    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-3.5
                border-b border-white/[0.06]">
        <div>
            <span class="font-display font-semibold text-sm">Jami: </span>
            <span class="font-bold tabnum"><?= $jami ?></span>
            <?php if ($qidiruv || $rol_filt): ?>
            <span class="text-xs text-white/40 ml-2">— filtr aktiv</span>
            <?php endif; ?>
        </div>
        <?php if ($qidiruv || $rol_filt): ?>
        <a href="<?= e(SAYT_URL) ?>/admin/foydalanuvchilar.php"
           class="badge badge-gray cursor-pointer hover:opacity-80">✕ Tozalash</a>
        <?php endif; ?>
    </div>

    <?php if (empty($royxat)): ?>
    <div class="py-16 text-center text-brand-muted">
        <div class="text-4xl mb-3">👥</div>
        <p><?= e(t('malumot_yoq')) ?></p>
    </div>
    <?php else: ?>

    <!-- Desktop jadval -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[700px]">
            <thead>
                <tr class="border-b border-white/[0.06] text-xs text-brand-muted uppercase tracking-wide">
                    <th class="py-3 px-5 text-left font-medium">Foydalanuvchi</th>
                    <th class="py-3 px-4 text-left font-medium">Telefon</th>
                    <th class="py-3 px-4 text-left font-medium">Rol</th>
                    <th class="py-3 px-4 text-left font-medium">Obuna</th>
                    <th class="py-3 px-4 text-left font-medium">Testlar</th>
                    <th class="py-3 px-4 text-left font-medium">Bonus</th>
                    <th class="py-3 px-4 text-left font-medium">Holat</th>
                    <th class="py-3 px-4 text-left font-medium">Sana</th>
                    <th class="py-3 px-5 text-right font-medium">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($royxat as $u):
                    // N+1 yo'q — obuna_faol subquery orqali keldi
                    $has_obuna = (int)$u['obuna_faol'] > 0;
                ?>
                <tr class="border-b border-white/[0.04] hover:bg-white/[0.02] transition group"
                    x-data="{ menu: false }">

                    <!-- Foydalanuvchi -->
                    <td class="py-3 px-5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-violet-600
                                        flex items-center justify-center font-bold text-white text-xs
                                        flex-shrink-0">
                                <?= e(mb_strtoupper(mb_substr($u['ism'],0,1))) ?>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-sm text-white truncate max-w-[130px]">
                                    <?= e($u['ism']) ?> <?= e($u['familiya'] ?? '') ?>
                                </p>
                                <?php if ($has_obuna): ?>
                                <span class="text-[10px] text-amber-400 font-medium">⭐ Faol obuna</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>

                    <!-- Telefon -->
                    <td class="py-3 px-4 font-mono text-xs text-brand-muted">
                        <?= e($u['telefon']) ?>
                    </td>

                    <!-- Rol -->
                    <td class="py-3 px-4">
                        <span class="badge <?= match($u['rol']) {
                            'developer' => 'badge-purple',
                            'admin'     => 'badge-blue',
                            default     => 'badge-gray'
                        } ?>">
                            <?= e($u['rol']) ?>
                        </span>
                    </td>

                    <!-- Obuna -->
                    <td class="py-3 px-4">
                        <?php if ($has_obuna): ?>
                        <span class="badge badge-green">Faol</span>
                        <?php else: ?>
                        <span class="text-xs text-white/25">—</span>
                        <?php endif; ?>
                    </td>

                    <!-- Testlar soni -->
                    <td class="py-3 px-4 tabnum text-sm font-medium">
                        <?= (int)$u['test_son'] ?>
                    </td>

                    <!-- Bonus -->
                    <td class="py-3 px-4 tabnum text-sm">
                        <?php if ((float)$u['bonus_balans'] > 0): ?>
                        <span class="text-emerald-400 font-semibold">
                            <?= e(pul($u['bonus_balans'])) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-white/25">—</span>
                        <?php endif; ?>
                    </td>

                    <!-- Holat -->
                    <td class="py-3 px-4">
                        <span class="badge <?= $u['holat'] === 'faol' ? 'badge-green' : 'badge-red' ?>">
                            <?= e($u['holat']) ?>
                        </span>
                    </td>

                    <!-- Sana -->
                    <td class="py-3 px-4 text-xs text-brand-muted tabnum">
                        <?= e(sana($u['yaratilgan'], 'd.m.Y')) ?>
                    </td>

                    <!-- Amallar -->
                    <td class="py-3 px-5 text-right relative">
                        <button @click="menu = !menu; $nextTick(() => {})"
                                class="w-8 h-8 rounded-lg bg-white/[0.05] hover:bg-white/10
                                       flex items-center justify-center ml-auto
                                       text-brand-muted hover:text-white transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/>
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="menu" @click.outside="menu = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             class="absolute right-4 top-full mt-1 z-30 w-64 glass-card p-2
                                    text-left shadow-2xl shadow-black/50"
                             x-cloak>

                            <!-- Foydalanuvchi info -->
                            <div class="px-3 py-2 border-b border-white/[0.07] mb-1">
                                <p class="text-xs font-semibold text-white truncate">
                                    <?= e($u['ism']) ?> <?= e($u['familiya'] ?? '') ?>
                                </p>
                                <p class="text-[10px] text-brand-muted font-mono">
                                    <?= e($u['telefon']) ?>
                                </p>
                            </div>

                            <!-- Obuna berish -->
                            <details class="group/det">
                                <summary class="flex items-center gap-2 px-3 py-2 rounded-lg
                                                hover:bg-white/[0.05] cursor-pointer text-sm
                                                text-white/70 hover:text-white list-none transition">
                                    <span>🎁</span>
                                    <span class="flex-1">Obuna berish</span>
                                    <svg class="w-3.5 h-3.5 text-white/30 transition-transform group-open/det:rotate-180"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </summary>
                                <form method="POST" class="mt-1 px-2 pb-2 space-y-2">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="obuna">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <select name="tarif_id" required class="field text-xs">
                                        <option value="">— tarif tanlang —</option>
                                        <?php foreach ($tariflar as $tar): ?>
                                        <option value="<?= (int)$tar['id'] ?>">
                                            <?= e($tar['nomi']) ?>
                                            (<?= e(pul($tar['narx'])) ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary w-full btn-xs">
                                        Berish
                                    </button>
                                </form>
                            </details>

                            <!-- Bonus -->
                            <details class="group/det2">
                                <summary class="flex items-center gap-2 px-3 py-2 rounded-lg
                                                hover:bg-white/[0.05] cursor-pointer text-sm
                                                text-white/70 hover:text-white list-none transition">
                                    <span>💰</span>
                                    <span class="flex-1">Bonus qo'shish</span>
                                    <svg class="w-3.5 h-3.5 text-white/30 transition-transform group-open/det2:rotate-180"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </summary>
                                <form method="POST" class="mt-1 px-2 pb-2 space-y-2">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="bonus">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <input type="number" name="bonus" required
                                           step="1000" min="0" placeholder="5000"
                                           class="field text-xs">
                                    <button class="btn btn-success w-full btn-xs">
                                        Qo'shish
                                    </button>
                                </form>
                            </details>

                            <div class="border-t border-white/[0.06] my-1"></div>

                            <!-- Block / Faollashtirish -->
                            <form method="POST">
                                <?= csrf_input() ?>
                                <input type="hidden" name="harakat" value="block">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <button class="w-full flex items-center gap-2 px-3 py-2 rounded-lg
                                               text-sm transition
                                               <?= $u['holat'] === 'faol'
                                                    ? 'text-red-400/80 hover:text-red-400 hover:bg-red-500/[0.07]'
                                                    : 'text-emerald-400/80 hover:text-emerald-400 hover:bg-emerald-500/[0.07]' ?>">
                                    <span><?= $u['holat'] === 'faol' ? '🚫' : '✓' ?></span>
                                    <?= $u['holat'] === 'faol' ? 'Bloklash' : 'Faollashtirish' ?>
                                </button>
                            </form>

                            <!-- Rol o'zgartirish (faqat developer) -->
                            <?php if ($f['rol'] === 'developer' && $u['id'] !== $f['id']): ?>
                            <details class="group/det3">
                                <summary class="flex items-center gap-2 px-3 py-2 rounded-lg
                                                hover:bg-white/[0.05] cursor-pointer text-sm
                                                text-violet-400/70 hover:text-violet-400 list-none transition">
                                    <span>🔧</span>
                                    <span class="flex-1">Rol o'zgartirish</span>
                                </summary>
                                <form method="POST" class="mt-1 px-2 pb-2 space-y-2">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="rol">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <select name="rol" class="field text-xs">
                                        <?php foreach (['user','admin','developer'] as $r): ?>
                                        <option value="<?= $r ?>"
                                                <?= $u['rol'] === $r ? 'selected' : '' ?>>
                                            <?= ucfirst($r) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary w-full btn-xs">
                                        Saqlash
                                    </button>
                                </form>
                            </details>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Sahifalash -->
    <?php $jami_sah = (int) ceil($jami / $limit); if ($jami_sah > 1): ?>
    <div class="flex items-center justify-between px-5 py-3.5 border-t border-white/[0.06]">
        <p class="text-xs text-brand-muted">
            <?= ($offset + 1) ?>–<?= min($offset + $limit, $jami) ?> / <?= $jami ?>
        </p>
        <div class="flex flex-wrap gap-1">
            <?php
            // Aqlli sahifalash: birinchi, oxirgi va joriy atrofidagi sahifalar
            $show = [];
            for ($i = 1; $i <= $jami_sah; $i++) {
                if ($i === 1 || $i === $jami_sah || abs($i - $sahifa) <= 2) {
                    $show[] = $i;
                }
            }
            $prev = 0;
            foreach ($show as $i):
                if ($prev && $i - $prev > 1): ?>
                <span class="w-8 h-8 flex items-center justify-center text-brand-muted text-sm">…</span>
                <?php endif; ?>
                <a href="?p=<?= $i ?>&q=<?= urlencode($qidiruv) ?>&rol=<?= urlencode($rol_filt) ?>"
                   class="w-8 h-8 rounded-lg text-sm flex items-center justify-center tabnum transition
                       <?= $i === $sahifa
                           ? 'bg-blue-500 text-white font-bold'
                           : 'bg-white/[0.05] text-brand-muted hover:bg-white/10 hover:text-white' ?>">
                    <?= $i ?>
                </a>
            <?php $prev = $i; endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
