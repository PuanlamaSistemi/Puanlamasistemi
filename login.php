<?php
require_once 'includes/db.php';

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = null;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Basit giriş kontrolü (Vize sunumu için şifrelenmemiş (plaintext) veya MD5 vs. yerine direkt DB ile eşleşme)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();
    
    if($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['project_id'] = $user['project_id'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "E-posta veya şifre hatalı!";
    }
}

require_once 'includes/header.php';
?>

<div class="flex flex-col items-center justify-center min-h-[75vh] relative z-10">
    
    <!-- Floating Background Decorators -->
    <div class="absolute top-20 right-10 w-48 h-48 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-40 float-animation -z-10"></div>
    <div class="absolute bottom-20 left-10 w-56 h-56 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-40 float-animation-delayed -z-10"></div>

    <div class="w-full max-w-md glass-card rounded-3xl p-8 relative">
        <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-pink-500 rounded-3xl blur opacity-10 pointer-events-none"></div>
        
        <div class="text-center mb-8 relative z-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-tr from-indigo-100 dark:from-indigo-900/40 to-purple-100 dark:to-purple-900/40 rounded-2xl mb-4 text-indigo-600 dark:text-indigo-400 shadow-sm float-animation-delayed">
                <i class="fa-solid fa-user-lock text-2xl"></i>
            </div>
            <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-800 dark:from-indigo-300 to-purple-800 dark:to-purple-300 tracking-tight">Sisteme Giriş</h2>
            <p class="text-indigo-400 dark:text-indigo-300 font-medium text-sm mt-2">Jüri ve Öğrenci Üyeleri içindir.</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50/80 dark:bg-red-900/30 dark:border-red-700 backdrop-blur-sm text-red-600 dark:text-red-300 p-4 rounded-xl mb-6 text-center border border-red-200/50 shadow-sm text-sm font-medium">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-5 relative z-10">
            <div class="group">
                <label class="block text-indigo-900 dark:text-indigo-300 font-bold mb-2 text-sm ml-1">E-posta Adresi</label>
                <div class="relative">
                    <i class="fa-solid fa-envelope absolute left-4 top-3.5 text-indigo-300 dark:text-indigo-400 group-focus-within:text-indigo-600 dark:group-focus-within:text-indigo-300 transition-colors"></i>
                    <input type="email" name="email" required placeholder="ornek@universite.edu.tr" 
                           class="glass-input w-full pl-11 pr-4 py-3 rounded-xl focus:outline-none text-gray-800 dark:text-gray-100 placeholder-indigo-200 dark:placeholder-indigo-400">
                </div>
            </div>
            
            <div class="group">
                <label class="block text-indigo-900 dark:text-indigo-300 font-bold mb-2 text-sm ml-1">Şifre</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-4 top-3.5 text-indigo-300 dark:text-indigo-400 group-focus-within:text-indigo-600 dark:group-focus-within:text-indigo-300 transition-colors"></i>
                    <input type="password" name="password" required placeholder="••••••••" 
                           class="glass-input w-full pl-11 pr-4 py-3 rounded-xl focus:outline-none text-gray-800 dark:text-gray-100 placeholder-indigo-200 dark:placeholder-indigo-400">
                </div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-500 dark:to-purple-500 dark:hover:from-indigo-600 dark:hover:to-purple-600 text-white font-bold py-3.5 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg shadow-indigo-200 dark:shadow-none hover:shadow-xl hover:-translate-y-0.5 mt-2">
                Giriş Yap <i class="fa-solid fa-right-to-bracket ml-1"></i>
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-indigo-900 dark:text-indigo-200 font-medium text-sm">Hesabın yok mu? <a href="register.php" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors">Kayıt Ol</a></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
