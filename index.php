<?php
session_start();
include 'config/database.php';

// Check if user is logged in as student
$is_logged_in = isset($_SESSION['user_id']) && $_SESSION['peran'] == 'siswa';

// Initialize variables for locked state
$student = ['nama_lengkap' => 'Siswa'];
$id_siswa = null;
$vak_data = null;
$vak_counts = ['Visual' => 0, 'Auditori' => 0, 'Kinestetik' => 0];
$dominant_desc = "Login untuk melihat hasil asesmenmu.";
$dominant_title = "-";
$res_schedule = null;
$res_pending = null;
$list_konselor = null;

// Only fetch student data if logged in
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];

    $sql_student = "SELECT id, nama_lengkap FROM siswa WHERE id_pengguna = '$user_id'";
    $res_student = $conn->query($sql_student);
    $student = $res_student->fetch_assoc();

    if (!$student) {
        die("Data siswa tidak ditemukan. Pastikan akun user sudah terhubung ke tabel siswa.");
    }

    $id_siswa = $student['id'];

    $sql_check = "SELECT * FROM detail_keluarga_siswa WHERE id_siswa = '$id_siswa'";
    $check_res = $conn->query($sql_check);

    if ($check_res->num_rows == 0) {
        header("Location: modul_asesmen.php");
        exit;
    }

    $sql_vak = "SELECT ringkasan_hasil, skor FROM hasil_asesmen WHERE id_siswa = '$id_siswa' AND kategori = 'gaya_belajar' ORDER BY id DESC LIMIT 1";
    $res_vak = $conn->query($sql_vak);
    $vak_data = $res_vak->fetch_assoc();

    $vak_counts = ['Visual' => 0, 'Auditori' => 0, 'Kinestetik' => 0];
    $dominant_desc = "Belum ada data";
    $dominant_title = "-";

    if ($vak_data) {
        $answers = json_decode($vak_data['ringkasan_hasil'], true);
        if ($answers) {
            foreach ($answers as $ans) {
                if (isset($vak_counts[$ans])) {
                    $vak_counts[$ans]++;
                }
            }
        }
        
        $max_score = max($vak_counts);
        $dominant_styles = array_keys($vak_counts, $max_score);
        
        if (count($dominant_styles) == 1) {
            $dominant_title = $dominant_styles[0];
        } else {
            $dominant_title = "Kombinasi (" . implode(" & ", $dominant_styles) . ")";
        }

        // Deskripsi Hardcoded
        $descriptions = [
            'Visual' => "Kamu adalah tipe Visual! Kamu lebih mudah memahami sesuatu dengan melihat gambar, grafik, atau membaca buku. Warna dan tata letak yang rapi sangat membantumu belajar.",
            'Auditori' => "Kamu adalah tipe Auditori! Kamu lebih suka mendengarkan penjelasan guru, berdiskusi, atau belajar sambil mendengarkan musik. Suara dan intonasi sangat penting bagimu.",
            'Kinestetik' => "Kamu adalah tipe Kinestetik! Kamu belajar paling baik dengan melakukan langsung, praktik, atau bergerak. Kamu mungkin sulit duduk diam terlalu lama saat belajar."
        ];

        if (count($dominant_styles) == 1) {
            $dominant_desc = $descriptions[$dominant_styles[0]];
        } else {
            $dominant_desc = "Kamu memiliki gaya belajar kombinasi! Ini berarti kamu fleksibel dan bisa menggunakan beberapa cara belajar sekaligus untuk memahami materi dengan lebih baik.";
        }
    }

    $sql_schedule = "
        SELECT k.*, c.nama_lengkap as nama_konselor 
        FROM konsultasi k 
        JOIN konselor c ON k.id_konselor = c.id 
        WHERE k.id_siswa = '$id_siswa' AND k.status = 'disetujui' AND k.tanggal_konsultasi >= NOW() 
        ORDER BY k.tanggal_konsultasi ASC
    ";
    $res_schedule = $conn->query($sql_schedule);

    // Pending requests
    $sql_pending = "
        SELECT k.*, c.nama_lengkap as nama_konselor 
        FROM konsultasi k 
        JOIN konselor c ON k.id_konselor = c.id 
        WHERE k.id_siswa = '$id_siswa' AND k.status = 'menunggu' 
        ORDER BY k.created_at DESC
    ";
    $res_pending = $conn->query($sql_pending);

    $list_konselor = $conn->query("SELECT id, nama_lengkap FROM konselor");
}

