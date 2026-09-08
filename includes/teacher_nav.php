<?php
$_nav_db = getDB();
$teacher_uid = (int)($_SESSION['user_id'] ?? 0);
$teacher_info = $_nav_db->query("SELECT u.avatar, g.ho_ten, g.ma_gv, g.khoa FROM users u JOIN giang_vien g ON g.user_id = u.id WHERE u.id = $teacher_uid")->fetch_assoc();
$_nav_db->close();

$is_admin_mode = isAdmin();
$t_av   = !empty($teacher_info['avatar']) ? '/tkb/assets/img/avatars/' . htmlspecialchars($teacher_info['avatar']) : ($is_admin_mode ? '/tkb/assets/img/avatar_khanh.png' : '');
$t_name = !empty($teacher_info['ho_ten']) ? $teacher_info['ho_ten'] : ($is_admin_mode ? ($_SESSION['ho_ten'] ?? 'Quản Trị Viên (Admin)') : ($_SESSION['ho_ten'] ?? 'Giảng viên'));
$t_code = !empty($teacher_info['ma_gv']) ? $teacher_info['ma_gv'] : ($is_admin_mode ? 'ADMIN (Toàn quyền)' : 'GV');
$cur    = basename($_SERVER['PHP_SELF']);

function tp_nav_active($file) {
    return basename($_SERVER['PHP_SELF']) === $file ? 'active' : '';
}
?>
<link rel="stylesheet" href="/tkb/assets/teacher_portal.css">

<!-- Overlay -->
<div class="tp-overlay" id="tp-overlay" onclick="tpToggleSidebar()"></div>

<!-- Mobile Topbar -->
<div class="tp-mobile-topbar">
  <button class="tp-ham" onclick="tpToggleSidebar()">
    <span></span><span></span><span></span>
  </button>
  <span class="tp-mobile-title"><?= $is_admin_mode ? 'Admin - Giảng Dạy &amp; Điểm' : 'Hệ thống Giảng viên' ?></span>
  <a href="/tkb/teacher/profile.php" class="tp-topbar-btn">
    <i class="fa-solid fa-user"></i>
  </a>
</div>

