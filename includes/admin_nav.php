<!-- Admin Nav & Layout (Lofi Chill Dreamy Purple-Black Aesthetic) -->
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$_nav_db = getDB();
$cur_uid = (int)($_SESSION['user_id'] ?? 1);
$current_adm_name = $_SESSION['ho_ten'] ?? 'Quản Trị Viên';
$current_adm_user = $_SESSION['username'] ?? 'admin';
$current_adm_avatar = '/tkb/assets/img/avatar_khanh.png';

if ($_nav_db) {
    $res_cur = @$_nav_db->query("SELECT id, username, ho_ten, avatar FROM users WHERE id = $cur_uid LIMIT 1");
    if ($res_cur && ($cur_row = $res_cur->fetch_assoc())) {
        if (!empty($cur_row['ho_ten'])) $current_adm_name = $cur_row['ho_ten'];
        if (!empty($cur_row['username'])) $current_adm_user = $cur_row['username'];
        if (!empty($cur_row['avatar'])) {
            $av = $cur_row['avatar'];
            $current_adm_avatar = (strpos($av, '/') === 0 || strpos($av, 'http') === 0) ? $av : '/tkb/assets/img/avatars/' . $av;
        }
    }
    @$_nav_db->close();
}

$is_tuyen = (isset($_SESSION['username']) && in_array(strtolower($_SESSION['username']), ['phanngoctuyen', 'admin_tuyen', 'tuyen']))
    || (isset($current_adm_user) && in_array(strtolower($current_adm_user), ['phanngoctuyen', 'admin_tuyen', 'tuyen']))
    || (isset($current_adm_name) && (strpos(mb_strtolower($current_adm_name), 'tuyền') !== false || strpos(mb_strtolower($current_adm_name), 'tuyen') !== false))
    || (isset($_GET['theme']) && $_GET['theme'] === 'tuyen');

if ($is_tuyen) {
    $current_adm_name = 'Phan Ngọc Tuyền';
    if (empty($cur_row['avatar']) || strpos($current_adm_avatar, 'avatar_khanh') !== false) {
        $current_adm_avatar = '/tkb/assets/img/avatar_tuyen.jpg';
    }
}

$cur_file = basename($_SERVER['PHP_SELF']);

if ($is_tuyen) {
    // Phan Ngọc Tuyền chỉ quản lý Giảng viên + Sinh viên, không vào các trang hệ thống/chức năng khác
    $restricted_pages = [
        'lop.php', 'monhoc.php', 'diem.php', 'baitap.php', 'tailieu.php', 
        'phanquyen.php', 'caidat.php', 'backup.php', 'nhatky.php', 
        'youtube.php', 'quanly_ai_models.php'
    ];
    if (in_array($cur_file, $restricted_pages)) {
        header('Location: /tkb/admin/dashboard.php');
        exit();
    }
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* ============================================================
   LOFI CHILL DREAMY PURPLE-BLACK THEME FOR ADMIN PORTAL
   ============================================================ */
:root {
    --adm-bg: #090514;
    --adm-bg-pattern: radial-gradient(circle at 50% 0%, #1e0d38 0%, #0d0719 50%, #080410 100%);
    --adm-sidebar-bg: #0b0718;
    --adm-sidebar-card: #140d27;
    --adm-sidebar-border: rgba(168, 85, 247, 0.16);
    --adm-sidebar-text: #a79bb7;
    --adm-sidebar-hover: rgba(139, 92, 246, 0.15);
    --adm-accent-grad: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
    --adm-accent-shadow: 0 8px 25px -2px rgba(168, 85, 247, 0.45);
    --adm-topbar-bg: rgba(11, 7, 24, 0.85);
    --adm-border: rgba(168, 85, 247, 0.16);
    --adm-border-hover: rgba(192, 132, 252, 0.35);
    --adm-text-main: #f3e8ff;
    --adm-text-muted: #9d8ba7;
}

/* ============================================================
   MODERN WHITE & SOFT LAVENDER THEME FOR CÔ PHAN NGỌC TUYỀN (40 TUỔI)
   ============================================================ */
body.tuyen-theme,
body.adm-light-mode,
body.admin-portal.tuyen-theme,
body.admin-portal.adm-light-mode {
    background: #f8fafc !important;
    background-color: #f8fafc !important;
    background-image: none !important;
    color: #1e293b !important;
}

body.tuyen-theme ::-webkit-scrollbar-track , body.adm-light-mode ::-webkit-scrollbar-track { background: #f1f5f9 !important; }
body.tuyen-theme ::-webkit-scrollbar-thumb , body.adm-light-mode ::-webkit-scrollbar-thumb { background: #cbd5e1 !important; border-radius: 4px; }
body.tuyen-theme ::-webkit-scrollbar-thumb:hover , body.adm-light-mode ::-webkit-scrollbar-thumb:hover { background: #a855f7 !important; }

/* Hide all Lofi Chill music player elements for Cô Phan Ngọc Tuyền */
body.tuyen-theme:not(.adm-light-mode) .adm-topbar-music,
body.tuyen-theme:not(.adm-light-mode) #topbarMusicPlayer,
body.tuyen-theme:not(.adm-light-mode) #admLofiPlaylistModal,
body.tuyen-theme:not(.adm-light-mode) #lofiYtPlayerHolder {
    display: none !important;
}

body.tuyen-theme .adm-sidebar,
body.adm-light-mode .adm-sidebar {
    background: #ffffff !important;
    border-right: 1px solid #e2e8f0 !important;
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.03) !important;
}

body.tuyen-theme .adm-brand-logo,
body.adm-light-mode .adm-brand-logo {
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.12) !important;
    border: 1px solid #e2e8f0 !important;
    background: #ffffff !important;
}

body.tuyen-theme .adm-brand-text h2,
body.adm-light-mode .adm-brand-text h2 {
    color: #0f172a !important;
    text-shadow: none !important;
}

body.tuyen-theme .adm-brand-text span,
body.adm-light-mode .adm-brand-text span {
    color: #64748b !important;
}

body.tuyen-theme .adm-user-card,
body.adm-light-mode .adm-user-card {
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02) !important;
}
body.tuyen-theme .adm-user-card:hover,
body.adm-light-mode .adm-user-card:hover {
    border-color: #ddd6fe !important;
    background: #ffffff !important;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.1) !important;
}

body.tuyen-theme .adm-user-av,
body.adm-light-mode .adm-user-av {
    border: 2px solid #ddd6fe !important;
    background: #f5f3ff !important;
}

body.tuyen-theme .adm-user-name,
body.adm-light-mode .adm-user-name {
    color: #0f172a !important;
}

body.tuyen-theme .adm-user-role,
body.adm-light-mode .adm-user-role {
    color: #64748b !important;
}

body.tuyen-theme .adm-user-card i.fa-chevron-right,
body.adm-light-mode .adm-user-card i.fa-chevron-right {
    color: #94a3b8 !important;
}

body.tuyen-theme .adm-nav-item a,
body.adm-light-mode .adm-nav-item a {
    color: #475569 !important;
    font-weight: 500 !important;
}
body.tuyen-theme .adm-nav-item a:hover,
body.adm-light-mode .adm-nav-item a:hover {
    background: #f5f3ff !important;
    color: #7c3aed !important;
}
body.tuyen-theme .adm-nav-item.active a,
body.tuyen-theme .adm-nav-item a.active,
body.adm-light-mode .adm-nav-item.active a,
body.adm-light-mode .adm-nav-item a.active {
    background: #ede9fe !important;
    color: #7c3aed !important;
    box-shadow: none !important;
    font-weight: 700 !important;
    border-left: 3.5px solid #7c3aed !important;
}
body.tuyen-theme .adm-nav-item.active a i,
body.tuyen-theme .adm-nav-item a.active i,
body.adm-light-mode .adm-nav-item.active a i,
body.adm-light-mode .adm-nav-item a.active i {
    color: #7c3aed !important;
}

body.tuyen-theme .adm-sidebar li[style*="text-transform: uppercase"],
body.adm-light-mode .adm-sidebar li[style*="text-transform: uppercase"],
body.tuyen-theme .adm-sidebar li[style*="letter-spacing"],
body.adm-light-mode .adm-sidebar li[style*="letter-spacing"] {
    color: #64748b !important;
}
body.tuyen-theme .adm-sidebar li[style*="text-transform: uppercase"] i,
body.adm-light-mode .adm-sidebar li[style*="text-transform: uppercase"] i {
    color: #7c3aed !important;
}

body.tuyen-theme .adm-sidebar::-webkit-scrollbar-thumb,
body.adm-light-mode .adm-sidebar::-webkit-scrollbar-thumb {
    background: #cbd5e1 !important;
}
body.tuyen-theme .adm-sidebar::-webkit-scrollbar-thumb:hover,
body.adm-light-mode .adm-sidebar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8 !important;
}

body.tuyen-theme .adm-topbar , body.adm-light-mode .adm-topbar {
    background: rgba(255, 255, 255, 0.96) !important;
    border-bottom: 1px solid #f1f5f9 !important;
    box-shadow: 0 1px 12px rgba(0, 0, 0, 0.02) !important;
}

body.tuyen-theme .adm-ham-btn , body.adm-light-mode .adm-ham-btn {
    color: #334155 !important;
}
body.tuyen-theme .adm-ham-btn:hover , body.adm-light-mode .adm-ham-btn:hover {
    background: #f1f5f9 !important;
}

body.tuyen-theme .adm-search-input , body.adm-light-mode .adm-search-input {
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    color: #0f172a !important;
}
body.tuyen-theme .adm-search-input:focus , body.adm-light-mode .adm-search-input:focus {
    background: #ffffff !important;
    border-color: #7c3aed !important;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12) !important;
}
body.tuyen-theme .adm-shortcut-badge , body.adm-light-mode .adm-shortcut-badge {
    background: #e2e8f0 !important;
    color: #64748b !important;
    border: 1px solid #cbd5e1 !important;
}

body.tuyen-theme .adm-action-icon-btn , body.adm-light-mode .adm-action-icon-btn {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    color: #475569 !important;
}
body.tuyen-theme .adm-action-icon-btn:hover , body.adm-light-mode .adm-action-icon-btn:hover {
    background: #f5f3ff !important;
    color: #7c3aed !important;
    border-color: #ddd6fe !important;
}

body.tuyen-theme .adm-topbar-music , body.adm-light-mode .adm-topbar-music {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04) !important;
}
body.tuyen-theme .adm-topbar-music:hover , body.adm-light-mode .adm-topbar-music:hover {
    border-color: #c4b5fd !important;
    box-shadow: 0 4px 16px rgba(124, 58, 237, 0.1) !important;
}
body.tuyen-theme .adm-topbar-music .adm-music-song , body.adm-light-mode .adm-topbar-music .adm-music-song {
    color: #1e293b !important;
}
body.tuyen-theme .adm-topbar-music .adm-music-ctrl-btn , body.adm-light-mode .adm-topbar-music .adm-music-ctrl-btn {
    background: #f5f3ff !important;
    border: 1px solid #ede9fe !important;
    color: #7c3aed !important;
}

body.tuyen-theme .adm-profile-pill , body.adm-light-mode .adm-profile-pill {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03) !important;
}
body.tuyen-theme .adm-profile-pill strong , body.adm-light-mode .adm-profile-pill strong {
    color: #0f172a !important;
}
body.tuyen-theme .adm-profile-pill span , body.adm-light-mode .adm-profile-pill span {
    color: #64748b !important;
}
body.tuyen-theme .adm-profile-av , body.adm-light-mode .adm-profile-av {
    border: 2px solid #ddd6fe !important;
}

body.tuyen-theme #admProfileDropdown , body.adm-light-mode #admProfileDropdown {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08) !important;
}
body.tuyen-theme #admProfileDropdown > div:first-child , body.adm-light-mode #admProfileDropdown > div:first-child {
    border-bottom: 1px solid #f1f5f9 !important;
}
body.tuyen-theme #admProfileDropdown div:first-child div:first-child , body.adm-light-mode #admProfileDropdown div:first-child div:first-child {
    color: #0f172a !important;
}
body.tuyen-theme #admProfileDropdown a , body.adm-light-mode #admProfileDropdown a {
    color: #334155 !important;
}
body.tuyen-theme #admProfileDropdown a:hover , body.adm-light-mode #admProfileDropdown a:hover {
    background: #f5f3ff !important;
    color: #7c3aed !important;
}

/* Cards, Tables, Forms & Modals in Tuyen Theme */
body.tuyen-theme .page-title , body.adm-light-mode .page-title {
    color: #1e1b4b !important;
}
body.tuyen-theme .page-sub , body.adm-light-mode .page-sub {
    color: #64748b !important;
}
body.tuyen-theme .card , body.adm-light-mode .card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04) !important;
    color: #1e293b !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}
body.tuyen-theme .card-head , body.adm-light-mode .card-head {
    border-bottom: 1px solid #f1f5f9 !important;
}
body.tuyen-theme .card-title , body.adm-light-mode .card-title {
    color: #0f172a !important;
}
body.tuyen-theme th , body.adm-light-mode th {
    background: #f8fafc !important;
    color: #475569 !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
body.tuyen-theme td , body.adm-light-mode td {
    color: #1e293b !important;
    border-bottom: 1px solid #f1f5f9 !important;
}
body.tuyen-theme tr:hover td , body.adm-light-mode tr:hover td {
    background: #f8fafc !important;
}
body.tuyen-theme .filter-bar , body.adm-light-mode .filter-bar {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02) !important;
}
body.tuyen-theme .filter-bar .search-input , body.adm-light-mode .filter-bar .search-input {
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    color: #0f172a !important;
}
body.tuyen-theme .filter-bar .form-select , body.adm-light-mode .filter-bar .form-select {
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    color: #0f172a !important;
}
body.tuyen-theme .filter-bar .btn-ghost , body.adm-light-mode .filter-bar .btn-ghost {
    background: #f8fafc !important;
    border: 1px solid #cbd5e1 !important;
    color: #475569 !important;
}

/* Modals in Tuyen Theme */
body.tuyen-theme .modal-overlay , body.adm-light-mode .modal-overlay {
    background: rgba(15, 23, 42, 0.45) !important;
}
body.tuyen-theme .modal-box , body.adm-light-mode .modal-box {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15) !important;
    color: #1e293b !important;
}
body.tuyen-theme .modal-title , body.adm-light-mode .modal-title {
    color: #0f172a !important;
}
body.tuyen-theme .modal-sub , body.adm-light-mode .modal-sub {
    color: #64748b !important;
}
body.tuyen-theme .form-label , body.adm-light-mode .form-label {
    color: #334155 !important;
}
body.tuyen-theme .form-group .form-input, body.adm-light-mode .form-group .form-input,
body.tuyen-theme .form-group .form-select, body.adm-light-mode .form-group .form-select,
body.tuyen-theme .form-group .form-control , body.adm-light-mode .form-group .form-control {
    background: #ffffff !important;
    border: 1.5px solid #cbd5e1 !important;
    color: #0f172a !important;
}
body.tuyen-theme .form-group .form-input:focus, body.adm-light-mode .form-group .form-input:focus,
body.tuyen-theme .form-group .form-select:focus, body.adm-light-mode .form-group .form-select:focus,
body.tuyen-theme .form-group .form-control:focus , body.adm-light-mode .form-group .form-control:focus {
    border-color: #7c3aed !important;
    background: #ffffff !important;
}

body.admin-portal {
    background-color: var(--adm-bg) !important;
    background-image: var(--adm-bg-pattern) !important;
    background-attachment: fixed !important;
    color: var(--adm-text-main) !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

/* Custom Scrollbar */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: rgba(10, 6, 20, 0.5); }
::-webkit-scrollbar-thumb { background: #3b1d6e; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #7c3aed; }

/* Sidebar */
.adm-sidebar {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 270px !important;
    height: 100vh !important;
    background: var(--adm-sidebar-bg) !important;
    display: flex !important;
    flex-direction: column !important;
    z-index: 1000 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 4px 0 30px rgba(0, 0, 0, 0.45);
    border-right: 1px solid var(--adm-sidebar-border);
}
.adm-sidebar::-webkit-scrollbar {
    width: 5px;
}
.adm-sidebar::-webkit-scrollbar-track {
    background: transparent;
}
.adm-sidebar::-webkit-scrollbar-thumb {
    background: rgba(168, 85, 247, 0.3);
    border-radius: 4px;
}
.adm-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(168, 85, 247, 0.6);
}

.adm-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(5, 3, 10, 0.7);
    backdrop-filter: blur(6px);
    z-index: 999;
}
.adm-overlay.active { display: block; }

/* Sidebar Brand Header */
.adm-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 22px 20px 18px;
    text-decoration: none;
}
.adm-brand-logo {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 0 15px rgba(168, 85, 247, 0.35);
    overflow: hidden;
    padding: 3px;
    box-sizing: border-box;
}
.adm-brand-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.adm-brand-text h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 15px;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    letter-spacing: 0.3px;
    line-height: 1.2;
    text-shadow: 0 0 12px rgba(168, 85, 247, 0.4);
}
.adm-brand-text span {
    font-size: 10px;
    font-weight: 700;
    color: #a79bb7;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    display: block;
    margin-top: 3px;
}

/* User Card in Sidebar */
.adm-user-card {
    margin: 6px 16px 16px;
    padding: 12px 14px;
    background: var(--adm-sidebar-card);
    border: 1px solid var(--adm-sidebar-border);
    border-radius: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
    transition: all 0.2s ease;
}
.adm-user-card:hover {
    border-color: rgba(192, 132, 252, 0.4);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(168, 85, 247, 0.2);
}
.adm-user-av {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #1e1238;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    border: 1.5px solid rgba(168, 85, 247, 0.35);
}
.adm-user-av img { width: 100%; height: 100%; object-fit: cover; }
.adm-user-info { min-width: 0; flex: 1; }
.adm-user-name {
    font-size: 13px;
    font-weight: 700;
    color: #ffffff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.adm-user-role {
    font-size: 11px;
    color: #a79bb7;
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.adm-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.8);
}

/* Nav Menu Items */
.adm-nav-list {
    list-style: none;
    padding: 0 14px;
    margin: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.adm-nav-item a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 11px 16px;
    border-radius: 12px;
    color: var(--adm-sidebar-text);
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 600;
    transition: all 0.2s ease;
}
.adm-nav-item a i {
    width: 20px;
    font-size: 15px;
    text-align: center;
    color: inherit;
    transition: all 0.2s ease;
}
.adm-nav-item a:hover {
    background: var(--adm-sidebar-hover);
    color: #ffffff;
}
.adm-nav-item.active a,
.adm-nav-item a.active {
    background: var(--adm-accent-grad) !important;
    color: #ffffff !important;
    box-shadow: var(--adm-accent-shadow);
    font-weight: 700;
}
.adm-nav-item.active a i,
.adm-nav-item a.active i {
    color: #ffffff !important;
}


/* Topbar Header & Admin Layout */
body.admin-portal {
    display: block !important;
    overflow-x: hidden;
}

.adm-topbar {
    margin-left: 270px;
    width: calc(100% - 270px);
    height: 68px;
    background: var(--adm-topbar-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--adm-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 32px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-sizing: border-box;
    transition: margin-left 0.3s ease, width 0.3s ease;
}

.adm-topbar-left {
    display: flex;
    align-items: center;
    gap: 18px;
}

.adm-ham-btn {
    background: none;
    border: none;
    font-size: 18px;
    color: var(--adm-text-main);
    cursor: pointer;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}
.adm-ham-btn:hover { background: rgba(139, 92, 246, 0.15); }

.adm-search-wrap {
    position: relative;
    width: 320px;
}
.adm-search-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9d8ba7;
    font-size: 13.5px;
}
.adm-search-input {
    width: 100%;
    padding: 9px 65px 9px 38px;
    background: #140d27;
    border: 1px solid var(--adm-border);
    border-radius: 12px;
    font-size: 13px;
    outline: none;
    color: var(--adm-text-main);
    transition: all 0.2s;
    box-sizing: border-box;
}
.adm-search-input:focus {
    background: #1c1136;
    border-color: #a855f7;
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2);
}
.adm-shortcut-badge {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: #231640;
    color: #c4b5fd;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    pointer-events: none;
    border: 1px solid rgba(168, 85, 247, 0.2);
}

.adm-topbar-right {
    display: flex;
    align-items: center;
    gap: 14px;
}

