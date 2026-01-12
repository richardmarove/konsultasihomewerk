<?php
session_start();
include 'config/database.php';

// Enforce login check
if (!isset($_SESSION['user_id']) || $_SESSION['peran'] != 'siswa') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student ID
$sql_student = "SELECT id, nama_lengkap FROM siswa WHERE id_pengguna = '$user_id'";
$res_student = $conn->query($sql_student);
$student = $res_student->fetch_assoc();
$id_siswa = $student['id'];

// Fetch History (Completed, Rejected, or Past Approved sessions)
// We LEFT JOIN with laporan_konsultasi to allow showing the report/notes if they exist
$sql_history = "
    SELECT k.*, c.nama_lengkap as nama_konselor, l.solusi_diberikan, l.inti_masalah
    FROM konsultasi k 
    JOIN konselor c ON k.id_konselor = c.id 
    LEFT JOIN laporan_konsultasi l ON k.id = l.id_konsultasi
    WHERE k.id_siswa = '$id_siswa' 
    ORDER BY k.tanggal_konsultasi DESC
";
$res_history = $conn->query($sql_history);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Riwayat Konsultasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <style>
        .lexend-font {
            font-family: "Lexend", sans-serif;
        }
    </style>
</head>
<body class="bg-slate-50 lexend-font min-h-screen">

    <nav class="bg-white/80 backdrop-blur-md border-b border-slate-100 sticky top-0 z-50">
        <div class="px-6 py-5 container mx-auto flex justify-between items-center">
            <div class="flex items-center gap-2.5">
                <a href="dashboard_siswa.php" class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 hover:bg-[#6C5CE7] hover:text-white transition-colors duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <h1 class="font-bold text-slate-800 text-xl tracking-tight">Riwayat Konsultasi</h1>
            </div>
             <div class="flex gap-6 items-center">
                <span class="text-slate-600 text-sm font-medium hidden md:inline"><?= htmlspecialchars($student['nama_lengkap']) ?></span>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 max-w-5xl">
        
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-100">
                <h2 class="text-2xl font-bold text-slate-800">Daftar Sesi Konseling</h2>
                <p class="text-slate-500 mt-1">Berikut adalah rekam jejak konseling yang pernah kamu ajukan.</p>
            </div>

            <?php if($res_history && $res_history->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider font-bold">
                                <th class="p-6">Tanggal & Waktu</th>
                                <th class="p-6">Konselor</th>
                                <th class="p-6">Topik</th>
                                <th class="p-6">Status</th>
                                <th class="p-6">Catatan / Solusi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php while($row = $res_history->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-6 text-sm whitespace-nowrap">
                                        <div class="font-bold text-slate-700"><?= date('d M Y', strtotime($row['tanggal_konsultasi'])) ?></div>
                                        <div class="text-slate-400 text-xs"><?= date('H:i', strtotime($row['tanggal_konsultasi'])) ?> WIB</div>
                                    </td>
                                    <td class="p-6 text-sm font-medium text-slate-700">
                                        <?= $row['nama_konselor'] ?>
                                    </td>
                                    <td class="p-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            <?= $row['kategori_topik'] ?>
                                        </span>
                                    </td>
                                    <td class="p-6">
                                        <?php
                                            $status_class = '';
                                            $status_label = ucfirst($row['status']);
                                            
                                            switch($row['status']) {
                                                case 'menunggu': 
                                                    $status_class = 'bg-yellow-50 text-yellow-700 border-yellow-200'; 
                                                    break;
                                                case 'disetujui': 
                                                    $status_class = 'bg-green-50 text-green-700 border-green-200'; 
                                                    break;
                                                case 'ditolak': 
                                                    $status_class = 'bg-red-50 text-red-700 border-red-200'; 
                                                    break;
                                                case 'selesai': 
                                                    $status_class = 'bg-blue-50 text-blue-700 border-blue-200'; 
                                                    break;
                                                default:
                                                    $status_class = 'bg-slate-50 text-slate-600 border-slate-200';
                                            }
                                        ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border <?= $status_class ?>">
                                            <?= $status_label ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-sm text-slate-600 max-w-xs">
                                        <?php if($row['solusi_diberikan']): ?>
                                            <div class="bg-blue-50/50 border border-blue-100 p-3 rounded-xl">
                                                <p class="text-xs font-bold text-blue-600 mb-1">Hasil Konseling:</p>
                                                <p class="leading-relaxed"><?= $row['solusi_diberikan'] ?></p>
                                            </div>
                                        <?php elseif($row['status'] == 'ditolak'): ?>
                                            <span class="text-slate-400 italic">Permintaan ditolak oleh guru.</span>
                                        <?php elseif($row['status'] == 'menunggu'): ?>
                                            <span class="text-slate-400 italic">Menunggu konfirmasi...</span>
                                        <?php else: ?>
                                            <span class="text-slate-400 italic">Belum ada catatan.</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-16 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-300"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">Belum Ada Riwayat</h3>
                    <p class="text-slate-500 mb-8 max-w-xs mx-auto">Kamu belum pernah melakukan sesi konsultasi. Yuk, jadwalkan sesi pertamamu!</p>
                    <a href="dashboard_siswa.php" class="inline-flex items-center gap-2 bg-[#6C5CE7] hover:bg-[#5B4ED1] text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-purple-200">
                        Buat Janji Sekarang
                    </a>
                </div>
            <?php endif; ?>

        </div>

    </div>

</body>
</html>
