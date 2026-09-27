<!-- Student Nav - Minecraft Theme -->
<?php
  $_nav_db = getDB();
  $student_id = $_SESSION['student_id'] ?? 0;
  $student_info = null;
  if ($student_id) {
      $res = $_nav_db->query("SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = $student_id");
      if ($res) $student_info = $res->fetch_assoc();
  }
  
  $st_av = !empty($student_info['avatar']) ? (strpos($student_info['avatar'], 'http') === 0 || strpos($student_info['avatar'], '/') === 0 ? $student_info['avatar'] : '/tkb/assets/img/avatars/' . htmlspecialchars($student_info['avatar'])) : '/tkb/assets/img/avatar_khanh.png';
  $st_ma = htmlspecialchars($student_info['ma_sv'] ?? $_SESSION['ma_sv'] ?? 'K24CDCNTT');
  $st_lop = htmlspecialchars($student_info['lop'] ?? 'K24CDCNTT');
  $st_khoa = htmlspecialchars($student_info['khoa'] ?? 'Công nghệ thông tin');
  $st_name = htmlspecialchars($_SESSION['ho_ten'] ?? $student_info['ho_ten'] ?? 'LÊ NHỰT KHÁNH');
  $st_user = htmlspecialchars($_SESSION['username'] ?? $student_info['username'] ?? 'lenhutkhanh');
  $st_gender = $student_info['gioi_tinh'] ?? $_SESSION['gioi_tinh'] ?? 'Nam';
?>
<link rel="stylesheet" href="/tkb/assets/minecraft_student.css?v=<?= time() ?>">
<?php if ($st_gender === 'Nữ'): ?>
<link rel="stylesheet" href="/tkb/assets/soft_female.css?v=<?= time() ?>">
<?php endif; ?>
<link rel="stylesheet" href="/tkb/assets/student_dark_mode.css?v=<?= time() ?>">
<link rel="preload" as="image" href="/tkb/assets/img/curtain_scene_light.png?v=<?= time() ?>">
<link rel="preload" as="image" href="/tkb/assets/img/curtain_scene_dark.png?v=<?= time() ?>">

<script>
(function() {
    var p1 = new Image(); p1.src = '/tkb/assets/img/curtain_scene_light.png?v=<?= time() ?>';
    var p2 = new Image(); p2.src = '/tkb/assets/img/curtain_scene_dark.png?v=<?= time() ?>';
})();

(function() {
    var savedThemeMode = localStorage.getItem('student_dark_mode') || 'light';
    document.documentElement.setAttribute('data-theme-mode', savedThemeMode);
    function applyDarkModeEarly() {
        if (document.body) {
            document.body.setAttribute('data-theme-mode', savedThemeMode);
        }
    }
    applyDarkModeEarly();
    document.addEventListener('DOMContentLoaded', function() {
        applyDarkModeEarly();
        if (typeof updateDarkModeButtonUI === 'function') {
            updateDarkModeButtonUI(savedThemeMode);
        }
    });

    var studentGender = <?= json_encode($st_gender) ?>;
    
    if (studentGender === 'Nữ') {
        localStorage.setItem('student_mc_mode', 'female');
        localStorage.setItem('student_mc_theme', 'female');
        document.documentElement.setAttribute('data-mc-mode', 'female');
        document.documentElement.setAttribute('data-mc-theme', 'female');
    function applyBodyMode() {
        if (document.body) {
            document.body.setAttribute('data-mc-mode', studentGender === 'Nữ' ? 'female' : (localStorage.getItem('student_mc_mode') || 'dark'));
            document.body.setAttribute('data-mc-theme', studentGender === 'Nữ' ? 'female' : (localStorage.getItem('student_mc_theme') || 'red'));
        }
    }
    applyBodyMode();
    document.addEventListener('DOMContentLoaded', applyBodyMode);
        try { document.title = document.title.replace(' - Minecraft Theme', ' - Hệ Thống Quản Lý Đào Tạo'); } catch(e){}
    } else {
        // If gender is Nam or Khác, clear female theme if previously saved
        var savedMode = localStorage.getItem('student_mc_mode');
        if (savedMode === 'female') {
            localStorage.setItem('student_mc_mode', 'dark');
            localStorage.setItem('student_mc_theme', 'red');
        }
        var currentMode = localStorage.getItem('student_mc_mode') || 'dark';
        var currentTheme = localStorage.getItem('student_mc_theme') || 'red';
        var currentBg = localStorage.getItem('student_mc_bg') || 'dark';

        document.documentElement.setAttribute('data-mc-mode', currentMode);
        document.documentElement.setAttribute('data-mc-theme', currentTheme);
        document.documentElement.setAttribute('data-mc-bg', currentBg);
        if (document.body) {
            document.body.setAttribute('data-mc-mode', currentMode);
            document.body.setAttribute('data-mc-theme', currentTheme);
            document.body.setAttribute('data-mc-bg', currentBg);
        }
    }
})();
</script>

