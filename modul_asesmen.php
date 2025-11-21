<?php
session_start();
include 'config/database.php';

// Cek Sesi
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

// Ambil ID Siswa
$user_id = $_SESSION['user_id'];
$res = $conn->query("SELECT id FROM siswa WHERE id_pengguna = '$user_id'");
$row = $res->fetch_assoc();
$id_siswa = $row['id'];

// PROSES SUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama_ayah      = $_POST['nama_ayah'];
    $pekerjaan_ayah = $_POST['pekerjaan_ayah'];
    $nama_ibu       = $_POST['nama_ibu'];
    $pekerjaan_ibu  = $_POST['pekerjaan_ibu'];
    $ekonomi        = $_POST['status_ekonomi'];
    $saudara        = $_POST['jumlah_saudara'];
    $alamat         = $_POST['alamat'];

    // Insert ke tabel 'detail_keluarga_siswa'
    $sql = "INSERT INTO detail_keluarga_siswa 
            (id_siswa, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, status_ekonomi, jumlah_saudara, alamat) 
            VALUES 
            ('$id_siswa', '$nama_ayah', '$pekerjaan_ayah', '$nama_ibu', '$pekerjaan_ibu', '$ekonomi', '$saudara', '$alamat')";
    
    if ($conn->query($sql) === TRUE) {
        // Setelah sukses isi data keluarga, anggap onboarding selesai
        // Opsional: Anda bisa lanjut redirect ke form asesmen (gaya belajar) jika mau
        header("Location: modul_asesmen_2.php");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Lengkapi Data Keluarga</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <style>
        .lexend-font {
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
            }
    </style>
</head>
<body class="bg-slate-50 py-10 lexend-font">

    <div class="max-w-3xl mx-auto bg-white shadow-xl rounded-xl overflow-hidden">
        <div class="bg-blue-600 p-6 text-white">
            <h2 class="text-xl font-bold">Data Keluarga & Ekonomi</h2>
            <p class="text-blue-100 text-sm">Mohon lengkapi data ini sebelum mengakses layanan BK.</p>
        </div>

        <form method="POST" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="space-y-4">
                <h3 class="font-bold text-slate-700 border-b pb-2">Data Ayah</h3>
                <div>
                    <label class="block text-sm text-slate-600">Nama Ayah</label>
                    <input type="text" name="nama_ayah" required class="w-full border p-2 rounded">
                </div>
                <div>
                    <label class="block text-sm text-slate-600">Pekerjaan Ayah</label>
                    <input type="text" name="pekerjaan_ayah" required class="w-full border p-2 rounded">
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="font-bold text-slate-700 border-b pb-2">Data Ibu</h3>
                <div>
                    <label class="block text-sm text-slate-600">Nama Ibu</label>
                    <input type="text" name="nama_ibu" required class="w-full border p-2 rounded">
                </div>
                <div>
                    <label class="block text-sm text-slate-600">Pekerjaan Ibu</label>
                    <input type="text" name="pekerjaan_ibu" required class="w-full border p-2 rounded">
                </div>
            </div>

            <div class="col-span-1 md:col-span-2 space-y-4 mt-4">
                <h3 class="font-bold text-slate-700 border-b pb-2">Lainnya</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm text-slate-600">Status Ekonomi</label>
                        <select name="status_ekonomi" class="w-full border p-2 rounded">
                            <option value="Mampu">Mampu</option>
                            <option value="Cukup">Cukup</option>
                            <option value="Kurang Mampu">Kurang Mampu</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600">Jumlah Saudara</label>
                        <input type="number" name="jumlah_saudara" required class="w-full border p-2 rounded">
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-600">Alamat Lengkap</label>
                    <textarea name="alamat" rows="3" required class="w-full border p-2 rounded"></textarea>
                </div>
            </div>

            <div class="col-span-1 md:col-span-2 pt-4">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition">
                    Simpan Data
                </button>
            </div>

        </form>
    </div>

</body>
</html>