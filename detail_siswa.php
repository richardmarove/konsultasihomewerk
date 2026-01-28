<?php
session_start();
include 'config/database.php';

// Cek Sesi Konselor
if (!isset($_SESSION['user_id']) || $_SESSION['peran'] != 'konselor') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID Siswa tidak ditemukan.");
}

$id_siswa = $_GET['id'];

// 1. Ambil Data Siswa & Keluarga
$sql_siswa = "SELECT s.*, k.* 
              FROM siswa s 
              LEFT JOIN detail_keluarga_siswa k ON s.id = k.id_siswa 
              WHERE s.id = '$id_siswa'";
$res_siswa = $conn->query($sql_siswa);
$data_siswa = $res_siswa->fetch_assoc();

if (!$data_siswa) {
    die("Data siswa tidak ditemukan.");
}

// 2. Ambil Data Asesmen
$sql_asesmen = "SELECT kategori, ringkasan_hasil, skor FROM hasil_asesmen WHERE id_siswa = '$id_siswa'";
$res_asesmen = $conn->query($sql_asesmen);

$data_asesmen = [];
$skor_asesmen = [];
while ($row = $res_asesmen->fetch_assoc()) {
    $data_asesmen[$row['kategori']] = json_decode($row['ringkasan_hasil'], true);
    $skor_asesmen[$row['kategori']] = $row['skor'];
}

// 3. Ambil History Mental Health untuk Chart
$sql_history = "SELECT skor_numerik, terakhir_diperbarui FROM hasil_asesmen WHERE id_siswa = '$id_siswa' AND kategori = 'kesehatan_mental' ORDER BY terakhir_diperbarui ASC";
$res_history = $conn->query($sql_history);
$history_dates = [];
$history_scores = [];
while ($h = $res_history->fetch_assoc()) {
    $history_dates[] = date('d/m', strtotime($h['terakhir_diperbarui']));
    $history_scores[] = $h['skor_numerik'] ?? ($h['skor'] == 'Stabil' ? 80 : 40);
}


// Helper
function getVal($array, $key, $default = '-') {
    return isset($array[$key]) ? $array[$key] : $default;
}

