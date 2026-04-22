<?php
require_once 'koneksi.php';

$pesan = '';

// HAPUS
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM hasil_spk WHERE id_siswa = $id");
    mysqli_query($koneksi, "DELETE FROM siswa WHERE id = $id");
    $pesan = '<div class="alert alert-success">Data siswa berhasil dihapus.</div>';
}

// TAMBAH
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nisn       = mysqli_real_escape_string($koneksi, $_POST['nisn']);
    $nama       = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $kelas      = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    $bidang     = mysqli_real_escape_string($koneksi, $_POST['bidang_olimpiade']);
    $nilai_smp  = (float)$_POST['nilai_smp'];
    $jml_sert   = (int)$_POST['jumlah_sertifikat'];
    $tingkat    = mysqli_real_escape_string($koneksi, $_POST['tingkat']);
    $nilai_mapel= (float)$_POST['nilai_mapel'];

    // cek duplikat NISN
    $cek = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM siswa WHERE nisn='$nisn'"))[0];
    if ($cek > 0) {
        $pesan = '<div class="alert alert-danger">NISN sudah terdaftar! Gunakan NISN yang berbeda.</div>';
    } else {
        $sql = "INSERT INTO siswa (nisn, nama, kelas, bidang_olimpiade, nilai_smp, jumlah_sertifikat, tingkat, nilai_mapel)
                VALUES ('$nisn','$nama','$kelas','$bidang',$nilai_smp,$jml_sert,'$tingkat',$nilai_mapel)";
        if (mysqli_query($koneksi, $sql)) {
            $pesan = '<div class="alert alert-success">Data siswa berhasil ditambahkan!</div>';
        } else {
            $pesan = '<div class="alert alert-danger">Gagal menambah data: ' . mysqli_error($koneksi) . '</div>';
        }
    }
}

$siswa_list = mysqli_query($koneksi, "SELECT * FROM siswa ORDER BY nama ASC");
$total = mysqli_num_rows($siswa_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK Olimpiade - Data Siswa</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="navbar">
    <a class="brand" href="index.php">SPK Olimpiade SMAN 3 Malang</a>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="siswa.php" class="active">Data Siswa</a>
        <a href="hitung.php">Hitung SPK</a>
        <a href="hasil.php">Hasil & Peringkat</a>
    </nav>
</div>

<div class="container">
    <div class="page-title">Data Siswa</div>
    <div class="page-sub">Kelola data calon peserta olimpiade</div>

    <?= $pesan ?>

    <!-- FORM TAMBAH -->
    <div class="form-wrap">
        <h3 style="font-size:15px;color:#1A3A5C;margin-bottom:18px;padding-bottom:10px;border-bottom:2px solid #f0f0f0;">
            Tambah Data Siswa
        </h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>NISN</label>
                    <input type="text" name="nisn" placeholder="Contoh: 0011220001" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" placeholder="Nama siswa" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Kelas</label>
                    <select name="kelas" required>
                        <option value="">-- Pilih Kelas --</option>
                        <option value="X-A">X-A</option>
                        <option value="X-B">X-B</option>
                        <option value="X-C">X-C</option>
                        <option value="X-D">X-D</option>
                        <option value="X-E">X-E</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Bidang Olimpiade</label>
                    <select name="bidang_olimpiade" required>
                        <option value="">-- Pilih Bidang --</option>
                        <option value="Matematika">Matematika</option>
                        <option value="Fisika">Fisika</option>
                        <option value="Kimia">Kimia</option>
                        <option value="Biologi">Biologi</option>
                        <option value="Informatika">Informatika</option>
                        <option value="Ekonomi">Ekonomi</option>
                        <option value="Astronomi">Astronomi</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Nilai Rata-rata SMP (C1)</label>
                    <input type="number" name="nilai_smp" step="0.01" min="0" max="100" placeholder="Contoh: 85.5" required>
                </div>
                <div class="form-group">
                    <label>Jumlah Sertifikat Prestasi (C2)</label>
                    <input type="number" name="jumlah_sertifikat" min="0" placeholder="Contoh: 3" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tingkat Sertifikat Tertinggi (C3)</label>
                    <select name="tingkat" required>
                        <option value="">-- Pilih Tingkat --</option>
                        <option value="Nasional">Nasional</option>
                        <option value="Provinsi">Provinsi</option>
                        <option value="Kabupaten/Kota">Kabupaten/Kota</option>
                        <option value="Sekolah">Sekolah</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nilai Mapel Terkait Olimpiade (C4)</label>
                    <input type="number" name="nilai_mapel" step="0.01" min="0" max="100" placeholder="Contoh: 88" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Data Siswa</button>
        </form>
    </div>

    <!-- TABEL DATA -->
    <div class="table-wrap">
        <div class="table-header">
            <h3>Daftar Siswa (<?= $total ?> data)</h3>
            <?php if ($total >= 3): ?>
                <a href="hitung.php" class="btn btn-success btn-sm">Hitung SPK &rarr;</a>
            <?php endif; ?>
        </div>
        <table>
            <tr>
                <th>No</th>
                <th>NISN</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Bidang</th>
                <th>C1 (Nilai SMP)</th>
                <th>C2 (Sertifikat)</th>
                <th>C3 (Tingkat)</th>
                <th>C4 (Nilai Mapel)</th>
                <th>Aksi</th>
            </tr>
            <?php
            $no = 1;
            mysqli_data_seek($siswa_list, 0);
            while ($s = mysqli_fetch_assoc($siswa_list)):
                $tingkat_map = ['Nasional'=>8,'Provinsi'=>9,'Kabupaten/Kota'=>10,'Sekolah'=>11];
                $kode_tingkat = $tingkat_map[$s['tingkat']] ?? '-';
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($s['nisn']) ?></td>
                <td><b><?= htmlspecialchars($s['nama']) ?></b></td>
                <td><?= $s['kelas'] ?></td>
                <td><?= $s['bidang_olimpiade'] ?></td>
                <td><?= $s['nilai_smp'] ?></td>
                <td><?= $s['jumlah_sertifikat'] ?></td>
                <td>
                    <?= $s['tingkat'] ?>
                    <span style="color:#888;font-size:11px;">(<?= $kode_tingkat ?>)</span>
                </td>
                <td><?= $s['nilai_mapel'] ?></td>
                <td>
                    <a href="siswa.php?hapus=<?= $s['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Hapus data <?= htmlspecialchars($s['nama']) ?>?')">
                        Hapus
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if ($total == 0): ?>
            <tr><td colspan="10" style="text-align:center;color:#aaa;padding:24px;">Belum ada data siswa. Tambahkan data di atas.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

</body>
</html>
