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
$user_id = $_SESSION['user_id'];

// Get konselor ID
$sql_konselor = "SELECT id FROM konselor WHERE id_pengguna = '$user_id'";
$res_konselor = $conn->query($sql_konselor);
$konselor = $res_konselor->fetch_assoc();
$id_konselor = $konselor['id'];

// Fetch Consultation + Report Data with validation
$sql = "
    SELECT 
        k.*,
        s.nama_lengkap as nama_siswa,
        s.nis,
        s.tingkat_kelas,
        s.jurusan,
        s.jenis_kelamin,
        c.nama_lengkap as nama_konselor,
        c.nip,
        l.inti_masalah,
        l.solusi_diberikan,
        l.perlu_tindak_lanjut,
        l.catatan_rahasia,
        l.created_at as tanggal_laporan
    FROM konsultasi k
    JOIN siswa s ON k.id_siswa = s.id
    JOIN konselor c ON k.id_konselor = c.id
    LEFT JOIN laporan_konsultasi l ON k.id = l.id_konsultasi
    WHERE k.id = ? AND k.id_konselor = ? AND k.status = 'selesai'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_konsultasi, $id_konselor);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Data laporan tidak ditemukan atau Anda tidak memiliki akses.");
}

$data = $result->fetch_assoc();

// Fetch latest assessment data for context (optional)
$sql_assessment = "
    SELECT kategori, skor, skor_numerik, terakhir_diperbarui
    FROM hasil_asesmen
    WHERE id_siswa = ?
    ORDER BY terakhir_diperbarui DESC
    LIMIT 3
";
$stmt_assessment = $conn->prepare($sql_assessment);
$stmt_assessment->bind_param("i", $data['id_siswa']);
$stmt_assessment->execute();
$res_assessment = $stmt_assessment->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Konseling - <?= htmlspecialchars($data['nama_siswa']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <style>
        .lexend-font { font-family: "Lexend", sans-serif; }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .print-shadow { box-shadow: none !important; }
            .print-page { page-break-after: always; }
        }
    </style>
