<?php
require_once '../connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// ---- DELETE SLIDE ----
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image_path FROM hero_slides WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $slide = $result->fetch_assoc();
        $imagePath = '../' . $slide['image_path'];
        
        $deleteStmt = $conn->prepare("DELETE FROM hero_slides WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        
        if ($deleteStmt->execute()) {
            if (file_exists($imagePath)) unlink($imagePath);
            $message = 'Slide deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting slide.';
            $messageType = 'error';
        }
        $deleteStmt->close();
    }
    $stmt->close();
}

// ---- UPLOAD NEW SLIDE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please select a valid image.';
        $messageType = 'error';
    } else {
        $file = $_FILES['image'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, ALLOWED_TYPES)) {
            $message = 'Invalid file type.';
            $messageType = 'error';
        } else {
            $filename = 'slide_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $destPath = UPLOAD_DIR . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                resizeImage($destPath, $destPath, 1920, 1080); // Higher res for hero
                $imagePath = UPLOAD_URL . $filename;
                
                $stmt = $conn->prepare("INSERT INTO hero_slides (image_path, title, subtitle, sort_order) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $imagePath, $title, $subtitle, $sort_order);
                
                if ($stmt->execute()) {
                    $message = 'Slide added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Database error: ' . $conn->error;
                    $messageType = 'error';
                }
                $stmt->close();
            }
        }
    }
}

