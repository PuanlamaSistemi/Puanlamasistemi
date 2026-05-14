<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Liderlik Tablosu (Sadece oy almış en iyi 3 proje)
$stmt_top = $pdo->query("
    SELECT * FROM (
        SELECT p.*, 
               (SELECT AVG((design_score + tech_score + presentation_score + innovation_score) / 4) / 2 FROM votes WHERE project_id = p.id) as avg_score,
               (SELECT COUNT(id) FROM votes WHERE project_id = p.id) as vote_count
        FROM projects p 
    ) as sub
    WHERE vote_count > 0
    ORDER BY avg_score DESC, created_at DESC
    LIMIT 3
");
$top_projects = $stmt_top->fetchAll();

// Tüm projeler (Eskisi gibi eklenme sırasına göre)
$stmt_all = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
$all_projects = $stmt_all->fetchAll();
?>

<div class="flex flex-col items-center justify-center min-h-[70vh] text-center relative z-10 py-10">
    
    <!-- Floating Background Decorators -->
    <div class="absolute top-0 left-10 w-32 h-32 bg-indigo-300 rounded-full mix-blend-multiply filter blur-2xl opacity-50 float-animation-delayed -z-10 dark:hidden"></div>
    <div class="absolute bottom-10 right-20 w-40 h-40 bg-pink-300 rounded-full mix-blend-multiply filter blur-2xl opacity-50 float-animation -z-10 dark:hidden"></div>

    <div class="glass-card p-6 rounded-full inline-flex items-center justify-center mb-8 mt-4 float-animation">
        <i class="fa-solid fa-expand text-5xl text-indigo-600"></i>
    </div>
    
    <h1 class="text-4xl md:text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-800 to-purple-600 dark:from-indigo-300 dark:to-purple-300 mb-6 tracking-tight drop-shadow-sm">
        Projeleri Kolayca <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-pink-500 dark:from-indigo-400 dark:to-pink-400">Değerlendirin</span>
    </h1>
    
    <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 mb-12 max-w-2xl font-medium leading-relaxed">
        Aşağıdan projeleri seçebilir veya projenin benzersiz kodunu girebilirsiniz.
    </p>

    <!-- Search Box -->
    <div class="glass-card p-8 md:p-10 rounded-3xl w-full max-w-lg relative mb-16">
        <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-3xl blur opacity-20 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>
        
        <form action="vote.php" method="GET" class="relative space-y-5 z-10">
            <div class="relative group">
                <i class="fa-solid fa-magnifying-glass absolute left-5 top-4 text-indigo-400 dark:text-indigo-300 text-lg transition-transform group-focus-within:scale-110 group-focus-within:text-indigo-600 dark:group-focus-within:text-indigo-200"></i>
                <input type="text" name="id" required placeholder="Proje Kodu (Örn: P-101)" 
                       class="glass-input w-full pl-14 pr-4 py-4 rounded-2xl text-lg focus:outline-none">
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-500 dark:to-purple-500 text-white font-bold py-4 rounded-2xl hover:from-indigo-700 hover:to-purple-700 dark:hover:from-indigo-400 dark:hover:to-purple-400 transition shadow-lg shadow-indigo-300 dark:shadow-none hover:shadow-xl hover:-translate-y-1 duration-300 group">
                Projeyi Bul ve Oyla
                <i class="fa-solid fa-arrow-right ml-2 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all"></i>
            </button>
        </form>
    </div>

    <!-- Leaderboard (Compact) -->
    <?php if(count($top_projects) > 0): ?>
    <div class="w-full max-w-3xl mx-auto px-4 mb-20">
        <h2 class="text-2xl font-extrabold text-amber-600 dark:text-amber-500 mb-6 flex items-center justify-center gap-3">
            <i class="fa-solid fa-trophy text-amber-500"></i> Güncel Liderlik Tablosu
        </h2>
        
        <div class="glass-card rounded-3xl overflow-hidden p-2 shadow-sm border border-amber-200/50">
            <?php foreach($top_projects as $index => $tp): 
                $avg = number_format($tp['avg_score'], 1);
                
                $medal_icon = '';
                $bg_color = 'hover:bg-indigo-50/50';
                $text_color = 'text-gray-500';
                
                if($index === 0) {
                    $medal_icon = '<i class="fa-solid fa-medal text-yellow-500 text-2xl drop-shadow-sm"></i>';
                    $bg_color = 'bg-yellow-50/50 dark:bg-white/5 hover:bg-yellow-100/50 dark:hover:bg-white/10 border border-yellow-100 dark:border-white/10';
                    $text_color = 'text-yellow-700 dark:text-yellow-400';
                } elseif($index === 1) {
                    $medal_icon = '<i class="fa-solid fa-medal text-gray-400 text-2xl drop-shadow-sm"></i>';
                    $bg_color = 'bg-gray-50 dark:bg-white/5 hover:bg-gray-100/50 dark:hover:bg-white/10 border border-gray-100 dark:border-white/10';
                    $text_color = 'text-gray-600 dark:text-gray-300';
                } elseif($index === 2) {
                    $medal_icon = '<i class="fa-solid fa-medal text-amber-600 text-2xl drop-shadow-sm"></i>';
                    $bg_color = 'bg-orange-50/30 dark:bg-white/5 hover:bg-orange-100/30 dark:hover:bg-white/10 border border-orange-100 dark:border-white/10';
                    $text_color = 'text-amber-800 dark:text-amber-400';
                }
            ?>
                <a href="vote.php?id=<?php echo urlencode($tp['id']); ?>" class="flex items-center justify-between p-4 mb-2 last:mb-0 rounded-2xl transition-colors <?php echo $bg_color; ?>">
                    <div class="flex items-center gap-4 text-left">
                        <div class="w-10 text-center font-black <?php echo $text_color; ?>">
                            <?php echo $medal_icon; ?>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-100 text-lg leading-tight line-clamp-1"><?php echo htmlspecialchars($tp['name']); ?></h3>
                            <span class="text-xs text-indigo-500 dark:text-indigo-400 font-bold tracking-wider">KOD: <?php echo htmlspecialchars($tp['id']); ?></span>
                        </div>
                    </div>
                    
                    <div class="text-right flex items-center gap-4">
                        <div class="hidden sm:block">
                            <span class="text-[10px] text-gray-500 dark:text-gray-400 font-bold block uppercase"><?php echo $tp['vote_count']; ?> OY</span>
                        </div>
                        <div class="bg-white dark:bg-black/30 rounded-xl px-4 py-2 shadow-sm border border-gray-100 dark:border-white/10 flex items-center gap-2">
                            <span class="text-lg font-black text-indigo-900 dark:text-white"><?php echo $avg; ?></span>
                            <i class="fa-solid fa-star text-amber-400 text-sm"></i>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Projects List (All Projects) -->
    <div class="w-full max-w-6xl mx-auto px-4" id="allProjectsSection">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <h2 class="text-3xl font-extrabold text-indigo-900 dark:text-indigo-200 border-l-4 border-indigo-500 pl-4">Yüklenen Tüm Projeler</h2>
            
            <div class="relative w-full md:w-80 group">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-indigo-400 dark:text-indigo-300 group-focus-within:text-indigo-600 dark:group-focus-within:text-indigo-100 transition-colors"></i>
                <input type="text" id="liveSearchInput" placeholder="Proje Adı veya Kod ile Ara..." class="glass-input w-full pl-10 pr-4 py-3 rounded-xl text-sm shadow-sm focus:shadow-md transition-shadow">
            </div>
        </div>
        
        <?php if(count($all_projects) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 text-left" id="projectsGrid">
                <?php foreach($all_projects as $p): ?>
                    <a href="vote.php?id=<?php echo urlencode($p['id']); ?>" class="project-card block glass-card rounded-3xl p-6 relative overflow-hidden group hover:-translate-y-2 hover:shadow-xl dark:hover:shadow-[0_0_25px_rgba(79,70,229,0.3)] transition-all duration-300" data-name="<?php echo htmlspecialchars(strtolower($p['name'])); ?>" data-code="<?php echo htmlspecialchars(strtolower($p['id'])); ?>">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-indigo-200 to-pink-200 dark:from-indigo-500 dark:to-pink-500 rounded-bl-full opacity-50 dark:opacity-20 group-hover:scale-150 transition-transform duration-500"></div>
                        
                        <div class="mb-4">
                            <span class="inline-block bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold px-3 py-1 rounded-full mb-3 shadow-sm border border-indigo-200/50 dark:border-indigo-500/30">KOD: <?php echo htmlspecialchars($p['id']); ?></span>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-300 transition-colors line-clamp-2"><?php echo htmlspecialchars($p['name']); ?></h3>
                        </div>
                        
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-6 line-clamp-3 leading-relaxed"><?php echo htmlspecialchars($p['description']); ?></p>
                        
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            <i class="fa-solid fa-users text-indigo-400 dark:text-indigo-300 text-sm"></i>
                            <?php 
                                $members = explode(',', $p['team_members'] ?? 'Belirtilmedi');
                                $count = 0;
                                foreach($members as $m):
                                    $m = trim($m);
                                    if(empty($m)) continue;
                                    if($count >= 3) { echo '<span class="text-xs text-gray-400 dark:text-gray-500 font-bold">+'.(count($members)-3).'</span>'; break; }
                            ?>
                                <span class="bg-indigo-50 dark:bg-black/30 text-indigo-700 dark:text-indigo-200 border border-indigo-100 dark:border-white/10 text-[10px] font-bold px-2 py-0.5 rounded-md shadow-sm">
                                    <?php echo htmlspecialchars($m); ?>
                                </span>
                            <?php 
                                $count++;
                                endforeach; 
                            ?>
                        </div>
                        
                        <div class="flex justify-between items-center border-t border-indigo-100/50 pt-4 relative z-10">
                            <?php if($p['video_url']): ?>
                                <span class="text-emerald-600 text-[10px] font-bold flex items-center bg-emerald-50 px-2 py-1 rounded-md border border-emerald-100"><i class="fa-solid fa-video mr-1"></i> Yüklü</span>
                            <?php else: ?>
                                <span class="text-amber-600 text-[10px] font-bold flex items-center bg-amber-50 px-2 py-1 rounded-md border border-amber-100"><i class="fa-solid fa-video-slash mr-1"></i> Yok</span>
                            <?php endif; ?>
                            
                            <span class="text-indigo-600 font-bold text-sm group-hover:text-purple-600 transition-colors flex items-center">
                                Oyla <i class="fa-solid fa-chevron-right ml-1 text-xs transition-transform group-hover:translate-x-1"></i>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass-card p-12 rounded-3xl text-center flex flex-col items-center justify-center">
                <i class="fa-solid fa-folder-open text-6xl text-indigo-200 mb-4"></i>
                <h3 class="text-2xl font-bold text-indigo-900 mb-2">Henüz Proje Yok</h3>
                <p class="text-gray-500">Sisteme henüz hiçbir proje yüklenmemiş.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
document.getElementById('liveSearchInput')?.addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.project-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const name = card.getAttribute('data-name');
        const code = card.getAttribute('data-code');
        
        if(name.includes(term) || code.includes(term)) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    let emptyMsg = document.getElementById('emptySearchMsg');
    if (visibleCount === 0 && term !== '') {
        if (!emptyMsg) {
            emptyMsg = document.createElement('div');
            emptyMsg.id = 'emptySearchMsg';
            emptyMsg.className = 'col-span-full text-center py-10 text-gray-500';
            emptyMsg.innerHTML = '<i class="fa-solid fa-magnifying-glass-minus text-4xl mb-3 text-indigo-200"></i><p class="font-medium">Aramanıza uygun proje bulunamadı.</p>';
            document.getElementById('projectsGrid').appendChild(emptyMsg);
        }
        emptyMsg.style.display = 'block';
    } else if (emptyMsg) {
        emptyMsg.style.display = 'none';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
