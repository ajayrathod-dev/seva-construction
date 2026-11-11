<?php
require_once '../connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// ---- UPDATE BACKGROUND IMAGE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_bg') {
    if (isset($_FILES['bg_image']) && $_FILES['bg_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['bg_image'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, ALLOWED_TYPES)) {
            $message = 'Invalid file type.';
            $messageType = 'error';
        } else {
            $filename = 'bg_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $destPath = UPLOAD_DIR . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                // Get old image path to delete
                $oldBg = getSetting('bg_image', 'uploads/default_bg.jpg');
                if ($oldBg !== 'uploads/default_bg.jpg' && file_exists('../' . $oldBg)) {
                    unlink('../' . $oldBg);
                }
                
                $imagePath = UPLOAD_URL . $filename;
                updateSetting('bg_image', $imagePath);
                
                $message = 'Background image updated successfully!';
                $messageType = 'success';
            }
        }
    }
}

$currentBg = getSetting('bg_image', 'uploads/default_bg.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appearance Settings - Seva Admin</title>
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
        <a href="slideshow.php" class="sidebar-link"><i data-lucide="image" class="w-4 h-4"></i> Hero Slideshow</a>
        <a href="appearance.php" class="sidebar-link active"><i data-lucide="palette" class="w-4 h-4"></i> Appearance</a>
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
            <h2 class="text-lg font-bold text-slate-900" style="font-family:'Outfit',sans-serif;">Appearance Setting</h2>
            <p class="text-xs text-slate-400">Customize the overall look and theme of your website</p>
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

    <div class="p-8">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-red-50 text-red-600 border border-red-100' ?>">
                <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5"></i>
                <span class="text-sm font-medium"><?= e($message) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-2 gap-8">
            <div class="glass-card p-8">
                <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i data-lucide="image" class="text-bhagwa"></i> Global Background Image
                </h3>
                <p class="text-slate-500 text-sm mb-6">This image appears faintly in the background of all pages to provide a unique Maratha construction vibe.</p>
                
                <div class="mb-8 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <img src="../<?= e($currentBg) ?>" class="w-full h-48 object-cover rounded-xl border border-slate-200 shadow-sm" onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22200%22><rect fill=%22%23f1f5f9%22 width=%22400%22 height=%22200%22/><text fill=%22%2394a3b8%22 font-family=%22sans-serif%22 font-size=%2214%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22>No Background Image</text></svg>';">
                    <p class="text-[10px] text-center text-slate-400 mt-4 uppercase tracking-widest font-bold">Current Background Image</p>
                </div>

                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="update_bg">
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 font-bold mb-2">Upload New Image</label>
                        <input type="file" name="bg_image" class="form-input" required>
                    </div>
                    <button type="submit" class="w-full bg-bhagwa hover:bg-bhagwa/90 text-white py-4 rounded-xl font-bold transition-all shadow-lg shadow-orange-500/20 flex items-center justify-center gap-2">
                        <i data-lucide="upload-cloud" class="w-5 h-5"></i> Update Background
                    </button>
                </form>
            </div>

            <div class="glass-card p-8">
                <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i data-lucide="palette" class="text-bhagwa"></i> Design Theme Overview
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-sm font-bold text-slate-700">Primary Base</span>
                        <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-white border border-slate-200"></span><span class="text-xs text-slate-400 font-medium">Pure White</span></div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-sm font-bold text-slate-700">Action Buttons</span>
                        <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-[#E87A20]"></span><span class="text-xs text-slate-400 font-medium">Bhagwa Orange</span></div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-sm font-bold text-slate-700">WhatsApp Call</span>
                        <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-[#25D366]"></span><span class="text-xs text-slate-400 font-medium">Success Green</span></div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-sm font-bold text-slate-700">Phone / Primary Link</span>
                        <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-blue-500"></span><span class="text-xs text-slate-400 font-medium">Royal Blue</span></div>
                    </div>
                </div>
                <div class="mt-8 p-5 border border-bhagwa/20 bg-bhagwa/5 rounded-2xl">
                    <p class="text-sm text-bhagwa font-bold flex items-center gap-2"><i data-lucide="info" class="w-4 h-4"></i> Appearance Note:</p>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed font-medium">The Maratha vibe is achieved through the use of Bhagwa colors and bold typography. These settings are pre-configured to match the professional construction theme.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>