<!-- Sidebar -->
<nav class="tp-sidebar" id="tp-sidebar">

  <!-- Logo -->
  <div class="tp-sidebar-logo">
    <div class="tp-logo-icon" style="<?= $is_admin_mode ? 'background: linear-gradient(135deg, #a855f7, #7c3aed);' : '' ?>"><i class="fa-solid fa-chalkboard-user"></i></div>
    <div>
      <div class="tp-logo-name">CĐ KT&amp;CN</div>
      <div class="tp-logo-sub"><?= $is_admin_mode ? 'Quản Trị / Giảng Dạy' : 'Giảng Viên' ?></div>
    </div>
  </div>

  <?php if ($is_admin_mode): ?>
  <div style="margin: 0 12px 10px; padding: 6px 12px; background: rgba(168,85,247,0.12); border: 1px solid rgba(168,85,247,0.3); border-radius: 10px; font-size: 11px; color: #7c3aed; font-weight: 700; display: flex; align-items: center; justify-content: space-between;">
    <span><i class="fa-solid fa-shield-halved"></i> Quyền Admin xem tất cả điểm</span>
    <a href="/tkb/admin/dashboard.php" style="color: #7c3aed; text-decoration: none; font-size: 10.5px; font-weight: 800;"><i class="fa-solid fa-arrow-left"></i> Admin</a>
  </div>
  <?php endif; ?>

  <!-- User profile -->
  <div class="tp-sidebar-user">
    <div class="tp-user-av">
      <?php if ($t_av): ?>
        <img src="<?= $t_av ?>" alt="avatar">
      <?php else: ?>
        <i class="fa-solid fa-user-tie"></i>
      <?php endif; ?>
    </div>
    <div style="flex:1;min-width:0;">
      <div class="tp-user-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($t_name) ?></div>
      <div class="tp-user-role">
        <span class="tp-user-dot" style="<?= $is_admin_mode ? 'background:#a855f7;' : '' ?>"></span>
        <?= htmlspecialchars($t_code) ?>
      </div>
    </div>
  </div>

  <!-- Main Nav -->
  <div class="tp-nav-section">
    <div class="tp-nav-label">Tổng quan</div>
    <ul class="tp-nav-list">
      <li>
        <a href="/tkb/teacher/dashboard.php" class="<?= tp_nav_active('dashboard.php') ?>">
          <span class="tp-nav-icon" style="color:<?= $cur==='dashboard.php' ? '' : '#6366f1' ?>"><i class="fa-solid fa-gauge-high"></i></span>
          <span>Dashboard</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/thongbao.php" class="<?= tp_nav_active('thongbao.php') ?>">
          <span class="tp-nav-icon" style="color:<?= $cur==='thongbao.php' ? '' : '#e11d48' ?>"><i class="fa-solid fa-bullhorn"></i></span>
          <span>Thông báo &amp; Lịch thi</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/baocao_tien_do.php" class="<?= tp_nav_active('baocao_tien_do.php') ?>">
          <span class="tp-nav-icon" style="color:<?= $cur==='baocao_tien_do.php' ? '' : '#818cf8' ?>"><i class="fa-solid fa-chart-line"></i></span>
          <span>Báo cáo tiến độ (DA)</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/ai_analytics.php" class="<?= tp_nav_active('ai_analytics.php') ?>" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.12), rgba(56, 189, 248, 0.08)); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 10px; margin: 4px 0;">
          <span class="tp-nav-icon" style="color:#c084fc"><i class="fa-solid fa-brain"></i></span>
          <span style="font-weight: 700; color: #c084fc;">AI Analytics Hub</span>
          <span style="background: #8b5cf6; color: #fff; font-size: 9px; padding: 2px 6px; border-radius: 10px; margin-left: auto; font-weight: 800;">PRO</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="tp-nav-divider"></div>

  <!-- Teaching Nav -->
  <div class="tp-nav-section">
    <div class="tp-nav-label">Giảng dạy</div>
    <ul class="tp-nav-list">
      <li>
        <a href="/tkb/teacher/quanlylop.php" class="<?= tp_nav_active('quanlylop.php') ?>">
          <span class="tp-nav-icon" style="color:#7c3aed"><i class="fa-solid fa-graduation-cap"></i></span>
          <span>Quản lý lớp</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/baitap.php" class="<?= tp_nav_active('baitap.php') ?>">
          <span class="tp-nav-icon" style="color:#0284c7"><i class="fa-solid fa-pen-to-square"></i></span>
          <span>Giao bài tập</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/quanly_thuchanh.php" class="<?= tp_nav_active('quanly_thuchanh.php') ?>">
          <span class="tp-nav-icon" style="color:#059669"><i class="fa-solid fa-code"></i></span>
          <span>Quản lý thực hành</span>
        </a>
      </li>
      <li>
        <a href="/tkb/student/kho_code_cong_dong.php" class="<?= tp_nav_active('kho_code_cong_dong.php') ?>">
          <span class="tp-nav-icon" style="color:#60a5fa"><i class="fa-solid fa-globe"></i></span>
          <span>Kho Code Cộng Đồng</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/quiz.php" class="<?= tp_nav_active('quiz.php') ?>">
          <span class="tp-nav-icon" style="color:#7c3aed"><i class="fa-solid fa-brain"></i></span>
          <span>Quiz</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/diemdanh.php" class="<?= tp_nav_active('diemdanh.php') ?>">
          <span class="tp-nav-icon" style="color:#d97706"><i class="fa-solid fa-user-check"></i></span>
          <span>Điểm danh</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/tailieu.php" class="<?= tp_nav_active('tailieu.php') ?>">
          <span class="tp-nav-icon" style="color:#0284c7"><i class="fa-solid fa-folder-open"></i></span>
          <span>Tài liệu</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/doan.php" class="<?= tp_nav_active('doan.php') ?>">
          <span class="tp-nav-icon" style="color:#6366f1"><i class="fa-solid fa-file-code"></i></span>
          <span>Đồ án</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="tp-nav-divider"></div>

  <!-- Extra Nav -->
  <div class="tp-nav-section">
    <div class="tp-nav-label">Khác</div>
    <ul class="tp-nav-list">
      <li>
        <a href="/tkb/teacher/quanly_nhac.php" class="<?= tp_nav_active('quanly_nhac.php') ?>">
          <span class="tp-nav-icon" style="color:#ec4899"><i class="fa-solid fa-music"></i></span>
          <span>Quản lý Âm nhạc</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/profile.php" class="<?= tp_nav_active('profile.php') ?>">
          <span class="tp-nav-icon" style="color:#64748b"><i class="fa-solid fa-user"></i></span>
          <span>Hồ sơ cá nhân</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="tp-nav-divider"></div>

  <!-- Admin section -->
  <div class="tp-nav-section">
    <div class="tp-nav-label"><i class="fa-solid fa-shield-halved" style="margin-right:4px;opacity:.6;"></i>Quản trị</div>
    <ul class="tp-nav-list">
      <li>
        <a href="/tkb/teacher/quanly_sinhvien.php" class="<?= tp_nav_active('quanly_sinhvien.php') ?>">
          <span class="tp-nav-icon" style="color:#059669"><i class="fa-solid fa-users"></i></span>
          <span>Sinh viên</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/quanly_giangvien.php" class="<?= tp_nav_active('quanly_giangvien.php') ?>">
          <span class="tp-nav-icon" style="color:#6366f1"><i class="fa-solid fa-chalkboard-user"></i></span>
          <span>Giảng viên</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/quanly_monhoc.php" class="<?= tp_nav_active('quanly_monhoc.php') ?>">
          <span class="tp-nav-icon" style="color:#0284c7"><i class="fa-solid fa-book"></i></span>
          <span>Môn học</span>
        </a>
      </li>
      <li>
        <a href="/tkb/teacher/nhatky.php" class="<?= tp_nav_active('nhatky.php') ?>">
          <span class="tp-nav-icon" style="color:#64748b"><i class="fa-solid fa-clock-rotate-left"></i></span>
          <span>Nhật ký</span>
        </a>
      </li>
    </ul>
  </div>

  <!-- Logout -->
  <div class="tp-sidebar-bottom">
    <a href="/tkb/api/logout.php" class="tp-btn-logout" onclick="return confirm('Bạn có chắc muốn đăng xuất?')">
      <i class="fa-solid fa-right-from-bracket"></i>
      Đăng xuất
    </a>
  </div>
</nav>

<script>
function tpToggleSidebar() {
  const sidebar = document.getElementById('tp-sidebar');
  const overlay = document.getElementById('tp-overlay');
  sidebar.classList.toggle('open');
  overlay.classList.toggle('active');
}
</script>

<div class="tp-main">
