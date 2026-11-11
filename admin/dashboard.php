<?php
require_once '../connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Handle actions
$message = '';
$messageType = '';

// ---- DELETE PROJECT ----
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get image path before deleting
    $stmt = $conn->prepare("SELECT image_path FROM Construction WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $project = $result->fetch_assoc();
        $imagePath = '../' . $project['image_path'];
        
        // Delete from database
        $deleteStmt = $conn->prepare("DELETE FROM Construction WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        
        if ($deleteStmt->execute()) {
            // Delete file from disk
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            $message = 'Construction project deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting construction project.';
            $messageType = 'error';
        }
        $deleteStmt->close();
    }
    $stmt->close();
}

// ---- UPLOAD NEW PROJECT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'upload') {
        $title = trim($_POST['title'] ?? '');
        $category = $_POST['category'] ?? 'Brickwork';
        $description = trim($_POST['description'] ?? '');
        
        if (empty($title)) {
            $message = 'Project title is required.';
            $messageType = 'error';
        } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $message = 'Please select a valid image to upload.';
            $messageType = 'error';
        } else {
            $file = $_FILES['image'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, ALLOWED_TYPES)) {
                $message = 'Invalid file type. Please upload JPG, PNG, or WebP images.';
                $messageType = 'error';
            } elseif ($file['size'] > MAX_FILE_SIZE) {
                $message = 'File size exceeds 5MB limit.';
                $messageType = 'error';
            } else {
                $filename = generateFilename($file['name']);
                $destPath = UPLOAD_DIR . $filename;
                
                // Move and resize the image
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    // Resize image
                    resizeImage($destPath, $destPath, MAX_IMAGE_WIDTH, MAX_IMAGE_HEIGHT);
                    
                    $imagePath = UPLOAD_URL . $filename;
                    
                    $stmt = $conn->prepare("INSERT INTO Construction (title, image_path, category, description) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $title, $imagePath, $category, $description);
                    
                    if ($stmt->execute()) {
                        $message = 'Construction project uploaded successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Database error: ' . $conn->error;
                        $messageType = 'error';
                        // Clean up file
                        if (file_exists($destPath)) unlink($destPath);
                    }
                    $stmt->close();
                } else {
                    $message = 'Failed to upload image. Check folder permissions.';
                    $messageType = 'error';
                }
            }
        }
    }
    
    // ---- UPDATE PROJECT ----
    if ($_POST['action'] === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = $_POST['category'] ?? 'Brickwork';
        $description = trim($_POST['description'] ?? '');
        
        if (empty($title) || $id <= 0) {
            $message = 'Invalid data for update.';
            $messageType = 'error';
        } else {
            // Check if new image is uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $fileType = mime_content_type($file['tmp_name']);
                
                if (!in_array($fileType, ALLOWED_TYPES)) {
                    $message = 'Invalid file type.';
                    $messageType = 'error';
                } elseif ($file['size'] > MAX_FILE_SIZE) {
                    $message = 'File size exceeds 5MB limit.';
                    $messageType = 'error';
                } else {
                    // Get old image path
                    $oldStmt = $conn->prepare("SELECT image_path FROM Construction WHERE id = ?");
                    $oldStmt->bind_param("i", $id);
                    $oldStmt->execute();
                    $oldResult = $oldStmt->get_result();
                    $oldProject = $oldResult->fetch_assoc();
                    $oldStmt->close();
                    
                    $filename = generateFilename($file['name']);
                    $destPath = UPLOAD_DIR . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        resizeImage($destPath, $destPath, MAX_IMAGE_WIDTH, MAX_IMAGE_HEIGHT);
                        $imagePath = UPLOAD_URL . $filename;
                        
                        $stmt = $conn->prepare("UPDATE Construction SET title = ?, image_path = ?, category = ?, description = ? WHERE id = ?");
                        $stmt->bind_param("ssssi", $title, $imagePath, $category, $description, $id);
                        
                        if ($stmt->execute()) {
                            // Delete old image
                            $oldPath = '../' . $oldProject['image_path'];
                            if (file_exists($oldPath)) unlink($oldPath);
                            
                            $message = 'Construction project updated successfully!';
                            $messageType = 'success';
                        } else {
                            $message = 'Error updating construction project.';
                            $messageType = 'error';
                        }
                        $stmt->close();
                    }
                }
            } else {
                // Update without changing image
                $stmt = $conn->prepare("UPDATE Construction SET title = ?, category = ?, description = ? WHERE id = ?");
                $stmt->bind_param("sssi", $title, $category, $description, $id);
                
                if ($stmt->execute()) {
                    $message = 'Construction project updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating construction project.';
                    $messageType = 'error';
                }
                $stmt->close();
            }
        }
    }
}

