<?php
require_once 'config.php';

// Fetch settings
$settings = [
    'website_name' => 'Roselia',
    'slogan' => 'Mỗi bó hoa, một câu chuyện',
    'logo' => 'Roselia',
    'address' => '',
    'phone' => '',
    'email' => '',
    'facebook' => '',
    'zalo' => '',
    'youtube' => ''
];

try {
    $stmt = $pdo->query("SELECT * FROM `Settings` LIMIT 1");
    $dbSettings = $stmt->fetch();
    if ($dbSettings) {
        $settings = $dbSettings;
    }
} catch (PDOException $e) {
    // If table settings is missing (e.g. before install), keep default arrays.
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($settings['website_name']) ?> - <?= sanitize($settings['slogan']) ?></title>
    <meta name="description" content="Roselia - Cửa hàng hoa tươi cao cấp mang lại vẻ đẹp và câu chuyện ý nghĩa qua từng cành hoa.">
    <!-- Link Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- FontAwesome for icons (useful if needed, but we can also use plain text or Unicode arrows) -->
</head>
<body>

    <!-- Server message container for Toast Notification -->
    <?php if (isset($_SESSION['toast'])): ?>
        <div id="server-toast-message" 
             data-message="<?= sanitize($_SESSION['toast']['message']) ?>" 
             data-type="<?= sanitize($_SESSION['toast']['type']) ?>" 
             style="display:none;">
        </div>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    <header>
        <div class="container">
            <div class="header-top">
                <a href="index.php" class="logo-container">
                    <div class="logo-text"><?= sanitize($settings['website_name']) ?></div>
                    <div class="logo-slogan"><?= sanitize($settings['slogan']) ?></div>
                </a>
            </div>
            
            <nav class="header-nav">
                <ul class="nav-links">
                    <li class="<?= $current_page == 'index.php' && !isset($_GET['cat']) ? 'active' : '' ?>">
                        <a href="index.php">Trang chủ</a>
                    </li>
                    <li class="<?= $current_page == 'index.php' && isset($_GET['cat']) ? 'active' : '' ?>">
                        <a href="index.php?cat=all">Sản phẩm</a>
                    </li>
                    <li class="<?= $current_page == 'reviews.php' ? 'active' : '' ?>">
                        <a href="reviews.php">Đánh giá</a>
                    </li>
                    <li class="<?= $current_page == 'news.php' ? 'active' : '' ?>">
                        <a href="news.php">Tin tức</a>
                    </li>
                    <li class="<?= $current_page == 'contact.php' ? 'active' : '' ?>">
                        <a href="contact.php">Liên hệ</a>
                    </li>
                    <li class="<?= $current_page == 'cart.php' ? 'active' : '' ?>">
                        <a href="cart.php">Giỏ hàng</a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <li class="user-menu-item">
                            <a href="#" class="username-trigger">
                                <?= sanitize($_SESSION['user_fullname'] ?? 'Thành viên') ?> &#9662;
                            </a>
                            <div class="dropdown-menu">
                                <a href="profile.php">Thông tin cá nhân</a>
                                <a href="cart.php">Giỏ hàng của tôi</a>
                                <?php if (isAdmin()): ?>
                                    <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 4px 0;">
                                    <a href="admin_products.php" style="font-weight: 600; color: var(--primary);">Quản lý sản phẩm</a>
                                <?php endif; ?>
                                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 4px 0;">
                                <a href="logout.php" style="color: var(--danger);">Đăng xuất</a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li class="<?= $current_page == 'auth.php' ? 'active' : '' ?>">
                            <a href="auth.php">Đăng nhập</a>
                        </li>
                        <li class="<?= $current_page == 'auth.php' ? 'active' : '' ?>">
                            <a href="auth.php#register">Đăng ký</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