.adm-action-icon-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #140d27;
    border: 1px solid var(--adm-border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #c4b5fd;
    font-size: 15px;
    cursor: pointer;
    position: relative;
    text-decoration: none;
    transition: all 0.2s;
}
.adm-action-icon-btn:hover {
    background: #20133d;
    color: #ffffff;
    border-color: rgba(192, 132, 252, 0.4);
}
.adm-action-icon-btn.active {
    background: rgba(168, 85, 247, 0.35) !important;
    border-color: #c084fc !important;
    color: #ffffff !important;
    box-shadow: 0 0 16px rgba(168, 85, 247, 0.7), inset 0 0 8px rgba(192, 132, 252, 0.3) !important;
}
.adm-badge-count {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ec4899;
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #0b0718;
    box-shadow: 0 0 8px rgba(236, 72, 153, 0.6);
}

/* ============================================================
/* ============================================================
   ULTRA-PREMIUM DYNAMIC ISLAND TOPBAR MUSIC PLAYER
   ============================================================ */
.adm-topbar-music {
    display: flex;
    align-items: center;
    gap: 9px;
    height: 42px;
    background: linear-gradient(135deg, rgba(23, 13, 44, 0.88), rgba(12, 7, 25, 0.94));
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(168, 85, 247, 0.32);
    border-radius: 22px;
    padding: 0 10px 0 5px;
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    position: relative;
    overflow: hidden;
}
.adm-topbar-music:hover {
    border-color: rgba(192, 132, 252, 0.65);
    box-shadow: 0 8px 30px rgba(168, 85, 247, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.18);
    transform: translateY(-1px);
}
.adm-topbar-music.playing {
    border-color: rgba(168, 85, 247, 0.55);
    box-shadow: 0 0 20px rgba(168, 85, 247, 0.25), 0 6px 25px rgba(0, 0, 0, 0.4);
}

/* Bottom Progress Glow Line */
.adm-music-progress-line {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2.5px;
    background: rgba(255, 255, 255, 0.06);
    pointer-events: none;
}
.adm-music-progress-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #a855f7, #ec4899, #38bdf8);
    box-shadow: 0 0 8px rgba(168, 85, 247, 0.8);
    transition: width 0.3s ease;
}

