<?php
require_once 'koneksi.php';

$tingkat_map = ['Nasional' => 8, 'Provinsi' => 9, 'Kabupaten/Kota' => 10, 'Sekolah' => 11];

$pesan = '';
$hasil_saw    = [];
$hasil_topsis = [];

// Ambil bobot dari database AHP
$bobot_db = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT * FROM bobot_ahp ORDER BY id DESC LIMIT 1"));

// Ambil data siswa
$query = mysqli_query($koneksi, "SELECT * FROM siswa ORDER BY id ASC");
$siswa_all = [];
while ($row = mysqli_fetch_assoc($query)) {
    $row['c3_num'] = $tingkat_map[$row['tingkat']] ?? 10;
    $siswa_all[] = $row;
}
$n = count($siswa_all);

// PROSES HITUNG
if (isset($_POST['hitung']) && $n >= 3 && $bobot_db) {

    $bobot = [
        'c1' => (float)$bobot_db['c1'],
        'c2' => (float)$bobot_db['c2'],
        'c3' => (float)$bobot_db['c3'],
        'c4' => (float)$bobot_db['c4'],
    ];

    // ── SAW ──────────────────────────────────────────────
    $max_c1 = max(array_column($siswa_all, 'nilai_smp'));
    $max_c2 = max(array_column($siswa_all, 'jumlah_sertifikat'));
    $min_c3 = min(array_column($siswa_all, 'c3_num'));
    $max_c4 = max(array_column($siswa_all, 'nilai_mapel'));

    foreach ($siswa_all as $s) {
        $n1 = $max_c1 > 0 ? $s['nilai_smp'] / $max_c1 : 0;
        $n2 = $max_c2 > 0 ? $s['jumlah_sertifikat'] / $max_c2 : 0;
        $n3 = $s['c3_num'] > 0 ? $min_c3 / $s['c3_num'] : 0;
        $n4 = $max_c4 > 0 ? $s['nilai_mapel'] / $max_c4 : 0;
        $skor = ($bobot['c1']*$n1) + ($bobot['c2']*$n2) + ($bobot['c3']*$n3) + ($bobot['c4']*$n4);
        $hasil_saw[] = [
            'id' => $s['id'], 'nama' => $s['nama'],
            'skor' => round($skor,4),
            'n1'=>round($n1,4),'n2'=>round($n2,4),
            'n3'=>round($n3,4),'n4'=>round($n4,4)
        ];
    }
    usort($hasil_saw, fn($a,$b) => $b['skor'] <=> $a['skor']);
    foreach ($hasil_saw as $i => &$h) {
        $h['peringkat'] = $i + 1;
        $h['rekomendasi'] = $h['skor'] >= 0.85 ? 'Sangat Layak'
            : ($h['skor'] >= 0.75 ? 'Layak'
            : ($h['skor'] >= 0.65 ? 'Pertimbangkan' : 'Belum Layak'));
    }
    unset($h);

    // ── TOPSIS ───────────────────────────────────────────
    $div_c1 = sqrt(array_sum(array_map(fn($s) => pow($s['nilai_smp'],2), $siswa_all)));
    $div_c2 = sqrt(array_sum(array_map(fn($s) => pow($s['jumlah_sertifikat'],2), $siswa_all)));
    $div_c3 = sqrt(array_sum(array_map(fn($s) => pow($s['c3_num'],2), $siswa_all)));
    $div_c4 = sqrt(array_sum(array_map(fn($s) => pow($s['nilai_mapel'],2), $siswa_all)));

    $Y = [];
    foreach ($siswa_all as $s) {
        $Y[] = [
            'id'   => $s['id'], 'nama' => $s['nama'],
            'y1'   => $div_c1>0 ? $bobot['c1']*($s['nilai_smp']/$div_c1) : 0,
            'y2'   => $div_c2>0 ? $bobot['c2']*($s['jumlah_sertifikat']/$div_c2) : 0,
            'y3'   => $div_c3>0 ? $bobot['c3']*($s['c3_num']/$div_c3) : 0,
            'y4'   => $div_c4>0 ? $bobot['c4']*($s['nilai_mapel']/$div_c4) : 0,
        ];
    }

    $Apos = ['y1'=>max(array_column($Y,'y1')),'y2'=>max(array_column($Y,'y2')),'y3'=>min(array_column($Y,'y3')),'y4'=>max(array_column($Y,'y4'))];
    $Aneg = ['y1'=>min(array_column($Y,'y1')),'y2'=>min(array_column($Y,'y2')),'y3'=>max(array_column($Y,'y3')),'y4'=>min(array_column($Y,'y4'))];

    foreach ($Y as $y) {
        $dp = sqrt(pow($y['y1']-$Apos['y1'],2)+pow($y['y2']-$Apos['y2'],2)+pow($y['y3']-$Apos['y3'],2)+pow($y['y4']-$Apos['y4'],2));
        $dn = sqrt(pow($y['y1']-$Aneg['y1'],2)+pow($y['y2']-$Aneg['y2'],2)+pow($y['y3']-$Aneg['y3'],2)+pow($y['y4']-$Aneg['y4'],2));
        $vi = ($dp+$dn)>0 ? $dn/($dp+$dn) : 0;
        $hasil_topsis[] = ['id'=>$y['id'],'nama'=>$y['nama'],'dp'=>round($dp,4),'dn'=>round($dn,4),'skor'=>round($vi,4)];
    }
    usort($hasil_topsis, fn($a,$b) => $b['skor'] <=> $a['skor']);
    foreach ($hasil_topsis as $i => &$h) {
        $h['peringkat'] = $i+1;
        $h['rekomendasi'] = $h['skor']>=0.75 ? 'Sangat Direkomendasikan'
            : ($h['skor']>=0.55 ? 'Direkomendasikan'
            : ($h['skor']>=0.40 ? 'Cukup Direkomendasikan' : 'Kurang Direkomendasikan'));
    }
    unset($h);

    // Simpan ke database
    mysqli_query($koneksi, "DELETE FROM hasil_spk");
    foreach ($hasil_saw as $h) {
        mysqli_query($koneksi, "INSERT INTO hasil_spk (id_siswa,metode,skor,peringkat,rekomendasi)
            VALUES ({$h['id']},'SAW',{$h['skor']},{$h['peringkat']},'{$h['rekomendasi']}')");
    }
    foreach ($hasil_topsis as $h) {
        mysqli_query($koneksi, "INSERT INTO hasil_spk (id_siswa,metode,skor,peringkat,rekomendasi)
            VALUES ({$h['id']},'TOPSIS',{$h['skor']},{$h['peringkat']},'{$h['rekomendasi']}')");
    }
    $pesan = '<div class="alert alert-success">Perhitungan SAW dan TOPSIS berhasil! <a href="hasil.php">Lihat Hasil →</a></div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK Olimpiade - Hitung SAW & TOPSIS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="navbar">
    <a class="brand" href="index.php">SPK Olimpiade SMAN 3 Malang</a>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="siswa.php">Data Siswa</a>
        <a href="ahp.php">Hitung AHP</a>
        <a href="hitung.php" class="active">Hitung SAW & TOPSIS</a>
        <a href="hasil.php">Hasil & Peringkat</a>
    </nav>
</div>

<div class="container">
    <div class="page-title">Hitung SAW & TOPSIS</div>
    <div class="page-sub">Perhitungan menggunakan bobot dari hasil AHP</div>

    <?= $pesan ?>

    <?php if (!$bobot_db): ?>
    <div class="alert alert-danger">
        Bobot AHP belum tersedia! Silakan <a href="ahp.php"><b>hitung AHP terlebih dahulu</b></a>.
    </div>
    <?php elseif ($n < 3): ?>
    <div class="alert alert-danger">
        Data siswa belum cukup (minimal 3). <a href="siswa.php">Tambah data siswa →</a>
    </div>
    <?php else: ?>

    <!-- INFO BOBOT AHP -->
    <div class="section-box">
        <h3>Bobot Kriteria dari Hasil AHP</h3>
        <div class="card-grid" style="grid-template-columns:repeat(5,1fr);">
            <div class="card">
                <div class="card-number" style="font-size:22px;"><?= round($bobot_db['c1']*100,1) ?>%</div>
                <div class="card-label">C1 — Nilai SMP</div>
            </div>
            <div class="card green">
                <div class="card-number" style="font-size:22px;"><?= round($bobot_db['c2']*100,1) ?>%</div>
                <div class="card-label">C2 — Sertifikat</div>
            </div>
            <div class="card amber">
                <div class="card-number" style="font-size:22px;"><?= round($bobot_db['c3']*100,1) ?>%</div>
                <div class="card-label">C3 — Tingkat</div>
            </div>
            <div class="card purple">
                <div class="card-number" style="font-size:22px;"><?= round($bobot_db['c4']*100,1) ?>%</div>
                <div class="card-label">C4 — Nilai Mapel</div>
            </div>
            <div class="card green">
                <div class="card-number" style="font-size:22px;"><?= round($bobot_db['cr'],4) ?></div>
                <div class="card-label">CR ✅ Konsisten</div>
            </div>
        </div>

        <div style="margin-top:16px;display:flex;gap:10px;">
            <form method="POST">
                <button type="submit" name="hitung" class="btn btn-success" style="font-size:14px;padding:10px 28px;">
                    Jalankan Perhitungan SAW & TOPSIS
                </button>
            </form>
            <a href="ahp.php" class="btn btn-warning">Ubah Bobot AHP</a>
        </div>
    </div>

    <!-- PREVIEW DATA -->
    <div class="table-wrap">
        <div class="table-header"><h3>Preview Data Siswa (<?= $n ?> data)</h3></div>
        <table>
            <tr><th>No</th><th>Nama</th><th>C1 Nilai SMP</th><th>C2 Sertifikat</th><th>C3 Tingkat</th><th>C4 Nilai Mapel</th></tr>
            <?php foreach ($siswa_all as $i => $s): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><b><?= htmlspecialchars($s['nama']) ?></b></td>
                <td><?= $s['nilai_smp'] ?></td>
                <td><?= $s['jumlah_sertifikat'] ?></td>
                <td><?= $s['tingkat'] ?> <span style="color:#888;font-size:11px;">(<?= $s['c3_num'] ?>)</span></td>
                <td><?= $s['nilai_mapel'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php endif; ?>

    <!-- HASIL SAW -->
    <?php if (!empty($hasil_saw)): ?>
    <div class="table-wrap">
        <div class="table-header"><h3>Hasil Perhitungan SAW</h3></div>
        <table>
            <tr><th>Rank</th><th>Nama</th><th>Norm C1</th><th>Norm C2</th><th>Norm C3</th><th>Norm C4</th><th>Skor SAW</th><th>Rekomendasi</th></tr>
            <?php foreach ($hasil_saw as $h):
                $bc = $h['rekomendasi']=='Sangat Layak' ? 'badge-green'
                    : ($h['rekomendasi']=='Layak' ? 'badge-blue'
                    : ($h['rekomendasi']=='Pertimbangkan' ? 'badge-amber' : 'badge-red'));
            ?>
            <tr>
                <td class="rank-<?= $h['peringkat']<=3?$h['peringkat']:'' ?>">
                    <?= $h['peringkat']==1?'🥇':($h['peringkat']==2?'🥈':($h['peringkat']==3?'🥉':$h['peringkat'])) ?>
                </td>
                <td><b><?= htmlspecialchars($h['nama']) ?></b></td>
                <td><?= $h['n1'] ?></td><td><?= $h['n2'] ?></td>
                <td><?= $h['n3'] ?></td><td><?= $h['n4'] ?></td>
                <td><b style="color:#1A3A5C;"><?= $h['skor'] ?></b></td>
                <td><span class="badge <?= $bc ?>"><?= $h['rekomendasi'] ?></span></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- HASIL TOPSIS -->
    <div class="table-wrap">
        <div class="table-header"><h3>Hasil Perhitungan TOPSIS</h3></div>
        <table>
            <tr><th>Rank</th><th>Nama</th><th>D+ (Jarak Positif)</th><th>D- (Jarak Negatif)</th><th>Vi (Skor)</th><th>Rekomendasi</th></tr>
            <?php foreach ($hasil_topsis as $h):
                $bc = str_contains($h['rekomendasi'],'Sangat') ? 'badge-green'
                    : (str_contains($h['rekomendasi'],'Kurang') ? 'badge-red'
                    : (str_contains($h['rekomendasi'],'Cukup') ? 'badge-amber' : 'badge-blue'));
            ?>
            <tr>
                <td class="rank-<?= $h['peringkat']<=3?$h['peringkat']:'' ?>">
                    <?= $h['peringkat']==1?'🥇':($h['peringkat']==2?'🥈':($h['peringkat']==3?'🥉':$h['peringkat'])) ?>
                </td>
                <td><b><?= htmlspecialchars($h['nama']) ?></b></td>
                <td style="color:#E24B4A;"><?= $h['dp'] ?></td>
                <td style="color:#1D9E75;"><?= $h['dn'] ?></td>
                <td><b style="color:#1A3A5C;"><?= $h['skor'] ?></b></td>
                <td><span class="badge <?= $bc ?>"><?= $h['rekomendasi'] ?></span></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
