<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['peran'] != 'admin') {
    header("Location: index.php");
    exit;
}

$msg = "";
$edit_data = null;
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'konselor';

// --- LOGIC KONSELOR ---
if ($current_tab == 'konselor') {
    // Ambil data untuk Edit
    if (isset($_GET['edit_id'])) {
        $edit_id = $_GET['edit_id'];
        $res_edit = $conn->query("SELECT k.*, u.email FROM konselor k JOIN user u ON k.id_pengguna = u.id WHERE u.id = '$edit_id'");
        if ($res_edit && $res_edit->num_rows > 0) {
            $edit_data = $res_edit->fetch_assoc();
        }
    }

    // Proses Tambah / Update Konselor
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_konselor']) || isset($_POST['edit_konselor']))) {
        $nama = $_POST['nama'];
        $nip = $_POST['nip'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $is_edit = isset($_POST['edit_konselor']);
        $user_id = $is_edit ? $_POST['user_id'] : null;
        
        $query_check = "SELECT id FROM user WHERE email='$email' " . ($is_edit ? "AND id != '$user_id'" : "");
        $query_check .= " UNION SELECT id FROM konselor WHERE nip='$nip' " . ($is_edit ? "AND id_pengguna != '$user_id'" : "");
        
        $check = $conn->query($query_check);
        
        if ($check && $check->num_rows > 0) {
            $msg = "<div class='bg-red-100 text-red-600 p-4 rounded-xl border border-red-200 mb-6 flex items-center gap-3'>
                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' viewBox='0 0 20 20' fill='currentColor'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' clip-rule='evenodd' /></svg>
                        Email atau NIP sudah terdaftar!
                    </div>";
        } else {
            $conn->begin_transaction();
            try {
                if ($is_edit) {
                    if (!empty($password)) {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt_u = $conn->prepare("UPDATE user SET email = ?, kata_sandi = ? WHERE id = ?");
                        $stmt_u->bind_param("ssi", $email, $hashed, $user_id);
                    } else {
                        $stmt_u = $conn->prepare("UPDATE user SET email = ? WHERE id = ?");
                        $stmt_u->bind_param("si", $email, $user_id);
                    }
                    $stmt_u->execute();

                    $stmt_k = $conn->prepare("UPDATE konselor SET nip = ?, nama_lengkap = ? WHERE id_pengguna = ?");
                    $stmt_k->bind_param("ssi", $nip, $nama, $user_id);
                    $stmt_k->execute();
                    
                    $conn->commit();
                    header("Location: dashboard_admin.php?tab=konselor&status=updated");
                    exit;
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_user = $conn->prepare("INSERT INTO user (email, kata_sandi, peran) VALUES (?, ?, 'konselor')");
                    $stmt_user->bind_param("ss", $email, $hashed);
                    $stmt_user->execute();
                    $new_id = $conn->insert_id;

                    $spesialisasi = "Konselor Umum";
                    $stmt_k = $conn->prepare("INSERT INTO konselor (id_pengguna, nip, nama_lengkap, spesialisasi) VALUES (?, ?, ?, ?)");
                    $stmt_k->bind_param("isss", $new_id, $nip, $nama, $spesialisasi);
                    $stmt_k->execute();

                    $conn->commit();
                    $msg = "<div class='bg-green-100 text-green-600 p-4 rounded-xl border border-green-200 mb-6 flex items-center gap-3'>
                                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' viewBox='0 0 20 20' fill='currentColor'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd' /></svg>
                                Konselor berhasil ditambahkan!
                            </div>";
                }
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "<div class='bg-red-100 text-red-600 p-4 rounded-xl border border-red-200 mb-6'>Error: " . $e->getMessage() . "</div>";
            }
        }
    }

    // Proses Hapus Konselor
    if (isset($_GET['delete_id'])) {
        $del_id = $_GET['delete_id'];
        $conn->query("DELETE FROM konselor WHERE id_pengguna = '$del_id'");
        $conn->query("DELETE FROM user WHERE id = '$del_id'");
        header("Location: dashboard_admin.php?tab=konselor");
        exit;
    }

    $sql_list = "SELECT k.*, u.email FROM konselor k JOIN user u ON k.id_pengguna = u.id";
    $res_list = $conn->query($sql_list);
}

