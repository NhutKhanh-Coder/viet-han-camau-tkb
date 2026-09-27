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
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
/* =========================================================
   PREMIUM MODERN NAVBAR & IMPORTANT MENU HIGHLIGHTS
   ========================================================= */
.nav-links {
    display: flex;
    align-items: center;
    gap: 8px;
    height: auto;
}

.nav-links .nav-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    height: 42px;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
    text-decoration: none;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
    position: relative;
    border: 1.5px solid transparent;
}

.nav-links .nav-link::after {
    display: none !important;
}

.nav-links .nav-link:hover {
    background: #f1f5f9;
    color: #0f172a;
    transform: translateY(-1px);
}

.nav-links .nav-link.active {
    background: #f8fafc;
    color: #d91b43;
    font-weight: 700;
}

/* 🎓 CỔNG SINH VIÊN */
.nav-links .nav-badge-sv {
    background: #fff1f2;
    color: #e11d48 !important;
    border: 1.5px solid #fecdd3;
    font-weight: 700;
    box-shadow: 0 2px 10px rgba(225, 29, 72, 0.08);
}
.nav-links .nav-badge-sv i {
    color: #e11d48;
    font-size: 14px;
    transition: transform 0.2s;
}
.nav-links .nav-badge-sv:hover {
    background: linear-gradient(135deg, #e11d48, #be123c) !important;
    color: #ffffff !important;
    border-color: #be123c;
    box-shadow: 0 6px 20px rgba(225, 29, 72, 0.35);
    transform: translateY(-2px);
}
.nav-links .nav-badge-sv:hover i {
    color: #ffffff !important;
    transform: scale(1.15);
}

/* 👨‍🏫 CỔNG GIẢNG VIÊN */
.nav-links .nav-badge-gv {
    background: #eef2ff;
    color: #4f46e5 !important;
    border: 1.5px solid #c7d2fe;
    font-weight: 700;
    box-shadow: 0 2px 10px rgba(79, 70, 229, 0.08);
}
.nav-links .nav-badge-gv i {
    color: #4f46e5;
    font-size: 14px;
    transition: transform 0.2s;
}
.nav-links .nav-badge-gv:hover {
    background: linear-gradient(135deg, #4f46e5, #4338ca) !important;
    color: #ffffff !important;
    border-color: #4338ca;
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
    transform: translateY(-2px);
}
.nav-links .nav-badge-gv:hover i {
    color: #ffffff !important;
    transform: scale(1.15);
}

/* ⚡💻 THỰC HÀNH IDE */
.nav-links .nav-badge-ide {
    background: linear-gradient(135deg, #0284c7, #0369a1);
    color: #ffffff !important;
    border: 1.5px solid rgba(56, 189, 248, 0.6);
    font-weight: 800;
    box-shadow: 0 4px 18px rgba(2, 132, 199, 0.35);
    padding: 8px 16px;
}
.nav-links .nav-badge-ide i {
    color: #7dd3fc;
    font-size: 14px;
    transition: transform 0.25s;
}
.nav-links .nav-badge-ide:hover {
    background: linear-gradient(135deg, #0ea5e9, #0284c7) !important;
    box-shadow: 0 6px 24px rgba(2, 132, 199, 0.55);
    transform: translateY(-2px);
}
.nav-links .nav-badge-ide:hover i {
    color: #ffffff;
    transform: rotate(-10deg) scale(1.15);
}
.nav-live-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(255, 255, 255, 0.22);
    color: #ffffff;
    font-size: 10px;
    font-weight: 900;
    padding: 2px 7px;
    border-radius: 20px;
    letter-spacing: 0.5px;
    border: 1px solid rgba(255, 255, 255, 0.35);
    margin-left: 2px;
}
.nav-live-dot {
    width: 6px;
    height: 6px;
    background: #4ade80;
    border-radius: 50%;
    box-shadow: 0 0 8px #4ade80;
    animation: dotPulse 1.6s infinite ease-in-out;
}
@keyframes dotPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.35); opacity: 0.7; }
}

