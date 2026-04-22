<?php
require_once 'koneksi.php';

// Bobot dari AHP Modul 3
$bobot = ['c1' => 0.375, 'c2' => 0.125, 'c3' => 0.125, 'c4' => 0.375];

// Kuantifikasi tingkat
$tingkat_map = ['Nasional' => 8, 'Provinsi' => 9, 'Kabupaten/Kota' => 10, 'Sekolah' => 11];

$pesan = '';
$hasil_saw    = [];
$hasil_topsis = [];

// Ambil data siswa
$query = mysqli_query($koneksi, "SELECT * FROM siswa ORDER BY id ASC");
$siswa_all = [];
while ($row = mysqli_fetch_assoc($query)) {
    $row['c3_num'] = $tingkat_map[$row['tingkat']] ?? 10;
    $siswa_all[] = $row;
}
$n = count($siswa_all);

// ===================== PROSES HITUNG =====================
if (isset($_POST['hitung']) && $n >= 3) {

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
        $hasil_saw[] = ['id' => $s['id'], 'nama' => $s['nama'], 'skor' => round($skor, 4)];
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
    // Pembagi (akar jumlah kuadrat)
    $div_c1 = sqrt(array_sum(array_map(fn($s) => pow($s['nilai_smp'],2), $siswa_all)));
    $div_c2 = sqrt(array_sum(array_map(fn($s) => pow($s['jumlah_sertifikat'],2), $siswa_all)));
    $div_c3 = sqrt(array_sum(array_map(fn($s) => pow($s['c3_num'],2), $siswa_all)));
    $div_c4 = sqrt(array_sum(array_map(fn($s) => pow($s['nilai_mapel'],2), $siswa_all)));

    // Normalisasi & bobot
    $Y = [];
    foreach ($siswa_all as $s) {
        $Y[] = [
            'id'   => $s['id'],
            'nama' => $s['nama'],
            'y1'   => $div_c1 > 0 ? $bobot['c1'] * ($s['nilai_smp'] / $div_c1) : 0,
            'y2'   => $div_c2 > 0 ? $bobot['c2'] * ($s['jumlah_sertifikat'] / $div_c2) : 0,
            'y3'   => $div_c3 > 0 ? $bobot['c3'] * ($s['c3_num'] / $div_c3) : 0,
            'y4'   => $div_c4 > 0 ? $bobot['c4'] * ($s['nilai_mapel'] / $div_c4) : 0,
        ];
    }

    // Solusi ideal
    $Apos = [
        'y1' => max(array_column($Y,'y1')), // benefit: max
        'y2' => max(array_column($Y,'y2')),
        'y3' => min(array_column($Y,'y3')), // cost: min
        'y4' => max(array_column($Y,'y4')),
    ];
    $Aneg = [
        'y1' => min(array_column($Y,'y1')),
        'y2' => min(array_column($Y,'y2')),
        'y3' => max(array_column($Y,'y3')),
        'y4' => min(array_column($Y,'y4')),
    ];

    // Jarak & skor
    foreach ($Y as $y) {
        $dp = sqrt(pow($y['y1']-$Apos['y1'],2)+pow($y['y2']-$Apos['y2'],2)+pow($y['y3']-$Apos['y3'],2)+pow($y['y4']-$Apos['y4'],2));
        $dn = sqrt(pow($y['y1']-$Aneg['y1'],2)+pow($y['y2']-$Aneg['y2'],2)+pow($y['y3']-$Aneg['y3'],2)+pow($y['y4']-$Aneg['y4'],2));
        $vi = ($dp + $dn) > 0 ? $dn / ($dp + $dn) : 0;
        $hasil_topsis[] = ['id' => $y['id'], 'nama' => $y['nama'], 'dp' => round($dp,4), 'dn' => round($dn,4), 'skor' => round($vi,4)];
    }
    usort($hasil_topsis, fn($a,$b) => $b['skor'] <=> $a['skor']);
    foreach ($hasil_topsis as $i => &$h) {
        $h['peringkat'] = $i + 1;
        $h['rekomendasi'] = $h['skor'] >= 0.75 ? 'Sangat Direkomendasikan'
            : ($h['skor'] >= 0.55 ? 'Direkomendasikan'
            : ($h['skor'] >= 0.40 ? 'Cukup Direkomendasikan' : 'Kurang Direkomendasikan'));
    }
    unset($h);

    // Simpan ke database
    mysqli_query($koneksi, "DELETE FROM hasil_spk");
    foreach ($hasil_saw as $h) {
        $sql = "INSERT INTO hasil_spk (id_siswa, metode, skor, peringkat, rekomendasi)
                VALUES ({$h['id']}, 'SAW', {$h['skor']}, {$h['peringkat']}, '{$h['rekomendasi']}')";
        mysqli_query($koneksi, $sql);
    }
    foreach ($hasil_topsis as $h) {
        $sql = "INSERT INTO hasil_spk (id_siswa, metode, skor, peringkat, rekomendasi)
                VALUES ({$h['id']}, 'TOPSIS', {$h['skor']}, {$h['peringkat']}, '{$h['rekomendasi']}')";
        mysqli_query($koneksi, $sql);
    }

    $pesan = '<div class="alert alert-success">Perhitungan SAW dan TOPSIS berhasil! <a href="hasil.php">Lihat Hasil &rarr;</a></div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK Olimpiade - Hitung SPK</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="navbar">
    <a class="brand" href="index.php">SPK Olimpiade SMAN 3 Malang</a>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="siswa.php">Data Siswa</a>
        <a href="hitung.php" class="active">Hitung SPK</a>
        <a href="hasil.php">Hasil & Peringkat</a>
    </nav>
</div>

<div class="container">
    <div class="page-title">Hitung SPK</div>
    <div class="page-sub">Proses perhitungan metode SAW dan TOPSIS</div>

    <?= $pesan ?>

    <?php if ($n < 3): ?>
    <div class="alert alert-danger">
        Data siswa belum cukup. Minimal 3 siswa diperlukan untuk perhitungan.
        <a href="siswa.php">Tambah data siswa &rarr;</a>
    </div>
    <?php else: ?>

    <!-- INFO -->
    <div class="section-box">
        <h3>Data Siap Dihitung</h3>
        <div class="card-grid" style="grid-template-columns:repeat(4,1fr);">
            <div class="card"><div class="card-number"><?= $n ?></div><div class="card-label">Total Siswa</div></div>
            <div class="card green"><div class="card-number">4</div><div class="card-label">Kriteria</div></div>
            <div class="card amber"><div class="card-number">2</div><div class="card-label">Metode (SAW & TOPSIS)</div></div>
            <div class="card purple"><div class="card-number">0.000</div><div class="card-label">CR (AHP Konsisten)</div></div>
        </div>

        <form method="POST">
            <button type="submit" name="hitung" class="btn btn-success" style="font-size:15px;padding:12px 32px;">
                Jalankan Perhitungan SAW & TOPSIS
            </button>
        </form>
    </div>

    <!-- PREVIEW DATA -->
    <div class="table-wrap">
        <div class="table-header"><h3>Preview Data Siswa (<?= $n ?> data)</h3></div>
        <table>
            <tr>
                <th>No</th><th>Nama</th><th>C1 Nilai SMP</th>
                <th>C2 Sertifikat</th><th>C3 Tingkat</th><th>C4 Nilai Mapel</th>
            </tr>
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

    <?php if (!empty($hasil_saw)): ?>
    <!-- HASIL SAW -->
    <div class="table-wrap">
        <div class="table-header"><h3>Hasil Perhitungan SAW</h3></div>
        <table>
            <tr><th>Rank</th><th>Nama</th><th>Skor SAW</th><th>Rekomendasi</th></tr>
            <?php foreach ($hasil_saw as $h): ?>
            <tr>
                <td class="rank-<?= $h['peringkat'] <= 3 ? $h['peringkat'] : '' ?>">
                    <?= $h['peringkat'] == 1 ? '🥇' : ($h['peringkat'] == 2 ? '🥈' : ($h['peringkat'] == 3 ? '🥉' : $h['peringkat'])) ?>
                </td>
                <td><b><?= htmlspecialchars($h['nama']) ?></b></td>
                <td><?= $h['skor'] ?></td>
                <td>
                    <?php
                    $bc = $h['rekomendasi'] == 'Sangat Layak' ? 'badge-green'
                        : ($h['rekomendasi'] == 'Layak' ? 'badge-blue'
                        : ($h['rekomendasi'] == 'Pertimbangkan' ? 'badge-amber' : 'badge-red'));
                    ?>
                    <span class="badge <?= $bc ?>"><?= $h['rekomendasi'] ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- HASIL TOPSIS -->
    <div class="table-wrap">
        <div class="table-header"><h3>Hasil Perhitungan TOPSIS</h3></div>
        <table>
            <tr><th>Rank</th><th>Nama</th><th>D+ (Jarak Positif)</th><th>D- (Jarak Negatif)</th><th>Vi (Skor)</th><th>Rekomendasi</th></tr>
            <?php foreach ($hasil_topsis as $h): ?>
            <tr>
                <td class="rank-<?= $h['peringkat'] <= 3 ? $h['peringkat'] : '' ?>">
                    <?= $h['peringkat'] == 1 ? '🥇' : ($h['peringkat'] == 2 ? '🥈' : ($h['peringkat'] == 3 ? '🥉' : $h['peringkat'])) ?>
                </td>
                <td><b><?= htmlspecialchars($h['nama']) ?></b></td>
                <td><?= $h['dp'] ?></td>
                <td><?= $h['dn'] ?></td>
                <td><b><?= $h['skor'] ?></b></td>
                <td>
                    <?php
                    $bc = str_contains($h['rekomendasi'],'Sangat') ? 'badge-green'
                        : (str_contains($h['rekomendasi'],'Cukup') ? 'badge-amber'
                        : (str_contains($h['rekomendasi'],'Kurang') ? 'badge-red' : 'badge-blue'));
                    ?>
                    <span class="badge <?= $bc ?>"><?= $h['rekomendasi'] ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
