<?php
require_once 'includes/db.php';

$project = null;
$error = null;
$success = false;

if(isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    // Projeyi çek
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $project = $stmt->fetch();
    
    if(!$project) {
        $error = "Belirtilen proje bulunamadı!";
    }
} else {
    $error = "Geçersiz istek. Proje ID'si gerekli.";
}

// Form gönderildiyse (Değerlendirme Formu)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vote']) && $project) {
    $session_id = session_id();
    
    // Daha önce oy vermiş mi kontrolü
    $check = $pdo->prepare("SELECT id FROM votes WHERE project_id = ? AND voter_session = ?");
    $check->execute([$project['id'], $session_id]);
    
    if($check->rowCount() > 0) {
        $error = "Bu proje için daha önce oy kullandınız.";
    } else {
        // Oyu kaydet
        $design = $_POST['design'];
        $tech = $_POST['tech'];
        $presentation = $_POST['presentation'];
        $innovation = $_POST['innovation'];
        $comment = $_POST['comment'] ?? '';
        
        $insert = $pdo->prepare("INSERT INTO votes (project_id, design_score, tech_score, presentation_score, innovation_score, comment, voter_session) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if($insert->execute([$project['id'], $design, $tech, $presentation, $innovation, $comment, $session_id])) {
            $success = true;
        } else {
            $error = "Oyunuz kaydedilirken sistemsel bir hata oluştu.";
        }
    }
}