/* Mousewheel Volume Tooltip */
.adm-music-vol-tooltip {
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%) translateY(6px);
    background: #1e1138;
    color: #f3e8ff;
    border: 1px solid rgba(168, 85, 247, 0.4);
    box-shadow: 0 6px 20px rgba(0,0,0,0.6);
    font-size: 11px;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 20px;
    pointer-events: none;
    opacity: 0;
    transition: all 0.25s ease;
    white-space: nowrap;
    z-index: 1000;
}
.adm-music-vol-tooltip.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* Vinyl Disc / Album Cover */
.adm-music-disk-wrap {
    position: relative;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    cursor: pointer;
}
.adm-music-disk {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: radial-gradient(circle, #2d1847 25%, #0d0818 26%, #1a0f2e 50%, #0d0818 51%, #25133d 75%, #0d0818 76%, #150a26 100%);
    border: 2px solid rgba(168, 85, 247, 0.45);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    transition: transform 0.25s;
}
.adm-music-disk img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}
.adm-music-disk-center {
    position: absolute;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: linear-gradient(135deg, #a855f7, #38bdf8);
    border: 2px solid #0d0818;
    box-shadow: 0 0 4px rgba(0,0,0,0.8);
    z-index: 2;
}
.adm-topbar-music.playing .adm-music-disk {
    animation: spinVinyl 6s linear infinite;
    box-shadow: 0 0 14px rgba(168, 85, 247, 0.65);
    border-color: #c084fc;
}

/* Meta & Scrolling Title */
.adm-music-meta {
    display: flex;
    flex-direction: column;
    justify-content: center;
    cursor: pointer;
    width: 175px;
    min-width: 110px;
    overflow: hidden;
}
.adm-music-marquee-wrap {
    width: 100%;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
    -webkit-mask-image: linear-gradient(90deg, #000 0%, #000 calc(100% - 14px), transparent 100%);
    mask-image: linear-gradient(90deg, #000 0%, #000 calc(100% - 14px), transparent 100%);
}
.adm-music-song {
    display: inline-block;
    font-size: 12px;
    font-weight: 700;
    color: #ffffff;
    line-height: 1.25;
    transition: color 0.2s;
    white-space: nowrap;
}
.adm-music-song.is-marquee {
    animation: marqueeScroll 8s ease-in-out infinite alternate;
}
@keyframes marqueeScroll {
    0%, 25% { transform: translateX(0); }
    75%, 100% { transform: translateX(calc(-100% + 160px)); }
}

.adm-music-sub-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 3px;
}
.adm-music-wave {
    display: flex;
    align-items: flex-end;
    gap: 2px;
    height: 10px;
}
.wave-bar {
    width: 2.2px;
    height: 3px;
    background: linear-gradient(to top, #a855f7, #38bdf8);
    border-radius: 2px;
    transition: height 0.2s;
}
.adm-topbar-music.playing .wave-bar:nth-child(1) { animation: waveAnim 0.75s ease-in-out infinite 0.1s; }
.adm-topbar-music.playing .wave-bar:nth-child(2) { animation: waveAnim 1.1s ease-in-out infinite 0.3s; }
.adm-topbar-music.playing .wave-bar:nth-child(3) { animation: waveAnim 0.85s ease-in-out infinite 0.15s; }
.adm-topbar-music.playing .wave-bar:nth-child(4) { animation: waveAnim 1.2s ease-in-out infinite 0.4s; }
.adm-topbar-music.playing .wave-bar:nth-child(5) { animation: waveAnim 0.95s ease-in-out infinite 0.25s; }

@keyframes waveAnim {
    0%, 100% { height: 3px; }
    50% { height: 10px; }
}

.adm-music-source-badge {
    font-size: 9px;
    font-weight: 800;
    padding: 1px 5px;
    border-radius: 4px;
    background: rgba(168, 85, 247, 0.18);
    color: #c4b5fd;
    border: 1px solid rgba(168, 85, 247, 0.25);
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}
.adm-music-source-badge.yt {
    background: rgba(244, 63, 94, 0.16);
    color: #fda4af;
    border-color: rgba(244, 63, 94, 0.3);
}

@keyframes spinVinyl {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Control Buttons */
.adm-music-ctrl-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(168, 85, 247, 0.2);
    color: #d8b4fe;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
}
.adm-music-ctrl-btn:hover {
    background: rgba(168, 85, 247, 0.3);
    color: #ffffff;
    border-color: #c084fc;
    transform: scale(1.12);
}
.adm-music-ctrl-btn:active {
    transform: scale(0.94);
}
.adm-music-ctrl-play {
    width: 31px;
    height: 31px;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #ffffff;
    border: none;
    box-shadow: 0 2px 10px rgba(168, 85, 247, 0.45);
}
.adm-music-ctrl-play:hover {
    background: linear-gradient(135deg, #c084fc, #f43f5e);
    box-shadow: 0 4px 16px rgba(236, 72, 153, 0.6);
    transform: scale(1.12);
}
.adm-music-pip-btn {
    color: #fda4af;
    border-color: rgba(244, 63, 94, 0.35);
    background: rgba(244, 63, 94, 0.12);
}
.adm-music-pip-btn:hover {
    background: rgba(244, 63, 94, 0.35);
    color: #ffffff;
    border-color: #f43f5e;
}

/* Playlist Modal Custom Styles */
.lofi-track-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.lofi-track-item:hover {
    background: rgba(168, 85, 247, 0.15);
    border-color: rgba(168, 85, 247, 0.3);
    transform: translateX(4px);
}
.lofi-track-item.active {
    background: linear-gradient(135deg, rgba(147, 51, 234, 0.25), rgba(56, 189, 248, 0.15));
    border-color: #a855f7;
    box-shadow: 0 4px 15px rgba(168, 85, 247, 0.2);
}

@media (max-width: 900px) {
    .adm-topbar-music .adm-music-meta { display: none; }
}


.adm-profile-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 12px 5px 6px;
    border-radius: 999px;
    background: #140d27;
    border: 1px solid var(--adm-border);
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    transition: all 0.2s;
}
.adm-profile-pill:hover {
    background: #20133d;
    border-color: rgba(192, 132, 252, 0.4);
}
.adm-profile-av {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    overflow: hidden;
    background: #1e1238;
    border: 1px solid rgba(168, 85, 247, 0.3);
}
.adm-profile-av img { width: 100%; height: 100%; object-fit: cover; }
.adm-profile-meta { font-size: 12.5px; }
.adm-profile-meta strong { display: block; color: var(--adm-text-main); font-weight: 700; line-height: 1.2; }
.adm-profile-meta span { font-size: 11px; color: #a79bb7; font-weight: 600; display: flex; align-items: center; gap: 4px; }

/* Unified Admin Layout Resets & Components */
body.admin-portal .main-content {
    margin-left: 270px !important;
    width: calc(100% - 270px) !important;
    max-width: calc(100% - 270px) !important;
    margin-top: 0 !important;
    margin-right: 0 !important;
    margin-bottom: 0 !important;
    padding: 24px 32px 48px !important;
    box-sizing: border-box !important;
    min-height: calc(100vh - 68px) !important;
    transition: margin-left 0.3s ease, width 0.3s ease;
}

body.admin-portal .adm-content-container,
body.admin-portal .adm-page-body {
    margin-left: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    box-sizing: border-box !important;
}

/* Typography & Headings */
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .page-title {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-size: 22px !important;
    font-weight: 800 !important;
    color: #f3e8ff !important;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    letter-spacing: -0.2px;
    text-shadow: 0 0 20px rgba(168, 85, 247, 0.25);
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .page-title i {
    color: #c084fc !important;
    font-size: 22px;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .page-sub {
    font-size: 13px !important;
    color: #a79bb7 !important;
    margin-top: 4px;
    font-weight: 500;
}

/* Modern Lofi Admin Cards */
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .card {
    background: rgba(20, 13, 38, 0.85) !important;
    border: 1px solid rgba(168, 85, 247, 0.16) !important;
    border-radius: 18px !important;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35) !important;
    overflow: hidden !important;
    margin-bottom: 24px;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    color: #f3e8ff !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .card-head {
    padding: 18px 24px !important;
    border-bottom: 1px solid rgba(168, 85, 247, 0.12) !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: transparent !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .card-title {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-size: 15px !important;
    font-weight: 800 !important;
    color: #f3e8ff !important;
    letter-spacing: 0 !important;
    text-transform: none !important;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .card-body {
    padding: 24px;
}

/* Modern Lofi Admin Tables */
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) table {
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) th {
    background: rgba(24, 15, 48, 0.9) !important;
    color: #c084fc !important;
    font-size: 11.5px !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.6px !important;
    padding: 14px 18px !important;
    border-bottom: 1px solid rgba(168, 85, 247, 0.2) !important;
    border-top: none !important;
    white-space: nowrap !important;
    text-align: left;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) td {
    padding: 14px 18px !important;
    font-size: 13.5px !important;
    color: #e9d5ff !important;
    border-bottom: 1px solid rgba(168, 85, 247, 0.08) !important;
    vertical-align: middle !important;
    background: transparent !important;
    transition: background 0.15s ease;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) tr:hover td {
    background: rgba(139, 92, 246, 0.1) !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) tr:last-child td {
    border-bottom: none !important;
}

/* Modern Lofi Admin Buttons */
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .btn {
    padding: 10px 18px !important;
    border-radius: 10px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    text-decoration: none !important;
    border: none !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    box-sizing: border-box !important;
    line-height: 1.4 !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .btn-primary {
    background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 18px rgba(168, 85, 247, 0.4) !important;
    border: none !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .btn-primary:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 22px rgba(168, 85, 247, 0.55) !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .btn-ghost {
    background: rgba(24, 15, 48, 0.85) !important;
    border: 1px solid rgba(168, 85, 247, 0.25) !important;
    color: #e9d5ff !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .btn-ghost:hover {
    background: #25164a !important;
    border-color: rgba(192, 132, 252, 0.45) !important;
    color: #ffffff !important;
    transform: translateY(-1px) !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .btn-sm {
    padding: 6px 12px !important;
    font-size: 12px !important;
    border-radius: 8px !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .btn-edit {
    width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 8px !important;
    background: rgba(139, 92, 246, 0.2) !important;
    color: #c084fc !important;
    border: 1px solid rgba(168, 85, 247, 0.3) !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-decoration: none !important;
    font-size: 12px !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .btn-edit:hover {
    background: #7c3aed !important;
    color: #ffffff !important;
    border-color: #7c3aed !important;
    transform: translateY(-1px) !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .btn-danger {
    background: rgba(239, 68, 68, 0.15) !important;
    color: #fca5a5 !important;
    border: 1px solid rgba(239, 68, 68, 0.3) !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .btn-danger.btn-sm,
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) a.btn-danger.btn-sm {
    width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 8px !important;
    font-size: 12px !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .btn-danger:hover {
    background: #dc2626 !important;
    color: #ffffff !important;
    border-color: #dc2626 !important;
    transform: translateY(-1px) !important;
}

/* Filter Bar & Search */
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .filter-bar {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 16px !important;
    margin-bottom: 20px !important;
    background: rgba(20, 13, 38, 0.85) !important;
    padding: 14px 20px !important;
    border-radius: 14px !important;
    border: 1px solid rgba(168, 85, 247, 0.16) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25) !important;
    flex-wrap: wrap !important;
    backdrop-filter: blur(16px) !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .filter-bar form {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    flex: 1 !important;
    flex-wrap: wrap !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .filter-bar .search-wrap {
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    flex: 1 !important;
    min-width: 220px !important;
    max-width: 380px !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .filter-bar .search-wrap i {
    position: absolute !important;
    left: 14px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    color: #9d8ba7 !important;
    font-size: 13.5px !important;
    pointer-events: none !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .filter-bar .search-input {
    width: 100% !important;
    padding: 10px 14px 10px 38px !important;
    background: #140d27 !important;
    border: 1.5px solid rgba(168, 85, 247, 0.2) !important;
    border-radius: 10px !important;
    color: #f3e8ff !important;
    font-size: 13.5px !important;
    outline: none !important;
    transition: all 0.2s !important;
    box-sizing: border-box !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .filter-bar .search-input:focus {
    background: #1c1136 !important;
    border-color: #a855f7 !important;
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2) !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .filter-bar .form-select {
    width: auto !important;
    min-width: 200px !important;
    max-width: 280px !important;
    padding: 10px 14px !important;
    font-size: 13.5px !important;
    border-radius: 10px !important;
    border: 1.5px solid rgba(168, 85, 247, 0.2) !important;
    background: #140d27 !important;
    color: #f3e8ff !important;
    cursor: pointer !important;
    outline: none !important;
    box-sizing: border-box !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .filter-bar .form-select:focus {
    background: #1c1136 !important;
    border-color: #a855f7 !important;
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2) !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .filter-bar .btn {
    padding: 10px 18px !important;
    font-size: 13px !important;
    white-space: nowrap !important;
    flex-shrink: 0 !important;
}

/* Modals & Forms */
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    background: rgba(6, 4, 12, 0.75) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    z-index: 99999 !important;
    display: none;
    align-items: center;
    justify-content: center;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .modal-overlay.active,
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .modal-overlay.open,
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .modal-overlay.show {
    display: flex !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .modal-box {
    background: #150d29 !important;
    border: 1px solid rgba(168, 85, 247, 0.25) !important;
    border-radius: 18px !important;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6) !important;
    padding: 28px !important;
    color: #f3e8ff !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .modal-title {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-size: 18px !important;
    font-weight: 800 !important;
    color: #f3e8ff !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .modal-sub {
    font-size: 13px !important;
    color: #a79bb7 !important;
    margin-bottom: 20px !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .form-group {
    margin-bottom: 16px !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .form-label {
    display: block !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    color: #c4b5fd !important;
    text-transform: none !important;
    letter-spacing: 0 !important;
    margin-bottom: 6px !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .form-group .form-input,
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .form-group .form-select,
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .form-group .form-control {
    background: rgba(22, 14, 42, 0.9) !important;
    border: 1.5px solid rgba(168, 85, 247, 0.2) !important;
    border-radius: 10px !important;
    padding: 10px 14px !important;
    font-size: 13.5px !important;
    color: #f3e8ff !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    outline: none !important;
    transition: all 0.2s !important;
    width: 100% !important;
    box-sizing: border-box !important;
}
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .form-group .form-input:focus,
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .form-group .form-select:focus,
body.admin-portal:not(.adm-light-mode):not(.tuyen-theme) .form-group .form-control:focus {
    background: #1c1136 !important;
    border-color: #a855f7 !important;
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2) !important;
}


/* ============================================================
   ★ COMPREHENSIVE LIGHT THEME OVERRIDES FOR ALL ADMIN FUNCTIONS ★
   Áp dụng đồng bộ cho toàn bộ chức năng (Sinh viên, Giảng viên, Lớp, Môn, Điểm, Bài tập, Tài liệu, Phân quyền, Cài đặt,...)
   ============================================================ */
body.admin-portal.adm-light-mode,
body.admin-portal.tuyen-theme,
body.adm-light-mode,
body.tuyen-theme {
    background-color: #f8fafc !important;
    background-image: none !important;
    color: #0f172a !important;
}

body.admin-portal.adm-light-mode .page-title,
body.admin-portal.tuyen-theme .page-title {
    color: #0f172a !important;
    text-shadow: none !important;
}
body.admin-portal.adm-light-mode .page-title i,
body.admin-portal.tuyen-theme .page-title i {
    color: #7c3aed !important;
}
body.admin-portal.adm-light-mode .page-sub,
body.admin-portal.tuyen-theme .page-sub {
    color: #64748b !important;
}

/* Cards & Containers */
body.admin-portal.adm-light-mode .card,
body.admin-portal.tuyen-theme .card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04) !important;
    color: #0f172a !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}
body.admin-portal.adm-light-mode .card-head,
body.admin-portal.tuyen-theme .card-head {
    border-bottom: 1px solid #f1f5f9 !important;
    background: #ffffff !important;
}
body.admin-portal.adm-light-mode .card-title,
body.admin-portal.tuyen-theme .card-title {
    color: #0f172a !important;
}

/* Tables (Danh sách sinh viên, giảng viên, môn học, lớp,...) */
body.admin-portal.adm-light-mode table,
body.admin-portal.tuyen-theme table {
    background: #ffffff !important;
}
body.admin-portal.adm-light-mode th,
body.admin-portal.tuyen-theme th {
    background: #f8fafc !important;
    color: #475569 !important;
    border-bottom: 1.5px solid #e2e8f0 !important;
    border-top: none !important;
}
body.admin-portal.adm-light-mode td,
body.admin-portal.tuyen-theme td {
    background: #ffffff !important;
    color: #1e293b !important;
    border-bottom: 1px solid #f1f5f9 !important;
}
body.admin-portal.adm-light-mode tr:hover td,
body.admin-portal.tuyen-theme tr:hover td {
    background: #f8fafc !important;
}

/* Filter Bar (Thanh tìm kiếm & lọc trên các trang chức năng) */
body.admin-portal.adm-light-mode .filter-bar,
body.admin-portal.tuyen-theme .filter-bar {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03) !important;
    backdrop-filter: none !important;
}
body.admin-portal.adm-light-mode .filter-bar .search-wrap i,
body.admin-portal.tuyen-theme .filter-bar .search-wrap i {
    color: #94a3b8 !important;
}
body.admin-portal.adm-light-mode .filter-bar .search-input,
body.admin-portal.tuyen-theme .filter-bar .search-input {
    background: #f8fafc !important;
    border: 1.5px solid #e2e8f0 !important;
    color: #0f172a !important;
}
body.admin-portal.adm-light-mode .filter-bar .search-input:focus,
body.admin-portal.tuyen-theme .filter-bar .search-input:focus {
    background: #ffffff !important;
    border-color: #7c3aed !important;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12) !important;
}
body.admin-portal.adm-light-mode .filter-bar .form-select,
body.admin-portal.tuyen-theme .filter-bar .form-select {
    background: #f8fafc !important;
    border: 1.5px solid #e2e8f0 !important;
    color: #0f172a !important;
}
body.admin-portal.adm-light-mode .filter-bar .form-select:focus,
body.admin-portal.tuyen-theme .filter-bar .form-select:focus {
    background: #ffffff !important;
    border-color: #7c3aed !important;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12) !important;
}

/* Buttons */
body.admin-portal.adm-light-mode .btn-ghost,
body.admin-portal.tuyen-theme .btn-ghost {
    background: #f8fafc !important;
    border: 1px solid #cbd5e1 !important;
    color: #334155 !important;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03) !important;
}
body.admin-portal.adm-light-mode .btn-ghost:hover,
body.admin-portal.tuyen-theme .btn-ghost:hover {
    background: #f1f5f9 !important;
    border-color: #94a3b8 !important;
    color: #0f172a !important;
}

/* Modals & Forms */
body.admin-portal.adm-light-mode .modal-overlay,
body.admin-portal.tuyen-theme .modal-overlay {
    background: rgba(15, 23, 42, 0.45) !important;
}
body.admin-portal.adm-light-mode .modal-box,
body.admin-portal.tuyen-theme .modal-box {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15) !important;
    color: #0f172a !important;
}
body.admin-portal.adm-light-mode .modal-title,
body.admin-portal.tuyen-theme .modal-title {
    color: #0f172a !important;
}
body.admin-portal.adm-light-mode .modal-sub,
body.admin-portal.tuyen-theme .modal-sub {
    color: #64748b !important;
}
body.admin-portal.adm-light-mode .form-label,
body.admin-portal.tuyen-theme .form-label {
    color: #334155 !important;
}
body.admin-portal.adm-light-mode .form-group .form-input,
body.admin-portal.adm-light-mode .form-group .form-select,
body.admin-portal.adm-light-mode .form-group .form-control,
body.admin-portal.tuyen-theme .form-group .form-input,
body.admin-portal.tuyen-theme .form-group .form-select,
body.admin-portal.tuyen-theme .form-group .form-control {
    background: #ffffff !important;
    border: 1.5px solid #cbd5e1 !important;
    color: #0f172a !important;
}
body.admin-portal.adm-light-mode .form-group .form-input:focus,
body.admin-portal.adm-light-mode .form-group .form-select:focus,
body.admin-portal.adm-light-mode .form-group .form-control:focus,
body.admin-portal.tuyen-theme .form-group .form-input:focus,
body.admin-portal.tuyen-theme .form-group .form-select:focus,
body.admin-portal.tuyen-theme .form-group .form-control:focus {
    border-color: #7c3aed !important;
    background: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12) !important;
}

/* Fallback for inline dark styles across all admin pages */
body.adm-light-mode [style*="background:#140d27"],
body.adm-light-mode [style*="background: #140d27"],
body.adm-light-mode [style*="background:#150d29"],
body.adm-light-mode [style*="background: #150d29"],
body.adm-light-mode [style*="background:rgba(20, 13, 38"],
body.adm-light-mode [style*="background: rgba(20, 13, 38"],
body.adm-light-mode [style*="background:rgba(24, 15, 48"],
body.adm-light-mode [style*="background: rgba(24, 15, 48"] {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
    color: #0f172a !important;
}
body.adm-light-mode select[style*="background:#140d27"],
body.adm-light-mode select[style*="background: #140d27"],
body.adm-light-mode input[style*="background:#140d27"],
body.adm-light-mode input[style*="background: #140d27"] {
    background: #f8fafc !important;
    border-color: #cbd5e1 !important;
    color: #0f172a !important;
}

/* Custom Function Cards across all admin modules in Light Mode */
body.adm-light-mode .kpi-card-admin,
body.tuyen-theme .kpi-card-admin,
body.adm-light-mode .kpi-box,
body.tuyen-theme .kpi-box,
body.adm-light-mode .assignment-item-card,
body.tuyen-theme .assignment-item-card,
body.adm-light-mode .doc-card,
body.tuyen-theme .doc-card,
body.adm-light-mode .adm-card,
body.tuyen-theme .adm-card,
body.adm-light-mode .aim-stat-card,
body.tuyen-theme .aim-stat-card,
body.adm-light-mode .aim-model-card,
body.tuyen-theme .aim-model-card,
body.adm-light-mode .profile-side-card,
body.tuyen-theme .profile-side-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04) !important;
    color: #0f172a !important;
}

body.adm-light-mode .tab-btn-admin,
body.tuyen-theme .tab-btn-admin,
body.adm-light-mode .grade-tab-btn,
body.tuyen-theme .grade-tab-btn {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    color: #475569 !important;
}
body.adm-light-mode .tab-btn-admin.active,
body.tuyen-theme .tab-btn-admin.active,
body.adm-light-mode .grade-tab-btn.active,
body.tuyen-theme .grade-tab-btn.active {
    background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%) !important;
    color: #ffffff !important;
}

/* Alert Boxes */
body.admin-portal .alert {
    padding: 14px 18px !important;
    border-radius: 12px !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    margin-bottom: 20px !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
}
body.admin-portal .alert-success {
    background: rgba(16, 185, 129, 0.15) !important;
    border: 1px solid rgba(16, 185, 129, 0.35) !important;
    color: #6ee7b7 !important;
}
body.admin-portal .alert-error {
    background: rgba(239, 68, 68, 0.15) !important;
    border: 1px solid rgba(239, 68, 68, 0.35) !important;
    color: #fca5a5 !important;
}

/* Responsive Mobile */
@media (max-width: 1024px) {
    .adm-sidebar { transform: translateX(-100%); }
    .adm-sidebar.open { transform: translateX(0); }
    .adm-topbar { margin-left: 0 !important; width: 100% !important; padding: 0 16px; }
    .adm-search-wrap { width: 200px; }
    body.admin-portal .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 20px 16px 36px !important;
    }
}

/* ============================================================
   SCREEN CURTAIN TRANSITION (ĐÓNG MÀN HÌNH - HIỆN MẶT TRỜI - MỞ MÀN HÌNH)
   ============================================================ */
.adm-theme-curtain {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 99999999;
    pointer-events: none;
    visibility: hidden;
    overflow: hidden;
}
.adm-theme-curtain.active {
    visibility: visible;
    pointer-events: all;
}
.adm-curtain-half {
    position: absolute;
    top: 0;
    width: 50.5vw;
    height: 100vh;
    background: radial-gradient(ellipse at 50% 45%, #241142 0%, #130726 50%, #07020d 100%);
    transition: transform 0.44s cubic-bezier(0.77, 0, 0.175, 1);
    will-change: transform;
    z-index: 1;
}
.adm-curtain-left {
    left: 0;
    transform: translateX(-101%);
    border-right: 1.5px solid rgba(168, 85, 247, 0.2);
    box-shadow: 20px 0 60px rgba(0, 0, 0, 0.9);
}
.adm-curtain-right {
    right: 0;
    transform: translateX(101%);
    border-left: 1.5px solid rgba(168, 85, 247, 0.2);
    box-shadow: -20px 0 60px rgba(0, 0, 0, 0.9);
}
.adm-theme-curtain.closing .adm-curtain-left,
.adm-theme-curtain.closed .adm-curtain-left {
    transform: translateX(0);
}
.adm-theme-curtain.closing .adm-curtain-right,
.adm-theme-curtain.closed .adm-curtain-right {
    transform: translateX(0);
}
.adm-theme-curtain.opening .adm-curtain-left {
    transform: translateX(-101%);
}
.adm-theme-curtain.opening .adm-curtain-right {
    transform: translateX(101%);
}

/* ============================================================
   CHẾ ĐỘ SÁNG: BẦU TRỜI XANH, MÂY TRẮNG, CHIM ÉN & CHIM SẺ
   ============================================================ */
.adm-theme-curtain.curtain-mode-light .adm-curtain-half {
    background: linear-gradient(180deg, #bae6fd 0%, #e0f2fe 28%, #fef9c3 68%, #ffffff 100%) !important;
}
.adm-theme-curtain.curtain-mode-light .adm-curtain-left {
    border-right: 1.5px solid rgba(245, 158, 11, 0.25) !important;
    box-shadow: 15px 0 50px rgba(0, 0, 0, 0.04) !important;
}
.adm-theme-curtain.curtain-mode-light .adm-curtain-right {
    border-left: 1.5px solid rgba(245, 158, 11, 0.25) !important;
    box-shadow: -15px 0 50px rgba(0, 0, 0, 0.04) !important;
}
.adm-theme-curtain.curtain-mode-light .adm-curtain-sun .adm-curtain-title {
    font-size: 32px !important;
    font-weight: 900 !important;
    letter-spacing: 3px !important;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #b45309 100%) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    text-shadow: none !important;
    margin-top: 18px !important;
    position: relative;
    z-index: 5;
}
.adm-theme-curtain.curtain-mode-light .adm-curtain-sun .adm-curtain-sub {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #475569 !important;
    margin-top: 6px !important;
    letter-spacing: 0.3px !important;
    position: relative;
    z-index: 5;
}
.adm-curtain-moon .adm-curtain-title {
    font-size: 32px !important;
    font-weight: 900 !important;
    letter-spacing: 3px !important;
    background: linear-gradient(135deg, #ffffff 0%, #e9d5ff 40%, #c084fc 80%, #38bdf8 100%) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    text-shadow: 0 0 25px rgba(168, 85, 247, 0.7) !important;
    margin-top: 18px !important;
    position: relative;
    z-index: 5;
}
.adm-curtain-moon .adm-curtain-sub {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #c4b5fd !important;
    margin-top: 6px !important;
    letter-spacing: 0.5px !important;
    text-shadow: 0 2px 10px rgba(0,0,0,0.8), 0 0 15px rgba(168, 85, 247, 0.4) !important;
    position: relative;
    z-index: 5;
}

/* Center Stage for Sun / Moon */
.adm-curtain-stage {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.8);
    z-index: 10;
    width: 100vw;
    height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.adm-theme-curtain.closed .adm-curtain-stage {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}
.adm-theme-curtain.opening .adm-curtain-stage {
    opacity: 0;
    transform: translate(-50%, -50%) scale(1.15);
    transition: opacity 0.25s ease, transform 0.35s ease-out;
}

/* LOADING PROGRESS BAR */
.adm-morning-progress-bar {
    position: relative;
    width: 300px;
    height: 32px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.9);
    border: 1.5px solid rgba(245, 158, 11, 0.3);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    margin-top: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 5;
}
.adm-progress-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #fef08a, #f59e0b, #fbbf24);
    animation: progressFill 2.8s cubic-bezier(0.1, 0.85, 0.35, 1) forwards 0.1s;
    border-radius: 20px;
}
.adm-progress-text {
    position: relative;
    z-index: 2;
    font-size: 11px;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    gap: 6px;
}
@keyframes progressFill {
    0% { width: 0%; }
    100% { width: 100%; }
}

/* DARK MODE PROGRESS BAR */
.adm-night-progress-bar {
    position: relative;
    width: 290px;
    height: 32px;
    border-radius: 20px;
    background: rgba(20, 13, 39, 0.85);
    border: 1.5px solid rgba(168, 85, 247, 0.35);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.4);
    overflow: hidden;
    margin-top: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 5;
}
.adm-night-progress-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #c084fc, #a855f7, #38bdf8);
    animation: progressFill 2.8s cubic-bezier(0.1, 0.85, 0.35, 1) forwards 0.1s;
    border-radius: 20px;
}
.adm-night-progress-text {
    position: relative;
    z-index: 2;
    font-size: 11px;
    font-weight: 800;
    color: #f3e8ff;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* EXPANDING SUN RIPPLES */
.adm-sun-ripple {
    position: absolute;
    border-radius: 50%;
    border: 1.5px solid rgba(245, 158, 11, 0.4);
    pointer-events: none;
    z-index: 1;
}
.adm-sun-ripple.r-1 {
    width: 160px;
    height: 160px;
    animation: sunRipple 2.5s cubic-bezier(0.1, 0.8, 0.3, 1) infinite;
}
.adm-sun-ripple.r-2 {
    width: 210px;
    height: 210px;
    animation: sunRipple 2.5s cubic-bezier(0.1, 0.8, 0.3, 1) infinite 0.7s;
}
@keyframes sunRipple {
    0% { transform: scale(0.65); opacity: 0.8; border-color: rgba(245, 158, 11, 0.6); }
    100% { transform: scale(1.6); opacity: 0; border-color: rgba(251, 191, 36, 0); }
}

/* MÂY TRẮNG BỒNG BỀNH (CLOUDS) */
.adm-morning-cloud {
    position: absolute;
    color: #ffffff;
    filter: drop-shadow(0 12px 28px rgba(186, 230, 253, 0.5)) drop-shadow(0 4px 12px rgba(148, 163, 184, 0.15));
    pointer-events: none;
    z-index: 2;
}
.adm-morning-cloud.cloud-1 {
    top: 10%;
    left: 6%;
    font-size: 95px;
    animation: floatCloud1 8s ease-in-out infinite alternate;
    opacity: 0.95;
}
.adm-morning-cloud.cloud-2 {
    top: 15%;
    right: 8%;
    font-size: 115px;
    animation: floatCloud2 9s ease-in-out infinite alternate;
    opacity: 0.95;
}
.adm-morning-cloud.cloud-3 {
    bottom: 16%;
    left: 8%;
    font-size: 85px;
    animation: floatCloud1 7s ease-in-out infinite alternate 1s;
    opacity: 0.9;
}
.adm-morning-cloud.cloud-4 {
    bottom: 20%;
    right: 10%;
    font-size: 80px;
    animation: floatCloud2 8.5s ease-in-out infinite alternate 0.5s;
    opacity: 0.9;
}
.adm-morning-cloud.cloud-5 {
    top: 24%;
    left: 22%;
    font-size: 55px;
    animation: floatCloud1 6s ease-in-out infinite alternate 1.5s;
    opacity: 0.8;
}
@keyframes floatCloud1 {
    from { transform: translateX(0) translateY(0); }
    to { transform: translateX(25px) translateY(-10px); }
}
@keyframes floatCloud2 {
    from { transform: translateX(0) translateY(0); }
    to { transform: translateX(-25px) translateY(10px); }
}

/* ĐÀN CHIM ÉN (SWALLOWS - ĐUÔI CHẺ) */
.adm-flock-en {
    position: absolute;
    top: 12%;
    left: 18%;
    z-index: 3;
    pointer-events: none;
    animation: enChaoLieng 9s ease-in-out infinite alternate;
}
.adm-chim-en {
    position: absolute;
    filter: drop-shadow(0 2px 4px rgba(15, 23, 42, 0.15));
}
.adm-chim-en.en-1 { top: 0; left: 0; animation: enCanh 1.2s ease-in-out infinite alternate; }
.adm-chim-en.en-2 { top: 22px; left: 45px; animation: enCanh 1.2s ease-in-out infinite alternate 0.2s; }
.adm-chim-en.en-3 { top: -14px; left: 70px; animation: enCanh 1.2s ease-in-out infinite alternate 0.4s; }

@keyframes enChaoLieng {
    0% { transform: translate(0, 0) rotate(0deg); }
    50% { transform: translate(45px, -15px) rotate(-3deg); }
    100% { transform: translate(90px, -5px) rotate(2deg); }
}
@keyframes enCanh {
    0% { transform: scaleY(1); }
    50% { transform: scaleY(0.85); }
    100% { transform: scaleY(1); }
}

/* ĐÀN CHIM SẺ (SPARROWS - THÂN TRÒN DỄ THƯƠNG) */
.adm-flock-se {
    position: absolute;
    top: 28%;
    right: 20%;
    z-index: 3;
    pointer-events: none;
    animation: seTungCanh 7s ease-in-out infinite alternate;
}
.adm-chim-se {
    position: absolute;
    filter: drop-shadow(0 2px 4px rgba(120, 53, 15, 0.2));
}
.adm-chim-se.se-1 { top: 0; right: 0; animation: seVoCanh 0.6s ease-in-out infinite alternate; }
.adm-chim-se.se-2 { top: 30px; right: 35px; animation: seVoCanh 0.6s ease-in-out infinite alternate 0.15s; }
.adm-chim-se.se-3 { top: -18px; right: 55px; animation: seVoCanh 0.6s ease-in-out infinite alternate 0.3s; }

@keyframes seTungCanh {
    0% { transform: translate(0, 0); }
    50% { transform: translate(-35px, 12px); }
    100% { transform: translate(-70px, -8px); }
}
@keyframes seVoCanh {
    0% { transform: translateY(0) scaleY(1); }
    50% { transform: translateY(-3px) scaleY(0.9); }
    100% { transform: translateY(0) scaleY(1); }
}

.adm-curtain-entity {
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
.adm-curtain-entity.show {
    display: flex;
}

/* SUN STYLING (HIỆN MẶT TRỜI) */
.adm-sun-wrap {
    position: relative;
    width: 140px;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.adm-sun-glow {
    position: absolute;
    width: 340px;
    height: 340px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(245, 158, 11, 0.55) 0%, rgba(251, 191, 36, 0.25) 45%, transparent 75%);
    animation: sunGlowPulse 2s ease-in-out infinite alternate;
    z-index: 1;
}
.adm-sun-rays {
    position: absolute;
    width: 190px;
    height: 190px;
    z-index: 2;
    animation: spinSunRays 14s linear infinite;
}
.adm-sun-rays span {
    position: absolute;
    top: 0;
    left: 50%;
    width: 6px;
    height: 100%;
    margin-left: -3px;
    transform: rotate(var(--r));
}
.adm-sun-rays span::before {
    content: '';
    position: absolute;
    top: -10px;
    left: 0;
    width: 6px;
    height: 28px;
    background: linear-gradient(to top, rgba(245, 158, 11, 0.1), #fbbf24);
    border-radius: 4px;
    box-shadow: 0 0 16px #f59e0b;
}
.adm-sun-core {
    width: 104px;
    height: 104px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #fffbeb 0%, #fef08a 25%, #f59e0b 65%, #d97706 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 55px rgba(245, 158, 11, 0.95), 0 0 110px rgba(251, 191, 36, 0.65), inset 0 0 22px rgba(255,255,255,0.9);
    position: relative;
    z-index: 3;
    animation: sunCorePulse 2s ease-in-out infinite alternate;
}
.adm-sun-core i {
    font-size: 48px;
    color: #ffffff;
    filter: drop-shadow(0 2px 8px rgba(180, 83, 9, 0.8));
    animation: spinSunIcon 14s linear infinite;
}

/* MOON STYLING (HIỆN MẶT TRĂNG - CHẾ ĐỘ BAN ĐÊM) */
.adm-moon-wrap {
    position: relative;
    width: 140px;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.adm-moon-glow {
    position: absolute;
    width: 360px;
    height: 360px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(168, 85, 247, 0.55) 0%, rgba(139, 92, 246, 0.25) 45%, transparent 75%);
    animation: moonGlowPulse 2.2s ease-in-out infinite alternate;
    z-index: 1;
}
.adm-moon-ripple {
    position: absolute;
    border-radius: 50%;
    border: 1.5px solid rgba(168, 85, 247, 0.45);
    pointer-events: none;
    z-index: 1;
}
.adm-moon-ripple.mr-1 {
    width: 170px;
    height: 170px;
    animation: moonRipple 2.8s cubic-bezier(0.1, 0.8, 0.3, 1) infinite;
}
.adm-moon-ripple.mr-2 {
    width: 230px;
    height: 230px;
    animation: moonRipple 2.8s cubic-bezier(0.1, 0.8, 0.3, 1) infinite 0.9s;
}
@keyframes moonRipple {
    0% { transform: scale(0.65); opacity: 0.85; border-color: rgba(192, 132, 252, 0.7); }
    100% { transform: scale(1.7); opacity: 0; border-color: rgba(168, 85, 247, 0); }
}

.adm-moon-stars {
    position: absolute;
    width: 250px;
    height: 250px;
    z-index: 2;
}
.adm-moon-stars i {
    position: absolute;
    color: #e9d5ff;
    animation: starTwinkle 1.8s ease-in-out infinite alternate;
}
.adm-moon-stars .star-1 { top: 20px; left: 30px; font-size: 16px; animation-delay: 0.1s; color: #fbbf24; }
.adm-moon-stars .star-2 { top: 25px; right: 30px; font-size: 14px; animation-delay: 0.5s; color: #c084fc; }
.adm-moon-stars .star-3 { bottom: 25px; left: 35px; font-size: 13px; animation-delay: 0.9s; color: #e9d5ff; }
.adm-moon-stars .star-4 { bottom: 20px; right: 40px; font-size: 17px; animation-delay: 0.3s; color: #38bdf8; }
.adm-moon-core {
    width: 104px;
    height: 104px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #f5f3ff 0%, #ddd6fe 30%, #a855f7 70%, #7c3aed 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 60px rgba(168, 85, 247, 0.95), 0 0 120px rgba(139, 92, 246, 0.65), inset 0 0 25px rgba(255,255,255,0.9);
    position: relative;
    z-index: 3;
    animation: moonCoreFloat 3s ease-in-out infinite alternate;
}
.adm-moon-core i {
    font-size: 48px;
    color: #ffffff;
    filter: drop-shadow(0 0 15px rgba(233, 213, 255, 0.95)) drop-shadow(0 2px 10px rgba(91, 33, 182, 0.9));
}

/* MÂY ĐÊM HUYỀN ẢO (ETHEREAL NIGHT CLOUDS) */
.adm-night-cloud {
    position: absolute;
    color: rgba(45, 20, 85, 0.65);
    filter: drop-shadow(0 12px 28px rgba(147, 51, 234, 0.35)) drop-shadow(0 0 15px rgba(192, 132, 252, 0.2));
    pointer-events: none;
    z-index: 2;
}
.adm-night-cloud.ncloud-1 {
    top: 9%;
    left: 7%;
    font-size: 100px;
    animation: floatCloud1 9s ease-in-out infinite alternate;
    opacity: 0.85;
}
.adm-night-cloud.ncloud-2 {
    top: 14%;
    right: 8%;
    font-size: 115px;
    animation: floatCloud2 10s ease-in-out infinite alternate;
    opacity: 0.85;
}
.adm-night-cloud.ncloud-3 {
    bottom: 14%;
    left: 9%;
    font-size: 85px;
    animation: floatCloud1 8s ease-in-out infinite alternate 1s;
    opacity: 0.75;
}
.adm-night-cloud.ncloud-4 {
    bottom: 18%;
    right: 10%;
    font-size: 90px;
    animation: floatCloud2 9.5s ease-in-out infinite alternate 0.5s;
    opacity: 0.75;
}

/* SAO BĂNG LƯỚT QUA BẦU TRỜI (SHOOTING STARS / METEORS) */
.adm-shooting-star {
    position: absolute;
    height: 2px;
    background: linear-gradient(90deg, rgba(255,255,255,0), #c084fc, #38bdf8, #ffffff);
    border-radius: 999px;
    filter: drop-shadow(0 0 8px rgba(192, 132, 252, 0.95)) drop-shadow(0 0 14px rgba(56, 189, 248, 0.8));
    pointer-events: none;
    z-index: 3;
    opacity: 0;
}
.adm-shooting-star.star-shoot-1 {
    top: 15%;
    left: 15%;
    width: 140px;
    transform: rotate(-32deg);
    animation: shootMeteor1 4.5s ease-in-out infinite;
}
.adm-shooting-star.star-shoot-2 {
    top: 25%;
    right: 18%;
    width: 160px;
    transform: rotate(-38deg);
    animation: shootMeteor2 5.5s ease-in-out infinite 1.8s;
}
@keyframes shootMeteor1 {
    0% { transform: translate(-80px, -60px) rotate(-32deg); opacity: 0; width: 0; }
    15% { opacity: 1; width: 140px; }
    35% { transform: translate(140px, 90px) rotate(-32deg); opacity: 0; width: 40px; }
    100% { transform: translate(140px, 90px) rotate(-32deg); opacity: 0; width: 0; }
}
@keyframes shootMeteor2 {
    0% { transform: translate(80px, -60px) rotate(-38deg); opacity: 0; width: 0; }
    15% { opacity: 1; width: 160px; }
    35% { transform: translate(-150px, 110px) rotate(-38deg); opacity: 0; width: 40px; }
    100% { transform: translate(-150px, 110px) rotate(-38deg); opacity: 0; width: 0; }
}

/* NGÀN SAO VŨ TRỤ TRẢI RỘNG (SCATTERED NIGHT SKY STARS) */
.adm-night-star {
    position: absolute;
    pointer-events: none;
    z-index: 2;
    animation: starTwinkle 2s ease-in-out infinite alternate;
}

/* ĐOM ĐÓM PHÁT SÁNG (BIOLUMINESCENT FIREFLIES) */
.adm-firefly {
    position: absolute;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #a3e635;
    box-shadow: 0 0 10px #a3e635, 0 0 20px #84cc16, 0 0 30px #4d7c0f;
    pointer-events: none;
    z-index: 4;
}
.adm-firefly.ff-1 { top: 42%; left: 32%; animation: flyWander1 6s ease-in-out infinite; }
.adm-firefly.ff-2 { top: 46%; right: 30%; animation: flyWander2 7s ease-in-out infinite 1s; }
.adm-firefly.ff-3 { bottom: 34%; left: 36%; animation: flyWander3 6.5s ease-in-out infinite 2s; }
.adm-firefly.ff-4 { bottom: 25%; right: 34%; animation: flyWander1 7.5s ease-in-out infinite 0.5s; }
.adm-firefly.ff-5 { top: 32%; right: 42%; animation: flyWander2 5.5s ease-in-out infinite 1.5s; }

@keyframes flyWander1 {
    0%, 100% { transform: translate(0, 0) scale(0.8); opacity: 0.3; }
    50% { transform: translate(25px, -20px) scale(1.3); opacity: 1; }
}
@keyframes flyWander2 {
    0%, 100% { transform: translate(0, 0) scale(0.7); opacity: 0.2; }
    50% { transform: translate(-30px, 18px) scale(1.25); opacity: 0.95; }
}
@keyframes flyWander3 {
    0%, 100% { transform: translate(0, 0) scale(0.9); opacity: 0.4; }
    50% { transform: translate(20px, 22px) scale(1.35); opacity: 1; }
}

.adm-curtain-title {
    margin-top: 26px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 23px;
    font-weight: 900;
    letter-spacing: 2px;
    color: #ffffff;
    text-transform: uppercase;
}
.adm-curtain-sun .adm-curtain-title {
    text-shadow: 0 4px 18px rgba(0,0,0,0.8), 0 0 35px rgba(245, 158, 11, 0.75);
}
.adm-curtain-moon .adm-curtain-title {
    text-shadow: 0 4px 18px rgba(0,0,0,0.8), 0 0 35px rgba(168, 85, 247, 0.75);
}
.adm-curtain-sub {
    margin-top: 6px;
    font-size: 13.5px;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.adm-curtain-sun .adm-curtain-sub {
    color: #fde68a;
    text-shadow: 0 2px 10px rgba(0,0,0,0.7);
}
.adm-curtain-moon .adm-curtain-sub {
    color: #ddd6fe;
    text-shadow: 0 2px 10px rgba(0,0,0,0.7);
}

@keyframes spinSunRays { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes spinSunIcon { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes sunGlowPulse { from { transform: scale(0.92); opacity: 0.7; } to { transform: scale(1.15); opacity: 1; } }
@keyframes sunCorePulse { from { transform: scale(0.96); } to { transform: scale(1.04); } }
@keyframes moonGlowPulse { from { transform: scale(0.92); opacity: 0.7; } to { transform: scale(1.15); opacity: 1; } }
@keyframes moonCoreFloat { from { transform: translateY(-4px) rotate(-4deg); } to { transform: translateY(4px) rotate(4deg); } }
@keyframes starTwinkle { from { opacity: 0.3; transform: scale(0.8); } to { opacity: 1; transform: scale(1.25); } }
</style>

<div class="adm-overlay" id="admOverlay" onclick="toggleAdminNav()"></div>

<!-- SIDEBAR -->
<aside class="adm-sidebar" id="admSidebar">
    
    <!-- Logo & Brand Header -->
    <a href="/tkb/admin/dashboard.php" class="adm-brand">
        <div class="adm-brand-logo">
            <img src="/tkb/assets/img/school_logo.png?v=<?= time() ?>" alt="Logo Trường Cao Đẳng Cà Mau">
        </div>
        <div class="adm-brand-text">
            <h2>CAO ĐẲNG CÀ MAU</h2>
            <span>HỆ THỐNG QUẢN TRỊ</span>
        </div>
    </a>

    <!-- Admin User Card -->
    <a href="/tkb/admin/profile.php" class="adm-user-card" title="Xem & chỉnh sửa hồ sơ quản trị viên" style="text-decoration:none; cursor:pointer;">
        <div class="adm-user-av">
            <img src="<?= htmlspecialchars($current_adm_avatar) ?>" onerror="this.onerror=null; this.src='/tkb/assets/img/avatar_khanh.png';" alt="Admin Avatar">
        </div>
        <div class="adm-user-info">
            <div class="adm-user-name"><?= htmlspecialchars($current_adm_name) ?><?php if ($is_tuyen): ?> <i class="fa-solid fa-circle-check" style="color:#7c3aed; font-size:12px; margin-left:3px;" title="Tài khoản Quản trị viên phụ"></i><?php endif; ?></div>
            <div class="adm-user-role"><span class="adm-status-dot"></span> <?= (function_exists('isSuperAdmin') && isSuperAdmin()) ? 'Quản trị viên cao cấp' : ($is_tuyen ? 'Quản lý Giáo viên &amp; Sinh viên' : 'Quản trị viên phụ') ?></div>
        </div>
        <i class="fa-solid fa-chevron-right" style="font-size:10px; color:#a79bb7; margin-left:auto;"></i>
    </a>

    <!-- Navigation List -->
    <ul class="adm-nav-list">
        <li class="adm-nav-item <?= $cur_file === 'dashboard.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/dashboard.php" class="<?= $cur_file === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-house"></i>
                <span>Tổng quan</span>
            </a>
        </li>
        <li class="adm-nav-item <?= in_array($cur_file, ['giangvien.php', 'teachers.php']) ? 'active' : '' ?>">
            <a href="/tkb/admin/giangvien.php" class="<?= in_array($cur_file, ['giangvien.php', 'teachers.php']) ? 'active' : '' ?>">
                <i class="fa-solid fa-chalkboard-user"></i>
                <span>Quản lý Giảng viên</span>
            </a>
        </li>
        <li class="adm-nav-item <?= in_array($cur_file, ['students.php', 'sinhvien.php']) ? 'active' : '' ?>">
            <a href="/tkb/admin/students.php" class="<?= in_array($cur_file, ['students.php', 'sinhvien.php']) ? 'active' : '' ?>">
                <i class="fa-solid fa-user-graduate"></i>
                <span>Quản lý Sinh viên</span>
            </a>
        </li>
        <li class="adm-nav-item <?= in_array($cur_file, ['baidang.php']) ? 'active' : '' ?>">
            <a href="/tkb/admin/baidang.php" class="<?= in_array($cur_file, ['baidang.php']) ? 'active' : '' ?>">
                <i class="fa-solid fa-file-pen" style="color: #ec4899;"></i>
                <span>Quản lý Bài đăng</span>
            </a>
        </li>

        <?php if (!$is_tuyen): ?>
        <li class="adm-nav-item <?= $cur_file === 'lop.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/lop.php" class="<?= $cur_file === 'lop.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>Quản lý lớp</span>
            </a>
        </li>
        <li class="adm-nav-item <?= $cur_file === 'monhoc.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/monhoc.php" class="<?= $cur_file === 'monhoc.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-book"></i>
                <span>Môn học</span>
            </a>
        </li>

        <!-- ĐÀO TẠO & ĐIỂM SỐ SINH VIÊN -->
        <li style="padding: 12px 18px 4px; font-size: 10px; font-weight: 800; color: #a79bb7; text-transform: uppercase; letter-spacing: 0.8px;">
            Đào Tạo &amp; Điểm Số
        </li>
        <li class="adm-nav-item <?= $cur_file === 'diem.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/diem.php" class="<?= $cur_file === 'diem.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-graduation-cap" style="color: #10b981;"></i>
                <span>Bảng Điểm Sinh Viên</span>
            </a>
        </li>
        <li class="adm-nav-item <?= $cur_file === 'quanly_thuchanh.php' ? 'active' : '' ?>">
            <a href="/tkb/teacher/quanly_thuchanh.php" class="<?= $cur_file === 'quanly_thuchanh.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-code" style="color: #38bdf8;"></i>
                <span>Quản lý thực hành</span>
            </a>
        </li>
        <li class="adm-nav-item <?= $cur_file === 'baitap.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/baitap.php" class="<?= $cur_file === 'baitap.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-pen-to-square" style="color: #a855f7;"></i>
                <span>Bài Tập Giáo Viên Đăng</span>
            </a>
        </li>
        <li class="adm-nav-item <?= $cur_file === 'tailieu.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/tailieu.php" class="<?= $cur_file === 'tailieu.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-folder-open" style="color: #c084fc;"></i>
                <span>Tài Liệu &amp; Video Bài Giảng</span>
            </a>
        </li>
        <li class="adm-nav-item <?= $cur_file === 'quiz.php' ? 'active' : '' ?>">
            <a href="/tkb/teacher/quiz.php" class="<?= $cur_file === 'quiz.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-brain" style="color: #a855f7;"></i>
                <span>Quản lý Quiz &amp; Thi</span>
            </a>
        </li>

        <!-- TRÍ TUỆ NHÂN TẠO & AI MODELS -->
        <li style="padding: 12px 18px 4px; font-size: 10px; font-weight: 800; color: #a79bb7; text-transform: uppercase; letter-spacing: 0.8px; display:flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-robot" style="font-size:10px; color:#c084fc;"></i>
            <span>Trí Tuệ Nhân Tạo &amp; Models AI</span>
        </li>
        <li class="adm-nav-item <?= $cur_file === 'quanly_ai_models.php' ? 'active' : '' ?>" id="navItemAiModels">
            <a href="/tkb/admin/quanly_ai_models.php" class="<?= $cur_file === 'quanly_ai_models.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-microchip" style="color: <?= $cur_file === 'quanly_ai_models.php' ? '#fff' : '#38bdf8' ?>;"></i>
                <span>Models AI Botchat</span>
            </a>
        </li>
        <li class="adm-nav-item <?= in_array($cur_file, ['ai_studio.php', 'botchat.php']) ? 'active' : '' ?>">
            <a href="/tkb/admin/ai_studio.php" class="<?= in_array($cur_file, ['ai_studio.php', 'botchat.php']) ? 'active' : '' ?>">
                <i class="fa-solid fa-wand-magic-sparkles" style="color: <?= in_array($cur_file, ['ai_studio.php', 'botchat.php']) ? '#fff' : '#c084fc' ?>;"></i>
                <span>AI Cosmic Admin Studio</span>
            </a>
        </li>

        <li class="adm-nav-item <?= $cur_file === 'doan.php' ? 'active' : '' ?>">
            <a href="/tkb/teacher/doan.php" class="<?= $cur_file === 'doan.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-file-code" style="color: #818cf8;"></i>
                <span>Quản lý Đồ án</span>
            </a>
        </li>
        <li class="adm-nav-item <?= $cur_file === 'baocao_tien_do.php' ? 'active' : '' ?>">
            <a href="/tkb/teacher/baocao_tien_do.php" class="<?= $cur_file === 'baocao_tien_do.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-line" style="color: #ec4899;"></i>
                <span>Báo cáo &amp; Bảng điểm</span>
            </a>
        </li>

        <!-- HỆ THỐNG & TIỆN ÍCH -->
        <li style="padding: 12px 18px 4px; font-size: 10px; font-weight: 800; color: #a79bb7; text-transform: uppercase; letter-spacing: 0.8px;">
            Hệ Thống &amp; Tiện Ích
        </li>
        <li class="adm-nav-item <?= $cur_file === 'youtube.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/youtube.php" class="<?= $cur_file === 'youtube.php' ? 'active' : '' ?>">
                <i class="fa-brands fa-youtube" style="color: <?= $cur_file === 'youtube.php' ? '#fff' : '#f43f5e' ?>;"></i>
                <span>Quản lý YouTube</span>
            </a>
        </li>
        <li class="adm-nav-item <?= $cur_file === 'phanquyen.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/phanquyen.php" class="<?= $cur_file === 'phanquyen.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-user-shield"></i>
                <span>Phân quyền</span>
            </a>
        </li>
        <li class="adm-nav-item <?= $cur_file === 'nhatky.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/nhatky.php" class="<?= $cur_file === 'nhatky.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Nhật ký hệ thống</span>
            </a>
        </li>
        <li class="adm-nav-item <?= $cur_file === 'backup.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/backup.php" class="<?= $cur_file === 'backup.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-database"></i>
                <span>Quản lý Backup</span>
            </a>
        </li>
        <li class="adm-nav-item <?= $cur_file === 'caidat.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/caidat.php" class="<?= $cur_file === 'caidat.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gears"></i>
                <span>Cài đặt hệ thống</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="adm-nav-item <?= $cur_file === 'profile.php' ? 'active' : '' ?>">
            <a href="/tkb/admin/profile.php" class="<?= $cur_file === 'profile.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-id-badge" style="color: <?= $cur_file === 'profile.php' ? '#fff' : '#f472b6' ?>;"></i>
                <span>Hồ sơ cá nhân</span>
            </a>
        </li>
    </ul>




    <!-- Sidebar Logout Button -->
    <div style="padding: 6px 16px 18px; margin-top: auto;">
        <a href="/tkb/api/logout.php" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất khỏi tài khoản Quản trị viên?');" style="display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:10px 16px; border-radius:12px; background:rgba(244,63,94,0.12); border:1px solid rgba(244,63,94,0.3); color:#fb7185; font-size:13px; font-weight:800; text-decoration:none; transition:all 0.2s; box-sizing:border-box;">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Đăng Xuất Admin</span>
        </a>
    </div>

</aside>
<script>
// Khôi phục vị trí cuộn thanh sidebar NGAY LẬP TỨC trước khi trình duyệt vẽ giao diện (chống giật 100%)
(function() {
    try {
        var sb = document.getElementById('admSidebar');
        if (!sb) return;
        var saved = sessionStorage.getItem('adm_sidebar_scroll');
        if (saved !== null) {
            sb.scrollTop = parseInt(saved, 10);
        } else {
            var act = sb.querySelector('.adm-nav-item.active');
            if (act) {
                var target = act.offsetTop - (sb.clientHeight / 2) + (act.clientHeight / 2);
                sb.scrollTop = Math.max(0, target);
            }
        }
    } catch(e) {}
})();
</script>

<?php if (!$is_tuyen): ?>
<!-- MODAL QUẢN LÝ & DANH SÁCH NHẠC LOFI CHILL -->
<div class="modal-overlay" id="admLofiPlaylistModal" style="z-index:99999;">
  <div class="modal-box" style="background:#140d27; border:1px solid rgba(168,85,247,0.35); border-radius:22px; color:#f3e8ff; max-width:650px; width:94%; box-shadow:0 15px 40px rgba(0,0,0,0.7); overflow:hidden; padding:0;">
    
    <!-- Modal Header with Vinyl Visualizer -->
    <div style="background:linear-gradient(135deg, rgba(147,51,234,0.3), rgba(30,15,55,0.95)), url('/tkb/assets/img/lofi_night_sky.jpg') center/cover; padding:22px; border-bottom:1px solid rgba(168,85,247,0.25); position:relative;">
      <button type="button" onclick="toggleModal('admLofiPlaylistModal')" style="position:absolute; top:16px; right:16px; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); color:#fff; width:30px; height:30px; border-radius:50%; cursor:pointer; font-size:13px; display:flex; align-items:center; justify-content:center; transition:0.2s;">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div style="display:flex; align-items:center; gap:18px;">
        <!-- Spinning Vinyl Turntable Record -->
        <div style="position:relative; width:88px; height:88px; flex-shrink:0;">
          <div id="modalVinylDisk" style="width:88px; height:88px; border-radius:50%; background:radial-gradient(circle, #2d1847 25%, #0d0818 26%, #1a0f2e 50%, #0d0818 51%, #25133d 75%, #0d0818 76%, #150a26 100%); border:3px solid rgba(168,85,247,0.45); box-shadow:0 6px 22px rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; animation: spinVinyl 7s linear infinite; animation-play-state: paused; overflow:hidden; position:relative;">
            <img id="modalDiskThumb" src="" style="width:100%; height:100%; object-fit:cover; border-radius:50%; display:none;">
            <div id="modalCenterHole" style="position:absolute; width:26px; height:26px; border-radius:50%; background:linear-gradient(135deg, #a855f7, #38bdf8); border:3px solid #0d0818; display:flex; align-items:center; justify-content:center; box-shadow:0 0 6px rgba(0,0,0,0.8); z-index:2;">
              <i class="fa-solid fa-compact-disc" id="modalCenterIcon" style="color:#ffffff; font-size:12px;"></i>
            </div>
          </div>
        </div>

        <!-- Now Playing Track Info -->
        <div style="flex:1; min-width:0;">
          <div style="font-size:10px; font-weight:800; color:#38bdf8; text-transform:uppercase; letter-spacing:1px; margin-bottom:3px; display:flex; align-items:center; gap:6px;">
            <span class="adm-status-dot" style="width:6px; height:6px;"></span>
            <span id="modalTrackBadge">Đang phát Lofi</span>
          </div>
          <div id="modalTrackTitle" style="font-size:16px; font-weight:800; color:#ffffff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:2px;">
            Coffee Shop Chillhop
          </div>
          <div id="modalTrackArtist" style="font-size:11.5px; color:#c4b5fd; margin-bottom:8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            Lofi Lounge • Relax &amp; Focus
          </div>

          <!-- Progress Bar & Seek -->
          <div style="display:flex; align-items:center; gap:10px;">
            <span id="modalCurrentTime" style="font-size:11px; color:#a79bb7; font-family:monospace; min-width:32px;">0:00</span>
            <input type="range" id="modalSeekSlider" min="0" max="100" value="0" oninput="seekLofiTrack(this.value)" style="flex:1; accent-color:#a855f7; height:4px; cursor:pointer;">
            <span id="modalTotalDuration" style="font-size:11px; color:#a79bb7; font-family:monospace; min-width:32px;">0:00</span>
          </div>
        </div>
      </div>

      <!-- Controls Row & Volume in Modal Header -->
      <div style="display:flex; align-items:center; justify-content:space-between; margin-top:16px; padding-top:12px; border-top:1px solid rgba(255,255,255,0.08); flex-wrap:wrap; gap:10px;">
        <!-- Playback buttons -->
        <div style="display:flex; align-items:center; gap:10px;">
          <button type="button" class="adm-music-ctrl-btn" onclick="prevLofiTrack()" title="Bài trước" style="width:34px; height:34px; font-size:13px;">
            <i class="fa-solid fa-backward-step"></i>
          </button>
          <button type="button" class="adm-music-ctrl-btn adm-music-ctrl-play" id="modalPlayBtn" onclick="toggleLofiMusic()" title="Phát / Dừng" style="width:40px; height:40px; font-size:15px;">
            <i class="fa-solid fa-play" id="modalPlayIcon"></i>
          </button>
          <button type="button" class="adm-music-ctrl-btn" onclick="nextLofiTrack()" title="Bài kế tiếp" style="width:34px; height:34px; font-size:13px;">
            <i class="fa-solid fa-forward-step"></i>
          </button>

          <!-- Button to toggle PiP screen when YouTube track is active -->
          <button type="button" id="modalYtPipBtn" onclick="toggleYtPip()" class="adm-music-ctrl-btn" style="display:none; width:auto; height:32px; border-radius:8px; padding:0 12px; font-size:11.5px; font-weight:700; gap:6px; background:rgba(244,63,94,0.18); border-color:rgba(244,63,94,0.4); color:#fecdd3;">
            <i class="fa-brands fa-youtube" style="color:#f43f5e;"></i> <span id="ytPipBtnText">Xem Video Mini</span>
          </button>
        </div>

        <!-- Volume Slider -->
        <div style="display:flex; align-items:center; gap:8px;">
          <button type="button" class="adm-music-ctrl-btn" onclick="toggleMuteLofi()" id="modalMuteBtn" title="Tắt / Bật tiếng" style="width:30px; height:30px;">
            <i class="fa-solid fa-volume-high" id="modalMuteIcon"></i>
          </button>
          <input type="range" id="modalVolumeSlider" min="0" max="1" step="0.05" value="0.7" oninput="setLofiVolume(this.value)" style="width:80px; accent-color:#38bdf8; height:4px; cursor:pointer;" title="Âm lượng">
        </div>
      </div>
    </div>

    <!-- Modal Content & Tabs -->
    <div style="padding:16px 20px 20px;">
      <!-- Tab Headers -->
      <div style="display:flex; gap:8px; margin-bottom:14px; border-bottom:1px solid rgba(168,85,247,0.18); padding-bottom:10px; flex-wrap:wrap;">
        <button type="button" id="tabBtnYt" onclick="switchMusicModalTab('yt')" style="background:rgba(244,63,94,0.25); color:#ffffff; border:1px solid rgba(244,63,94,0.4); border-radius:10px; padding:6px 14px; font-size:12px; font-weight:700; cursor:pointer;">
          <i class="fa-brands fa-youtube" style="color:#f43f5e;"></i> Tìm &amp; Thêm Nhạc YouTube
        </button>
        <button type="button" id="tabBtnAddCustom" onclick="switchMusicModalTab('add')" style="background:transparent; color:#a79bb7; border:1px solid transparent; border-radius:10px; padding:6px 14px; font-size:12px; font-weight:700; cursor:pointer;">
          <i class="fa-solid fa-link"></i> Thêm File / MP3
        </button>
        <button type="button" id="tabBtnMyTracks" onclick="switchMusicModalTab('my')" style="background:transparent; color:#a79bb7; border:1px solid transparent; border-radius:10px; padding:6px 14px; font-size:12px; font-weight:700; cursor:pointer;">
          <i class="fa-solid fa-folder-open"></i> Nhạc Của Bạn (<span id="myTracksCount">0</span>)
        </button>
      </div>

      <!-- TAB 1: YouTube Search & Importer Tab -->
      <div id="musicTabYt" style="display:block;">
        <div style="background:rgba(244,63,94,0.08); border:1px solid rgba(244,63,94,0.25); border-radius:16px; padding:16px; margin-bottom:14px;">
          <div style="font-weight:800; font-size:13.5px; color:#fecdd3; margin-bottom:4px; display:flex; align-items:center; gap:8px;">
            <i class="fa-brands fa-youtube" style="color:#f43f5e; font-size:18px;"></i> Tìm Kiếm Trực Tiếp Bài Hát Trên YouTube
          </div>
          <div style="font-size:11.5px; color:#a79bb7; margin-bottom:12px;">
            Gõ tên bất kỳ bài hát hoặc ca sĩ (VD: <em>Trái đất ôm mặt trời, Kai Đinh, Lofi Chill...</em>) để phát ngay:
          </div>

          <div style="display:flex; gap:8px; margin-bottom:12px;">
            <input type="text" id="ytSearchInput" onkeydown="if(event.key==='Enter') executeYtSearch()" placeholder="Nhập tên bài hát hoặc ca sĩ cần tìm..." style="flex:1; background:#190e33; border:1px solid rgba(244,63,94,0.35); border-radius:10px; padding:10px 14px; color:#fff; font-size:13px; outline:none;">
            <button type="button" onclick="executeYtSearch()" id="ytSearchSubmitBtn" style="background:linear-gradient(135deg, #f43f5e, #e11d48); color:#fff; border:none; border-radius:10px; padding:0 18px; font-size:12.5px; font-weight:800; cursor:pointer; display:flex; align-items:center; gap:6px; box-shadow:0 4px 15px rgba(244,63,94,0.4);">
              <i class="fa-solid fa-magnifying-glass"></i> Tìm Kiếm
            </button>
          </div>

          <!-- Live Search Results List -->
          <div id="ytSearchResults" style="max-height:220px; overflow-y:auto; display:none; margin-bottom:12px; padding-right:4px;"></div>

          <!-- Accordion to paste manual URL -->
          <div style="border-top:1px solid rgba(244,63,94,0.18); padding-top:10px; margin-top:8px;">
            <details style="cursor:pointer;">
              <summary style="font-size:11.5px; font-weight:700; color:#fbcfe8; outline:none; user-select:none;">
                <i class="fa-solid fa-link"></i> Hoặc dán đường link YouTube thủ công (click để mở)
              </summary>
              <div style="margin-top:10px;">
                <input type="url" id="customYtUrlInput" oninput="handleYtUrlInput(this.value)" placeholder="Dán link YouTube (VD: https://www.youtube.com/watch?v=...)" style="width:100%; box-sizing:border-box; background:#190e33; border:1px solid rgba(244,63,94,0.3); border-radius:8px; padding:8px 12px; color:#fff; font-size:12px; margin-bottom:8px;">
                <div id="ytPreviewBox" style="display:none; align-items:center; gap:10px; background:#150b28; border:1px solid rgba(244,63,94,0.2); border-radius:8px; padding:8px; margin-bottom:8px;">
                  <img id="ytPreviewThumb" src="" style="width:60px; height:38px; object-fit:cover; border-radius:4px;">
                  <div style="flex:1; min-width:0;">
                    <div id="ytFetchStatus" style="font-size:11.5px; font-weight:700; color:#34d399;">✓ Đã nhận diện</div>
                  </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                  <input type="text" id="customYtNameInput" placeholder="Tên bài hát (tự động nhận diện)" style="background:#190e33; border:1px solid rgba(168,85,247,0.3); border-radius:8px; padding:7px 10px; color:#fff; font-size:11.5px;">
                  <input type="text" id="customYtArtistInput" placeholder="Nghệ sĩ / Kênh" style="background:#190e33; border:1px solid rgba(168,85,247,0.3); border-radius:8px; padding:7px 10px; color:#fff; font-size:11.5px;">
                </div>
                <button type="button" onclick="submitAddCustomYtTrack()" style="background:linear-gradient(135deg, #f43f5e, #e11d48); color:#fff; border:none; border-radius:8px; padding:7px 14px; font-size:12px; font-weight:800; cursor:pointer;">
                  <i class="fa-brands fa-youtube"></i> Thêm Vào Danh Sách &amp; Phát
                </button>
              </div>
            </details>
          </div>
        </div>

        <!-- Quick suggestions -->
        <div style="font-size:11.5px; font-weight:700; color:#c4b5fd; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
          <i class="fa-solid fa-fire" style="color:#f59e0b;"></i> Kênh Lofi YouTube hay nghe:
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
          <button type="button" onclick="quickAddYt('ohgodOTSHjA', 'Trái đất ôm Mặt trời ☀️', 'Kai Đinh x Grey D')" style="background:rgba(244,63,94,0.18); border:1px solid rgba(244,63,94,0.35); color:#fecdd3; border-radius:8px; padding:6px 12px; font-size:11px; cursor:pointer; font-weight:700;">
            ☀️ Trái Đất Ôm Mặt Trời
          </button>
          <button type="button" onclick="quickAddYt('jfKfPfyJRdk', 'Lofi Girl - Study Beats ☕', 'Lofi Girl')" style="background:rgba(168,85,247,0.15); border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:8px; padding:6px 12px; font-size:11px; cursor:pointer; font-weight:600;">
            ☕ Lofi Girl Study
          </button>
          <button type="button" onclick="quickAddYt('4xDzrJKXOOY', 'Synthwave Radio - Chill Synth 🌆', 'Lofi Girl')" style="background:rgba(168,85,247,0.15); border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:8px; padding:6px 12px; font-size:11px; cursor:pointer; font-weight:600;">
            🌆 Synthwave Radio
          </button>
          <button type="button" onclick="quickAddYt('plrqhgNHgHY', 'Lofi Chill Việt Nhẹ Nhàng 🌸', 'Lofi Chill VN')" style="background:rgba(168,85,247,0.15); border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; border-radius:8px; padding:6px 12px; font-size:11px; cursor:pointer; font-weight:600;">
            🌸 Lofi Việt Nam
          </button>
        </div>
      </div>

      <!-- TAB 3: Add Custom Music Form (MP3 / Audio URL) -->
      <div id="musicTabAdd" style="display:none;">
        <div style="background:rgba(168,85,247,0.08); border:1px solid rgba(168,85,247,0.2); border-radius:14px; padding:14px; margin-bottom:12px;">
          <div style="font-weight:700; font-size:12.5px; color:#f3e8ff; margin-bottom:4px;"><i class="fa-solid fa-link" style="color:#38bdf8;"></i> Thêm Bằng Link File Âm Thanh Trực Tiếp</div>
          <div style="font-size:11px; color:#a79bb7; margin-bottom:10px;">Hỗ trợ link trực tiếp file MP3, AAC, M4A, hoặc luồng phát Radio.</div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:8px;">
            <input type="text" id="customTrackNameInput" placeholder="Tên bài hát (VD: Lofi Chill Remix)" style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); border-radius:8px; padding:8px 12px; color:#fff; font-size:12px;">
            <input type="text" id="customTrackArtistInput" placeholder="Nghệ sĩ / Thể loại (VD: Acoustic)" style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); border-radius:8px; padding:8px 12px; color:#fff; font-size:12px;">
          </div>
          <input type="url" id="customTrackUrlInput" placeholder="Nhập liên kết nhạc trực tiếp (https://.../song.mp3)" style="width:100%; box-sizing:border-box; background:#1f1338; border:1px solid rgba(168,85,247,0.3); border-radius:8px; padding:8px 12px; color:#fff; font-size:12px; margin-bottom:10px;">
          <button type="button" onclick="submitAddCustomTrackUrl()" style="background:linear-gradient(135deg, #7c3aed, #9333ea); color:#fff; border:none; border-radius:8px; padding:7px 14px; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-plus"></i> Lưu &amp; Thêm Vào Danh Sách
          </button>
        </div>

        <div style="background:rgba(56,189,248,0.08); border:1px solid rgba(56,189,248,0.2); border-radius:14px; padding:14px;">
          <div style="font-weight:700; font-size:12.5px; color:#f3e8ff; margin-bottom:4px;"><i class="fa-solid fa-file-audio" style="color:#38bdf8;"></i> Chọn Tệp Nhạc Từ Máy Tính</div>
          <div style="font-size:11px; color:#a79bb7; margin-bottom:8px;">Chọn bài hát MP3/WAV/M4A từ máy tính để phát ngay trên trình duyệt.</div>
          <input type="file" id="customLocalFileInput" accept="audio/*" onchange="handleLocalAudioFile(event)" style="font-size:12px; color:#c4b5fd;">
        </div>
      </div>

      <!-- TAB 4: My Custom Tracks List -->
      <div id="musicTabMy" style="display:none; max-height:220px; overflow-y:auto; padding-right:4px;">
        <div id="myTracksContainer"></div>
      </div>
    </div>

  </div>
</div>

<!-- FLOATING BACKGROUND YOUTUBE PLAYER HOLDER (DO NOT DISPLAY:NONE TO PREVENT AUDIO FREEZE) -->
<div id="lofiYtPlayerHolder" style="position:fixed; z-index:999998; bottom:16px; right:16px; width:1px; height:1px; opacity:0.001; pointer-events:none; border-radius:14px; overflow:hidden; border:1px solid rgba(168,85,247,0.5); box-shadow:0 12px 35px rgba(0,0,0,0.85); background:#0f091f; transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
    <div style="background:#190e33; padding:6px 12px; display:flex; align-items:center; justify-content:space-between; font-size:11px; color:#f3e8ff; border-bottom:1px solid rgba(168,85,247,0.2);">
        <span style="font-weight:800; display:flex; align-items:center; gap:6px;">
            <i class="fa-brands fa-youtube" style="color:#f43f5e; font-size:14px;"></i> <span id="ytPipTitle" style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">YouTube Lofi</span>
        </span>
        <div style="display:flex; align-items:center; gap:8px;">
            <button type="button" onclick="openLofiPlaylistModal()" title="Mở bảng nhạc" style="background:none; border:none; color:#c4b5fd; cursor:pointer; font-size:11px;"><i class="fa-solid fa-list-ul"></i></button>
            <button type="button" onclick="toggleYtPip(false)" title="Thu nhỏ (vẫn tiếp tục nghe nhạc)" style="background:none; border:none; color:#a79bb7; cursor:pointer; font-size:12px;"><i class="fa-solid fa-chevron-down"></i></button>
        </div>
    </div>
    <div id="lofiYtPlayerTarget" style="width:100%; height:calc(100% - 28px);"></div>
</div>
<?php endif; ?>


    <!-- TOPBAR -->
    <header class="adm-topbar">
        <div class="adm-topbar-left">
            <button type="button" class="adm-ham-btn" onclick="toggleAdminNav()">
                <i class="fa-solid fa-bars"></i>
            </button>
            
            <div class="adm-search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" class="adm-search-input" placeholder="Tìm kiếm nhanh...">
                <span class="adm-shortcut-badge">Ctrl + K</span>
            </div>
        </div>

        <div class="adm-topbar-right">
            <?php if (!$is_tuyen): ?>
            <!-- Ultra-Premium Lofi Chill Dynamic Topbar Island -->
            <div class="adm-topbar-music" id="topbarMusicPlayer" title="Cuộn chuột để tăng/giảm âm lượng">
                <!-- Floating Volume Tooltip -->
                <div class="adm-music-vol-tooltip" id="topbarVolTooltip">🔊 70%</div>

                <!-- Bottom Progress Glow Line -->
                <div class="adm-music-progress-line">
                    <div class="adm-music-progress-fill" id="topbarProgressFill"></div>
                </div>

                <!-- Vinyl Disc with Thumbnail or Glowing Disc -->
                <div class="adm-music-disk-wrap" onclick="openLofiPlaylistModal(event)" title="Mở Studio Lofi Chill">
                    <div class="adm-music-disk" id="topbarMusicDisk">
                        <img id="topbarDiskThumb" src="" style="display:none;" alt="Cover">
                        <i class="fa-solid fa-compact-disc" id="topbarDiskIcon"></i>
                        <div class="adm-music-disk-center"></div>
                    </div>
                </div>

                <!-- Song Info with Marquee & Visualizer Wave -->
                <div class="adm-music-meta" onclick="openLofiPlaylistModal(event)" title="Nhấp mở danh sách &amp; tìm nhạc YouTube">
                    <div class="adm-music-marquee-wrap">
                        <span class="adm-music-song" id="topbarMusicSong">Lofi Chill</span>
                    </div>
                    <div class="adm-music-sub-row">
                        <div class="adm-music-wave" id="topbarMusicWave">
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                        </div>
                        <span class="adm-music-source-badge" id="topbarSourceBadge">LOFI</span>
                    </div>
                </div>

                <!-- Control Buttons -->
                <button type="button" class="adm-music-ctrl-btn adm-music-ctrl-play" id="topbarPlayBtn" onclick="toggleLofiMusic(event)" title="Phát / Tạm dừng">
                    <i class="fa-solid fa-play" id="topbarPlayIcon" style="font-size:10px; margin-left:1px;"></i>
                </button>
                <button type="button" class="adm-music-ctrl-btn" onclick="nextLofiTrack(event)" title="Chuyển bài tiếp theo">
                    <i class="fa-solid fa-forward-step" style="font-size:10px;"></i>
                </button>
                <button type="button" class="adm-music-ctrl-btn adm-music-pip-btn" id="topbarPipToggleBtn" onclick="toggleYtPip(event)" title="Bật / Ẩn màn hình video mini" style="display:none;">
                    <i class="fa-solid fa-tv" style="font-size:9.5px;"></i>
                </button>
                <button type="button" class="adm-music-ctrl-btn" onclick="openLofiPlaylistModal(event)" title="Bảng điều khiển Studio &amp; Tìm nhạc">
                    <i class="fa-solid fa-sliders" style="font-size:10px;"></i>
                </button>
            </div>
            <?php endif; ?>
            <!-- Nút Chuyển Đổi Chế Độ Sáng / Tối (Light Mode / Dark Mode) -->
            <button type="button" class="adm-action-icon-btn adm-theme-btn" id="admThemeToggleBtn" onclick="toggleAdminTheme(event)" title="Chuyển đổi Chế độ Sáng / Tối (Light / Dark Mode)" style="background:rgba(245,158,11,0.15); border-color:rgba(245,158,11,0.4); color:#f59e0b;">
                <i class="fa-solid fa-sun" id="admThemeIcon"></i>
            </button>
            <a href="/tkb/admin/nhatky.php" class="adm-action-icon-btn" title="Thông báo hệ thống">
                <i class="fa-regular fa-bell"></i>
                <span class="adm-badge-count" style="<?= $is_tuyen ? 'background:#ef4444;' : '' ?>"><?= $is_tuyen ? '3' : '1' ?></span>
            </a>

            <?php if ($is_tuyen): ?>
            <a href="/tkb/teacher/thongbao.php" class="adm-action-icon-btn" title="Hộp thư &amp; Trao đổi">
                <i class="fa-regular fa-envelope"></i>
                <span class="adm-badge-count" style="background:#ef4444;">2</span>
            </a>
            <?php endif; ?>

            <button type="button" class="adm-action-icon-btn" onclick="toggleFullScreen()" title="Toàn màn hình">
                <i class="fa-solid fa-expand"></i>
            </button>

            <!-- Profile Menu Container with Dropdown -->
            <div style="position: relative;">
                <div class="adm-profile-pill" onclick="toggleProfileDropdown(event)">
                    <div class="adm-profile-av">
                        <img src="<?= htmlspecialchars($current_adm_avatar) ?>" onerror="this.onerror=null; this.src='/tkb/assets/img/avatar_khanh.png';" alt="Admin">
                    </div>
                    <div class="adm-profile-meta">
                        <strong><?= htmlspecialchars($current_adm_name) ?></strong>
                        <span><span class="adm-status-dot" style="width:5px;height:5px;"></span> <?= htmlspecialchars($current_adm_user) ?></span>
                    </div>
                    <i class="fa-solid fa-chevron-down" style="font-size: 10px; color: #a79bb7; margin-left: 4px;"></i>
                </div>

                <!-- Dropdown Menu -->
                <div id="admProfileDropdown" style="display:none; position:absolute; top:calc(100% + 8px); right:0; width:230px; background:#140d27; border:1px solid rgba(168,85,247,0.25); border-radius:14px; box-shadow:0 10px 30px rgba(0,0,0,0.5); padding:8px; z-index:9999;">
                    <div style="padding:8px 10px 10px; border-bottom:1px solid rgba(168,85,247,0.12); margin-bottom:6px;">
                        <div style="font-weight:800; font-size:13px; color:#f3e8ff;"><?= htmlspecialchars($current_adm_name) ?></div>
                        <div style="font-size:11.5px; color:#a79bb7;">Vai trò: <?= $is_tuyen ? 'Quản lý Giáo viên &amp; Sinh viên' : 'Quản trị viên cao cấp' ?></div>
                    </div>
                    <a href="/tkb/admin/profile.php" style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; color:#e9d5ff; font-size:13px; font-weight:600; text-decoration:none; transition:0.2s;" onmouseover="this.style.background='rgba(168,85,247,0.18)'; this.style.color='#ffffff'" onmouseout="this.style.background='transparent'; this.style.color='#e9d5ff'">
                        <i class="fa-solid fa-id-card-clip" style="color:#c084fc;"></i> Hồ sơ cá nhân
                    </a>
                    <?php if (!$is_tuyen): ?>
                    <a href="/tkb/admin/caidat.php" style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; color:#e9d5ff; font-size:13px; font-weight:600; text-decoration:none; transition:0.2s;" onmouseover="this.style.background='rgba(168,85,247,0.18)'; this.style.color='#ffffff'" onmouseout="this.style.background='transparent'; this.style.color='#e9d5ff'">
                        <i class="fa-solid fa-gears" style="color:#a79bb7;"></i> Cài đặt hệ thống
                    </a>
                    <a href="/tkb/admin/phanquyen.php" style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; color:#e9d5ff; font-size:13px; font-weight:600; text-decoration:none; transition:0.2s;" onmouseover="this.style.background='rgba(168,85,247,0.18)'; this.style.color='#ffffff'" onmouseout="this.style.background='transparent'; this.style.color='#e9d5ff'">
                        <i class="fa-solid fa-user-shield" style="color:#818cf8;"></i> Phân quyền bảo mật
                    </a>
                    <?php endif; ?>
                    <div style="border-top:1px solid rgba(168,85,247,0.12); margin:6px 0;"></div>
                    <a href="/tkb/api/logout.php" onclick="return confirm('Bạn có chắc muốn đăng xuất?');" style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; color:#fca5a5; font-size:13px; font-weight:700; text-decoration:none; transition:0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.15)'" onmouseout="this.style.background='transparent'">
                        <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </header>

<!-- MÀN HÌNH ĐÓNG MỞ KHI CHUYỂN CHẾ ĐỘ SÁNG / TỐI (HIỆN MẶT TRỜI / MẶT TRĂNG) -->
<div id="admThemeCurtain" class="adm-theme-curtain">
    <div class="adm-curtain-half adm-curtain-left" id="curtainLeft"></div>
    <div class="adm-curtain-half adm-curtain-right" id="curtainRight"></div>
    <div class="adm-curtain-stage" id="curtainStage">
        <!-- HIỆN MẶT TRỜI KHI BẬT CHẾ ĐỘ SÁNG (MÂY, BẦU TRỜI, CHIM ÉN, CHIM SẺ) -->
        <div class="adm-curtain-entity adm-curtain-sun" id="curtainSun">
            
            <!-- Mây trắng bồng bềnh khắp bầu trời -->
            <div class="adm-morning-cloud cloud-1"><i class="fa-solid fa-cloud"></i></div>
            <div class="adm-morning-cloud cloud-2"><i class="fa-solid fa-cloud"></i></div>
            <div class="adm-morning-cloud cloud-3"><i class="fa-solid fa-cloud"></i></div>
            <div class="adm-morning-cloud cloud-4"><i class="fa-solid fa-cloud"></i></div>
            <div class="adm-morning-cloud cloud-5"><i class="fa-solid fa-cloud"></i></div>

            <!-- Đàn chim én chao liệng trên cao (đuôi chẻ) -->
            <div class="adm-flock-en">
                <svg class="adm-chim-en en-1" viewBox="0 0 40 24" width="42" height="25">
                    <path d="M0,10 C8,2 16,5 20,11 C24,5 32,2 40,10 C32,8 26,13 22,22 C20,17 17,17 15,22 C12,13 6,8 0,10 Z" fill="#1e293b"/>
                    <path d="M17,12 C19,9 21,9 23,12 C21,15 19,15 17,12 Z" fill="#f8fafc" opacity="0.75"/>
                </svg>
                <svg class="adm-chim-en en-2" viewBox="0 0 40 24" width="30" height="18">
                    <path d="M0,10 C8,2 16,5 20,11 C24,5 32,2 40,10 C32,8 26,13 22,22 C20,17 17,17 15,22 C12,13 6,8 0,10 Z" fill="#334155" opacity="0.9"/>
                </svg>
                <svg class="adm-chim-en en-3" viewBox="0 0 40 24" width="22" height="13">
                    <path d="M0,10 C8,2 16,5 20,11 C24,5 32,2 40,10 C32,8 26,13 22,22 C20,17 17,17 15,22 C12,13 6,8 0,10 Z" fill="#475569" opacity="0.8"/>
                </svg>
            </div>

            <!-- Đàn chim sẻ vỗ cánh tung bay (thân tròn xinh xắn) -->
            <div class="adm-flock-se">
                <svg class="adm-chim-se se-1" viewBox="0 0 32 24" width="34" height="25">
                    <path d="M28,8 C31,7 32,8 30,10 C27,12 25,13 23,13 C22,16 19,18 15,19 C11,20 7,19 4,21 C2,22 1,21 2,19 C3,17 4,15 5,13 C6,9 10,6 14,6 C17,6 20,4 23,4 C25,4 27,6 28,8 Z" fill="#92400e"/>
                    <path d="M12,8 C16,4 20,6 18,11 C15,14 11,13 10,11 Z" fill="#78350f"/>
                    <path d="M10,14 C14,14 17,16 15,19 C12,20 8,18 10,14 Z" fill="#fef3c7" opacity="0.85"/>
                </svg>
                <svg class="adm-chim-se se-2" viewBox="0 0 32 24" width="26" height="19">
                    <path d="M28,8 C31,7 32,8 30,10 C27,12 25,13 23,13 C22,16 19,18 15,19 C11,20 7,19 4,21 C2,22 1,21 2,19 C3,17 4,15 5,13 C6,9 10,6 14,6 C17,6 20,4 23,4 C25,4 27,6 28,8 Z" fill="#92400e" opacity="0.9"/>
                    <path d="M12,8 C16,4 20,6 18,11 C15,14 11,13 10,11 Z" fill="#78350f" opacity="0.9"/>
                </svg>
                <svg class="adm-chim-se se-3" viewBox="0 0 32 24" width="20" height="15">
                    <path d="M28,8 C31,7 32,8 30,10 C27,12 25,13 23,13 C22,16 19,18 15,19 C11,20 7,19 4,21 C2,22 1,21 2,19 C3,17 4,15 5,13 C6,9 10,6 14,6 C17,6 20,4 23,4 C25,4 27,6 28,8 Z" fill="#b45309" opacity="0.85"/>
                </svg>
            </div>

            <!-- Vầng thái dương rực rỡ ở trung tâm -->
            <div class="adm-sun-wrap">
                <div class="adm-sun-ripple r-1"></div>
                <div class="adm-sun-ripple r-2"></div>
                <div class="adm-sun-glow"></div>
                <div class="adm-sun-rays">
                    <span style="--r: 0deg"></span>
                    <span style="--r: 30deg"></span>
                    <span style="--r: 60deg"></span>
                    <span style="--r: 90deg"></span>
                    <span style="--r: 120deg"></span>
                    <span style="--r: 150deg"></span>
                    <span style="--r: 180deg"></span>
                    <span style="--r: 210deg"></span>
                    <span style="--r: 240deg"></span>
                    <span style="--r: 270deg"></span>
                    <span style="--r: 300deg"></span>
                    <span style="--r: 330deg"></span>
                </div>
                <div class="adm-sun-core">
                    <i class="fa-solid fa-sun"></i>
                </div>
            </div>

            <!-- Tiêu đề & Phụ đề -->
            <div class="adm-curtain-title">CHẾ ĐỘ BAN NGÀY</div>
            <div class="adm-curtain-sub">Chào ngày mới • Bầu trời trong xanh tươi sáng</div>

            <!-- Thanh tiến trình nạp 3s -->
            <div class="adm-morning-progress-bar">
                <div class="adm-progress-fill"></div>
                <span class="adm-progress-text"><i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Đang kích hoạt giao diện sáng...</span>
            </div>
        </div>

        <!-- HIỆN MẶT TRĂNG KHI BẬT CHẾ ĐỘ TỐI -->
        <div class="adm-curtain-entity adm-curtain-moon" id="curtainMoon">
            <!-- MÂY ĐÊM HUYỀN ẢO -->
            <div class="adm-night-cloud ncloud-1"><i class="fa-solid fa-cloud"></i></div>
            <div class="adm-night-cloud ncloud-2"><i class="fa-solid fa-cloud"></i></div>
            <div class="adm-night-cloud ncloud-3"><i class="fa-solid fa-cloud"></i></div>
            <div class="adm-night-cloud ncloud-4"><i class="fa-solid fa-cloud"></i></div>

            <!-- SAO BĂNG LƯỚT QUA BẦU TRỜI -->
            <div class="adm-shooting-star star-shoot-1"></div>
            <div class="adm-shooting-star star-shoot-2"></div>

            <!-- NGÀN SAO VŨ TRỤ LẤP LÁNH -->
            <i class="fa-solid fa-star adm-night-star" style="top: 8%; left: 24%; font-size: 15px; color: #fbbf24; animation-delay: 0.2s; filter: drop-shadow(0 0 8px #fbbf24);"></i>
            <i class="fa-solid fa-sparkles adm-night-star" style="top: 22%; left: 12%; font-size: 18px; color: #c084fc; animation-delay: 0.7s; filter: drop-shadow(0 0 8px #c084fc);"></i>
            <i class="fa-solid fa-star adm-night-star" style="top: 35%; left: 20%; font-size: 13px; color: #38bdf8; animation-delay: 1.2s; filter: drop-shadow(0 0 8px #38bdf8);"></i>
            <i class="fa-solid fa-sparkles adm-night-star" style="bottom: 28%; left: 16%; font-size: 16px; color: #ffffff; animation-delay: 0.4s; filter: drop-shadow(0 0 10px #ffffff);"></i>
            <i class="fa-solid fa-star adm-night-star" style="bottom: 12%; left: 26%; font-size: 14px; color: #fef08a; animation-delay: 0.9s; filter: drop-shadow(0 0 8px #fef08a);"></i>
            <i class="fa-solid fa-star adm-night-star" style="top: 7%; right: 25%; font-size: 17px; color: #c084fc; animation-delay: 0.3s; filter: drop-shadow(0 0 8px #c084fc);"></i>
            <i class="fa-solid fa-sparkles adm-night-star" style="top: 20%; right: 13%; font-size: 14px; color: #fbbf24; animation-delay: 0.8s; filter: drop-shadow(0 0 8px #fbbf24);"></i>
            <i class="fa-solid fa-star adm-night-star" style="top: 36%; right: 22%; font-size: 15px; color: #38bdf8; animation-delay: 1.4s; filter: drop-shadow(0 0 8px #38bdf8);"></i>
            <i class="fa-solid fa-sparkles adm-night-star" style="bottom: 26%; right: 17%; font-size: 15px; color: #e9d5ff; animation-delay: 0.5s; filter: drop-shadow(0 0 8px #e9d5ff);"></i>
            <i class="fa-solid fa-star adm-night-star" style="bottom: 10%; right: 27%; font-size: 18px; color: #fbbf24; animation-delay: 1.1s; filter: drop-shadow(0 0 8px #fbbf24);"></i>
            <i class="fa-solid fa-star adm-night-star" style="top: 30%; left: 38%; font-size: 12px; color: #ffffff; animation-delay: 1.6s; filter: drop-shadow(0 0 8px #ffffff);"></i>
            <i class="fa-solid fa-sparkles adm-night-star" style="top: 28%; right: 36%; font-size: 13px; color: #fef08a; animation-delay: 0.6s; filter: drop-shadow(0 0 8px #fef08a);"></i>

            <!-- ĐOM ĐÓM PHÁT SÁNG -->
            <div class="adm-firefly ff-1"></div>
            <div class="adm-firefly ff-2"></div>
            <div class="adm-firefly ff-3"></div>
            <div class="adm-firefly ff-4"></div>
            <div class="adm-firefly ff-5"></div>

            <!-- VÒNG TRĂNG HUYỀN ẢO & RIPPLES -->
            <div class="adm-moon-wrap">
                <div class="adm-moon-ripple mr-1"></div>
                <div class="adm-moon-ripple mr-2"></div>
                <div class="adm-moon-glow"></div>
                <div class="adm-moon-stars">
                    <i class="fa-solid fa-star star-1"></i>
                    <i class="fa-solid fa-star star-2"></i>
                    <i class="fa-solid fa-star star-3"></i>
                    <i class="fa-solid fa-sparkles star-4"></i>
                </div>
                <div class="adm-moon-core">
                    <i class="fa-solid fa-moon"></i>
                </div>
            </div>

            <!-- TIÊU ĐỀ & CHÚ THÍCH -->
            <div class="adm-curtain-title">CHẾ ĐỘ BAN ĐÊM</div>
            <div class="adm-curtain-sub">Lofi vũ trụ • Bầu trời ngàn sao huyền ảo &amp; thư giãn</div>

            <!-- Dynamic Loading Progress Bar for Dark Mode -->
            <div class="adm-night-progress-bar">
                <div class="adm-night-progress-fill"></div>
                <span class="adm-night-progress-text" id="nightProgressText"><i class="fa-solid fa-moon" style="color:#c084fc;"></i> Đang kích hoạt giao diện tối...</span>
            </div>
        </div>
    </div>
</div>

<script>
document.body.classList.add('admin-portal');
<?php if ($is_tuyen): ?>
document.body.classList.add('tuyen-theme');
<?php endif; ?>

// Âm thanh synth chime nhẹ nhàng (Web Audio API không cần file ngoài)
function playThemeChime(isSun) {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const now = ctx.currentTime;
        if (isSun) {
            // Hợp âm bình minh ấm áp (C5 - E5 - G5 - C6)
            [523.25, 659.25, 783.99, 1046.5].forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, now + idx * 0.08);
                gain.gain.setValueAtTime(0, now + idx * 0.08);
                gain.gain.linearRampToValueAtTime(0.08, now + idx * 0.08 + 0.04);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + idx * 0.08 + 0.6);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now + idx * 0.08);
                osc.stop(now + idx * 0.08 + 0.65);
            });
        } else {
            // Hợp âm đêm trăng lofi du dương (A4 - C5 - E5)
            [440, 523.25, 659.25].forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, now + idx * 0.1);
                gain.gain.setValueAtTime(0, now + idx * 0.1);
                gain.gain.linearRampToValueAtTime(0.06, now + idx * 0.1 + 0.05);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + idx * 0.1 + 0.7);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now + idx * 0.1);
                osc.stop(now + idx * 0.1 + 0.75);
            });
        }
    } catch(e) {}
}

let isThemeTransitioning = false;

// === HỆ THỐNG ĐIỀU KHIỂN CHẾ ĐỘ SÁNG / TỐI (LIGHT / DARK MODE) ===
(function() {
    const savedTheme = localStorage.getItem('adm_theme');
    // MẶC ĐỊNH LÀ DARK MODE (LOFI DREAMY TÍM ĐEN) CHO ADMIN
    const isLight = (savedTheme === 'light');
    if (isLight) {
        document.body.classList.add('adm-light-mode');
    } else {
        document.body.classList.remove('adm-light-mode');
    }
})();

function updateAdminThemeUI(isLight) {
    const btn = document.getElementById('admThemeToggleBtn');
    const icon = document.getElementById('admThemeIcon');
    if (isLight) {
        document.body.classList.add('adm-light-mode');
        if (icon) {
            icon.className = 'fa-solid fa-sun';
            icon.style.color = '#f59e0b';
        }
        if (btn) {
            btn.setAttribute('title', 'Đang ở Chế độ Sáng (Mặt Trời) — Bấm để chuyển sang Chế độ Tối');
            btn.style.background = '#fffbeb';
            btn.style.borderColor = '#fde68a';
            btn.style.color = '#d97706';
            btn.style.boxShadow = '0 2px 10px rgba(245, 158, 11, 0.2)';
        }
    } else {
        document.body.classList.remove('adm-light-mode');
        if (icon) {
            icon.className = 'fa-solid fa-moon';
            icon.style.color = '#c084fc';
        }
        if (btn) {
            btn.setAttribute('title', 'Đang ở Chế độ Tối (Mặt Trăng) — Bấm để chuyển sang Chế độ Sáng');
            btn.style.background = 'rgba(168, 85, 247, 0.16)';
            btn.style.borderColor = 'rgba(168, 85, 247, 0.35)';
            btn.style.color = '#c084fc';
            btn.style.boxShadow = 'none';
        }
    }
}

// BẤM NÚT NÀY SẼ ĐÓNG MÀN HÌNH, HIỆN HÌNH MẶT TRỜI / MẶT TRĂNG VÀ MỞ MÀN HÌNH RA
function toggleAdminTheme(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    if (isThemeTransitioning) return;
    isThemeTransitioning = true;

    const isCurrentlyLight = document.body.classList.contains('adm-light-mode');
    const newIsLight = !isCurrentlyLight;

    const curtain = document.getElementById('admThemeCurtain');
    const curtainSun = document.getElementById('curtainSun');
    const curtainMoon = document.getElementById('curtainMoon');

    if (!curtain) {
        localStorage.setItem('adm_theme', newIsLight ? 'light' : 'dark');
        document.cookie = 'adm_theme=' + (newIsLight ? 'light' : 'dark') + ';path=/;max-age=31536000';
        updateAdminThemeUI(newIsLight);
        isThemeTransitioning = false;
        return;
    }

    // Cập nhật hiển thị biểu tượng tương ứng
    if (newIsLight) {
        curtain.classList.add('curtain-mode-light');
        if (curtainSun) curtainSun.classList.add('show');
        if (curtainMoon) curtainMoon.classList.remove('show');
    } else {
        curtain.classList.remove('curtain-mode-light');
        if (curtainMoon) curtainMoon.classList.add('show');
        if (curtainSun) curtainSun.classList.remove('show');
    }

    // BƯỚC 1: ĐÓNG MÀN HÌNH (2 cánh cửa trượt khép kín lại ở giữa)
    curtain.classList.remove('opening', 'closed');
    curtain.classList.add('active', 'closing');

    // Reset lại animation của progress bar để chạy mượt mà từ 0% trong 3 giây
    const sunBar = document.querySelector('.adm-progress-fill');
    const moonBar = document.querySelector('.adm-night-progress-fill');
    if (sunBar) {
        sunBar.style.animation = 'none';
        sunBar.offsetHeight;
        sunBar.style.animation = 'progressFill 2.8s cubic-bezier(0.1, 0.85, 0.35, 1) forwards 0.1s';
    }
    if (moonBar) {
        moonBar.style.animation = 'none';
        moonBar.offsetHeight;
        moonBar.style.animation = 'progressFill 2.8s cubic-bezier(0.1, 0.85, 0.35, 1) forwards 0.1s';
    }

    setTimeout(function() {
        // Màn hình đã đóng kín hoàn toàn
        curtain.classList.remove('closing');
        curtain.classList.add('closed');

        // Phát âm thanh chime du dương
        playThemeChime(newIsLight);

        // BƯỚC 2: ĐỔI GIAO DIỆN (LÚC NÀY ĐANG ĐÓNG KÍN MÀN HÌNH)
        localStorage.setItem('adm_theme', newIsLight ? 'light' : 'dark');
        document.cookie = 'adm_theme=' + (newIsLight ? 'light' : 'dark') + ';path=/;max-age=31536000';
        updateAdminThemeUI(newIsLight);

        // Đếm ngược 3 giây trực tiếp trên thanh tiến trình
        let remaining = 3;
        const sunText = document.querySelector('.adm-progress-text');
        const moonText = document.querySelector('.adm-night-progress-text');
        if (sunText) sunText.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="color:#d97706;"></i> Đang kích hoạt giao diện sáng... (3s)';
        if (moonText) moonText.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="color:#c084fc;"></i> Đang kích hoạt giao diện tối... (3s)';

        const countInterval = setInterval(function() {
            remaining--;
            if (remaining > 0) {
                if (sunText) sunText.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="color:#d97706;"></i> Đang kích hoạt giao diện sáng... (' + remaining + 's)';
                if (moonText) moonText.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="color:#c084fc;"></i> Đang kích hoạt giao diện tối... (' + remaining + 's)';
            } else {
                clearInterval(countInterval);
                if (sunText) sunText.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Đã kích hoạt Chế độ Sáng thành công!';
                if (moonText) moonText.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Đã kích hoạt Chế độ Tối thành công!';
            }
        }, 1000);

        // BƯỚC 3: GIỮ HÌNH MẶT TRỜI / MẶT TRĂNG VÀ CHỜ ĐÚNG 3 GIÂY (3000ms)
        setTimeout(function() {
            clearInterval(countInterval);
            // BƯỚC 4: MỞ MÀN HÌNH RA (2 cánh cửa trượt mở toang sang 2 bên)
            curtain.classList.remove('closed');
            curtain.classList.add('opening');

            // BƯỚC 5: HOÀN TẤT VÀ GIẢI PHÓNG TRẠNG THÁI
            setTimeout(function() {
                curtain.classList.remove('active', 'opening', 'curtain-mode-light');
                if (curtainSun) curtainSun.classList.remove('show');
                if (curtainMoon) curtainMoon.classList.remove('show');
                isThemeTransitioning = false;
            }, 460);
        }, 3000);
    }, 440);
}

document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('adm_theme');
    // MẶC ĐỊNH LÀ DARK MODE (LOFI DREAMY TÍM ĐEN)
    const isLight = (savedTheme === 'light');
    document.cookie = 'adm_theme=' + (isLight ? 'light' : 'dark') + ';path=/;max-age=31536000';
    updateAdminThemeUI(isLight);

    // === CỐ ĐỊNH VÀ GIỮ VỊ TRÍ CUỘN THANH SIDEBAR (MƯỢT MÀ, KHÔNG GIẬT) ===
    const sidebar = document.getElementById('admSidebar');
    if (sidebar) {
        const savedScroll = sessionStorage.getItem('adm_sidebar_scroll');
        const activeItem = sidebar.querySelector('.adm-nav-item.active');

        if (savedScroll !== null) {
            // Giữ nguyên chính xác vị trí cuộn người dùng đang đứng
            sidebar.scrollTop = parseInt(savedScroll, 10);

            // Chỉ căn chỉnh nhẹ nếu mục active bị trôi hẳn ra ngoài màn hình
            if (activeItem) {
                const itemTop = activeItem.offsetTop;
                const itemBottom = itemTop + activeItem.clientHeight;
                const viewTop = sidebar.scrollTop;
                const viewBottom = viewTop + sidebar.clientHeight;
                if (itemTop < viewTop || itemBottom > viewBottom) {
                    sidebar.scrollTop = Math.max(0, itemTop - (sidebar.clientHeight / 2) + (activeItem.clientHeight / 2));
                    sessionStorage.setItem('adm_sidebar_scroll', sidebar.scrollTop);
                }
            }
        } else if (activeItem) {
            sidebar.scrollTop = Math.max(0, activeItem.offsetTop - (sidebar.clientHeight / 2) + (activeItem.clientHeight / 2));
            sessionStorage.setItem('adm_sidebar_scroll', sidebar.scrollTop);
        }

        // Lưu vị trí cuộn khi người dùng cuộn sidebar
        let scrollTicking = false;
        sidebar.addEventListener('scroll', function() {
            if (!scrollTicking) {
                window.requestAnimationFrame(function() {
                    sessionStorage.setItem('adm_sidebar_scroll', sidebar.scrollTop);
                    scrollTicking = false;
                });
                scrollTicking = true;
            }
        }, { passive: true });

        // Khi người dùng bấm bất kỳ link nào: lưu vị trí tức thời
        sidebar.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                sessionStorage.setItem('adm_sidebar_scroll', sidebar.scrollTop);
            });
        });
    }

});

function toggleProfileDropdown(e) {
    e.stopPropagation();
    const d = document.getElementById('admProfileDropdown');
    d.style.display = d.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', function(e) {
    const d = document.getElementById('admProfileDropdown');
    if (d && d.style.display === 'block') {
        d.style.display = 'none';
    }
});

function toggleAdminNav() {
    document.getElementById('admSidebar').classList.toggle('open');
    document.getElementById('admOverlay').classList.toggle('active');
}

function toggleFullScreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
    } else if (document.exitFullscreen) {
        document.exitFullscreen();
    }
}

