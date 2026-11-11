<?php
require_once 'connection.php';
require_once 'lang.php';

// Fetch Construction from database
$category_filter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

if ($category_filter && $category_filter !== 'All') {
    $stmt = $conn->prepare("SELECT * FROM Construction WHERE category = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $category_filter);
} else {
    $stmt = $conn->prepare("SELECT * FROM Construction ORDER BY created_at DESC");
}
$stmt->execute();
$Construction = $stmt->get_result();

// Get categories
$categories_result = $conn->query("SELECT DISTINCT category FROM Construction ORDER BY category");
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat['category'];
}

// Fetch Slideshow and Settings
$hero_slides = getHeroSlides();
$bg_image = getSetting('bg_image', 'uploads/default_bg.jpg');
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seva Construction - Expert Mason | Brickwork, Plastering & House Design</title>
    <meta name="description" content="Seva Construction - Expert masonry services specializing in brickwork, plastering, and house design. Building beautiful homes brick by brick. Call 7796774539 for a free quote.">
    <meta name="keywords" content="mason, brickwork, plastering, house construction, house design, wall construction, plaster work, brick laying, home building">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#ffffff',
                        bhagwa: '#E87A20',
                        maratha: '#ff8c00',
                        success: '#25D366',
                        royal: '#3b82f6', // Bright Blue
                        dark: '#0f172a',
                    },
                    fontFamily: {
                        heading: ['Outfit', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&family=Noto+Sans+Devanagari:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Glide.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.2.0/css/glide.core.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.2.0/css/glide.theme.min.css">
    
    <!-- GLightbox CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #ffffff; color: #0f172a; position: relative; }
        
        /* Global Background Image (Transparent) */
        .site-bg-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('<?= e($bg_image) ?>');
            background-size: cover;
            background-position: center;
            opacity: 0.08; /* Transparent construction image */
            z-index: -1;
            pointer-events: none;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #E87A20; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #d45d00; }

        /* Header & Main Navigation Layout */
        .header-container {
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 15px 0;
            transition: all 0.3s;
        }

        /* Hero Slider Refinement */
        .hero-slider-container {
            position: relative;
            width: 100%;
            background: #f8fafc;
            overflow: hidden;
            margin-bottom: 4rem; /* space before next section */
        }
        .hero-slider-container .glide__arrows {
            overflow: visible;
        }
        .hero-slider-container .glide__arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 30;
        }
        .hero-slider-container .glide__arrow--left { left: 24px; }
        .hero-slider-container .glide__arrow--right { right: 24px; }
        .hero-slide-img {
            width: 100%;
            display: block; /* prevent inline gaps */
            height: auto;
            aspect-ratio: 21 / 9;
            object-fit: cover;
            min-height: 450px;
        }
        @media (max-width: 1024px) {
            .hero-slide-img { aspect-ratio: 16 / 9; min-height: 400px; }
        }
        @media (max-width: 768px) {
            .hero-slide-img { aspect-ratio: 4 / 3; min-height: 350px; }
        }

        /* Gallery */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
        }
        @media (max-width: 768px) {
            .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        }

        .project-card {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(145deg, #111b38, #0d1530);
            border: 1px solid rgba(255,255,255,0.06);
            transition: all 0.5s cubic-bezier(0.23, 1, 0.320, 1);
            cursor: pointer;
        }
        .project-card:hover {
            transform: translateY(-8px);
            border-color: rgba(232,122,32,0.3);
            box-shadow: 0 20px 60px rgba(0,0,0,0.4), 0 0 40px rgba(232,122,32,0.1);
        }
        .project-card .card-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.23, 1, 0.320, 1);
        }
        .project-card:hover .card-image {
            transform: scale(1.05);
        }
        .project-card .card-overlay {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: linear-gradient(transparent, rgba(8,14,34,0.95));
            padding: 40px 20px 20px;
            transition: all 0.4s;
        }
        .project-card:hover .card-overlay {
            background: linear-gradient(transparent, rgba(8,14,34,1));
        }
        .project-card .zoom-icon {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) scale(0);
            width: 56px; height: 56px;
            background: rgba(232,122,32,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            backdrop-filter: blur(10px);
        }
        .project-card:hover .zoom-icon {
            transform: translate(-50%, -50%) scale(1);
        }

        /* Category Badge */
        .cat-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            background: rgba(232,122,32,0.15);
            color: #ffb74d;
            border: 1px solid rgba(232,122,32,0.2);
        }

        /* Filter Tabs */
        .filter-tab {
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.03);
            color: rgba(255,255,255,0.6);
            white-space: nowrap;
        }
        .filter-tab:hover {
            background: rgba(232,122,32,0.1);
            color: #ffb74d;
            border-color: rgba(232,122,32,0.3);
        }
        .filter-tab.active {
            background: linear-gradient(135deg, #E87A20, #E56A10);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 20px rgba(232,122,32,0.4);
        }

        /* Stats Counter */
        .stat-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
            border: 1px solid rgba(255,255,255,0.06);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 32px;
            text-align: center;
            transition: all 0.4s;
        }
        .stat-card:hover {
            border-color: rgba(232,122,32,0.3);
            transform: translateY(-4px);
        }

        /* Section heading */
        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            background: rgba(232,122,32,0.1);
            color: #E87A20;
            border: 1px solid rgba(232,122,32,0.2);
            margin-bottom: 16px;
        }

        /* Navbar */
        .nav-link {
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            transition: all 0.3s;
            text-decoration: none;
        }
        .nav-link:hover, .nav-link.active {
            color: #E87A20;
        }

        /* Footer */
        .footer-gradient {
            background: linear-gradient(180deg, #080e22 0%, #0a1229 50%, #0d1633 100%);
        }

        /* Reveal animation */
        .reveal {
            opacity: 1;
            transform: translateY(0);
            transition: all 0.8s cubic-bezier(0.23, 1, 0.320, 1);
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Noise texture overlay */
        .noise-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            z-index: 9999;
            opacity: 0.015;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
        }

        /* Mobile menu */
        .mobile-menu {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(8,14,34,0.98);
            backdrop-filter: blur(20px);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: all 0.4s;
        }
        .mobile-menu.active {
            opacity: 1;
            pointer-events: auto;
        }

        /* Parallax */
        .parallax-slow { will-change: transform; }

        /* Pulse ring animation on phone icon */
        .pulse-ring {
            animation: pulseRing 2s ease-out infinite;
        }
        @keyframes pulseRing {
            0% { box-shadow: 0 0 0 0 rgba(232,122,32,0.6); }
            70% { box-shadow: 0 0 0 15px rgba(232,122,32,0); }
            100% { box-shadow: 0 0 0 0 rgba(232,122,32,0); }
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        /* Devanagari font for Hindi/Marathi */
        html[lang="hi"] body,
        html[lang="mr"] body {
            font-family: 'Noto Sans Devanagari', 'Inter', sans-serif;
        }

        /* Language Switcher */
        .lang-switcher {
            position: relative;
        }
        .lang-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }
        .lang-btn:hover {
            border-color: #E87A20;
            color: #E87A20;
            background: rgba(232,122,32,0.05);
        }
        .lang-btn .globe-icon {
            width: 16px;
            height: 16px;
        }
        .lang-btn .chevron-icon {
            width: 14px;
            height: 14px;
            transition: transform 0.3s;
        }
        .lang-switcher.open .lang-btn .chevron-icon {
            transform: rotate(180deg);
        }
        .lang-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 180px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12), 0 4px 12px rgba(0,0,0,0.06);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px) scale(0.96);
            transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1001;
        }
        .lang-switcher.open .lang-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        .lang-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #475569;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .lang-option:hover {
            background: #f8fafc;
            color: #0f172a;
        }
        .lang-option.active {
            background: rgba(232,122,32,0.08);
            color: #E87A20;
            font-weight: 700;
        }
        .lang-option .lang-flag {
            font-size: 1.2rem;
            line-height: 1;
        }
        .lang-option .lang-label {
            flex: 1;
        }
        .lang-option .lang-native {
            font-size: 0.7rem;
            color: #94a3b8;
        }
        .lang-option.active .lang-native {
            color: rgba(232,122,32,0.6);
        }
        .lang-option .lang-check {
            width: 16px;
            height: 16px;
            opacity: 0;
        }
        .lang-option.active .lang-check {
            opacity: 1;
            color: #E87A20;
        }

        /* Mobile lang switcher */
        .mobile-lang-options {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        .mobile-lang-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgba(255,255,255,0.5);
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.03);
            text-decoration: none;
            transition: all 0.3s;
        }
        .mobile-lang-btn:hover {
            border-color: rgba(232,122,32,0.5);
            color: #E87A20;
        }
        .mobile-lang-btn.active {
            background: #E87A20;
            color: #fff;
            border-color: transparent;
        }
    </style>