function showArray($array) {
    if (empty($array)) return '-';
    return implode(", ", $array);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Detail Siswa - <?= htmlspecialchars($data_siswa['nama_lengkap']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>.lexend-font { font-family: "Lexend", sans-serif; }</style>
</head>
<body class="bg-slate-50 lexend-font py-10">

    <div class="max-w-4xl mx-auto bg-white shadow-xl rounded-xl overflow-hidden">
        
        <!-- Header -->
        <div class="bg-blue-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold"><?= htmlspecialchars($data_siswa['nama_lengkap']) ?></h2>
                <p class="text-blue-100 text-sm"><?= $data_siswa['tingkat_kelas'] ?> <?= $data_siswa['jurusan'] ?></p>
            </div>
            <a href="dashboard_guru.php" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded transition text-sm">Kembali</a>
        </div>

        <div class="p-8 space-y-10">

            <!-- 1. Data Keluarga -->
            <section>
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2 mb-4">1. Data Keluarga & Ekonomi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 text-sm">
                    <div>
                        <span class="block text-slate-500">Nama Ayah</span>
                        <span class="font-medium text-slate-800"><?= getVal($data_siswa, 'nama_ayah') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Pekerjaan Ayah</span>
                        <span class="font-medium text-slate-800"><?= getVal($data_siswa, 'pekerjaan_ayah') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Nama Ibu</span>
                        <span class="font-medium text-slate-800"><?= getVal($data_siswa, 'nama_ibu') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Pekerjaan Ibu</span>
                        <span class="font-medium text-slate-800"><?= getVal($data_siswa, 'pekerjaan_ibu') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Status Ekonomi</span>
                        <span class="font-medium text-slate-800"><?= getVal($data_siswa, 'status_ekonomi') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Jumlah Saudara</span>
                        <span class="font-medium text-slate-800"><?= getVal($data_siswa, 'jumlah_saudara') ?></span>
                    </div>
                    <div class="md:col-span-2">
                        <span class="block text-slate-500">Alamat</span>
                        <span class="font-medium text-slate-800"><?= getVal($data_siswa, 'alamat') ?></span>
                    </div>
                </div>
            </section>

            <!-- 2. Kondisi Sosial -->
            <?php $kep = getVal($data_asesmen, 'kepribadian', []); ?>
            <section>
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2 mb-4">2. Kondisi Sosial</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 text-sm">
                    <div>
                        <span class="block text-slate-500">Status Orang Tua</span>
                        <span class="font-medium text-slate-800"><?= getVal($kep, 'q1_status_ortu') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Suasana Rumah</span>
                        <span class="font-medium text-slate-800"><?= getVal($kep, 'q2_status_ortu') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Penerima KIP/KKS</span>
                        <span class="font-medium text-slate-800"><?= getVal($kep, 'q3_status_ortu') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Transportasi</span>
                        <span class="font-medium text-slate-800"><?= getVal($kep, 'q4_status_ortu') ?></span>
                    </div>
                </div>
            </section>

            <!-- 3. Gaya Belajar -->
            <?php $gb = getVal($data_asesmen, 'gaya_belajar', []); ?>
            <section>
                <div class="flex justify-between items-center border-b pb-2 mb-4">
                    <h3 class="text-lg font-bold text-slate-800">3. Gaya Belajar (VAK)</h3>
                    <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full">
                        Hasil: <?= getVal($skor_asesmen, 'gaya_belajar') ?>
                    </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 text-sm">
                    <div>
                        <span class="block text-slate-500">Cara Belajar Cepat</span>
                        <span class="font-medium text-slate-800"><?= getVal($gb, 'q1_gaya_belajar') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Aktivitas Luang</span>
                        <span class="font-medium text-slate-800"><?= getVal($gb, 'q2_gaya_belajar') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Mengingat Jalan</span>
                        <span class="font-medium text-slate-800"><?= getVal($gb, 'q3_gaya_belajar') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Ekspresi Marah</span>
                        <span class="font-medium text-slate-800"><?= getVal($gb, 'q4_gaya_belajar') ?></span>
                    </div>
                </div>
            </section>

            <!-- 4. Kesehatan Mental -->
            <?php $km = getVal($data_asesmen, 'kesehatan_mental', []); ?>
            <section>
                <div class="flex justify-between items-center border-b pb-2 mb-4">
                    <h3 class="text-lg font-bold text-slate-800">4. Kesehatan Mental</h3>
                    <?php if(strpos(getVal($skor_asesmen, 'kesehatan_mental'), 'PERLU PERHATIAN') !== false): ?>
                        <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-1 rounded-full">PERLU PERHATIAN</span>
                    <?php else: ?>
                        <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full">Stabil</span>
                    <?php endif; ?>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 text-sm">
                    <div>
                        <span class="block text-slate-500">Nyaman & Punya Teman?</span>
                        <span class="font-medium text-slate-800"><?= getVal($km, 'q1_nyaman_teman') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Cemas Berlebihan?</span>
                        <span class="font-medium text-slate-800"><?= getVal($km, 'q2_cemas') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Mudah Bercerita?</span>
                        <span class="font-medium text-slate-800"><?= getVal($km, 'q3_cerita') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Tekanan Akademik?</span>
                        <span class="font-medium text-slate-800"><?= getVal($km, 'q4_tekanan_akademik') ?></span>
                    </div>
                    <div class="md:col-span-2">
                        <span class="block text-slate-500">Pengalaman Bullying?</span>
                        <span class="font-bold <?= getVal($km, 'q5_bullying') == 'Ya' ? 'text-red-600' : 'text-slate-800' ?>">
                            <?= getVal($km, 'q5_bullying') ?>
                        </span>
                    </div>
                </div>

                <!-- Progress Chart for Counselor -->
                <div class="mt-8 bg-slate-50 p-6 rounded-xl border border-slate-100">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Grafik Trend Kesehatan Mental</h4>
                    <div class="h-48 w-full">
                        <?php if (count($history_scores) > 0): ?>
                            <canvas id="mentalChart"></canvas>
                        <?php else: ?>
                            <p class="text-sm text-slate-400 text-center py-10">Belum ada data history.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>


            <!-- 5. Minat Karir -->
            <?php $mk = getVal($data_asesmen, 'minat_karir', []); ?>
            <section>
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2 mb-4">5. Minat Karir</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 text-sm">
                    <div>
                        <span class="block text-slate-500">Rencana Lulus</span>
                        <span class="font-medium text-slate-800"><?= getVal($mk, 'rencana_lulus') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500">Bidang Pekerjaan</span>
                        <span class="font-medium text-slate-800"><?= getVal($mk, 'minat_pekerjaan') ?></span>
                    </div>
                    <div class="md:col-span-2">
                        <span class="block text-slate-500">Mapel Favorit</span>
                        <span class="font-medium text-slate-800"><?= showArray(getVal($mk, 'mapel_favorit', [])) ?></span>
                    </div>
                </div>
            </section>

        </div>
    </div>

    <script>
        <?php if (count($history_scores) > 0): ?>
        new Chart(document.getElementById('mentalChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($history_dates) ?>,
                datasets: [{
                    label: 'Skor',
                    data: <?= json_encode($history_scores) ?>,
                    borderColor: '#3b82f6',
                    tension: 0.3,
                    fill: false,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, display: false },
                    x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>