function toggleCosmicStation(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    if (typeof window.toggleVtModal === 'function') {
        window.toggleVtModal();
    } else {
        const m = document.getElementById('vtModal');
        if (m) {
            const isOpen = m.classList.toggle('open');
            const topBtn = document.getElementById('admCosmicStationBtn');
            if (topBtn) topBtn.classList.toggle('active', isOpen);
            if (isOpen) {
                const inp = document.getElementById('vtmInput');
                if (inp) setTimeout(() => inp.focus(), 150);
            }
        } else {
            window.location.href = '/tkb/admin/ai_studio.php';
        }
    }
}

<?php if (!$is_tuyen): ?>
// Load YouTube IFrame Player API dynamically (Standard Google API pattern)
if (!window.YT) {
    const ytTag = document.createElement('script');
    ytTag.src = "https://www.youtube.com/iframe_api";
    const firstScript = document.getElementsByTagName('script')[0];
    if (firstScript && firstScript.parentNode) {
        firstScript.parentNode.insertBefore(ytTag, firstScript);
    } else {
        document.head.appendChild(ytTag);
    }
}

// ==========================================
// ==========================================
// ADVANCED TOPBAR MUSIC HUB & ENGINE (YOUTUBE & MP3)
// ==========================================
let customLofiTracks = [];
try {
    customLofiTracks = JSON.parse(localStorage.getItem('adm_custom_lofi_tracks') || '[]');
} catch(e) { customLofiTracks = []; }