</head>
<body>

<!-- Global Background -->
<div class="site-bg-overlay"></div>

<!-- Noise Overlay -->
<div class="noise-overlay"></div>

<!-- ============ HEADER ============ -->
<header class="header-container" id="header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <a href="index.php" class="flex items-center gap-3 no-underline">
                <div class="w-10 h-10 bg-bhagwa rounded-lg flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <i data-lucide="hard-hat" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold font-heading text-dark leading-tight tracking-tight" style="font-family:'Outfit',sans-serif;">SEVA</h1>
                    <p class="text-[10px] text-bhagwa font-bold tracking-[3px] uppercase -mt-1">CONSTRUCTION</p>
                </div>
            </a>

            <!-- Desktop Nav -->
            <div class="hidden md:flex items-center gap-8">
                <a href="#home" class="nav-link"><?= t('nav_home') ?></a>
                <a href="#Construction" class="nav-link"><?= t('nav_gallery') ?></a>
                <a href="#about" class="nav-link"><?= t('nav_about') ?></a>
                <a href="#contact" class="nav-link"><?= t('nav_contact') ?></a>
                
                <!-- Language Switcher -->
                <?php $availLangs = getAvailableLanguages(); ?>
                <div class="lang-switcher" id="lang-switcher">
                    <button class="lang-btn" id="lang-toggle" aria-label="Change language">
                        <i data-lucide="globe" class="globe-icon"></i>
                        <span><?= strtoupper($current_lang) ?></span>
                        <i data-lucide="chevron-down" class="chevron-icon"></i>
                    </button>
                    <div class="lang-dropdown" id="lang-dropdown">
                        <?php foreach ($availLangs as $code => $info): ?>
                        <a href="<?= langSwitchUrl($code) ?>" class="lang-option <?= $code === $current_lang ? 'active' : '' ?>">
                            <span class="lang-flag"><?= $info['flag'] ?></span>
                            <span class="lang-label">
                                <?= $info['native'] ?>
                                <span class="lang-native block"><?= $info['name'] ?></span>
                            </span>
                            <i data-lucide="check" class="lang-check"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                 <!-- Contractors/Partners Redesign Profiles -->
                <div class="flex items-center gap-6 border-l border-slate-200 pl-6 py-1">
                    <!-- Balasaheb Shinde (Contractor 1) -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-11 h-11 rounded-full overflow-hidden border-2 border-bhagwa shadow-md shadow-orange-500/10">
                            <img src="uploads/partner_1_portrait.png" class="w-full h-full object-cover" alt="<?= t('partner_1_name') ?>">
                        </div>
                        <span class="text-[11px] font-bold text-slate-800 mt-1 leading-none font-heading"><?= t('partner_1_name') ?></span>
                        <a href="tel:<?= t('partner_1_phone') ?>" class="text-[10px] text-royal font-bold hover:text-bhagwa transition-colors mt-0.5"><?= t('partner_1_phone') ?></a>
                    </div>
                    
                    <!-- Sanjay Patil (Contractor 2) -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-11 h-11 rounded-full overflow-hidden border-2 border-bhagwa shadow-md shadow-orange-500/10">
                            <img src="uploads/partner_2_portrait.png" class="w-full h-full object-cover" alt="<?= t('partner_2_name') ?>">
                        </div>
                        <span class="text-[11px] font-bold text-slate-800 mt-1 leading-none font-heading"><?= t('partner_2_name') ?></span>
                        <a href="tel:<?= t('partner_2_phone') ?>" class="text-[10px] text-royal font-bold hover:text-bhagwa transition-colors mt-0.5"><?= t('partner_2_phone') ?></a>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="md:hidden text-dark p-2" id="mobile-menu-toggle">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobile-menu">
    <button class="absolute top-6 right-6 text-white/70 p-2 hover:text-white" id="mobile-menu-close">
        <i data-lucide="x" class="w-6 h-6"></i>
    </button>
    <div class="flex flex-col items-center gap-8 w-full max-w-sm px-6">
        <!-- Dual Circular Profiles for Mobile Menu -->
        <div class="grid grid-cols-2 gap-8 w-full border-b border-white/10 pb-6 mb-2">
            <!-- Balasaheb Shinde -->
            <div class="flex flex-col items-center text-center">
                <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-bhagwa shadow-xl shadow-orange-500/20">
                    <img src="uploads/partner_1_portrait.png" class="w-full h-full object-cover" alt="<?= t('partner_1_name') ?>">
                </div>
                <span class="text-sm font-bold text-white mt-3 font-heading"><?= t('partner_1_name') ?></span>
                <a href="tel:<?= t('partner_1_phone') ?>" class="inline-flex items-center gap-1.5 text-xs text-royal font-bold bg-white px-3 py-1.5 rounded-full shadow-lg shadow-blue-500/10 mt-2 hover:scale-105 transition-transform no-underline">
                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                    <?= t('partner_1_phone') ?>
                </a>
            </div>
            
            <!-- Sanjay Patil -->
            <div class="flex flex-col items-center text-center">
                <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-bhagwa shadow-xl shadow-orange-500/20">
                    <img src="uploads/partner_2_portrait.png" class="w-full h-full object-cover" alt="<?= t('partner_2_name') ?>">
                </div>
                <span class="text-sm font-bold text-white mt-3 font-heading"><?= t('partner_2_name') ?></span>
                <a href="tel:<?= t('partner_2_phone') ?>" class="inline-flex items-center gap-1.5 text-xs text-royal font-bold bg-white px-3 py-1.5 rounded-full shadow-lg shadow-blue-500/10 mt-2 hover:scale-105 transition-transform no-underline">
                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                    <?= t('partner_2_phone') ?>
                </a>
            </div>
        </div>

        <a href="#home" class="text-xl font-bold text-white hover:text-bhagwa transition-colors no-underline font-heading mobile-link"><?= t('nav_home') ?></a>
        <a href="#Construction" class="text-xl font-bold text-white hover:text-bhagwa transition-colors no-underline font-heading mobile-link"><?= t('nav_gallery') ?></a>
        <a href="#about" class="text-xl font-bold text-white hover:text-bhagwa transition-colors no-underline font-heading mobile-link"><?= t('nav_about') ?></a>
        <a href="#contact" class="text-xl font-bold text-white hover:text-bhagwa transition-colors no-underline font-heading mobile-link"><?= t('nav_contact') ?></a>
        
        <!-- Mobile Language Switcher -->
        <div class="mobile-lang-options">
            <?php foreach ($availLangs as $code => $info): ?>
            <a href="<?= langSwitchUrl($code) ?>" class="mobile-lang-btn <?= $code === $current_lang ? 'active' : '' ?>">
                <?= $info['native'] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============ HERO SLIDESHOW ============ -->
