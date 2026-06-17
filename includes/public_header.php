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
    <title>Cao Đẳng Nghề Việt Nam - Hàn Quốc Cà Mau</title>
    <meta name="description" content="Trường Cao Đẳng Nghề Việt Nam - Hàn Quốc Cà Mau - Kiến tạo tương lai với đào tạo nghề chất lượng cao.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/home.css?v=<?= filemtime(__DIR__ . '/../assets/home.css') ?>">
</head>
<body>

<!-- TOP UTILITY BAR -->
<div class="top-bar">
    <div class="top-bar-left">
        <span><i class="fas fa-phone-alt"></i> 0290 3838 234</span>
        <span><i class="fas fa-envelope"></i> tuyensinh@vkc.edu.vn</span>
    </div>
    <div class="top-bar-right">
        <a href="/tkb/tai_lieu.php"><i class="fas fa-file-alt"></i> Tài liệu</a>
        <a href="/tkb/thu_vien.php"><i class="fas fa-book-open"></i> Thư viện</a>
        <a href="/tkb/su_kien.php"><i class="fas fa-calendar-alt"></i> Sự kiện</a>
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
            <img src="/tkb/assets/img/logo_vkc.jpg" alt="Logo VKC" class="logo-img">
            <div class="brand-text">
                <span class="brand-title">CAO ĐẲNG NGHỀ VIỆT NAM – HÀN QUỐC</span>
                <span class="brand-subtitle">Cà Mau · Kiến Tạo Tương Lai</span>
            </div>
        </a>

        <div class="nav-links" id="navLinks">
            <a href="/tkb/index.php" class="nav-link <?= $currentPage==='index.php'?'active':'' ?>">Trang chủ</a>
            <a href="/tkb/gioi_thieu.php" class="nav-link <?= $currentPage==='gioi_thieu.php'?'active':'' ?>">Giới thiệu</a>
            <a href="/tkb/dao_tao.php" class="nav-link <?= $currentPage==='dao_tao.php'?'active':'' ?>">Đào tạo</a>
            <a href="/tkb/tuyen_sinh.php" class="nav-link <?= $currentPage==='tuyen_sinh.php'?'active':'' ?>">Tuyển sinh</a>
            <a href="/tkb/lien_he.php" class="nav-link <?= $currentPage==='lien_he.php'?'active':'' ?>">Liên hệ</a>
            <a href="/tkb/lien_he.php#dang-ky" class="btn-register">Đăng ký ngay <i class="fas fa-arrow-right"></i></a>
        </div>

        <button class="hamburger" onclick="toggleNav()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </nav>

    <!-- MOBILE DRAWER -->
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="drawer-header">
            <span>Menu</span>
            <button onclick="toggleNav()" class="drawer-close">✕</button>
        </div>
        <div class="drawer-links">
            <a href="/tkb/index.php"><i class="fas fa-home"></i> Trang chủ</a>
            <a href="/tkb/gioi_thieu.php"><i class="fas fa-info-circle"></i> Giới thiệu</a>
            <a href="/tkb/dao_tao.php"><i class="fas fa-graduation-cap"></i> Đào tạo</a>
            <a href="/tkb/tuyen_sinh.php"><i class="fas fa-user-graduate"></i> Tuyển sinh</a>
            <a href="/tkb/lien_he.php"><i class="fas fa-phone"></i> Liên hệ</a>
            <?php if ($loggedIn): ?>
                <a href="<?= $dashboardUrl ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="/tkb/login.php?logout=1" class="drawer-danger"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            <?php else: ?>
                <a href="/tkb/login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
            <?php endif; ?>
            <a href="/tkb/lien_he.php#dang-ky" class="drawer-register">Đăng ký ngay <i class="fas fa-arrow-right"></i></a>
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