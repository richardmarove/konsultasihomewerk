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
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_konsul = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        $conn->query("UPDATE konsultasi SET status='disetujui' WHERE id='$id_konsul'");
    } elseif ($action == 'reject') {
        $conn->query("UPDATE konsultasi SET status='ditolak' WHERE id='$id_konsul'");
    }
    header("Location: dashboard_guru.php");
    exit;
}

// Statistik
$today = date('Y-m-d');
$stat_today = $conn->query("SELECT COUNT(*) as total FROM konsultasi WHERE id_konselor='$id_konselor' AND status='disetujui' AND DATE(tanggal_konsultasi) = '$today'")->fetch_assoc()['total'];
$stat_pending = $conn->query("SELECT COUNT(*) as total FROM konsultasi WHERE id_konselor='$id_konselor' AND status='menunggu'")->fetch_assoc()['total'];

// Siswa Prioritas (Yang punya skor 'PERLU PERHATIAN KHUSUS')
// Kita hitung dari tabel hasil_asesmen yang join ke siswa, tapi idealnya kita hitung berapa REQUEST yang datang dari siswa prioritas.
// Untuk simpelnya, kita hitung total siswa yang pernah asesmen bahaya.
$stat_priority = $conn->query("SELECT COUNT(DISTINCT id_siswa) as total FROM hasil_asesmen WHERE skor LIKE '%PERLU PERHATIAN KHUSUS%'")->fetch_assoc()['total'];


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

// Jadwal Mendatang (Disetujui)
$sql_schedule = "
    SELECT k.*, s.nama_lengkap 
    FROM konsultasi k 
    JOIN siswa s ON k.id_siswa = s.id 
    WHERE k.id_konselor = '$id_konselor' AND k.status = 'disetujui' AND k.tanggal_konsultasi >= NOW() 
    ORDER BY k.tanggal_konsultasi ASC
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
</head>
<body class="bg-slate-50 lexend-font min-h-screen">

    <nav class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <h1 class="font-bold text-blue-600 text-xl">Panel Konselor</h1>
        <div class="flex gap-4 items-center">
            <span class="text-slate-500 text-sm"><?= $guru['nama_lengkap'] ?></span>
            <a href="logout.php" class="text-red-500 text-sm hover:underline">Keluar</a>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-800 mb-6">Ringkasan Aktivitas</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-slate-500 text-sm">Konsultasi Hari Ini</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?= $stat_today ?></h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-yellow-500">
                    <p class="text-slate-500 text-sm">Permintaan Baru</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?= $stat_pending ?></h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-red-500">
                    <p class="text-slate-500 text-sm">Siswa Prioritas (Risk)</p>
                    <h3 class="text-3xl font-bold text-red-600"><?= $stat_priority ?></h3>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
                <h3 class="font-bold text-lg text-slate-700 mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mailbox-icon lucide-mailbox text-blue-500"><path d="M22 17a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9.5C2 7 4 5 6.5 5H18c2.2 0 4 1.8 4 4v8Z"/><polyline points="15,9 18,9 18,11"/><path d="M6.5 5C9 5 11 7 11 9.5V17a2 2 0 0 1-2 2"/><line x1="6" x2="7" y1="10" y2="10"/></svg> Permintaan Masuk
                    <?php if($stat_pending > 0): ?>
                        <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full"><?= $stat_pending ?></span>
                    <?php endif; ?>
                </h3>
                
                <div class="space-y-4">
                    <?php if($res_requests->num_rows > 0): ?>
                        <?php while($req = $res_requests->fetch_assoc()): ?>
                            <div class="bg-white p-5 rounded-xl shadow-sm border <?= $req['is_priority'] > 0 ? 'border-red-300 ring-1 ring-red-100' : 'border-slate-100' ?>">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-bold text-slate-800 flex items-center gap-2">
                                            <a href="detail_siswa.php?id=<?= $req['id_siswa'] ?>" class="hover:text-blue-600 hover:underline transition">
                                                <?= $req['nama_lengkap'] ?>
                                            </a>
                                            <?php if($req['is_priority'] > 0): ?>
                                                <span class="bg-red-100 text-red-600 text-[10px] px-2 py-0.5 rounded-full uppercase font-bold tracking-wide">Prioritas</span>
                                            <?php endif; ?>
                                        </h4>
                                        <p class="text-xs text-slate-500"><?= $req['tingkat_kelas'] ?> <?= $req['jurusan'] ?> • <?= ucfirst($req['metode_konsultasi']) ?></p>
                                    </div>
                                    <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">
                                        <?= date('d M, H:i', strtotime($req['tanggal_konsultasi'])) ?>
                                    </span>
                                </div>
                                
                                <div class="bg-slate-50 p-3 rounded-lg mb-4">
                                    <p class="text-xs font-bold text-slate-500 uppercase mb-1"><?= $req['kategori_topik'] ?></p>
                                    <p class="text-sm text-slate-700 italic">"<?= $req['deskripsi_keluhan'] ?>"</p>
                                </div>

                                <div class="flex gap-2">
                                    <a href="?action=approve&id=<?= $req['id'] ?>" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-lg text-sm font-bold transition">
                                        Terima
                                    </a>
                                    <a href="?action=reject&id=<?= $req['id'] ?>" onclick="return confirm('Tolak permintaan ini?')" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 text-center py-2 rounded-lg text-sm font-bold transition">
                                        Tolak
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="bg-white p-8 rounded-xl border border-dashed border-slate-300 text-center text-slate-400">
                            Tidak ada permintaan baru.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <h3 class="font-bold text-lg text-slate-700 mb-4 flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-icon lucide-calendar text-blue-500"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg> Jadwal Mendatang</h3>
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <?php if($res_schedule->num_rows > 0): ?>
                        <div class="divide-y">
                            <?php while($sch = $res_schedule->fetch_assoc()): ?>
                                <div class="p-4 hover:bg-slate-50 transition flex gap-4 items-center">
                                    <div class="bg-blue-50 text-blue-600 w-14 h-14 rounded-lg flex flex-col items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold uppercase"><?= date('M', strtotime($sch['tanggal_konsultasi'])) ?></span>
                                        <span class="text-xl font-bold"><?= date('d', strtotime($sch['tanggal_konsultasi'])) ?></span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800">
                                            <a href="detail_siswa.php?id=<?= $sch['id_siswa'] ?>" class="hover:text-blue-600 hover:underline transition">
                                                <?= $sch['nama_lengkap'] ?>
                                            </a>
                                        </h4>
                                        <p class="text-sm text-slate-500"><?= date('H:i', strtotime($sch['tanggal_konsultasi'])) ?> WIB • <?= $sch['kategori_topik'] ?></p>
                                        <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded bg-green-100 text-green-700 font-medium">
                                            <?= ucfirst($sch['metode_konsultasi']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-8 text-center text-slate-400">
                            Belum ada jadwal yang disetujui.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