let currentTrackIndex = parseInt(localStorage.getItem('adm_last_lofi_track_idx') || '0', 10);
if (isNaN(currentTrackIndex) || currentTrackIndex < 0 || (customLofiTracks.length > 0 && currentTrackIndex >= customLofiTracks.length)) {
    currentTrackIndex = 0;
}

let isLofiPlaying = false;
let lofiAudioElement = new Audio();
let lofiAudioCtx = null;
let lofiSynthTimer = null;
let lofiVolume = parseFloat(localStorage.getItem('adm_lofi_volume') || '0.7');

let ytPlayer = null;
let isYtReady = false;
let isPipExpanded = false;

lofiAudioElement.volume = lofiVolume;

// YouTube Iframe API Initialization
window.onYouTubeIframeAPIReady = function() {
    initYtPlayer();
};

function initYtPlayer() {
    if (ytPlayer || !window.YT || !window.YT.Player) return;
    try {
        ytPlayer = new YT.Player('lofiYtPlayerTarget', {
            height: '100%',
            width: '100%',
            videoId: 'jfKfPfyJRdk',
            playerVars: {
                autoplay: 0,
                controls: 1,
                modestbranding: 1,
                rel: 0,
                playsinline: 1,
                fs: 0
            },
            events: {
                onReady: function(event) {
                    isYtReady = true;
                    try { event.target.setVolume(lofiVolume * 100); } catch(e) {}
                },
                onStateChange: function(event) {
                    if (event.data === YT.PlayerState.PLAYING) {
                        isLofiPlaying = true;
                        updateUI();
                    } else if (event.data === YT.PlayerState.PAUSED) {
                        isLofiPlaying = false;
                        updateUI();
                    } else if (event.data === YT.PlayerState.ENDED) {
                        nextLofiTrack();
                    }
                }
            }
        });
    } catch(err) {
        console.warn("YouTube API init error:", err);
    }
}