</head>
<body class="bg-slate-50 lexend-font py-8">

    <!-- Navigation (Hidden on Print) -->
    <div class="no-print max-w-5xl mx-auto px-6 mb-6 flex justify-between items-center">
        <a href="dashboard_guru.php" class="text-slate-500 hover:text-[#6C5CE7] flex items-center gap-2 font-bold text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Kembali ke Dashboard
        </a>
        <button onclick="window.print()" class="bg-[#6C5CE7] hover:bg-[#5B4ED1] text-white px-6 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 transition-all shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
            Cetak Laporan
        </button>
    </div>

    <!-- Report Container -->
    <div class="max-w-5xl mx-auto px-6">
        <div class="bg-white rounded-2xl shadow-xl print-shadow overflow-hidden">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-[#6C5CE7] to-[#5B4ED1] p-8 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">Laporan Hasil Konseling</h1>
                        <p class="text-purple-200">Dokumentasi Sesi Bimbingan dan Konseling</p>
                    </div>
                    <div class="text-right text-sm">
                        <p class="text-purple-200">Tanggal Laporan</p>
                        <p class="font-bold"><?= date('d F Y', strtotime($data['tanggal_laporan'] ?? $data['created_at'])) ?></p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8 space-y-8">
                
                <!-- Session Information -->
                <section>
                    <h2 class="text-xl font-bold text-slate-800 mb-4 pb-2 border-b-2 border-[#6C5CE7]">Informasi Sesi</h2>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <span class="block text-xs uppercase font-bold text-slate-400 mb-1">Nama Siswa</span>
                                <span class="font-bold text-slate-700 text-lg"><?= htmlspecialchars($data['nama_siswa']) ?></span>
                            </div>
                            <div>
                                <span class="block text-xs uppercase font-bold text-slate-400 mb-1">NIS</span>
                                <span class="font-medium text-slate-700"><?= htmlspecialchars($data['nis']) ?></span>
                            </div>
                            <div>
                                <span class="block text-xs uppercase font-bold text-slate-400 mb-1">Kelas / Jurusan</span>
                                <span class="font-medium text-slate-700">Kelas <?= $data['tingkat_kelas'] ?> - <?= htmlspecialchars($data['jurusan']) ?></span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <span class="block text-xs uppercase font-bold text-slate-400 mb-1">Konselor</span>
                                <span class="font-bold text-slate-700 text-lg"><?= htmlspecialchars($data['nama_konselor']) ?></span>
                            </div>
                            <div>
                                <span class="block text-xs uppercase font-bold text-slate-400 mb-1">Tanggal & Waktu Sesi</span>
                                <span class="font-medium text-slate-700"><?= date('d F Y, H:i', strtotime($data['tanggal_konsultasi'])) ?> WIB</span>
                            </div>
                            <div>
                                <span class="block text-xs uppercase font-bold text-slate-400 mb-1">Kategori Topik</span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <?= htmlspecialchars($data['kategori_topik']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Student Assessment Context (if available) -->
                <?php if($res_assessment->num_rows > 0): ?>
                <section>
                    <h2 class="text-xl font-bold text-slate-800 mb-4 pb-2 border-b-2 border-[#6C5CE7]">Konteks Asesmen Siswa</h2>
                    <div class="bg-slate-50 p-5 rounded-xl border border-slate-200">
                        <p class="text-xs text-slate-500 mb-3">Riwayat asesmen terbaru untuk konteks:</p>
                        <div class="grid grid-cols-3 gap-4">
                            <?php while($assessment = $res_assessment->fetch_assoc()): ?>
                                <div class="bg-white p-4 rounded-lg border border-slate-100">
                                    <p class="text-xs uppercase font-bold text-slate-400 mb-1"><?= ucfirst(str_replace('_', ' ', $assessment['kategori'])) ?></p>
                                    <p class="font-bold text-slate-700"><?= htmlspecialchars($assessment['skor']) ?></p>
                                    <p class="text-xs text-slate-400 mt-1"><?= date('d M Y', strtotime($assessment['terakhir_diperbarui'])) ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Original Complaint -->
                <section>
                    <h2 class="text-xl font-bold text-slate-800 mb-4 pb-2 border-b-2 border-[#6C5CE7]">Keluhan Awal Siswa</h2>
                    <div class="bg-blue-50/50 p-6 rounded-xl border border-blue-100">
                        <p class="text-slate-700 leading-relaxed"><?= nl2br(htmlspecialchars($data['deskripsi_keluhan'])) ?></p>
                    </div>
                </section>

                <!-- Problem Summary -->
                <?php if($data['inti_masalah']): ?>
                <section>
                    <h2 class="text-xl font-bold text-slate-800 mb-4 pb-2 border-b-2 border-[#6C5CE7]">Inti Masalah (Analisis Konselor)</h2>
                    <div class="bg-amber-50/50 p-6 rounded-xl border border-amber-100">
                        <p class="text-slate-700 leading-relaxed"><?= nl2br(htmlspecialchars($data['inti_masalah'])) ?></p>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Solution Given -->
                <?php if($data['solusi_diberikan']): ?>
                <section>
                    <h2 class="text-xl font-bold text-slate-800 mb-4 pb-2 border-b-2 border-[#6C5CE7]">Solusi & Saran yang Diberikan</h2>
                    <div class="bg-green-50/50 p-6 rounded-xl border border-green-100">
                        <p class="text-slate-700 leading-relaxed"><?= nl2br(htmlspecialchars($data['solusi_diberikan'])) ?></p>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Follow-up Status -->
                <section>
                    <h2 class="text-xl font-bold text-slate-800 mb-4 pb-2 border-b-2 border-[#6C5CE7]">Status Tindak Lanjut</h2>
                    <div class="flex items-center gap-3">
                        <?php if($data['perlu_tindak_lanjut']): ?>
                            <div class="flex items-center gap-2 bg-orange-50 text-orange-700 px-4 py-3 rounded-xl border border-orange-200">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                <span class="font-bold">Perlu Sesi Tindak Lanjut</span>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center gap-2 bg-green-50 text-green-700 px-4 py-3 rounded-xl border border-green-200">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                <span class="font-bold">Tidak Perlu Tindak Lanjut</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Confidential Notes (Konselor Only) -->
                <?php if($data['catatan_rahasia']): ?>
                <section class="no-print">
                    <h2 class="text-xl font-bold text-red-800 mb-4 pb-2 border-b-2 border-red-500 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Catatan Rahasia Konselor
                    </h2>
                    <div class="bg-red-50 p-6 rounded-xl border-2 border-red-200">
                        <p class="text-xs text-red-600 font-bold mb-3 uppercase">⚠️ Hanya untuk Konselor - Tidak untuk dipublikasikan</p>
                        <p class="text-slate-700 leading-relaxed"><?= nl2br(htmlspecialchars($data['catatan_rahasia'])) ?></p>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Signature Section -->
                <section class="mt-12 pt-8 border-t-2 border-slate-200">
                    <div class="grid grid-cols-2 gap-12">
                        <div class="text-center">
                            <p class="text-sm text-slate-500 mb-16">Mengetahui,</p>
                            <div class="border-t-2 border-slate-300 pt-2">
                                <p class="font-bold text-slate-700"><?= htmlspecialchars($data['nama_konselor']) ?></p>
                                <p class="text-sm text-slate-500">NIP: <?= htmlspecialchars($data['nip']) ?></p>
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-slate-500 mb-16">Siswa,</p>
                            <div class="border-t-2 border-slate-300 pt-2">
                                <p class="font-bold text-slate-700"><?= htmlspecialchars($data['nama_siswa']) ?></p>
                                <p class="text-sm text-slate-500">NIS: <?= htmlspecialchars($data['nis']) ?></p>
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            <!-- Footer -->
            <div class="bg-slate-50 p-6 text-center border-t border-slate-200">
                <p class="text-xs text-slate-400">Dokumen ini dibuat secara otomatis oleh Sistem Bimbingan dan Konseling</p>
                <p class="text-xs text-slate-400 mt-1">Dicetak pada: <?= date('d F Y, H:i') ?> WIB</p>
            </div>

        </div>
    </div>

</body>
</html>
