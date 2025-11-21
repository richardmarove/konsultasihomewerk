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
    // Collect answers
    $answers = [
        'status_orang_tua' => [
            'q1_status_ortu' => $_POST['q1_status_ortu'],
            'q2_status_ortu' => $_POST['q2_status_ortu'],
            'q3_status_ortu' => $_POST['q3_status_ortu'],
            'q4_status_ortu' => $_POST['q4_status_ortu'],
        ],
        'tes_gaya_belajar' => [
            'q1_gaya_belajar' => $_POST['q1_gaya_belajar'],
            'q2_gaya_belajar' => $_POST['q2_gaya_belajar'],
            'q3_gaya_belajar' => $_POST['q3_gaya_belajar'],
            'q4_gaya_belajar' => $_POST['q4_gaya_belajar'],
        ],
    ];

    $json_answers = json_encode($answers);

    // Insert into hasil_asesmen table
    // Assuming hasil_asesmen has id_siswa and ringkasan_hasil (TEXT) columns
    $sql = "INSERT INTO hasil_asesmen (id_siswa, ringkasan_hasil) VALUES ('$id_siswa', '$json_answers')";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: dashboard_siswa.php");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Lengkapi Asesmen Sosial</title>
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
            <h2 class="text-xl font-bold">Asesmen Sosial Lanjutan</h2>
            <p class="text-blue-100 text-sm">Mohon lengkapi bagian ini untuk asesmen sosial.</p>
        </div>

        <form method="POST" class="p-8 space-y-8">
            
            <div class="space-y-4">
                <h3 class="font-bold text-slate-700 border-b pb-2">Kondisi Keluarga & Ekonomi</h3>
                <div class="space-y-2">
                    <label class="block text-sm text-slate-600">Q1: Status Orang Tua?</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="q1_status_ortu" value="Lengkap" class="form-radio" required> <span class="text-slate-700">Lengkap</span>
                        <input type="radio" name="q1_status_ortu" value="Bercerai" class="form-radio"> <span class="text-slate-700">Bercerai</span>
                        <input type="radio" name="q1_status_ortu" value="Yatim" class="form-radio"> <span class="text-slate-700">Yatim/Yatim Piatu</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm text-slate-600">Q2: Suasana Rumah?</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="q2_status_ortu" value="Tenang" class="form-radio" required> <span class="text-slate-700">Tenang</span>
                        <input type="radio" name="q2_status_ortu" value="Ramai" class="form-radio"> <span class="text-slate-700">Ramai</span>
                        <input type="radio" name="q2_status_ortu" value="Konflik" class="form-radio"> <span class="text-slate-700">Berkonflik</span>
                        <input type="radio" name="q2_status_ortu" value="Sepi" class="form-radio"> <span class="text-slate-700">Sepi</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm text-slate-600">Q3: Apakah kamu penerima KIP/KKS/PKH?</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="q3_status_ortu" value="Ya" class="form-radio" required> <span class="text-slate-700">Ya</span>
                        <input type="radio" name="q3_status_ortu" value="Tidak" class="form-radio"> <span class="text-slate-700">Tidak</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm text-slate-600">Q4: Transportasi ke sekolah menggunakan apa?</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="q4_status_ortu" value="Ya" class="form-radio" required> <span class="text-slate-700">Jalan Kaki</span>
                        <input type="radio" name="q4_status_ortu" value="Tidak" class="form-radio"> <span class="text-slate-700">Angkot</span>
                        <input type="radio" name="q4_status_ortu" value="Tidak" class="form-radio"> <span class="text-slate-700">Diantar</span>
                        <input type="radio" name="q4_status_ortu" value="Tidak" class="form-radio"> <span class="text-slate-700">Motor Pribadi</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="font-bold text-slate-700 border-b pb-2">Tes Gaya Belajar (VAK - Visual, Auditori, Kinestetik)</h3>
                <div class="space-y-2">
                    <label class="block text-sm text-slate-600">Q1: Kalau ada pelajaran baru, aku lebih cepat paham jika...</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="radio" name="q1_gaya_belajar" value="Visual" class="form-radio" required> <span class="ml-2 text-slate-700">Membaca buku atau melihat slide presentasi guru.</span>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="q1_gaya_belajar" value="Auditori" class="form-radio"> <span class="ml-2 text-slate-700">Mendengarkan penjelasan guru atau diskusi teman.</span>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="q1_gaya_belajar" value="Kinestetik" class="form-radio"> <span class="ml-2 text-slate-700">Mempraktikkannya langsung atau melakukan eksperimen.</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm text-slate-600">Q2: Saat waktu luang, aku lebih suka...</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="radio" name="q2_gaya_belajar" value="Visual" class="form-radio" required> <span class="ml-2 text-slate-700">Nonton film, baca komik/novel, scroll Instagram/TikTok.</span>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="q2_gaya_belajar" value="Auditori" class="form-radio"> <span class="ml-2 text-slate-700">Dengar musik, podcast, atau ngobrol/curhat.</span>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="q2_gaya_belajar" value="Kinestetik" class="form-radio"> <span class="ml-2 text-slate-700">Olahraga, jalan-jalan, gaming, atau bikin kerajinan tangan.</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm text-slate-600">Q3: Kalau aku lupa jalan ke suatu tempat, aku biasanya...</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="radio" name="q3_gaya_belajar" value="Visual" class="form-radio" required> <span class="ml-2 text-slate-700">Membayangkan peta atau patokan gedung yang pernah kulihat.</span>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="q3_gaya_belajar" value="Auditori" class="form-radio"> <span class="ml-2 text-slate-700">Bertanya pada orang lain.</span>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="q3_gaya_belajar" value="Kinestetik" class="form-radio"> <span class="ml-2 text-slate-700">Jalan saja dulu, nanti tubuhku "ingat" sendiri jalannya.</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm text-slate-600">Q4: Saat marah atau kesal, aku biasanya...</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="radio" name="q4_gaya_belajar" value="Visual" class="form-radio" required> <span class="ml-2 text-slate-700">Cemberut, diam, atau menangis (ekspresi wajah berubah).</span>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="q4_gaya_belajar" value="Auditori" class="form-radio"> <span class="ml-2 text-slate-700">Mengomel, teriak, atau curhat panjang lebar.</span>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="q4_gaya_belajar" value="Kinestetik" class="form-radio"> <span class="ml-2 text-slate-700">Membanting pintu, memukul bantal, atau pergi keluar rumah (fisik).</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition">
                    Simpan dan Lanjutkan
                </button>
            </div>

        </form>
    </div>

</body>
</html>