// Ensure YT init if API was already cached
if (window.YT && window.YT.Player) {
    initYtPlayer();
}

function toggleYtPip(forceState) {
    isPipExpanded = (forceState !== undefined) ? forceState : !isPipExpanded;
    const holder = document.getElementById('lofiYtPlayerHolder');
    const btnText = document.getElementById('ytPipBtnText');
    if (!holder) return;

    if (isPipExpanded) {
        holder.style.width = '290px';
        holder.style.height = '185px';
        holder.style.opacity = '1';
        holder.style.pointerEvents = 'auto';
        if (btnText) btnText.textContent = 'Thu Nhỏ Video';
    } else {
        holder.style.width = '1px';
        holder.style.height = '1px';
        holder.style.opacity = '0.001';
        holder.style.pointerEvents = 'none';
        if (btnText) btnText.textContent = 'Xem Video';
    }
}

// HTML5 Audio element events
lofiAudioElement.addEventListener('timeupdate', function() {
    const track = getCurrentTrack();
    if (!isLofiPlaying || track.type === 'empty' || track.isSynth || track.isStream || track.type === 'youtube' || track.ytId) return;
    const cur = lofiAudioElement.currentTime || 0;
    const dur = lofiAudioElement.duration || 0;
    const curTimeEl = document.getElementById('modalCurrentTime');
    const totTimeEl = document.getElementById('modalTotalDuration');
    const seekSlider = document.getElementById('modalSeekSlider');
    const progressFill = document.getElementById('topbarProgressFill');

    if (curTimeEl) curTimeEl.textContent = formatTime(cur);
    if (totTimeEl && dur > 0) totTimeEl.textContent = formatTime(dur);
    if (seekSlider && dur > 0) seekSlider.value = (cur / dur) * 100;
    if (progressFill && dur > 0) progressFill.style.width = ((cur / dur) * 100) + '%';
});

