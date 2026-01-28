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

// Fetch mental health history
$sql_history = "SELECT skor, skor_numerik, terakhir_diperbarui, ringkasan_hasil 
                FROM hasil_asesmen 
                WHERE id_siswa = '$id_siswa' AND kategori = 'kesehatan_mental' 
                ORDER BY terakhir_diperbarui ASC";
$res_history = $conn->query($sql_history);

$dates = [];
$raw_dates = [];
$scores = [];
$history_rows = [];

while ($row = $res_history->fetch_assoc()) {
    $dates[] = date('d M Y', strtotime($row['terakhir_diperbarui']));
    $raw_dates[] = date('Y-m-d', strtotime($row['terakhir_diperbarui'])); // Raw date for JS filtering
    // Fallback if numerical score is null (for old records)
    $val = ($row['skor_numerik'] !== null) ? $row['skor_numerik'] : ($row['skor'] == 'Stabil' ? 80 : 40);
    $scores[] = $val;
    $history_rows[] = $row;
}

// Latest analysis
$latest = end($history_rows);
$trend_text = "Pertahankan kesehatan mentalmu!";
if (count($scores) >= 2) {
    $diff = $scores[count($scores)-1] - $scores[count($scores)-2];
    if ($diff > 5) $trend_text = "Ada peningkatan positif dari asesmen sebelumnya. Bagus!";
    elseif ($diff < -5) $trend_text = "Skor kamu sedikit menurun. Jangan ragu untuk bercerita ke guru BK ya.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Progress Kesehatan Mental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .lexend-font { font-family: "Lexend", sans-serif; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
    </style>
</head>
<body class="bg-slate-50 lexend-font min-h-screen text-slate-800">

    <nav class="bg-white/80 backdrop-blur-md border-b border-slate-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="dashboard_siswa.php" class="p-2 hover:bg-slate-100 rounded-full transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-[#6C5CE7] rounded-lg flex items-center justify-center text-white shadow-lg shadow-purple-200">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <h1 class="font-bold text-slate-800 text-xl tracking-tight">Progress Mental</h1>
                </div>
            </div>
            <span class="text-slate-500 text-sm font-medium hidden md:inline"><?= htmlspecialchars($student['nama_lengkap']) ?></span>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto p-6 space-y-8 animate-fade-in-up">
        
        <!-- Hero Banner -->
        <div class="bg-gradient-to-r from-teal-500 to-teal-600 text-white p-8 md:p-10 rounded-3xl shadow-xl shadow-teal-100 relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-3xl font-bold mb-3 tracking-tight">Halo, <?= explode(' ', $student['nama_lengkap'])[0] ?>! 👋</h2>
                <p class="text-teal-50 text-lg max-w-2xl mb-6 font-light leading-relaxed">Berikut adalah catatan perkembangan kesehatan mentalmu. Teruslah jaga semangat dan kesejahteraanmu ya!</p>
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 backdrop-blur-sm border border-white/30 px-5 py-2 rounded-2xl">
                        <span class="text-xs font-bold uppercase tracking-wider block opacity-70">Skor Terakhir</span>
                        <span class="text-2xl font-bold"><?= $latest['skor_numerik'] ?? '-' ?></span>
                    </div>
                    <div class="bg-white/20 backdrop-blur-sm border border-white/30 px-5 py-2 rounded-2xl">
                        <span class="text-xs font-bold uppercase tracking-wider block opacity-70">Status</span>
                        <span class="text-xl font-bold"><?= $latest['skor'] ?? '-' ?></span>
                    </div>
                </div>
            </div>
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-48 h-48 bg-teal-400 opacity-20 rounded-full blur-3xl"></div>
        </div>
        
        <!-- Top Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100/50 hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-16 h-16 bg-teal-50 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500"></div>
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-2 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                    Skor Terbaru
                </p>
                <h3 class="text-3xl font-bold <?= ($latest['skor_numerik'] ?? 0) >= 60 ? 'text-teal-600' : 'text-rose-500' ?>">
                    <?= $latest['skor_numerik'] ?? '-' ?>
                </h3>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100/50 hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-16 h-16 bg-indigo-50 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500"></div>
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-2 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#6C5CE7]"></span>
                    Status
                </p>
                <h3 class="text-xl font-bold text-slate-700"><?= $latest['skor'] ?? 'Belum ada data' ?></h3>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100/50 hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-16 h-16 bg-amber-50 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500"></div>
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-2 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    Trend
                </p>
                <p class="text-sm text-slate-600 font-medium leading-tight"><?= $trend_text ?></p>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 relative overflow-hidden group/chart">
            <div class="absolute -bottom-6 -right-6 w-32 h-32 bg-teal-50/50 rounded-full blur-2xl group-hover/chart:bg-teal-100/50 transition-colors duration-500"></div>
            <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-8 relative z-10">
                <div>
                    <h3 class="font-bold text-lg text-slate-800">Grafik Perkembangan</h3>
                    <p class="text-slate-400 text-xs mt-1">Estimasi kesehatan mental dari waktu ke waktu</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="bg-slate-50 p-1 rounded-xl flex gap-1 border border-slate-100">
                        <button onclick="filterChart('all')" id="btn-all" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg bg-white shadow-sm text-[#6C5CE7] hover:bg-white transition-all transform active:scale-95">All</button>
                        <button onclick="filterChart(30)" id="btn-30" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100/50 transition-all transform active:scale-95">30D</button>
                        <button onclick="filterChart(7)" id="btn-7" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100/50 transition-all transform active:scale-95">7D</button>
                    </div>
                    <div class="h-8 w-px bg-slate-100 mx-1"></div>
                    <a href="modul_asesmen_3.php" class="p-2 bg-teal-50 text-teal-600 rounded-xl hover:bg-teal-600 hover:text-white transition-all shadow-sm shadow-teal-100" title="Update Asesmen">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    </a>
                </div>
            </div>
            <div class="h-80 w-full relative z-10">
                <?php if (count($scores) > 0): ?>
                    <canvas id="mentalChart"></canvas>
                <?php else: ?>
                    <div class="h-full flex flex-col items-center justify-center text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3 opacity-20"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                        <p>Belum ada data history asesmen.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- History Table -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50">
                <h3 class="font-bold text-slate-800">Riwayat Lengkap</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 text-slate-400 text-xs font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Tanggal</th>
                            <th class="px-6 py-4">Skor</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach (array_reverse($history_rows) as $row): ?>
                        <tr class="hover:bg-slate-50/50 transition group">
                            <td class="px-6 py-4 text-sm font-medium text-slate-600"><?= date('d M Y, H:i', strtotime($row['terakhir_diperbarui'])) ?></td>
                            <td class="px-6 py-4">
                                <span class="font-bold <?= ($row['skor_numerik'] ?? 0) >= 60 ? 'text-teal-600' : 'text-rose-500' ?>">
                                    <?= $row['skor_numerik'] ?? '-' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold <?= ($row['skor_numerik'] ?? 0) >= 60 ? 'bg-teal-50 text-teal-700' : 'bg-rose-50 text-rose-700' ?>">
                                    <?= $row['skor'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-400">
                                <?php 
                                    $answers = json_decode($row['ringkasan_hasil'], true);
                                    if ($answers && $answers['q5_bullying'] == 'Ya') echo '<span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-amber-50 text-amber-700 text-[10px] font-bold uppercase tracking-wide border border-amber-100">⚠️ Terdeteksi Bullying</span>';
                                    else echo '<span class="text-slate-300">-</span>';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        <?php if (count($scores) > 0): ?>
        // Store original data
        const allDates = <?= json_encode($dates) ?>;
        const allRawDates = <?= json_encode($raw_dates) ?>;
        const allScores = <?= json_encode($scores) ?>;
        
        const ctx = document.getElementById('mentalChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(20, 184, 166, 0.2)');
        gradient.addColorStop(1, 'rgba(20, 184, 166, 0)');

        let mentalChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: allDates,
                datasets: [{
                    label: 'Skor Kesehatan Mental',
                    data: allScores,
                    borderColor: '#14b8a6',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#14b8a6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 12,
                        titleFont: { size: 10 },
                        bodyFont: { weight: 'bold' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });

        function filterChart(days) {
            // Update buttons styling
            const buttons = ['all', '30', '7'];
            const activeClasses = ['bg-white', 'shadow-sm', 'text-[#6C5CE7]'];
            const inactiveClasses = ['text-slate-400', 'hover:text-slate-600', 'hover:bg-slate-100/50'];

            buttons.forEach(btn => {
                const el = document.getElementById(`btn-${btn}`);
                const isSelected = (days === 'all' && btn === 'all') || (days == btn);
                
                if (isSelected) {
                    el.classList.remove(...inactiveClasses);
                    el.classList.add(...activeClasses);
                } else {
                    el.classList.remove(...activeClasses);
                    el.classList.add(...inactiveClasses);
                }
            });

            // Filter Logic
            if (days === 'all') {
                mentalChart.data.labels = allDates;
                mentalChart.data.datasets[0].data = allScores;
            } else {
                const cutoffDate = new Date();
                cutoffDate.setDate(cutoffDate.getDate() - parseInt(days));

                const filteredDates = [];
                const filteredScores = [];

                for (let i = 0; i < allRawDates.length; i++) {
                    const recordDate = new Date(allRawDates[i]);
                    // Include if record date is new enough
                    if (recordDate >= cutoffDate) {
                        filteredDates.push(allDates[i]);
                        filteredScores.push(allScores[i]);
                    }
                }

                mentalChart.data.labels = filteredDates;
                mentalChart.data.datasets[0].data = filteredScores;
            }

            mentalChart.update();
        }
        <?php endif; ?>
    </script>
</body>
</html>
