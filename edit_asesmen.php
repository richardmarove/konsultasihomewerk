<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$user_id = $_SESSION['user_id'];
$res = $conn->query("SELECT id FROM siswa WHERE id_pengguna = '$user_id'");
$row = $res->fetch_assoc();
$id_siswa = $row['id'];


// Data Keluarga
$sql_keluarga = "SELECT * FROM detail_keluarga_siswa WHERE id_siswa = '$id_siswa'";
$res_keluarga = $conn->query($sql_keluarga);
$data_keluarga = $res_keluarga->fetch_assoc();

// Data Asesmen (Looping untuk ambil semua kategori)
$sql_asesmen = "SELECT kategori, ringkasan_hasil FROM hasil_asesmen WHERE id_siswa = '$id_siswa'";
$res_asesmen = $conn->query($sql_asesmen);

$data_asesmen = [];
while ($row_asesmen = $res_asesmen->fetch_assoc()) {
    $data_asesmen[$row_asesmen['kategori']] = json_decode($row_asesmen['ringkasan_hasil'], true);
}

// Helper function untuk ambil value aman (biar gak error undefined index)
function getVal($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

// Helper buat checkbox checked
function isChecked($array, $key, $value) {
    if (!isset($array[$key])) return '';
    if (is_array($array[$key])) {
        return in_array($value, $array[$key]) ? 'checked' : '';
    }
    return $array[$key] == $value ? 'checked' : '';
}

// Proses Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Update Data Keluarga
    $nama_ayah      = $_POST['nama_ayah'];
    $pekerjaan_ayah = $_POST['pekerjaan_ayah'];
    $nama_ibu       = $_POST['nama_ibu'];
    $pekerjaan_ibu  = $_POST['pekerjaan_ibu'];
    $ekonomi        = $_POST['status_ekonomi'];
    $saudara        = $_POST['jumlah_saudara'];
    $alamat         = $_POST['alamat'];

    // Cek apakah data keluarga sudah ada sebelumnya atau belum (untuk handle insert/update)
    // Tapi asumsi fitur ini untuk edit, jadi kita pakai UPDATE. 
    // Jika user belum pernah isi, logic ini mungkin perlu INSERT. 
    // Untuk aman, kita pakai INSERT ... ON DUPLICATE KEY UPDATE atau cek dulu.
    // Sederhananya kita cek $data_keluarga tadi.
    
    if ($data_keluarga) {
        $sql_update_keluarga = "UPDATE detail_keluarga_siswa SET 
            nama_ayah='$nama_ayah', pekerjaan_ayah='$pekerjaan_ayah', 
            nama_ibu='$nama_ibu', pekerjaan_ibu='$pekerjaan_ibu', 
            status_ekonomi='$ekonomi', jumlah_saudara='$saudara', alamat='$alamat' 
            WHERE id_siswa='$id_siswa'";
        $conn->query($sql_update_keluarga);
    } else {
        $sql_insert_keluarga = "INSERT INTO detail_keluarga_siswa 
            (id_siswa, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, status_ekonomi, jumlah_saudara, alamat) 
            VALUES 
            ('$id_siswa', '$nama_ayah', '$pekerjaan_ayah', '$nama_ibu', '$pekerjaan_ibu', '$ekonomi', '$saudara', '$alamat')";
        $conn->query($sql_insert_keluarga);
    }

    // Update Kepribadian (Kondisi Keluarga)
    $answers_kepribadian = [
        'q1_status_ortu' => $_POST['q1_status_ortu'],
        'q2_status_ortu' => $_POST['q2_status_ortu'],
        'q3_status_ortu' => $_POST['q3_status_ortu'],
        'q4_status_ortu' => $_POST['q4_status_ortu'],
    ];
    $json_kepribadian = json_encode($answers_kepribadian);
    // Update or Insert
    $conn->query("DELETE FROM hasil_asesmen WHERE id_siswa='$id_siswa' AND kategori='kepribadian'");
    $conn->query("INSERT INTO hasil_asesmen (id_siswa, kategori, ringkasan_hasil, skor) VALUES ('$id_siswa', 'kepribadian', '$json_kepribadian', '-')");

    // Update Gaya Belajar (VAK)
    $answers_gaya_belajar = [
        'q1_gaya_belajar' => $_POST['q1_gaya_belajar'],
        'q2_gaya_belajar' => $_POST['q2_gaya_belajar'],
        'q3_gaya_belajar' => $_POST['q3_gaya_belajar'],
        'q4_gaya_belajar' => $_POST['q4_gaya_belajar'],
    ];
    $json_gaya_belajar = json_encode($answers_gaya_belajar);
    
    // Hitung Skor VAK Ulang
    $vak_scores = ['Visual' => 0, 'Auditori' => 0, 'Kinestetik' => 0];
    foreach ($answers_gaya_belajar as $val) { if (isset($vak_scores[$val])) $vak_scores[$val]++; }
    $max_score = max($vak_scores);
    $dominant_styles = array_keys($vak_scores, $max_score);
    $hasil_skor_vak = (count($dominant_styles) == 1) ? $dominant_styles[0] . " Dominan" : "Kombinasi " . implode(" & ", $dominant_styles);

    $conn->query("DELETE FROM hasil_asesmen WHERE id_siswa='$id_siswa' AND kategori='gaya_belajar'");
    $conn->query("INSERT INTO hasil_asesmen (id_siswa, kategori, ringkasan_hasil, skor) VALUES ('$id_siswa', 'gaya_belajar', '$json_gaya_belajar', '$hasil_skor_vak')");

    // Update Kesehatan Mental
    $answers_mental = [
        'q1_nyaman_teman' => $_POST['q1'],
        'q2_cemas' => $_POST['q2'],
        'q3_cerita' => $_POST['q3'],
        'q4_tekanan_akademik' => $_POST['q4'],
        'q5_bullying' => $_POST['q5']
    ];
    $json_mental = json_encode($answers_mental);
    $skor_mental = ($answers_mental['q5_bullying'] == 'Ya') ? "PERLU PERHATIAN KHUSUS (Bullying)" : "Stabil";

    $conn->query("DELETE FROM hasil_asesmen WHERE id_siswa='$id_siswa' AND kategori='kesehatan_mental'");
    $conn->query("INSERT INTO hasil_asesmen (id_siswa, kategori, ringkasan_hasil, skor) VALUES ('$id_siswa', 'kesehatan_mental', '$json_mental', '$skor_mental')");

    // Update Minat Karir
    $answers_karir = [
        'rencana_lulus' => $_POST['karir_q1'],
        'mapel_favorit' => isset($_POST['karir_q2']) ? $_POST['karir_q2'] : [],
        'minat_pekerjaan' => $_POST['karir_q3']
    ];
    $json_karir = json_encode($answers_karir);
    $skor_karir = $answers_karir['rencana_lulus'];

    $conn->query("DELETE FROM hasil_asesmen WHERE id_siswa='$id_siswa' AND kategori='minat_karir'");
    $conn->query("INSERT INTO hasil_asesmen (id_siswa, kategori, ringkasan_hasil, skor) VALUES ('$id_siswa', 'minat_karir', '$json_karir', '$skor_karir')");

    header("Location: dashboard_siswa.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit Data Asesmen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <style>
        .lexend-font { font-family: "Lexend", sans-serif; }
    </style>
    <script>
        function validateCheckbox() {
            var checkboxes = document.querySelectorAll('input[name="karir_q2[]"]');
            var count = 0;
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) count++;
            }
            if (count > 3) { alert("Maksimal pilih 3 mata pelajaran favorit."); return false; }
            if (count === 0) { alert("Pilih setidaknya 1 mata pelajaran favorit."); return false; }
            return true;
        }
    </script>
