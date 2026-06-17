<!-- Student Nav -->
<link rel="stylesheet" href="/tkb/assets/student_new.css">

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-logo-area">
        <img src="/tkb/assets/img/logo_vkc.jpg" alt="Logo" class="sidebar-logo-img">
        <div class="sidebar-brand">CAO ĐẲNG NGHỀ VN-HQ</div>
        <div class="sidebar-sub">Hệ Thống Quản Lý Đào Tạo</div>
    </div>
    <ul class="nav-list">
        <li><a href="/tkb/student/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>">
            <i class="fa-regular fa-user"></i> Cổng sinh viên
        </a></li>
        <li><a href="/tkb/student/tkb.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='tkb.php'?'active':'' ?>">
            <i class="fa-solid fa-list-check"></i> Xem thời khóa biểu
        </a></li>
        <li><a href="/tkb/student/hoc_phan.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='hoc_phan.php'?'active':'' ?>">
            <i class="fa-solid fa-book-open"></i> Xem chương trình môn học
        </a></li>
        <li><a href="/tkb/student/diem.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='diem.php'?'active':'' ?>">
            <i class="fa-solid fa-graduation-cap"></i> Xem điểm (Kết quả học tập)
        </a></li>
        <li><a href="/tkb/student/tai_chinh.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='tai_chinh.php'?'active':'' ?>">
            <i class="fa-solid fa-money-bill"></i> Tài chính
        </a></li>
        <li><a href="/tkb/student/diem_danh.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='diem_danh.php'?'active':'' ?>">
            <i class="fa-solid fa-user-check"></i> Điểm danh
        </a></li>
        <li><a href="/tkb/student/profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='profile.php'?'active':'' ?>">
            <i class="fa-solid fa-users"></i> Dịch vụ HSSV
        </a></li>
        <li style="margin-top: 20px;"><a href="/tkb/api/logout.php" class="nav-link nav-link-danger">
            <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
        </a></li>
    </ul>
</nav>

<div class="main-content">
    <div class="top-header">
        <div class="header-left">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <span></span><span></span><span></span>
            </button>
            <i class="fa-solid fa-gauge-high"></i> CỔNG THÔNG TIN SINH VIÊN
        </div>
        <div class="header-right">
            <i class="fa-solid fa-bell header-bell"></i>
            <div class="user-profile">
                <div class="user-info">
                    <div class="u-name"><?= htmlspecialchars($_SESSION['ho_ten'] ?? 'Sinh viên') ?></div>
                    <div class="u-id"><?= htmlspecialchars($_SESSION['ma_sv'] ?? 'SV000') ?></div>
                </div>
                <a href="/tkb/api/logout.php" class="btn-logout-header"><i class="fa-solid fa-right-from-bracket"></i> Thoát</a>
            </div>
        </div>
    </div>
    <div class="content-pad">

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
</script>