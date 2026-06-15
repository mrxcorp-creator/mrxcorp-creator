<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

$xato = '';
$natija = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } else {
        $bilet_id = (int) post('bilet_id');
        $bilet = $bilet_id ? db_qator('SELECT * FROM biletlar WHERE id = ?', [$bilet_id]) : null;
        if (!$bilet) {
            $xato = 'Bilet tanlang';
        } elseif (empty($_FILES['fayl']['tmp_name'])) {
            $xato = 'Fayl yuklanmadi';
        } else {
            $fayl = $_FILES['fayl']['tmp_name'];
            $kengaytma = strtolower(pathinfo($_FILES['fayl']['name'], PATHINFO_EXTENSION));

            $qatorlar = [];
            try {
                if ($kengaytma === 'json') {
                    $j = json_decode(file_get_contents($fayl), true);
                    if (!is_array($j)) {
                        throw new Exception('JSON noto\'g\'ri formatda');
                    }
                    $qatorlar = $j;
                } elseif ($kengaytma === 'csv') {
                    $fp = fopen($fayl, 'r');
                    $sarlavha = fgetcsv($fp);
                    if (!$sarlavha) {
                        throw new Exception('Sarlavha qatori yo\'q');
                    }
                    $sarlavha = array_map('trim', array_map('mb_strtolower', $sarlavha));

                    while (($r = fgetcsv($fp)) !== false) {
                        $row = [];
                        foreach ($sarlavha as $i => $kalit) {
                            $row[$kalit] = $r[$i] ?? '';
                        }
                        $qatorlar[] = $row;
                    }
                    fclose($fp);
                } else {
                    throw new Exception('Faqat .csv yoki .json qabul qilinadi');
                }

                $tugagan = 0;
                $oraliq = 0;
                $xatolar = [];

                db()->beginTransaction();
                foreach ($qatorlar as $i => $r) {
                    $matn = trim($r['matn'] ?? '');
                    $a = trim($r['variant_a'] ?? $r['a'] ?? '');
                    $b = trim($r['variant_b'] ?? $r['b'] ?? '');
                    $c = trim($r['variant_c'] ?? $r['c'] ?? '');
                    $d = trim($r['variant_d'] ?? $r['d'] ?? '');
                    $togri = strtolower(trim($r['togri'] ?? $r['togri_javob'] ?? ''));
                    $izoh = trim($r['izoh'] ?? '');

                    if (!$matn || !$a || !$b || !in_array($togri, ['a', 'b', 'c', 'd'], true)) {
                        $oraliq++;
                        $xatolar[] = ($i + 1) . "-qator: ma'lumot to'liq emas";
                        continue;
                    }

                    db_bajar(
                        'INSERT INTO savollar (bilet_id, matn, variant_a, variant_b, variant_c, variant_d, togri_javob, izoh, tartib)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [$bilet_id, $matn, $a, $b, $c, $d, $togri, $izoh ?: null, $i]
                    );
                    $tugagan++;
                }
                db()->commit();

                audit_yoz('savollar_import', 'savol', null, [
                    'bilet_id' => $bilet_id,
                    'tugagan' => $tugagan,
                    'xato' => $oraliq,
                ]);

                $natija = [
                    'tugagan' => $tugagan,
                    'oraliq' => $oraliq,
                    'xatolar' => array_slice($xatolar, 0, 20),
                    'bilet_nomi' => $bilet['nomi'],
                ];
            } catch (Throwable $e) {
                if (db()->inTransaction()) db()->rollBack();
                $xato = $e->getMessage();
            }
        }
    }
}

$biletlar = db_barcha('SELECT id, raqam, nomi FROM biletlar ORDER BY raqam');

$admin_sahifa = 'savollar_import';
$sahifa_sarlavha = 'Savollarni import qilish';
require_once __DIR__ . '/_layout.php';
?>

