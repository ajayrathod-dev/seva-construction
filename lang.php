<?php
/**
 * Seva Construction - Language Loader
 * =====================================
 * Handles language detection, cookie persistence, and translation.
 * 
 * Priority: URL param (?lang=xx) → Cookie → Default (en)
 * Cookie: seva_lang, expires in 365 days
 */

// Supported languages
$supported_languages = ['en', 'hi', 'mr'];
$default_language = 'en';

// 1. Check for language change via URL parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], $supported_languages)) {
    $current_lang = $_GET['lang'];
    // Set cookie for 365 days
    setcookie('seva_lang', $current_lang, time() + (365 * 24 * 60 * 60), '/');
} 
// 2. Check cookie
elseif (isset($_COOKIE['seva_lang']) && in_array($_COOKIE['seva_lang'], $supported_languages)) {
    $current_lang = $_COOKIE['seva_lang'];
} 
// 3. Default
else {
    $current_lang = $default_language;
}

// Load the language file
$lang_file = __DIR__ . '/lang/' . $current_lang . '.php';
if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    // Fallback to English
    $current_lang = 'en';
    require_once __DIR__ . '/lang/en.php';
}

/**
 * Get translated text by key
 * 
 * @param string $key The translation key
 * @param array $replacements Optional associative array of replacements (e.g., ['%year%' => '2026'])
 * @return string The translated text, or the key itself if not found
 */
function t($key, $replacements = []) {
    global $lang;
    $text = $lang[$key] ?? $key;
    
    // Apply replacements
    if (!empty($replacements)) {
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    return $text;
}

/**
 * Get all available languages with their metadata
 * 
 * @return array List of languages with code, name, native name, and flag
 */
function getAvailableLanguages() {
    return [
        'en' => ['name' => 'English',  'native' => 'English', 'flag' => '🇬🇧'],
        'hi' => ['name' => 'Hindi',    'native' => 'हिंदी',   'flag' => '🇮🇳'],
        'mr' => ['name' => 'Marathi',  'native' => 'मराठी',   'flag' => '🇮🇳'],
    ];
}

/**
 * Build a URL for language switching, preserving existing query params
 * 
 * @param string $langCode The target language code
 * @return string The URL with lang parameter
 */
function langSwitchUrl($langCode) {
    $params = $_GET;
    $params['lang'] = $langCode;
    return '?' . http_build_query($params);
}
?>