// Proses Konsultasi (only if logged in and POST)
$msg_konsul = "";
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_konsul'])) {
    $id_konselor = $_POST['id_konselor'];
    $topik = $_POST['topik'];
    $keluhan = $_POST['keluhan'];
    $tanggal = $_POST['tanggal'];
    $jam = $_POST['jam'];
    $metode = $_POST['metode'];
    
    $tgl_waktu = $tanggal . ' ' . $jam . ':00';

    $stmt = $conn->prepare("INSERT INTO konsultasi (id_siswa, id_konselor, kategori_topik, deskripsi_keluhan, tanggal_konsultasi, status, metode_konsultasi) VALUES (?, ?, ?, ?, ?, 'menunggu', ?)");
    $stmt->bind_param("iissss", $id_siswa, $id_konselor, $topik, $keluhan, $tgl_waktu, $metode);
    
    if ($stmt->execute()) {
        $msg_konsul = "<div class='bg-green-100 text-green-700 p-4 rounded mb-6'>Permintaan konsultasi berhasil dikirim! Tunggu konfirmasi dari guru ya.</div>";
    } else {
        $msg_konsul = "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Gagal mengirim permintaan: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .lexend-font {
            font-family: "Lexend", sans-serif;
        }
    </style>
</head>
<body class="bg-slate-50 lexend-font min-h-screen flex flex-col">

    <nav class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-black">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            </svg>
            <h1 class="font-bold text-black text-xl tracking-tight">Aplikasi BK</h1>
        </div>
        <div class="flex gap-4 items-center">
            <?php if ($is_logged_in): ?>
                <span class="text-slate-500 text-sm hidden md:inline"><?= htmlspecialchars($student['nama_lengkap']) ?></span>
                <a href="logout.php" class="text-red-500 text-sm font-medium hover:bg-red-50 px-3 py-1 rounded transition">Keluar</a>
            <?php else: ?>
                <a href="login.php" class="bg-black text-white text-sm font-medium hover:bg-zinc-800 px-4 py-2 rounded-lg transition">Masuk</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container mx-auto p-6 flex-grow">
        
        <?= $msg_konsul ?>

        <?php if ($is_logged_in): ?>
        <div class="bg-black text-white p-8 rounded-2xl shadow-lg mb-8 relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-3xl md:text-4xl font-bold mb-2">Selamat Datang, <?= explode(' ', $student['nama_lengkap'])[0] ?>! 👋</h2>
                <p class="text-zinc-400 text-lg max-w-2xl mb-6">Siap untuk mengenal dirimu lebih dalam hari ini? Jadwalkan konsultasi atau cek hasil asesmenmu di bawah ini.</p>
                <a href="edit_asesmen.php" class="inline-flex items-center gap-2 bg-white text-black px-5 py-2.5 rounded-lg font-bold hover:bg-zinc-200 transition shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                        <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                        <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                    </svg>
                    Edit Data Asesmen
                </a>
            </div>
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-zinc-800 opacity-20 rounded-full blur-3xl"></div>
        </div>
        <?php else: ?>
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white p-8 rounded-2xl shadow-lg mb-8 relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-3xl md:text-4xl font-bold mb-2">Selamat Datang di Aplikasi BK 👋</h2>
                <p class="text-blue-100 text-lg max-w-2xl mb-6">Kenali potensimu, jadwalkan konsultasi dengan guru BK, dan temukan solusi terbaik untuk masalahmu. Masuk untuk mengakses semua fitur.</p>
                <a href="login.php" class="inline-flex items-center gap-2 bg-white text-blue-700 px-5 py-2.5 rounded-lg font-bold hover:bg-blue-50 transition shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0v-2z"/>
                        <path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                    </svg>
                    Masuk Sekarang
                </a>
            </div>
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1 space-y-6">
                
                <?php if ($is_logged_in): ?>
                <!-- Permintaan Menunggu -->
                <?php if($res_pending && $res_pending->num_rows > 0): ?>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-yellow-100 hover:shadow-md transition relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-yellow-50 rounded-bl-full -mr-8 -mt-8 z-0"></div>
                    <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2 mb-4 relative z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-yellow-600"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Menunggu Konfirmasi
                    </h3>
                    
                    <div class="space-y-4">
                        <?php while($pend = $res_pending->fetch_assoc()): ?>
                            <div class="flex gap-3 items-start border-b border-slate-50 pb-3 last:border-0 last:pb-0 relative z-10">
                                <div class="bg-yellow-50 text-yellow-600 w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm"><?= $pend['nama_konselor'] ?></h4>
                                    <p class="text-xs text-slate-500 mb-1">
                                        <?= date('d M Y', strtotime($pend['tanggal_konsultasi'])) ?>, <?= date('H:i', strtotime($pend['tanggal_konsultasi'])) ?> WIB
                                    </p>
                                    <span class="inline-block text-[10px] px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700 font-medium">
                                        Menunggu
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition">
                    <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-black"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M16 2v4"/></svg>
                        Jadwal Mendatang
                    </h3>
                    
                    <?php if($res_schedule && $res_schedule->num_rows > 0): ?>
                        <div class="space-y-4 mb-6">
                            <?php while($sch = $res_schedule->fetch_assoc()): ?>
                                <div class="flex gap-3 items-start border-b border-slate-50 pb-3 last:border-0 last:pb-0">
                                    <div class="bg-black text-white w-12 h-12 rounded-lg flex flex-col items-center justify-center flex-shrink-0">
                                        <span class="text-[10px] font-bold uppercase"><?= date('M', strtotime($sch['tanggal_konsultasi'])) ?></span>
                                        <span class="text-lg font-bold leading-none"><?= date('d', strtotime($sch['tanggal_konsultasi'])) ?></span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm"><?= $sch['nama_konselor'] ?></h4>
                                        <p class="text-xs text-slate-500 mb-1"><?= date('H:i', strtotime($sch['tanggal_konsultasi'])) ?> WIB • <?= $sch['kategori_topik'] ?></p>
                                        <span class="inline-block text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-medium">
                                            <?= ucfirst($sch['metode_konsultasi']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-slate-500 text-sm mb-6">Belum ada jadwal konsultasi yang disetujui.</p>
                    <?php endif; ?>

                    <button onclick="document.getElementById('modalKonsul').showModal()" class="w-full bg-white text-black border border-black hover:text-white px-4 py-2.5 rounded-lg text-sm font-bold hover:bg-zinc-800 transition shadow-sm">
                        Buat Janji Baru
                    </button>
                </div>
                <?php else: ?>
                <!-- Locked state for guests -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition">
                    <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-black"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M16 2v4"/></svg>
                        Jadwal Konsultasi
                    </h3>
                    <div class="text-center py-6">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <p class="text-slate-500 text-sm mb-4">Masuk untuk melihat jadwal konsultasi dan membuat janji baru.</p>
                        <a href="login.php" class="inline-block bg-black text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-zinc-800 transition">
                            Masuk Sekarang
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 h-full">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-xl text-slate-800 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-black"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                            Profil Gaya Belajar
                        </h3>
                        <?php if ($is_logged_in): ?>
                        <span class="bg-zinc-100 text-black text-xs font-bold px-3 py-1 rounded-full">
                            <?= $dominant_title ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_logged_in): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <div class="relative h-64 w-full flex justify-center">
                            <?php if ($vak_data): ?>
                                <canvas id="vakChart"></canvas>
                            <?php else: ?>
                                <div class="flex items-center justify-center h-full text-slate-400 text-sm">
                                    Belum ada data asesmen.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="bg-slate-50 p-6 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-700 mb-2">Apa artinya?</h4>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                <?= $dominant_desc ?>
                            </p>
                            <div class="mt-4 pt-4 border-t border-slate-200">
                                <p class="text-xs text-slate-400">Hasil ini berdasarkan tes VAK yang telah kamu isi.</p>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Locked state for guests -->
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <h4 class="font-bold text-slate-700 mb-2">Temukan Gaya Belajarmu</h4>
                        <p class="text-slate-500 text-sm max-w-md mx-auto mb-6">Masuk untuk melihat profil gaya belajarmu berdasarkan tes VAK. Ketahui apakah kamu tipe Visual, Auditori, atau Kinestetik!</p>
                        <a href="login.php" class="inline-block bg-black text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-zinc-800 transition">
                            Masuk Sekarang
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <?php if ($is_logged_in): ?>
    <dialog id="modalKonsul" class="modal p-0 rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-lg">
        <div class="bg-white p-6">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="font-bold text-lg text-slate-800">Buat Janji Konsultasi</h3>
                <button onclick="document.getElementById('modalKonsul').close()" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Pilih Guru BK</label>
                    <select name="id_konselor" required class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">-- Pilih Guru --</option>
                        <?php if ($list_konselor): ?>
                        <?php while($k = $list_konselor->fetch_assoc()): ?>
                            <option value="<?= $k['id'] ?>"><?= $k['nama_lengkap'] ?></option>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Topik</label>
                        <select name="topik" required class="w-full border rounded px-3 py-2 text-sm">
                            <option value="Akademik">Akademik</option>
                            <option value="Pribadi">Pribadi</option>
                            <option value="Sosial">Sosial</option>
                            <option value="Karir">Karir</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Metode</label>
                        <select name="metode" required class="w-full border rounded px-3 py-2 text-sm">
                            <option value="offline">Tatap Muka (Offline)</option>
                            <option value="online">Daring (Online)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tanggal</label>
                        <input type="date" name="tanggal" required class="w-full border rounded px-3 py-2 text-sm" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Jam</label>
                        <input type="time" name="jam" required class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Keluhan / Masalah</label>
                    <textarea name="keluhan" rows="3" required class="w-full border rounded px-3 py-2 text-sm" placeholder="Ceritakan sedikit apa yang ingin kamu bahas..."></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" name="submit_konsul" class="w-full bg-black hover:bg-zinc-800 text-white font-bold py-2.5 rounded-lg transition">
                        Kirim Permintaan
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        <?php if ($vak_data): ?>
        const ctx = document.getElementById('vakChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Visual', 'Auditori', 'Kinestetik'],
                datasets: [{
                    data: [<?= $vak_counts['Visual'] ?>, <?= $vak_counts['Auditori'] ?>, <?= $vak_counts['Kinestetik'] ?>],
                    backgroundColor: [
                        '#14e545ff',
                        '#e1ff69ff', 
                        '#f03c3cff'  
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                family: "'Lexend', sans-serif"
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
        <?php endif; ?>
    </script>
    <?php endif; ?>

</body>
</html>