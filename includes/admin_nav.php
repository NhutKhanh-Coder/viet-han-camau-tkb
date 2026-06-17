<!-- Admin Nav -->
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['role']) && $_SESSION['role'] === 'principal') {
    include __DIR__ . '/principal_nav.php';
    return;
}
?>
<style>
/* Admin Sidebar Dark Theme & Global Dark Mode for Admin Portal */
body.admin-portal {
  --bg: #090d16;       /* Deep dark background */
  --bg2: #111827;      /* Dark slate cards */
  --bg3: #1f2937;      /* Dark inputs and headers */
  --border: rgba(255, 255, 255, 0.08);
  --text: #f3f4f6;     /* Light text */
  --text2: #9ca3af;    /* Muted text */
  --shadow-sm: 0 8px 24px rgba(0, 0, 0, 0.2);
  --shadow-md: 0 16px 40px rgba(0, 0, 0, 0.3);
  --shadow-lg: 0 24px 64px rgba(0, 0, 0, 0.4);
  background: var(--bg) !important;
  color: var(--text) !important;
}

body.admin-portal td {
  color: #e2e8f0 !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04) !important;
}

body.admin-portal tr:hover td {
  background: rgba(255, 255, 255, 0.02) !important;
}

body.admin-portal th {
  color: var(--accent) !important;
  background: rgba(255, 255, 255, 0.02) !important;
  border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
}

body.admin-portal .tkb-table th {
  background: rgba(255, 255, 255, 0.02) !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  color: var(--accent) !important;
}

body.admin-portal .tkb-table td {
  background: var(--bg2) !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
}

body.admin-portal .tiet-col {
  background: rgba(255, 255, 255, 0.01) !important;
  color: var(--text) !important;
  border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
}

body.admin-portal select.form-select option {
  background: #111827 !important;
  color: #f3f4f6 !important;
}

body.admin-portal .stat-card {
  border: 1px solid var(--border) !important;
  background: var(--bg2) !important;
}

body.admin-portal .btn-ghost {
  background: var(--bg3) !important;
  color: var(--text2) !important;
  border: 1px solid var(--border) !important;
}
body.admin-portal .btn-ghost:hover {
  background: var(--bg2) !important;
  color: var(--text) !important;
}

body.admin-portal .modal-box {
  background: var(--bg2) !important;
  border: 1px solid var(--border) !important;
  box-shadow: var(--shadow-lg) !important;
}
body.admin-portal .modal-title {
  color: var(--text) !important;
}
body.admin-portal .form-label {
  color: var(--text2) !important;
}

/* Chatbot dark styling for Admin */
body.admin-portal .cbox {
  background: rgba(17, 24, 39, 0.85) !important;
  border: 1px solid var(--border) !important;
}
body.admin-portal .cmsgs {
  background: rgba(15, 23, 42, 0.6) !important;
}
body.admin-portal .cbubble {
  color: var(--text) !important;
}
body.admin-portal .cmsg.bot .cbubble {
  background: var(--bg3) !important;
  color: var(--text) !important;
  border: 1px solid var(--border) !important;
}
body.admin-portal .cinput {
  background: var(--bg3) !important;
  color: var(--text) !important;
  border: 1px solid var(--border) !important;
}
body.admin-portal .cinput:focus {
  background: var(--bg2) !important;
}
body.admin-portal .cinrow {
  border-top: 1px solid var(--border) !important;
  background: rgba(17, 24, 39, 0.5) !important;
}

::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: #0a0f1d; }
::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 3px; }

/* Sidebar dark design */
.sidebar {
    position: fixed;
    top: 0; left: 0;
    width: 260px; height: 100%;
    background: #0f172a;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    flex-direction: column;
    z-index: 200;
    overflow-y: auto;
    transition: transform 0.3s ease;
}

.admin-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    z-index: 199;
}
.admin-overlay.active { display: block; }

