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
    <title>Skaju • Ruang Amanmu</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        display: ['"Outfit"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5', // Deep Indigo
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                        accent: {
                            light: '#ffe4e6',
                            DEFAULT: '#f43f5e', // Coral
                            dark: '#e11d48',
                        },
                        surface: '#ffffff',
                    },
                    animation: {
                        'float': 'float 8s ease-in-out infinite',
                        'float-delayed': 'float 8s ease-in-out 4s infinite',
                        'pulse-slow': 'pulse 6s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0) scale(1)' },
                            '50%': { transform: 'translateY(-20px) scale(1.02)' },
                        }
                    },
                    backgroundImage: {
                        'mesh': 'radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%)',
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Utilities */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .glass-dark {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .mesh-bg {
            background-color: #ffffff;
            background-image: 
                radial-gradient(at 88% 40%, hsla(240,50%,94%,1) 0px, transparent 50%),
                radial-gradient(at 0% 50%, hsla(260,100%,96%,1) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(340,100%,96%,1) 0px, transparent 50%),
                radial-gradient(at 0% 0%, hsla(220,100%,96%,1) 0px, transparent 50%);
        }

        .text-balance {
            text-wrap: balance;
        }

        /* Smooth reveal */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            animation: revealAnim 0.8s cubic-bezier(0.5, 0, 0, 1) forwards;
        }
        
        @keyframes revealAnim {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased mesh-bg selection:bg-brand-500 selection:text-white">

    <!-- Navbar -->
    <nav class="fixed w-full z-50 top-0 pt-4 px-4 sm:px-6">
        <div class="glass rounded-2xl max-w-7xl mx-auto px-6 py-4 flex justify-between items-center shadow-sm shadow-slate-200/50 transition-all duration-300">
            <!-- Logo -->
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 bg-brand-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-500/30 group-hover:scale-105 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <span class="font-display font-bold text-2xl text-slate-900 tracking-tight"><span class="text-blue-500">BK</span>Skaju</span>
            </a>

            <!-- Actions -->
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="login.php" class="hidden sm:inline-block text-sm font-semibold text-slate-600 hover:text-brand-600 px-4 py-2 transition-colors">Masuk</a>
                <a href="register.php" class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-6 py-3 rounded-xl transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0">
                    Mulai Konsultasi
                </a>
            </div>
        </div>
    </nav>

    <main class="pt-32 pb-20 overflow-x-hidden">
        
        <!-- Hero Section -->
        <section class="container mx-auto px-6 mb-24 lg:mb-32">
            <div class="max-w-5xl mx-auto text-center relative">
                
                <!-- Decorative BG Elements -->
                <div class="absolute -top-24 -left-20 w-72 h-72 bg-purple-300/30 rounded-full blur-[80px] animate-float"></div>
                <div class="absolute top-10 -right-20 w-96 h-96 bg-brand-300/20 rounded-full blur-[100px] animate-float-delayed"></div>

                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-slate-200 shadow-sm mb-8 reveal" style="animation-delay: 0.1s">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-300 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-yellow-300"></span>
                    </span>
                    <span class="text-sm font-semibold text-slate-600 tracking-wide uppercase text-[11px]">Platform BK Skaju</span>
                </div>

                <!-- Headline -->
                <h1 class="font-display font-extrabold text-5xl sm:text-7xl lg:text-8xl text-slate-900 tracking-tight leading-[1] mb-8 text-balance reveal" style="animation-delay: 0.2s">
                    Ceritakan masalahmu, <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 via-purple-600 to-accent-DEFAULT">temukan solusi.</span>
                </h1>

                <!-- Subheadline -->
                <p class="text-lg sm:text-xl text-slate-600 max-w-2xl mx-auto mb-12 leading-relaxed text-balance reveal" style="animation-delay: 0.3s">
                    Nikmati layanan konseling profesional yang aman, nyaman, dan rahasia. Kami hadir untuk mendengarkan setiap ceritamu tanpa menghakimi.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 reveal" style="animation-delay: 0.4s">
                    <a href="register.php" class="w-full sm:w-auto px-8 py-4 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-2xl shadow-xl shadow-brand-600/20 transition-all hover:scale-105">
                        Daftar Gratis
                    </a>
                    <a href="#fitur" class="w-full sm:w-auto px-8 py-4 bg-white hover:bg-slate-50 text-slate-700 font-bold border border-slate-200 rounded-2xl shadow-sm transition-all hover:border-slate-300">
                        Pelajari Layanan
                    </a>
                </div>

                <!-- Mockup / Visual -->
                <div class="mt-20 relative reveal" style="animation-delay: 0.6s">
                    <div class="relative z-10 glass p-4 rounded-3xl shadow-2xl border-2 border-white/50 max-w-4xl mx-auto transform rotate-1 hover:rotate-0 transition-transform duration-700">
                        <div class="aspect-[16/9] bg-slate-100 rounded-2xl overflow-hidden relative group">
                            <!-- Placeholder UI for Dashboard Preview -->
                             <div class="absolute inset-0 bg-gradient-to-br from-brand-50 to-white flex items-center justify-center">
                                <div class="text-center">
                                    <div class="w-16 h-16 bg-white rounded-2xl shadow-lg mx-auto mb-4 flex items-center justify-center text-brand-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                    </div>
                                    <p class="font-display font-medium text-slate-400">Ceritakan masalahmu, temukan solusi.</p>
                                </div>
                             </div>
                             <!-- Simulate Chat UI Bubbles -->
                             <div class="absolute bottom-6 left-6 right-6 flex flex-col gap-3 opacity-90">
                                <div class="self-end bg-brand-600 text-white p-4 rounded-2xl rounded-tr-sm shadow-md max-w-xs ml-auto transform translate-y-12 group-hover:translate-y-0 transition-transform duration-500">
                                    <p class="text-sm">Halo Bu, saya merasa cemas akhir-akhir ini...</p>
                                </div>
                                <div class="self-start bg-white text-slate-700 p-4 rounded-2xl rounded-tl-sm shadow-md max-w-xs mr-auto transform translate-y-12 group-hover:translate-y-0 transition-transform duration-500 delay-100">
                                    <p class="text-sm">Tentu, mari kita bicarakan pelan-pelan ya.</p>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Bento Grid Features -->
        <section id="fitur" class="container mx-auto px-6 py-20">
            <div class="max-w-xl mx-auto text-center mb-16">
                <h2 class="font-display font-bold text-3xl md:text-4xl text-slate-900 mb-4">Kenapa aplikasi ini?</h2>
                <p class="text-slate-500">Layanan holistik yang didesain untuk kenyamanan dan privasimu.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                
                <!-- Feature 1: Large -->
                <div class="md:col-span-2 bg-brand-600 rounded-[2.5rem] p-10 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-16 -mt-16 group-hover:scale-110 transition-transform duration-700"></div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row h-full items-start md:items-center gap-8">
                        <div class="flex-1">
                            <div class="w-14 h-14 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-white mb-6">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" /></svg>
                            </div>
                            <h3 class="text-3xl font-bold text-white mb-3">Konsultasi Terjadwal</h3>
                            <p class="text-brand-100 text-lg leading-relaxed">
                                Atur janji temu dengan guru BK melalui sistem yang terorganisir. Semua riwayat bimbingan tercatat untuk membantumu berkembang.
                            </p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm p-6 rounded-3xl border border-white/10 transform rotate-3 group-hover:rotate-0 transition-transform duration-300 w-full md:w-64">
                            <div class="space-y-3">
                                <div class="h-2 bg-white/20 rounded w-1/2"></div>
                                <div class="h-2 bg-white/20 rounded w-3/4"></div>
                                <div class="h-2 bg-white/20 rounded w-full"></div>
                                <div class="h-16 bg-white/5 rounded-xl mt-4"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 2: Tall -->
                <div class="bg-white border border-slate-100 shadow-xl shadow-slate-200/50 rounded-[2.5rem] p-8 group hover:-translate-y-1 transition-transform duration-300">
                    <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-3">100% Rahasia</h3>
                    <p class="text-slate-500 leading-relaxed mb-6">
                        Privasi adalah prioritas kami. Data dan curhatanmu dienkripsi dan hanya bisa diakses oleh konselormu.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white border border-slate-100 shadow-xl shadow-slate-200/50 rounded-[2.5rem] p-8 group hover:-translate-y-1 transition-transform duration-300">
                    <div class="w-14 h-14 bg-sky-100 text-sky-600 rounded-2xl flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-3">Tes VAK</h3>
                    <p class="text-slate-500 leading-relaxed">
                        Ketahui gaya belajarmu (Visual, Auditori, Kinestetik) untuk memaksimalkan potensi akademik.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="md:col-span-2 bg-slate-900 rounded-[2.5rem] p-10 text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-slate-900 to-slate-800"></div>
                    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                        <div class="text-left">
                            <h3 class="text-3xl font-bold text-white mb-2">Siap untuk bercerita?</h3>
                            <p class="text-slate-400">Bergabung dengan 500+ siswa lainnya.</p>
                        </div>
                        <a href="register.php" class="bg-brand-500 hover:bg-brand-400 text-white font-bold px-8 py-4 rounded-xl transition-all shadow-lg shadow-brand-500/25 whitespace-nowrap">
                            Buat Akun Sekarang
                        </a>
                    </div>
                </div>

            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 pt-16 pb-8">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
                <div class="col-span-1 md:col-span-2">
                    <a href="#" class="flex items-center gap-2 mb-6">
                        <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <span class="font-display font-bold text-xl text-slate-900"><span class="text-blue-500">BK</span>Skaju</span>
                    </a>
                    <p class="text-slate-500 max-w-sm">
                        Platform bimbingan konseling digital yang mengutamakan kenyamanan dan keamanan siswa dalam bercerita dan mengembangkan diri.
                    </p>
                </div>
                
                <div>
                    <h4 class="font-bold text-slate-900 mb-6">Layanan</h4>
                    <ul class="space-y-4 text-slate-500">
                        <li><a href="#" class="hover:text-brand-600 transition-colors">Konseling Online</a></li>
                        <li><a href="#" class="hover:text-brand-600 transition-colors">Tes Gaya Belajar</a></li>
                        <li><a href="#" class="hover:text-brand-600 transition-colors">Arsip Artikel</a></li>
                        <li><a href="#" class="hover:text-brand-600 transition-colors">FAQ</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-slate-900 mb-6">Kontak</h4>
                    <ul class="space-y-4 text-slate-500">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            smknegeri7batam@gmail.com
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C7.82 21 2 15.18 2 5z"></path></svg>
                            08117779492
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-slate-400">
                <p>&copy; 2026 Ricky Marove. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-brand-600 transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-brand-600 transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>