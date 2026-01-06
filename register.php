<?php
session_start();
include 'config/database.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama       = $_POST['nama_lengkap'];
    $nis        = $_POST['nis'];
    $email      = $_POST['email'];
    $password   = $_POST['password'];
    $kelas      = $_POST['tingkat_kelas'];
    $jurusan    = $_POST['jurusan'];
    $gender     = $_POST['jenis_kelamin'];

    $check = $conn->query("SELECT id FROM user WHERE email='$email' UNION SELECT id FROM siswa WHERE nis='$nis'");
    if ($check->num_rows > 0) {
        $error = "Email atau NIS sudah terdaftar!";
    } else {
        $conn->begin_transaction();

        try {
            // HASH PASSWORD
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert ke tabel 'user'
            // Default peran = 'siswa'
            $stmt_user = $conn->prepare("INSERT INTO user (email, kata_sandi, peran) VALUES (?, ?, 'siswa')");
            $stmt_user->bind_param("ss", $email, $hashed_password);
            $stmt_user->execute();
            
            $new_user_id = $conn->insert_id;

            $stmt_siswa = $conn->prepare("INSERT INTO siswa (id_pengguna, nis, nama_lengkap, tingkat_kelas, jurusan, jenis_kelamin) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_siswa->bind_param("ississ", $new_user_id, $nis, $nama, $kelas, $jurusan, $gender);
            $stmt_siswa->execute();

            $conn->commit();
            $success = "Pendaftaran berhasil! Silakan login.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <style>
        .lexend-font {
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
            }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center py-10 lexend-font">

    <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row">
        
        <div class="md:w-1/3 bg-blue-600 p-8 text-white flex flex-col justify-center items-center text-center">
            <h2 class="text-3xl font-bold mb-4">Pendaftaran</h2>
            <p class="opacity-90 mb-6">Membutuhkan akun untuk menggunakan aplikasi ini.</p>
            <div class="text-6xl">🚀</div>
            <a href="login.php" class="mt-8 text-sm underline text-blue-200 hover:text-white">Kembali ke Login</a>
        </div>

        <div class="md:w-2/3 p-8">
            <h2 class="text-2xl font-bold text-slate-800 mb-6">Form Pendaftaran Siswa</h2>

            <?php if($error): ?>
                <div class="bg-red-100 text-red-600 p-3 rounded mb-4 text-sm"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-100 text-green-600 p-3 rounded mb-4 text-sm">
                    <?= $success ?> <a href="login.php" class="font-bold underline">Login disini</a>.
                </div>
            <?php endif; ?>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-blue-600 uppercase tracking-wide">Data Sekolah</h3>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">NIS</label>
                        <input type="text" name="nis" required class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" required class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Kelas</label>
                            <select name="tingkat_kelas" class="w-full border rounded px-3 py-2">
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Jurusan</label>
                            <select name="jurusan" class="w-full border rounded px-3 py-2">
                                <option value="RPL">RPL</option>
                                <option value="TKJ">TKJ</option>
                                <option value="DKV">DKV</option>
                                <option value="TKL">TKL</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Jenis Kelamin</label>
                        <div class="flex gap-4 mt-1">
                            <label class="flex items-center text-sm"><input type="radio" name="jenis_kelamin" value="L" required class="mr-1"> Laki-laki</label>
                            <label class="flex items-center text-sm"><input type="radio" name="jenis_kelamin" value="P" class="mr-1"> Perempuan</label>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-blue-600 uppercase tracking-wide">Data Akun</h3>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Email</label>
                        <input type="email" name="email" required class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Password</label>
                        <input type="password" name="password" required class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition shadow-lg">
                            Daftar Sekarang
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

</body>
</html>