</head>
<body class="bg-slate-50 py-10 lexend-font">

    <div class="max-w-4xl mx-auto bg-white shadow-xl rounded-xl overflow-hidden">
        <div class="bg-teal-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Edit Data Asesmen</h2>
                <p class="text-teal-100 text-sm">Perbarui informasi Anda jika ada perubahan.</p>
            </div>
            <a href="dashboard_siswa.php" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded transition text-sm">Kembali</a>
        </div>

        <form method="POST" class="p-8 space-y-8" onsubmit="return validateCheckbox()">
            
            <!-- SECTION 1: Data Keluarga -->
            <section class="space-y-6">
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2">1. Data Keluarga & Ekonomi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Nama Ayah</label>
                        <input type="text" name="nama_ayah" value="<?= getVal($data_keluarga, 'nama_ayah') ?>" required class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Pekerjaan Ayah</label>
                        <input type="text" name="pekerjaan_ayah" value="<?= getVal($data_keluarga, 'pekerjaan_ayah') ?>" required class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Nama Ibu</label>
                        <input type="text" name="nama_ibu" value="<?= getVal($data_keluarga, 'nama_ibu') ?>" required class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Pekerjaan Ibu</label>
                        <input type="text" name="pekerjaan_ibu" value="<?= getVal($data_keluarga, 'pekerjaan_ibu') ?>" required class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Status Ekonomi</label>
                        <select name="status_ekonomi" class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                            <option value="Mampu" <?= getVal($data_keluarga, 'status_ekonomi') == 'Mampu' ? 'selected' : '' ?>>Mampu</option>
                            <option value="Cukup" <?= getVal($data_keluarga, 'status_ekonomi') == 'Cukup' ? 'selected' : '' ?>>Cukup</option>
                            <option value="Kurang Mampu" <?= getVal($data_keluarga, 'status_ekonomi') == 'Kurang Mampu' ? 'selected' : '' ?>>Kurang Mampu</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Jumlah Saudara</label>
                        <input type="number" name="jumlah_saudara" value="<?= getVal($data_keluarga, 'jumlah_saudara') ?>" required class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                    </div>
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-slate-700 font-medium">Alamat Lengkap</label>
                        <textarea name="alamat" rows="2" required class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2"><?= getVal($data_keluarga, 'alamat') ?></textarea>
                    </div>
                </div>
            </section>

            <!-- SECTION 2: Kondisi Keluarga -->
            <?php $kep = getVal($data_asesmen, 'kepribadian', []); ?>
            <section class="space-y-6 pt-6 border-t">
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2">2. Kondisi Keluarga (Sosial)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Status Orang Tua</label>
                        <select name="q1_status_ortu" class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                            <option value="Lengkap" <?= isChecked($kep, 'q1_status_ortu', 'Lengkap') ? 'selected' : '' ?>>Lengkap</option>
                            <option value="Bercerai" <?= isChecked($kep, 'q1_status_ortu', 'Bercerai') ? 'selected' : '' ?>>Bercerai</option>
                            <option value="Yatim" <?= isChecked($kep, 'q1_status_ortu', 'Yatim') ? 'selected' : '' ?>>Yatim/Piatu</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Suasana Rumah</label>
                        <select name="q2_status_ortu" class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                            <option value="Tenang" <?= isChecked($kep, 'q2_status_ortu', 'Tenang') ? 'selected' : '' ?>>Tenang</option>
                            <option value="Ramai" <?= isChecked($kep, 'q2_status_ortu', 'Ramai') ? 'selected' : '' ?>>Ramai</option>
                            <option value="Konflik" <?= isChecked($kep, 'q2_status_ortu', 'Konflik') ? 'selected' : '' ?>>Berkonflik</option>
                            <option value="Sepi" <?= isChecked($kep, 'q2_status_ortu', 'Sepi') ? 'selected' : '' ?>>Sepi</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Penerima KIP/KKS?</label>
                        <div class="flex gap-6 mt-2">
                             <label class="flex items-center cursor-pointer"><input type="radio" name="q3_status_ortu" value="Ya" class="form-radio text-teal-600" <?= isChecked($kep, 'q3_status_ortu', 'Ya') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Ya</span></label>
                             <label class="flex items-center cursor-pointer"><input type="radio" name="q3_status_ortu" value="Tidak" class="form-radio text-teal-600" <?= isChecked($kep, 'q3_status_ortu', 'Tidak') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Tidak</span></label>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Transportasi</label>
                        <select name="q4_status_ortu" class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                            <option value="Jalan Kaki" <?= isChecked($kep, 'q4_status_ortu', 'Jalan Kaki') ? 'selected' : '' ?>>Jalan Kaki</option>
                            <option value="Angkot" <?= isChecked($kep, 'q4_status_ortu', 'Angkot') ? 'selected' : '' ?>>Angkot</option>
                            <option value="Diantar" <?= isChecked($kep, 'q4_status_ortu', 'Diantar') ? 'selected' : '' ?>>Diantar</option>
                            <option value="Motor Pribadi" <?= isChecked($kep, 'q4_status_ortu', 'Motor Pribadi') ? 'selected' : '' ?>>Motor Pribadi</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- SECTION 3: Gaya Belajar -->
            <?php $gb = getVal($data_asesmen, 'gaya_belajar', []); ?>
            <section class="space-y-6 pt-6 border-t">
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2">3. Gaya Belajar (VAK)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Cara belajar paling cepat?</label>
                        <select name="q1_gaya_belajar" class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                            <option value="Visual" <?= isChecked($gb, 'q1_gaya_belajar', 'Visual') ? 'selected' : '' ?>>Membaca/Melihat</option>
                            <option value="Auditori" <?= isChecked($gb, 'q1_gaya_belajar', 'Auditori') ? 'selected' : '' ?>>Mendengar/Diskusi</option>
                            <option value="Kinestetik" <?= isChecked($gb, 'q1_gaya_belajar', 'Kinestetik') ? 'selected' : '' ?>>Praktik</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Aktivitas waktu luang?</label>
                        <select name="q2_gaya_belajar" class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                            <option value="Visual" <?= isChecked($gb, 'q2_gaya_belajar', 'Visual') ? 'selected' : '' ?>>Nonton/Baca</option>
                            <option value="Auditori" <?= isChecked($gb, 'q2_gaya_belajar', 'Auditori') ? 'selected' : '' ?>>Musik/Ngobrol</option>
                            <option value="Kinestetik" <?= isChecked($gb, 'q2_gaya_belajar', 'Kinestetik') ? 'selected' : '' ?>>Olahraga/Gaming</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Cara mengingat jalan?</label>
                        <select name="q3_gaya_belajar" class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                            <option value="Visual" <?= isChecked($gb, 'q3_gaya_belajar', 'Visual') ? 'selected' : '' ?>>Bayangkan Peta</option>
                            <option value="Auditori" <?= isChecked($gb, 'q3_gaya_belajar', 'Auditori') ? 'selected' : '' ?>>Tanya Orang</option>
                            <option value="Kinestetik" <?= isChecked($gb, 'q3_gaya_belajar', 'Kinestetik') ? 'selected' : '' ?>>Jalan Saja</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Ekspresi saat marah?</label>
                        <select name="q4_gaya_belajar" class="w-full border-slate-300 focus:border-teal-500 focus:ring-teal-500 rounded-lg shadow-sm border p-2">
                            <option value="Visual" <?= isChecked($gb, 'q4_gaya_belajar', 'Visual') ? 'selected' : '' ?>>Diam/Cemberut</option>
                            <option value="Auditori" <?= isChecked($gb, 'q4_gaya_belajar', 'Auditori') ? 'selected' : '' ?>>Ngomel/Teriak</option>
                            <option value="Kinestetik" <?= isChecked($gb, 'q4_gaya_belajar', 'Kinestetik') ? 'selected' : '' ?>>Banting Barang/Pergi</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- SECTION 4: Kesehatan Mental -->
            <?php $km = getVal($data_asesmen, 'kesehatan_mental', []); ?>
            <section class="space-y-6 pt-6 border-t">
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2">4. Kesehatan Mental</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Nyaman & punya teman?</label>
                        <div class="flex gap-6 mt-2">
                            <label class="flex items-center cursor-pointer"><input type="radio" name="q1" value="Ya" class="form-radio text-teal-600" <?= isChecked($km, 'q1_nyaman_teman', 'Ya') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Ya</span></label>
                            <label class="flex items-center cursor-pointer"><input type="radio" name="q1" value="Tidak" class="form-radio text-teal-600" <?= isChecked($km, 'q1_nyaman_teman', 'Tidak') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Tidak</span></label>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Cemas berlebihan?</label>
                        <div class="flex gap-6 mt-2">
                            <label class="flex items-center cursor-pointer"><input type="radio" name="q2" value="Ya" class="form-radio text-teal-600" <?= isChecked($km, 'q2_cemas', 'Ya') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Ya</span></label>
                            <label class="flex items-center cursor-pointer"><input type="radio" name="q2" value="Tidak" class="form-radio text-teal-600" <?= isChecked($km, 'q2_cemas', 'Tidak') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Tidak</span></label>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Mudah bercerita?</label>
                        <div class="flex gap-6 mt-2">
                            <label class="flex items-center cursor-pointer"><input type="radio" name="q3" value="Ya" class="form-radio text-teal-600" <?= isChecked($km, 'q3_cerita', 'Ya') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Ya</span></label>
                            <label class="flex items-center cursor-pointer"><input type="radio" name="q3" value="Tidak" class="form-radio text-teal-600" <?= isChecked($km, 'q3_cerita', 'Tidak') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Tidak</span></label>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-medium">Tertekan akademik?</label>
                        <div class="flex gap-6 mt-2">
                           <label class="flex items-center cursor-pointer"><input type="radio" name="q4" value="Ya" class="form-radio text-teal-600" <?= isChecked($km, 'q4_tekanan_akademik', 'Ya') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Ya</span></label>
                           <label class="flex items-center cursor-pointer"><input type="radio" name="q4" value="Tidak" class="form-radio text-teal-600" <?= isChecked($km, 'q4_tekanan_akademik', 'Tidak') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Tidak</span></label>
                        </div>
                    </div>
                    <div class="md:col-span-2 bg-red-50 p-4 rounded-lg border border-red-100 mt-2">
                        <label class="block text-red-800 font-bold mb-2">Pernah mengalami Bullying?</label>
                        <div class="flex gap-6">
                            <label class="flex items-center cursor-pointer"><input type="radio" name="q5" value="Ya" class="form-radio text-red-600" <?= isChecked($km, 'q5_bullying', 'Ya') ? 'checked' : '' ?>> <span class="ml-2 text-red-700 font-medium">Ya</span></label>
                            <label class="flex items-center cursor-pointer"><input type="radio" name="q5" value="Tidak" class="form-radio text-red-600" <?= isChecked($km, 'q5_bullying', 'Tidak') ? 'checked' : '' ?>> <span class="ml-2 text-red-700 font-medium">Tidak</span></label>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 5: Minat Karir -->
            <?php $mk = getVal($data_asesmen, 'minat_karir', []); ?>
            <section class="space-y-6 pt-6 border-t">
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2">5. Minat Karir</h3>
                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">Rencana setelah lulus sekolah?</label>
                    <div class="flex flex-col space-y-2 mt-2">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q1" value="Kuliah" class="form-radio text-teal-600" <?= isChecked($mk, 'rencana_lulus', 'Kuliah') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Kuliah</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q1" value="Kerja/Wirausaha" class="form-radio text-teal-600" <?= isChecked($mk, 'rencana_lulus', 'Kerja/Wirausaha') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Kerja/Wirausaha</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q1" value="Sekolah Kedinasan" class="form-radio text-teal-600" <?= isChecked($mk, 'rencana_lulus', 'Sekolah Kedinasan') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Sekolah Kedinasan</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q1" value="Belum Tahu/Bingung" class="form-radio text-teal-600" <?= isChecked($mk, 'rencana_lulus', 'Belum Tahu/Bingung') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Belum Tahu/Bingung</span></label>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">Mata Pelajaran Favorit (Pilih maks 3)</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-2">
                        <?php 
                        $mapel_opts = ["Matematika", "Olahraga", "KK", "Bahasa Indonesia", "Agama", "MPP", "Bahasa Inggris"];
                        foreach ($mapel_opts as $m) {
                            $checked = isChecked($mk, 'mapel_favorit', $m);
                            echo "<label class='flex items-center space-x-2 cursor-pointer bg-slate-50 p-2 rounded hover:bg-slate-100 transition'><input type='checkbox' name='karir_q2[]' value='$m' $checked class='rounded text-teal-600 form-checkbox'> <span class='text-slate-700'>$m</span></label>";
                        }
                        ?>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-700 font-medium">Bidang Pekerjaan Diminati</label>
                    <div class="flex flex-col space-y-2 mt-2">
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q3" value="Teknik & Komputer" class="form-radio text-teal-600" <?= isChecked($mk, 'minat_pekerjaan', 'Teknik & Komputer') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Teknik & Komputer</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q3" value="Kesehatan" class="form-radio text-teal-600" <?= isChecked($mk, 'minat_pekerjaan', 'Kesehatan') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Kesehatan</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q3" value="Seni & Kreatif" class="form-radio text-teal-600" <?= isChecked($mk, 'minat_pekerjaan', 'Seni & Kreatif') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Seni & Kreatif</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q3" value="Sosial & Hukum" class="form-radio text-teal-600" <?= isChecked($mk, 'minat_pekerjaan', 'Sosial & Hukum') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Sosial & Hukum</span></label>
                        <label class="flex items-center cursor-pointer"><input type="radio" name="karir_q3" value="Bisnis & Manajemen" class="form-radio text-teal-600" <?= isChecked($mk, 'minat_pekerjaan', 'Bisnis & Manajemen') ? 'checked' : '' ?>> <span class="ml-2 text-slate-600">Bisnis & Manajemen</span></label>
                    </div>
                </div>
            </section>

            <div class="pt-6 border-t">
                <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 rounded-lg transition shadow-lg transform hover:-translate-y-0.5">
                    Simpan Perubahan
                </button>
            </div>

        </form>
    </div>

</body>
</html>
