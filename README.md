# 🏆 SPK Seleksi Calon Peserta Olimpiade SMAN 3 Malang

Sistem Pendukung Keputusan (SPK) untuk menyeleksi calon peserta olimpiade menggunakan metode **SAW (Simple Additive Weighting)** dan **TOPSIS (Technique for Order Preference by Similarity to Ideal Solution)**.

---

## 📌 Deskripsi Proyek

Sistem ini dibuat sebagai implementasi tugas praktikum mata kuliah **Sistem Pendukung Keputusan** di Universitas Brawijaya. Sistem membantu Waka Kurikulum SMAN 3 Malang dalam menentukan siswa yang paling layak mewakili sekolah dalam kompetisi olimpiade secara **objektif** dan **berbasis data**.

---

## 👤 Identitas

| Keterangan | Detail |
|---|---|
| Nama | Tsania Lutfiani Hanifa |
| NIM | 235150601111011 |
| Program Studi | Pendidikan Teknologi Informasi |
| Departemen | Sistem Informasi |
| Fakultas | Ilmu Komputer |
| Universitas | Universitas Brawijaya |

---

## ✨ Fitur Sistem

- ➕ **Input data siswa** — tambah dan hapus data calon peserta olimpiade
- 🧮 **Perhitungan SAW** — perangkingan berbasis Simple Additive Weighting
- 📐 **Perhitungan TOPSIS** — perangkingan berbasis jarak solusi ideal
- 📊 **Perbandingan hasil** — membandingkan peringkat SAW vs TOPSIS
- 💾 **Tersimpan di database** — hasil tersimpan di MySQL via phpMyAdmin

---

## 📋 Kriteria Penilaian

Bobot kriteria diperoleh dari perhitungan **AHP (Analytic Hierarchy Process)** dengan nilai CR = 0.000 (konsisten).

| Kode | Kriteria | Tipe | Bobot |
|---|---|---|---|
| C1 | Nilai Rata-rata SMP | Benefit | 37.5% |
| C2 | Jumlah Sertifikat Prestasi | Benefit | 12.5% |
| C3 | Tingkat Sertifikat Tertinggi | Cost | 12.5% |
| C4 | Nilai Mata Pelajaran Terkait | Benefit | 37.5% |

**Kuantifikasi C3 (Tingkat Sertifikat):**
- Nasional = 8
- Provinsi = 9
- Kabupaten/Kota = 10
- Sekolah = 11

---

## 🛠️ Teknologi yang Digunakan

| Teknologi | Kegunaan |
|---|---|
| PHP | Backend & logika perhitungan |
| HTML & CSS | Tampilan antarmuka (UI) |
| MySQL | Database penyimpanan data |
| phpMyAdmin | Manajemen database |
| XAMPP | Local server (Apache + MySQL) |

---

## 📁 Struktur File

```
TA_SPK/
├── css/
│   └── style.css        # Styling tampilan
├── index.php            # Dashboard utama
├── siswa.php            # Kelola data siswa
├── hitung.php           # Proses perhitungan SAW & TOPSIS
├── hasil.php            # Tampilan hasil & peringkat
├── koneksi.php          # Koneksi database
├── .gitignore           # File git ignore
└── README.md            # Dokumentasi proyek
```

---

## ⚙️ Cara Instalasi & Menjalankan

### Prasyarat
- XAMPP sudah terinstall (Apache + MySQL)
- Browser (Chrome / Firefox)

### Langkah-langkah

**1. Clone repository ini:**
```bash
git clone https://github.com/tsanialutfi/TA_SPK.git
```

**2. Pindahkan folder ke htdocs:**
```
C:\xampp\htdocs\TA_SPK
```

**3. Buat database di phpMyAdmin:**
- Buka `localhost/phpmyadmin`
- Buat database baru bernama `ta_spk`
- Collation: `utf8mb4_general_ci`

**4. Buat tabel dengan query SQL berikut:**
```sql
CREATE TABLE siswa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nisn VARCHAR(20) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  kelas VARCHAR(10) NOT NULL,
  bidang_olimpiade VARCHAR(50) NOT NULL,
  nilai_smp DOUBLE NOT NULL,
  jumlah_sertifikat INT NOT NULL,
  tingkat VARCHAR(20) NOT NULL,
  nilai_mapel DOUBLE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE hasil_spk (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_siswa INT NOT NULL,
  metode VARCHAR(10) NOT NULL,
  skor DOUBLE NOT NULL,
  peringkat INT NOT NULL,
  rekomendasi VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_siswa) REFERENCES siswa(id)
);
```

**5. Jalankan XAMPP** — Start Apache & MySQL

**6. Buka browser:**
```
localhost/TA_SPK
```

---

## 🚀 Cara Penggunaan

1. **Data Siswa** → Input data calon peserta olimpiade
2. **Hitung SPK** → Klik tombol untuk menjalankan perhitungan SAW & TOPSIS
3. **Hasil & Peringkat** → Lihat peringkat dan perbandingan kedua metode

---

## 📐 Metode yang Diimplementasikan

### SAW (Simple Additive Weighting)
1. Normalisasi nilai: benefit = nilai ÷ max, cost = min ÷ nilai
2. Hitung skor: `V = Σ (bobot × nilai normalisasi)`
3. Ranking dari skor tertinggi

### TOPSIS
1. Normalisasi: `rij = xij ÷ √(Σ xij²)`
2. Bobot normalisasi: `yij = wj × rij`
3. Tentukan solusi ideal positif (A⁺) dan negatif (A⁻)
4. Hitung jarak: `D⁺ = √Σ(yij − A⁺j)²` dan `D⁻ = √Σ(yij − A⁻j)²`
5. Nilai preferensi: `Vi = D⁻ ÷ (D⁺ + D⁻)`
6. Ranking dari Vi tertinggi

---

## 📝 Lisensi

Proyek ini dibuat untuk keperluan akademik — Praktikum Sistem Pendukung Keputusan, Universitas Brawijaya 2026.