/* Logo row */
.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 16px 14px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(217, 27, 67, 0.03);
}
.logo-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    background: var(--accent);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px; flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(217, 27, 67, 0.25);
}
.logo-name { font-family: 'Playfair Display', serif; font-size: 15px; font-weight: 800; color: #ffffff; }
.logo-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

/* User cards */
.sidebar-users { padding: 14px 12px 8px; display: flex; flex-direction: column; gap: 8px; }
.sidebar-user {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px;
}
.user-av {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: var(--accent);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.user-av img { width: 100%; height: 100%; object-fit: cover; }
.user-av i { font-size:14px; color:#fff; }
.user-name { font-size: 13.5px; font-weight: 600; color: #ffffff; }
.user-role { font-size: 11px; color: #94a3b8; margin-top: 3px; display: flex; align-items: center; gap: 5px; }
.dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; flex-shrink: 0; }

/* Nav list */
.nav-list { list-style: none; padding: 10px 0; flex: 1; }
.nav-list li a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 18px;
    font-size: 14px;
    color: #94a3b8;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: 0.15s;
    font-weight: 600;
}
.nav-list li a i { width: 18px; text-align: center; font-size: 15px; }
.nav-list li a:hover { background: rgba(217, 27, 67, 0.1); color: #ffffff; }
.nav-link.active {
    background: linear-gradient(90deg, rgba(217, 27, 67, 0.15), transparent) !important;
    border-left: 3px solid var(--accent) !important;
    color: #ffffff !important;
}

/* Bottom logout */
.nav-bottom { padding: 14px 16px; border-top: 1px solid rgba(255, 255, 255, 0.08); }
.btn-logout {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px;
    background: rgba(248, 113, 113, 0.05);
    border: 1px solid rgba(248, 113, 113, 0.1);
    border-radius: 10px;
    color: #f87171;
    font-size: 13.5px; font-weight: 600;
    text-decoration: none;
    transition: 0.15s;
}
.btn-logout:hover {
    background: rgba(248, 113, 113, 0.15);
    color: #fca5a5;
    transform: translateY(-1px);
}

/* Mobile topbar */
.admin-topbar {
    display: none;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #0f172a;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    position: sticky;
    top: 0; z-index: 50;
    box-shadow: 0 2px 10px rgba(0,0,0,0.02);
}
.admin-topbar-ham {
    display: flex; flex-direction: column; gap: 5px;
    cursor: pointer; background: none; border: none; padding: 4px;
}
.admin-topbar-ham span { display: block; width: 22px; height: 3px; background: #ffffff; border-radius: 3px; }
.admin-topbar-title { color: #ffffff; font-weight: 800; font-size: 14px; flex: 1; letter-spacing: 0.5px; }

/* Main */
.main-content { margin-left: 260px; min-height: 100vh; background: var(--bg); }

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .main-content { margin-left: 0 !important; width: 100%; }
    .admin-topbar { display: flex; }
    body { display: block !important; overflow-x: hidden; }
}
</style>

<?php
  $_nav_db = getDB();
  $admins = $_nav_db->query("SELECT id, username, ho_ten, avatar FROM users WHERE role='admin' ORDER BY id ASC LIMIT 2")->fetch_all(MYSQLI_ASSOC);
  $_nav_db->close();
?>

<div class="admin-overlay" id="adminOverlay" onclick="toggleAdminSidebar()"></div>

<nav class="sidebar" id="adminSidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <div class="logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
    <div>
      <div class="logo-name">CAO ĐẲNG KT&CN</div>
      <div class="logo-sub">Hệ thống Quản trị</div>
    </div>
  </div>

  <!-- Admin users -->
  <div class="sidebar-users">
    <?php foreach ($admins as $index => $adm):
        $av = !empty($adm['avatar']) ? '/tkb/assets/img/avatars/' . htmlspecialchars($adm['avatar']) : '';
        $name = !empty($adm['ho_ten']) ? $adm['ho_ten'] : ('Quản Trị Viên ' . ($index + 1));
    ?>
    <div class="sidebar-user">
      <div class="user-av">
        <?php if ($av): ?>
          <img src="<?= $av ?>" alt="avatar">
        <?php else: ?>
          <i class="fa-solid fa-user-shield" style="font-size:15px;color:#fff;"></i>
        <?php endif; ?>
      </div>
      <div>
        <div class="user-name"><?= htmlspecialchars($name) ?></div>
        <div class="user-role"><span class="dot"></span> Quản trị viên <?= $index + 1 ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Nav -->
  <ul class="nav-list">
    <li><a href="/tkb/admin/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>">
      <i class="fa-solid fa-gauge"></i><span>Tổng quan</span></a></li>
    <li><a href="/tkb/admin/students.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='students.php'?'active':'' ?>">
      <i class="fa-solid fa-users"></i><span>Sinh viên</span></a></li>
    <li><a href="/tkb/admin/tkb.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='tkb.php'?'active':'' ?>">
      <i class="fa-solid fa-calendar-days"></i><span>Thời khóa biểu</span></a></li>
    <li><a href="/tkb/admin/diem.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='diem.php'?'active':'' ?>">
      <i class="fa-solid fa-star-half-stroke"></i><span>Quản lý điểm</span></a></li>
    <li><a href="/tkb/admin/monhoc.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='monhoc.php'?'active':'' ?>">
      <i class="fa-solid fa-book"></i><span>Môn học</span></a></li>
    <li><a href="/tkb/admin/diemdanh.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='diemdanh.php'?'active':'' ?>">
      <i class="fa-solid fa-user-check"></i><span>Điểm danh</span></a></li>
    <li><a href="/tkb/admin/taichinh.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='taichinh.php'?'active':'' ?>">
      <i class="fa-solid fa-money-bill"></i><span>Tài chính</span></a></li>
    <li><a href="/tkb/admin/thongbao.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='thongbao.php'?'active':'' ?>">
      <i class="fa-solid fa-bullhorn"></i><span>Thông báo</span></a></li>
    <li><a href="/tkb/admin/profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='profile.php'?'active':'' ?>">
      <i class="fa-solid fa-id-card"></i><span>Hồ sơ cá nhân</span></a></li>
  </ul>

  <div class="nav-bottom">
    <a href="/tkb/api/logout.php" class="btn-logout">
      <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
    </a>
  </div>
</nav>

<!-- Topbar mobile -->
<div class="admin-topbar" id="adminTopbar">
  <button class="admin-topbar-ham" onclick="toggleAdminSidebar()">
    <span></span><span></span><span></span>
  </button>
  <span class="admin-topbar-title">
    <i class="fa-solid fa-shield-halved" style="color:red;margin-right:6px;"></i> QUẢN TRỊ HỆ THỐNG
  </span>
</div>

<script>
document.body.classList.add('admin-portal');
function toggleAdminSidebar() {
    document.getElementById('adminSidebar').classList.toggle('open');
    document.getElementById('adminOverlay').classList.toggle('active');
}
</script>

<div class="main-content">