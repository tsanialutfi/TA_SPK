<?php
require_once 'koneksi.php';

$pesan = '';
$hasil_ahp = null;

// Nilai skala Saaty yang diperbolehkan
$skala_valid = [1, 2, 3, 4, 5, 6, 7, 8, 9, 1/2, 1/3, 1/4, 1/5, 1/6, 1/7, 1/8, 1/9];

// Ambil bobot terakhir yang tersimpan
$bobot_tersimpan = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT * FROM bobot_ahp ORDER BY id DESC LIMIT 1"));

// PROSES HITUNG AHP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $n = 4; // jumlah kriteria

    // Ambil input matriks (hanya segitiga atas)
    $input = [
        [1,      (float)$_POST['m12'], (float)$_POST['m13'], (float)$_POST['m14']],
        [0,      1,                    (float)$_POST['m23'], (float)$_POST['m24']],
        [0,      0,                    1,                    (float)$_POST['m34']],
        [0,      0,                    0,                    1],
    ];

    // Lengkapi matriks (segitiga bawah = kebalikan)
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            if ($i > $j) {
                $input[$i][$j] = $input[$j][$i] > 0 ? 1 / $input[$j][$i] : 1;
            }
        }
    }

    // LANGKAH 1: Hitung jumlah kolom
    $col_sum = [];
    for ($j = 0; $j < $n; $j++) {
        $col_sum[$j] = 0;
        for ($i = 0; $i < $n; $i++) {
            $col_sum[$j] += $input[$i][$j];
        }
    }

    // LANGKAH 2: Normalisasi matriks
    $norm = [];
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            $norm[$i][$j] = $col_sum[$j] > 0 ? $input[$i][$j] / $col_sum[$j] : 0;
        }
    }

    // LANGKAH 3: Hitung bobot (rata-rata baris)
    $bobot = [];
    for ($i = 0; $i < $n; $i++) {
        $bobot[$i] = array_sum($norm[$i]) / $n;
    }

    // LANGKAH 4: Hitung Aw
    $Aw = [];
    for ($i = 0; $i < $n; $i++) {
        $Aw[$i] = 0;
        for ($j = 0; $j < $n; $j++) {
            $Aw[$i] += $input[$i][$j] * $bobot[$j];
        }
    }

    // LANGKAH 5: Hitung lambda
    $lambda = [];
    for ($i = 0; $i < $n; $i++) {
        $lambda[$i] = $bobot[$i] > 0 ? $Aw[$i] / $bobot[$i] : 0;
    }
    $lambda_max = array_sum($lambda) / $n;

    // LANGKAH 6: CI dan CR
    $CI = ($lambda_max - $n) / ($n - 1);
    $RI = 0.90; // RI untuk n=4
    $CR = $RI > 0 ? $CI / $RI : 0;
    $status = $CR < 0.1 ? 'Konsisten' : 'Tidak Konsisten';

    $hasil_ahp = [
        'matriks'     => $input,
        'col_sum'     => $col_sum,
        'norm'        => $norm,
        'bobot'       => $bobot,
        'Aw'          => $Aw,
        'lambda'      => $lambda,
        'lambda_max'  => $lambda_max,
        'CI'          => $CI,
        'RI'          => $RI,
        'CR'          => $CR,
        'status'      => $status,
    ];

    // Simpan ke database kalau konsisten
    if ($status === 'Konsisten') {
        mysqli_query($koneksi, "DELETE FROM bobot_ahp");
        $sql = "INSERT INTO bobot_ahp (c1, c2, c3, c4, lambda_max, ci, cr, status)
                VALUES ({$bobot[0]}, {$bobot[1]}, {$bobot[2]}, {$bobot[3]},
                        $lambda_max, $CI, $CR, '$status')";
        mysqli_query($koneksi, $sql);
        $bobot_tersimpan = mysqli_fetch_assoc(mysqli_query($koneksi,
            "SELECT * FROM bobot_ahp ORDER BY id DESC LIMIT 1"));
        $pesan = '<div class="alert alert-success">Bobot AHP berhasil dihitung dan disimpan! CR = ' . round($CR, 4) . ' (Konsisten) ✅</div>';
    } else {
        $pesan = '<div class="alert alert-danger">Matriks tidak konsisten! CR = ' . round($CR, 4) . ' ≥ 0.1. Silakan isi ulang nilai perbandingan.</div>';
    }
}

