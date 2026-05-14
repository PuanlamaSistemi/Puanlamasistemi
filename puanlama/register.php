<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Lütfen tüm alanları doldurun.";
    } else {
        // Kullanıcı var mı kontrol et
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->rowCount() > 0) {
            $error = "Bu e-posta adresi zaten kullanılıyor.";
        } else {
            // Şifreyi şimdilik düz metin olarak kaydedelim (basit sistem)
            // İleride password_hash() kullanılabilir
            $insert = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'student')");
            if ($insert->execute([$email, $password])) {
                $success = "Kayıt başarılı! Giriş sayfasına yönlendiriliyorsunuz...";
                echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
            } else {
                $error = "Kayıt sırasında bir hata oluştu.";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="flex items-center justify-center min-h-[70vh] relative z-10">
    <!-- Floating Background Decorators -->
    <div class="absolute top-10 left-20 w-40 h-40 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-40 float-animation -z-10"></div>
    <div class="absolute bottom-10 right-20 w-48 h-48 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-40 float-animation-delayed -z-10"></div>

    <div class="glass-card p-10 rounded-3xl w-full max-w-md relative overflow-hidden group">
        <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-3xl blur opacity-20 group-hover:opacity-40 transition duration-1000"></div>
        
        <div class="relative z-10">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-100/50 text-indigo-600 mb-4 shadow-inner border border-indigo-200">
                    <i class="fa-solid fa-user-plus text-2xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-800 to-purple-700 tracking-tight">Kayıt Ol</h2>
                <p class="text-indigo-900/60 font-medium mt-2">Projelerini yüklemek için hesap oluştur.</p>
            </div>

            <?php if($error): ?>
                <div class="bg-red-100/80 border border-red-300 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm backdrop-blur-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span class="block sm:inline font-medium"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-100/80 border border-green-300 text-green-700 px-4 py-3 rounded-xl mb-6 shadow-sm backdrop-blur-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-check"></i>
                    <span class="block sm:inline font-medium"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-indigo-900 font-bold mb-2 text-sm uppercase tracking-wide">E-Posta Adresi</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-4 text-indigo-400"></i>
                        <input type="email" name="email" required placeholder="ornek@universite.edu.tr"
                               class="glass-input w-full pl-12 pr-4 py-3.5 rounded-xl text-gray-800 placeholder-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-400/50 transition-all">
                    </div>
                </div>
                
                <div>
                    <label class="block text-indigo-900 font-bold mb-2 text-sm uppercase tracking-wide">Şifre</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-4 text-indigo-400"></i>
                        <input type="password" name="password" required placeholder="••••••••"
                               class="glass-input w-full pl-12 pr-4 py-3.5 rounded-xl text-gray-800 placeholder-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-400/50 transition-all">
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-4 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-300 hover:shadow-xl hover:-translate-y-0.5 duration-300 flex justify-center items-center gap-2">
                    Kayıt Ol <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
                
                <div class="text-center mt-6 border-t border-indigo-100/50 pt-4">
                    <p class="text-indigo-900/70 font-medium text-sm">Zaten hesabın var mı? <a href="login.php" class="text-indigo-600 font-bold hover:underline hover:text-indigo-800 transition-colors">Giriş Yap</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
