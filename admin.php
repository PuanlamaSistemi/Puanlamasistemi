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
            
            <h2 class="text-2xl font-extrabold text-gray-800 dark:text-indigo-300 mb-2">Erişim Reddedildi</h2>
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

// --- GRAFİK VERİLERİ İÇİN ---
// Projelere göre oy dağılımı (Pie Chart)
$pie_data_stmt = $pdo->query("
    SELECT p.name, COUNT(v.id) as vote_count 
    FROM projects p 
    LEFT JOIN votes v ON p.id = v.project_id 
    GROUP BY p.id
    HAVING vote_count > 0
");
$pie_labels = [];
$pie_data = [];
while($row = $pie_data_stmt->fetch()) {
    $pie_labels[] = $row['name'];
    $pie_data[] = $row['vote_count'];
}

// Projelere göre ortalama puanlar (Bar Chart)
$bar_data_stmt = $pdo->query("
    SELECT p.name, 
           AVG((v.design_score + v.tech_score + v.presentation_score + v.innovation_score)/4)/2 as avg_score 
    FROM projects p 
    JOIN votes v ON p.id = v.project_id 
    GROUP BY p.id
");
$bar_labels = [];
$bar_data = [];
while($row = $bar_data_stmt->fetch()) {
    $bar_labels[] = $row['name'];
    $bar_data[] = round($row['avg_score'], 2);
}

require_once 'includes/header.php';
?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="max-w-6xl mx-auto">
    <div
        class="flex flex-col md:flex-row justify-between items-center bg-rose-50 dark:bg-rose-950/30 dark:border-rose-900/50 border border-rose-200 text-rose-800 dark:text-rose-300 p-6 rounded-2xl mb-8">
        <div>
            <h1 class="text-2xl font-bold mb-1 flex items-center gap-2 dark:text-rose-200">
                <i class="fa-solid fa-triangle-exclamation"></i> Jüri / Yönetici Paneli
            </h1>
            <p class="opacity-80 dark:opacity-70">
                Sistem genelindeki verilere müdahale yetkilisiniz.
            </p>
        </div>
    </div>

    <!-- İstatistikler Paneli -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-indigo-50 dark:border-indigo-900/50">
            <div class="w-14 h-14 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-2xl shadow-sm"><i class="fa-solid fa-folder-open"></i></div>
            <div class="flex-1 overflow-hidden">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Toplam Proje</p>
                <h3 class="text-2xl font-black text-gray-800 dark:text-gray-200"><?php echo $stat_projects; ?></h3>
            </div>
        </div>
        <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-pink-50 dark:border-pink-900/50">
            <div class="w-14 h-14 rounded-full bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 flex items-center justify-center text-2xl shadow-sm"><i class="fa-solid fa-check-to-slot"></i></div>
            <div class="flex-1 overflow-hidden">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Kullanılan Oy</p>
                <h3 class="text-2xl font-black text-gray-800 dark:text-gray-200"><?php echo $stat_votes; ?></h3>
            </div>
        </div>
        <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-amber-50 dark:border-amber-900/50">
            <div class="w-14 h-14 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-2xl shadow-sm"><i class="fa-solid fa-fire"></i></div>
            <div class="flex-1 overflow-hidden">
                <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">En Çok Oylanan</p>
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 leading-tight line-clamp-1" title="<?php echo htmlspecialchars($most_voted['name'] ?? '-'); ?>"><?php echo htmlspecialchars($most_voted['name'] ?? 'Yok'); ?></h3>
                <p class="text-xs text-amber-600 dark:text-amber-400 font-bold mt-0.5"><?php echo $most_voted ? $most_voted['cnt'] . ' Oy' : '-'; ?></p>
            </div>
        </div>
        <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-emerald-50 dark:border-emerald-900/50">
            <div class="w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-2xl shadow-sm"><i class="fa-solid fa-star"></i></div>
            <div class="flex-1 overflow-hidden">
                <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">En Yüksek Puanlı</p>
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 leading-tight line-clamp-1" title="<?php echo htmlspecialchars($top_rated['name'] ?? '-'); ?>"><?php echo htmlspecialchars($top_rated['name'] ?? 'Yok'); ?></h3>
                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-bold mt-0.5"><?php echo $top_rated ? number_format($top_rated['avg_score'], 1) . ' / 5.0' : '-'; ?></p>
            </div>
        </div>
    </div>

    <!-- Grafikler -->
    <?php if(count($pie_labels) > 0): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Oy Dağılımı (Pie Chart) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4 self-start">Projelere Göre Oy Dağılımı</h3>
            <div class="w-full max-w-sm aspect-square relative">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
        <!-- Ortalama Puanlar (Bar Chart) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4 self-start">Projelerin Ortalama Puanları</h3>
            <div class="w-full h-64 relative">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Karanlık mod kontrolü
        const isDarkMode = document.documentElement.classList.contains('dark');
        const chartTextColor = isDarkMode ? '#d0d0d0' : '#374151';
        const chartGridColor = isDarkMode ? '#404040' : '#e5e7eb';

        // Pie Chart
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($pie_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($pie_data); ?>,
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.8)',  // indigo-500
                        'rgba(236, 72, 153, 0.8)',  // pink-500
                        'rgba(245, 158, 11, 0.8)',  // amber-500
                        'rgba(16, 185, 129, 0.8)',  // emerald-500
                        'rgba(59, 130, 246, 0.8)'   // blue-500
                    ],
                    borderWidth: 0
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { 
                        position: 'bottom',
                        labels: {
                            color: chartTextColor,
                            font: { size: 12, weight: 'bold' }
                        }
                    }
                }
            }
        });

        // Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($bar_labels); ?>,
                datasets: [{
                    label: 'Ortalama Puan',
                    data: <?php echo json_encode($bar_data); ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.6)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true, 
                        max: 5,
                        ticks: { color: chartTextColor },
                        grid: { color: chartGridColor }
                    },
                    x: {
                        ticks: { color: chartTextColor },
                        grid: { color: chartGridColor }
                    }
                },
                plugins: { 
                    legend: { 
                        display: true,
                        labels: {
                            color: chartTextColor,
                            font: { size: 12, weight: 'bold' }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>

    <?php if (isset($success_msg)): ?>
        <div
            class="bg-green-100/80 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300 border border-green-300 text-green-700 px-4 py-3 rounded-xl mb-6 shadow-sm backdrop-blur-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-check"></i>
            <span class="block sm:inline font-medium"><?php echo $success_msg; ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div
            class="bg-red-100/80 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300 border border-red-300 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm backdrop-blur-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span class="block sm:inline font-medium"><?php echo $error_msg; ?></span>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">Sistemdeki Projeler</h2>
        <a href="export.php" class="bg-emerald-500 dark:bg-emerald-600 dark:hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-bold shadow-sm hover:bg-emerald-600 transition flex items-center gap-2">
            <i class="fa-solid fa-file-csv"></i> Sonuçları İndir (CSV)
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <th class="p-4 font-bold text-gray-700 dark:text-gray-300">Proje Kodu</th>
                    <th class="p-4 font-bold text-gray-700 dark:text-gray-300">Proje Adı</th>
                    <th class="p-4 font-bold text-gray-700 dark:text-gray-300">Durum</th>
                    <th class="p-4 text-center font-bold text-gray-700 dark:text-gray-300">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM projects");
                while ($row = $stmt->fetch()):
                    ?>
                    <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 font-mono font-bold text-indigo-600 dark:text-indigo-400 text-sm"><?php echo $row['id']; ?></td>
                        <td class="p-4 font-medium text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td class="p-4"><span
                                class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-1 rounded text-xs font-bold">Aktif</span></td>
                        <td class="p-4 text-center">
                            <div class="flex flex-wrap justify-center gap-1 md:gap-2">
                                <a href="vote.php?id=<?php echo urlencode($row['id']); ?>"
                                    class="inline-flex items-center justify-center bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-2 py-1 md:px-3 md:py-1 rounded text-xs md:text-sm hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition whitespace-nowrap"
                                    title="Proje Sayfasını Görüntüle">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="admin_project_details.php?id=<?php echo urlencode($row['id']); ?>"
                                    class="inline-flex items-center justify-center bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-1 md:px-3 md:py-1 rounded text-xs md:text-sm hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition whitespace-nowrap gap-1"
                                    title="Puan Detayları ve Yorumlar">
                                    <i class="fa-solid fa-list-check hidden md:inline"></i> <span class="hidden md:inline">Detay</span><span class="inline md:hidden">D</span>
                                </a>
                                <?php 
                                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
                                    $base_dir = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
                                    if($base_dir == '/') $base_dir = '';
                                    $v_url = $protocol . $_SERVER['HTTP_HOST'] . $base_dir . "/vote.php?id=" . urlencode($row['id']);
                                    $q_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($v_url);
                                ?>
                                <a href="<?php echo $q_url; ?>" target="_blank"
                                    class="inline-flex items-center justify-center bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 px-2 py-1 md:px-3 md:py-1 rounded text-xs md:text-sm hover:bg-pink-200 dark:hover:bg-pink-900/50 transition whitespace-nowrap"
                                    title="QR Kodunu Görüntüle (Büyük Boy)">
                                    <i class="fa-solid fa-qrcode"></i>
                                </a>
                                <a href="admin_edit.php?id=<?php echo urlencode($row['id']); ?>"
                                    class="inline-flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-1 md:px-3 md:py-1 rounded text-xs md:text-sm hover:bg-blue-200 dark:hover:bg-blue-900/50 transition whitespace-nowrap">Düzenle</a>

                                <form method="POST" action="admin.php" class="inline-block"
                                    onsubmit="return confirm('<?php echo htmlspecialchars($row['name']); ?> projesini silmek istediğinize emin misiniz? Bu işlem geri alınamaz!');">
                                    <input type="hidden" name="delete_project_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit"
                                        class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-2 py-1 md:px-3 md:py-1 rounded text-xs md:text-sm hover:bg-red-200 dark:hover:bg-red-900/50 transition whitespace-nowrap">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>