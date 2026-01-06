<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$user_id = $_SESSION['user_id'];
$res = $conn->query("SELECT id FROM siswa WHERE id_pengguna = '$user_id'");
$row = $res->fetch_assoc();
$id_siswa = $row['id'];

// Cek kalo modul sebelumnya udah complete atau belum
$sql_check_prev = "SELECT id FROM hasil_asesmen WHERE id_siswa = '$id_siswa' AND kategori = 'gaya_belajar'";
$check_prev = $conn->query($sql_check_prev);

if ($check_prev->num_rows == 0) {
    header("Location: modul_asesmen_2.php");
    exit;
}

// Proses Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Bagian Kesehatan Mental
    $answers_mental = [
        'q1_nyaman_teman' => $_POST['q1'],
        'q2_cemas' => $_POST['q2'],
        'q3_cerita' => $_POST['q3'],
        'q4_tekanan_akademik' => $_POST['q4'],
        'q5_bullying' => $_POST['q5']
    ];
    $json_mental = json_encode($answers_mental);

    $skor_mental = "Stabil";
    if ($answers_mental['q5_bullying'] == 'Ya') {
        $skor_mental = "PERLU PERHATIAN KHUSUS (Bullying)";
    }

    // Bagian Minat Karir
    $answers_karir = [
        'rencana_lulus' => $_POST['karir_q1'],
        'mapel_favorit' => isset($_POST['karir_q2']) ? $_POST['karir_q2'] : [], // Array
        'minat_pekerjaan' => $_POST['karir_q3']
    ];
    $json_karir = json_encode($answers_karir);
    
    // Skor Karir (Simpan Rencana Lulus sebagai 'skor' ringkas, atau string kosong)
    $skor_karir = $answers_karir['rencana_lulus'];

    // Insert ke database
    $sql_mental = "INSERT INTO hasil_asesmen (id_siswa, kategori, ringkasan_hasil, skor) 
                   VALUES ('$id_siswa', 'kesehatan_mental', '$json_mental', '$skor_mental')";
    
    $sql_karir = "INSERT INTO hasil_asesmen (id_siswa, kategori, ringkasan_hasil, skor) 
                  VALUES ('$id_siswa', 'minat_karir', '$json_karir', '$skor_karir')";

    $success_mental = $conn->query($sql_mental);
    $success_karir = $conn->query($sql_karir);

    if ($success_mental && $success_karir) {
        header("Location: index.php");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Asesmen Sensitif</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <style>
        .lexend-font { font-family: "Lexend", sans-serif; }
    </style>
    <script>
        function validateCheckbox() {
            var checkboxes = document.querySelectorAll('input[name="karir_q2[]"]');
            var checkedOne = Array.prototype.slice.call(checkboxes).some(x => x.checked);
            
            // Count checked
            var count = 0;
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) count++;
            }

            if (count > 3) {
                alert("Maksimal pilih 3 mata pelajaran favorit.");
                return false;
            }
            if (count === 0) {
                alert("Pilih setidaknya 1 mata pelajaran favorit.");
                return false;
            }
            return true;
        }
    </script>