// ---- FETCH ALL Construction ----
$filter = isset($_GET['filter']) ? $conn->real_escape_string($_GET['filter']) : '';
if ($filter && $filter !== 'All') {
    $projectsStmt = $conn->prepare("SELECT * FROM Construction WHERE category = ? ORDER BY created_at DESC");
    $projectsStmt->bind_param("s", $filter);
} else {
    $projectsStmt = $conn->prepare("SELECT * FROM Construction ORDER BY created_at DESC");
}
$projectsStmt->execute();
$allProjects = $projectsStmt->get_result();

// Stats
$totalProjects = $conn->query("SELECT COUNT(*) as total FROM Construction")->fetch_assoc()['total'];
$totalCategories = $conn->query("SELECT COUNT(DISTINCT category) as total FROM Construction")->fetch_assoc()['total'];

// Get edit data if editing
$editProject = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editStmt = $conn->prepare("SELECT * FROM Construction WHERE id = ?");
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $editResult = $editStmt->get_result();
    if ($editResult->num_rows === 1) {
        $editProject = $editResult->fetch_assoc();
    }
    $editStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Seva Construction Admin</title>
    <meta name="robots" content="noindex, nofollow">
    
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
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #E87A20; border-radius: 3px; }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            z-index: 50;
            transition: transform 0.3s;
        }
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0 !important; }
        }
        .main-content { margin-left: 260px; }

        .sidebar-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 20px;
            color: #64748b;
            font-size: 0.875rem; font-weight: 500;
            border-radius: 12px;
            margin: 2px 12px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .sidebar-link:hover { background: #f1f5f9; color: #0f172a; }
        .sidebar-link.active {
            background: rgba(232,122,32,0.1);
            color: #E87A20;
            border: 1px solid rgba(232,122,32,0.15);
        }

        /* Cards */
        .glass-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
        }

        .stat-card-admin {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s;
        }
        .stat-card-admin:hover {
            border-color: #E87A20;
            transform: translateY(-2px);
        }

        /* Form */
        .form-input {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #0f172a;
            transition: all 0.3s;
            font-size: 0.875rem;
        }
        .form-input:focus {
            border-color: #E87A20;
            box-shadow: 0 0 0 3px rgba(232,122,32,0.1);
            outline: none;
        }
        .form-input::placeholder { color: #94a3b8; }

        select.form-input option {
            background: #ffffff;
            color: #0f172a;
        }

        /* Upload area */
        .upload-area {
            border: 2px dashed #e2e8f0;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        .upload-area:hover, .upload-area.dragover {
            border-color: #E87A20;
            background: rgba(232,122,32,0.05);
        }
        .upload-area input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer;
        }

        /* Table */
        .project-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .project-table th {
            text-align: left;
            padding: 12px 16px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .project-table td {
            padding: 12px 16px;
            font-size: 0.875rem;
            color: #334155;
            vertical-align: middle;
        }
        .project-table tbody tr {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        .project-table tbody tr:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .project-table tbody tr td:first-child { border-radius: 12px 0 0 12px; border-left: 1px solid #e2e8f0; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
        .project-table tbody tr td:last-child { border-radius: 0 12px 12px 0; border-right: 1px solid #e2e8f0; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }

        /* Action buttons */
        .btn-action {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-edit {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        .btn-edit:hover { background: #dbeafe; }
        .btn-delete {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .btn-delete:hover { background: #fee2e2; }

        .submit-btn {
            background: #E87A20;
            color: #fff;
            transition: all 0.3s;
        }
        .submit-btn:hover {
            background: #d45d00;
            transform: translateY(-1px);
            box-shadow: 0 8px 30px rgba(232,122,32,0.4);
        }

        /* Category badge */
        .cat-badge-sm {
            display: inline-flex; align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .cat-Brickwork { background: rgba(34,197,94,0.1); color: #16a34a; }
        .cat-Plastering { background: rgba(59,130,246,0.1); color: #2563eb; }
        .cat-House-Design { background: rgba(245,158,11,0.1); color: #d97706; }
        .cat-Full-Construction { background: rgba(168,85,247,0.1); color: #9333ea; }
        .cat-Renovation { background: rgba(236,72,153,0.1); color: #db2777; }

        /* Toast animation */
        .toast-enter { animation: toastIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes toastIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Mobile overlay */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 40;
        }
        .sidebar-overlay.active { display: block; }

        /* Image preview in table */
        .table-thumb {
            width: 64px; height: 48px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid rgba(255,255,255,0.05);
        }

        /* Mobile card view */
        .mobile-project-card {
            display: none;
        }
        @media (max-width: 768px) {
            .project-table { display: none; }
            .mobile-project-card { display: block; }
        }
    </style>
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- ============ SIDEBAR ============ -->
<aside class="sidebar" id="sidebar">
    <div class="p-6 border-b border-slate-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-bhagwa rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                <i data-lucide="hard-hat" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <h1 class="text-base font-bold text-slate-900" style="font-family:'Outfit',sans-serif;">SEVA</h1>
                <p class="text-[9px] text-bhagwa tracking-[2px] -mt-1 font-bold">ADMIN PANEL</p>
            </div>
        </div>
    </div>

    <div class="py-4">
        <p class="px-8 py-2 text-[10px] text-slate-400 font-semibold uppercase tracking-widest">Menu</p>
        <a href="dashboard.php" class="sidebar-link active">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
            Construction
        </a>
        <a href="slideshow.php" class="sidebar-link">
            <i data-lucide="image" class="w-4 h-4"></i>
            Hero Slideshow
        </a>
        <a href="appearance.php" class="sidebar-link">
            <i data-lucide="palette" class="w-4 h-4"></i>
            Appearance
        </a>
        <a href="../index.php" target="_blank" class="sidebar-link">
            <i data-lucide="external-link" class="w-4 h-4"></i>
            View Website
        </a>
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

<!-- ============ MAIN CONTENT ============ -->
<main class="main-content min-h-screen">
    <!-- Top Bar -->
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-xl border-b border-slate-200 px-4 sm:px-8 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-slate-600 hover:text-bhagwa" id="sidebar-toggle">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div>
                    <h2 class="text-lg font-bold text-slate-900" style="font-family:'Outfit',sans-serif;">Dashboard</h2>
                    <p class="text-xs text-slate-400">Manage your construction showcase</p>
                </div>
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
        </div>
    </header>

    <div class="p-4 sm:p-8">
        <!-- Toast Message -->
        <?php if ($message): ?>
        <div class="toast-enter mb-6 p-4 rounded-xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 border border-emerald-100' : 'bg-red-50 border border-red-100' ?>">
            <i data-lucide="<?= $messageType === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-5 h-5 <?= $messageType === 'success' ? 'text-emerald-500' : 'text-red-500' ?> flex-shrink-0"></i>
            <span class="text-sm <?= $messageType === 'success' ? 'text-emerald-800' : 'text-red-800' ?>"><?= e($message) ?></span>
        </div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="stat-card-admin">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-bhagwa/10 rounded-xl flex items-center justify-center">
                        <i data-lucide="image" class="w-5 h-5 text-bhagwa"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-900" style="font-family:'Outfit',sans-serif;"><?= $totalProjects ?></p>
                <p class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Total Projects</p>
            </div>
            <div class="stat-card-admin">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="layers" class="w-5 h-5 text-blue-500"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-900" style="font-family:'Outfit',sans-serif;"><?= $totalCategories ?></p>
                <p class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Categories</p>
            </div>
            <div class="stat-card-admin">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="hard-drive" class="w-5 h-5 text-emerald-500"></i>
                    </div>
                </div>
                <?php
                $uploadSize = 0;
                $uploadDir = '../uploads/';
                if (is_dir($uploadDir)) {
                    foreach (glob($uploadDir . '*') as $file) {
                        if (is_file($file)) $uploadSize += filesize($file);
                    }
                }
                $sizeFormatted = $uploadSize < 1048576 
                    ? round($uploadSize / 1024, 1) . ' KB' 
                    : round($uploadSize / 1048576, 1) . ' MB';
                ?>
                <p class="text-2xl font-bold text-slate-900" style="font-family:'Outfit',sans-serif;"><?= $sizeFormatted ?></p>
                <p class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Storage Used</p>
            </div>
            <div class="stat-card-admin">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="calendar" class="w-5 h-5 text-purple-500"></i>
                    </div>
                </div>
                <?php
                $latestDate = $conn->query("SELECT MAX(created_at) as latest FROM Construction")->fetch_assoc()['latest'];
                $latestFormatted = $latestDate ? date('d M Y', strtotime($latestDate)) : 'N/A';
                ?>
                <p class="text-lg font-bold text-slate-900" style="font-family:'Outfit',sans-serif;"><?= $latestFormatted ?></p>
                <p class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Last Upload</p>
            </div>
        </div>

        <!-- Two Column Layout: Upload Form + Projects Table -->
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Upload / Edit Form -->
            <div class="lg:col-span-1">
                <div class="glass-card p-6 sticky top-24">
                    <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2" style="font-family:'Outfit',sans-serif;">
                        <i data-lucide="<?= $editProject ? 'edit-3' : 'upload' ?>" class="w-5 h-5 text-bhagwa"></i>
                        <?= $editProject ? 'Edit Construction' : 'Upload New Work' ?>
                    </h3>
                    
                    <?php if ($editProject): ?>
                    <a href="dashboard.php" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-bhagwa mb-4 no-underline transition-colors">
                        <i data-lucide="x" class="w-3 h-3"></i> Cancel editing
                    </a>
                    <?php endif; ?>

                    <form method="POST" action="dashboard.php" enctype="multipart/form-data" id="upload-form">
                        <input type="hidden" name="action" value="<?= $editProject ? 'update' : 'upload' ?>">
                        <?php if ($editProject): ?>
                        <input type="hidden" name="id" value="<?= $editProject['id'] ?>">
                        <?php endif; ?>

                        <div class="space-y-5">
                            <!-- Title -->
                            <div>
                                <label class="block text-slate-500 text-xs font-bold mb-2 uppercase tracking-wider" for="project-title">Title *</label>
                                <input 
                                    type="text" 
                                    id="project-title" 
                                    name="title" 
                                    class="form-input w-full px-4 py-3 rounded-xl"
                                    placeholder="e.g. Modern Villa Brickwork"
                                    value="<?= $editProject ? e($editProject['title']) : '' ?>"
                                    required
                                >
                            </div>

                            <!-- Category -->
                            <div>
                                <label class="block text-slate-500 text-xs font-bold mb-2 uppercase tracking-wider" for="project-category">Category *</label>
                                <select id="project-category" name="category" class="form-input w-full px-4 py-3 rounded-xl" required>
                                    <?php
                                    $cats = ['Brickwork', 'Plastering', 'House Design', 'Full Construction', 'Renovation'];
                                    foreach ($cats as $cat):
                                        $selected = ($editProject && $editProject['category'] === $cat) ? 'selected' : '';
                                    ?>
                                    <option value="<?= $cat ?>" <?= $selected ?>><?= $cat ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-slate-500 text-xs font-bold mb-2 uppercase tracking-wider" for="project-desc">Description</label>
                                <textarea 
                                    id="project-desc" 
                                    name="description" 
                                    class="form-input w-full px-4 py-3 rounded-xl resize-none" 
                                    rows="3"
                                    placeholder="Brief details about the work..."
                                ><?= $editProject ? e($editProject['description']) : '' ?></textarea>
                            </div>

                            <!-- Image Upload -->
                            <div>
                                <label class="block text-slate-500 text-xs font-bold mb-2 uppercase tracking-wider">
                                    Construction Image <?= $editProject ? '(keep empty if same)' : '*' ?>
                                </label>
                                
                                <?php if ($editProject && $editProject['image_path']): ?>
                                <div class="mb-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <img src="../<?= e($editProject['image_path']) ?>" alt="Current image" class="w-full h-32 object-cover rounded-lg">
                                    <p class="text-xs text-slate-400 mt-2 font-medium">Current construction image</p>
                                </div>
                                <?php endif; ?>

                                <div class="upload-area" id="upload-area">
                                    <input type="file" name="image" id="image-input" accept=".jpg,.jpeg,.png,.webp" <?= $editProject ? '' : 'required' ?>>
                                    <div id="upload-placeholder">
                                        <div class="w-12 h-12 bg-bhagwa/10 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <i data-lucide="cloud-upload" class="w-6 h-6 text-bhagwa"></i>
                                        </div>
                                        <p class="text-slate-600 text-sm font-bold">Drop image or browse</p>
                                        <p class="text-slate-400 text-[10px] mt-1 font-semibold">JPG, PNG, WebP — Max 5MB</p>
                                    </div>
                                    <div id="upload-preview" class="hidden">
                                        <img id="preview-image" class="max-h-32 mx-auto rounded-lg" alt="Preview">
                                        <p id="preview-name" class="text-bhagwa text-[10px] mt-2 font-bold"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <button type="submit" class="submit-btn w-full py-3.5 rounded-xl text-white font-semibold text-sm flex items-center justify-center gap-2" id="submit-btn">
                                <i data-lucide="<?= $editProject ? 'save' : 'plus' ?>" class="w-4 h-4"></i>
                                <?= $editProject ? 'Update Project' : 'Upload Project' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Projects Table -->
            <div class="lg:col-span-2">
                <div class="glass-card p-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2" style="font-family:'Outfit',sans-serif;">
                            <i data-lucide="folder-open" class="w-5 h-5 text-bhagwa"></i>
                            All Construction
                            <span class="text-xs text-slate-400 font-normal">(<?= $totalProjects ?> total)</span>
                        </h3>
                        
                        <!-- Filter dropdown -->
                        <select id="category-filter" class="form-input px-4 py-2 rounded-lg text-xs font-bold" onchange="window.location='dashboard.php?filter='+this.value">
                            <option value="All" <?= $filter === '' || $filter === 'All' ? 'selected' : '' ?>>All Categories</option>
                            <?php foreach ($cats as $c): ?>
                            <option value="<?= $c ?>" <?= $filter === $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($allProjects->num_rows > 0): ?>
                    <!-- Desktop Table -->
                    <div class="overflow-x-auto">
                        <table class="project-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($p = $allProjects->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="../<?= e($p['image_path']) ?>" alt="<?= e($p['title']) ?>" class="table-thumb"
                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2264%22 height=%2248%22 fill=%22%23f1f5f9%22%3E%3Crect width=%2264%22 height=%2248%22 rx=%228%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%23cbd5e1%22 font-size=%228%22%3ENo Img%3C/text%3E%3C/svg%3E';">
                                    </td>
                                    <td>
                                        <p class="text-slate-900 font-bold text-sm"><?= e($p['title']) ?></p>
                                        <?php if (!empty($p['description'])): ?>
                                        <p class="text-slate-500 text-xs mt-0.5 line-clamp-1 font-medium"><?= e(substr($p['description'], 0, 60)) ?>...</p>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="cat-badge-sm cat-<?= str_replace(' ', '-', e($p['category'])) ?>"><?= e($p['category']) ?></span>
                                    </td>
                                    <td class="text-slate-400 text-xs font-semibold">
                                        <?= date('d M Y', strtotime($p['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <a href="dashboard.php?edit=<?= $p['id'] ?>#upload-form" class="btn-action btn-edit">
                                                <i data-lucide="pencil" class="w-3 h-3"></i>
                                                Edit
                                            </a>
                                            <a href="dashboard.php?delete=<?= $p['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this project? This action cannot be undone.');">
                                                <i data-lucide="trash-2" class="w-3 h-3"></i>
                                                Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="mobile-project-card space-y-3">
                        <?php 
                        // Reset pointer
                        $allProjects->data_seek(0);
                        while ($p = $allProjects->fetch_assoc()): 
                        ?>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                            <div class="flex gap-4">
                                <img src="../<?= e($p['image_path']) ?>" alt="<?= e($p['title']) ?>" class="w-20 h-16 object-cover rounded-lg flex-shrink-0"
                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2264%22 fill=%22%23f1f5f9%22%3E%3Crect width=%2280%22 height=%2264%22 rx=%228%22/%3E%3C/svg%3E';">
                                <div class="flex-1 min-w-0">
                                    <p class="text-slate-900 font-bold text-sm truncate"><?= e($p['title']) ?></p>
                                    <span class="cat-badge-sm cat-<?= str_replace(' ', '-', e($p['category'])) ?> mt-1 font-bold"><?= e($p['category']) ?></span>
                                    <p class="text-slate-400 text-xs mt-1 font-semibold"><?= date('d M Y', strtotime($p['created_at'])) ?></p>
                                </div>
                            </div>
                            <div class="flex gap-2 mt-3">
                                <a href="dashboard.php?edit=<?= $p['id'] ?>#upload-form" class="btn-action btn-edit flex-1 justify-center">
                                    <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                                </a>
                                <a href="dashboard.php?delete=<?= $p['id'] ?>" class="btn-action btn-delete flex-1 justify-center" onclick="return confirm('Delete this construction piece?');">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>

                    <?php else: ?>
                    <div class="text-center py-16">
                        <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="image-off" class="w-8 h-8 text-white/15"></i>
                        </div>
                        <h4 class="text-white/40 font-semibold mb-1" style="font-family:'Outfit',sans-serif;">No Projects Found</h4>
                        <p class="text-white/20 text-sm">Upload your first project using the form on the left.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    lucide.createIcons();

    // ---- Sidebar Toggle (mobile) ----
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }

    // ---- Image Preview ----
    const imageInput = document.getElementById('image-input');
    const uploadPlaceholder = document.getElementById('upload-placeholder');
    const uploadPreview = document.getElementById('upload-preview');
    const previewImage = document.getElementById('preview-image');
    const previewName = document.getElementById('preview-name');
    const uploadArea = document.getElementById('upload-area');

    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewName.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                uploadPlaceholder.classList.add('hidden');
                uploadPreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });

    // Drag and drop
    ['dragenter', 'dragover'].forEach(evt => {
        uploadArea.addEventListener(evt, (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach(evt => {
        uploadArea.addEventListener(evt, (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
    });

    // ---- Auto-dismiss toast ----
    const toast = document.querySelector('.toast-enter');
    if (toast) {
        setTimeout(() => {
            toast.style.transition = 'all 0.4s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }
</script>
</body>
</html>
