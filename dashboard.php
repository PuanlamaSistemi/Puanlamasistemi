<?php
require_once 'includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$project = null;
if ($user['project_id']) {
    $p_stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $p_stmt->execute([$user['project_id']]);
    $project = $p_stmt->fetch();
}

$error = null;
$success = null;

// Form gönderimi (Proje Oluşturma veya Video Güncelleme)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_dir('public/videos')) {
        mkdir('public/videos', 0777, true);
    }
    
    // Video yükleme işlemi
    $video_url = $project ? $project['video_url'] : null;
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['video']['tmp_name'];
        $name = basename($_FILES['video']['name']);
        $size = $_FILES['video']['size'];
        
        // 50 MB sınır (50 * 1024 * 1024 = 52428800)
        if ($size > 52428800) {
            $error = "Video boyutu en fazla 50 MB olabilir.";
        } else {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext !== 'mp4') {
                $error = "Sadece MP4 formatında video yükleyebilirsiniz.";
            } else {
                $new_name = uniqid('vid_') . '.mp4';
                $destination = 'public/videos/' . $new_name;
                if (move_uploaded_file($tmp_name, $destination)) {
                    $video_url = $destination;
                } else {
                    $error = "Video yüklenirken bir sorun oluştu.";
                }
            }
        }
    }
    
    if (!$error) {
        if (!$project && isset($_POST['create_project'])) {
            // Yeni proje oluştur
            $project_name = $_POST['project_name'];
            $description = $_POST['description'];
            $team_members = $_POST['team_members'];
            
            // Özel ID veya kısa rastgele ID
            if (!empty($_POST['custom_id'])) {
                $project_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['custom_id']); // Sadece harf, rakam, tire ve alt çizgi
            } else {
                $project_id = 'P-' . rand(1000, 9999);
            }
            
            // ID daha önce alınmış mı kontrol et
            $check_id = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
            $check_id->execute([$project_id]);
            
            if ($check_id->rowCount() > 0) {
                $error = "Bu proje kodu (".$project_id.") zaten kullanımda. Lütfen başka bir kod girin veya boş bırakın.";
            } else {
                $insert = $pdo->prepare("INSERT INTO projects (id, name, description, team_members, video_url) VALUES (?, ?, ?, ?, ?)");
                if ($insert->execute([$project_id, $project_name, $description, $team_members, $video_url])) {
                    // Kullanıcının project_id'sini güncelle
                    $update_user = $pdo->prepare("UPDATE users SET project_id = ? WHERE id = ?");
                    $update_user->execute([$project_id, $user_id]);
                    
                    $_SESSION['project_id'] = $project_id;
                    $success = "Projeniz başarıyla oluşturuldu ve videonuz yüklendi.";
                    // Projeyi tekrar çek
                    $p_stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
                    $p_stmt->execute([$project_id]);
                    $project = $p_stmt->fetch();
                } else {
                    $error = "Proje oluşturulurken bir hata meydana geldi.";
                }
            }
        } elseif ($project && isset($_POST['update_project_details'])) {
            $new_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['edit_id']);
            $new_name = $_POST['edit_name'];
            $new_team = $_POST['edit_team'];
            $new_desc = $_POST['edit_desc'];
            
            $id_taken = false;
            if ($new_id !== $project['id']) {
                $check = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
                $check->execute([$new_id]);
                if ($check->rowCount() > 0) {
                    $id_taken = true;
                    $error = "Bu proje kodu başka bir proje tarafından kullanılıyor.";
                }
            }
            
            if (!$id_taken) {
                try {
                    // Update project ID requires foreign_key_checks disabling if it's referenced
                    $pdo->exec("SET foreign_key_checks = 0");
                    
                    // Update the project
                    $upd = $pdo->prepare("UPDATE projects SET id=?, name=?, team_members=?, description=? WHERE id=?");
                    $upd->execute([$new_id, $new_name, $new_team, $new_desc, $project['id']]);
                    
                    // Update references if ID changed
                    if ($new_id !== $project['id']) {
                        $pdo->prepare("UPDATE users SET project_id=? WHERE project_id=?")->execute([$new_id, $project['id']]);
                        $pdo->prepare("UPDATE votes SET project_id=? WHERE project_id=?")->execute([$new_id, $project['id']]);
                        $_SESSION['project_id'] = $new_id;
                    }
                    
                    $pdo->exec("SET foreign_key_checks = 1");
                    
                    $success = "Proje bilgileriniz başarıyla güncellendi.";
                    // Reload project
                    $p_stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
                    $p_stmt->execute([$new_id]);
                    $project = $p_stmt->fetch();
                } catch(PDOException $e) {
                    $pdo->exec("SET foreign_key_checks = 1");
                    $error = "Güncelleme sırasında hata oluştu: " . $e->getMessage();
                }
            }

        } elseif ($project && isset($_POST['update_video'])) {
            if ($video_url) {
                $upd_vid = $pdo->prepare("UPDATE projects SET video_url = ? WHERE id = ?");
                if ($upd_vid->execute([$video_url, $project['id']])) {
                    $success = "Videonuz başarıyla güncellendi.";
                    $project['video_url'] = $video_url;
                } else {
                    $error = "Video veritabanına kaydedilirken hata oluştu.";
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8 relative z-10">
    <!-- Floating Background Decorator for Dashboard -->
    <div class="absolute -top-10 left-1/4 w-64 h-64 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 float-animation -z-10 dark:hidden"></div>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center glass-card p-8 rounded-3xl mb-8">
        <div>
            <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-800 to-purple-800 dark:from-indigo-400 dark:to-purple-400 tracking-tight mb-2">Öğrenci Paneli</h1>
            <p class="text-indigo-900/60 dark:text-indigo-300 font-medium">Hoş geldiniz. Projenizi buradan yönetebilirsiniz.</p>
        </div>
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

    <?php if($_SESSION['role'] === 'jury'): ?>
        <!-- Jüri Ekranı (Proje Ekleyemez) -->
        <div class="glass-card p-12 rounded-3xl text-center flex flex-col items-center justify-center">
            <i class="fa-solid fa-user-shield text-6xl text-indigo-200 dark:text-indigo-500 mb-4"></i>
            <h3 class="text-2xl font-bold text-indigo-900 dark:text-indigo-300 mb-2">Jüri Yetkisi</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Jüri üyeleri sisteme proje ekleyemez. Tüm projeleri yönetmek için Yönetici Paneline geçiş yapın.</p>
            <a href="admin.php" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-indigo-700 transition">Yönetici Paneline Git <i class="fa-solid fa-arrow-right ml-2"></i></a>
        </div>
    <?php elseif(!$project): ?>
        <!-- Proje Oluşturma Formu -->
        <div class="glass-card p-8 rounded-3xl shadow-sm relative overflow-hidden group">
            <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-700 to-purple-700 dark:from-indigo-400 dark:to-purple-400 mb-6 relative z-10"><i class="fa-solid fa-folder-plus mr-2 text-indigo-500"></i>Projenizi Oluşturun</h2>
            
            <form action="dashboard.php" method="POST" enctype="multipart/form-data" class="space-y-5 relative z-10">
                <input type="hidden" name="create_project" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-indigo-900 dark:text-indigo-300 font-bold mb-2 text-sm uppercase tracking-wide">Proje Adı</label>
                        <input type="text" name="project_name" required class="glass-input w-full px-4 py-3 rounded-xl focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-indigo-900 dark:text-indigo-300 font-bold mb-2 text-sm uppercase tracking-wide">Projeyi Yapanlar (Ekip Üyeleri)</label>
                        <div id="create-team-container" class="glass-input w-full px-2 py-2 rounded-xl flex flex-wrap gap-2 items-center cursor-text bg-white/50 dark:bg-gray-800/50" onclick="this.querySelector('input').focus()">
                            <input type="text" class="flex-1 min-w-[120px] bg-transparent outline-none border-none text-gray-800 dark:text-gray-200 placeholder-indigo-300 dark:placeholder-indigo-600 px-2 py-1" placeholder="İsim yazıp Enter'a basın">
                        </div>
                        <input type="hidden" name="team_members" id="create-team-hidden" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-indigo-900 dark:text-indigo-300 font-bold mb-2 text-sm uppercase tracking-wide">Özel Proje Kodu <span class="text-xs text-indigo-400 normal-case">(Opsiyonel)</span></label>
                        <input type="text" name="custom_id" placeholder="Örn: P-101 veya AKILLI-COP" class="glass-input w-full px-4 py-3 rounded-xl focus:outline-none">
                        <p class="text-xs text-indigo-400 mt-1">Boş bırakırsanız sistem kısa ve rastgele bir kod üretir.</p>
                    </div>
                    <div>
                        <label class="block text-indigo-900 dark:text-indigo-300 font-bold mb-2 text-sm uppercase tracking-wide">Proje Açıklaması</label>
                        <textarea name="description" required rows="2" class="glass-input w-full px-4 py-3 rounded-xl focus:outline-none resize-none"></textarea>
                    </div>
                </div>
                
                <div>
                    <label class="block text-indigo-900 dark:text-indigo-300 font-bold mb-2 text-sm uppercase tracking-wide">Proje Videosu (MP4 - Max 50MB)</label>
                    <input type="file" name="video" accept="video/mp4" required class="glass-input w-full px-4 py-3 rounded-xl file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/50 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800">
                </div>

                <button type="submit" class="w-full md:w-auto px-8 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-4 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-300 dark:shadow-none hover:shadow-xl hover:-translate-y-0.5 duration-300">
                    Projeyi Kaydet ve Videoyu Yükle <i class="fa-solid fa-upload ml-2"></i>
                </button>
            </form>
        </div>
    <?php else: ?>
        <!-- Proje Detayları ve Video Güncelleme -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-10 relative">
            <div class="glass-card p-8 rounded-3xl relative overflow-hidden">
                <h3 class="font-bold text-gray-800 dark:text-gray-100 border-b border-gray-200/50 dark:border-gray-700 pb-3 mb-6 relative z-10 text-xl">Proje Bilgilerini Düzenle</h3>
                <form action="dashboard.php" method="POST" class="space-y-4 relative z-10">
                    <input type="hidden" name="update_project_details" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-indigo-900 dark:text-indigo-300 font-bold mb-1 text-sm uppercase tracking-wide">Proje Kodu</label>
                            <input type="text" name="edit_id" value="<?php echo htmlspecialchars($project['id']); ?>" required class="glass-input w-full px-3 py-2 rounded-xl focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-indigo-900 dark:text-indigo-300 font-bold mb-1 text-sm uppercase tracking-wide">Proje Adı</label>
                            <input type="text" name="edit_name" value="<?php echo htmlspecialchars($project['name']); ?>" required class="glass-input w-full px-3 py-2 rounded-xl focus:outline-none">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-indigo-900 font-bold mb-1 text-sm uppercase tracking-wide">Ekip Üyeleri</label>
                        <div id="edit-team-container" class="glass-input w-full px-2 py-1.5 rounded-xl flex flex-wrap gap-2 items-center cursor-text bg-white/50" onclick="this.querySelector('input').focus()">
                            <input type="text" class="flex-1 min-w-[120px] bg-transparent outline-none border-none text-gray-800 placeholder-indigo-300 px-2" placeholder="İsim yazıp Enter'a basın">
                        </div>
                        <input type="hidden" name="edit_team" id="edit-team-hidden" required>
                    </div>
                    
                    <div>
                        <label class="block text-indigo-900 font-bold mb-1 text-sm uppercase tracking-wide">Açıklama</label>
                        <textarea name="edit_desc" required rows="3" class="glass-input w-full px-3 py-2 rounded-xl text-gray-800 focus:outline-none resize-none"><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold py-3 rounded-xl hover:from-emerald-600 hover:to-teal-600 transition shadow-md">
                        Bilgileri Güncelle <i class="fa-solid fa-save ml-1"></i>
                    </button>
                </form>
                
                <!-- QR Code Display -->
                <div class="mt-8 bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100 flex items-center gap-4">
                    <?php 
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
                        $base_dir = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
                        if($base_dir == '/') $base_dir = '';
                        $vote_url = $protocol . $_SERVER['HTTP_HOST'] . $base_dir . "/vote.php?id=" . urlencode($project['id']);
                        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($vote_url);
                    ?>
                    <div class="bg-white p-2 rounded-xl shadow-sm">
                        <img src="<?php echo $qr_url; ?>" alt="Proje QR Kodu" class="w-24 h-24 object-contain">
                    </div>
                    <div>
                        <h4 class="font-bold text-indigo-900 text-sm mb-1">Proje QR Kodunuz</h4>
                        <p class="text-xs text-indigo-700/70 mb-2">Bu kodu masanıza koyun, jüriler telefonlarıyla okutarak projenizi oylasın.</p>
                        <a href="<?php echo $qr_url; ?>" download="QR_<?php echo htmlspecialchars($project['id']); ?>.png" target="_blank" class="text-xs bg-indigo-200 text-indigo-800 font-bold px-3 py-1.5 rounded-lg hover:bg-indigo-300 transition">Kodu İndir</a>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t border-indigo-100/50">
                    <a href="vote.php?id=<?php echo urlencode($project['id']); ?>" class="inline-flex items-center text-indigo-600 font-bold hover:text-indigo-800 transition">
                        <i class="fa-solid fa-eye mr-2"></i> Proje Sayfasını Görüntüle
                    </a>
                </div>
            </div>

            <div class="glass-card p-8 rounded-3xl relative overflow-hidden">
                <h3 class="font-bold text-gray-800 mb-6 relative z-10 text-xl border-b border-gray-200/50 pb-3">Videoyu Güncelle</h3>
                
                <?php if($project['video_url']): ?>
                    <div class="mb-6 rounded-xl overflow-hidden aspect-video bg-black/80 flex items-center justify-center">
                        <video controls class="w-full h-full object-cover">
                            <source src="<?php echo htmlspecialchars($project['video_url']); ?>" type="video/mp4">
                        </video>
                    </div>
                <?php else: ?>
                    <div class="mb-6 bg-indigo-50 border-2 border-dashed border-indigo-200 rounded-xl aspect-video flex flex-col items-center justify-center text-indigo-400">
                        <i class="fa-solid fa-video-slash text-4xl mb-2 opacity-50"></i>
                        <span class="font-medium text-sm">Video Bulunmuyor</span>
                    </div>
                <?php endif; ?>

                <form action="dashboard.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="update_video" value="1">
                    <div>
                        <label class="block text-indigo-900 font-bold mb-2 text-sm uppercase tracking-wide">Yeni Video Seç (MP4 - Max 50MB)</label>
                        <input type="file" name="video" accept="video/mp4" required class="glass-input w-full px-4 py-2 rounded-xl text-gray-800 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-indigo-500 to-purple-500 text-white font-bold py-3 rounded-xl hover:from-indigo-600 hover:to-purple-600 transition shadow-md">
                        Videoyu Değiştir <i class="fa-solid fa-arrows-rotate ml-1"></i>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function initTagInput(containerId, hiddenInputId, initialTags = []) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const hiddenInput = document.getElementById(hiddenInputId);
    const input = container.querySelector('input');
    
    let tags = initialTags.map(t => t.trim()).filter(t => t !== '');

    function renderTags() {
        container.querySelectorAll('.tag-badge').forEach(el => el.remove());
        tags.forEach((tag, index) => {
            const tagEl = document.createElement('span');
            tagEl.className = 'tag-badge bg-indigo-100 text-indigo-700 px-2 py-1 rounded-md text-sm font-bold flex items-center gap-1 shadow-sm';
            tagEl.innerHTML = `${tag} <i class="fa-solid fa-xmark cursor-pointer hover:text-red-500" onclick="removeTag('${containerId}', ${index})"></i>`;
            container.insertBefore(tagEl, input);
        });
        hiddenInput.value = tags.join(', ');
    }

    window.removeTag = function(cId, index) {
        if(cId === containerId) {
            tags.splice(index, 1);
            renderTags();
        }
    }

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const val = input.value.trim().replace(/,/g, '');
            if (val) {
                tags.push(val);
                input.value = '';
                renderTags();
            }
        } else if (e.key === 'Backspace' && input.value === '') {
            tags.pop();
            renderTags();
        }
    });
    
    input.addEventListener('blur', function() {
        const val = input.value.trim().replace(/,/g, '');
        if (val) {
            tags.push(val);
            input.value = '';
            renderTags();
        }
    });

    // Enter'a basınca form submit olmasını engellemek için container içindeki keypress'i durdur
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });

    renderTags();
}

document.addEventListener('DOMContentLoaded', () => {
    // Proje oluşturma formu için
    initTagInput('create-team-container', 'create-team-hidden', []);
    
    // Proje düzenleme formu için (varsa)
    <?php if($project): ?>
    const existingTags = <?php echo json_encode(explode(',', $project['team_members'] ?? '')); ?>;
    initTagInput('edit-team-container', 'edit-team-hidden', existingTags);
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>
