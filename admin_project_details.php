<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jury') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit;
}

$project_id = $_GET['id'];

// Proje bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    die("Proje bulunamadı.");
}

// Oyları ve yorumları çek
$votes_stmt = $pdo->prepare("SELECT * FROM votes WHERE project_id = ? ORDER BY created_at DESC");
$votes_stmt->execute([$project_id]);
$votes = $votes_stmt->fetchAll();

// Ortalama hesaplamaları
$avg_design = 0;
$avg_tech = 0;
$avg_presentation = 0;
$avg_innovation = 0;
$total_votes = count($votes);

if ($total_votes > 0) {
    $sum_design = 0; $sum_tech = 0; $sum_presentation = 0; $sum_innovation = 0;
    foreach($votes as $v) {
        $sum_design += $v['design_score'] / 2;
        $sum_tech += $v['tech_score'] / 2;
        $sum_presentation += $v['presentation_score'] / 2;
        $sum_innovation += $v['innovation_score'] / 2;
    }
    $avg_design = $sum_design / $total_votes;
    $avg_tech = $sum_tech / $total_votes;
    $avg_presentation = $sum_presentation / $total_votes;
    $avg_innovation = $sum_innovation / $total_votes;
}

$general_avg = ($avg_design + $avg_tech + $avg_presentation + $avg_innovation) / 4;

require_once 'includes/header.php';
?>

<div class="max-w-5xl mx-auto py-8">
    
    <div class="mb-6">
        <a href="admin.php" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-2 transition">
            <i class="fa-solid fa-arrow-left"></i> Admin Paneline Dön
        </a>
    </div>

    <div class="glass-card p-8 rounded-3xl mb-8 border border-indigo-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-xs font-bold mb-3 inline-block">KOD: <?php echo htmlspecialchars($project['id']); ?></span>
            <h1 class="text-3xl font-extrabold text-gray-800 dark:text-indigo-300 mb-2"><?php echo htmlspecialchars($project['name']); ?></h1>
            <p class="text-gray-500 font-medium">Toplam <span class="font-bold text-indigo-600"><?php echo $total_votes; ?></span> jüri değerlendirmesi.</p>
        </div>
        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 text-white px-8 py-4 rounded-2xl shadow-lg flex flex-col items-center">
            <span class="text-sm font-bold uppercase tracking-wider opacity-90">Genel Ortalama</span>
            <div class="text-4xl font-black flex items-center gap-2 mt-1">
                <?php echo number_format($general_avg, 2); ?> <i class="fa-solid fa-star text-yellow-300 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Puan Dağılımı -->
    <?php if($total_votes > 0): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-pink-100 text-center">
            <i class="fa-solid fa-palette text-pink-500 text-2xl mb-2"></i>
            <h3 class="text-gray-500 font-bold text-xs uppercase">Tasarım</h3>
            <p class="text-2xl font-black text-gray-800 mt-1"><?php echo number_format($avg_design, 1); ?>/5</p>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-blue-100 text-center">
            <i class="fa-solid fa-microchip text-blue-500 text-2xl mb-2"></i>
            <h3 class="text-gray-500 font-bold text-xs uppercase">Teknik</h3>
            <p class="text-2xl font-black text-gray-800 mt-1"><?php echo number_format($avg_tech, 1); ?>/5</p>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-orange-100 text-center">
            <i class="fa-solid fa-person-chalkboard text-orange-500 text-2xl mb-2"></i>
            <h3 class="text-gray-500 font-bold text-xs uppercase">Sunum</h3>
            <p class="text-2xl font-black text-gray-800 mt-1"><?php echo number_format($avg_presentation, 1); ?>/5</p>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100 text-center">
            <i class="fa-solid fa-lightbulb text-purple-500 text-2xl mb-2"></i>
            <h3 class="text-gray-500 font-bold text-xs uppercase">İnovasyon</h3>
            <p class="text-2xl font-black text-gray-800 mt-1"><?php echo number_format($avg_innovation, 1); ?>/5</p>
        </div>
    </div>

    <!-- Yorumlar -->
    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
        <i class="fa-regular fa-comments text-indigo-500"></i> Jüri Yorumları ve Detayları
    </h2>

    <div class="space-y-4">
        <?php foreach($votes as $index => $v): 
            $v_avg = (($v['design_score'] + $v['tech_score'] + $v['presentation_score'] + $v['innovation_score']) / 4) / 2;
        ?>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-gray-100 text-gray-600 w-10 h-10 rounded-full flex items-center justify-center font-bold">
                        J<?php echo $total_votes - $index; ?>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-bold"><?php echo date('d.m.Y H:i', strtotime($v['created_at'])); ?></p>
                        <p class="text-indigo-600 font-bold text-sm">Jüri Değerlendirmesi</p>
                    </div>
                </div>
                <div class="bg-indigo-50 text-indigo-800 px-3 py-1 rounded-lg font-bold border border-indigo-100 flex items-center gap-1">
                    <?php echo number_format($v_avg, 1); ?> <i class="fa-solid fa-star text-amber-400"></i>
                </div>
            </div>
            
            <?php if(!empty($v['comment'])): ?>
                <div class="bg-gray-50 border-l-4 border-indigo-400 p-4 rounded-r-xl mb-4 italic text-gray-700">
                    "<?php echo nl2br(htmlspecialchars($v['comment'])); ?>"
                </div>
            <?php else: ?>
                <div class="text-gray-400 text-sm italic mb-4">Yorum yapılmamış.</div>
            <?php endif; ?>

            <div class="flex gap-2 flex-wrap">
                <span class="text-xs bg-pink-50 text-pink-700 px-2 py-1 rounded border border-pink-100">Tasarım: <?php echo $v['design_score']/2; ?></span>
                <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded border border-blue-100">Teknik: <?php echo $v['tech_score']/2; ?></span>
                <span class="text-xs bg-orange-50 text-orange-700 px-2 py-1 rounded border border-orange-100">Sunum: <?php echo $v['presentation_score']/2; ?></span>
                <span class="text-xs bg-purple-50 text-purple-700 px-2 py-1 rounded border border-purple-100">İnovasyon: <?php echo $v['innovation_score']/2; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php else: ?>
        <div class="bg-white p-10 rounded-2xl shadow-sm text-center border border-gray-100">
            <i class="fa-regular fa-folder-open text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-bold text-gray-700">Henüz oy kullanılmamış.</h3>
            <p class="text-gray-500 mt-2">Bu proje için hiçbir jüri değerlendirme yapmadı.</p>
        </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
