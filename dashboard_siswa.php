<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['peran'] != 'siswa') {
    header("Location: index.php");
    exit;
}

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
    
    // Tentukan Dominan untuk Deskripsi
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
        <h1 class="font-bold text-blue-600 text-xl tracking-tight">Aplikasi BK</h1>
        <div class="flex gap-4 items-center">
            <span class="text-slate-500 text-sm hidden md:inline"><?= htmlspecialchars($student['nama_lengkap']) ?></span>
            <a href="logout.php" class="text-red-500 text-sm font-medium hover:bg-red-50 px-3 py-1 rounded transition">Keluar</a>
        </div>
    </nav>

    <div class="container mx-auto p-6 flex-grow">
        
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white p-8 rounded-2xl shadow-lg mb-8 relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-3xl md:text-4xl font-bold mb-2">Selamat Datang, <?= explode(' ', $student['nama_lengkap'])[0] ?>! 👋</h2>
                <p class="text-blue-100 text-lg max-w-2xl">Siap untuk mengenal dirimu lebih dalam hari ini? Jadwalkan konsultasi atau cek hasil asesmenmu di bawah ini.</p>
            </div>
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-blue-400 opacity-20 rounded-full blur-2xl"></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition">
                    <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M16 2v4"/></svg>
                        Jadwal Konsultasi
                    </h3>
                    <p class="text-slate-500 text-sm mb-6">Belum ada jadwal konsultasi yang akan datang.</p>
                    <button class="w-full bg-blue-50 text-blue-600 px-4 py-2.5 rounded-lg text-sm font-bold hover:bg-blue-100 transition">
                        Buat Janji Temu
                    </button>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 h-full">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-xl text-slate-800 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-500"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                            Profil Gaya Belajar
                        </h3>
                        <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full">
                            <?= $dominant_title ?>
                        </span>
                    </div>

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
                </div>
            </div>

        </div>
    </div>

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
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b'
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

</body>
</html>