<section class="hero-slider-container" id="home">
    <div class="glide hero-slider">
        <div class="glide__track" data-glide-el="track">
            <ul class="glide__slides">
                <?php if ($hero_slides->num_rows > 0): ?>
                    <?php while($slide = $hero_slides->fetch_assoc()): ?>
                    <li class="glide__slide relative">
                        <div class="absolute inset-0 bg-black/30 z-10"></div>
                        <img src="<?= e($slide['image_path']) ?>" class="hero-slide-img" alt="<?= e($slide['title']) ?>">
                        <div class="absolute inset-0 z-20 flex items-center">
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                                <div class="max-w-2xl bg-white/10 backdrop-blur-md p-6 sm:p-10 rounded-3xl border border-white/20">
                                    <span class="text-bhagwa font-bold uppercase tracking-widest text-xs mb-3 block"><?= e($slide['subtitle']) ?></span>
                                    <h2 class="text-3xl md:text-5xl font-black text-white mb-6 font-heading leading-tight"><?= e($slide['title']) ?></h2>
                                    <div class="flex flex-wrap gap-4">
                                        <a href="tel:<?= SITE_PHONE ?>" class="bg-bhagwa text-white px-8 py-4 rounded-full font-bold text-sm shadow-xl shadow-orange-500/30 hover:scale-105 transition-transform"><?= t('hero_cta_quote') ?></a>
                                        <a href="#Construction" class="bg-white/20 text-white border border-white/40 px-8 py-4 rounded-full font-bold text-sm backdrop-blur hover:bg-white hover:text-dark transition-all"><?= t('hero_cta_gallery') ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="glide__slide relative">
                        <div class="absolute inset-0 bg-black/30 z-10"></div>
                        <img src="https://images.unsplash.com/photo-1541888946425-d81bb19240f5?auto=format&fit=crop&q=80&w=1920" class="hero-slide-img" alt="Construction">
                        <div class="absolute inset-0 z-20 flex items-center">
                             <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                                <div class="max-w-2xl bg-white/10 backdrop-blur-md p-10 rounded-3xl border border-white/20">
                                    <span class="text-bhagwa font-bold uppercase tracking-widest text-xs mb-4 block"><?= t('hero_subtitle') ?></span>
                                    <h2 class="text-4xl md:text-6xl font-black text-white mb-6 font-heading leading-tight"><?= t('hero_title') ?></h2>
                                    <a href="tel:<?= SITE_PHONE ?>" class="inline-block bg-bhagwa text-white px-10 py-5 rounded-full font-black text-lg shadow-2xl shadow-orange-500/40"><?= t('hero_cta_call') ?>: <?= SITE_PHONE ?></a>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Controls -->
        <div class="glide__arrows" data-glide-el="controls">
            <button class="glide__arrow glide__arrow--left w-12 h-12 rounded-full bg-white/10 border border-white/20 text-white flex items-center justify-center backdrop-blur hover:bg-bhagwa transition-all absolute left-6 top-1/2 -translate-y-1/2 z-30" data-glide-dir="<">
                <i data-lucide="chevron-left"></i>
            </button>
            <button class="glide__arrow glide__arrow--right w-12 h-12 rounded-full bg-white/10 border border-white/20 text-white flex items-center justify-center backdrop-blur hover:bg-bhagwa transition-all absolute right-6 top-1/2 -translate-y-1/2 z-30" data-glide-dir=">">
                <i data-lucide="chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<!-- ============ Construction / GALLERY SECTION ============ -->
