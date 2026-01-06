<?php
session_start();
// Redirect already logged-in students to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['peran'] == 'siswa') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Counseling | Ruang Aman</title>
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

<body class="min-h-screen flex items-center justify-center lexend-font bg-[#f0f9ff]">

    <div class="container mx-auto px-4 h-screen flex flex-col md:flex-row items-center">
        
        <div class="w-full md:w-1/2 p-8 text-center md:text-left">
            <h1 class="text-4xl md:text-5xl font-bold text-slate-800 mb-4">
                Aplikasi Konseling <span class="text-yellow-400">Skaju</span>
            </h1>
            <p class="text-lg text-slate-600 mb-8">
                Kami siap mendengarkan. Jadwalkan konsultasi, kenali potensimu, dan temukan solusi terbaik. 
            </p>
            
            <div class="flex flex-wrap gap-4 justify-center md:justify-start text-sm text-slate-500">
                <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-full shadow-sm">
                    🔒 Rahasia Terjamin
                </div>
                <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-full shadow-sm">
                    🧠 Kenali Diri
                </div>
            </div>
        </div>

        <div class="w-full md:w-1/2 p-4 max-w-md mx-auto">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-2xl font-bold text-center text-slate-800 mb-6">Masuk Aplikasi</h2>
                
                <?php if(isset($_GET['error'])): ?>
                    <div class="bg-red-100 text-red-600 p-3 rounded mb-4 text-sm">
                        Email atau Password salah!
                    </div>
                <?php endif; ?>

                <form action="auth_process.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" required 
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input type="password" name="password" required 
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none">
                    </div>
                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">
                        Masuk Sekarang
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <div class="border-t pt-4 mt-4">
                        <p class="text-sm text-slate-600">Belum punya akun?</p>
                        <a href="register.php" class="inline-block mt-2 text-blue-600 font-bold hover:underline">
                            Daftar sebagai Siswa
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>


                <!-- is this the cleanest front-end code you've ever seen? /s  --> 


</body>
</html>