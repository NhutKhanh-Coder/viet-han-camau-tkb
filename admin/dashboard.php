<?php
require_once '../config.php';
requireAdmin();
$db = getDB();

$fkhoa = $_GET['khoa'] ?? '';

$count_sv = $db->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$count_mon = $db->query("SELECT COUNT(*) FROM mon_hoc")->fetch_row()[0];
$count_tkb = $db->query("SELECT COUNT(*) FROM thoi_khoa_bieu")->fetch_row()[0];
$count_diem = $db->query("SELECT COUNT(*) FROM diem")->fetch_row()[0];

$sql_sv = "SELECT * FROM students";
if ($fkhoa) {
    $sql_sv .= " WHERE khoa = '" . $db->real_escape_string($fkhoa) . "'";
}
$sql_sv .= " ORDER BY id DESC LIMIT 10";
$recent_students = $db->query($sql_sv)->fetch_all(MYSQLI_ASSOC);

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
<style>
  .main-content {
      background: var(--bg);
      min-height: 100vh;
      animation: fadeIn 0.8s ease-out;
  }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

  .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 40px; }
  .stat-card {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 20px; padding: 28px;
      border: 1px solid rgba(255, 255, 255, 0.55);
      position: relative; overflow: hidden;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: var(--shadow-sm);
  }
  .stat-card::before {
      content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
      background: linear-gradient(90deg, var(--accent), var(--accent2));
      opacity: 0; transition: 0.3s;
  }
  .stat-card:hover { 
      transform: translateY(-4px); 
      box-shadow: var(--shadow-md); 
      border-color: rgba(217, 27, 67, 0.15); 
  }
  .stat-card:hover::before { opacity: 1; }
  
  .stat-icon { 
      width: 52px; height: 52px; 
      background: rgba(198,42,71,0.08); 
      border-radius: 14px; 
      display: flex; align-items: center; justify-content: center; 
      font-size: 22px; margin-bottom: 16px; 
      color: var(--accent); 
      border: 1px solid rgba(198,42,71,0.12); 
  }
  .stat-value { 
      font-size: 34px; 
      font-weight: 800; 
      line-height: 1; 
      margin-bottom: 6px; 
      color: #0f172a; 
      font-family: 'Playfair Display', serif; 
  }
  .stat-label { 
      font-size: 12px; 
      color: var(--text2); 
      font-weight: 700; 
      text-transform: uppercase; 
      letter-spacing: 0.5px; 
  }

  .db-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; }
  .db-panel { 
      background: rgba(255, 255, 255, 0.75); 
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 20px; 
      padding: 30px; 
      box-shadow: var(--shadow-sm); 
      border: 1px solid rgba(255, 255, 255, 0.55); 
      transition: all 0.3s ease;
  }
  .db-panel:hover {
      box-shadow: var(--shadow-md);
      border-color: rgba(217, 27, 67, 0.12);
  }
  .db-panel h3 { 
      margin: 0 0 24px; 
      font-size: 16px; 
      font-weight: 800; 
      color: #0f172a; 
      text-transform: uppercase; 
      letter-spacing: 0.5px; 
      display: flex; 
      align-items: center; 
      gap: 10px; 
  }
  .db-panel h3::before { 
      content: ''; width: 4px; height: 16px; 
      background: var(--accent); 
      border-radius: 4px; 
      box-shadow: 0 0 8px rgba(198,42,71,0.4); 
  }

  .sv-item { 
      display: flex; 
      align-items: center; 
      gap: 16px; 
      padding: 14px; 
      border-radius: 12px; 
      border: 1px solid #f1f5f9; 
      transition: 0.25s; 
      background: #f8fafc; 
      margin-bottom: 10px; 
  }
  .sv-item:last-child { margin-bottom: 0; }
  .sv-item:hover { 
      background: rgba(198,42,71,0.04); 
      border-color: rgba(198,42,71,0.15); 
      transform: translateX(4px); 
  }
  .sv-av { 
      width: 40px; height: 40px; 
      border-radius: 10px; 
      background: rgba(198,42,71,0.08); 
      color: var(--accent); 
      display: flex; align-items: center; justify-content: center; 
      font-weight: 800; font-size: 15px; 
      border: 1px solid rgba(198,42,71,0.15); 
  }
  .sv-info { flex: 1; }
  .sv-name { font-weight: 700; font-size: 14.5px; color: #0f172a; margin-bottom: 2px; }
  .sv-sub { font-size: 12px; color: var(--text2); display: flex; align-items: center; gap: 8px; }

  .quick-actions { display: grid; grid-template-columns: 1fr; gap: 12px; }
  .qa-btn { 
      display: flex; align-items: center; gap: 16px; 
      padding: 16px 20px; border-radius: 14px; 
      cursor: pointer; text-decoration: none; 
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
      background: #ffffff; 
      border: 1px solid rgba(217, 27, 67, 0.08); 
      position: relative; overflow: hidden; 
  }
  .qa-btn:hover { 
      transform: translateX(6px) translateY(-1px); 
      border-color: rgba(217,27,67,0.25); 
      background: rgba(217,27,67,0.02); 
      box-shadow: var(--shadow-sm); 
  }
  .qa-icon { 
      font-size: 18px; color: var(--accent); 
      width: 40px; height: 40px; 
      background: rgba(198,42,71,0.06); 
      border-radius: 10px; 
      display: flex; align-items: center; justify-content: center; 
      border: 1px solid rgba(198,42,71,0.12); 
      transition: 0.25s; 
  }
  .qa-btn:hover .qa-icon { 
      background: var(--accent); color: #fff; 
      box-shadow: 0 4px 12px rgba(198,42,71,0.2); 
      border-color: transparent; 
  }
  .qa-text h4 { margin: 0 0 4px; font-weight: 700; font-size: 14.5px; color: #0f172a; transition: 0.25s; }
  .qa-text p { margin: 0; font-size: 12.5px; color: var(--text2); line-height: 1.4; }
  .qa-btn:hover .qa-text h4 { color: var(--accent); }

  /* Mobile hamburger */
  .mobile-header { 
      display: none; align-items: center; justify-content: space-between; 
      padding: 15px 20px; background: #ffffff; 
      border-bottom: 1px solid #e2e8f0; 
      position: sticky; top: 0; z-index: 50; 
      box-shadow: 0 2px 10px rgba(0,0,0,0.02);
  }
  .mobile-header-title { color: #0f172a; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
  .mobile-hamburger { display: flex; flex-direction: column; gap: 5px; cursor: pointer; background: none; border: none; padding: 5px; }
  .mobile-hamburger span { display: block; width: 22px; height: 2.5px; background: #0f172a; border-radius: 2px; }

  /* ===== CHATBOT (Admin Light style) ===== */
  #chat-fab {
    position: fixed; bottom: 30px; right: 30px; z-index: 9999;
    width: 58px; height: 58px; border-radius: 50%;
    background: var(--accent);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; box-shadow: 0 8px 25px rgba(217, 27, 67, 0.45);
    transition: transform 0.25s, box-shadow 0.25s;
    border: 1.5px solid rgba(255, 255, 255, 0.3);
  }
  #chat-fab:hover { transform: scale(1.1) rotate(5deg); box-shadow: 0 12px 32px rgba(217, 27, 67, 0.6); }
  #chat-fab .notif-dot {
    position: absolute; top: 4px; right: 4px;
    width: 12px; height: 12px; background: #10b981;
    border-radius: 50%; border: 2px solid #fff;
    animation: pulse-dot 2s infinite;
  }
  @keyframes pulse-dot { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.3);opacity:0.7} }

  #chat-window {
    position: fixed; bottom: 100px; right: 30px; z-index: 9998;
    width: 390px; height: 560px;
    background: rgba(255, 255, 255, 0.78);
    backdrop-filter: blur(30px);
    -webkit-backdrop-filter: blur(30px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 16px 48px rgba(15,23,42,0.12);
    display: none; flex-direction: column; overflow: hidden;
    font-family: 'Outfit', sans-serif;
    animation: chatSlideIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
  }
  @keyframes chatSlideIn { from { opacity:0; transform:translateY(20px) scale(0.97); } to { opacity:1; transform:translateY(0) scale(1); } }

  #chat-header {
    background: var(--accent);
    padding: 16px 20px; display: flex; align-items: center; gap: 12px; flex-shrink: 0;
  }
  .ch-av {
    width: 38px; height: 38px; border-radius: 50%;
    background: rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff; flex-shrink: 0;
  }
  .ch-info h4 { color: #fff; font-weight: 700; font-size: 13.5px; margin: 0 0 2px; }
  .ch-info p { color: rgba(255,255,255,0.75); font-size: 11px; margin: 0; }
  #ch-close {
    margin-left: auto; background: rgba(255,255,255,0.12); border: none;
    color: #fff; width: 28px; height: 28px; border-radius: 50%;
    cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center;
    transition: background 0.2s; flex-shrink: 0;
  }
  #ch-close:hover { background: rgba(255,255,255,0.25); }

  #chat-msgs {
    flex: 1; overflow-y: auto; padding: 16px;
    display: flex; flex-direction: column; gap: 12px;
    background: rgba(244, 246, 252, 0.6);
  }
  #chat-msgs::-webkit-scrollbar { width: 4px; }
  #chat-msgs::-webkit-scrollbar-thumb { background: rgba(217, 27, 67, 0.25); border-radius: 4px; }

  .cmsg { display: flex; gap: 9px; align-items: flex-end; max-width: 88%; }
  .cmsg.user { align-self: flex-end; flex-direction: row-reverse; }
  .cmsg-av {
    width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700;
  }
  .cmsg.bot .cmsg-av { background: rgba(217,27,67,0.08); border: 1px solid rgba(217,27,67,0.15); color: var(--accent); }
  .cmsg.user .cmsg-av { background: rgba(217,27,67,0.08); border: 1px solid rgba(217,27,67,0.15); color: var(--accent); }
  
  .cbubble {
    padding: 10px 14px; border-radius: 16px;
    font-size: 13px; line-height: 1.55;
  }
  .cmsg.bot .cbubble {
    background: rgba(255,255,255,0.9); border: 1px solid rgba(255,255,255,0.6);
    border-bottom-left-radius: 4px; color: #0f172a;
    box-shadow: 0 4px 12px rgba(15,23,42,0.03);
  }
  .cmsg.user .cbubble {
    background: var(--accent);
    border-bottom-right-radius: 4px; color: #fff;
    box-shadow: 0 4px 12px rgba(217, 27, 67, 0.15);
  }

  @keyframes chatBounce { 0%,80%,100%{transform:translateY(0)} 40%{transform:translateY(-6px)} }
  .typing-dot {
    width: 6px; height: 6px; background: var(--accent); border-radius: 50%; display: block;
    animation: chatBounce 1.2s infinite;
  }

  #chat-quick {
    padding: 10px 16px 14px; display: flex; gap: 6px; flex-wrap: wrap; flex-shrink: 0;
    background: rgba(244, 246, 252, 0.6); border-top: 1px solid rgba(255,255,255,0.4);
  }
  .cq-btn {
    font-size: 11px; padding: 5px 12px; border-radius: 20px;
    border: 1px solid rgba(217, 27, 67, 0.25); background: rgba(217, 27, 67, 0.04);
    color: var(--accent); cursor: pointer; font-family: 'Outfit', sans-serif;
    transition: all 0.2s; white-space: nowrap;
    font-weight: 600;
  }
  .cq-btn:hover { background: var(--accent); border-color: var(--accent); color: #fff; transform: translateY(-1px); }

  #chat-footer {
    padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.4);
    display: flex; gap: 10px; flex-shrink: 0; background: rgba(255, 255, 255, 0.5);
  }
  #chat-input-box {
    flex: 1; background: rgba(255,255,255,0.6); border: 1.5px solid rgba(255,255,255,0.5);
    border-radius: 12px; padding: 10px 14px; color: #0f172a;
    font-size: 13px; font-family: 'Outfit', sans-serif;
    resize: none; outline: none; line-height: 1.4;
    max-height: 100px; overflow-y: auto; transition: border-color 0.2s, background-color 0.2s;
  }
  #chat-input-box:focus { border-color: rgba(217, 27, 67, 0.4); background: #fff; }
  #chat-input-box::placeholder { color: #94a3b8; }
  #chat-send-btn {
    background: var(--accent);
    border: none; border-radius: 12px; width: 40px; height: 40px;
    color: #fff; cursor: pointer; font-size: 14px; flex-shrink: 0;
    transition: transform 0.1s; display: flex; align-items: center; justify-content: center;
  }
  #chat-send-btn:hover { transform: scale(1.05); }
  #chat-send-btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

  @media (max-width: 1200px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 900px) { .db-grid { grid-template-columns: 1fr; } }
  @media (max-width: 768px) {
    .mobile-header { display: flex; }
    .page-header { padding: 12px 15px !important; margin-bottom: 15px !important; }
    .page-header h1 { font-size: 16px !important; }
    .page-header p { display: none !important; }
    .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px; }
    .stat-card { padding: 16px 12px; border-radius: 14px; }
    .stat-icon { width: 36px; height: 36px; font-size: 16px; margin-bottom: 10px; border-radius: 10px; }
    .stat-value { font-size: 28px; margin-bottom: 4px; }
    .stat-label { font-size: 10px; letter-spacing: 0; line-height: 1.3; }
    .db-panel { padding: 15px; border-radius: 12px; }
    .db-panel h3 { font-size: 13px; margin-bottom: 12px; }
    .qa-btn { padding: 12px 14px; gap: 12px; border-radius: 10px; }
    .qa-icon { width: 36px; height: 36px; font-size: 15px; border-radius: 10px; }
    .qa-text h4 { font-size: 13px; margin-bottom: 2px; }
    .qa-text p { display: none; }
    .sv-item { padding: 10px; gap: 10px; border-radius: 10px; }
    .sv-name { font-size: 13px; }
    .sv-sub { font-size: 11px; }
    .main-content { padding: 0 !important; }
    #chat-window { width: calc(100vw - 20px); right: 10px; bottom: 90px; height: 75vh; }
    #chat-fab { bottom: 20px; right: 20px; }
  }
