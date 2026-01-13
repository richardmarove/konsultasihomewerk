<?php
session_start();
include 'config/database.php';

// Enforce login check
if (!isset($_SESSION['user_id']) || $_SESSION['peran'] != 'siswa') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialize variables
$student = ['nama_lengkap' => 'Siswa'];
$id_siswa = null;
$vak_data = null;
$vak_counts = ['Visual' => 0, 'Auditori' => 0, 'Kinestetik' => 0];
$dominant_desc = "Belum ada data";
$dominant_title = "-";
$res_schedule = null;
$res_pending = null;
$list_konselor = null;

// Fetch student data
$sql_student = "SELECT id, nama_lengkap FROM siswa WHERE id_pengguna = '$user_id'";
$res_student = $conn->query($sql_student);
$student = $res_student->fetch_assoc();

if (!$student) {
    die("Data siswa tidak ditemukan. Pastikan akun user sudah terhubung ke tabel siswa.");
}

$id_siswa = $student['id'];

// Check family details
$sql_check = "SELECT * FROM detail_keluarga_siswa WHERE id_siswa = '$id_siswa'";
$check_res = $conn->query($sql_check);

if ($check_res->num_rows == 0) {
    header("Location: modul_asesmen.php");
    exit;
}

// Fetch VAK data
$sql_vak = "SELECT ringkasan_hasil, skor FROM hasil_asesmen WHERE id_siswa = '$id_siswa' AND kategori = 'gaya_belajar' ORDER BY id DESC LIMIT 1";
$res_vak = $conn->query($sql_vak);
$vak_data = $res_vak->fetch_assoc();

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

// Fetch Schedules
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

// Proses Konsultasi (POST)
$msg_konsul = "";

