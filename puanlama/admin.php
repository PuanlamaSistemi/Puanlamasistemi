<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] !== 'jury') {
    require_once 'includes/header.php';
    echo '
    <div class="min-h-[60vh] flex items-center justify-center relative z-10 px-4">
        <div class="glass-card p-10 rounded-3xl text-center max-w-md w-full shadow-2xl relative overflow-hidden group">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-red-400 rounded-full mix-blend-multiply filter blur-2xl opacity-20 group-hover:scale-150 transition-transform duration-700"></div>
            
            <div class="bg-red-50 text-red-500 w-20 h-20 rounded-full flex items-center justify-center text-4xl mx-auto mb-6 shadow-sm border border-red-100">
                <i class="fa-solid fa-lock"></i>
            </div>
            
            <h2 class="text-2xl font-extrabold text-gray-800 mb-2">Erişim Reddedildi</h2>
            <p class="text-gray-500 font-medium mb-8">Bu paneli görüntüleme yetkiniz yok. Sadece jüri üyeleri giriş yapabilir.</p>
            
            <a href="dashboard.php" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-3 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-300 hover:shadow-xl">
                Öğrenci Paneline Dön <i class="fa-solid fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>';
    require_once 'includes/footer.php';
    exit;
}

// Proje Silme İşlemi
if (isset($_POST['delete_project_id'])) {
    $del_id = $_POST['delete_project_id'];

    // Öğrenci hesabının silinmemesi için önce user tablosundan projeyi kopar
    $detach_stmt = $pdo->prepare("UPDATE users SET project_id = NULL WHERE project_id = ?");
    $detach_stmt->execute([$del_id]);

    $del_stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    if ($del_stmt->execute([$del_id])) {
        $success_msg = "Proje başarıyla silindi.";
    } else {
        $error_msg = "Proje silinirken bir hata oluştu.";
    }
}

// --- İstatistikleri Çek ---
$stat_votes = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$stat_projects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();

// En çok oy alan proje
$most_voted = $pdo->query("
    SELECT p.name, COUNT(v.id) as cnt 
    FROM projects p 
    JOIN votes v ON p.id = v.project_id 
    GROUP BY p.id 
    ORDER BY cnt DESC LIMIT 1
")->fetch();

// En yüksek puanlı proje
$top_rated = $pdo->query("
    SELECT p.name, AVG((v.design_score + v.tech_score + v.presentation_score + v.innovation_score)/4)/2 as avg_score 
    FROM projects p 
    JOIN votes v ON p.id = v.project_id 
    GROUP BY p.id 
    ORDER BY avg_score DESC LIMIT 1
")->fetch();

require_once 'includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div
        class="flex flex-col md:flex-row justify-between items-center bg-rose-50 border border-rose-200 text-rose-800 p-6 rounded-2xl mb-8">
        <div>
            <h1 class="text-2xl font-bold mb-1 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i> Jüri / Yönetici Paneli
            </h1>
            <p class="opacity-80">
                Sistem genelindeki verilere müdahale yetkilisiniz.
            </p>
        </div>
    </div>

    <!-- İstatistikler Paneli -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-indigo-50">
            <div class="w-14 h-14 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl shadow-sm"><i class="fa-solid fa-folder-open"></i></div>
            <div class="flex-1 overflow-hidden">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Toplam Proje</p>
                <h3 class="text-2xl font-black text-gray-800"><?php echo $stat_projects; ?></h3>
            </div>
        </div>
        <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-pink-50">
            <div class="w-14 h-14 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center text-2xl shadow-sm"><i class="fa-solid fa-check-to-slot"></i></div>
            <div class="flex-1 overflow-hidden">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Kullanılan Oy</p>
                <h3 class="text-2xl font-black text-gray-800"><?php echo $stat_votes; ?></h3>
            </div>
        </div>
        <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-amber-50">
            <div class="w-14 h-14 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-2xl shadow-sm"><i class="fa-solid fa-fire"></i></div>
            <div class="flex-1 overflow-hidden">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">En Çok Oylanan</p>
                <h3 class="text-sm font-bold text-gray-800 leading-tight line-clamp-1" title="<?php echo htmlspecialchars($most_voted['name'] ?? '-'); ?>"><?php echo htmlspecialchars($most_voted['name'] ?? 'Yok'); ?></h3>
                <p class="text-xs text-amber-600 font-bold mt-0.5"><?php echo $most_voted ? $most_voted['cnt'] . ' Oy' : '-'; ?></p>
            </div>
        </div>
        <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-emerald-50">
            <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl shadow-sm"><i class="fa-solid fa-star"></i></div>
            <div class="flex-1 overflow-hidden">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">En Yüksek Puanlı</p>
                <h3 class="text-sm font-bold text-gray-800 leading-tight line-clamp-1" title="<?php echo htmlspecialchars($top_rated['name'] ?? '-'); ?>"><?php echo htmlspecialchars($top_rated['name'] ?? 'Yok'); ?></h3>
                <p class="text-xs text-emerald-600 font-bold mt-0.5"><?php echo $top_rated ? number_format($top_rated['avg_score'], 1) . ' / 5.0' : '-'; ?></p>
            </div>
        </div>
    </div>

    <?php if (isset($success_msg)): ?>
        <div
            class="bg-green-100/80 border border-green-300 text-green-700 px-4 py-3 rounded-xl mb-6 shadow-sm backdrop-blur-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-check"></i>
            <span class="block sm:inline font-medium"><?php echo $success_msg; ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div
            class="bg-red-100/80 border border-red-300 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm backdrop-blur-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span class="block sm:inline font-medium"><?php echo $error_msg; ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="p-4 font-bold text-gray-700">Proje Kodu</th>
                    <th class="p-4 font-bold text-gray-700">Proje Adı</th>
                    <th class="p-4 font-bold text-gray-700">Durum</th>
                    <th class="p-4 text-right font-bold text-gray-700">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM projects");
                while ($row = $stmt->fetch()):
                    ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="p-4 font-mono font-bold text-indigo-600"><?php echo $row['id']; ?></td>
                        <td class="p-4 font-medium text-gray-800"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td class="p-4"><span
                                class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Aktif</span></td>
                        <td class="p-4 text-right space-x-2">
                            <a href="vote.php?id=<?php echo urlencode($row['id']); ?>"
                                class="inline-block bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-sm hover:bg-indigo-200 transition"
                                title="Proje Sayfasını Görüntüle">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="admin_edit.php?id=<?php echo urlencode($row['id']); ?>"
                                class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded text-sm hover:bg-blue-200 transition">Düzenle</a>

                            <form method="POST" action="admin.php" class="inline-block"
                                onsubmit="return confirm('<?php echo htmlspecialchars($row['name']); ?> projesini silmek istediğinize emin misiniz? Bu işlem geri alınamaz!');">
                                <input type="hidden" name="delete_project_id" value="<?php echo $row['id']; ?>">
                                <button type="submit"
                                    class="bg-red-100 text-red-700 px-3 py-1 rounded text-sm hover:bg-red-200 transition">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>