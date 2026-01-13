<?php
session_start();
include 'config/database.php';

// Cek Sesi Konselor
if (!isset($_SESSION['user_id']) || $_SESSION['peran'] != 'konselor') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID Konsultasi tidak ditemukan.");
}

$id_konsultasi = $_GET['id'];

// Fetch Consultation Data (Validation)
$stmt_check = $conn->prepare("SELECT k.*, s.nama_lengkap as nama_siswa 
              FROM konsultasi k 
              JOIN siswa s ON k.id_siswa = s.id 
              WHERE k.id = ? AND k.status = 'disetujui'");
$stmt_check->bind_param("i", $id_konsultasi);
$stmt_check->execute();
$res_check = $stmt_check->get_result();

if ($res_check->num_rows == 0) {
    die("Data konsultasi tidak ditemukan atau status belum disetujui.");
}

$data = $res_check->fetch_assoc();

// Handle Form Submission
$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_laporan'])) {
    $inti = $_POST['inti_masalah'];
    $solusi = $_POST['solusi'];
    $rahasia = $_POST['catatan_rahasia'];
    $tindak_lanjut = isset($_POST['tindak_lanjut']) ? 1 : 0;

    // Transaction
    $conn->begin_transaction();
    try {
        // 1. Insert ke laporan_konsultasi
        $stmt = $conn->prepare("INSERT INTO laporan_konsultasi (id_konsultasi, inti_masalah, solusi_diberikan, catatan_rahasia, perlu_tindak_lanjut) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $id_konsultasi, $inti, $solusi, $rahasia, $tindak_lanjut);
        $stmt->execute();

        // 2. Update status konsultasi jadi 'selesai'
        $conn->query("UPDATE konsultasi SET status = 'selesai' WHERE id = '$id_konsultasi'");

        $conn->commit();
        header("Location: dashboard_guru.php?msg=success_report");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "Gagal menyimpan laporan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Tulis Laporan Konsultasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <style>.lexend-font { font-family: "Lexend", sans-serif; }</style>
</head>
<body class="bg-slate-50 lexend-font py-10">

    <div class="max-w-3xl mx-auto px-6">
        <a href="dashboard_guru.php" class="text-slate-500 hover:text-[#6C5CE7] flex items-center gap-2 mb-6 font-bold text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Kembali ke Dashboard
        </a>

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-[#6C5CE7] p-8 text-white">
                <h1 class="text-2xl font-bold mb-2">Laporan Hasil Konseling</h1>
                <p class="text-purple-200">Selesaikan sesi dengan <?= htmlspecialchars($data['nama_siswa']) ?></p>
            </div>
            
            <div class="p-8">
                <?php if($msg): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6"><?= $msg ?></div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    
                    <!-- Info Readonly -->
                    <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <div>
                            <span class="block text-xs uppercase font-bold text-slate-400 mb-1">Topik</span>
                            <span class="font-bold text-slate-700"><?= $data['kategori_topik'] ?></span>
                        </div>
                        <div>
                            <span class="block text-xs uppercase font-bold text-slate-400 mb-1">Tanggal</span>
                            <span class="font-bold text-slate-700"><?= date('d M Y', strtotime($data['tanggal_konsultasi'])) ?></span>
                        </div>
                    </div>

                    <!-- Input Fields -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Inti Masalah</label>
                        <textarea name="inti_masalah" required rows="3" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:border-[#6C5CE7] focus:ring-4 focus:ring-[#6C5CE7]/10" placeholder="Apa masalah utama yang didiskusikan?"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Solusi / Pesan untuk Siswa</label>
                        <p class="text-xs text-slate-400 mb-2 flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg> Akan dilihat oleh siswa di menu Riwayat.</p>
                        <textarea name="solusi" required rows="4" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:border-[#6C5CE7] focus:ring-4 focus:ring-[#6C5CE7]/10" placeholder="Berikan saran, tugas, atau kesimpulan..."></textarea>
                    </div>

                    <div class="bg-red-50 p-5 rounded-xl border border-red-100">
                        <label class="block text-sm font-bold text-red-800 mb-2 flex items-center gap-2">
                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                             Catatan Rahasia (Opsional)
                        </label>
                        <p class="text-xs text-red-600/70 mb-2">Hanya dapat dilihat oleh Guru BK.</p>
                        <textarea name="catatan_rahasia" rows="2" class="w-full bg-white border border-red-200 rounded-xl px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-500/10 placeholder:text-red-300" placeholder="Catatan pribadi konselor..."></textarea>
                    </div>

                    <div class="flex items-center gap-3 py-2">
                        <input type="checkbox" name="tindak_lanjut" id="tl" class="w-5 h-5 text-[#6C5CE7] rounded focus:ring-[#6C5CE7]">
                        <label for="tl" class="font-medium text-slate-700 cursor-pointer select-none">Perlu sesi tindak lanjut?</label>
                    </div>

                    <button type="submit" name="submit_laporan" class="w-full bg-[#6C5CE7] hover:bg-[#5B4ED1] text-white font-bold py-4 rounded-xl shadow-lg transition transform active:scale-[0.99]">
                        Simpan Laporan & Selesaikan Sesi
                    </button>
                    
                </form>
            </div>
        </div>
    </div>

</body>
</html>