lofiAudioElement.addEventListener('ended', function() {
    nextLofiTrack();
});

lofiAudioElement.addEventListener('error', function() {
    console.warn("Audio stream error, falling back to synth engine...");
    if (isLofiPlaying && getCurrentTrack().type !== 'empty') {
        startSynthEngine();
    }
});

// Periodic timer for YouTube current time / duration updates
setInterval(function() {
    if (!isLofiPlaying) return;
    const track = getCurrentTrack();
    if (track.type === 'youtube' || track.ytId) {
        if (ytPlayer && isYtReady && typeof ytPlayer.getCurrentTime === 'function') {
            try {
                const cur = ytPlayer.getCurrentTime() || 0;
                const dur = ytPlayer.getDuration() || 0;
                const curTimeEl = document.getElementById('modalCurrentTime');
                const totTimeEl = document.getElementById('modalTotalDuration');
                const seekSlider = document.getElementById('modalSeekSlider');
                const progressFill = document.getElementById('topbarProgressFill');

                if (curTimeEl) curTimeEl.textContent = formatTime(cur);
                if (totTimeEl && dur > 0) totTimeEl.textContent = formatTime(dur);
                if (seekSlider && dur > 0) seekSlider.value = (cur / dur) * 100;
                if (progressFill && dur > 0) progressFill.style.width = ((cur / dur) * 100) + '%';
            } catch(e) {}
        }
    }
}, 500);

function getCurrentTrack() {
    if (customLofiTracks && customLofiTracks.length > 0) {
        if (currentTrackIndex >= customLofiTracks.length) currentTrackIndex = 0;
        if (currentTrackIndex < 0) currentTrackIndex = customLofiTracks.length - 1;
        return customLofiTracks[currentTrackIndex];
    }
    return {
        title: "Chưa chọn bài hát",
        artist: "Tìm trên YouTube hoặc thêm MP3 để phát",
        type: "empty",
        vibe: "MUSIC",
        url: "",
        thumbnail: "",
        isSynth: false
    };
}

function updateUI() {
    const track = getCurrentTrack();
    const topbarPlayer = document.getElementById('topbarMusicPlayer');
    const topbarSong = document.getElementById('topbarMusicSong');
    const topbarPlayIcon = document.getElementById('topbarPlayIcon');
    const topbarSourceBadge = document.getElementById('topbarSourceBadge');
    const topbarDiskThumb = document.getElementById('topbarDiskThumb');
    const topbarDiskIcon = document.getElementById('topbarDiskIcon');
    const topbarPipToggleBtn = document.getElementById('topbarPipToggleBtn');

    const modalTitle = document.getElementById('modalTrackTitle');
    const modalArtist = document.getElementById('modalTrackArtist');
    const modalPlayIcon = document.getElementById('modalPlayIcon');
    const modalBadge = document.getElementById('modalTrackBadge');
    const modalPipBtn = document.getElementById('modalYtPipBtn');
    const ytPipTitle = document.getElementById('ytPipTitle');
    const vinyl = document.getElementById('modalVinylDisk');
    const modalDiskThumb = document.getElementById('modalDiskThumb');
    const modalCenterIcon = document.getElementById('modalCenterIcon');
    const volSlider = document.getElementById('modalVolumeSlider');

    const isYt = (track.type === 'youtube' || !!track.ytId);

    // Title & Marquee
    if (topbarSong) {
        topbarSong.textContent = track.title;
        if (track.title.length > 16) {
            topbarSong.classList.add('is-marquee');
        } else {
            topbarSong.classList.remove('is-marquee');
        }
    }
    if (modalTitle) modalTitle.textContent = track.title;
    if (modalArtist) modalArtist.textContent = track.artist || 'Chưa chọn bài hát';
    if (ytPipTitle) ytPipTitle.textContent = track.title;
    if (volSlider) volSlider.value = lofiVolume;

    // Badges & Thumbnails
    const thumbUrl = track.thumbnail || (isYt ? ('https://img.youtube.com/vi/' + (track.ytId || extractYouTubeId(track.url)) + '/hqdefault.jpg') : '');

    if (topbarDiskThumb && topbarDiskIcon) {
        if (thumbUrl) {
            topbarDiskThumb.src = thumbUrl;
            topbarDiskThumb.style.display = 'block';
            topbarDiskIcon.style.display = 'none';
        } else {
            topbarDiskThumb.style.display = 'none';
            topbarDiskIcon.style.display = 'block';
        }
    }

    if (modalDiskThumb && modalCenterIcon) {
        if (thumbUrl) {
            modalDiskThumb.src = thumbUrl;
            modalDiskThumb.style.display = 'block';
            modalCenterIcon.style.display = 'none';
        } else {
            modalDiskThumb.style.display = 'none';
            modalCenterIcon.style.display = 'block';
        }
    }

    if (topbarSourceBadge) {
        if (isYt) {
            topbarSourceBadge.innerHTML = '<i class="fa-brands fa-youtube"></i> YT';
            topbarSourceBadge.className = 'adm-music-source-badge yt';
        } else if (track.type === 'empty') {
            topbarSourceBadge.textContent = 'MUSIC';
            topbarSourceBadge.className = 'adm-music-source-badge';
        } else {
            topbarSourceBadge.textContent = track.vibe || 'MP3';
            topbarSourceBadge.className = 'adm-music-source-badge';
        }
    }

    if (modalBadge) {
        modalBadge.innerHTML = isYt ? '<i class="fa-brands fa-youtube" style="color:#f43f5e;"></i> YouTube Stream' : (track.type === 'empty' ? 'Chưa chọn bài hát' : 'Đang phát');
    }
    if (modalPipBtn) {
        modalPipBtn.style.display = isYt ? 'inline-flex' : 'none';
    }
    if (topbarPipToggleBtn) {
        topbarPipToggleBtn.style.display = isYt ? 'inline-flex' : 'none';
    }

    if (isLofiPlaying && track.type !== 'empty') {
        if (topbarPlayer) topbarPlayer.classList.add('playing');
        if (topbarPlayIcon) topbarPlayIcon.className = 'fa-solid fa-pause';
        if (modalPlayIcon) modalPlayIcon.className = 'fa-solid fa-pause';
        if (vinyl) vinyl.style.animationPlayState = 'running';
    } else {
        if (topbarPlayer) topbarPlayer.classList.remove('playing');
        if (topbarPlayIcon) topbarPlayIcon.className = 'fa-solid fa-play';
        if (modalPlayIcon) modalPlayIcon.className = 'fa-solid fa-play';
        if (vinyl) vinyl.style.animationPlayState = 'paused';
    }

    renderPlaylistContainers();
}

function toggleLofiMusic(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    if (!customLofiTracks || customLofiTracks.length === 0) {
        openLofiPlaylistModal();
        switchMusicModalTab('yt');
        return;
    }
    if (isLofiPlaying) {
        pauseLofiMusic();
    } else {
        playLofiMusic();
    }
}

function playLofiMusic() {
    if (!customLofiTracks || customLofiTracks.length === 0) {
        openLofiPlaylistModal();
        switchMusicModalTab('yt');
        return;
    }
    const track = getCurrentTrack();
    if (track.type === 'empty') return;
    isLofiPlaying = true;

    if (track.type === 'youtube' || track.ytId) {
        lofiAudioElement.pause();
        stopSynthEngine();

        const ytId = track.ytId || extractYouTubeId(track.url);
        if (ytPlayer && isYtReady && typeof ytPlayer.loadVideoById === 'function') {
            try {
                ytPlayer.loadVideoById(ytId);
                ytPlayer.setVolume(lofiVolume * 100);
                ytPlayer.playVideo();
            } catch(e) {}
        } else {
            setTimeout(function() {
                if (ytPlayer && typeof ytPlayer.loadVideoById === 'function') {
                    try {
                        ytPlayer.loadVideoById(ytId);
                        ytPlayer.playVideo();
                    } catch(e) {}
                }
            }, 800);
        }
    } else if (track.isSynth) {
        if (ytPlayer && isYtReady && typeof ytPlayer.pauseVideo === 'function') {
            try { ytPlayer.pauseVideo(); } catch(e) {}
        }
        lofiAudioElement.pause();
        startSynthEngine();
    } else {
        if (ytPlayer && isYtReady && typeof ytPlayer.pauseVideo === 'function') {
            try { ytPlayer.pauseVideo(); } catch(e) {}
        }
        stopSynthEngine();
        if (lofiAudioElement.src !== track.url) {
            lofiAudioElement.src = track.url;
            lofiAudioElement.load();
        }
        lofiAudioElement.play().catch(function(err) {
            console.warn("Autoplay blocked/failed, starting synth fallback:", err);
            startSynthEngine();
        });
    }
    updateUI();
}

function pauseLofiMusic() {
    isLofiPlaying = false;
    const track = getCurrentTrack();
    if ((track.type === 'youtube' || track.ytId) && ytPlayer && typeof ytPlayer.pauseVideo === 'function') {
        try { ytPlayer.pauseVideo(); } catch(e) {}
    }
    lofiAudioElement.pause();
    stopSynthEngine();
    updateUI();
}

function nextLofiTrack(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    if (!customLofiTracks || customLofiTracks.length === 0) return;
    currentTrackIndex = (currentTrackIndex + 1) % customLofiTracks.length;
    playCurrentTrack();
}

function prevLofiTrack(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    if (!customLofiTracks || customLofiTracks.length === 0) return;
    currentTrackIndex = (currentTrackIndex - 1 + customLofiTracks.length) % customLofiTracks.length;
    playCurrentTrack();
}

function playTrackByIndex(index) {
    currentTrackIndex = index;
    playCurrentTrack();
}

function playCurrentTrack() {
    try {
        localStorage.setItem('adm_last_lofi_track_idx', currentTrackIndex);
    } catch(e) {}

    const track = getCurrentTrack();
    stopSynthEngine();
    lofiAudioElement.pause();
    if (ytPlayer && isYtReady && typeof ytPlayer.pauseVideo === 'function') {
        try { ytPlayer.pauseVideo(); } catch(e) {}
    }

    if (track.type === 'empty') {
        isLofiPlaying = false;
        updateUI();
        return;
    }

    if (!track.isSynth && track.type !== 'youtube' && !track.ytId) {
        lofiAudioElement.src = track.url;
        lofiAudioElement.load();
    }
    if (isLofiPlaying) {
        playLofiMusic();
    } else {
        updateUI();
    }
}