</style>
</head>
<body>
<?php include '../includes/admin_nav.php'; ?>

<div class="main-content">
  <div class="mobile-header">
    <button class="mobile-hamburger" onclick="toggleAdminSidebar()">
      <span></span><span></span><span></span>
    </button>
    <span class="mobile-header-title"><i class="fa-solid fa-bolt" style="color:red;margin-right:8px;"></i>Bảng Điều Khiển</span>
  </div>

  <div class="page-header" style="flex-direction:column; align-items:flex-start; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 1px solid rgba(217,27,67,0.1);">
    <h1 style="font-size: 32px; font-weight: 900; color: var(--text); margin: 0 0 8px; text-transform: uppercase; letter-spacing: 1px;">
      <i class="fa-solid fa-bolt" style="color: var(--accent); margin-right: 10px;"></i> Bảng Điều Khiển Trung Tâm
    </h1>
    <p style="color: var(--text2); margin: 0; font-size: 15px; letter-spacing: 0.5px;">HỆ THỐNG QUẢN TRỊ VIÊN CẤP CAO (SYSTEM ADMIN)</p>
  </div>

  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
      <div class="stat-value"><?= str_pad($count_sv, 2, '0', STR_PAD_LEFT) ?></div>
      <div class="stat-label">Tổng Sinh Viên</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
      <div class="stat-value"><?= str_pad($count_mon, 2, '0', STR_PAD_LEFT) ?></div>
      <div class="stat-label">Môn Học</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fa-solid fa-calendar-days"></i></div>
      <div class="stat-value"><?= str_pad($count_tkb, 2, '0', STR_PAD_LEFT) ?></div>
      <div class="stat-label">Lịch Học (TKB)</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fa-solid fa-chart-simple"></i></div>
      <div class="stat-value"><?= str_pad($count_diem, 2, '0', STR_PAD_LEFT) ?></div>
      <div class="stat-label">Bản Ghi Điểm</div>
    </div>
  </div>

  <div class="db-grid">
    <div class="db-panel">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
        <h3 style="margin:0;">Sinh viên</h3>
        <form method="GET" style="display:inline-block;">
          <select name="khoa" class="form-select" onchange="this.form.submit()" style="padding: 6px 12px; font-size: 13px; min-width: 150px;">
            <option value="">-- Tất cả ngành --</option>
            <?php global $NGANH_LIST; foreach ($NGANH_LIST as $ng): ?>
              <option value="<?= htmlspecialchars($ng) ?>" <?= $fkhoa === $ng ? 'selected' : '' ?>><?= htmlspecialchars($ng) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <div style="max-height: 400px; overflow-y: auto; padding-right: 5px;">
        <?php foreach($recent_students as $s):
          $parts = explode(' ', trim($s['ho_ten']));
          $initial = mb_substr(array_pop($parts), 0, 1, 'UTF-8');
          $khoa_parts = explode(' ', $s['khoa'] ?? '');
          $khoa_short = $khoa_parts[0] ?? '';
        ?>
        <div class="sv-item" onclick="showStudentInfo(<?= $s['id'] ?>)" style="cursor: pointer;">
          <?php if (!empty($s['avatar'])): ?>
            <img src="/tkb/assets/img/avatars/<?= htmlspecialchars($s['avatar']) ?>" class="sv-av" style="object-fit: cover; border: 1px solid var(--accent); padding: 0; display: block;" alt="Avatar">
          <?php else: ?>
            <div class="sv-av"><?= mb_strtoupper($initial, 'UTF-8') ?></div>
          <?php endif; ?>
          <div class="sv-info">
            <div class="sv-name"><?= htmlspecialchars($s['ho_ten']) ?></div>
            <div class="sv-sub"><?= htmlspecialchars($s['ma_sv']) ?> &bull; <?= htmlspecialchars($s['lop'] ?? '') ?></div>
          </div>
          <?php if($khoa_short): ?>
          <span class="badge" style="background:rgba(166,25,46,0.1);color:#a6192e"><?= htmlspecialchars($khoa_short) ?></span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if(empty($recent_students)): ?>
          <div style="color:#aaa; text-align:center; padding:20px">Chưa có sinh viên nào</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="db-panel">
      <h3>Thao tác quản trị hệ thống</h3>
      <div class="quick-actions">
        <a href="/tkb/admin/students.php" class="qa-btn">
          <div class="qa-icon"><i class="fa-solid fa-user-graduate"></i></div>
          <div class="qa-text"><h4>Quản lý Sinh viên</h4><p>Xem hồ sơ, cấp tài khoản, chỉnh sửa thông tin</p></div>
        </a>
        <a href="/tkb/admin/tkb.php" class="qa-btn">
          <div class="qa-icon"><i class="fa-solid fa-calendar-week"></i></div>
          <div class="qa-text"><h4>Thời khóa biểu</h4><p>Sắp xếp lịch học, phân công giảng viên</p></div>
        </a>
        <a href="/tkb/admin/diem.php" class="qa-btn">
          <div class="qa-icon"><i class="fa-solid fa-award"></i></div>
          <div class="qa-text"><h4>Hệ thống Điểm số</h4><p>Cập nhật điểm quá trình, kết thúc học phần</p></div>
        </a>
        <a href="/tkb/admin/diemdanh.php" class="qa-btn">
          <div class="qa-icon"><i class="fa-solid fa-clipboard-user"></i></div>
          <div class="qa-text"><h4>Trung tâm Điểm danh</h4><p>Khởi tạo QR Check-in, thống kê chuyên cần</p></div>
        </a>
        <a href="/tkb/admin/taichinh.php" class="qa-btn">
          <div class="qa-icon"><i class="fa-solid fa-wallet"></i></div>
          <div class="qa-text"><h4>Tài chính & Học phí</h4><p>Quản lý các khoản thu và công nợ sinh viên</p></div>
        </a>
        <a href="/tkb/admin/thongbao.php" class="qa-btn">
          <div class="qa-icon"><i class="fa-solid fa-rss"></i></div>
          <div class="qa-text"><h4>Phát sóng Thông báo</h4><p>Gửi thông báo khẩn đến toàn hệ thống</p></div>
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ===== MODAL SINH VIÊN ===== -->
<div class="modal-overlay" id="svModal" onclick="if(event.target==this) toggleModal('svModal')">
  <div class="modal-box" style="width: 700px; max-width: 95vw;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; border-bottom: 1px solid rgba(255,0,0,0.2); padding-bottom: 15px;">
      <h3 class="modal-title" style="margin:0;"><i class="fa-solid fa-address-card" style="color:var(--accent);"></i> Thông tin Sinh viên</h3>
      <button onclick="toggleModal('svModal')" style="background:none; border:none; color:var(--text2); font-size:20px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="svModalContent" style="display: none;">
      <div style="display: flex; gap: 20px; margin-bottom: 20px; background: rgba(217, 27, 67, 0.03); padding: 20px; border-radius: 12px; border: 1px solid var(--border); flex-wrap: wrap;">
        <div id="svModalAv" style="width:80px;height:80px;border-radius:50%;background:rgba(217, 27, 67, 0.08);color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:32px;border:2px solid rgba(217, 27, 67, 0.2);overflow:hidden;flex-shrink:0;"></div>
        <div style="flex:1;min-width:150px;">
          <h2 id="svModalName" style="margin:0 0 5px;color:var(--text);font-size:20px;font-weight:800;"></h2>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;color:var(--text2);">
            <div><i class="fa-solid fa-id-badge" style="width:16px;color:var(--accent);"></i> <span id="svModalMsv"></span></div>
            <div><i class="fa-solid fa-shield" style="width:16px;color:var(--accent);"></i> <span id="svModalLop"></span></div>
            <div><i class="fa-solid fa-graduation-cap" style="width:16px;color:var(--accent);"></i> <span id="svModalKhoa"></span></div>
            <div><i class="fa-solid fa-envelope" style="width:16px;color:var(--accent);"></i> <span id="svModalEmail"></span></div>
          </div>
        </div>
        <div style="text-align:center;display:flex;flex-direction:column;justify-content:center;border-left:1px solid var(--border);padding-left:20px;">
          <div style="font-size:12px;color:var(--text2);text-transform:uppercase;font-weight:600;margin-bottom:5px;">Học lực</div>
          <div id="svModalHocLuc" style="font-size:24px;font-weight:900;color:var(--accent);"></div>
          <div style="font-size:12px;color:var(--text2);margin-top:5px;">ĐTB: <strong id="svModalDtb" style="color:var(--text);"></strong></div>
        </div>
      </div>
      <h4 style="margin:0 0 15px;color:var(--text);font-size:15px;border-left:3px solid var(--accent);padding-left:10px;">Bảng điểm chi tiết</h4>
      <div style="max-height:250px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;">
          <thead style="background:rgba(217, 27, 67, 0.03);position:sticky;top:0;">
            <tr>
              <th style="padding:10px;border-bottom:1px solid var(--border);color:var(--accent);font-weight:600;background:rgba(217, 27, 67, 0.03);">Môn học</th>
              <th style="padding:10px;border-bottom:1px solid var(--border);color:var(--accent);font-weight:600;background:rgba(217, 27, 67, 0.03);">Quá trình</th>
              <th style="padding:10px;border-bottom:1px solid var(--border);color:var(--accent);font-weight:600;background:rgba(217, 27, 67, 0.03);">Thi</th>
              <th style="padding:10px;border-bottom:1px solid var(--border);color:var(--accent);font-weight:600;background:rgba(217, 27, 67, 0.03);">Tổng kết</th>
            </tr>
          </thead>
          <tbody id="svModalDiemBody"></tbody>
        </table>
      </div>
    </div>
    <div id="svModalLoading" style="text-align:center;padding:40px;color:#888;">
      <i class="fa-solid fa-circle-notch fa-spin" style="font-size:30px;color:var(--accent);margin-bottom:15px;"></i>
      <div>Đang tải dữ liệu...</div>
    </div>
  </div>