<section class="py-24 relative" id="Construction">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-16 reveal">
            <span class="text-bhagwa font-bold tracking-[4px] uppercase text-xs mb-3 block"><?= t('gallery_tag') ?></span>
            <h2 class="text-4xl md:text-6xl font-black text-dark mb-4 font-heading">
                <?= t('gallery_title_1') ?> <span class="text-bhagwa border-b-4 border-bhagwa/20"><?= t('gallery_title_2') ?></span>
            </h2>
            <p class="text-dark/40 max-w-xl mx-auto"><?= t('gallery_desc') ?></p>
        </div>

        <!-- Category Filters -->
        <div class="flex flex-wrap items-center justify-center gap-3 mb-12 reveal" id="filter-container">
            <a href="index.php#Construction" class="filter-tab <?= empty($category_filter) || $category_filter === 'All' ? 'active' : '' ?>" data-category="All">
                <?= t('gallery_all') ?>
            </a>
            <?php foreach ($categories as $cat): ?>
            <a href="index.php?category=<?= urlencode($cat) ?>#Construction" class="filter-tab <?= $category_filter === $cat ? 'active' : '' ?>" data-category="<?= e($cat) ?>">
                <?= e($cat) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Gallery Grid -->
        <?php if ($Construction->num_rows > 0): ?>
        <div class="gallery-grid" id="gallery-grid">
            <?php while ($project = $Construction->fetch_assoc()): ?>
            <div class="project-card reveal" data-category="<?= e($project['category']) ?>">
                <a href="<?= e($project['image_path']) ?>" class="glightbox" data-gallery="Construction" data-title="<?= e($project['title']) ?>" data-description="<?= e($project['category']) ?>">
                    <img 
                        src="<?= e($project['image_path']) ?>" 
                        alt="<?= e($project['title']) ?>" 
                        class="card-image"
                        loading="lazy"
                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22280%22 fill=%22%230d1530%22%3E%3Crect width=%22400%22 height=%22280%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%23555%22 font-family=%22sans-serif%22 font-size=%2216%22%3EImage Not Found%3C/text%3E%3C/svg%3E';"
                    >
                    <div class="zoom-icon">
                        <i data-lucide="maximize-2" class="w-5 h-5 text-white"></i>
                    </div>
                </a>
                <div class="card-overlay">
                    <span class="bg-bhagwa text-white text-[10px] px-3 py-1 rounded-full font-bold mb-2 inline-block"><?= e($project['category']) ?></span>
                    <h3 class="text-lg font-bold text-white mt-1 font-heading"><?= e($project['title']) ?></h3>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state reveal">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-slate-100">
                <i data-lucide="image-off" class="w-10 h-10 text-slate-300"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-400 mb-2" style="font-family:'Outfit',sans-serif;"><?= t('gallery_empty_title') ?></h3>
            <p class="text-slate-400 text-sm font-medium"><?= t('gallery_empty_desc') ?></p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ ABOUT SECTION ============ -->
