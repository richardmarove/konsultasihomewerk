<?php
session_start();
// Redirect already logged-in students to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['peran'] == 'siswa') {
    header("Location: dashboard_siswa.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - BK Skaju</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Lexend', 'sans-serif'],
                    },
                    colors: {
                        primary: '#6C5CE7',
                        secondary: '#a55eea',
                        accent: '#F9F7FF',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans antialiased bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-5xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col md:flex-row h-[650px] md:h-[700px]">
        
        <!-- Left Side: Branding & Illustration -->
        <div class="hidden md:flex w-1/2 bg-gradient-to-br from-primary to-secondary relative flex-col justify-between p-12 text-white overflow-hidden">
            <!-- Background Decorations -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full -ml-16 -mb-16 blur-3xl"></div>
            
            <div class="relative z-10">
                <a href="index.php" class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-md py-2 px-4 rounded-xl hover:bg-white/30 transition w-max">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5m7 7-7-7 7-7"/>
                    </svg>
                    <span class="font-bold text-sm">Kembali ke Beranda</span>
                </a>
            </div>

            <div class="relative z-10 flex flex-col items-center text-center mt-8 p-4">
                <img src="assets/img/login_hero.png" alt="Welcome Back Illustration" class="w-full max-w-sm drop-shadow-2xl animate-float">
            </div>

            <div class="relative z-10 text-center">
                <h2 class="text-3xl font-bold mb-2">Selamat Datang Kembali!</h2>
                <p class="text-indigo-100">Siap untuk melanjutkan perjalananmu menemukan potensi diri?</p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-center relative">
            
            <div class="md:hidden absolute top-6 left-6">
                 <a href="index.php" class="inline-flex items-center gap-2 text-slate-500 hover:text-primary transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5m7 7-7-7 7-7"/>
                    </svg>
                    <span class="font-semibold text-sm">Kembali</span>
                </a>
            </div>

            <div class="mb-10 text-center md:text-left">
                <span class="font-bold text-3xl tracking-tight text-slate-900 block mb-2">BK<span class="text-primary">Skaju</span></span>
                <h1 class="text-2xl font-bold text-slate-800">Masuk ke Akunmu</h1>
                <p class="text-slate-500 mt-2">Silakan masukkan detail akunmu untuk melanjutkan.</p>
            </div>

            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 flex items-center gap-3 text-sm animate-pulse">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    Email atau Password yang kamu masukkan salah.
                </div>
            <?php endif; ?>

            <form action="auth_process.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Email Sekolah</label>
                    <input type="email" name="email" required placeholder="rickymarove@gmail.com"
                           class="w-full px-5 py-4 bg-slate-100 border border-slate-200 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition font-medium text-slate-700 placeholder:text-slate-400">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="passwordInput" required placeholder="••••••••"
                               class="w-full px-5 py-4 bg-slate-100 border border-slate-200 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition font-medium text-slate-700 placeholder:text-slate-400 pr-12">
                        <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary transition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" id="eyeIcon">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <script>
                    function togglePassword() {
                        const passwordInput = document.getElementById('passwordInput');
                        const eyeIcon = document.getElementById('eyeIcon');
                        
                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            // Icon for "Hide" (Slash)
                            eyeIcon.innerHTML = '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/>';
                        } else {
                            passwordInput.type = 'password';
                            // Icon for "Show" (Normal Eye)
                            eyeIcon.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
                        }
                    }
                </script>

                <button type="submit" 
                        class="w-full bg-primary hover:bg-secondary text-white font-bold py-4 rounded-xl transition shadow-lg shadow-primary/30 transform active:scale-[0.98]">
                    Masuk Sekarang
                </button>
            </form>

            <div class="mt-8 text-center text-sm text-slate-500 font-medium">
                Belum punya akun? 
                <a href="register.php" class="text-primary hover:text-secondary font-bold hover:underline transition">Daftar Akun Baru</a>
            </div>
            
        </div>
    </div>

</body>
</html>