// --- LOGIC SISWA ---
if ($current_tab == 'siswa') {
    // Ambil data untuk Edit
    if (isset($_GET['edit_id'])) {
        $edit_id = $_GET['edit_id'];
        $res_edit = $conn->query("SELECT s.*, u.email FROM siswa s JOIN user u ON s.id_pengguna = u.id WHERE u.id = '$edit_id'");
        if ($res_edit && $res_edit->num_rows > 0) {
            $edit_data = $res_edit->fetch_assoc();
        }
    }

    // Proses Tambah / Update Siswa
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_siswa']) || isset($_POST['edit_siswa']))) {
        $nama = $_POST['nama'];
        $nis = $_POST['nis'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $kelas = $_POST['tingkat_kelas'];
        $jurusan = $_POST['jurusan'];
        $gender = $_POST['jenis_kelamin'];
        $is_edit = isset($_POST['edit_siswa']);
        $user_id = $is_edit ? $_POST['user_id'] : null;

        $query_check = "SELECT id FROM user WHERE email='$email' " . ($is_edit ? "AND id != '$user_id'" : "");
        $query_check .= " UNION SELECT id FROM siswa WHERE nis='$nis' " . ($is_edit ? "AND id_pengguna != '$user_id'" : "");
        
        $check = $conn->query($query_check);
        
        if ($check && $check->num_rows > 0) {
            $msg = "<div class='bg-red-100 text-red-600 p-4 rounded-xl border border-red-200 mb-6 flex items-center gap-3'>
                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' viewBox='0 0 20 20' fill='currentColor'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' clip-rule='evenodd' /></svg>
                        Email atau NIS sudah terdaftar!
                    </div>";
        } else {
            $conn->begin_transaction();
            try {
                if ($is_edit) {
                    if (!empty($password)) {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt_u = $conn->prepare("UPDATE user SET email = ?, kata_sandi = ? WHERE id = ?");
                        $stmt_u->bind_param("ssi", $email, $hashed, $user_id);
                    } else {
                        $stmt_u = $conn->prepare("UPDATE user SET email = ? WHERE id = ?");
                        $stmt_u->bind_param("si", $email, $user_id);
                    }
                    $stmt_u->execute();

                    $stmt_s = $conn->prepare("UPDATE siswa SET nis = ?, nama_lengkap = ?, tingkat_kelas = ?, jurusan = ?, jenis_kelamin = ? WHERE id_pengguna = ?");
                    $stmt_s->bind_param("ssissi", $nis, $nama, $kelas, $jurusan, $gender, $user_id);
                    $stmt_s->execute();
                    
                    $conn->commit();
                    header("Location: dashboard_admin.php?tab=siswa&status=updated");
                    exit;
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_user = $conn->prepare("INSERT INTO user (email, kata_sandi, peran) VALUES (?, ?, 'siswa')");
                    $stmt_user->bind_param("ss", $email, $hashed);
                    $stmt_user->execute();
                    $new_id = $conn->insert_id;

                    $stmt_s = $conn->prepare("INSERT INTO siswa (id_pengguna, nis, nama_lengkap, tingkat_kelas, jurusan, jenis_kelamin) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_s->bind_param("ississ", $new_id, $nis, $nama, $kelas, $jurusan, $gender);
                    $stmt_s->execute();

                    $conn->commit();
                    $msg = "<div class='bg-green-100 text-green-600 p-4 rounded-xl border border-green-200 mb-6 flex items-center gap-3'>
                                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' viewBox='0 0 20 20' fill='currentColor'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd' /></svg>
                                Siswa berhasil ditambahkan!
                            </div>";
                }
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "<div class='bg-red-100 text-red-600 p-4 rounded-xl border border-red-200 mb-6'>Error: " . $e->getMessage() . "</div>";
            }
        }
    }

    // Proses Hapus Siswa
    if (isset($_GET['delete_id'])) {
        $del_id = $_GET['delete_id'];
        $conn->query("DELETE FROM siswa WHERE id_pengguna = '$del_id'");
        $conn->query("DELETE FROM user WHERE id = '$del_id'");
        header("Location: dashboard_admin.php?tab=siswa");
        exit;
    }

    $sql_list = "SELECT s.*, u.email FROM siswa s JOIN user u ON s.id_pengguna = u.id";
    $res_list = $conn->query($sql_list);
}

// Pesan Sukses Update (Global)
if (isset($_GET['status']) && $_GET['status'] == 'updated') {
    $msg = "<div class='bg-blue-100 text-blue-600 p-4 rounded-xl border border-blue-200 mb-6 flex items-center gap-3'>
                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' viewBox='0 0 20 20' fill='currentColor'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd' /></svg>
                Data berhasil diupdate!
            </div>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Bimbingan Konseling</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;800&family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <style>
        .lexend-font { font-family: "Lexend", sans-serif; }
        .plus-jakarta { font-family: "Plus Jakarta Sans", sans-serif; }
        .tab-active { position: relative; color: #2563eb; }
        .tab-active::after { content: ''; position: absolute; bottom: -4px; left: 0; right: 0; height: 3px; background: #2563eb; border-radius: 99px; }
    </style>
</head>
<body class="bg-[#f8fafc] lexend-font min-h-screen">

    <!-- Top Navigation -->
    <nav class="bg-white/80 backdrop-blur-md sticky top-0 z-[100] border-b border-slate-200">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="bg-blue-600 p-2.5 rounded-xl shadow-lg shadow-blue-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.040L3 20l9 2 9-2-1.382-14.016z" />
                    </svg>
                </div>
                <div>
                    <h1 class="plus-jakarta font-extrabold text-xl text-slate-900 leading-tight">Admin<span class="text-blue-600">Counsel</span></h1>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] leading-none">Management Portal</p>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Logged in sebagai</span>
                    <span class="text-sm font-bold text-slate-700">Administrator</span>
                </div>
                <div class="h-10 w-px bg-slate-200 hidden md:block"></div>
                <a href="logout.php" class="bg-red-50 text-red-600 hover:bg-red-100 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 flex items-center gap-2 group">
                    <span>Logout</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 md:p-8 lg:p-10">
        
        <!-- Welcome Header -->
        <div class="mb-10">
            <h2 class="text-3xl font-extrabold text-slate-900 mb-2">Manajemen Pengguna</h2>
            <p class="text-slate-500">Kelola akun konselor dan siswa dalam sistem bimbingan konseling.</p>
        </div>

        <!-- Tab Navigation -->
        <div class="flex items-center gap-8 border-b border-slate-200 mb-10 overflow-hidden">
            <a href="?tab=konselor" class="pb-4 text-sm font-bold transition-all whitespace-nowrap <?= $current_tab == 'konselor' ? 'tab-active' : 'text-slate-400 hover:text-slate-600' ?>">
                Daftar Konselor
            </a>
            <a href="?tab=siswa" class="pb-4 text-sm font-bold transition-all whitespace-nowrap <?= $current_tab == 'siswa' ? 'tab-active' : 'text-slate-400 hover:text-slate-600' ?>">
                Daftar Siswa
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <!-- Management Form -->
            <div class="lg:col-span-4">
                <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 sticky top-28">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="h-12 w-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                            <?php if ($edit_data): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-lg text-slate-800 leading-tight"><?= $edit_data ? 'Edit Data' : 'Tambah Baru' ?></h3>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wider"><?= $current_tab == 'konselor' ? 'Akun Konselor' : 'Akun Siswa' ?></p>
                        </div>
                    </div>
                    
                    <?= $msg ?>

                    <form method="POST" class="space-y-6">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="user_id" value="<?= $edit_data['id_pengguna'] ?>">
                        <?php endif; ?>

                        <div class="group">
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 group-focus-within:text-blue-600 transition-colors">Nama Lengkap</label>
                            <input type="text" name="nama" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3.5 focus:ring-4 focus:ring-blue-100 focus:bg-white focus:border-blue-500 transition-all outline-none" value="<?= $edit_data['nama_lengkap'] ?? '' ?>" placeholder="e.g. Budi Santoso">
                        </div>

                        <?php if ($current_tab == 'konselor'): ?>
                            <div class="group">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 group-focus-within:text-blue-600 transition-colors">NIP</label>
                                <input type="text" name="nip" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3.5 focus:ring-4 focus:ring-blue-100 focus:bg-white focus:border-blue-500 transition-all outline-none" value="<?= $edit_data['nip'] ?? '' ?>" placeholder="Nomor Induk Pegawai">
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="group">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 group-focus-within:text-blue-600 transition-colors">NIS</label>
                                    <input type="text" name="nis" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3.5 focus:ring-4 focus:ring-blue-100 focus:bg-white focus:border-blue-500 transition-all outline-none" value="<?= $edit_data['nis'] ?? '' ?>" placeholder="NIS">
                                </div>
                                <div class="group">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 group-focus-within:text-blue-600 transition-colors">Gender</label>
                                    <select name="jenis_kelamin" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3.5 focus:ring-4 focus:ring-blue-100 focus:bg-white focus:border-blue-500 transition-all outline-none appearance-none">
                                        <option value="L" <?= (isset($edit_data['jenis_kelamin']) && $edit_data['jenis_kelamin'] == 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="P" <?= (isset($edit_data['jenis_kelamin']) && $edit_data['jenis_kelamin'] == 'P') ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="group">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 group-focus-within:text-blue-600 transition-colors">Kelas</label>
                                    <select name="tingkat_kelas" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3.5 focus:ring-4 focus:ring-blue-100 focus:bg-white focus:border-blue-500 transition-all outline-none appearance-none">
                                        <option value="10" <?= (isset($edit_data['tingkat_kelas']) && $edit_data['tingkat_kelas'] == '10') ? 'selected' : '' ?>>10</option>
                                        <option value="11" <?= (isset($edit_data['tingkat_kelas']) && $edit_data['tingkat_kelas'] == '11') ? 'selected' : '' ?>>11</option>
                                        <option value="12" <?= (isset($edit_data['tingkat_kelas']) && $edit_data['tingkat_kelas'] == '12') ? 'selected' : '' ?>>12</option>
                                    </select>
                                </div>
                                <div class="group">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 group-focus-within:text-blue-600 transition-colors">Jurusan</label>
                                    <select name="jurusan" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3.5 focus:ring-4 focus:ring-blue-100 focus:bg-white focus:border-blue-500 transition-all outline-none appearance-none">
                                        <option value="RPL" <?= (isset($edit_data['jurusan']) && $edit_data['jurusan'] == 'RPL') ? 'selected' : '' ?>>RPL</option>
                                        <option value="TKJ" <?= (isset($edit_data['jurusan']) && $edit_data['jurusan'] == 'TKJ') ? 'selected' : '' ?>>TKJ</option>
                                        <option value="DKV" <?= (isset($edit_data['jurusan']) && $edit_data['jurusan'] == 'DKV') ? 'selected' : '' ?>>DKV</option>
                                        <option value="TKL" <?= (isset($edit_data['jurusan']) && $edit_data['jurusan'] == 'TKL') ? 'selected' : '' ?>>TKL</option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="group">
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 group-focus-within:text-blue-600 transition-colors">Email Login</label>
                            <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3.5 focus:ring-4 focus:ring-blue-100 focus:bg-white focus:border-blue-500 transition-all outline-none" value="<?= $edit_data['email'] ?? '' ?>" placeholder="email@sekolah.sch.id">
                        </div>

                        <div class="group">
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 group-focus-within:text-blue-600 transition-colors">Kata Sandi <?= $edit_data ? '(Opsional)' : '' ?></label>
                            <input type="password" name="password" <?= $edit_data ? '' : 'required' ?> class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3.5 focus:ring-4 focus:ring-blue-100 focus:bg-white focus:border-blue-500 transition-all outline-none" placeholder="<?= $edit_data ? '••••••••' : 'Min. 6 karakter' ?>">
                        </div>

                        <div class="flex flex-col gap-4 pt-4">
                            <button type="submit" name="<?= ($current_tab == 'konselor') ? ($edit_data ? 'edit_konselor' : 'add_konselor') : ($edit_data ? 'edit_siswa' : 'add_siswa') ?>" class="w-full bg-blue-600 text-white font-black py-4 rounded-2xl hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-200 transition-all duration-300 uppercase tracking-widest text-xs">
                                <?= $edit_data ? 'Simpan Perubahan' : 'Buat Akun Baru' ?>
                            </button>
                            <?php if ($edit_data): ?>
                                <a href="dashboard_admin.php?tab=<?= $current_tab ?>" class="w-full bg-slate-100 text-slate-600 font-black py-4 rounded-2xl hover:bg-slate-200 transition-all text-center uppercase tracking-widest text-xs">Batalkan</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List Table -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                    <div class="p-8 border-b border-slate-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h2 class="font-extrabold text-xl text-slate-900 leading-tight">Database <?= $current_tab == 'konselor' ? 'Konselor' : 'Siswa' ?></h2>
                            <p class="text-xs text-slate-400 mt-1 font-bold uppercase tracking-wider">Menampilkan <?= $res_list->num_rows ?> entri data</p>
                        </div>
                        <div class="relative w-full md:w-64 group">
                            <input type="text" placeholder="Cari data..." class="w-full bg-slate-50 border border-slate-100 rounded-xl px-10 py-2.5 text-sm focus:ring-4 focus:ring-blue-50 focus:bg-white focus:border-blue-400 transition-all outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-8 py-5 text-xs font-black text-slate-400 uppercase tracking-widest">Informasi Dasar</th>
                                    <th class="px-8 py-5 text-xs font-black text-slate-400 uppercase tracking-widest"><?= $current_tab == 'konselor' ? 'Identitas' : 'Pendidikan' ?></th>
                                    <th class="px-8 py-5 text-xs font-black text-slate-400 uppercase tracking-widest">Akses</th>
                                    <th class="px-8 py-5 text-right text-xs font-black text-slate-400 uppercase tracking-widest">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if($res_list->num_rows > 0): ?>
                                    <?php while($row = $res_list->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/80 transition-all duration-300 group">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-4">
                                                <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center text-blue-600 font-extrabold text-lg shadow-sm border border-blue-100">
                                                    <?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="font-extrabold text-slate-900 mb-0.5"><?= $row['nama_lengkap'] ?></div>
                                                    <div class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">Terdaftar Baru</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <?php if ($current_tab == 'konselor'): ?>
                                                <div class="font-bold text-slate-700"><?= $row['nip'] ?></div>
                                                <div class="text-xs text-slate-500 mt-1"><?= $row['spesialisasi'] ?></div>
                                            <?php else: ?>
                                                <div class="font-bold text-slate-700">Kelas <?= $row['tingkat_kelas'] ?> • <?= $row['jurusan'] ?></div>
                                                <div class="text-xs text-slate-500 mt-1">NIS: <?= $row['nis'] ?> • <?= $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-8 py-6">
                                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-600 text-[11px] font-black uppercase tracking-wider">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                <?= $row['email'] ?>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <a href="?tab=<?= $current_tab ?>&edit_id=<?= $row['id_pengguna'] ?>" class="p-2.5 bg-white border border-slate-100 shadow-sm text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all" title="Ubah Data">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </a>
                                                <a href="?tab=<?= $current_tab ?>&delete_id=<?= $row['id_pengguna'] ?>" onclick="return confirm('Hapus akun ini? Semua data terkait juga akan terhapus secara permanen.')" class="p-2.5 bg-white border border-slate-100 shadow-sm text-red-500 hover:bg-red-600 hover:text-white rounded-xl transition-all" title="Hapus Akun">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-8 py-32 text-center">
                                            <div class="flex flex-col items-center max-w-sm mx-auto">
                                                <div class="h-20 w-20 bg-slate-50 flex items-center justify-center rounded-3xl mb-6">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                                    </svg>
                                                </div>
                                                <h4 class="font-extrabold text-slate-900 mb-2">Database Kosong</h4>
                                                <p class="text-sm text-slate-500">Belum ada data <?= $current_tab == 'konselor' ? 'konselor' : 'siswa' ?> yang terdaftar di sistem. Gunakan formulir di samping untuk menambah data perdana.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

</body>
</html>
