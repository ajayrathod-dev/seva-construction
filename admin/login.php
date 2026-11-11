<?php
require_once '../connection.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Seva Construction</title>
    <meta name="robots" content="noindex, nofollow">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 800: '#233162', 900: '#0f1b3d', 950: '#080e22' },
                        orange: { 400: '#ffa726', 500: '#E87A20', 600: '#E56A10' },
                    }
                }
            }
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            color: #0f172a;
        }
        .login-card {
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
        }
        .input-field {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #0f172a;
            transition: all 0.3s;
        }
        .input-field:focus {
            border-color: #E87A20;
            box-shadow: 0 0 0 3px rgba(232,122,32,0.1);
            outline: none;
            background: #ffffff;
        }
        .input-field::placeholder { color: #94a3b8; }
        .login-btn {
            background: #E87A20;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(232,122,32,0.4);
            background: #d45d00;
        }
        .login-btn:active { transform: translateY(0); }
    </style>
</head>
<body>
    <div class="relative z-10 w-full max-w-md mx-4">
        <!-- Back to site link -->
        <a href="../index.php" class="inline-flex items-center gap-2 text-slate-400 hover:text-bhagwa text-sm mb-8 transition-colors no-underline">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to Website
        </a>

        <div class="login-card rounded-2xl p-8 sm:p-10">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-8">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <i data-lucide="hard-hat" class="w-6 h-6 text-white"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white" style="font-family:'Outfit',sans-serif;">SEVA</h1>
                    <p class="text-[10px] text-orange-400 font-semibold tracking-[3px] -mt-1">ADMIN PANEL</p>
                </div>
            </div>

            <h2 class="text-2xl font-bold text-slate-900 mb-1" style="font-family:'Outfit',sans-serif;">Welcome Back</h2>
            <p class="text-slate-500 text-sm mb-8">Sign in to manage your construction showcase.</p>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0"></i>
                <span class="text-red-700 text-sm"><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="login-form">
                <div class="space-y-5">
                    <div>
                        <label class="block text-slate-700 text-sm font-medium mb-2" for="username">Username</label>
                        <div class="relative">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2"></i>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="input-field w-full pl-11 pr-4 py-3.5 rounded-xl text-sm"
                                placeholder="Enter your username"
                                value="<?= e($username ?? '') ?>"
                                required
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-700 text-sm font-medium mb-2" for="password">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password"
                                class="input-field w-full pl-11 pr-12 py-3.5 rounded-xl text-sm"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" id="toggle-password" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-bhagwa transition-colors">
                                <i data-lucide="eye" class="w-4 h-4" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-btn w-full py-3.5 rounded-xl text-white font-semibold text-sm flex items-center justify-center gap-2" id="login-submit">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        Sign In
                    </button>
                </div>
            </form>

            <p class="text-slate-400 text-xs text-center mt-8">
                Protected area. Unauthorized access is prohibited.
            </p>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Toggle password visibility
        const toggleBtn = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggleBtn.innerHTML = isPassword 
                ? '<i data-lucide="eye-off" class="w-4 h-4"></i>'
                : '<i data-lucide="eye" class="w-4 h-4"></i>';
            lucide.createIcons();
        });
    </script>
</body>
</html>