<section class="py-24 relative" id="about">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <!-- Left: Content -->
            <div class="reveal">
                <div class="section-tag">
                    <i data-lucide="hammer" class="w-4 h-4"></i>
                    <?= t('about_tag') ?>
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-dark mt-4 mb-6" style="font-family:'Outfit',sans-serif;">
                    <?= t('about_title_1') ?> <span class="bg-gradient-to-r from-orange-500 to-orange-600 bg-clip-text text-transparent"><?= t('about_title_2') ?></span>
                </h2>
                <p class="text-slate-500 mb-8 leading-relaxed font-medium">
                    <?= t('about_desc') ?>
                </p>

                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-orange-500/10 rounded-xl flex items-center justify-center flex-shrink-0 border border-orange-500/20">
                            <i data-lucide="brick-wall" class="w-5 h-5 text-orange-500"></i>
                        </div>
                        <div>
                            <h4 class="text-dark font-bold mb-1" style="font-family:'Outfit',sans-serif;"><?= t('about_feat1_title') ?></h4>
                            <p class="text-slate-500 text-sm font-medium"><?= t('about_feat1_desc') ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-bhagwa/10 rounded-xl flex items-center justify-center flex-shrink-0 border border-bhagwa/20">
                            <i data-lucide="paintbrush" class="w-5 h-5 text-bhagwa"></i>
                        </div>
                        <div>
                            <h4 class="text-dark font-bold mb-1 font-heading"><?= t('about_feat2_title') ?></h4>
                            <p class="text-dark/40 text-sm"><?= t('about_feat2_desc') ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-bhagwa/10 rounded-xl flex items-center justify-center flex-shrink-0 border border-bhagwa/20">
                            <i data-lucide="ruler" class="w-5 h-5 text-bhagwa"></i>
                        </div>
                        <div>
                            <h4 class="text-dark font-bold mb-1 font-heading"><?= t('about_feat3_title') ?></h4>
                            <p class="text-dark/40 text-sm"><?= t('about_feat3_desc') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Visual -->
            <div class="reveal">
                <div class="relative">
                    <div class="bg-white rounded-3xl p-8 border border-bhagwa/10 shadow-2xl shadow-bhagwa/5 relative overflow-hidden">
                        <!-- Blueprint pattern -->
                        <div class="absolute inset-0 opacity-5" style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22%3E%3Cpath d=%22M0 0h40v40H0z%22 fill=%22none%22 stroke=%22%23E87A20%22 stroke-width=%221%22/%3E%3C/svg%3E');"></div>
                        
                        <div class="relative z-10 space-y-6">
                            <div class="flex items-center gap-4 p-4 bg-bhagwa/5 rounded-2xl border border-bhagwa/10">
                                <div class="w-14 h-14 bg-bhagwa rounded-xl flex items-center justify-center shadow-lg shadow-bhagwa/20">
                                    <i data-lucide="brick-wall" class="w-7 h-7 text-white"></i>
                                </div>
                                <div>
                                    <h4 class="text-dark font-bold font-heading"><?= t('about_service1') ?></h4>
                                    <p class="text-dark/40 text-sm"><?= t('about_service1_desc') ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-bhagwa/5 rounded-2xl border border-bhagwa/10">
                                <div class="w-14 h-14 bg-bhagwa rounded-xl flex items-center justify-center shadow-lg shadow-bhagwa/20">
                                    <i data-lucide="paintbrush" class="w-7 h-7 text-white"></i>
                                </div>
                                <div>
                                    <h4 class="text-dark font-bold font-heading"><?= t('about_service2') ?></h4>
                                    <p class="text-dark/40 text-sm"><?= t('about_service2_desc') ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-bhagwa/5 rounded-2xl border border-bhagwa/10">
                                <div class="w-14 h-14 bg-bhagwa rounded-xl flex items-center justify-center shadow-lg shadow-bhagwa/20">
                                    <i data-lucide="pencil-ruler" class="w-7 h-7 text-white"></i>
                                </div>
                                <div>
                                    <h4 class="text-dark font-bold font-heading"><?= t('about_service3') ?></h4>
                                    <p class="text-dark/40 text-sm"><?= t('about_service3_desc') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Decorative glow -->
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-bhagwa/10 rounded-full blur-3xl"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ CONTACT / CTA SECTION ============ -->
<section class="py-24 relative" id="contact">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-3xl p-8 sm:p-16 border border-bhagwa/10 shadow-2xl shadow-bhagwa/5 relative overflow-hidden reveal">
            <div class="relative z-10 text-center max-w-2xl mx-auto">
                <span class="text-bhagwa font-bold tracking-[4px] uppercase text-xs mb-3 block"><?= t('contact_tag') ?></span>
                <h2 class="text-4xl md:text-6xl font-black text-dark mt-4 mb-6 font-heading">
                    <?= t('contact_title_1') ?> <span class="text-bhagwa"><?= t('contact_title_2') ?></span>
                </h2>
                <p class="text-dark/40 mb-10 text-lg"><?= t('contact_desc') ?></p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                    <a href="tel:<?= SITE_PHONE ?>" class="group flex items-center gap-4 bg-royal text-white px-8 py-5 rounded-full text-xl font-bold no-underline shadow-xl shadow-blue-500/30 hover:scale-105 transition-transform">
                        <i data-lucide="phone" class="w-6 h-6 group-hover:rotate-12 transition-transform"></i>
                        <?= SITE_PHONE ?>
                    </a>
                    <a href="https://wa.me/91<?= SITE_PHONE ?>" target="_blank" class="group flex items-center gap-4 bg-success text-white px-8 py-5 rounded-full text-xl font-bold no-underline shadow-xl shadow-green-500/30 hover:scale-105 transition-transform">
                        <i data-lucide="message-circle" class="w-6 h-6 group-hover:scale-110 transition-transform"></i>
                        <?= t('contact_whatsapp') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="bg-white border-t border-slate-100 py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-3 gap-16">
            <!-- Brand -->
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-bhagwa rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                        <i data-lucide="hard-hat" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-dark font-heading">SEVA</h3>
                        <p class="text-[10px] text-bhagwa tracking-[4px] uppercase -mt-1 font-bold">CONSTRUCTION</p>
                    </div>
                </div>
                <p class="text-slate-500 text-sm leading-relaxed mb-6 font-medium"><?= t('footer_desc') ?></p>
                <div class="flex gap-4">
                    <a href="#" class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-bhagwa hover:text-white transition-all"><i data-lucide="facebook" class="w-4 h-4"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-bhagwa hover:text-white transition-all"><i data-lucide="instagram" class="w-4 h-4"></i></a>
                </div>
            </div>

            <!-- Links -->
            <div>
                <h4 class="text-dark font-bold mb-8 font-heading text-lg"><?= t('footer_links') ?></h4>
                <div class="grid grid-cols-2 gap-4">
                    <a href="#home" class="text-slate-500 hover:text-bhagwa text-sm transition-colors no-underline font-medium"><?= t('footer_home') ?></a>
                    <a href="#Construction" class="text-slate-500 hover:text-bhagwa text-sm transition-colors no-underline font-medium"><?= t('footer_construction') ?></a>
                    <a href="#about" class="text-slate-500 hover:text-bhagwa text-sm transition-colors no-underline font-medium"><?= t('footer_about') ?></a>
                    <a href="#contact" class="text-slate-500 hover:text-bhagwa text-sm transition-colors no-underline font-medium"><?= t('footer_contact') ?></a>
                    <a href="admin/login.php" class="text-slate-400 hover:text-bhagwa text-xs transition-colors no-underline font-bold mt-4"><?= t('footer_admin') ?></a>
                </div>
            </div>

            <!-- Contact -->
            <div>
                <h4 class="text-dark font-bold mb-8 font-heading text-lg"><?= t('footer_location_title') ?></h4>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <i data-lucide="map-pin" class="w-5 h-5 text-bhagwa flex-shrink-0"></i>
                        <span class="text-slate-500 text-sm font-medium"><?= t('footer_address') ?></span>
                    </div>
                    <div class="flex items-center gap-4">
                        <i data-lucide="phone" class="w-5 h-5 text-royal flex-shrink-0"></i>
                        <a href="tel:<?= SITE_PHONE ?>" class="text-dark font-bold hover:text-bhagwa transition-colors no-underline"><?= SITE_PHONE ?></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-20 pt-8 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-slate-400 text-xs font-medium"><?= t('footer_copyright', ['%year%' => date('Y')]) ?></p>
            <p class="text-slate-300 text-[10px] uppercase tracking-widest font-bold"><?= t('footer_tagline') ?></p>
        </div>
    </div>
