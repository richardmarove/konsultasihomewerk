<?php
session_start();
// Check if user is already logged in
if (isset($_SESSION['user_id']) && $_SESSION['peran'] == 'siswa') {
    header("Location: dashboard_siswa.php");
    exit;
}

$error = isset($_GET['error']) ? $_GET['error'] : "";
$success = isset($_GET['success']) ? $_GET['success'] : "";
$page_title = "Daftar Akun Siswa";
include 'includes/header.php';
?>

    <div class="w-full max-w-2xl mx-auto bg-white shadow-xl rounded-xl overflow-hidden">
        
        <div class="bg-blue-600 p-6 text-white text-center">
            <h2 class="text-2xl font-bold">Pendaftaran Siswa Baru</h2>
            <p class="text-blue-100 text-sm mt-1">Lengkapi data diri Anda untuk membuat akun.</p>
        </div>

        <div class="p-8">
            <?php if($error): ?>
                <div class="bg-red-100 text-red-600 p-4 rounded-lg mb-6 text-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6 text-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span><?= htmlspecialchars($success) ?> <a href="login.php" class="font-bold underline hover:text-green-900 ml-1">Login disini</a>.</span>
                </div>
            <?php endif; ?>

            <form action="auth/register_process.php" method="POST" class="space-y-6">
                
                <div>
                    <h3 class="text-sm font-bold text-blue-600 uppercase tracking-wide border-b border-blue-100 pb-2 mb-4">Data Sekolah</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" required class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">NIS</label>
                            <input type="text" name="nis" required class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Jenis Kelamin</label>
                            <div class="flex gap-4 mt-2">
                                <label class="flex items-center text-sm cursor-pointer"><input type="radio" name="jenis_kelamin" value="L" required class="mr-2 text-blue-600 focus:ring-blue-500"> Laki-laki</label>
                                <label class="flex items-center text-sm cursor-pointer"><input type="radio" name="jenis_kelamin" value="P" class="mr-2 text-blue-600 focus:ring-blue-500"> Perempuan</label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Kelas</label>
                            <select name="tingkat_kelas" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition outline-none bg-white">
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Jurusan</label>
                            <select name="jurusan" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition outline-none bg-white">
                                <option value="RPL">RPL</option>
                                <option value="TKJ">TKJ</option>
                                <option value="DKV">DKV</option>
                                <option value="TKL">TKL</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-blue-600 uppercase tracking-wide border-b border-blue-100 pb-2 mb-4">Data Akun</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <input type="email" name="email" required class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                            <input type="password" name="password" required minlength="6" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition outline-none">
                            <p class="text-xs text-slate-500 mt-1">Minimal 6 karakter</p>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-blue-200 transform active:scale-[0.98]">
                        Daftar Pembelajaran
                    </button>
                </div>

                <p class="text-center text-sm text-slate-500 mt-6">
                    Sudah punya akun? <a href="login.php" class="text-blue-600 font-bold hover:underline">Masuk disini</a>
                </p>

            </form>
        </div>
    </div>

</body>
</html>
