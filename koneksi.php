<?php
$host     = "localhost";
$user     = "root";
$password = "";
$database = "ta_spk";

$koneksi = mysqli_connect($host, $user, $password, $database);

if (!$koneksi) {
    die("<div style='font-family:Arial;padding:20px;color:red;'>
        <h3>Koneksi Database Gagal!</h3>
        <p>" . mysqli_connect_error() . "</p>
        <p>Pastikan XAMPP (Apache & MySQL) sudah berjalan.</p>
    </div>");
}

mysqli_set_charset($koneksi, "utf8mb4");
?>