<?php if ($natija): ?>
    <div class="ring-grad mb-6 fade-up">
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-14 h-14 rounded-2xl bg-success/15 text-success flex items-center justify-center text-3xl flex-shrink-0">✓</div>
                <div>
                    <h2 class="text-xl font-display font-bold mb-1">Import yakunlandi</h2>
                    <p class="text-sm text-muted">Bilet: <strong class="text-text"><?= e($natija['bilet_nomi']) ?></strong></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="p-4 rounded-xl bg-success/5 border border-success/20">
                    <div class="text-xs text-muted uppercase mb-1">Muvaffaqiyatli</div>
                    <div class="text-3xl font-display font-extrabold text-success"><?= $natija['tugagan'] ?></div>
                </div>
                <div class="p-4 rounded-xl bg-amber/5 border border-amber/20">
                    <div class="text-xs text-muted uppercase mb-1">O'tkazib yuborildi</div>
                    <div class="text-3xl font-display font-extrabold text-amber"><?= $natija['oraliq'] ?></div>
                </div>
            </div>

            <?php if (!empty($natija['xatolar'])): ?>
                <details class="text-sm">
                    <summary class="cursor-pointer text-muted hover:text-text"><?= count($natija['xatolar']) ?> ta xato</summary>
                    <ul class="mt-2 space-y-1 text-xs">
                        <?php foreach ($natija['xatolar'] as $x): ?>
                            <li class="text-amber">• <?= e($x) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($xato): ?>
    <div class="mb-5 p-4 rounded-xl bg-danger/10 border border-danger/30 text-danger text-sm">
        <strong>Xato:</strong> <?= e($xato) ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="glass p-6 mb-6 fade-up">
    <?= csrf_input() ?>

    <h2 class="text-xl font-display font-bold mb-4">📥 Savollarni ommaviy import qilish</h2>

    <div class="grid sm:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="field-label">Bilet *</label>
            <select name="bilet_id" required class="field">
                <option value="">— tanlang —</option>
                <?php foreach ($biletlar as $b): ?>
                    <option value="<?= (int) $b['id'] ?>">№<?= (int) $b['raqam'] ?> — <?= e($b['nomi']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="field-label">Fayl (.csv yoki .json) *</label>
            <input type="file" name="fayl" accept=".csv,.json" required
                   class="field file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-violet/20 file:text-violet">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">📤 Import qilish</button>
</form>

<div class="grid md:grid-cols-2 gap-4">
    <div class="glass p-5 fade-up">
        <h3 class="font-display font-bold mb-3 flex items-center gap-2">
            <span>📄</span> CSV format
        </h3>
        <p class="text-sm text-muted mb-3">Birinchi qator — sarlavha. Maydonlar:</p>
        <code class="block text-xs bg-black/30 p-3 rounded-lg overflow-x-auto whitespace-pre">matn,variant_a,variant_b,variant_c,variant_d,togri,izoh
"Savol matni","Variant A","Variant B","Variant C","Variant D",a,"Izoh matni"
"Boshqa savol","A","B","C","D",b,""</code>
        <p class="text-xs text-muted mt-3">
            ✓ <strong>matn</strong>, <strong>variant_a</strong>, <strong>variant_b</strong>, <strong>togri</strong> majburiy<br>
            ✓ <strong>variant_c, variant_d, izoh</strong> ixtiyoriy<br>
            ✓ togri = a/b/c/d
        </p>
    </div>

    <div class="glass p-5 fade-up">
        <h3 class="font-display font-bold mb-3 flex items-center gap-2">
            <span>🔧</span> JSON format
        </h3>
        <code class="block text-xs bg-black/30 p-3 rounded-lg overflow-x-auto whitespace-pre">[
  {
    "matn": "Savol matni",
    "variant_a": "Variant A",
    "variant_b": "Variant B",
    "variant_c": "Variant C",
    "variant_d": "Variant D",
    "togri": "a",
    "izoh": "Izoh"
  },
  {...}
]</code>
        <p class="text-xs text-muted mt-3">UTF-8 kodlash, JSON_UNESCAPED_UNICODE</p>
    </div>
</div>

<details class="glass p-5 mt-6 fade-up">
    <summary class="cursor-pointer font-display font-bold">💡 Maslahatlar</summary>
    <ul class="mt-3 space-y-2 text-sm text-muted list-disc pl-5">
        <li>Excel'dan eksport qilganda <strong>UTF-8</strong> kodlashda saqlang (LibreOffice yoki "Save As CSV UTF-8")</li>
        <li>Matnda vergul bo'lsa, qo'shtirnoq ichida bo'lsin: <code>"Bu, savol matni"</code></li>
        <li>Qo'shtirnoq ichidagi qo'shtirnoq ikki marta yoziladi: <code>"U ""salom"" dedi"</code></li>
        <li>Bir martada 100-500 ta savol import qilish tavsiya etiladi</li>
        <li>Xato qator <strong>o'tkazib yuboriladi</strong>, qolganlari saqlanadi</li>
    </ul>
</details>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
