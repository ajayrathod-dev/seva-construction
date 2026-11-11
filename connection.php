<?php
/**
 * Seva Construction - Database Connection
 * ========================================
 * Centralized database connection file.
 * Update credentials for your hosting environment.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'seva_construction');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    error_log("Database Connection Failed: " . $conn->connect_error);
    die("<div style='text-align:center;padding:50px;font-family:sans-serif;'>
        <h2 style='color:#d32f2f;'>Database Connection Error</h2>
        <p>Please check your database configuration in <code>connection.php</code></p>
    </div>");
}

// Set charset
$conn->set_charset("utf8mb4");

// Site configuration
define('SITE_NAME', 'Seva Construction');
define('SITE_PHONE', '7796774539');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('MAX_IMAGE_WIDTH', 1200);
define('MAX_IMAGE_HEIGHT', 900);
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Ensure uploads directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Resize image to max dimensions while maintaining aspect ratio
 */
function resizeImage($sourcePath, $destPath, $maxWidth = 1200, $maxHeight = 900) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) return false;

    $mime = $imageInfo['mime'];
    $origWidth = $imageInfo[0];
    $origHeight = $imageInfo[1];

    // Calculate new dimensions
    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
    
    // Only resize if image is larger than max dimensions
    if ($ratio >= 1) {
        // Image is already small enough, just copy
        if ($sourcePath !== $destPath) {
            copy($sourcePath, $destPath);
        }
        return true;
    }

    $newWidth = (int)($origWidth * $ratio);
    $newHeight = (int)($origHeight * $ratio);

    // Create source image
    switch ($mime) {
        case 'image/jpeg':
            $srcImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $srcImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            $srcImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    if (!$srcImage) return false;

    // Create destination image
    $destImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG
    if ($mime === 'image/png') {
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
    }

    // Resize
    imagecopyresampled($destImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

    // Save
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($destImage, $destPath, 85);
            break;
        case 'image/png':
            imagepng($destImage, $destPath, 8);
            break;
        case 'image/webp':
            imagewebp($destImage, $destPath, 85);
            break;
    }

    imagedestroy($srcImage);
    imagedestroy($destImage);

    return true;
}

/**
 * Generate a unique filename
 */
function generateFilename($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return 'project_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
}

/**
 * Sanitize output
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get a site setting by key
 */
function getSetting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        return $result->fetch_assoc()['setting_value'];
    }
    return $default;
}

/**
 * Update or insert a site setting
 */
function updateSetting($key, $value) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->bind_param("ss", $key, $value);
    return $stmt->execute();
}

/**
 * Get all hero slides
 */
function getHeroSlides() {
    global $conn;
    return $conn->query("SELECT * FROM hero_slides ORDER BY sort_order ASC, id DESC");
}
?>