</footer>

<!-- Floating Action Buttons -->
<div class="fixed bottom-6 right-6 z-[100] flex flex-col gap-4">
    <!-- WhatsApp -->
    <a href="https://wa.me/91<?= SITE_PHONE ?>" target="_blank" class="w-14 h-14 bg-success text-white rounded-full flex items-center justify-center shadow-2xl shadow-green-500/40 hover:scale-110 transition-transform group relative" title="<?= t('fab_whatsapp') ?>">
        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.486a.5.5 0 00.627.616l4.584-1.377A11.955 11.955 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.239 0-4.308-.724-5.993-1.953l-.42-.306-2.716.816.752-2.793-.326-.44A9.935 9.935 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
        <span class="absolute right-full mr-4 bg-dark text-white px-3 py-1 rounded text-xs whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity"><?= t('fab_whatsapp') ?></span>
    </a>
    <!-- Call -->
    <a href="tel:<?= SITE_PHONE ?>" class="w-14 h-14 bg-royal text-white rounded-full flex items-center justify-center shadow-2xl shadow-blue-500/40 hover:scale-110 transition-transform group relative" title="<?= t('fab_call') ?>">
        <i data-lucide="phone" class="w-7 h-7"></i>
        <span class="absolute right-full mr-4 bg-dark text-white px-3 py-1 rounded text-xs whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity"><?= t('fab_call') ?></span>
    </a>