$slides = $conn->query("SELECT * FROM hero_slides ORDER BY sort_order ASC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hero Slideshow - Seva Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bhagwa: '#E87A20',
                        slate: { 50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1', 400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155', 800: '#1e293b', 900: '#0f172a' },
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #E87A20; border-radius: 3px; }

        .sidebar { width: 260px; position: fixed; top: 0; bottom: 0; left: 0; background: #ffffff; border-right: 1px solid #e2e8f0; z-index: 50; }
        .main-content { margin-left: 260px; }
        
        .sidebar-link { 
            display: flex; align-items: center; gap: 12px; 
            padding: 12px 20px; 
            color: #64748b; 
            font-size: 0.875rem; font-weight: 500;
            border-radius: 12px; margin: 2px 12px; 
            text-decoration: none; transition: 0.3s; 
        }
        .sidebar-link:hover { background: #f1f5f9; color: #0f172a; }
        .sidebar-link.active { background: rgba(232,122,32,0.1); color: #E87A20; border: 1px solid rgba(232,122,32,0.15); }

        .glass-card { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-radius: 16px; }
        .form-input { 
            background: #ffffff; border: 1px solid #e2e8f0; color: #0f172a; 
            padding: 12px; border-radius: 12px; width: 100%; transition: 0.3s;
        }
        .form-input:focus { outline: none; border-color: #E87A20; box-shadow: 0 0 0 3px rgba(232,122,32,0.1); }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="p-6 border-b border-slate-100 flex items-center gap-3">
        <div class="w-10 h-10 bg-bhagwa rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
            <i data-lucide="hard-hat" class="w-5 h-5 text-white"></i>
        </div>
        <div>
            <h1 class="text-base font-bold text-slate-900" style="font-family:'Outfit',sans-serif;">SEVA</h1>
            <p class="text-[9px] text-bhagwa tracking-[2px] -mt-1 font-bold">ADMIN PANEL</p>
        </div>
    </div>
    <div class="py-4">
        <a href="dashboard.php" class="sidebar-link"><i data-lucide="layout-dashboard" class="w-4 h-4"></i> Construction</a>
        <a href="slideshow.php" class="sidebar-link active"><i data-lucide="image" class="w-4 h-4"></i> Hero Slideshow</a>
        <a href="appearance.php" class="sidebar-link"><i data-lucide="palette" class="w-4 h-4"></i> Appearance</a>
        <a href="../index.php" target="_blank" class="sidebar-link"><i data-lucide="external-link" class="w-4 h-4"></i> View Website</a>
    </div>

    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-100">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="user" class="w-4 h-4 text-slate-500"></i>
                </div>
                <span class="text-sm text-slate-700 font-medium"><?= e($_SESSION['admin_username']) ?></span>
            </div>
            <a href="logout.php" class="text-slate-400 hover:text-red-500 transition-colors" title="Logout">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</aside>

<main class="main-content min-h-screen">
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-xl border-b border-slate-200 px-8 py-4 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-bold text-slate-900" style="font-family:'Outfit',sans-serif;">Hero Slideshow</h2>
            <p class="text-xs text-slate-400">Manage rotating banners on your homepage</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="../index.php" target="_blank" class="hidden sm:inline-flex items-center gap-2 text-slate-500 hover:text-bhagwa text-sm border border-slate-200 px-4 py-2 rounded-xl transition-all no-underline hover:border-bhagwa/30">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                View Site
            </a>
            <a href="logout.php" class="inline-flex items-center gap-2 text-slate-500 hover:text-red-500 text-sm px-4 py-2 rounded-xl transition-all no-underline border border-slate-200 hover:border-red-200 hover:bg-red-50">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </header>

    <div class="p-8 grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1">
            <div class="glass-card p-6 sticky top-24">
                <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2" style="font-family:'Outfit',sans-serif;">
                    <i data-lucide="plus" class="text-bhagwa"></i> Add New Slide
                </h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="upload">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Slide Image *</label>
                        <input type="file" name="image" class="form-input" required>
                        <p class="text-[10px] text-slate-400 mt-1">Recommended: 1920x1080px</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Main Title</label>
                        <input type="text" name="title" class="form-input" placeholder="e.g. Expert Masonry Work">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Subtitle</label>
                        <input type="text" name="subtitle" class="form-input" placeholder="e.g. Building your dream home">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Sort Order</label>
                        <input type="number" name="sort_order" class="form-input" value="0">
                    </div>
                    <button type="submit" class="w-full bg-bhagwa hover:bg-bhagwa/90 text-white py-3 rounded-xl font-bold transition-all shadow-lg shadow-orange-500/20">Upload Slide</button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-red-50 text-red-600 border border-red-100' ?>">
                    <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5"></i>
                    <span class="text-sm font-medium"><?= e($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($slides->num_rows > 0): ?>
            <div class="grid sm:grid-cols-2 gap-4">
                <?php while ($slide = $slides->fetch_assoc()): ?>
                <div class="glass-card overflow-hidden group hover:border-bhagwa/30 transition-all">
                    <div class="relative h-48 sm:h-56">
                        <img src="../<?= e($slide['image_path']) ?>" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-bhagwa/10 opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-all flex items-center justify-center">
                            <button type="button" onclick="deleteSlide(<?= $slide['id'] ?>)" class="p-3 bg-white text-red-500 rounded-full hover:bg-red-50 transition-all shadow-xl pointer-events-auto cursor-pointer">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-4">
                        <h4 class="font-bold text-slate-900 truncate"><?= e($slide['title']) ?></h4>
                        <p class="text-xs text-slate-400 truncate"><?= e($slide['subtitle']) ?></p>
                        <div class="mt-3 flex items-center justify-between">
                             <div class="text-[10px] text-bhagwa font-bold uppercase tracking-widest bg-bhagwa/5 px-2 py-1 rounded">Order: <?= $slide['sort_order'] ?></div>
                             <button type="button" onclick="deleteSlide(<?= $slide['id'] ?>)" class="text-xs text-red-400 hover:text-red-600 font-medium flex items-center gap-1 transition-colors cursor-pointer bg-transparent border-none">
                                 <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                             </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="glass-card p-12 text-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="image" class="w-8 h-8 text-slate-300"></i>
                </div>
                <h4 class="text-slate-900 font-bold mb-1">No slides uploaded yet</h4>
                <p class="text-slate-400 text-sm">Upload a hero slide using the form on the left.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.4); backdrop-filter:blur(4px); justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:20px; padding:32px; max-width:380px; width:90%; text-align:center; box-shadow:0 25px 50px rgba(0,0,0,0.15); animation:modalIn 0.2s ease-out;">
        <div style="width:56px; height:56px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
        </div>
        <h3 style="font-family:'Outfit',sans-serif; font-size:18px; font-weight:700; color:#0f172a; margin-bottom:8px;">Delete Slide?</h3>
        <p style="font-size:14px; color:#64748b; margin-bottom:24px;">This action cannot be undone. The slide and its image will be permanently removed.</p>
        <div style="display:flex; gap:12px;">
            <button type="button" onclick="closeDeleteModal()" style="flex:1; padding:12px; border-radius:12px; border:1px solid #e2e8f0; background:#fff; color:#64748b; font-weight:600; font-size:14px; cursor:pointer; transition:0.2s;">Cancel</button>
            <button type="button" id="confirmDeleteBtn" style="flex:1; padding:12px; border-radius:12px; border:none; background:#ef4444; color:#fff; font-weight:600; font-size:14px; cursor:pointer; transition:0.2s;">Delete</button>
        </div>
    </div>
</div>

<style>
    @keyframes modalIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }
</style>

<script>
    lucide.createIcons();

    let deleteId = null;

    function deleteSlide(id) {
        deleteId = id;
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        deleteId = null;
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (deleteId) {
            window.location.href = 'slideshow.php?delete=' + deleteId;
        }
    });

    // Close modal on backdrop click
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
</script>
</body>
</html>
