<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['peran'] != 'konselor') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$sql_guru = "SELECT * FROM konselor WHERE id_pengguna = '$user_id'";
$res_guru = $conn->query($sql_guru);
$guru = $res_guru->fetch_assoc();
$id_konselor = $guru['id'];

// Action (Terima/Tolak)
// Action (Terima/Tolak)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_konsul = $_GET['id'];
    $action = $_GET['action'];
    
    // Validasi status yang diperbolehkan
    $status_baru = '';
    if ($action == 'approve') {
        $status_baru = 'disetujui';
    } elseif ($action == 'reject') {
        $status_baru = 'ditolak';
    }

    if ($status_baru) {
        $stmt = $conn->prepare("UPDATE konsultasi SET status=? WHERE id=?");
        $stmt->bind_param("si", $status_baru, $id_konsul);
        $stmt->execute();
    }
    
    header("Location: dashboard_guru.php");
    exit;
}

// Statistik
$today = date('Y-m-d');
$stat_today = $conn->query("SELECT COUNT(*) as total FROM konsultasi WHERE id_konselor='$id_konselor' AND status='disetujui' AND DATE(tanggal_konsultasi) = '$today'")->fetch_assoc()['total'];
$stat_pending = $conn->query("SELECT COUNT(*) as total FROM konsultasi WHERE id_konselor='$id_konselor' AND status='menunggu'")->fetch_assoc()['total'];

// Siswa Prioritas (Yang punya skor 'PERLU PERHATIAN KHUSUS')
$stat_priority = $conn->query("SELECT COUNT(DISTINCT id_siswa) as total FROM hasil_asesmen WHERE skor LIKE '%PERLU PERHATIAN KHUSUS%'")->fetch_assoc()['total'];

// --- ANALYTICS LOGIC ---

// 1. Student Wellness Distribution (from latest 'kesehatan_mental' assessments)
$wellness_stats = [
    'Stabil' => 0,
    'Berisiko' => 0
];
$sql_wellness = "
    SELECT ha.skor 
    FROM hasil_asesmen ha
    INNER JOIN (
        SELECT id_siswa, MAX(terakhir_diperbarui) as max_date
        FROM hasil_asesmen
        WHERE kategori = 'kesehatan_mental'
        GROUP BY id_siswa
    ) latest ON ha.id_siswa = latest.id_siswa AND ha.terakhir_diperbarui = latest.max_date
    WHERE ha.kategori = 'kesehatan_mental'
";
$res_wellness = $conn->query($sql_wellness);
if ($res_wellness && $res_wellness->num_rows > 0) {
    while($row = $res_wellness->fetch_assoc()) {
        if (strpos($row['skor'], 'PERLU PERHATIAN KHUSUS') !== false) {
            $wellness_stats['Berisiko']++;
        } else {
            // Assuming anything not 'PERLU PERHATIAN KHUSUS' is 'Stabil' or neutral for now
            $wellness_stats['Stabil']++;
        }
    }
}

// 2. Trend Indicator: Consultation Requests (Last 7 days vs Previous 7 days)
$date_7_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
$date_14_days_ago = date('Y-m-d H:i:s', strtotime('-14 days'));

// Current Week Count
$sql_trend_curr = "SELECT COUNT(*) as total FROM konsultasi WHERE id_konselor='$id_konselor' AND created_at >= '$date_7_days_ago'";
$trend_curr = $conn->query($sql_trend_curr)->fetch_assoc()['total'];

// Previous Week Count
$sql_trend_prev = "SELECT COUNT(*) as total FROM konsultasi WHERE id_konselor='$id_konselor' AND created_at >= '$date_14_days_ago' AND created_at < '$date_7_days_ago'";
$trend_prev = $conn->query($sql_trend_prev)->fetch_assoc()['total'];

$trend_diff = $trend_curr - $trend_prev;
$trend_text = "";
$trend_color = "text-slate-500";
$trend_icon = "";

if ($trend_diff > 0) {
    $trend_text = "+" . $trend_diff . " dari minggu lalu";
    $trend_color = "text-emerald-600";
    $trend_icon = "↑";
} elseif ($trend_diff < 0) {
    $trend_text = $trend_diff . " dari minggu lalu";
    $trend_color = "text-red-500"; // Less requests might be bad or good depending on context, assuming neutral/red for drop in engagement? Or green?
    // Let's assume for a "Request" stat, ANY change is just a trend. But usually more requests = more work. 
    // Let's keep it neutral or red if significant drop? Let's just use red for negative numbers visually.
    $trend_icon = "↓";
} else {
    $trend_text = "Stabil dari minggu lalu";
    $trend_color = "text-slate-500";
    $trend_icon = "-";
}


// Query Data

// Permintaan Masuk (Pending) + Cek Prioritas
// Kita join ke tabel siswa, lalu subquery/join ke hasil_asesmen untuk cek status mental
$sql_requests = "
    SELECT k.*, s.nama_lengkap, s.tingkat_kelas, s.jurusan,
    (SELECT COUNT(*) FROM hasil_asesmen ha WHERE ha.id_siswa = k.id_siswa AND ha.skor LIKE '%PERLU PERHATIAN KHUSUS%') as is_priority
    FROM konsultasi k 
    JOIN siswa s ON k.id_siswa = s.id 
    WHERE k.id_konselor = '$id_konselor' AND k.status = 'menunggu' 
    ORDER BY is_priority DESC, k.created_at ASC