</div>

<!-- ===== CHATBOT GROQ ===== -->
<!-- Nút mở chatbot -->
<button id="chat-fab" onclick="toggleChatBot()" title="Trợ lý AI">
  <i class="fa-solid fa-robot" style="color:#fff;font-size:22px;"></i>
  <span class="notif-dot"></span>
</button>

<!-- Cửa sổ chat -->
<div id="chat-window">
  <!-- Header -->
  <div id="chat-header">
    <div class="ch-av"><i class="fa-solid fa-robot"></i></div>
    <div class="ch-info">
      <h4>Trợ lý Admin AI</h4>
      <p>Powered by Groq &bull; Llama 3.3-70b &bull; <span style="color:#4ade80;">&#9679;</span> Online</p>
    </div>
    <button id="ch-close" onclick="toggleChatBot()">&times;</button>
  </div>

  <!-- Messages -->
  <div id="chat-msgs">
    <div class="cmsg bot">
      <div class="cmsg-av"><i class="fa-solid fa-robot" style="font-size:12px;"></i></div>
      <div class="cbubble">
        Xin chào <strong>Admin</strong>! 👋<br>
        Tôi là trợ lý AI của hệ thống. Tôi có thể giúp bạn:<br>
        &bull; Quản lý sinh viên, điểm số, lịch học<br>
        &bull; Giải đáp thắc mắc về hệ thống<br>
        &bull; Phân tích dữ liệu và báo cáo
      </div>
    </div>
  </div>

  <!-- Câu hỏi nhanh -->
  <div id="chat-quick">
    <button class="cq-btn" onclick="groqQuick(this)">Thêm sinh viên mới</button>
    <button class="cq-btn" onclick="groqQuick(this)">Cách nhập điểm</button>
    <button class="cq-btn" onclick="groqQuick(this)">Tạo QR điểm danh</button>
    <button class="cq-btn" onclick="groqQuick(this)">Xuất báo cáo</button>
  </div>

  <!-- Input -->
  <div id="chat-footer">
    <textarea id="chat-input-box" placeholder="Nhập câu hỏi... (Enter để gửi)" rows="1"
      onkeydown="groqKey(event)"
      oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"></textarea>
    <button id="chat-send-btn" onclick="groqSend()">
      <i class="fa-solid fa-paper-plane"></i>
    </button>
  </div>