</head>
<body class="bg-slate-50 py-10 lexend-font">

    <div class="max-w-3xl mx-auto bg-white shadow-xl rounded-xl overflow-hidden">
        <div class="bg-teal-600 p-6 text-white">
            <h2 class="text-xl font-bold">Asesmen Sensitif</h2>
            <p class="text-teal-100 text-sm">Mohon lengkapi kedua bagian asesmen ini dengan jujur.</p>
        </div>

        <form method="POST" class="p-8 space-y-8" onsubmit="return validateCheckbox()">
            
            <div class="space-y-6">
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2">Bagian 1: Kesehatan Mental</h3>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">1. Saya merasa nyaman dan punya teman di sekolah.</label>
                    <div class="flex gap-6">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q1" value="Ya" class="form-radio text-teal-600" required> <span class="ml-2 text-slate-600">Ya</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q1" value="Tidak" class="form-radio text-teal-600"> <span class="ml-2 text-slate-600">Tidak</span></label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">2. Saya sering merasa cemas berlebihan tanpa sebab yang jelas.</label>
                    <div class="flex gap-6">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q2" value="Ya" class="form-radio text-teal-600" required> <span class="ml-2 text-slate-600">Ya</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q2" value="Tidak" class="form-radio text-teal-600"> <span class="ml-2 text-slate-600">Tidak</span></label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">3. Saya merasa mudah untuk bercerita masalah saya ke orang lain.</label>
                    <div class="flex gap-6">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q3" value="Ya" class="form-radio text-teal-600" required> <span class="ml-2 text-slate-600">Ya</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q3" value="Tidak" class="form-radio text-teal-600"> <span class="ml-2 text-slate-600">Tidak</span></label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">4. Saya merasa tertekan dengan tuntutan nilai akademik.</label>
                    <div class="flex gap-6">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q4" value="Ya" class="form-radio text-teal-600" required> <span class="ml-2 text-slate-600">Ya</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q4" value="Tidak" class="form-radio text-teal-600"> <span class="ml-2 text-slate-600">Tidak</span></label>
                    </div>
                </div>

                <div class="space-y-2 bg-red-50 p-4 rounded-lg border border-red-100">
                    <label class="block text-red-800 font-bold">5. Saya pernah atau sedang mengalami perundungan (bullying) di sekolah.</label>
                    <div class="flex gap-6 mt-2">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q5" value="Ya" class="form-radio text-red-600" required> <span class="ml-2 text-red-700 font-medium">Ya</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="q5" value="Tidak" class="form-radio text-red-600"> <span class="ml-2 text-red-700 font-medium">Tidak</span></label>
                    </div>
                </div>
            </div>

            <div class="space-y-6 pt-6 border-t">
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2">Bagian 2: Perencanaan Karir</h3>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">1. Rencana setelah lulus sekolah?</label>
                    <div class="flex flex-col space-y-2">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q1" value="Kuliah" class="form-radio text-blue-600" required> <span class="ml-2 text-slate-600">Kuliah</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q1" value="Kerja/Wirausaha" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Kerja/Wirausaha</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q1" value="Sekolah Kedinasan" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Sekolah Kedinasan</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q1" value="Belum Tahu/Bingung" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Belum Tahu/Bingung</span></label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">2. Mata Pelajaran Favorit (Pilih maks 3)</label>
                    <div class="flex flex-col space-y-2">
                        <label class="flex items-center cursor-pointer"><input type="checkbox" name="karir_q2[]" value="Matematika" class="form-checkbox text-blue-600"> <span class="ml-2 text-slate-600">Matematika</span></label>
                        <label class="flex items-center cursor-pointer"><input type="checkbox" name="karir_q2[]" value="Olahraga" class="form-checkbox text-blue-600"> <span class="ml-2 text-slate-600">Olahraga</span></label>
                        <label class="flex items-center cursor-pointer"><input type="checkbox" name="karir_q2[]" value="KK" class="form-checkbox text-blue-600"> <span class="ml-2 text-slate-600">KK (Kejuruan)</span></label>
                        <label class="flex items-center cursor-pointer"><input type="checkbox" name="karir_q2[]" value="Bahasa Indonesia" class="form-checkbox text-blue-600"> <span class="ml-2 text-slate-600">Bahasa Indonesia</span></label>
                        <label class="flex items-center cursor-pointer"><input type="checkbox" name="karir_q2[]" value="Agama" class="form-checkbox text-blue-600"> <span class="ml-2 text-slate-600">Agama</span></label>
                        <label class="flex items-center cursor-pointer"><input type="checkbox" name="karir_q2[]" value="MPP" class="form-checkbox text-blue-600"> <span class="ml-2 text-slate-600">MPP</span></label>
                        <label class="flex items-center cursor-pointer"><input type="checkbox" name="karir_q2[]" value="Bahasa Inggris" class="form-checkbox text-blue-600"> <span class="ml-2 text-slate-600">Bahasa Inggris</span></label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">3. Bidang pekerjaan yang menarik minatmu?</label>
                    <div class="flex flex-col space-y-2">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q3" value="Teknik & Komputer" class="form-radio text-blue-600" required> <span class="ml-2 text-slate-600">Teknik & Komputer</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q3" value="Kesehatan" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Kesehatan</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q3" value="Seni & Kreatif" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Seni & Kreatif</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q3" value="Sosial & Hukum" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Sosial & Hukum</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q3" value="Bisnis & Manajemen" class="form-radio text-blue-600"> <span class="ml-2 text-slate-600">Bisnis & Manajemen</span></label>
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 rounded transition shadow-lg">
                    Selesai & Simpan Semua
                </button>
            </div>

        </form>
    </div>

</body>
</html>