";
$res_requests = $conn->query($sql_requests);

// Jadwal Mendatang (Disetujui) + Selesai (untuk akses laporan)
$sql_schedule = "
    SELECT k.*, s.nama_lengkap 
    FROM konsultasi k 
    JOIN siswa s ON k.id_siswa = s.id 
    WHERE k.id_konselor = '$id_konselor' AND (k.status = 'disetujui' OR k.status = 'selesai')
    ORDER BY k.tanggal_konsultasi DESC
";
$res_schedule = $conn->query($sql_schedule);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Konselor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <style>.lexend-font { font-family: "Lexend", sans-serif; }</style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-[#FDFDFD] lexend-font min-h-screen">

    <nav class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <h1 class="font-bold text-[#6C5CE7] text-xl">Panel Konselor</h1>
        <div class="flex gap-4 items-center">
            <span class="text-slate-500 text-sm"><?= $guru['nama_lengkap'] ?></span>
            <a href="logout.php" class="text-slate-400 text-sm hover:text-[#6C5CE7]">Keluar</a>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        
        <!-- ANALYTICS SECTION -->
        <h2 class="text-xl font-bold text-slate-700 mb-6">Analitik dan Wawasan</h2>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
            <!-- Wellness Distribution Chart -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 lg:col-span-2 flex flex-col md:flex-row items-center gap-8">
                <div class="w-full md:w-1/3 relative h-48 md:h-auto flex justify-center">
                    <canvas id="wellnessChart" class="max-w-[200px] max-h-[200px]"></canvas>
                </div>
                <div class="flex-1 w-full">
                    <h3 class="font-bold text-lg text-slate-800 mb-2">Student Wellness Distribution</h3>
                    <p class="text-sm text-slate-500 mb-6">Distribusi hasil asesmen kesehatan mental siswa terbaru.</p>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                            <span class="text-sm font-bold text-emerald-700 flex items-center gap-2">
                                <span class="w-3 h-3 bg-emerald-500 rounded-full"></span> Stabil
                            </span>
                            <span class="font-bold text-emerald-700"><?= $wellness_stats['Stabil'] ?> Siswa</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-red-50 rounded-xl border border-red-100">
                            <span class="text-sm font-bold text-red-700 flex items-center gap-2">
                                <span class="w-3 h-3 bg-red-500 rounded-full"></span> Perlu Perhatian
                            </span>
                            <span class="font-bold text-red-700"><?= $wellness_stats['Berisiko'] ?> Siswa</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trend Indicators -->
            <div class="space-y-6">
                 <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 h-full flex flex-col justify-center relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 rounded-full -mr-10 -mt-10 blur-2xl opacity-50"></div>
                    <p class="text-slate-500 text-sm font-bold uppercase tracking-wider mb-2 relative z-10">Trend Permintaan</p>
                    <div class="flex items-end gap-3 mb-2 relative z-10">
                        <h3 class="text-4xl font-extrabold text-slate-900"><?= $trend_curr ?></h3>
                        <span class="text-sm font-bold <?= $trend_color ?> mb-1.5 flex items-center gap-1 bg-slate-50 px-2 py-1 rounded-lg border border-slate-100">
                            <?= $trend_icon ?> <?= $trend_text ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 relative z-10">Total permintaan konsultasi dalam 7 hari terakhir.</p>
                 </div>
            </div>
        </div>

        <!-- Initialize Chart -->
        <script>
            const ctx = document.getElementById('wellnessChart');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Stabil', 'Perlu Perhatian'],
                    datasets: [{
                        data: [<?= $wellness_stats['Stabil'] ?>, <?= $wellness_stats['Berisiko'] ?>],
                        backgroundColor: [
                            '#10B981', // Emerald 500
                            '#EF4444'  // Red 500
                        ],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    cutout: '75%',
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        </script>

        <div class="bg-[#6C5CE7] text-white p-8 rounded-2xl shadow-lg mb-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white opacity-20 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
                <h2 class="text-2xl font-bold mb-6">Ringkasan Aktivitas</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <p class="text-slate-500 text-sm">Konsultasi Hari Ini</p>
                        <h3 class="text-3xl font-bold text-slate-800"><?= $stat_today ?></h3>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <p class="text-slate-500 text-sm">Permintaan Baru</p>
                        <h3 class="text-3xl font-bold text-slate-800"><?= $stat_pending ?></h3>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <p class="text-slate-500 text-sm">Siswa Prioritas (Risk)</p>
                        <h3 class="text-3xl font-bold text-red-600"><?= $stat_priority ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-[#F9F7FF] h-full">
                <h3 class="font-bold text-lg text-slate-700 mb-6 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mailbox-icon lucide-mailbox text-[#6C5CE7]"><path d="M22 17a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9.5C2 7 4 5 6.5 5H18c2.2 0 4 1.8 4 4v8Z"/><polyline points="15,9 18,9 18,11"/><path d="M6.5 5C9 5 11 7 11 9.5V17a2 2 0 0 1-2 2"/><line x1="6" x2="7" y1="10" y2="10"/></svg> Permintaan Masuk
                    <?php if($stat_pending > 0): ?>
                        <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full"><?= $stat_pending ?></span>
                    <?php endif; ?>
                </h3>
                
                <div class="space-y-4">
                    <?php if($res_requests->num_rows > 0): ?>
                        <?php while($req = $res_requests->fetch_assoc()): ?>
                            <div class="bg-[#F9F7FF] p-5 rounded-xl border <?= $req['is_priority'] > 0 ? 'border-red-300 ring-1 ring-red-100' : 'border-slate-100' ?>">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-bold text-slate-800 flex items-center gap-2">
                                            <a href="detail_siswa.php?id=<?= $req['id_siswa'] ?>" class="hover:text-[#6C5CE7] hover:underline transition">
                                                <?= $req['nama_lengkap'] ?>
                                            </a>
                                            <?php if($req['is_priority'] > 0): ?>
                                                <span class="bg-red-100 text-red-600 text-[10px] px-2 py-0.5 rounded-full uppercase font-bold tracking-wide">Prioritas</span>
                                            <?php endif; ?>
                                        </h4>
                                        <p class="text-xs text-slate-500"><?= $req['tingkat_kelas'] ?> <?= $req['jurusan'] ?></p>
                                    </div>
                                    <span class="text-xs bg-white text-slate-600 px-2 py-1 rounded border border-slate-100">
                                        <?= date('d M, H:i', strtotime($req['tanggal_konsultasi'])) ?>
                                    </span>
                                </div>
                                
                                <div class="bg-white p-3 rounded-lg mb-4 border border-slate-50">
                                    <p class="text-xs font-bold text-slate-500 uppercase mb-1"><?= $req['kategori_topik'] ?></p>
                                    <p class="text-sm text-slate-700 italic">"<?= $req['deskripsi_keluhan'] ?>"</p>
                                </div>

                                <div class="flex gap-2">
                                    <a href="?action=approve&id=<?= $req['id'] ?>" class="flex-1 bg-[#6C5CE7] hover:bg-[#5B4ED1] text-white text-center py-2 rounded-lg text-sm font-bold transition">
                                        Terima
                                    </a>
                                    <a href="?action=reject&id=<?= $req['id'] ?>" onclick="return confirm('Tolak permintaan ini?')" class="flex-1 bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 text-center py-2 rounded-lg text-sm font-bold transition">
                                        Tolak
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="bg-[#F9F7FF] p-8 rounded-xl border border-dashed border-slate-300 text-center text-slate-400">
                            Tidak ada permintaan baru.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-[#F9F7FF] h-full">
                <h3 class="font-bold text-lg text-slate-700 mb-6 flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-icon lucide-calendar text-[#6C5CE7]"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg> Jadwal & Riwayat Sesi</h3>
                <div class="rounded-xl overflow-hidden">
                    <?php if($res_schedule->num_rows > 0): ?>
                        <div class="space-y-4">
                            <?php while($sch = $res_schedule->fetch_assoc()): ?>
                                <div class="p-4 bg-[#F9F7FF] rounded-xl flex gap-4 items-center">
                                    <div class="bg-[#6C5CE7] text-white w-14 h-14 rounded-lg flex flex-col items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold uppercase"><?= date('M', strtotime($sch['tanggal_konsultasi'])) ?></span>
                                        <span class="text-xl font-bold"><?= date('d', strtotime($sch['tanggal_konsultasi'])) ?></span>
                                    </div>
                                    <div class="flex-grow">
                                        <h4 class="font-bold text-slate-800">
                                            <a href="detail_siswa.php?id=<?= $sch['id_siswa'] ?>" class="hover:text-[#6C5CE7] hover:underline transition">
                                                <?= $sch['nama_lengkap'] ?>
                                            </a>
                                        </h4>
                                        <p class="text-sm text-slate-500"><?= date('H:i', strtotime($sch['tanggal_konsultasi'])) ?> WIB • <?= $sch['kategori_topik'] ?></p>
                                    </div>
                                    <?php if($sch['status'] == 'selesai'): ?>
                                        <a href="laporan_konsultasi.php?id=<?= $sch['id'] ?>" class="bg-green-50 border border-green-200 text-green-700 hover:bg-green-100 px-3 py-2 rounded-lg text-xs font-bold transition flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                            Lihat Laporan
                                        </a>
                                    <?php else: ?>
                                        <a href="tulis_laporan.php?id=<?= $sch['id'] ?>" class="bg-white border border-slate-200 text-slate-600 hover:text-[#6C5CE7] hover:border-[#6C5CE7] px-3 py-2 rounded-lg text-xs font-bold transition">
                                            Isi Laporan
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-8 text-center text-slate-400 bg-[#F9F7FF] rounded-xl border border-dashed border-slate-300">
                            Belum ada jadwal yang disetujui.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