</div>

<!-- ============ SCRIPTS ============ -->
<!-- Glide.js JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.2.0/glide.min.js"></script>

<!-- GLightbox JS -->
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>

<script>
    // Initialize Lucide Icons
    lucide.createIcons();

    // ---- Language Switcher ----
    const langSwitcher = document.getElementById('lang-switcher');
    const langToggle = document.getElementById('lang-toggle');
    if (langSwitcher && langToggle) {
        langToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            langSwitcher.classList.toggle('open');
        });
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!langSwitcher.contains(e.target)) {
                langSwitcher.classList.remove('open');
            }
        });
        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') langSwitcher.classList.remove('open');
        });
    }

    // Initialize Hero Slider
    if(document.querySelector('.hero-slider')) {
        new Glide('.hero-slider', {
            type: 'carousel',
            autoplay: 5000,
            hoverpause: true,
            perView: 1,
            animationDuration: 1000,
            animationTimingFunc: 'cubic-bezier(0.165, 0.84, 0.44, 1)'
        }).mount();
    }

    // Initialize GLightbox
    const lightbox = GLightbox({
        selector: '.glightbox',
        touchNavigation: true,
        loop: true
    });

    // ---- Navbar Scroll Effect ----
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // ---- Mobile Menu ----
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    const mobileLinks = document.querySelectorAll('.mobile-link');

    mobileMenuToggle.addEventListener('click', () => mobileMenu.classList.add('active'));
    mobileMenuClose.addEventListener('click', () => mobileMenu.classList.remove('active'));
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => mobileMenu.classList.remove('active'));
    });

    // ---- Smooth Scroll ----
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ---- Counter Animation ----
    function animateCounters() {
        const counters = document.querySelectorAll('[data-count]');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const suffix = counter.textContent.replace(/[0-9]/g, '');
            let current = 0;
            const increment = Math.ceil(target / 60);
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                counter.textContent = current + suffix;
            }, 30);
        });
    }

    // ---- Reveal on Scroll ----
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                
                // Trigger counter animation when stats section is visible
                if (entry.target.querySelector('[data-count]')) {
                    animateCounters();
                }
                
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

    // ---- Re-init Lucide after dynamic content ----
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
</body>
</html>
