<?php
require_once __DIR__ . '/../config.php';
$loggedIn = isLoggedIn();
$dashboardUrl = $loggedIn ? getDashboardUrlByRole($_SESSION['role'] ?? '') : '/tkb/login.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi" id="top">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trường Cao Đẳng Cà Mau</title>
    <meta name="description" content="Trường Cao Đẳng Cà Mau - Kiến tạo tương lai với đào tạo chất lượng cao.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/tkb/assets/home.css?v=<?= filemtime(__DIR__ . '/../assets/home.css') ?>">
</head>
<body>

<style>
/* =========================================================
   SLEEK GLASSMORPHISM LED 7-COLOR RAINBOW BADGE
   ========================================================= */
.plain-led-copyright {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
}

.top-led-copyright {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 5px 18px;
    background: #ffffff;
    border-radius: 50px;
    border: 1.5px solid transparent;
    background-image: linear-gradient(#ffffff, #ffffff), 
                      linear-gradient(90deg, #ff0055, #ff5000, #ffcc00, #00ff66, #00ccff, #7000ff, #ff00cc, #ff0055);
    background-origin: border-box;
    background-clip: padding-box, border-box;
    background-size: 100% 100%, 300% 100%;
    animation: ledBorderRotate 4s linear infinite;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08), 0 0 12px rgba(217, 27, 67, 0.15);
}

.top-led-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #00ff66;
    box-shadow: 0 0 8px #00ff66, 0 0 14px #00ff66;
    animation: ledPulse 2s linear infinite;
    flex-shrink: 0;
}

.square-led-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    padding: 2.5px;
    background: #ffffff;
    border: 2px solid transparent;
    background-image: linear-gradient(#ffffff, #ffffff), 
                      linear-gradient(90deg, #ff0055, #ff5000, #ffcc00, #00ff66, #00ccff, #7000ff, #ff00cc, #ff0055);
    background-origin: border-box;
    background-clip: padding-box, border-box;
    background-size: 100% 100%, 300% 100%;
    animation: ledBorderRotate 3s linear infinite;
    box-shadow: 0 4px 15px rgba(0, 255, 102, 0.3), 0 0 12px rgba(255, 0, 85, 0.2);
    flex-shrink: 0;
    transition: transform 0.3s ease;
}
.square-led-avatar:hover {
    transform: scale(1.15) rotate(3deg);
}
.square-led-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 9px;
    background: #fff;
    display: block;
}

.top-led-text {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: 12px;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    background: linear-gradient(90deg, 
        #ff0055, #ff5000, #ffcc00, #00ff66, #00ccff, #7000ff, #ff00cc, #ff0055);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: rainbowGlow 4s linear infinite;
}

@keyframes ledBorderRotate {
    0% { background-position: 0% 0%, 0% 50%; }
    50% { background-position: 0% 0%, 100% 50%; }
    100% { background-position: 0% 0%, 0% 50%; }
}
@keyframes rainbowGlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
@keyframes ledPulse {
    0% { background: #00ff66; box-shadow: 0 0 6px #00ff66, 0 0 12px #00ff66; }
    33% { background: #00ccff; box-shadow: 0 0 6px #00ccff, 0 0 12px #00ccff; }
    66% { background: #ff00cc; box-shadow: 0 0 6px #ff00cc, 0 0 12px #ff00cc; }
    100% { background: #00ff66; box-shadow: 0 0 6px #00ff66, 0 0 12px #00ff66; }
}
</style>

<!-- TOP UTILITY BAR -->
<div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; padding: 6px 30px;">
    <div class="top-bar-left">
        <span><i class="fas fa-graduation-cap"></i> Cổng Thông Tin Học Tập & IDE</span>
    </div>

    <div class="top-bar-right">
        <?php if ($loggedIn): ?>
            <a href="<?= $dashboardUrl ?>" class="top-bar-btn"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="/tkb/login.php?logout=1" class="top-bar-btn danger"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        <?php else: ?>
            <a href="/tkb/login.php" class="top-bar-btn"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
        <?php endif; ?>
    </div>
</div>

<!-- MAIN NAVIGATION -->
<header class="site-header" id="siteHeader">
    <nav class="navbar">
        <a href="/tkb/index.php" class="nav-brand">
            <img src="/tkb/assets/img/logo_vkc.jpg" alt="Logo CĐ Cà Mau" class="logo-img">
            <div class="brand-text">
                <span class="brand-title">TRƯỜNG CAO ĐẲNG CÀ MAU</span>
                <span class="brand-subtitle">Cổng Thông Tin Học Tập &amp; Đào Tạo</span>
            </div>
        </a>



        <div class="nav-links" id="navLinks">
            <a href="/tkb/index.php" class="nav-link <?= $currentPage==='index.php'?'active':'' ?>">Trang chủ</a>
            <a href="/tkb/student/dashboard.php" class="nav-link">Cổng Sinh Viên</a>
            <a href="/tkb/teacher/dashboard.php" class="nav-link">Cổng Giảng Viên</a>
            <a href="/tkb/student/code_ide.php" class="nav-link"><i class="fas fa-code"></i> Thực Hành IDE</a>
            <a href="/tkb/huong_dan.php" class="nav-link <?= $currentPage==='huong_dan.php'?'active':'' ?>"><i class="fas fa-book"></i> Hướng dẫn</a>
            <a href="/tkb/lien_he.php" class="nav-link <?= $currentPage==='lien_he.php'?'active':'' ?>">Liên hệ</a>
        </div>

        <button class="hamburger" onclick="toggleNav()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </nav>

    <!-- MOBILE DRAWER -->
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="drawer-header">
            <span>Menu Navigation</span>
            <button onclick="toggleNav()" class="drawer-close">✕</button>
        </div>
        <div class="drawer-links">
            <a href="/tkb/index.php"><i class="fas fa-home"></i> Trang chủ</a>
            <a href="/tkb/student/dashboard.php"><i class="fas fa-user-graduate"></i> Cổng Sinh Viên</a>
            <a href="/tkb/teacher/dashboard.php"><i class="fas fa-chalkboard-teacher"></i> Cổng Giảng Viên</a>
            <a href="/tkb/student/code_ide.php"><i class="fas fa-code"></i> Thực Hành IDE</a>
            <a href="/tkb/huong_dan.php"><i class="fas fa-book"></i> Hướng dẫn</a>
            <a href="/tkb/lien_he.php"><i class="fas fa-phone"></i> Liên hệ</a>
            <?php if ($loggedIn): ?>
                <a href="<?= $dashboardUrl ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="/tkb/login.php?logout=1" class="drawer-danger"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            <?php else: ?>
                <a href="/tkb/login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
function toggleNav() {
    const drawer = document.getElementById('mobileDrawer');
    const header = document.getElementById('siteHeader');
    drawer.classList.toggle('open');
    document.body.classList.toggle('drawer-open');
}
document.addEventListener('click', function(e) {
    const drawer = document.getElementById('mobileDrawer');
    const hamburger = document.querySelector('.hamburger');
    if (drawer.classList.contains('open') && !drawer.contains(e.target) && !hamburger.contains(e.target)) {
        drawer.classList.remove('open');
        document.body.classList.remove('drawer-open');
    }
});
// Scroll effect on navbar
window.addEventListener('scroll', function() {
    const header = document.getElementById('siteHeader');
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});
</script>