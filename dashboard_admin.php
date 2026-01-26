<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['peran'] != 'admin') {
    header("Location: index.php");
    exit;
}

$msg = "";
$edit_data = null;

// Ambil data untuk Edit
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $res_edit = $conn->query("SELECT k.*, u.email FROM konselor k JOIN user u ON k.id_pengguna = u.id WHERE u.id = '$edit_id'");
    if ($res_edit->num_rows > 0) {
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
    
    // Validasi duplikasi NIP/Email (kecuali data yang sedang diedit)
    $query_check = "SELECT id FROM user WHERE email='$email' " . ($is_edit ? "AND id != '$user_id'" : "");
    $query_check .= " UNION SELECT id FROM konselor WHERE nip='$nip' " . ($is_edit ? "AND id_pengguna != '$user_id'" : "");
    
    $check = $conn->query($query_check);
    
    if ($check->num_rows > 0) {
        $msg = "<div class='bg-red-100 text-red-600 p-3 rounded mb-4'>Email atau NIP sudah terdaftar!</div>";
    } else {
        $conn->begin_transaction();
        try {
            if ($is_edit) {
                // UPDATE
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
                header("Location: dashboard_admin.php?status=updated");
                exit;
            } else {
                // INSERT
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
                $msg = "<div class='bg-green-100 text-green-600 p-3 rounded mb-4'>Konselor berhasil ditambahkan!</div>";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "<div class='bg-red-100 text-red-600 p-3 rounded mb-4'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Pesan Sukses Update
if (isset($_GET['status']) && $_GET['status'] == 'updated') {
    $msg = "<div class='bg-green-100 text-green-600 p-3 rounded mb-4'>Data konselor berhasil diupdate!</div>";
}

// Proses Hapus Konselor
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id']; // ID User
    $conn->query("DELETE FROM konselor WHERE id_pengguna = '$del_id'");
    $conn->query("DELETE FROM user WHERE id = '$del_id'");
    header("Location: dashboard_admin.php");
    exit;
}

// Ambil Data Konselor
$sql_list = "SELECT k.*, u.email FROM konselor k JOIN user u ON k.id_pengguna = u.id";
$res_list = $conn->query($sql_list);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <style>.lexend-font { font-family: "Lexend", sans-serif; }</style>
</head>
<body class="bg-slate-50 lexend-font min-h-screen">

    <nav class="bg-slate-800 text-white px-6 py-4 flex justify-between items-center">
        <h1 class="font-bold text-xl">Admin Panel</h1>
        <a href="logout.php" class="text-red-400 hover:text-red-300 text-sm">Keluar</a>
    </nav>

    <div class="container mx-auto p-6">
        
        <div class="flex flex-col md:flex-row gap-8">
            
            <div class="w-full md:w-1/3">
                <div class="bg-white p-6 rounded-xl shadow-sm border">
                    <h2 class="font-bold text-lg text-slate-700 mb-4"><?= $edit_data ? 'Edit Konselor' : 'Tambah Konselor' ?></h2>
                    <?= $msg ?>
                    <form method="POST" class="space-y-4">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="user_id" value="<?= $edit_data['id_pengguna'] ?>">
                        <?php endif; ?>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase">Nama Lengkap</label>
                            <input type="text" name="nama" required class="w-full border rounded px-3 py-2" value="<?= $edit_data['nama_lengkap'] ?? '' ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase">NIP</label>
                            <input type="text" name="nip" required class="w-full border rounded px-3 py-2" value="<?= $edit_data['nip'] ?? '' ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase">Email Login</label>
                            <input type="email" name="email" required class="w-full border rounded px-3 py-2" value="<?= $edit_data['email'] ?? '' ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase">Password <?= $edit_data ? '(Kosongkan jika tidak ubah)' : '' ?></label>
                            <input type="text" name="password" <?= $edit_data ? '' : 'required' ?> class="w-full border rounded px-3 py-2" placeholder="<?= $edit_data ? '••••••••' : 'Contoh: guru123' ?>">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" name="<?= $edit_data ? 'edit_konselor' : 'add_konselor' ?>" class="flex-1 bg-blue-600 text-white font-bold py-2 rounded hover:bg-blue-700 transition">
                                <?= $edit_data ? 'Simpan Perubahan' : '+ Tambah Akun' ?>
                            </button>
                            <?php if ($edit_data): ?>
                                <a href="dashboard_admin.php" class="bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded hover:bg-slate-300 transition text-center">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="w-full md:w-2/3">
                <div class="bg-white p-6 rounded-xl shadow-sm border">
                    <h2 class="font-bold text-lg text-slate-700 mb-4">Daftar Konselor</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-100 text-slate-700 uppercase font-bold text-xs">
                                <tr>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="px-4 py-3">NIP</th>
                                    <th class="px-4 py-3">Email</th>
                                    <th class="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php if($res_list->num_rows > 0): ?>
                                    <?php while($row = $res_list->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-medium text-slate-800"><?= $row['nama_lengkap'] ?></td>
                                        <td class="px-4 py-3"><?= $row['nip'] ?></td>
                                        <td class="px-4 py-3"><?= $row['email'] ?></td>
                                        <td class="px-4 py-3">
                                            <div class="flex gap-3">
                                                <a href="?edit_id=<?= $row['id_pengguna'] ?>" class="text-blue-500 hover:underline">Edit</a>
                                                <a href="?delete_id=<?= $row['id_pengguna'] ?>" onclick="return confirm('Hapus akun ini?')" class="text-red-500 hover:underline">Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Belum ada data konselor.</td></tr>
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
