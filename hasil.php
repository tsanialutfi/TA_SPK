<?php
require_once 'koneksi.php';

$saw_list = mysqli_query($koneksi,
    "SELECT h.*, s.nisn, s.nama, s.kelas, s.bidang_olimpiade,
            s.nilai_smp, s.jumlah_sertifikat, s.tingkat, s.nilai_mapel
     FROM hasil_spk h
     JOIN siswa s ON h.id_siswa = s.id
     WHERE h.metode = 'SAW'
     ORDER BY h.peringkat ASC");

$topsis_list = mysqli_query($koneksi,
    "SELECT h.*, s.nisn, s.nama, s.kelas, s.bidang_olimpiade
     FROM hasil_spk h
     JOIN siswa s ON h.id_siswa = s.id
     WHERE h.metode = 'TOPSIS'
     ORDER BY h.peringkat ASC");

$total_saw    = mysqli_num_rows($saw_list);
$total_topsis = mysqli_num_rows($topsis_list);

function badge($rek) {
    if (str_contains($rek,'Sangat')) return 'badge-green';
    if (str_contains($rek,'Layak') || str_contains($rek,'Direkomendasikan')) return 'badge-blue';
    if (str_contains($rek,'Cukup') || str_contains($rek,'Pertimbangkan')) return 'badge-amber';
    return 'badge-red';
}

function rankIcon($r) {
    return $r == 1 ? '🥇' : ($r == 2 ? '🥈' : ($r == 3 ? '🥉' : $r));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK Olimpiade - Hasil & Peringkat</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="navbar">
    <a class="brand" href="index.php">SPK Olimpiade SMAN 3 Malang</a>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="siswa.php">Data Siswa</a>
        <a href="hitung.php">Hitung SPK</a>
        <a href="hasil.php" class="active">Hasil & Peringkat</a>
    </nav>
</div>

<div class="container">
    <div class="page-title">Hasil & Peringkat</div>
    <div class="page-sub">Hasil perhitungan SPK metode SAW dan TOPSIS</div>

    <?php if ($total_saw == 0): ?>
    <div class="alert alert-info">
        Belum ada hasil perhitungan. Silakan <a href="hitung.php">jalankan perhitungan</a> terlebih dahulu.
    </div>
    <?php else: ?>

    <!-- TABS -->
    <div style="display:flex;gap:10px;margin-bottom:20px;">
        <button onclick="showTab('saw')" id="tab-saw"
            style="padding:10px 24px;border:none;border-radius:6px;cursor:pointer;font-weight:bold;font-size:13px;background:#1A3A5C;color:white;">
            Hasil SAW
        </button>
        <button onclick="showTab('topsis')" id="tab-topsis"
            style="padding:10px 24px;border:none;border-radius:6px;cursor:pointer;font-weight:bold;font-size:13px;background:#eee;color:#555;">
            Hasil TOPSIS
        </button>
        <button onclick="showTab('perbandingan')" id="tab-perbandingan"
            style="padding:10px 24px;border:none;border-radius:6px;cursor:pointer;font-weight:bold;font-size:13px;background:#eee;color:#555;">
            Perbandingan
        </button>
    </div>

    <!-- TAB SAW -->
    <div id="content-saw">
        <div class="table-wrap">
            <div class="table-header">
                <h3>Peringkat Siswa — Metode SAW (<?= $total_saw ?> siswa)</h3>
                <a href="hitung.php" class="btn btn-warning btn-sm">Hitung Ulang</a>
            </div>
            <table>
                <tr>
                    <th>Rank</th><th>Nama</th><th>Kelas</th><th>Bidang</th>
                    <th>C1</th><th>C2</th><th>C3</th><th>C4</th>
                    <th>Skor SAW</th><th>Rekomendasi</th>
                </tr>
                <?php while ($r = mysqli_fetch_assoc($saw_list)): ?>
                <tr>
                    <td class="rank-<?= $r['peringkat'] <= 3 ? $r['peringkat'] : '' ?>">
                        <?= rankIcon($r['peringkat']) ?>
                    </td>
                    <td><b><?= htmlspecialchars($r['nama']) ?></b><br>
                        <span style="font-size:11px;color:#999;"><?= $r['nisn'] ?></span></td>
                    <td><?= $r['kelas'] ?></td>
                    <td><?= $r['bidang_olimpiade'] ?></td>
                    <td><?= $r['nilai_smp'] ?></td>
                    <td><?= $r['jumlah_sertifikat'] ?></td>
                    <td><?= $r['tingkat'] ?></td>
                    <td><?= $r['nilai_mapel'] ?></td>
                    <td><b style="color:#1A3A5C;"><?= $r['skor'] ?></b></td>
                    <td><span class="badge <?= badge($r['rekomendasi']) ?>"><?= $r['rekomendasi'] ?></span></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- TAB TOPSIS -->
    <div id="content-topsis" style="display:none;">
        <div class="table-wrap">
            <div class="table-header">
                <h3>Peringkat Siswa — Metode TOPSIS (<?= $total_topsis ?> siswa)</h3>
            </div>
            <table>
                <tr>
                    <th>Rank</th><th>Nama</th><th>Kelas</th><th>Bidang</th>
                    <th>D+ (Jarak Positif)</th><th>D- (Jarak Negatif)</th>
                    <th>Vi (Skor)</th><th>Rekomendasi</th>
                </tr>
                <?php while ($r = mysqli_fetch_assoc($topsis_list)): ?>
                <tr>
                    <td class="rank-<?= $r['peringkat'] <= 3 ? $r['peringkat'] : '' ?>">
                        <?= rankIcon($r['peringkat']) ?>
                    </td>
                    <td><b><?= htmlspecialchars($r['nama']) ?></b><br>
                        <span style="font-size:11px;color:#999;"><?= $r['nisn'] ?></span></td>
                    <td><?= $r['kelas'] ?></td>
                    <td><?= $r['bidang_olimpiade'] ?></td>
                    <td style="color:#E24B4A;"><?= $r['skor'] ?></td>
                    <td style="color:#1D9E75;"><?= $r['skor'] ?></td>
                    <td><b style="color:#1A3A5C;"><?= $r['skor'] ?></b></td>
                    <td><span class="badge <?= badge($r['rekomendasi']) ?>"><?= $r['rekomendasi'] ?></span></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- TAB PERBANDINGAN -->
    <div id="content-perbandingan" style="display:none;">
        <?php
        $saw2 = mysqli_query($koneksi,
            "SELECT h.peringkat as rank_saw, s.nama
             FROM hasil_spk h JOIN siswa s ON h.id_siswa=s.id
             WHERE h.metode='SAW' ORDER BY h.peringkat ASC");
        $topsis2 = mysqli_query($koneksi,
            "SELECT h.peringkat as rank_topsis, h.skor as skor_topsis, s.nama
             FROM hasil_spk h JOIN siswa s ON h.id_siswa=s.id
             WHERE h.metode='TOPSIS' ORDER BY h.id_siswa ASC");
        $saw_data = [];
        while ($r = mysqli_fetch_assoc($saw2)) $saw_data[$r['nama']] = $r['rank_saw'];
        $topsis_data = [];
        while ($r = mysqli_fetch_assoc($topsis2)) $topsis_data[$r['nama']] = ['rank'=>$r['rank_topsis'],'skor'=>$r['skor_topsis']];

        $saw3 = mysqli_query($koneksi,
            "SELECT h.peringkat, h.skor, s.nama
             FROM hasil_spk h JOIN siswa s ON h.id_siswa=s.id
             WHERE h.metode='SAW' ORDER BY h.peringkat ASC");
        ?>
        <div class="table-wrap">
            <div class="table-header"><h3>Perbandingan Peringkat SAW vs TOPSIS</h3></div>
            <table>
                <tr>
                    <th>Nama Siswa</th>
                    <th>Rank SAW</th>
                    <th>Skor SAW</th>
                    <th>Rank TOPSIS</th>
                    <th>Skor TOPSIS</th>
                    <th>Selisih Rank</th>
                </tr>
                <?php while ($r = mysqli_fetch_assoc($saw3)):
                    $rank_t = $topsis_data[$r['nama']]['rank'] ?? '-';
                    $skor_t = $topsis_data[$r['nama']]['skor'] ?? '-';
                    $selisih = is_numeric($rank_t) ? abs($r['peringkat'] - $rank_t) : '-';
                    $warna = $selisih === 0 ? '#1D9E75' : ($selisih <= 2 ? '#E8A020' : '#E24B4A');
                ?>
                <tr>
                    <td><b><?= htmlspecialchars($r['nama']) ?></b></td>
                    <td><?= rankIcon($r['peringkat']) ?></td>
                    <td><?= $r['skor'] ?></td>
                    <td><?= is_numeric($rank_t) ? rankIcon($rank_t) : $rank_t ?></td>
                    <td><?= $skor_t ?></td>
                    <td style="color:<?= $warna ?>;font-weight:bold;"><?= $selisih === 0 ? 'Sama' : ($selisih !== '-' ? '±'.$selisih : '-') ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <div class="alert alert-info">
            <b>Keterangan:</b> Selisih rank menunjukkan perbedaan peringkat antara SAW dan TOPSIS.
            Warna <span style="color:#1D9E75;font-weight:bold;">hijau</span> = peringkat sama,
            <span style="color:#E8A020;font-weight:bold;">kuning</span> = selisih kecil (1-2),
            <span style="color:#E24B4A;font-weight:bold;">merah</span> = selisih besar (3+).
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
function showTab(tab) {
    ['saw','topsis','perbandingan'].forEach(function(t) {
        document.getElementById('content-' + t).style.display = 'none';
        var btn = document.getElementById('tab-' + t);
        btn.style.background = '#eee';
        btn.style.color = '#555';
    });
    document.getElementById('content-' + tab).style.display = 'block';
    var active = document.getElementById('tab-' + tab);
    active.style.background = '#1A3A5C';
    active.style.color = 'white';
}
</script>

</body>
</html>
