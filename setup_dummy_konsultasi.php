<?php
include 'config/database.php';

echo "<h1>Setup Dummy Data Konsultasi</h1>";

// 1. Pastikan ada Siswa Dummy
$check_siswa = $conn->query("SELECT id FROM siswa LIMIT 1");
if ($check_siswa->num_rows == 0) {
    die("Harap buat minimal satu siswa dulu (via Register) sebelum menjalankan script ini.");
}
$id_siswa = $check_siswa->fetch_assoc()['id'];

// 2. Pastikan ada Konselor Dummy
$check_konselor = $conn->query("SELECT id FROM konselor LIMIT 1");
if ($check_konselor->num_rows == 0) {
    die("Harap buat minimal satu konselor dulu (via Admin Dashboard) sebelum menjalankan script ini.");
}
$id_konselor = $check_konselor->fetch_assoc()['id'];

// 3. Insert Dummy Hasil Asesmen (PRIORITAS)
// Kita buat satu record kesehatan mental yang "PERLU PERHATIAN KHUSUS"
$json_mental = json_encode(['cemas' => 'Ya', 'depresi' => 'Ya']);
$skor_bahaya = "PERLU PERHATIAN KHUSUS (Indikasi Depresi)";
$conn->query("INSERT INTO hasil_asesmen (id_siswa, kategori, ringkasan_hasil, skor) VALUES ('$id_siswa', 'kesehatan_mental', '$json_mental', '$skor_bahaya')");
echo "<p style='color:green'>[OK] Insert Data Asesmen Prioritas (Mental Health).</p>";

// 4. Insert Dummy Konsultasi
// A. Request Baru (Pending)
$sql_pending = "INSERT INTO konsultasi (id_siswa, id_konselor, kategori_topik, deskripsi_keluhan, tanggal_konsultasi, status, metode_konsultasi) 
                VALUES ('$id_siswa', '$id_konselor', 'Masalah Pribadi', 'Saya merasa sangat cemas akhir-akhir ini dan butuh teman cerita.', DATE_ADD(NOW(), INTERVAL 2 DAY), 'menunggu', 'offline')";

// B. Jadwal Besok (Disetujui)
$sql_approved = "INSERT INTO konsultasi (id_siswa, id_konselor, kategori_topik, deskripsi_keluhan, tanggal_konsultasi, status, metode_konsultasi) 
                 VALUES ('$id_siswa', '$id_konselor', 'Akademik', 'Nilai saya turun drastis.', DATE_ADD(NOW(), INTERVAL 1 DAY), 'disetujui', 'online')";

if ($conn->query($sql_pending) === TRUE) {
    echo "<p style='color:green'>[OK] Insert Konsultasi Pending (Topik: Masalah Pribadi).</p>";
} else {
    echo "Error: " . $conn->error;
}

if ($conn->query($sql_approved) === TRUE) {
    echo "<p style='color:green'>[OK] Insert Konsultasi Disetujui (Topik: Akademik).</p>";
} else {
    echo "Error: " . $conn->error;
}

echo "<hr><p>Selesai. Silakan cek <a href='dashboard_guru.php'>Dashboard Guru</a>.</p>";
?>