<?php if ($st_gender !== 'Nữ'): ?>
<style>
/* Global Light Purple Theme for Male Student Pages */
body[data-mc-mode="male"]:not([data-theme-mode="dark"]), body:not([data-mc-mode="female"]):not([data-theme-mode="dark"]) {
    background: #f5f3ff !important;
    color: #0f172a !important;
    font-family: 'Outfit', sans-serif !important;
}
body:not([data-theme-mode="dark"]) .main-content { background: #f5f3ff !important; }
body:not([data-theme-mode="dark"]) .top-header {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(12px) !important;
    border-bottom: 1px solid #e9d5ff !important;
    box-shadow: 0 4px 20px rgba(124, 58, 237, 0.05) !important;
}
.sidebar {
    background: linear-gradient(180deg, #1d1744 0%, #120e2e 100%) !important;
    border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-top: none !important;
    box-shadow: 4px 0 20px rgba(29, 23, 68, 0.15) !important;
}
.sidebar-logo-area {
    background: transparent !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}
.nav-link {
    color: #cbd5e1 !important;
    border-radius: 10px !important;
    margin: 3px 10px !important;
    padding: 10px 14px !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    transition: all 0.3s ease !important;
    border: 1px solid transparent !important;
}
.nav-link:hover {
    background: rgba(139, 92, 246, 0.15) !important;
    color: #ffffff !important;
    border-color: rgba(139, 92, 246, 0.3) !important;
    transform: translateX(4px) !important;
}
.nav-link.active {
    background: linear-gradient(90deg, #7c3aed, #6d28d9) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4) !important;
    border-radius: 10px !important;
}
.nav-link.active .nav-icon-female {
    color: #ffffff !important;
    filter: drop-shadow(0 0 6px rgba(255, 255, 255, 0.6)) !important;
}

/* Header Elements */
body:not([data-theme-mode="dark"]) .header-left { color: #1e293b !important; font-weight: 900 !important; }
body:not([data-theme-mode="dark"]) .header-left i { color: #8b5cf6 !important; }
body:not([data-theme-mode="dark"]) .user-profile { background: #ffffff !important; border: 1px solid #e9d5ff !important; box-shadow: 0 4px 15px rgba(124, 58, 237, 0.05) !important; }
body:not([data-theme-mode="dark"]) .u-name { color: #4c1d95 !important; font-weight: 800 !important; }
body:not([data-theme-mode="dark"]) .u-id { color: #64748b !important; }
body:not([data-theme-mode="dark"]) .btn-theme-switcher { background: #faf5ff !important; border: 1px solid #e9d5ff !important; color: #8b5cf6 !important; }
body:not([data-theme-mode="dark"]) .btn-theme-switcher i { color: #8b5cf6 !important; }
body:not([data-theme-mode="dark"]) .header-bell { background: #faf5ff !important; border: 1px solid #e9d5ff !important; color: #8b5cf6 !important; }
body:not([data-theme-mode="dark"]) .header-bell i { color: #8b5cf6 !important; }

/* Student Info Card inside Sidebar */
.sidebar-st-info {
    background: rgba(139, 92, 246, 0.1) !important;
    border: 1.5px solid rgba(139, 92, 246, 0.25) !important;
    border-radius: 14px !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
}
.sidebar-st-title {
    color: #c084fc !important;
    font-family: 'Outfit', sans-serif !important;
    font-weight: 800 !important;
}
.sidebar-st-avatar {
    border-color: #8b5cf6 !important;
    box-shadow: 0 0 10px rgba(139, 92, 246, 0.4) !important;
}

/* Cards on all subpages */
body:not([data-theme-mode="dark"]) .card, 
body:not([data-theme-mode="dark"]) .mc-card, 
body:not([data-theme-mode="dark"]) .box, 
body:not([data-theme-mode="dark"]) .panel, 
body:not([data-theme-mode="dark"]) .container-box {
    background: #ffffff !important;
    border: 1.5px solid #f3e8ff !important;
    border-radius: 16px !important;
    box-shadow: 0 8px 30px rgba(124, 58, 237, 0.05) !important;
    color: #0f172a !important;
}
</style>
<?php endif; ?>


<!-- Outer Screen LED 7-Color RGB Strips -->
<div class="mc-outer-led-strip-top"></div>
<div class="mc-outer-led-strip-bottom"></div>
<div class="mc-outer-led-strip-left"></div>
<div class="mc-outer-led-strip-right"></div>

<!-- Screen Transition Curtain (Đóng / Mở Màn Hình Chuyển Giao Sáng / Tối Full Màn Hình) -->
<div id="screenCurtain" class="screen-curtain-wrap">
    <!-- Nửa màn hình trên -->
    <div class="curtain-half curtain-top">
        <div class="curtain-scene-layer layer-dark" id="curtainTopDark">
            <img src="/tkb/assets/img/curtain_scene_dark.png?v=<?= time() ?>" alt="Dark Mode" class="curtain-full-img img-top" loading="eager" decoding="sync">
        </div>
        <div class="curtain-scene-layer layer-light" id="curtainTopLight">
            <img src="/tkb/assets/img/curtain_scene_light.png?v=<?= time() ?>" alt="Light Mode" class="curtain-full-img img-top" loading="eager" decoding="sync">
        </div>
    </div>

    <!-- Nửa màn hình dưới -->
    <div class="curtain-half curtain-bottom">
        <div class="curtain-scene-layer layer-dark" id="curtainBottomDark">
            <img src="/tkb/assets/img/curtain_scene_dark.png?v=<?= time() ?>" alt="Dark Mode" class="curtain-full-img img-bottom" loading="eager" decoding="sync">
        </div>
        <div class="curtain-scene-layer layer-light" id="curtainBottomLight">
            <img src="/tkb/assets/img/curtain_scene_light.png?v=<?= time() ?>" alt="Light Mode" class="curtain-full-img img-bottom" loading="eager" decoding="sync">
        </div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-logo-area">
        <img src="/tkb/assets/img/logo_vkc.jpg" alt="Logo Trường Cao Đẳng Cà Mau" class="sidebar-logo-img">
        <div>
            <div class="sidebar-brand">TRƯỜNG CAO ĐẲNG CÀ MAU</div>
            <div class="sidebar-sub">HỆ THỐNG QUẢN LÝ ĐÀO TẠO</div>
        </div>
    </div>
    <style>
    .nav-icon-female {
        font-size: 17px !important;
        width: 24px !important;
        height: 24px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin-right: 10px !important;
        transition: transform 0.2s ease;
    }
    .nav-link:hover .nav-icon-female {
        transform: scale(1.18);
    }
    </style>

    <ul class="nav-list">
        <?php if ($st_gender === 'Nữ'): ?>
        <!-- =========================================================
             DANH SÁCH MENU DÀNH RIÊNG CHO SINH VIÊN NỮ (ICON HIỆN ĐẠI TONE PASTEL)
             ========================================================= -->
        <li><a href="/tkb/student/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>">
            <i class="fa-solid fa-house-chimney nav-icon-female" style="color: #0284c7;"></i> Cổng sinh viên
        </a></li>
        <li><a href="/tkb/student/hoc_bai.php" class="nav-link <?= (basename($_SERVER['PHP_SELF'])=='hoc_bai.php'||basename($_SERVER['PHP_SELF'])=='tailieu.php')?'active':'' ?>">
            <i class="fa-solid fa-book-open-reader nav-icon-female" style="color: #ec4899;"></i> Tài liệu &amp; Bài học
        </a></li>
        <li><a href="/tkb/student/quiz.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='quiz.php'?'active':'' ?>">
            <i class="fa-solid fa-graduation-cap nav-icon-female" style="color: #10b981;"></i> Làm Quiz
        </a></li>
        <li><a href="/tkb/student/flashcard.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='flashcard.php'?'active':'' ?>">
            <i class="fa-solid fa-layer-group nav-icon-female" style="color: #f59e0b;"></i> Flashcard
        </a></li>
        <li><a href="/tkb/student/ai.php?v=<?= time() ?>" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='ai.php'?'active':'' ?>">
            <i class="fa-solid fa-wand-magic-sparkles nav-icon-female" style="color: #8b5cf6;"></i> AI hỗ trợ
        </a></li>
        <li><a href="/tkb/student/ai_advisor.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='ai_advisor.php'?'active':'' ?>" style="background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3);">
            <i class="fa-solid fa-brain nav-icon-female" style="color: #c084fc;"></i> AI Cố Vấn Năng Lực
        </a></li>
        <li><a href="/tkb/student/lam_bai_tap.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='lam_bai_tap.php'?'active':'' ?>">
            <i class="fa-solid fa-pen-ruler nav-icon-female" style="color: #ec4899;"></i> Làm bài tập
        </a></li>
        <li><a href="/tkb/student/code_ide.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='code_ide.php'?'active':'' ?>">
            <i class="fa-solid fa-code nav-icon-female" style="color: #06b6d4;"></i> Thực hành Code
        </a></li>
        <li><a href="/tkb/student/luu_tru_code.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='luu_tru_code.php'?'active':'' ?>">
            <i class="fa-solid fa-folder-code nav-icon-female" style="color: #a855f7;"></i> Kho Lưu Trữ Code
        </a></li>
        <li><a href="/tkb/student/kho_code_cong_dong.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='kho_code_cong_dong.php'?'active':'' ?>">
            <i class="fa-solid fa-globe nav-icon-female" style="color: #60a5fa;"></i> Kho Code Cộng Đồng
        </a></li>
        <li><a href="/tkb/student/doan.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='doan.php'?'active':'' ?>">
            <i class="fa-solid fa-diagram-project nav-icon-female" style="color: #3b82f6;"></i> Quản lý đồ án
        </a></li>
        <li><a href="/tkb/student/games.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='games.php'?'active':'' ?>">
            <i class="fa-solid fa-gamepad nav-icon-female" style="color: #ec4899;"></i> Trò chơi giải trí
        </a></li>
        <li><a href="/tkb/student/music.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='music.php'?'active':'' ?>">
            <i class="fa-solid fa-music nav-icon-female" style="color: #14b8a6;"></i> Nghe nhạc thư giãn
        </a></li>
        <li><a href="/tkb/student/tien_do.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='tien_do.php'?'active':'' ?>">
            <i class="fa-solid fa-chart-pie nav-icon-female" style="color: #10b981;"></i> Theo dõi tiến độ
        </a></li>
        <li><a href="/tkb/student/profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='profile.php'?'active':'' ?>">
            <img src="<?= $st_av ?>" class="mc-svg-icon" style="width: 22px; height: 22px; border-radius: 50%; object-fit: cover; border: 1.5px solid #ec4899;" alt="Avatar Hồ sơ"> Hồ sơ cá nhân
        </a></li>
        <li><a href="javascript:void(0)" onclick="openBannerManagerModal()" class="nav-link">
            <i class="fa-solid fa-image nav-icon-female" style="color: #0284c7;"></i> Đổi Banner Trang Chủ
        </a></li>
        <?php else: ?>
        <!-- =========================================================
             DANH SÁCH MENU DÀNH RIÊNG CHO SINH VIÊN NAM (GIAO DIỆN CHUẨN 100% THEO ẢNH)
             ========================================================= -->
        <li><a href="/tkb/student/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>">
            <svg class="mc-svg-icon" viewBox="0 0 24 24" fill="none"><path d="M12 2L2 7V17L12 22L22 17V7L12 2Z" fill="#5b8731"/><path d="M12 2L22 7V17L12 22V2Z" fill="#4a7227"/><path d="M12 2L2 7L12 12L22 7L12 2Z" fill="#7cbc37"/><path d="M2 7.5L12 12.5V22L2 17V7.5Z" fill="#866043"/><path d="M22 7.5L12 12.5V22L22 17V7.5Z" fill="#684832"/><path d="M2 7L12 12L22 7L20 8.5L12 10.5L4 8.5L2 7Z" fill="#5b8731"/><path d="M2 10L5 9V11L8 10.5V12.5L12 12L16 12.5V10.5L19 11V9L22 10V11L19 12.5V13.5L16 14V13L12 13.5L8 13V14L5 13.5V11.5L2 11V10Z" fill="#7cbc37"/></svg> Cổng sinh viên
        </a></li>
        <li><a href="/tkb/student/hoc_bai.php" class="nav-link <?= (basename($_SERVER['PHP_SELF'])=='hoc_bai.php'||basename($_SERVER['PHP_SELF'])=='tailieu.php')?'active':'' ?>">
            <svg class="mc-svg-icon" viewBox="0 0 24 24" fill="none"><path d="M4 4H10V19H4V4Z" fill="#f5f5f5"/><path d="M14 4H20V19H14V4Z" fill="#e0e0e0"/><path d="M2 3H4V20H2V3Z" fill="#8b0000"/><path d="M20 3H22V20H20V3Z" fill="#8b0000"/><path d="M3 2H21V4H3V2Z" fill="#b22222"/><path d="M3 19H21V21H3V19Z" fill="#b22222"/><path d="M11 4H13V20H11V4Z" fill="#7f1d1d"/><path d="M6 7H9V8H6V7ZM6 10H9V11H6V10ZM6 13H9V14H6V13ZM15 7H18V8H15V7ZM15 10H18V11H15V10ZM15 13H18V14H15V13Z" fill="#666"/></svg> Tài liệu &amp; Bài học
        </a></li>
        <li><a href="/tkb/student/quiz.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='quiz.php'?'active':'' ?>">
            <svg class="mc-svg-icon" viewBox="0 0 24 24" fill="none"><path d="M7 2H17L22 7V17L17 22H7L2 17V7L7 2Z" fill="#059669"/><path d="M8 4H16L20 8V16L16 20H8L4 16V8L8 4Z" fill="#10b981"/><path d="M9 6H15L18 9V15L15 18H9L6 15V9L9 6Z" fill="#34d399"/><path d="M10 8H14L15 9V13L14 14H10L9 13V9L10 8Z" fill="#a7f3d0"/><path d="M11 9H13V11H11V9Z" fill="#ffffff"/></svg> Làm Quiz
        </a></li>
        <li><a href="/tkb/student/flashcard.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='flashcard.php'?'active':'' ?>">
            <svg class="mc-svg-icon" viewBox="0 0 24 24" fill="none"><path d="M5 3H19V21H5V3Z" fill="#fef3c7"/><path d="M4 2H20V3H4V2ZM4 21H20V22H4V21ZM4 3H5V21H4V3ZM19 3H20V21H19V3Z" fill="#d97706"/><path d="M7 6H17V8H7V6ZM7 10H17V11H7V10ZM7 13H14V14H7V13ZM7 16H16V17H7V16Z" fill="#92400e"/><circle cx="16" cy="14" r="2" fill="#dc2626"/></svg> Flashcard
        </a></li>
        <li><a href="/tkb/student/ai.php?v=<?= time() ?>" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='ai.php'?'active':'' ?>">
            <svg class="mc-svg-icon" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" fill="#64748b"/><rect x="2" y="3" width="20" height="18" rx="3" fill="none" stroke="#334155" stroke-width="2"/><rect x="5" y="8" width="5" height="4" fill="#ef4444"/><rect x="14" y="8" width="5" height="4" fill="#ef4444"/><rect x="6" y="9" width="2" height="2" fill="#fef08a"/><rect x="15" y="9" width="2" height="2" fill="#fef08a"/><rect x="8" y="15" width="8" height="2" fill="#1e293b"/><rect x="9" y="15" width="2" height="2" fill="#94a3b8"/><rect x="13" y="15" width="2" height="2" fill="#94a3b8"/><rect x="11" y="1" width="2" height="3" fill="#3b82f6"/><circle cx="12" cy="1" r="1.5" fill="#ef4444"/></svg> AI hỗ trợ
        </a></li>
        <li><a href="/tkb/student/ai_advisor.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='ai_advisor.php'?'active':'' ?>" style="background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3);">
            <i class="fa-solid fa-brain" style="color: #c084fc; font-size: 16px; margin-right: 6px;"></i> AI Cố Vấn Năng Lực
        </a></li>
        <li><a href="/tkb/student/lam_bai_tap.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='lam_bai_tap.php'?'active':'' ?>">
            <i class="fa-solid fa-pen-ruler nav-icon-female" style="color: #ec4899; font-size: 16px; margin-right: 6px;"></i> Làm bài tập
        </a></li>
        <li><a href="/tkb/student/code_ide.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='code_ide.php'?'active':'' ?>">
            <i class="fa-solid fa-code nav-icon-female" style="color: #38bdf8;"></i> Thực hành Code
        </a></li>
        <li><a href="/tkb/student/luu_tru_code.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='luu_tru_code.php'?'active':'' ?>">
            <i class="fa-solid fa-folder-code nav-icon-female" style="color: #c084fc;"></i> Kho Lưu Trữ Code
        </a></li>
        <li><a href="/tkb/student/kho_code_cong_dong.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='kho_code_cong_dong.php'?'active':'' ?>">
            <i class="fa-solid fa-globe nav-icon-female" style="color: #60a5fa;"></i> Kho Code Cộng Đồng
        </a></li>
        <li><a href="/tkb/student/doan.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='doan.php'?'active':'' ?>">
            <i class="fa-solid fa-box-open nav-icon-female" style="color: #f59e0b;"></i> Quản lý đồ án
        </a></li>
        <li><a href="/tkb/student/games.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='games.php'?'active':'' ?>">
            <i class="fa-solid fa-gamepad nav-icon-female" style="color: #a855f7;"></i> Trò chơi giải trí
        </a></li>
        <li><a href="/tkb/student/music.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='music.php'?'active':'' ?>">
            <i class="fa-solid fa-music nav-icon-female" style="color: #ec4899;"></i> Nghe nhạc thư giãn
        </a></li>
        <li><a href="/tkb/student/tien_do.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='tien_do.php'?'active':'' ?>">
            <i class="fa-solid fa-chart-column nav-icon-female" style="color: #10b981;"></i> Theo dõi tiến độ
        </a></li>
        <li><a href="/tkb/student/profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='profile.php'?'active':'' ?>">
            <img src="<?= $st_av ?>" class="mc-svg-icon" style="width: 22px; height: 22px; border-radius: 4px; object-fit: cover; border: 1.5px solid #ec4899;" alt="Avatar Hồ sơ"> Hồ sơ cá nhân
        </a></li>
        <li><a href="javascript:void(0)" onclick="openBannerManagerModal()" class="nav-link">
            <i class="fa-solid fa-image nav-icon-female" style="color: #f59e0b;"></i> Đổi Banner Trang Chủ
        </a></li>
        <?php endif; ?>
    </ul>

    <form id="sidebarBannerForm" action="/tkb/student/dashboard.php" method="POST" enctype="multipart/form-data" style="display:none;">
        <input type="hidden" name="action" value="upload_banner">
        <input type="hidden" name="banner_base64" id="sidebarBannerBase64">
        <input type="file" id="sidebarBannerInput" name="banner_file" accept="image/*" onchange="submitSidebarBannerForm()">
    </form>

    <?php if ($st_gender === 'Nữ'): ?>
    <!-- Female Bottom Sidebar Illustration Card (100% Replica from Image 2) -->
    <div style="background: linear-gradient(135deg, #a855f7, #7c3aed); border-radius: 20px; padding: 14px; margin: 14px 12px; color: #ffffff; display: flex; align-items: center; gap: 12px; box-shadow: 0 8px 24px rgba(124, 58, 237, 0.25);">
        <img src="/tkb/assets/img/female_study.png" style="width: 48px; height: 48px; object-fit: contain; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2)); flex-shrink: 0;">
        <div style="font-size: 11px; line-height: 1.4;">
            <div style="font-weight: 800; font-size: 12px; color: #fff;">Chào mừng trở lại!</div>
            <div style="opacity: 0.9; font-size: 10px; margin-top: 2px;">Học tập chăm chỉ<br>Chinh phục ước mơ 🌸✨</div>
        </div>
    </div>
    <?php else: ?>
    <!-- Bottom Sidebar Student Info Card -->
    <div class="sidebar-st-info" style="background: rgba(139, 92, 246, 0.1) !important; border: 1.5px solid rgba(139, 92, 246, 0.25) !important; border-radius: 16px !important; padding: 14px !important; margin: 14px 12px !important; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;">
        <div class="sidebar-st-title" style="color: #c084fc !important; font-size: 10px !important; font-weight: bold !important; letter-spacing: 0.8px !important; margin-bottom: 12px !important; display: flex !important; align-items: center !important; gap: 6px !important;">
            <i class="fa-solid fa-shield-halved"></i> THÔNG TIN SINH VIÊN
        </div>
        <div class="sidebar-st-body" style="display: flex !important; align-items: center !important; gap: 14px !important;">
            <img src="<?= $st_av ?>" alt="Avatar" class="sidebar-st-avatar" style="width: 58px !important; height: 58px !important; border-radius: 12px !important; border: 2px solid #8b5cf6 !important; box-shadow: 0 0 10px rgba(139, 92, 246, 0.4) !important; object-fit: cover !important; display: block !important;">
            <div class="sidebar-st-details" style="font-size: 12px !important; color: #cbd5e1 !important; line-height: 1.5 !important;">
                <div style="color: #94a3b8;">Mã SV: <strong style="color: #ffffff; font-weight: 700;"><?= $st_user ?></strong></div>
                <div style="color: #94a3b8;">Lớp: <strong style="color: #ffffff; font-weight: 700;"><?= $st_lop ?></strong></div>
                <div style="color: #94a3b8;">Giới tính: <strong style="color: #38bdf8; font-weight: 700;"><?= $st_gender ?></strong></div>
                <div style="color: #94a3b8;">Khóa: <strong style="color: #ffffff; font-weight: 700;">2024 - 2027</strong></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</nav>
<script>
// Khôi phục vị trí cuộn thanh sidebar sinh viên NGAY LẬP TỨC trước khi vẽ giao diện (chống giật 100%)
(function() {
    try {
        var sb = document.getElementById('sidebar');
        var nl = sb ? sb.querySelector('.nav-list') : null;
        var saved = sessionStorage.getItem('st_sidebar_scroll');
        if (saved !== null) {
            var val = parseInt(saved, 10);
            if (nl) nl.scrollTop = val;
            if (sb) sb.scrollTop = val;
        } else {
            var act = sb ? sb.querySelector('.nav-link.active, a.active') : null;
            if (act) {
                var cont = (nl && nl.scrollHeight > nl.clientHeight) ? nl : sb;
                if (cont) {
                    var target = act.offsetTop - (cont.clientHeight / 2) + (act.clientHeight / 2);
                    if (nl) nl.scrollTop = Math.max(0, target);
                    if (sb) sb.scrollTop = Math.max(0, target);
                }
            }
        }
    } catch(e) {}
})();
</script>


<div class="main-content">
    <div class="top-header">
        <div class="header-left">
            <button class="sidebar-toggle" onclick="toggleSidebar()" title="Đóng / Mở thanh menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <?php if ($st_gender === 'Nữ'): ?>
                <i class="fa-solid fa-graduation-cap" style="color: #ec4899; font-size: 18px; margin-right: 8px;"></i> <span class="st-header-title-text" style="font-family: 'Outfit', 'Inter', sans-serif; font-weight: 800; letter-spacing: 0.5px;">CỔNG THÔNG TIN SINH VIÊN</span>
            <?php else: ?>
                <i class="fa-solid fa-cube"></i> <span class="st-header-title-text" style="font-family: 'Outfit', 'Inter', sans-serif; font-weight: 800; letter-spacing: 0.5px;">CỔNG THÔNG TIN SINH VIÊN</span>
            <?php endif; ?>
        </div>
        <div class="header-right">
            <!-- Nút chuyển đổi Chế độ Sáng / Tối -->
            <button type="button" class="btn-theme-toggle" id="btnThemeToggle" onclick="toggleStudentDarkMode()" title="Chuyển đổi giao diện Sáng / Tối">
                <i class="fa-solid fa-moon" id="themeToggleIcon" style="color: #6366f1;"></i>
                <span id="themeToggleText">Chế độ tối</span>
            </button>

            <div class="header-bell">
                <i class="fa-solid fa-bell"></i>
                <span class="header-bell-badge">3</span>
            </div>
            <div class="user-profile">
                <img src="<?= $st_av ?>" alt="Avatar" class="user-avatar">
                <div class="user-info">
                    <div class="u-name"><?= $st_name ?></div>
                    <div class="u-id"><?= $st_user ?></div>
                </div>
                <a href="/tkb/api/logout.php" class="btn-logout-header"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </div>
        </div>
    </div>
    <div class="content-pad">

    <!-- Theme Switcher Modal UI -->
    <div class="mc-theme-modal-overlay" id="mcThemeOverlay" onclick="toggleThemeModal()"></div>
    <div class="mc-theme-modal" id="mcThemeModal">
        <div class="mc-theme-modal-header">
            <div>
                <h3><i class="fa-solid fa-palette"></i> BỘ SƯU TẬP TONE MÀU GIAO DIỆN</h3>
                <p>Chọn tone màu Minecraft yêu thích cho giao diện của bạn</p>
            </div>
            <button class="mc-theme-close" onclick="toggleThemeModal()">&times;</button>
        </div>

        <!-- Section 0: Web Color Mode (Light / Dark Mode) -->
        <div style="margin-bottom: 16px; padding-bottom: 14px; border-bottom: 2px dashed var(--mc-border);">
            <div style="font-family: var(--mc-pixel-font); font-size: 11px; color: var(--mc-text); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-sun" style="color: var(--mc-red);"></i> CHẾ ĐỘ NỀN WEB (THEME MODE)
            </div>
            <div class="mc-mode-grid">
                <div class="mc-mode-btn" data-mode="dark" onclick="setStudentWebMode('dark')">
                    <i class="fa-solid fa-moon"></i> <span>Tối Minecraft (Dark)</span>
                </div>
                <div class="mc-mode-btn" data-mode="light" onclick="setStudentWebMode('light')">
                    <i class="fa-solid fa-sun"></i> <span>Sáng Tinh Tế (Light)</span>
                </div>
            </div>
        </div>
        <div class="mc-theme-grid">
            <div class="mc-theme-item" data-theme="red" onclick="setStudentTheme('red')">
                <div class="mc-theme-preview" style="background: linear-gradient(135deg, #0b090e 50%, #ff1a40 50%); border-color: #ff1a40;">
                    <span class="mc-theme-dot" style="background: #ff1a40;"></span>
                </div>
                <div class="mc-theme-info">
                    <strong>Đỏ Nether</strong>
                    <span>Mặc định huyền bí</span>
                </div>
                <i class="fa-solid fa-circle-check mc-theme-check"></i>
            </div>
            <div class="mc-theme-item" data-theme="cyan" onclick="setStudentTheme('cyan')">
                <div class="mc-theme-preview" style="background: linear-gradient(135deg, #061118 50%, #00d9ff 50%); border-color: #00d9ff;">
                    <span class="mc-theme-dot" style="background: #00d9ff;"></span>
                </div>
                <div class="mc-theme-info">
                    <strong>Xanh Diamond</strong>
                    <span>Kim cương tươi mát</span>
                </div>
                <i class="fa-solid fa-circle-check mc-theme-check"></i>
            </div>
            <div class="mc-theme-item" data-theme="emerald" onclick="setStudentTheme('emerald')">
                <div class="mc-theme-preview" style="background: linear-gradient(135deg, #06140d 50%, #10b981 50%); border-color: #10b981;">
                    <span class="mc-theme-dot" style="background: #10b981;"></span>
                </div>
                <div class="mc-theme-info">
                    <strong>Xanh Emerald</strong>
                    <span>Lục bảo rực rỡ</span>
                </div>
                <i class="fa-solid fa-circle-check mc-theme-check"></i>
            </div>
            <div class="mc-theme-item" data-theme="purple" onclick="setStudentTheme('purple')">
                <div class="mc-theme-preview" style="background: linear-gradient(135deg, #0f0717 50%, #a855f7 50%); border-color: #a855f7;">
                    <span class="mc-theme-dot" style="background: #a855f7;"></span>
                </div>
                <div class="mc-theme-info">
                    <strong>Tím Ender</strong>
                    <span>Huyền thoại Rồng Ender</span>
                </div>
                <i class="fa-solid fa-circle-check mc-theme-check"></i>
            </div>
            <div class="mc-theme-item" data-theme="gold" onclick="setStudentTheme('gold')">
                <div class="mc-theme-preview" style="background: linear-gradient(135deg, #140f06 50%, #eab308 50%); border-color: #eab308;">
                    <span class="mc-theme-dot" style="background: #eab308;"></span>
                </div>
                <div class="mc-theme-info">
                    <strong>Vàng Kim Hoàng Gia</strong>
                    <span>Vàng thỏi lấp lánh</span>
                </div>
                <i class="fa-solid fa-circle-check mc-theme-check"></i>
            </div>
            <div class="mc-theme-item" data-theme="blue" onclick="setStudentTheme('blue')">
                <div class="mc-theme-preview" style="background: linear-gradient(135deg, #060b14 50%, #3b82f6 50%); border-color: #3b82f6;">
                    <span class="mc-theme-dot" style="background: #3b82f6;"></span>
                </div>
                <div class="mc-theme-info">
                    <strong>Xanh Biển Thẫm</strong>
                    <span>Đại dương thẳm sâu</span>
                </div>
                <i class="fa-solid fa-circle-check mc-theme-check"></i>
            </div>
            <div class="mc-theme-item" data-theme="pink" onclick="setStudentTheme('pink')">
                <div class="mc-theme-preview" style="background: linear-gradient(135deg, #14070e 50%, #f43f5e 50%); border-color: #f43f5e;">
                    <span class="mc-theme-dot" style="background: #f43f5e;"></span>
                </div>
                <div class="mc-theme-info">
                    <strong>Hồng Ruby Cyber</strong>
                    <span>Ngọt ngào & Nổi bật</span>
                </div>
                <i class="fa-solid fa-circle-check mc-theme-check"></i>
            </div>
            <div class="mc-theme-item" data-theme="orange" onclick="setStudentTheme('orange')">
                <div class="mc-theme-preview" style="background: linear-gradient(135deg, #140a06 50%, #f97316 50%); border-color: #f97316;">
                    <span class="mc-theme-dot" style="background: #f97316;"></span>
                </div>
                <div class="mc-theme-info">
                    <strong>Cam Nham Thạch</strong>
                    <span>Lửa Lava cháy bỏng</span>
                </div>
                <i class="fa-solid fa-circle-check mc-theme-check"></i>
            </div>
        </div>

        <!-- Section 2: Màu Nền Background -->
        <div style="margin-top: 20px; padding-top: 16px; border-top: 2px dashed var(--mc-border);">
            <div style="font-family: var(--mc-pixel-font); font-size: 11px; color: #ffffff; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-image" style="color: var(--mc-red);"></i> MÀU NỀN GIAO DIỆN (BACKGROUND)
            </div>
            <div class="mc-bg-grid">
                <div class="mc-bg-item" data-bg="dark" onclick="setStudentBg('dark')">
                    <span class="mc-bg-swatch" style="background: #0b090e; border-color: #ff1a40;"></span>
                    <span>Bóng Đêm</span>
                </div>
                <div class="mc-bg-item" data-bg="obsidian" onclick="setStudentBg('obsidian')">
                    <span class="mc-bg-swatch" style="background: #12091c; border-color: #a855f7;"></span>
                    <span>Tím Void</span>
                </div>
                <div class="mc-bg-item" data-bg="navy" onclick="setStudentBg('navy')">
                    <span class="mc-bg-swatch" style="background: #08101e; border-color: #00d9ff;"></span>
                    <span>Xanh Đêm</span>
                </div>
                <div class="mc-bg-item" data-bg="forest" onclick="setStudentBg('forest')">
                    <span class="mc-bg-swatch" style="background: #07170f; border-color: #10b981;"></span>
                    <span>Rừng Đêm</span>
                </div>
                <div class="mc-bg-item" data-bg="lava" onclick="setStudentBg('lava')">
                    <span class="mc-bg-swatch" style="background: #18080a; border-color: #f97316;"></span>
                    <span>Nham Thạch</span>
                </div>
                <div class="mc-bg-item" data-bg="charcoal" onclick="setStudentBg('charcoal')">
                    <span class="mc-bg-swatch" style="background: #121214; border-color: #94a3b8;"></span>
                    <span>Xám Than</span>
                </div>
            </div>
        </div>
    </div>

<script>
function toggleSidebar() {
    var isDesktop = window.innerWidth > 992;
    if (isDesktop) {
        document.body.classList.toggle('sidebar-collapsed');
        var collapsed = document.body.classList.contains('sidebar-collapsed');
        localStorage.setItem('student_sidebar_collapsed', collapsed ? '1' : '0');
    } else {
        const sb = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sb) sb.classList.toggle('active');
        if (overlay) overlay.classList.toggle('active');
    }
}

function triggerSidebarBannerUpload() {
    document.getElementById('sidebarBannerInput').click();
}

function submitSidebarBannerForm() {
    const input = document.getElementById('sidebarBannerInput');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('sidebarBannerBase64').value = e.target.result;
            document.getElementById('sidebarBannerForm').submit();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleThemeModal() {
    const modal = document.getElementById('mcThemeModal');
    const overlay = document.getElementById('mcThemeOverlay');
    modal.classList.toggle('active');
    overlay.classList.toggle('active');
    updateThemeModalUI();
}

function setStudentTheme(theme) {
    document.documentElement.setAttribute('data-mc-theme', theme);
    document.body.setAttribute('data-mc-theme', theme);
    localStorage.setItem('student_mc_theme', theme);
    updateThemeModalUI();
}

function setStudentBg(bg) {
    document.documentElement.setAttribute('data-mc-bg', bg);
    document.body.setAttribute('data-mc-bg', bg);
    localStorage.setItem('student_mc_bg', bg);
    updateThemeModalUI();
}

var isScreenTransitioning = false;

function toggleStudentDarkMode() {
    if (isScreenTransitioning) return;
    
    var current = document.documentElement.getAttribute('data-theme-mode') === 'dark' ? 'dark' : 'light';
    var next = current === 'dark' ? 'light' : 'dark';
    
    playScreenTransition(next, function() {
        document.documentElement.setAttribute('data-theme-mode', next);
        if (document.body) document.body.setAttribute('data-theme-mode', next);
        localStorage.setItem('student_dark_mode', next);
        updateDarkModeButtonUI(next);
    });
}

function playScreenTransition(targetMode, onClosedCallback) {
    isScreenTransitioning = true;
    var curtain = document.getElementById('screenCurtain');
    var topDark = document.getElementById('curtainTopDark');
    var topLight = document.getElementById('curtainTopLight');
    var botDark = document.getElementById('curtainBottomDark');
    var botLight = document.getElementById('curtainBottomLight');
    
    if (!curtain) {
        if (onClosedCallback) onClosedCallback();
        isScreenTransitioning = false;
        return;
    }
    
    var themeClass = targetMode === 'light' ? 'theme-light' : 'theme-dark';
    
    // Kích hoạt layer ảnh tương ứng cho cả nửa trên và nửa dưới
    if (targetMode === 'dark') {
        if (topDark) topDark.classList.add('active');
        if (topLight) topLight.classList.remove('active');
        if (botDark) botDark.classList.add('active');
        if (botLight) botLight.classList.remove('active');
    } else {
        if (topLight) topLight.classList.add('active');
        if (topDark) topDark.classList.remove('active');
        if (botLight) botLight.classList.add('active');
        if (botDark) botDark.classList.remove('active');
    }
    
    // 1. Đóng màn hình full screen (hai nửa cánh rèm khép lại ở giữa)
    curtain.className = 'screen-curtain-wrap closing ' + themeClass;
    
    // 2. Khi màn hình vừa đóng kín hoàn toàn (420ms) -> kích hoạt đổi theme ngầm
    setTimeout(function() {
        if (onClosedCallback) onClosedCallback();
        
        // 3. Giữ trọn bức tranh nghệ thuật full màn hình trong 900ms để chiêm ngưỡng
        setTimeout(function() {
            // 4. Mở màn hình ra (hai nửa cánh rèm tách ra trên và dưới)
            curtain.className = 'screen-curtain-wrap opening ' + themeClass;
            
            // 5. Kết thúc hiệu ứng sau khi rèm mở hoàn tất
            setTimeout(function() {
                curtain.className = 'screen-curtain-wrap';
                isScreenTransitioning = false;
            }, 460);
        }, 900);
    }, 420);
}

function updateDarkModeButtonUI(mode) {
    var btn = document.getElementById('btnThemeToggle');
    var icon = document.getElementById('themeToggleIcon');
    var text = document.getElementById('themeToggleText');
    if (!btn || !icon || !text) return;
    
    if (mode === 'dark') {
        icon.className = 'fa-solid fa-sun';
        icon.style.color = '#fbbf24';
        text.textContent = 'Chế độ sáng';
        btn.classList.add('is-dark');
        btn.title = 'Chuyển sang Chế độ Sáng';
    } else {
        icon.className = 'fa-solid fa-moon';
        icon.style.color = '#6366f1';
        text.textContent = 'Chế độ tối';
        btn.classList.remove('is-dark');
        btn.title = 'Chuyển sang Chế độ Tối';
    }
}

function setStudentWebMode(mode) {
    if (isScreenTransitioning) return;
    playScreenTransition(mode, function() {
        document.documentElement.setAttribute('data-theme-mode', mode);
        if (document.body) document.body.setAttribute('data-theme-mode', mode);
        localStorage.setItem('student_dark_mode', mode);
        updateDarkModeButtonUI(mode);
        updateThemeModalUI();
    });
}

function updateThemeModalUI() {
    const currentTheme = localStorage.getItem('student_mc_theme') || 'red';
    const currentBg = localStorage.getItem('student_mc_bg') || 'dark';
    const currentMode = localStorage.getItem('student_mc_mode') || 'dark';

    document.querySelectorAll('.mc-theme-item').forEach(el => {
        if (el.getAttribute('data-theme') === currentTheme) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });

    document.querySelectorAll('.mc-bg-item').forEach(el => {
        if (el.getAttribute('data-bg') === currentBg) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });

    document.querySelectorAll('.mc-mode-btn').forEach(el => {
        if (el.getAttribute('data-mode') === currentMode) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
}

function toggleStudentFullscreen() {
    if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement && !document.msFullscreenElement) {
        var docEl = document.documentElement;
        if (docEl.requestFullscreen) {
            docEl.requestFullscreen().catch(function(e) {});
        } else if (docEl.webkitRequestFullscreen) {
            docEl.webkitRequestFullscreen();
        } else if (docEl.mozRequestFullScreen) {
            docEl.mozRequestFullScreen();
        } else if (docEl.msRequestFullscreen) {
            docEl.msRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(function(e) {});
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }
}

function updateFullscreenUI() {
    var isFull = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
    var icon = document.getElementById('fullscreenIcon');
    var text = document.getElementById('fullscreenText');
    var btn = document.getElementById('btnFullscreenToggle');
    if (!icon || !btn) return;
    
    if (isFull) {
        icon.className = 'fa-solid fa-compress';
        if (text) text.textContent = 'Thu nhỏ';
        btn.title = 'Đóng toàn màn hình (Thu nhỏ)';
    } else {
        icon.className = 'fa-solid fa-expand';
        if (text) text.textContent = 'Toàn màn hình';
        btn.title = 'Mở toàn màn hình';
    }
}

document.addEventListener('fullscreenchange', updateFullscreenUI);
document.addEventListener('webkitfullscreenchange', updateFullscreenUI);
document.addEventListener('mozfullscreenchange', updateFullscreenUI);
document.addEventListener('MSFullscreenChange', updateFullscreenUI);

document.addEventListener('DOMContentLoaded', function() {
    var sb = document.getElementById('sidebar');
    if (!sb) return;
    var nl = sb.querySelector('.nav-list');
    var cont = (nl && (nl.scrollHeight > nl.clientHeight || window.getComputedStyle(nl).overflowY === 'auto' || window.getComputedStyle(nl).overflowY === 'scroll')) ? nl : sb;
    var savedScroll = sessionStorage.getItem('st_sidebar_scroll');
    var activeItem = sb.querySelector('.nav-link.active, a.active');

    function applyScroll(val) {
        if (nl) nl.scrollTop = val;
        if (sb) sb.scrollTop = val;
    }

    if (savedScroll !== null) {
        var val = parseInt(savedScroll, 10);
        applyScroll(val);
        if (activeItem && cont) {
            var itemTop = activeItem.offsetTop;
            var itemBottom = itemTop + activeItem.clientHeight;
            var viewTop = cont.scrollTop;
            var viewBottom = viewTop + cont.clientHeight;
            if (itemTop < viewTop || itemBottom > viewBottom) {
                var target = Math.max(0, itemTop - (cont.clientHeight / 2) + (activeItem.clientHeight / 2));
                applyScroll(target);
                sessionStorage.setItem('st_sidebar_scroll', target);
            }
        }
    } else if (activeItem && cont) {
        var target = Math.max(0, activeItem.offsetTop - (cont.clientHeight / 2) + (activeItem.clientHeight / 2));
        applyScroll(target);
        sessionStorage.setItem('st_sidebar_scroll', target);
    }

    function recordStudentScroll() {
        var val = (nl && nl.scrollTop > 0) ? nl.scrollTop : sb.scrollTop;
        sessionStorage.setItem('st_sidebar_scroll', val);
    }

    var scrollTicking = false;
    var onScrollHandler = function() {
        if (!scrollTicking) {
            window.requestAnimationFrame(function() {
                recordStudentScroll();
                scrollTicking = false;
            });
            scrollTicking = true;
        }
    };

    sb.addEventListener('scroll', onScrollHandler, { passive: true });
    if (nl) nl.addEventListener('scroll', onScrollHandler, { passive: true });

    sb.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', recordStudentScroll);
    });
});
</script>
<!-- 🌸 Hiệu ứng Hoa Anh Đào Rơi Tự Nhiên (Sakura Falling Canvas Engine) 🌸 -->
<script src="/tkb/assets/sakura_fall.js?v=<?= time() ?>" defer></script>