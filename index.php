<?php
session_start();
// Redirect logged-in students to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['peran'] == 'siswa') {
    header("Location: dashboard_siswa.php");
    exit;
}
// Redirect others if needed
if (isset($_SESSION['user_id'])) {
     if ($_SESSION['peran'] == 'konselor') header("Location: dashboard_guru.php");
     if ($_SESSION['peran'] == 'admin') header("Location: dashboard_admin.php");
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Konseling Sekolah - Skaju</title>
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
                    }
                }
            }
        }
    </script>
    <style>
        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }
        .hero-blob {
            position: absolute;
            width: 600px;
            height: 600px;
            background: linear-gradient(180deg, rgba(108, 92, 231, 0.2) 0%, rgba(165, 94, 234, 0.1) 100%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            animation: float 10s infinite ease-in-out;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased overflow-x-hidden">

    <!-- Navbar -->
    <nav class="glass-nav fixed w-full z-50 top-0 transition-all duration-300">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="bg-primary/10 p-2 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <span class="font-bold text-xl tracking-tight text-slate-900">BK<span class="text-yellow-500">Skaju</span></span>
            </div>
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#fitur" class="hover:text-primary transition">Fitur</a>
                <a href="#tentang" class="hover:text-primary transition">Tentang</a>
                <a href="#kontak" class="hover:text-primary transition">Kontak</a>
            </div>
            <div class="flex gap-4">
                <a href="login.php" class="px-5 py-2.5 text-sm font-bold text-slate-700 hover:text-primary transition">Masuk</a>
                <a href="register.php" class="px-5 py-2.5 text-sm font-bold bg-primary text-white rounded-xl hover:bg-secondary transition shadow-lg shadow-primary/30 transform hover:-translate-y-0.5">Daftar</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div class="hero-blob -top-20 -left-20"></div>
        <div class="hero-blob bottom-0 right-0 bg-blue-100/50"></div>

        <div class="container mx-auto px-6 text-center relative z-10">
            <span class="inline-block py-1 px-3 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-6 animate-fade-in-up">Platform Konseling Modern</span>
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-slate-900 leading-tight mb-8">
                Cerita Kamu, <br/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-yellow-300">Prioritas Kami.</span>
            </h1>
            <p class="text-lg md:text-xl text-slate-500 max-w-2xl mx-auto mb-10 leading-relaxed">
                Ruang aman untuk bercerita, menemukan potensi diri, dan mendapatkan bimbingan profesional dari guru BK sekolahmu.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="login.php" class="px-8 py-4 bg-primary text-white rounded-2xl font-bold text-lg hover:bg-secondary transition shadow-xl shadow-primary/30 w-full sm:w-auto">
                    Mulai Konsultasi
                </a>
                <a href="#fitur" class="px-8 py-4 bg-white text-slate-700 border border-slate-200 rounded-2xl font-bold text-lg hover:bg-slate-50 transition w-full sm:w-auto">
                    Pelajari Lebih Lanjut
                </a>
            </div>

            <!-- Stats/Trust -->
            <div class="mt-16 pt-8 border-t border-slate-200/60 max-w-4xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <h4 class="text-3xl font-bold text-slate-900">500+</h4>
                    <p class="text-sm text-slate-500">Siswa Terdaftar</p>
                </div>
                <div>
                    <h4 class="text-3xl font-bold text-slate-900">24/7</h4>
                    <p class="text-sm text-slate-500">Akses Online</p>
                </div>
                <div>
                    <h4 class="text-3xl font-bold text-slate-900">100%</h4>
                    <p class="text-sm text-slate-500">Rahasia Terjamin</p>
                </div>
                 <div>
                    <h4 class="text-3xl font-bold text-slate-900">VAK</h4>
                    <p class="text-sm text-slate-500">Tes Gaya Belajar</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="fitur" class="py-20 bg-white relative">
        <div class="container mx-auto px-6">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Fitur Unggulan</h2>
                <p class="text-slate-500">Kami menyediakan berbagai layanan untuk mendukung kesehatan mental dan pengembangan diri siswa.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-slate-50 p-8 rounded-3xl border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition duration-300 group">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-sm mb-6 group-hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Konseling Online</h3>
                    <p class="text-slate-500 leading-relaxed">Jadwalkan sesi konsultasi dengan guru BK kapan saja dan di mana saja tanpa rasa canggung.</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-slate-50 p-8 rounded-3xl border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition duration-300 group">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-sm mb-6 group-hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Tes Gaya Belajar</h3>
                    <p class="text-slate-500 leading-relaxed">Ketahui gaya belajarmu (Visual, Auditori, Kinestetik) untuk memaksimalkan potensi akademis.</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-slate-50 p-8 rounded-3xl border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition duration-300 group">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-sm mb-6 group-hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Rahasia Terjamin</h3>
                    <p class="text-slate-500 leading-relaxed">Privasimu adalah prioritas kami. Semua sesi konseling dilakukan dalam lingkungan yang aman.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20">
        <div class="container mx-auto px-6">
            <div class="bg-primary rounded-[2.5rem] p-10 md:p-16 text-center text-white relative overflow-hidden">
                <!-- Background Pattern -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full -ml-16 -mb-16 blur-3xl"></div>

                <div class="relative z-10 max-w-2xl mx-auto">
                    <h2 class="text-3xl md:text-5xl font-bold mb-6">Siap Untuk Memulai?</h2>
                    <p class="text-purple-100 text-lg mb-10">Jangan biarkan masalahmu berlarut-larut. Mari cari solusinya bersama kami hari ini.</p>
                    <a href="register.php" class="inline-block bg-white text-primary px-10 py-4 rounded-2xl font-bold text-lg hover:bg-purple-50 transition shadow-lg transform hover:-translate-y-1">
                        Daftar Akun Gratis
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-50 pt-20 pb-10 border-t border-slate-200">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6 mb-8">
                <div class="flex items-center gap-2">
                    <div class="bg-white border border-slate-200 p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <span class="font-bold text-xl text-slate-800">BK<span class="text-yellow-500">Skaju</span></span>
                </div>
                <p class="text-slate-500 text-sm">© 2026 SMKN 7 Batam.</p>
            </div>
        </div>
    </footer>

</body>
</html>