/* Mobile drawer enhancements */
.drawer-links a {
    border-radius: 12px;
    margin: 4px 14px;
    padding: 12px 18px;
    font-weight: 600;
}
.drawer-highlight-sv {
    background: #fff1f2 !important;
    color: #e11d48 !important;
    border: 1px solid #fecdd3 !important;
    font-weight: 700 !important;
}
.drawer-highlight-gv {
    background: #eef2ff !important;
    color: #4f46e5 !important;
    border: 1px solid #c7d2fe !important;
    font-weight: 700 !important;
}
.drawer-highlight-ide {
    background: linear-gradient(135deg, #0284c7, #0369a1) !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    box-shadow: 0 4px 15px rgba(2, 132, 199, 0.35);
}
.drawer-pill {
    margin-left: auto;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 20px;
    background: rgba(0,0,0,0.06);
}
.drawer-pill-ide {
    background: #38bdf8 !important;
    color: #0c4a6e !important;
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
            <a href="/tkb/index.php" class="nav-link <?= $currentPage==='index.php'?'active':'' ?>">
                <i class="fa-solid fa-house-chimney"></i> <span>Trang chủ</span>
            </a>
            <a href="/tkb/student/dashboard.php" class="nav-link nav-badge-sv">
                <i class="fa-solid fa-user-graduate"></i> <span>Cổng Sinh Viên</span>
            </a>
            <a href="/tkb/teacher/dashboard.php" class="nav-link nav-badge-gv">
                <i class="fa-solid fa-chalkboard-user"></i> <span>Cổng Giảng Viên</span>
            </a>
            <a href="/tkb/student/code_ide.php" class="nav-link nav-badge-ide">
                <i class="fa-solid fa-code"></i> <span>Thực Hành IDE</span> <span class="nav-live-pill"><span class="nav-live-dot"></span>HOT</span>
            </a>
            <a href="/tkb/huong_dan.php" class="nav-link <?= $currentPage==='huong_dan.php'?'active':'' ?>">
                <i class="fa-solid fa-book-open"></i> <span>Hướng dẫn</span>
            </a>
            <a href="/tkb/lien_he.php" class="nav-link <?= $currentPage==='lien_he.php'?'active':'' ?>">
                <i class="fa-solid fa-headset"></i> <span>Liên hệ</span>
            </a>
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
            <a href="/tkb/index.php" class="<?= $currentPage==='index.php'?'active':'' ?>">
                <i class="fa-solid fa-house-chimney"></i> <span>Trang chủ</span>
            </a>
            <a href="/tkb/student/dashboard.php" class="drawer-highlight-sv">
                <i class="fa-solid fa-user-graduate"></i> <span>Cổng Sinh Viên</span> <span class="drawer-pill">PORTAL</span>
            </a>
            <a href="/tkb/teacher/dashboard.php" class="drawer-highlight-gv">
                <i class="fa-solid fa-chalkboard-user"></i> <span>Cổng Giảng Viên</span> <span class="drawer-pill">GIẢNG DẠY</span>
            </a>
            <a href="/tkb/student/code_ide.php" class="drawer-highlight-ide">
                <i class="fa-solid fa-code"></i> <span>Thực Hành IDE</span> <span class="drawer-pill drawer-pill-ide">AI PRO</span>
            </a>
            <a href="/tkb/huong_dan.php" class="<?= $currentPage==='huong_dan.php'?'active':'' ?>">
                <i class="fa-solid fa-book-open"></i> <span>Hướng dẫn</span>
            </a>
            <a href="/tkb/lien_he.php" class="<?= $currentPage==='lien_he.php'?'active':'' ?>">
                <i class="fa-solid fa-headset"></i> <span>Liên hệ</span>
            </a>
            <?php if ($loggedIn): ?>
                <a href="<?= $dashboardUrl ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a href="/tkb/login.php?logout=1" class="drawer-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất</a>
            <?php else: ?>
                <a href="/tkb/login.php"><i class="fa-solid fa-arrow-right-to-bracket"></i> Đăng nhập</a>
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