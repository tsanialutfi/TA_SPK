<?php
require_once 'koneksi.php';

$total_siswa = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM siswa"))[0];
$total_hasil = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_siswa) FROM hasil_spk WHERE metode='SAW'"))[0];
$total_layak = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM hasil_spk WHERE metode='SAW' AND rekomendasi LIKE '%Layak%'"))[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK Olimpiade - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="navbar">
    <a class="brand" href="index.php">SPK Kelas Olimpiade</a>
    <nav>
        <a href="index.php" class="active">Dashboard</a>
        <a href="siswa.php">Data Siswa</a>
        <a href="hitung.php">Hitung SPK</a>
        <a href="hasil.php">Hasil & Peringkat</a>
    </nav>
</div>

<div class="container">
    <div class="page-title">Dashboard</div>
    <div class="page-sub">Sistem Pendukung Keputusan Seleksi Calon Peserta Olimpiade</div>

    <div class="card-grid">
        <div class="card">
            <div class="card-number"><?= $total_siswa ?></div>
            <div class="card-label">Total Siswa Terdaftar</div>
        </div>
        <div class="card green">
            <div class="card-number"><?= $total_hasil ?></div>
            <div class="card-label">Sudah Dihitung</div>
        </div>
        <div class="card amber">
            <div class="card-number"><?= $total_layak ?></div>
            <div class="card-label">Siswa Layak Olimpiade</div>
        </div>
    </div>

    <!-- Info Kriteria -->
    <div class="section-box">
        <h3>Kriteria & Bobot Penilaian (dari AHP)</h3>
        <table>
            <tr>
                <th>Kode</th>
                <th>Kriteria</th>
                <th>Tipe</th>
                <th>Bobot</th>
                <th>Persentase</th>
            </tr>
            <tr>
                <td><b>C1</b></td>
                <td>Nilai Rata-rata SMP</td>
                <td><span class="badge badge-green">Benefit</span></td>
                <td>0.375</td>
                <td>37.5%</td>
            </tr>
            <tr>
                <td><b>C2</b></td>
                <td>Jumlah Sertifikat Prestasi</td>
                <td><span class="badge badge-green">Benefit</span></td>
                <td>0.125</td>
                <td>12.5%</td>
            </tr>
            <tr>
                <td><b>C3</b></td>
                <td>Tingkat Sertifikat Tertinggi</td>
                <td><span class="badge badge-red">Cost</span></td>
                <td>0.125</td>
                <td>12.5%</td>
            </tr>
            <tr>
                <td><b>C4</b></td>
                <td>Nilai Mata Pelajaran Terkait</td>
                <td><span class="badge badge-green">Benefit</span></td>
                <td>0.375</td>
                <td>37.5%</td>
            </tr>
        </table>
    </div>

    <!-- Panduan -->
    <div class="section-box">
        <h3>Panduan Penggunaan Sistem</h3>
        <table>
            <tr>
                <th>Langkah</th>
                <th>Halaman</th>
                <th>Keterangan</th>
            </tr>
            <tr>
                <td>1</td>
                <td><a href="siswa.php" style="color:#1A3A5C;">Data Siswa</a></td>
                <td>Input data seluruh calon peserta olimpiade</td>
            </tr>
            <tr>
                <td>2</td>
                <td><a href="hitung.php" style="color:#1A3A5C;">Hitung SPK</a></td>
                <td>Jalankan perhitungan metode SAW dan TOPSIS</td>
            </tr>
            <tr>
                <td>3</td>
                <td><a href="hasil.php" style="color:#1A3A5C;">Hasil & Peringkat</a></td>
                <td>Lihat hasil perangkingan dan rekomendasi siswa</td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>