</div>
<!-- ===== END CHATBOT ===== -->

<script>
// ===== MODAL SINH VIÊN =====
function toggleModal(id) {
    document.getElementById(id).classList.toggle('show');
}
function showStudentInfo(id) {
    toggleModal('svModal');
    document.getElementById('svModalContent').style.display = 'none';
    document.getElementById('svModalLoading').style.display = 'block';
    fetch('/tkb/api/get_student_info.php?id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.error) { alert(data.error); toggleModal('svModal'); return; }
            let sv = data.sv;
            document.getElementById('svModalName').textContent = sv.ho_ten;
            document.getElementById('svModalMsv').textContent = sv.ma_sv;
            document.getElementById('svModalLop').textContent = sv.lop || 'Chưa cập nhật';
            document.getElementById('svModalKhoa').textContent = sv.khoa || 'Chưa cập nhật';
            document.getElementById('svModalEmail').textContent = sv.email || 'Chưa cập nhật';
            let initial = sv.ho_ten.split(' ').pop().charAt(0).toUpperCase();
            document.getElementById('svModalAv').innerHTML = sv.avatar
                ? `<img src="/tkb/assets/img/avatars/${sv.avatar}" style="width:100%;height:100%;object-fit:cover;">`
                : initial;
            document.getElementById('svModalHocLuc').textContent = data.hoc_luc;
            document.getElementById('svModalDtb').textContent = data.dtb;
            let tbody = document.getElementById('svModalDiemBody');
            tbody.innerHTML = '';
            if (data.diem.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:15px;color:#888;">Chưa có điểm nào</td></tr>';
            } else {
                data.diem.forEach(d => {
                    let qt = d.diem_qua_trinh !== null ? d.diem_qua_trinh : '-';
                    let thi = d.diem_thi !== null ? d.diem_thi : '-';
                    let tk = d.diem_tong_ket !== null ? d.diem_tong_ket : '-';
                    let col = tk !== '-' ? (tk >= 5 ? 'var(--success)' : 'var(--accent)') : 'var(--text2)';
                    tbody.innerHTML += `<tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:10px;color:var(--text);">${d.ten_mon}</td>
                        <td style="padding:10px;color:var(--text2);">${qt}</td>
                        <td style="padding:10px;color:var(--text2);">${thi}</td>
                        <td style="padding:10px;font-weight:700;color:${col};">${tk}</td>
                    </tr>`;
                });
            }
            document.getElementById('svModalLoading').style.display = 'none';
            document.getElementById('svModalContent').style.display = 'block';
        })
        .catch(err => { console.error(err); alert('Lỗi khi tải dữ liệu'); toggleModal('svModal'); });
}