// Yorum Silme İşlemi (Sadece jüriler)
$comment_msg = null;
if(isset($_POST['delete_comment_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'jury') {
    $del_comment_id = $_POST['delete_comment_id'];
    $del_cmt = $pdo->prepare("DELETE FROM votes WHERE id = ?");
    if($del_cmt->execute([$del_comment_id])) {
        $comment_msg = "Yorum ve değerlendirme başarıyla silindi.";
    }
}

// Proje ortalamasını ve yorumları çek (Eğer proje varsa)
$overall_avg = 0;
$total_votes = 0;
$votes = [];
if ($project) {
    // Genel Ortalama (1-10 arası saklandığı için 2'ye bölerek 5 üzerinden hesaplıyoruz)
    $avg_stmt = $pdo->prepare("SELECT AVG((design_score + tech_score + presentation_score + innovation_score) / 4) as overall_avg, COUNT(id) as total FROM votes WHERE project_id = ?");
    $avg_stmt->execute([$project['id']]);
    $avg_data = $avg_stmt->fetch();
    
    if ($avg_data['total'] > 0) {
        $overall_avg = round($avg_data['overall_avg'] / 2, 1);
        $total_votes = $avg_data['total'];
    }
    
    // Yorumlar
    $votes_stmt = $pdo->prepare("SELECT * FROM votes WHERE project_id = ? ORDER BY created_at DESC");
    $votes_stmt->execute([$project['id']]);
    $votes = $votes_stmt->fetchAll();
}

require_once 'includes/header.php';
?>

<style>
/* Yıldız arayüzü için özel CSS */
.star-rating-overlay {
    position: relative;
    display: inline-block;
    font-size: 2.5rem; /* Yıldız boyutu */
    color: #e2e8f0; /* Boş yıldız rengi */
    cursor: pointer;
    line-height: 1;
}
.star-rating-overlay .stars-bg {
    display: flex;
    gap: 4px;
}
.star-rating-overlay .stars-fill {
    position: absolute;
    top: 0;
    left: 0;
    white-space: nowrap;
    overflow: hidden;
    color: #fbbf24; /* Dolu yıldız rengi (amber-400) */
    pointer-events: none;
    display: flex;
    gap: 4px;
}
.star-rating-overlay input[type="range"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    margin: 0;
}
</style>

<?php if($error): ?>
    <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Hata!</strong>
        <span class="block sm:inline"><?php echo $error; ?></span>
        <div class="mt-4">
            <a href="index.php" class="text-indigo-600 dark:text-indigo-400 hover:underline">Geri Dön</a>
        </div>
    </div>
<?php elseif($success): ?>
    <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl text-center max-w-2xl mx-auto my-12 dark:border dark:border-gray-700">
        <i class="fa-solid fa-circle-check text-6xl text-green-500 mb-4"></i>
        <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-200 mb-2">Başarılı!</h2>
        <p class="text-gray-600 dark:text-gray-300 text-lg">Oyunuz başarıyla veritabanına kaydedildi. Desteğiniz için teşekkür ederiz.</p>
        <a href="vote.php?id=<?php echo urlencode($project['id']); ?>" class="mt-6 inline-block bg-indigo-600 dark:bg-indigo-500 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600">Projeye Geri Dön</a>
    </div>
<?php elseif($project): ?>

    <div class="max-w-5xl mx-auto relative z-10 space-y-8">
        <?php if($comment_msg): ?>
            <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-2xl relative mb-4 shadow-sm flex items-center" role="alert">
                <i class="fa-solid fa-circle-check mr-3 text-xl"></i>
                <span class="block sm:inline font-bold"><?php echo $comment_msg; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Proje Bilgi Kartı -->
        <div class="glass-card rounded-3xl p-6 md:p-10 flex flex-col md:flex-row gap-10 relative overflow-hidden">
            <div class="w-full md:w-1/2">
                <?php if($project['video_url']): ?>
                    <div class="bg-black/80 rounded-2xl overflow-hidden aspect-video relative flex items-center justify-center shadow-lg border border-gray-700/50">
                        <video controls class="w-full h-full object-cover">
                            <source src="<?php echo htmlspecialchars($project['video_url']); ?>" type="video/mp4">
                        </video>
                    </div>
                <?php else: ?>
                    <div class="bg-gradient-to-tr from-indigo-100/50 to-purple-100/50 dark:from-white/5 dark:to-white/10 backdrop-blur-sm border border-white/50 dark:border-white/10 rounded-2xl aspect-video flex flex-col items-center justify-center text-indigo-400 dark:text-gray-400 shadow-inner">
                        <i class="fa-solid fa-video-slash text-5xl mb-3 opacity-60"></i>
                        <p class="font-medium">Video Bulunmuyor</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="w-full md:w-1/2 flex flex-col justify-center">
                <div class="flex justify-between items-start mb-4">
                    <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-900 to-purple-900 dark:from-white dark:to-gray-300 tracking-tight"><?php echo htmlspecialchars($project['name']); ?></h1>
                    
                    <!-- Genel Reyting Gösterimi -->
                    <div class="bg-amber-50 dark:bg-black/30 border border-amber-200 dark:border-white/10 px-4 py-2 rounded-xl text-center shadow-sm">
                        <div class="text-2xl font-black text-amber-500 mb-1 leading-none"><?php echo number_format($overall_avg, 1); ?></div>
                        <div class="flex text-amber-400 text-xs">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <?php if($i <= $overall_avg): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php elseif($i - 0.5 <= $overall_avg): ?>
                                    <i class="fa-solid fa-star-half-stroke"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="text-[10px] text-amber-700 dark:text-amber-400 mt-1 font-bold uppercase tracking-widest"><?php echo $total_votes; ?> OY</div>
                    </div>
                </div>

                <p class="text-indigo-900/70 dark:text-indigo-200/80 leading-relaxed font-medium mb-6"><?php echo htmlspecialchars($project['description']); ?></p>
                
                <div class="bg-indigo-50/80 dark:bg-white/5 backdrop-blur-sm border-l-4 border-indigo-400 p-4 rounded-r-xl shadow-sm mb-4">
                    <p class="text-xs font-bold text-indigo-400 dark:text-indigo-300 uppercase tracking-wider mb-2"><i class="fa-solid fa-users mr-1"></i> Projeyi Yapanlar</p>
                    <div class="flex flex-wrap gap-2">
                        <?php 
                            $members = explode(',', $project['team_members'] ?? 'Belirtilmedi');
                            foreach($members as $m):
                                $m = trim($m);
                                if(empty($m)) continue;
                        ?>
                            <span class="bg-white dark:bg-black/40 text-indigo-700 dark:text-indigo-200 border border-indigo-100 dark:border-white/10 text-sm font-bold px-3 py-1 rounded-full shadow-sm">
                                <?php echo htmlspecialchars($m); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Değerlendirme Formu -->
            <div class="glass-card rounded-3xl p-6 md:p-8 relative">
                <h2 class="text-2xl font-extrabold text-indigo-900 dark:text-indigo-300 mb-6 border-b-2 border-indigo-100/50 dark:border-indigo-900/50 pb-3 inline-block tracking-tight">Projeyi Değerlendirin</h2>
                
                <form action="vote.php?id=<?php echo urlencode($project['id']); ?>" method="POST" class="relative z-10 space-y-6">
                    <input type="hidden" name="submit_vote" value="1">
                    
                    <?php 
                    $criteria = [
                        'design' => 'Tasarım',
                        'tech' => 'Teknik Uygulama',
                        'presentation' => 'Sunum',
                        'innovation' => 'İnovasyon'
                    ];
                    
                    foreach($criteria as $name => $label): 
                    ?>
                        <div class="group flex flex-col md:flex-row md:items-center justify-between gap-2">
                            <label class="font-bold text-indigo-900 dark:text-indigo-300 tracking-wide text-sm uppercase md:w-1/3"><?php echo $label; ?></label>
                            
                            <div class="flex items-center gap-4">
                                <div class="star-rating-overlay">
                                    <div class="stars-bg dark:text-gray-700">
                                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                                    </div>
                                    <div class="stars-fill" style="width: 50%;">
                                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                                    </div>
                                    <input type="range" name="<?php echo $name; ?>" min="1" max="10" value="5" 
                                           oninput="this.previousElementSibling.style.width = (this.value * 10) + '%'; document.getElementById('val_<?php echo $name; ?>').innerText = (this.value / 2).toFixed(1);">
                                </div>
                                <span class="text-lg font-black text-amber-500 w-8 text-right" id="val_<?php echo $name; ?>">2.5</span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="mt-8 pt-6 border-t border-indigo-100/50 dark:border-indigo-900/50">
                        <label class="block font-bold text-indigo-900 dark:text-indigo-300 mb-3 tracking-wide text-sm uppercase">Yorum ve Geri Bildirim <span class="text-indigo-400 dark:text-indigo-500 font-normal lowercase">(Opsiyonel)</span></label>
                        <textarea name="comment" rows="3" class="glass-input w-full rounded-2xl p-4 text-gray-800 dark:text-gray-100 placeholder-indigo-300 dark:placeholder-indigo-400 resize-none" placeholder="Örn: Gerçekten çok başarılı bir fikir..."></textarea>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="w-full md:w-auto bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-4 px-10 rounded-2xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg shadow-indigo-200 dark:shadow-none hover:shadow-xl hover:-translate-y-1">
                            Oyunu Gönder <i class="fa-solid fa-paper-plane ml-2"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Yorumlar Bölümü -->
            <div class="glass-card rounded-3xl p-6 md:p-8 flex flex-col h-full">
                <h2 class="text-2xl font-extrabold text-indigo-900 dark:text-indigo-300 mb-6 border-b-2 border-indigo-100/50 dark:border-indigo-900/50 pb-3 inline-block tracking-tight">Değerlendirmeler ve Yorumlar</h2>
                
                <div class="overflow-y-auto pr-2 space-y-4 flex-1 max-h-[600px]">
                    <?php if(empty($votes)): ?>
                        <div class="text-center py-10">
                            <i class="fa-regular fa-comments text-5xl text-indigo-200 dark:text-gray-600 mb-3"></i>
                            <p class="text-indigo-400 dark:text-indigo-300 font-medium">Henüz hiç değerlendirme yapılmamış.<br>İlk oylayan siz olun!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($votes as $v): 
                            // Bireysel Ortalama Hesaplama (5 üzerinden)
                            $ind_avg = ($v['design_score'] + $v['tech_score'] + $v['presentation_score'] + $v['innovation_score']) / 4 / 2;
                        ?>
                            <div class="bg-white/60 dark:bg-gray-800/80 dark:border-gray-700 p-5 rounded-2xl border border-white shadow-sm hover:shadow-md transition">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                        <span class="font-bold text-indigo-900 dark:text-indigo-200 text-sm">Anonim Jüri</span>
                                        
                                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'jury'): ?>
                                            <form method="POST" action="vote.php?id=<?php echo urlencode($project['id']); ?>" class="inline ml-2" onsubmit="return confirm('Bu değerlendirmeyi silmek istediğinize emin misiniz?');">
                                                <input type="hidden" name="delete_comment_id" value="<?php echo $v['id']; ?>">
                                                <button type="submit" class="text-red-400 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 text-xs transition px-2 py-1 bg-red-50 dark:bg-red-900/30 dark:border dark:border-red-800/50 rounded-lg"><i class="fa-solid fa-trash"></i> Sil</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <div class="bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 px-2 py-1 rounded-lg text-xs font-black flex items-center gap-1 border border-amber-200 dark:border-amber-800/50">
                                        <?php echo number_format($ind_avg, 1); ?> <i class="fa-solid fa-star text-[10px]"></i>
                                    </div>
                                </div>
                                <?php if(!empty($v['comment'])): ?>
                                    <p class="text-gray-700 dark:text-gray-200 text-sm mt-3 bg-white/50 dark:bg-black/20 p-3 rounded-xl border border-indigo-50 dark:border-indigo-900/30 leading-relaxed italic">
                                        "<?php echo htmlspecialchars($v['comment']); ?>"
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
