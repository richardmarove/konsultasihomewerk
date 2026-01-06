<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$user_id = $_SESSION['user_id'];
$res = $conn->query("SELECT id FROM siswa WHERE id_pengguna = '$user_id'");
$row = $res->fetch_assoc();
$id_siswa = $row['id'];

// Proses Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // --- BAGIAN 1: KEPRIBADIAN (Kondisi Keluarga) ---
    $answers_kepribadian = [
        'q1_status_ortu' => $_POST['q1_status_ortu'],
        'q2_status_ortu' => $_POST['q2_status_ortu'],
        'q3_status_ortu' => $_POST['q3_status_ortu'],
        'q4_status_ortu' => $_POST['q4_status_ortu'],
    ];
    $json_kepribadian = json_encode($answers_kepribadian);
    $skor_kepribadian = "-"; // Tidak ada skor khusus untuk bagian ini

    // --- BAGIAN 2: GAYA BELAJAR (VAK) ---
    $answers_gaya_belajar = [
        'q1_gaya_belajar' => $_POST['q1_gaya_belajar'],
        'q2_gaya_belajar' => $_POST['q2_gaya_belajar'],
        'q3_gaya_belajar' => $_POST['q3_gaya_belajar'],
        'q4_gaya_belajar' => $_POST['q4_gaya_belajar'],
    ];
    $json_gaya_belajar = json_encode($answers_gaya_belajar);

    // Hitung Skor VAK
    $vak_scores = [
        'Visual' => 0,
        'Auditori' => 0,
        'Kinestetik' => 0
    ];

    foreach ($answers_gaya_belajar as $key => $value) {
        if (isset($vak_scores[$value])) {
            $vak_scores[$value]++;
        }
    }

    $max_score = max($vak_scores);
    $dominant_styles = array_keys($vak_scores, $max_score);

    if (count($dominant_styles) == 1) {
        $hasil_skor = $dominant_styles[0] . " Dominan";
    } else {
        $hasil_skor = "Kombinasi " . implode(" & ", $dominant_styles);
    }

    // --- INSERT DATABASE (2 Query Terpisah) ---
    
    // 1. Insert Kepribadian
    $sql_kepribadian = "INSERT INTO hasil_asesmen (id_siswa, kategori, ringkasan_hasil, skor) 
                        VALUES ('$id_siswa', 'kepribadian', '$json_kepribadian', '$skor_kepribadian')";
    
    // 2. Insert Gaya Belajar
    $sql_gaya_belajar = "INSERT INTO hasil_asesmen (id_siswa, kategori, ringkasan_hasil, skor) 
                         VALUES ('$id_siswa', 'gaya_belajar', '$json_gaya_belajar', '$hasil_skor')";
    
    // Jalankan Query
    $success_kepribadian = $conn->query($sql_kepribadian);
    $success_gaya_belajar = $conn->query($sql_gaya_belajar);

    if ($success_kepribadian && $success_gaya_belajar) {
        header("Location: modul_asesmen_3.php");
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
            
            <div class="space-y-6">
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2">Bagian 1: Kondisi Keluarga & Ekonomi</h3>
                
                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">Q1: Status Orang Tua?</label>
                    <div class="flex flex-wrap gap-6">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q1_status_ortu" value="Lengkap" class="form-radio text-blue-600" required> <span class="ml-2 text-slate-600">Lengkap</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q1_status_ortu" value="Bercerai" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Bercerai</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q1_status_ortu" value="Yatim" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Yatim/Yatim Piatu</span></label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">Q2: Suasana Rumah?</label>
                    <div class="flex flex-wrap gap-6">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q2_status_ortu" value="Tenang" class="form-radio text-blue-600" required> <span class="ml-2 text-slate-600">Tenang</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q2_status_ortu" value="Ramai" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Ramai</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q2_status_ortu" value="Konflik" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Berkonflik</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q2_status_ortu" value="Sepi" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Sepi</span></label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">Q3: Apakah kamu penerima KIP/KKS/PKH?</label>
                    <div class="flex gap-6">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q3_status_ortu" value="Ya" class="form-radio text-blue-600" required> <span class="ml-2 text-slate-600">Ya</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q3_status_ortu" value="Tidak" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Tidak</span></label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">Q4: Transportasi ke sekolah menggunakan apa?</label>
                    <div class="flex flex-wrap gap-6">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q4_status_ortu" value="Jalan Kaki" class="form-radio text-blue-600" required> <span class="ml-2 text-slate-600">Jalan Kaki</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q4_status_ortu" value="Angkot" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Angkot</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q4_status_ortu" value="Diantar" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Diantar</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q4_status_ortu" value="Motor Pribadi" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Motor Pribadi</span></label>
                    </div>
                </div>
            </div>

            <div class="space-y-6 pt-6 border-t">
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2">Bagian 2: Tes Gaya Belajar (VAK - Visual, Auditori, Kinestetik)</h3>
                
                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">Q1: Kalau ada pelajaran baru, aku lebih cepat paham jika...</label>
                    <div class="flex flex-col space-y-2">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q1_gaya_belajar" value="Visual" class="form-radio text-blue-600" required> <span class="ml-2 text-slate-600">Membaca buku atau melihat slide presentasi guru.</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q1_gaya_belajar" value="Auditori" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Mendengarkan penjelasan guru atau diskusi teman.</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q1_gaya_belajar" value="Kinestetik" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Mempraktikkannya langsung atau melakukan eksperimen.</span></label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">Q2: Saat waktu luang, aku lebih suka...</label>
                    <div class="flex flex-col space-y-2">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q2_gaya_belajar" value="Visual" class="form-radio text-blue-600" required> <span class="ml-2 text-slate-600">Nonton film, baca komik/novel, scroll Instagram/TikTok.</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q2_gaya_belajar" value="Auditori" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Dengar musik, podcast, atau ngobrol/curhat.</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q2_gaya_belajar" value="Kinestetik" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Olahraga, jalan-jalan, gaming, atau bikin kerajinan tangan.</span></label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">Q3: Kalau aku lupa jalan ke suatu tempat, aku biasanya...</label>
                    <div class="flex flex-col space-y-2">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q3_gaya_belajar" value="Visual" class="form-radio text-blue-600" required> <span class="ml-2 text-slate-600">Membayangkan peta atau patokan gedung yang pernah kulihat.</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q3_gaya_belajar" value="Auditori" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Bertanya pada orang lain.</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q3_gaya_belajar" value="Kinestetik" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Jalan saja dulu, nanti tubuhku "ingat" sendiri jalannya.</span></label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">Q4: Saat marah atau kesal, aku biasanya...</label>
                    <div class="flex flex-col space-y-2">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q4_gaya_belajar" value="Visual" class="form-radio text-blue-600" required> <span class="ml-2 text-slate-600">Cemberut, diam, atau menangis (ekspresi wajah berubah).</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q4_gaya_belajar" value="Auditori" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Mengomel, teriak, atau curhat panjang lebar.</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q4_gaya_belajar" value="Kinestetik" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Membanting pintu, memukul bantal, atau pergi keluar rumah (fisik).</span></label>
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition shadow-lg">
                    Simpan dan Lanjutkan
                </button>
            </div>

        </form>
    </div>

</body>
</html>