$kriteria = ['C1: Nilai Rata-rata SMP', 'C2: Jumlah Sertifikat', 'C3: Tingkat Sertifikat', 'C4: Nilai Mapel Terkait'];
$kshort   = ['C1', 'C2', 'C3', 'C4'];

// Default nilai matriks (dari AHP Modul 3)
$default = [
    'm12' => 3, 'm13' => 3, 'm14' => 1,
    'm23' => 1, 'm24' => '1/3',
    'm34' => '1/3'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK Olimpiade - Hitung AHP</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .matrix-table th, .matrix-table td {
            text-align: center;
            padding: 10px 14px;
            font-size: 13px;
        }
        .matrix-table th { background: #1A3A5C; color: white; }
        .matrix-table td { border: 1px solid #eee; }
        .matrix-table input[type=text] {
            width: 70px; text-align: center;
            padding: 6px; border: 1px solid #ccc;
            border-radius: 4px; font-size: 13px;
        }
        .matrix-table input:focus { border-color: #1A3A5C; outline: none; }
        .diag { background: #f0f4f8; color: #888; font-weight: bold; }
        .recip { background: #f8f9ff; color: #1A3A5C; font-size: 12px; }
        .step-box {
            background: #f8f9ff;
            border-left: 4px solid #1A3A5C;
            padding: 14px 18px;
            margin-bottom: 16px;
            border-radius: 0 6px 6px 0;
        }
        .step-box h4 { color: #1A3A5C; margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>

<div class="navbar">
    <a class="brand" href="index.php">SPK Olimpiade SMAN 3 Malang</a>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="siswa.php">Data Siswa</a>
        <a href="ahp.php" class="active">Hitung AHP</a>
        <a href="hitung.php">Hitung SAW & TOPSIS</a>
        <a href="hasil.php">Hasil & Peringkat</a>
    </nav>
</div>

<div class="container">
    <div class="page-title">Hitung AHP</div>
    <div class="page-sub">Perhitungan bobot kriteria menggunakan Analytic Hierarchy Process</div>

    <?= $pesan ?>

    <!-- STATUS BOBOT TERSIMPAN -->
    <?php if ($bobot_tersimpan): ?>
    <div class="alert alert-success">
        <b>Bobot aktif saat ini:</b>
        C1 = <?= round($bobot_tersimpan['c1']*100,1) ?>% &nbsp;|&nbsp;
        C2 = <?= round($bobot_tersimpan['c2']*100,1) ?>% &nbsp;|&nbsp;
        C3 = <?= round($bobot_tersimpan['c3']*100,1) ?>% &nbsp;|&nbsp;
        C4 = <?= round($bobot_tersimpan['c4']*100,1) ?>% &nbsp;|&nbsp;
        CR = <?= round($bobot_tersimpan['cr'],4) ?> ✅
        &nbsp;&nbsp;<a href="hitung.php" style="color:#1A3A5C;font-weight:bold;">Lanjut Hitung SAW & TOPSIS →</a>
    </div>
    <?php else: ?>
    <div class="alert alert-danger">Bobot AHP belum dihitung. Isi matriks di bawah terlebih dahulu.</div>
    <?php endif; ?>

    <!-- INFO SKALA SAATY -->
    <div class="section-box">
        <h3>Panduan Skala Saaty</h3>
        <table>
            <tr>
                <th>Nilai</th><th>Keterangan</th>
                <th>Nilai</th><th>Keterangan</th>
            </tr>
            <tr>
                <td><b>1</b></td><td>Sama penting</td>
                <td><b>1/3</b></td><td>Sedikit kurang penting</td>
            </tr>
            <tr>
                <td><b>3</b></td><td>Sedikit lebih penting</td>
                <td><b>1/5</b></td><td>Kurang penting</td>
            </tr>
            <tr>
                <td><b>5</b></td><td>Lebih penting</td>
                <td><b>1/7</b></td><td>Sangat kurang penting</td>
            </tr>
            <tr>
                <td><b>7</b></td><td>Sangat lebih penting</td>
                <td><b>1/9</b></td><td>Mutlak kurang penting</td>
            </tr>
            <tr>
                <td><b>9</b></td><td>Mutlak lebih penting</td>
                <td><b>2,4,6,8</b></td><td>Nilai antara</td>
            </tr>
        </table>
    </div>

    <!-- FORM MATRIKS -->
    <div class="form-wrap">
        <h3 style="font-size:15px;color:#1A3A5C;margin-bottom:6px;padding-bottom:10px;border-bottom:2px solid #f0f0f0;">
            Isi Matriks Perbandingan Berpasangan
        </h3>
        <p style="font-size:12px;color:#777;margin-bottom:18px;">
            Isi nilai perbandingan pada sel berwarna putih (segitiga atas). Nilai kebalikan akan otomatis dihitung.
            Gunakan angka bulat (1,3,5,7,9) atau pecahan (1/3, 1/5, 1/7, 1/9).
        </p>
        <form method="POST">
            <table class="matrix-table" style="margin-bottom:20px;">
                <tr>
                    <th></th>
                    <?php foreach ($kshort as $k): ?>
                    <th><?= $k ?></th>
                    <?php endforeach; ?>
                </tr>
                <?php
                $fields = [
                    [null,   'm12',  'm13',  'm14'],
                    ['m12',  null,   'm23',  'm24'],
                    ['m13',  'm23',  null,   'm34'],
                    ['m14',  'm24',  'm34',  null ],
                ];
                foreach ($fields as $i => $row):
                ?>
                <tr>
                    <th style="background:#1A3A5C;color:white;text-align:left;padding:10px 14px;">
                        <?= $kshort[$i] ?>
                    </th>
                    <?php foreach ($row as $j => $field): ?>
                    <td <?= $i == $j ? 'class="diag"' : '' ?>>
                        <?php if ($i == $j): ?>
                            <b>1</b>
                        <?php elseif ($i < $j): ?>
                            <input type="text" name="<?= $field ?>"
                                value="<?= $_POST[$field] ?? $default[$field] ?? 1 ?>"
                                placeholder="1">
                        <?php else: ?>
                            <span class="recip" id="recip_<?= $i.$j ?>">1/<?= $default[$fields[$j][$i]] ?? 1 ?></span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </table>
            <button type="submit" class="btn btn-primary" style="font-size:14px;padding:10px 28px;">
                Hitung Bobot AHP
            </button>
        </form>
    </div>

    <!-- HASIL PERHITUNGAN -->
    <?php if ($hasil_ahp): ?>

    <!-- STEP 1: Matriks Awal -->
    <div class="section-box">
        <h3>Hasil Perhitungan AHP</h3>

        <div class="step-box">
            <h4>Langkah 1 — Matriks Perbandingan Berpasangan & Jumlah Kolom</h4>
            <table class="matrix-table">
                <tr>
                    <th></th>
                    <?php foreach ($kshort as $k): ?><th><?= $k ?></th><?php endforeach; ?>
                </tr>
                <?php foreach ($hasil_ahp['matriks'] as $i => $row): ?>
                <tr>
                    <th style="background:#1A3A5C;color:white;"><?= $kshort[$i] ?></th>
                    <?php foreach ($row as $j => $val): ?>
                    <td <?= $i==$j ? 'class="diag"' : '' ?>>
                        <?php
                        if ($val == 1) echo '1';
                        elseif ($val >= 1) echo round($val, 4);
                        else echo '1/' . round(1/$val);
                        ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#EBF5FB;">
                    <th style="background:#2E86C1;color:white;">Jumlah</th>
                    <?php foreach ($hasil_ahp['col_sum'] as $cs): ?>
                    <td><b><?= round($cs,4) ?></b></td>
                    <?php endforeach; ?>
                </tr>
            </table>
        </div>

        <!-- STEP 2: Normalisasi -->
        <div class="step-box">
            <h4>Langkah 2 — Matriks Normalisasi & Bobot (Priority Vector)</h4>
            <table class="matrix-table">
                <tr>
                    <th></th>
                    <?php foreach ($kshort as $k): ?><th><?= $k ?></th><?php endforeach; ?>
                    <th style="background:#1E8449;">Bobot</th>
                    <th style="background:#1E8449;">Persentase</th>
                </tr>
                <?php foreach ($hasil_ahp['norm'] as $i => $row): ?>
                <tr>
                    <th style="background:#1A3A5C;color:white;"><?= $kshort[$i] ?></th>
                    <?php foreach ($row as $val): ?>
                    <td><?= round($val,4) ?></td>
                    <?php endforeach; ?>
                    <td style="background:#D5F5E3;color:#1E8449;font-weight:bold;">
                        <?= round($hasil_ahp['bobot'][$i],4) ?>
                    </td>
                    <td style="background:#D5F5E3;color:#1E8449;font-weight:bold;">
                        <?= round($hasil_ahp['bobot'][$i]*100,2) ?>%
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- STEP 3: Konsistensi -->
        <div class="step-box">
            <h4>Langkah 3 — Uji Konsistensi</h4>
            <table>
                <tr><th>Kriteria</th><th>Aw</th><th>Bobot (w)</th><th>λ = Aw ÷ w</th></tr>
                <?php foreach ($kshort as $i => $k): ?>
                <tr>
                    <td><b><?= $k ?></b> — <?= $kriteria[$i] ?></td>
                    <td><?= round($hasil_ahp['Aw'][$i],4) ?></td>
                    <td><?= round($hasil_ahp['bobot'][$i],4) ?></td>
                    <td><b><?= round($hasil_ahp['lambda'][$i],4) ?></b></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div style="margin-top:16px;display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                <div class="card" style="padding:14px;">
                    <div class="card-number" style="font-size:22px;"><?= round($hasil_ahp['lambda_max'],4) ?></div>
                    <div class="card-label">λ max</div>
                </div>
                <div class="card" style="padding:14px;">
                    <div class="card-number" style="font-size:22px;"><?= round($hasil_ahp['CI'],4) ?></div>
                    <div class="card-label">CI</div>
                </div>
                <div class="card" style="padding:14px;">
                    <div class="card-number" style="font-size:22px;"><?= $hasil_ahp['RI'] ?></div>
                    <div class="card-label">RI (n=4)</div>
                </div>
                <div class="card <?= $hasil_ahp['status']=='Konsisten' ? 'green' : 'red' ?>" style="padding:14px;">
                    <div class="card-number" style="font-size:22px;"><?= round($hasil_ahp['CR'],4) ?></div>
                    <div class="card-label">CR — <?= $hasil_ahp['status'] ?></div>
                </div>
            </div>

            <?php if ($hasil_ahp['status'] == 'Konsisten'): ?>
            <div class="alert alert-success" style="margin-top:14px;">
                ✅ CR = <?= round($hasil_ahp['CR'],4) ?> &lt; 0.1 → Matriks <b>KONSISTEN</b>.
                Bobot telah disimpan dan siap digunakan untuk perhitungan SAW & TOPSIS.
                <a href="hitung.php" class="btn btn-success btn-sm" style="margin-left:12px;">Lanjut Hitung SAW & TOPSIS →</a>
            </div>
            <?php else: ?>
            <div class="alert alert-danger" style="margin-top:14px;">
                ❌ CR = <?= round($hasil_ahp['CR'],4) ?> ≥ 0.1 → Matriks <b>TIDAK KONSISTEN</b>.
                Silakan perbaiki nilai perbandingan di atas.
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Update label kebalikan secara real-time
const pairs = {
    'm12': 'recip_10', 'm13': 'recip_20', 'm14': 'recip_30',
    'm23': 'recip_21', 'm24': 'recip_31', 'm34': 'recip_32'
};
Object.keys(pairs).forEach(function(name) {
    var input = document.querySelector('input[name="' + name + '"]');
    var recip = document.getElementById(pairs[name]);
    if (input && recip) {
        input.addEventListener('input', function() {
            var val = this.value.trim();
            if (val.includes('/')) {
                var parts = val.split('/');
                var num = parseFloat(parts[0]);
                var den = parseFloat(parts[1]);
                if (!isNaN(num) && !isNaN(den) && den !== 0) {
                    recip.textContent = den + '/' + num;
                }
            } else {
                var n = parseFloat(val);
                if (!isNaN(n) && n > 0) {
                    recip.textContent = n === 1 ? '1' : '1/' + n;
                }
            }
        });
    }
});
</script>
</body>
</html>