function seekLofiTrack(pct) {
    const track = getCurrentTrack();
    if (track.type === 'youtube' || track.ytId) {
        if (ytPlayer && isYtReady && typeof ytPlayer.getDuration === 'function') {
            try {
                const dur = ytPlayer.getDuration() || 0;
                if (dur > 0) {
                    ytPlayer.seekTo((pct / 100) * dur, true);
                }
            } catch(e) {}
        }
        return;
    }
    if (track.type === 'empty' || track.isSynth || track.isStream) return;
    const dur = lofiAudioElement.duration || 0;
    if (dur > 0) {
        lofiAudioElement.currentTime = (pct / 100) * dur;
    }
}

function setLofiVolume(vol) {
    lofiVolume = parseFloat(vol);
    lofiAudioElement.volume = lofiVolume;
    if (ytPlayer && isYtReady && typeof ytPlayer.setVolume === 'function') {
        try { ytPlayer.setVolume(lofiVolume * 100); } catch(e) {}
    }
    localStorage.setItem('adm_lofi_volume', lofiVolume);
    const muteIcon = document.getElementById('modalMuteIcon');
    if (muteIcon) {
        muteIcon.className = lofiVolume === 0 ? 'fa-solid fa-volume-xmark' : 'fa-solid fa-volume-high';
    }
}

function toggleMuteLofi() {
    if (lofiVolume > 0) {
        lofiAudioElement.volume = 0;
        setLofiVolume(0);
    } else {
        lofiAudioElement.volume = 0.7;
        setLofiVolume(0.7);
    }
}

function formatTime(secs) {
    const m = Math.floor(secs / 60);
    const s = Math.floor(secs % 60);
    return m + ':' + (s < 10 ? '0' : '') + s;
}

// Procedural Ambient Synthesizer Engine
function startSynthEngine() {
    stopSynthEngine();
    try {
        if (!lofiAudioCtx) {
            lofiAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (lofiAudioCtx.state === 'suspended') {
            lofiAudioCtx.resume();
        }

        const chords = [
            [220, 277.18, 329.63, 415.30],
            [174.61, 220, 261.63, 329.63],
            [196, 246.94, 293.66, 369.99],
            [164.81, 207.65, 246.94, 311.13]
        ];

        const masterGain = lofiAudioCtx.createGain();
        masterGain.gain.setValueAtTime(0.08 * lofiVolume, lofiAudioCtx.currentTime);
        masterGain.connect(lofiAudioCtx.destination);

        let chordStep = 0;
        function playStep() {
            if (!isLofiPlaying || !getCurrentTrack().isSynth) return;
            const freqs = chords[chordStep % chords.length];
            chordStep++;

            freqs.forEach(freq => {
                const osc = lofiAudioCtx.createOscillator();
                const noteGain = lofiAudioCtx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, lofiAudioCtx.currentTime);

                const filter = lofiAudioCtx.createBiquadFilter();
                filter.type = 'lowpass';
                filter.frequency.setValueAtTime(480, lofiAudioCtx.currentTime);

                noteGain.gain.setValueAtTime(0, lofiAudioCtx.currentTime);
                noteGain.gain.linearRampToValueAtTime(0.12, lofiAudioCtx.currentTime + 1.2);
                noteGain.gain.exponentialRampToValueAtTime(0.001, lofiAudioCtx.currentTime + 4.5);

                osc.connect(filter);
                filter.connect(noteGain);
                noteGain.connect(masterGain);

                osc.start();
                osc.stop(lofiAudioCtx.currentTime + 4.8);
            });

            lofiSynthTimer = setTimeout(playStep, 3800);
        }

        playStep();
    } catch(e) { console.error(e); }
}

function stopSynthEngine() {
    if (lofiSynthTimer) {
        clearTimeout(lofiSynthTimer);
        lofiSynthTimer = null;
    }
}

// Modal handling
function toggleModal(modalId) {
    const m = document.getElementById(modalId);
    if (!m) return;
    if (m.classList.contains('active') || m.classList.contains('open') || m.style.display === 'flex') {
        m.classList.remove('active', 'open');
        m.style.display = 'none';
    } else {
        m.classList.add('active');
        m.style.display = 'flex';
    }
}

function openLofiPlaylistModal(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    const m = document.getElementById('admLofiPlaylistModal');
    if (m) {
        m.classList.add('active');
        m.style.display = 'flex';
    }
    updateUI();
}

function closeLofiPlaylistModal() {
    const m = document.getElementById('admLofiPlaylistModal');
    if (m) {
        m.classList.remove('active', 'open');
        m.style.display = 'none';
    }
}

document.addEventListener('click', function(e) {
    const m = document.getElementById('admLofiPlaylistModal');
    if (m && e.target === m) {
        closeLofiPlaylistModal();
    }
});

function switchMusicModalTab(tab) {
    const tabYt  = document.getElementById('musicTabYt');
    const tabAdd = document.getElementById('musicTabAdd');
    const tabMy  = document.getElementById('musicTabMy');

    const btnYt  = document.getElementById('tabBtnYt');
    const btnAdd = document.getElementById('tabBtnAddCustom');
    const btnMy  = document.getElementById('tabBtnMyTracks');

    [tabYt, tabAdd, tabMy].forEach(t => { if(t) t.style.display = 'none'; });
    [btnYt, btnAdd, btnMy].forEach(b => { 
        if(b) {
            b.style.background = 'transparent';
            b.style.color = '#a79bb7';
            b.style.borderColor = 'transparent';
        }
    });

    if (tab === 'yt' && tabYt && btnYt) {
        tabYt.style.display = 'block';
        btnYt.style.background = 'rgba(244,63,94,0.25)';
        btnYt.style.color = '#ffffff';
        btnYt.style.borderColor = 'rgba(244,63,94,0.4)';
    } else if (tab === 'add' && tabAdd && btnAdd) {
        tabAdd.style.display = 'block';
        btnAdd.style.background = 'rgba(168,85,247,0.25)';
        btnAdd.style.color = '#ffffff';
        btnAdd.style.borderColor = 'rgba(168,85,247,0.4)';
    } else if (tab === 'my' && tabMy && btnMy) {
        tabMy.style.display = 'block';
        btnMy.style.background = 'rgba(168,85,247,0.25)';
        btnMy.style.color = '#ffffff';
        btnMy.style.borderColor = 'rgba(168,85,247,0.4)';
    }
}

// YouTube Link Extraction & Live Info Fetcher
function extractYouTubeId(url) {
    if (!url) return '';
    url = url.trim();
    if (/^[a-zA-Z0-9_-]{11}$/.test(url)) return url;
    const regExp = /(?:https?:\/\/)?(?:www\.|m\.|music\.)?(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|v\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
    const match = url.match(regExp);
    return match ? match[1] : '';
}

function handleYtUrlInput(url) {
    const ytId = extractYouTubeId(url);
    const previewEl = document.getElementById('ytPreviewBox');
    const thumbEl = document.getElementById('ytPreviewThumb');
    const nameInput = document.getElementById('customYtNameInput');
    const artistInput = document.getElementById('customYtArtistInput');
    const statusText = document.getElementById('ytFetchStatus');

    if (!ytId) {
        if (previewEl) previewEl.style.display = 'none';
        return;
    }

    if (previewEl) previewEl.style.display = 'flex';
    if (thumbEl) thumbEl.src = 'https://img.youtube.com/vi/' + ytId + '/hqdefault.jpg';
    if (statusText) statusText.textContent = 'Đang nhận diện bài hát từ YouTube...';

    fetch('https://noembed.com/embed?url=https://www.youtube.com/watch?v=' + ytId)
        .then(res => res.json())
        .then(data => {
            if (data && data.title) {
                if (nameInput && !nameInput.value) nameInput.value = data.title;
                if (artistInput && !artistInput.value) artistInput.value = data.author_name || 'YouTube Music';
                if (statusText) statusText.textContent = '✓ ' + data.title;
            } else {
                if (statusText) statusText.textContent = '✓ Video ID: ' + ytId;
            }
        })
        .catch(() => {
            if (statusText) statusText.textContent = '✓ Video ID: ' + ytId;
        });
}

function executeYtSearch() {
    const input = document.getElementById('ytSearchInput');
    const query = input ? input.value.trim() : '';
    const container = document.getElementById('ytSearchResults');
    const btn = document.getElementById('ytSearchSubmitBtn');

    if (!query) {
        alert('Vui lòng nhập tên bài hát hoặc ca sĩ!');
        return;
    }

    if (container) {
        container.style.display = 'block';
        container.innerHTML = '<div style="text-align:center; padding:25px 10px; color:#c4b5fd; font-size:12.5px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:22px; margin-bottom:8px; display:block; color:#f43f5e;"></i>Đang tìm bài hát từ YouTube...</div>';
    }
    if (btn) btn.disabled = true;

    fetch('/tkb/api/yt_search.php?q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            if (btn) btn.disabled = false;
            if (!container) return;

            if (!data || data.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:20px; color:#a79bb7; font-size:12px;"><i class="fa-solid fa-circle-exclamation" style="font-size:20px; margin-bottom:6px; display:block; color:#f59e0b;"></i>Không tìm thấy kết quả phù hợp. Hãy thử từ khóa khác!</div>';
                return;
            }

            let html = '';
            data.forEach(item => {
                const safeTitle = (item.title || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                const safeChannel = (item.channel || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                const safeThumb = (item.thumbnail || '').replace(/'/g, "\\'");
                html += `
                <div style="display:flex; align-items:center; gap:10px; background:#140c26; border:1px solid rgba(244,63,94,0.22); border-radius:10px; padding:8px 10px; margin-bottom:8px; transition:all 0.2s; cursor:pointer;" onmouseover="this.style.borderColor='#f43f5e'; this.style.background='#1f103b';" onmouseout="this.style.borderColor='rgba(244,63,94,0.22)'; this.style.background='#140c26';" onclick="quickAddYt('${item.id}', '${safeTitle}', '${safeChannel}', '${safeThumb}')">
                    <div style="position:relative; width:68px; height:42px; flex-shrink:0; border-radius:6px; overflow:hidden; border:1px solid rgba(255,255,255,0.1);">
                        <img src="${item.thumbnail}" style="width:100%; height:100%; object-fit:cover;">
                        ${item.duration ? `<span style="position:absolute; bottom:2px; right:2px; background:rgba(0,0,0,0.8); color:#fff; font-size:9.5px; font-weight:700; padding:1px 4px; border-radius:3px;">${item.duration}</span>` : ''}
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:12px; font-weight:700; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${item.title}</div>
                        <div style="font-size:11px; color:#c4b5fd; margin-top:2px;">${item.channel} • <span style="color:#38bdf8;">${item.views || 'YouTube'}</span></div>
                    </div>
                    <button type="button" style="background:linear-gradient(135deg, #f43f5e, #e11d48); color:#fff; border:none; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; font-size:11px; box-shadow:0 2px 8px rgba(244,63,94,0.4);">
                        <i class="fa-solid fa-play"></i>
                    </button>
                </div>`;
            });
            container.innerHTML = html;
        })
        .catch(() => {
            if (btn) btn.disabled = false;
            if (container) {
                container.innerHTML = '<div style="text-align:center; padding:15px; color:#f87171; font-size:12px;">Lỗi kết nối khi tìm kiếm YouTube!</div>';
            }
        });
}

function submitAddCustomYtTrack() {
    const url = document.getElementById('customYtUrlInput').value.trim();
    const ytId = extractYouTubeId(url);

    if (!ytId) {
        alert('Vui lòng nhập đường link video hoặc livestream YouTube hợp lệ!');
        return;
    }

    let title = document.getElementById('customYtNameInput').value.trim();
    let artist = document.getElementById('customYtArtistInput').value.trim();
    if (!title) title = 'YouTube Music (' + ytId + ')';
    if (!artist) artist = 'YouTube';

    const newTrack = {
        title: title,
        artist: artist,
        type: 'youtube',
        ytId: ytId,
        url: 'https://www.youtube.com/watch?v=' + ytId,
        thumbnail: 'https://img.youtube.com/vi/' + ytId + '/hqdefault.jpg',
        vibe: 'YouTube',
        isSynth: false
    };

    customLofiTracks.push(newTrack);
    localStorage.setItem('adm_custom_lofi_tracks', JSON.stringify(customLofiTracks));

    document.getElementById('customYtUrlInput').value = '';
    document.getElementById('customYtNameInput').value = '';
    document.getElementById('customYtArtistInput').value = '';
    const previewEl = document.getElementById('ytPreviewBox');
    if (previewEl) previewEl.style.display = 'none';

    switchMusicModalTab('my');
    renderPlaylistContainers();
    playTrackByIndex(customLofiTracks.length - 1);
}

function quickAddYt(ytId, title, artist, thumb) {
    const newTrack = {
        title: title,
        artist: artist,
        type: 'youtube',
        ytId: ytId,
        url: 'https://www.youtube.com/watch?v=' + ytId,
        thumbnail: thumb || ('https://img.youtube.com/vi/' + ytId + '/hqdefault.jpg'),
        vibe: 'YouTube',
        isSynth: false
    };
    customLofiTracks.push(newTrack);
    localStorage.setItem('adm_custom_lofi_tracks', JSON.stringify(customLofiTracks));
    switchMusicModalTab('my');
    renderPlaylistContainers();
    playTrackByIndex(customLofiTracks.length - 1);
}

function renderPlaylistContainers() {
    const myCont = document.getElementById('myTracksContainer');
    const myCount = document.getElementById('myTracksCount');
    if (myCount) myCount.textContent = customLofiTracks.length;

    if (myCont) {
        if (customLofiTracks.length === 0) {
            myCont.innerHTML = '<div style="text-align:center; padding:30px 10px; color:#a79bb7; font-size:12px;"><i class="fa-solid fa-folder-open" style="font-size:24px; color:#a855f7; margin-bottom:8px; display:block;"></i>Bạn chưa thêm bài hát nào. Hãy dùng tab "Tìm &amp; Thêm Nhạc YouTube" để tìm bài hát yêu thích nhé!</div>';
        } else {
            let html = '';
            customLofiTracks.forEach((t, idx) => {
                const isActive = (currentTrackIndex === idx);
                const isYt = (t.type === 'youtube' || !!t.ytId);
                const thumbUrl = t.thumbnail || (isYt ? ('https://img.youtube.com/vi/' + (t.ytId || extractYouTubeId(t.url)) + '/hqdefault.jpg') : '');
                html += `
                <div class="lofi-track-item ${isActive ? 'active' : ''}" onclick="playTrackByIndex(${idx})">
                    <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                        <div style="width:32px; height:32px; border-radius:8px; overflow:hidden; background:${isYt ? 'rgba(244,63,94,0.18)' : 'rgba(56,189,248,0.2)'}; display:flex; align-items:center; justify-content:center; color:${isActive ? '#34d399' : (isYt ? '#fb7185' : '#38bdf8')}; font-size:12px; flex-shrink:0;">
                            ${thumbUrl ? `<img src="${thumbUrl}" style="width:100%; height:100%; object-fit:cover;">` : `<i class="${isYt ? 'fa-brands fa-youtube' : (isActive && isLofiPlaying ? 'fa-solid fa-volume-high fa-beat' : 'fa-solid fa-play')}"></i>`}
                        </div>
                        <div style="min-width:0;">
                            <div style="font-weight:700; font-size:12.5px; color:${isActive ? '#ffffff' : '#e9d5ff'}; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${t.title}</div>
                            <div style="font-size:11px; color:#a79bb7;">${t.artist}</div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:10px; color:${isYt ? '#fb7185' : '#34d399'}; background:${isYt ? 'rgba(244,63,94,0.15)' : 'rgba(52,211,153,0.12)'}; padding:2px 6px; border-radius:4px; font-weight:700;">${isYt ? 'YouTube' : 'Tệp Nhạc'}</span>
                        <button type="button" onclick="event.stopPropagation(); deleteCustomTrack(${idx})" title="Xóa bài hát" style="background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color:#fca5a5; width:24px; height:24px; border-radius:6px; cursor:pointer; font-size:10px; display:flex; align-items:center; justify-content:center;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>`;
            });
            myCont.innerHTML = html;
        }
    }
}

function submitAddCustomTrackUrl() {
    const title = document.getElementById('customTrackNameInput').value.trim();
    const artist = document.getElementById('customTrackArtistInput').value.trim() || 'Nhạc Tự Thêm';
    const url = document.getElementById('customTrackUrlInput').value.trim();

    if (!title || !url) {
        alert('Vui lòng nhập đầy đủ tên bài hát và liên kết âm thanh!');
        return;
    }

    const newTrack = {
        title: title,
        artist: artist,
        url: url,
        vibe: 'MP3',
        isSynth: false
    };

    customLofiTracks.push(newTrack);
    localStorage.setItem('adm_custom_lofi_tracks', JSON.stringify(customLofiTracks));

    document.getElementById('customTrackNameInput').value = '';
    document.getElementById('customTrackArtistInput').value = '';
    document.getElementById('customTrackUrlInput').value = '';

    switchMusicModalTab('my');
    renderPlaylistContainers();
    playTrackByIndex(customLofiTracks.length - 1);
}

function handleLocalAudioFile(e) {
    const file = e.target.files[0];
    if (!file) return;

    const fileUrl = URL.createObjectURL(file);
    const fileName = file.name.replace(/\.[^/.]+$/, "");

    const newTrack = {
        title: fileName,
        artist: 'Tệp từ máy tính (' + Math.round(file.size / 1024 / 1024 * 10) / 10 + ' MB)',
        url: fileUrl,
        vibe: 'Local',
        isSynth: false
    };

    customLofiTracks.push(newTrack);
    localStorage.setItem('adm_custom_lofi_tracks', JSON.stringify(customLofiTracks));

    switchMusicModalTab('my');
    renderPlaylistContainers();
    playTrackByIndex(customLofiTracks.length - 1);
}

function deleteCustomTrack(idx) {
    if (!confirm('Bạn có chắc muốn xóa bài hát này khỏi danh sách?')) return;
    const wasPlaying = (currentTrackIndex === idx && isLofiPlaying);
    customLofiTracks.splice(idx, 1);
    localStorage.setItem('adm_custom_lofi_tracks', JSON.stringify(customLofiTracks));
    if (currentTrackIndex >= customLofiTracks.length) {
        currentTrackIndex = Math.max(0, customLofiTracks.length - 1);
    }
    if (customLofiTracks.length === 0) {
        pauseLofiMusic();
        updateUI();
    } else if (wasPlaying) {
        playCurrentTrack();
    } else {
        updateUI();
    }
    renderPlaylistContainers();
}

let volTooltipTimer = null;
function showVolumeTooltip(pct) {
    const tip = document.getElementById('topbarVolTooltip');
    if (!tip) return;
    tip.textContent = (pct === 0 ? '🔇 Mute' : (pct < 40 ? '🔉 ' : '🔊 ') + pct + '%');
    tip.classList.add('show');
    clearTimeout(volTooltipTimer);
    volTooltipTimer = setTimeout(() => {
        tip.classList.remove('show');
    }, 1200);
}

document.addEventListener('DOMContentLoaded', function() {
    updateUI();

    const musicPlayerEl = document.getElementById('topbarMusicPlayer');
    if (musicPlayerEl) {
        musicPlayerEl.addEventListener('wheel', function(e) {
            e.preventDefault();
            const delta = e.deltaY < 0 ? 0.05 : -0.05;
            let newVol = Math.min(1, Math.max(0, lofiVolume + delta));
            newVol = Math.round(newVol * 100) / 100;
            setLofiVolume(newVol);
            showVolumeTooltip(Math.round(newVol * 100));
        }, { passive: false });
    }
});
<?php else: ?>
// Ensure no background audio or Lofi player runs in Cô Phan Ngọc Tuyền's interface
try {
    if (window.lofiAudioElement) {
        window.lofiAudioElement.pause();
        window.lofiAudioElement.src = '';
    }
} catch(e) {}
<?php endif; ?>
</script>

<?php
// Nhúng Widget Trợ Lý AI Vũ Trụ Cosmic Admin
if (file_exists(__DIR__ . '/admin_cosmic_bot.php')) {
    include __DIR__ . '/admin_cosmic_bot.php';
}
?>
<!-- 🌸 Hiệu ứng Hoa Anh Đào Rơi Tự Nhiên (Sakura Falling Canvas Engine) 🌸 -->
<script src="/tkb/assets/sakura_fall.js?v=<?= time() ?>" defer></script>