// Check for status messages from redirect
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $msg_konsul = "<div class='bg-green-50 text-green-700 px-5 py-4 rounded-xl border border-green-200 mb-8 flex items-center gap-3'><svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='20 6 9 17 4 12'/></svg>Permintaan konsultasi berhasil dikirim! Tunggu konfirmasi dari guru ya.</div>";
    } elseif ($_GET['status'] == 'error' && isset($_GET['msg'])) {
        $msg_konsul = "<div class='bg-red-50 text-red-700 px-5 py-4 rounded-xl border border-red-200 mb-8 flex items-center gap-3'><svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='15' y1='9' x2='9' y2='15'/><line x1='9' y1='9' x2='15' y2='15'/></svg>" . htmlspecialchars($_GET['msg']) . "</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_konsul'])) {
    $id_konselor = $_POST['id_konselor'];
    $topik = $_POST['topik'];
    $keluhan = $_POST['keluhan'];
    $tanggal = $_POST['tanggal'];
    $jam = $_POST['jam'];
    
    $tgl_waktu = $tanggal . ' ' . $jam . ':00';

    // Server-side Duplicate Check
    $check_stmt = $conn->prepare("SELECT id FROM konsultasi WHERE id_siswa = ? AND id_konselor = ? AND tanggal_konsultasi = ?");
    $check_stmt->bind_param("iis", $id_siswa, $id_konselor, $tgl_waktu);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res->num_rows > 0) {
        $error_msg = "Permintaan konsultasi untuk waktu tersebut sudah ada.";
        header("Location: dashboard_siswa.php?status=error&msg=" . urlencode($error_msg));
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO konsultasi (id_siswa, id_konselor, kategori_topik, deskripsi_keluhan, tanggal_konsultasi, status) VALUES (?, ?, ?, ?, ?, 'menunggu')");
    $stmt->bind_param("iisss", $id_siswa, $id_konselor, $topik, $keluhan, $tgl_waktu);
    
    if ($stmt->execute()) {
        header("Location: dashboard_siswa.php?status=success");
        exit;
    } else {
        $error_msg = "Gagal mengirim permintaan: " . $conn->error;
        header("Location: dashboard_siswa.php?status=error&msg=" . urlencode($error_msg));
        exit;
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
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body class="bg-slate-50 lexend-font min-h-screen flex flex-col text-slate-800">

    <nav class="bg-white/80 backdrop-blur-md border-b border-slate-100 sticky top-0 z-50">
        <div class="px-6 py-5 container mx-auto flex justify-between items-center">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-[#6C5CE7] rounded-lg flex items-center justify-center text-white shadow-lg shadow-purple-200">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <h1 class="font-bold text-slate-800 text-xl tracking-tight">Aplikasi BK</h1>
            </div>
            <div class="flex gap-6 items-center">
                <div class="flex items-center gap-3">
                <span class="text-slate-600 text-sm font-medium hidden md:inline"><?= htmlspecialchars($student['nama_lengkap']) ?></span>
                </div>
                <div class="h-4 w-px bg-slate-200"></div>
                <a href="logout.php" class="text-slate-500 text-sm font-medium hover:text-red-500 transition-colors">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 flex-grow max-w-7xl animate-fade-in-up">
        
        <?= $msg_konsul ?>

        <div class="bg-gradient-to-r from-[#6C5CE7] to-[#8075FF] text-white p-8 md:p-10 rounded-3xl shadow-xl shadow-purple-200 mb-10 relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-3xl md:text-5xl font-bold mb-4 tracking-tight">Halo, <?= explode(' ', $student['nama_lengkap'])[0] ?>! 👋</h2>
                <p class="text-purple-100 text-lg max-w-2xl mb-8 font-light leading-relaxed">Siap untuk mengenal dirimu lebih dalam hari ini? Jadwalkan konsultasi atau cek hasil asesmenmu di bawah ini.</p>
                <div class="flex flex-wrap gap-4">
                    <a href="edit_asesmen.php" class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 text-white px-6 py-3 rounded-xl font-semibold hover:bg-white hover:text-[#6C5CE7] transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                        </svg>
                        Update Asesmen
                    </a>
                    <a href="riwayat_konsultasi.php" class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 text-white px-6 py-3 rounded-xl font-semibold hover:bg-white hover:text-[#6C5CE7] transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/><path d="M12 14v-4"/><path d="M12 10h2"/></svg>
                        Riwayat
                    </a>
                    <button onclick="document.getElementById('modalKonsul').showModal()" class="inline-flex items-center gap-2 bg-white text-[#6C5CE7] px-6 py-3 rounded-xl font-bold hover:bg-purple-50 transition-all duration-300 shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M11 14h10"/><path d="M11 18h10"/></svg>
                        Buat Janji Konsultasi
                    </button>
                    </div>
            </div>
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-80 h-80 bg-white opacity-10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-64 h-64 bg-purple-500 opacity-20 rounded-full blur-3xl"></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left Column: Tasks & Schedules -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- Permintaan Menunggu -->
                <?php if($res_pending && $res_pending->num_rows > 0): ?>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100/50 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-50/50 rounded-bl-full -mr-8 -mt-8 z-0 group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="relative z-10">
                        <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2 mb-5">
                            <span class="w-8 h-8 rounded-full bg-yellow-50 flex items-center justify-center text-yellow-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </span>
                            Menunggu Konfirmasi
                        </h3>
                        
                        <div class="space-y-4">
                            <?php while($pend = $res_pending->fetch_assoc()): ?>
                                <div class="flex gap-4 items-start p-3 rounded-2xl hover:bg-slate-50 transition-colors">
                                    <div class="flex-grow">
                                        <h4 class="font-bold text-slate-700 text-sm"><?= $pend['nama_konselor'] ?></h4>
                                        <p class="text-xs text-slate-500 mb-2">
                                            <?= date('d M Y', strtotime($pend['tanggal_konsultasi'])) ?> • <?= date('H:i', strtotime($pend['tanggal_konsultasi'])) ?>
                                        </p>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 text-[10px] font-bold uppercase tracking-wide">
                                            Menunggu
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100/50 hover:shadow-md transition-all duration-300">
                    <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2 mb-6">
                       <span class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center text-[#6C5CE7]">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M16 2v4"/></svg>
                        </span>
                        Jadwal Mendatang
                    </h3>
                    
                    <?php if($res_schedule && $res_schedule->num_rows > 0): ?>
                        <div class="space-y-5 mb-8">
                            <?php while($sch = $res_schedule->fetch_assoc()): ?>
                                <div class="flex gap-4 items-center group">
                                    <div class="bg-purple-50 text-[#6C5CE7] w-14 h-14 rounded-2xl flex flex-col items-center justify-center flex-shrink-0 border border-purple-100 group-hover:bg-[#6C5CE7] group-hover:text-white transition-colors duration-300">
                                        <span class="text-[10px] font-bold uppercase tracking-wider"><?= date('M', strtotime($sch['tanggal_konsultasi'])) ?></span>
                                        <span class="text-xl font-bold leading-none"><?= date('d', strtotime($sch['tanggal_konsultasi'])) ?></span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm group-hover:text-[#6C5CE7] transition-colors"><?= $sch['nama_konselor'] ?></h4>
                                        <p class="text-xs text-slate-500 font-medium"><?= date('H:i', strtotime($sch['tanggal_konsultasi'])) ?> WIB</p>
                                        <p class="text-xs text-slate-400 mt-0.5"><?= $sch['kategori_topik'] ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 px-4 bg-slate-50 rounded-2xl mb-6">
                            <p class="text-slate-500 text-sm">Belum ada jadwal konsultasi.</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Right Column: Profile & Chart -->
            <div class="lg:col-span-8">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100/50 h-full">
                    <div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
                        <div class="text-center md:text-left">
                            <h3 class="font-bold text-xl text-slate-800 flex items-center gap-2 justify-center md:justify-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#6C5CE7]"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                Profil Gaya Belajar
                            </h3>
                            <p class="text-slate-400 text-sm mt-1">Analisis berdasarkan tes asesmen VAK</p>
                        </div>
                        <span class="bg-gradient-to-r from-purple-50 to-indigo-50 text-[#6C5CE7] text-sm font-bold px-4 py-2 rounded-xl border border-purple-100 shadow-sm">
                            <?= $dominant_title ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                        <div class="relative aspect-square md:h-72 w-full flex justify-center items-center">
                            <?php if ($vak_data): ?>
                                <canvas id="vakChart"></canvas>
                                <!-- Center Text Overlay -->
                                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-xs text-slate-400 font-bold uppercase tracking-widest">Dominan</span>
                                    <span class="text-xl font-bold text-slate-700 mt-1"><?= explode(' ', $dominant_title)[0] ?></span>
                                </div>
                            <?php else: ?>
                                <div class="flex flex-col items-center justify-center h-full text-slate-400 text-sm text-center p-8 bg-slate-50 rounded-2xl w-full border-2 border-dashed border-slate-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-300 mb-2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <p>Belum ada data asesmen.</p>
                                    <a href="edit_asesmen.php" class="text-[#6C5CE7] font-bold mt-2 hover:underline">Isi Asesmen Sekarang</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-6">
                            <div class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100 relative">
                                <div class="absolute -top-3 -left-3 bg-indigo-100 text-indigo-600 p-2 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                </div>
                                <h4 class="font-bold text-slate-800 mb-3 pl-6">Insight Belajar</h4>
                                <p class="text-slate-600 text-sm leading-relaxed">
                                    <?= $dominant_desc ?>
                                </p>
                            </div>

                            <?php if ($vak_data): ?>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="text-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Visual</div>
                                    <div class="font-bold text-[#873de9] text-lg"><?= $vak_counts['Visual'] ?></div>
                                </div>
                                <div class="text-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Auditori</div>
                                    <div class="font-bold text-[#a6c125] text-lg"><?= $vak_counts['Auditori'] ?></div>
                                </div>
                                <div class="text-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Kinestetik</div>
                                    <div class="font-bold text-[#f03c3c] text-lg"><?= $vak_counts['Kinestetik'] ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Layout Refinement -->
    <dialog id="modalKonsul" class="modal bg-transparent p-0 w-full h-full max-w-none max-h-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:flex items-center justify-center">
        <form method="dialog" class="fixed inset-0 w-full h-full cursor-default focus:outline-none"></form>
        
        <div class="bg-white w-full max-w-lg p-0 rounded-3xl shadow-2xl relative z-10 m-4 flex flex-col max-h-[90vh] overflow-hidden animate-fade-in-up">
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-xl text-slate-800">Buat Janji Konsultasi</h3>
                <button onclick="document.getElementById('modalKonsul').close()" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            
            <div class="p-8 overflow-y-auto custom-scrollbar">
                <form method="POST" class="space-y-6" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerHTML = 'Mengirim...';">
                    <input type="hidden" name="submit_konsul" value="1">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pilih Guru BK</label>
                        <div class="relative">
                            <select name="id_konselor" required class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-sm font-medium focus:outline-none focus:border-[#6C5CE7] focus:ring-4 focus:ring-[#6C5CE7]/10 transition cursor-pointer">
                                <option value="">-- Pilih Guru --</option>
                                <?php if ($list_konselor): ?>
                                <?php while($k = $list_konselor->fetch_assoc()): ?>
                                    <option value="<?= $k['id'] ?>"><?= $k['nama_lengkap'] ?></option>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Topik</label>
                        <div class="relative">
                            <select name="topik" required class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-sm font-medium focus:outline-none focus:border-[#6C5CE7] focus:ring-4 focus:ring-[#6C5CE7]/10 transition cursor-pointer">
                                <option value="Akademik">Akademik</option>
                                <option value="Pribadi">Pribadi</option>
                                <option value="Sosial">Sosial</option>
                                <option value="Karir">Karir</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tanggal</label>
                            <input type="date" name="tanggal" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-sm font-medium focus:outline-none focus:border-[#6C5CE7] focus:ring-4 focus:ring-[#6C5CE7]/10 transition" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Jam</label>
                            <input type="time" name="jam" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-sm font-medium focus:outline-none focus:border-[#6C5CE7] focus:ring-4 focus:ring-[#6C5CE7]/10 transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Keluhan / Masalah</label>
                        <textarea name="keluhan" rows="4" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-sm font-medium focus:outline-none focus:border-[#6C5CE7] focus:ring-4 focus:ring-[#6C5CE7]/10 transition resize-none" placeholder="Ceritakan sedikit apa yang ingin kamu bahas..."></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" name="submit_konsul" class="w-full bg-[#6C5CE7] hover:bg-[#5B4ED1] text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-purple-200 transform active:scale-[0.98]">
                            Kirim Permintaan Konsultasi
                        </button>
                    </div>
                </form>
            </div>
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
                        '#873de9',
                        '#e1ff69', 
                        '#f03c3c'  
                    ],
                    borderWidth: 0,
                    hoverOffset: 10,
                    borderRadius: 20
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
                            pointStyle: 'circle',
                            padding: 20,
                            font: {
                                family: "'Lexend', sans-serif",
                                size: 12,
                                weight: 600
                            },
                            color: '#64748b'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#1e293b',
                        bodyColor: '#475569',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 12,
                        displayColors: true,
                        boxPadding: 4
                    }
                },
                cutout: '75%',
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>