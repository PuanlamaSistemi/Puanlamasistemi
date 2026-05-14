<?php
require_once 'includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if(isset($_SESSION['role']) && $_SESSION['role'] !== 'jury') {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h2>Yetkiniz Yok</h2><p>Bu sayfayı sadece jüri üyeleri görüntüleyebilir.</p><a href='dashboard.php'>Geri Dön</a></div>");
}

$error = null;
$success = null;
$project = null;

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        die("Proje bulunamadı.");
    }
} else {
    die("Geçersiz istek.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $team_members = $_POST['team_members'];
    $description = $_POST['description'];
    
    $update = $pdo->prepare("UPDATE projects SET name = ?, team_members = ?, description = ? WHERE id = ?");
    if ($update->execute([$name, $team_members, $description, $project['id']])) {
        $success = "Proje bilgileri başarıyla güncellendi.";
        // Veriyi tekrar çek
        $stmt->execute([$project['id']]);
        $project = $stmt->fetch();
    } else {
        $error = "Güncelleme sırasında bir hata oluştu.";
    }
}

require_once 'includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-8 relative z-10">
    <div class="glass-card p-8 rounded-3xl shadow-sm relative overflow-hidden">
        <div class="flex justify-between items-center mb-6 border-b border-gray-200/50 pb-4">
            <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-700 to-purple-700 relative z-10"><i class="fa-solid fa-pen-to-square mr-2 text-indigo-500"></i> Proje Düzenle (<?php echo htmlspecialchars($project['id']); ?>)</h2>
            <a href="admin.php" class="text-indigo-600 font-bold hover:underline"><i class="fa-solid fa-arrow-left mr-1"></i> Panele Dön</a>
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

        <form action="admin_edit.php?id=<?php echo urlencode($project['id']); ?>" method="POST" class="space-y-5 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-indigo-900 font-bold mb-2 text-sm uppercase tracking-wide">Proje Adı</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" required class="glass-input w-full px-4 py-3 rounded-xl text-gray-800 placeholder-indigo-300 focus:outline-none">
                </div>
                <div>
                    <label class="block text-indigo-900 font-bold mb-2 text-sm uppercase tracking-wide">Ekip Üyeleri</label>
                    <div id="admin-team-container" class="glass-input w-full px-2 py-1.5 rounded-xl flex flex-wrap gap-2 items-center cursor-text bg-white/50" onclick="this.querySelector('input').focus()">
                        <input type="text" class="flex-1 min-w-[120px] bg-transparent outline-none border-none text-gray-800 placeholder-indigo-300 px-2" placeholder="İsim yazıp Enter'a basın">
                    </div>
                    <input type="hidden" name="team_members" id="admin-team-hidden" required>
                </div>
            </div>

            <div>
                <label class="block text-indigo-900 font-bold mb-2 text-sm uppercase tracking-wide">Proje Açıklaması</label>
                <textarea name="description" required rows="6" class="glass-input w-full px-4 py-3 rounded-xl text-gray-800 placeholder-indigo-300 focus:outline-none resize-none"><?php echo htmlspecialchars($project['description']); ?></textarea>
            </div>

            <button type="submit" class="w-full md:w-auto px-8 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-4 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-300 hover:shadow-xl hover:-translate-y-0.5 duration-300">
                Değişiklikleri Kaydet <i class="fa-solid fa-save ml-2"></i>
            </button>
        </form>
    </div>
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

    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });

    renderTags();
}

document.addEventListener('DOMContentLoaded', () => {
    const existingTags = <?php echo json_encode(explode(',', $project['team_members'] ?? '')); ?>;
    initTagInput('admin-team-container', 'admin-team-hidden', existingTags);
});
</script>

<?php require_once 'includes/footer.php'; ?>
