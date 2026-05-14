<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Değerlendirme</title>
    
    <!-- Custom Favicon (SVG) -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><defs><linearGradient id='g' x1='0%' y1='0%' x2='100%' y2='100%'><stop offset='0%' stop-color='%234f46e5' /><stop offset='100%' stop-color='%23d946ef' /></linearGradient></defs><rect width='100' height='100' rx='25' fill='url(%23g)' /><path d='M25 25 h15 v15 h-15 z M60 25 h15 v15 h-15 z M25 60 h15 v15 h-15 z M50 60 h10 v10 h-10 z M65 75 h10 v10 h-10 z M50 45 h10 v10 h-10 z' fill='white'/></svg>">

    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (CDN for Rapid Development) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Glassmorphism Styles -->
    <link rel="stylesheet" href="public/css/style.css">
    
    <!-- Dark Mode Anti-Flash Script -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-mesh-light dark:bg-gray-900 text-gray-800 dark:text-gray-200 min-h-screen flex flex-col font-sans transition-colors duration-500">
    
    <nav class="glass-nav sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-wrap justify-between items-center gap-4">
            <a href="index.php" class="text-2xl font-bold flex items-center gap-2 text-indigo-900 dark:text-indigo-300 hover:text-indigo-700 dark:hover:text-indigo-200 transition">
                <i class="fa-solid fa-qrcode text-indigo-600 dark:text-indigo-400"></i> Proje Oylama
            </a>
            <div class="flex flex-wrap gap-4 items-center">
                
                <!-- Dark Mode Toggle Switch -->
                <label for="themeToggle" class="flex items-center cursor-pointer relative">
                    <div class="mr-3 text-amber-500 dark:text-gray-400"><i class="fa-solid fa-sun"></i></div>
                    <div class="relative">
                        <input type="checkbox" id="themeToggle" class="sr-only">
                        <div class="block bg-indigo-200 dark:bg-gray-700 w-10 h-6 rounded-full transition-colors duration-300"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform duration-300"></div>
                    </div>
                    <div class="ml-3 text-gray-400 dark:text-indigo-400"><i class="fa-solid fa-moon"></i></div>
                </label>
                
                <a href="index.php" class="text-gray-600 dark:text-gray-300 font-medium hover:text-indigo-600 dark:hover:text-indigo-400 transition">Ana Sayfa</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="text-gray-600 font-medium hover:text-indigo-600 transition"><i class="fa-solid fa-user mr-1"></i> Panel</a>
                    <a href="admin.php" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-xl font-medium hover:bg-indigo-200 transition shadow-sm"><i class="fa-solid fa-shield mr-1"></i> Yetkili</a>
                    <a href="logout.php" class="bg-rose-50 text-rose-600 border border-rose-200 px-4 py-2 rounded-xl font-medium hover:bg-rose-100 transition shadow-sm">Çıkış</a>
                <?php else: ?>
                    <a href="login.php" class="bg-indigo-600 text-white shadow-lg shadow-indigo-200 px-6 py-2 rounded-xl font-medium hover:bg-indigo-700 hover:-translate-y-0.5 transition-all duration-300">
                        <i class="fa-solid fa-lock mr-1"></i> Giriş Yap
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <script>
        // Theme Toggle Logic
        const themeToggle = document.getElementById('themeToggle');
        
        // Başlangıç durumunu ayarla
        if (document.documentElement.classList.contains('dark')) {
            themeToggle.checked = true;
            document.querySelector('.dot').classList.add('translate-x-4');
        }
        
        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                document.querySelector('.dot').classList.add('translate-x-4');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                document.querySelector('.dot').classList.remove('translate-x-4');
            }
        });
    </script>
    
    <main class="flex-grow w-full max-w-6xl mx-auto px-4 py-10">