// ===== CHATBOT GROQ =====
(function() {
    const GROQ_KEY   = 'gsk_RQc8Lf9ro9hKAfMkltkcWGdyb3FY' . '8DX77y7FuqQhcY88iLQ6K0nB';
    const GROQ_MODEL = 'llama-3.3-70b-versatile';
    const SYS_PROMPT = `Bạn là trợ lý AI thông minh tích hợp trong Admin Dashboard của hệ thống quản trị đại học.
Hệ thống gồm: quản lý sinh viên, môn học, thời khóa biểu (TKB), điểm số, điểm danh QR, học phí, thông báo.
Hỗ trợ admin giải quyết công việc, hướng dẫn sử dụng, phân tích dữ liệu.
Trả lời ngắn gọn, rõ ràng, chuyên nghiệp bằng tiếng Việt. Dùng emoji khi phù hợp.`;

    let history = [];
    let busy    = false;
    let isOpen  = false;

    window.toggleChatBot = function() {
        isOpen = !isOpen;
        const win = document.getElementById('chat-window');
        if (isOpen) {
            win.style.display = 'flex';
            // Xóa dấu chấm thông báo
            const dot = document.querySelector('#chat-fab .notif-dot');
            if (dot) dot.style.display = 'none';
            document.getElementById('chat-input-box').focus();
            scrollBot();
        } else {
            win.style.display = 'none';
        }
    };

    function scrollBot() {
        const m = document.getElementById('chat-msgs');
        m.scrollTop = m.scrollHeight;
    }

    function botAvHtml() {
        return `<div class="cmsg-av"><i class="fa-solid fa-robot" style="font-size:12px;"></i></div>`;
    }

    function addMsg(role, text) {
        const msgs = document.getElementById('chat-msgs');
        const isUser = role === 'user';
        const div = document.createElement('div');
        div.className = 'cmsg ' + (isUser ? 'user' : 'bot');
        const av = isUser
            ? `<div class="cmsg-av" style="font-size:10px;font-weight:700;">You</div>`
            : botAvHtml();
        div.innerHTML = av + `<div class="cbubble">${text.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')}</div>`;
        msgs.appendChild(div);
        scrollBot();
    }

    function showTyping() {
        const msgs = document.getElementById('chat-msgs');
        const div = document.createElement('div');
        div.id = 'groq-typing';
        div.className = 'cmsg bot';
        div.innerHTML = botAvHtml() + `<div class="cbubble" style="padding:12px 16px;">
            <span class="typing-dot" style="display:inline-block;margin-right:4px;"></span>
            <span class="typing-dot" style="display:inline-block;margin-right:4px;animation-delay:0.2s;"></span>
            <span class="typing-dot" style="display:inline-block;animation-delay:0.4s;"></span>
        </div>`;
        msgs.appendChild(div);
        scrollBot();
    }

    function hideTyping() {
        const t = document.getElementById('groq-typing');
        if (t) t.remove();
    }

    async function callGroq(userText) {
        if (busy) return;
        busy = true;

        // Ẩn câu hỏi nhanh sau lần đầu
        const qk = document.getElementById('chat-quick');
        if (qk) qk.style.display = 'none';

        const btn = document.getElementById('chat-send-btn');
        btn.disabled = true;

        addMsg('user', userText);
        history.push({ role: 'user', content: userText });
        showTyping();

        try {
            const res = await fetch('https://api.groq.com/openai/v1/chat/completions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + GROQ_KEY
                },
                body: JSON.stringify({
                    model: GROQ_MODEL,
                    messages: [{ role: 'system', content: SYS_PROMPT }, ...history],
                    max_tokens: 900,
                    temperature: 0.7
                })
            });
            const data = await res.json();
            hideTyping();

            if (data.choices && data.choices[0]) {
                const reply = data.choices[0].message.content;
                history.push({ role: 'assistant', content: reply });
                addMsg('bot', reply);
            } else {
                addMsg('bot', '⚠️ Lỗi: ' + (data.error?.message || 'Không có phản hồi từ API'));
            }
        } catch (e) {
            hideTyping();
            addMsg('bot', '⚠️ Lỗi kết nối: ' + e.message);
        }

        busy = false;
        btn.disabled = false;
        document.getElementById('chat-input-box').focus();
    }

    window.groqSend = function() {
        const input = document.getElementById('chat-input-box');
        const text = input.value.trim();
        if (!text || busy) return;
        input.value = '';
        input.style.height = 'auto';
        callGroq(text);
    };

    window.groqKey = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            groqSend();
        }
    };

    window.groqQuick = function(btn) {
        if (busy) return;
        callGroq(btn.textContent);
    };
})();
</script>
</body>
</html>