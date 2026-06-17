<?php
require_once '../config.php';
requireAdmin();
$db  = getDB();
$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $ten_mon = trim($_POST['ten_mon']);
    // Find or create mon_hoc
    $mid = 0;
    $st_mon = $db->prepare("SELECT id FROM mon_hoc WHERE ten_mon = ?");
    $st_mon->bind_param("s", $ten_mon);
    $st_mon->execute();
    $res_mon = $st_mon->get_result();
    if ($res_mon->num_rows > 0) {
        $mid = $res_mon->fetch_assoc()['id'];
    } else {
        $st_ins = $db->prepare("INSERT INTO mon_hoc (ten_mon, ma_mon) VALUES (?, 'NEW')");
        $st_ins->bind_param("s", $ten_mon);
        $st_ins->execute();
        $mid = $db->insert_id;
    }
    
    $gid = (int)$_POST['giang_vien_id'] ?: null;
    $kh  = trim($_POST['khoa'] ?? '');
    $thu = (int)$_POST['thu'];
    $tb  = (int)$_POST['tiet_bat_dau'];
    $tk  = (int)$_POST['tiet_ket_thuc'];
    $ph  = trim($_POST['phong_hoc'] ?? '');
    $hk  = trim($_POST['hoc_ky'] ?? '');
    $nh  = trim($_POST['nam_hoc'] ?? '');
    $st  = $db->prepare("INSERT INTO thoi_khoa_bieu (mon_hoc_id,giang_vien_id,khoa,thu,tiet_bat_dau,tiet_ket_thuc,phong_hoc,hoc_ky,nam_hoc) VALUES(?,?,?,?,?,?,?,?,?)");
    $st->bind_param("iisiissss", $mid,$gid,$kh,$thu,$tb,$tk,$ph,$hk,$nh);
    if ($st->execute()) $msg='success:Thêm TKB thành công!';
    else $msg='error:Lỗi thêm TKB!';
}
if ($action === 'edit') {
    $id  = (int)$_POST['id'];
    $ten_mon = trim($_POST['ten_mon']);
    $mid = 0;
    $st_mon = $db->prepare("SELECT id FROM mon_hoc WHERE ten_mon = ?");
    $st_mon->bind_param("s", $ten_mon);
    $st_mon->execute();
    $res_mon = $st_mon->get_result();
    if ($res_mon->num_rows > 0) {
        $mid = $res_mon->fetch_assoc()['id'];
    } else {
        $st_ins = $db->prepare("INSERT INTO mon_hoc (ten_mon, ma_mon) VALUES (?, 'NEW')");
        $st_ins->bind_param("s", $ten_mon);
        $st_ins->execute();
        $mid = $db->insert_id;
    }
    $gid = (int)$_POST['giang_vien_id'] ?: null;
    $kh  = trim($_POST['khoa'] ?? '');
    $thu = (int)$_POST['thu'];
    $tb  = (int)$_POST['tiet_bat_dau'];
    $tk  = (int)$_POST['tiet_ket_thuc'];
    $ph  = trim($_POST['phong_hoc'] ?? '');
    $hk  = trim($_POST['hoc_ky'] ?? '');
    $nh  = trim($_POST['nam_hoc'] ?? '');
    $st  = $db->prepare("UPDATE thoi_khoa_bieu SET mon_hoc_id=?,giang_vien_id=?,khoa=?,thu=?,tiet_bat_dau=?,tiet_ket_thuc=?,phong_hoc=?,hoc_ky=?,nam_hoc=? WHERE id=?");
    $st->bind_param("iisiissssi",$mid,$gid,$kh,$thu,$tb,$tk,$ph,$hk,$nh,$id);
    if ($st->execute()) $msg='success:Cập nhật thành công!';
    else $msg='error:Lỗi cập nhật!';
}
if ($action === 'delete') {
    $id = (int)$_GET['id'];
    $db->query("DELETE FROM thoi_khoa_bieu WHERE id=$id");
    $msg = 'success:Đã xóa!';
}

$monList = $db->query("SELECT * FROM mon_hoc ORDER BY ten_mon")->fetch_all(MYSQLI_ASSOC);
$gvList  = $db->query("SELECT * FROM giang_vien ORDER BY ho_ten")->fetch_all(MYSQLI_ASSOC);

global $NGANH_LIST;
$fhk = $_GET['hk'] ?? 'HK1'; $fnh = $_GET['nh'] ?? '2026-2027'; $fkhoa = $_GET['khoa'] ?? '';

$sql = "SELECT t.*,m.ten_mon,m.ma_mon,g.ho_ten as ten_gv FROM thoi_khoa_bieu t 
        JOIN mon_hoc m ON t.mon_hoc_id=m.id LEFT JOIN giang_vien g ON t.giang_vien_id=g.id 
        WHERE t.hoc_ky=? AND t.nam_hoc=?";
$params = [$fhk,$fnh]; $types="ss";

if ($fkhoa) {
    $sql .= " AND t.khoa=?";
    $params[] = $fkhoa;
    $types .= "s";
}
$sql .= " ORDER BY t.khoa,t.thu,t.tiet_bat_dau";
$st2 = $db->prepare($sql); $st2->bind_param($types,...$params); $st2->execute();
$list = $st2->get_result()->fetch_all(MYSQLI_ASSOC);

$editRow = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $editRow = $db->query("SELECT * FROM thoi_khoa_bieu WHERE id=$eid")->fetch_assoc();
}

$days = [2=>'Thứ Hai',3=>'Thứ Ba',4=>'Thứ Tư',5=>'Thứ Năm',6=>'Thứ Sáu',7=>'Thứ Bảy',8=>'Chủ Nhật'];
$msgType=$msgText=''; if($msg) [$msgType,$msgText]=explode(':',$msg,2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý TKB</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/admin_nav.php'; ?>
<div class="main-content">
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-calendar-days" style="color:var(--accent)"></i> Quản lý Thời Khóa Biểu</h1></div>
    <button class="btn btn-primary" onclick="toggleModal('addModal')"><i class="fa-solid fa-plus"></i> Thêm lịch</button>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div>
  <?php endif; ?>

  <div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
      <select name="hk" class="form-select" onchange="this.form.submit()">
        <option <?= $fhk=='HK1'?'selected':'' ?>>HK1</option>
        <option <?= $fhk=='HK2'?'selected':'' ?>>HK2</option>
      </select>
      <select name="nh" class="form-select" onchange="this.form.submit()">
        <option <?= $fnh=='2026-2027'?'selected':'' ?>>2026-2027</option>
        <option <?= $fnh=='2025-2026'?'selected':'' ?>>2025-2026</option>
        <option <?= $fnh=='2024-2025'?'selected':'' ?>>2024-2025</option>
        <option <?= $fnh=='2023-2024'?'selected':'' ?>>2023-2024</option>
      </select>
      <select name="khoa" class="form-select" onchange="this.form.submit()">
        <option value="">-- Tất cả ngành/khoa --</option>
        <?php foreach ($NGANH_LIST as $ng): ?>
        <option <?= $fkhoa==$ng?'selected':'' ?>><?= htmlspecialchars($ng) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <span style="color:var(--text2);font-size:13px;align-self:center">Tổng: <b style="color:var(--text)"><?= count($list) ?></b> lịch</span>
  </div>

  <div class="card">
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>#</th><th>Môn học</th><th>Ngành</th><th>Thứ</th><th>Tiết</th><th>Giảng viên</th><th>Phòng</th><th>HK/NH</th><th>Thao tác</th></tr></thead>
        <tbody>
          <?php if(empty($list)): ?>
          <tr><td colspan="9" style="text-align:center;color:var(--text2);padding:30px">Không có dữ liệu</td></tr>
          <?php else: foreach($list as $i=>$r): ?>
          <tr>
            <td style="color:var(--text2)"><?= $i+1 ?></td>
            <td><b><?= htmlspecialchars($r['ten_mon']) ?></b><br><code style="font-size:11px;color:#f43f6d"><?= $r['ma_mon'] ?></code></td>
            <td><?= htmlspecialchars($r['khoa']) ?></td>
            <td><span class="badge" style="background:rgba(245,158,11,0.15);color:#fbbf24"><?= $days[$r['thu']] ?? $r['thu'] ?></span></td>
            <td><?= $r['tiet_bat_dau'] ?> - <?= $r['tiet_ket_thuc'] ?></td>
            <td style="font-size:12px;color:var(--text2)"><?= htmlspecialchars($r['ten_gv'] ?? 'TBA') ?></td>
            <td><span class="badge" style="background:rgba(217, 27, 67, 0.08);color:#d91b43"><?= $r['phong_hoc'] ?></span></td>
            <td style="font-size:12px;color:var(--text2)"><?= $r['hoc_ky'] ?>/<?= $r['nam_hoc'] ?></td>
            <td>
              <a href="?edit_id=<?= $r['id'] ?>&hk=<?= $fhk ?>&nh=<?= $fnh ?>&khoa=<?= urlencode($fkhoa) ?>" class="btn btn-edit btn-sm"><i class="fa-solid fa-pen"></i></a>
              <a href="?action=delete&id=<?= $r['id'] ?>&hk=<?= $fhk ?>&nh=<?= $fnh ?>&khoa=<?= urlencode($fkhoa) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa lịch này?')"><i class="fa-solid fa-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$formFields = function($d=null) use ($monList,$gvList,$days) { 
  // Get subject name if editing
  $d_ten_mon = '';
  if ($d && isset($d['mon_hoc_id'])) {
      foreach ($monList as $m) {
          if ($m['id'] == $d['mon_hoc_id']) { $d_ten_mon = $m['ten_mon']; break; }
      }
  }
?>
<datalist id="monHocList">
  <?php foreach($monList as $m): ?>
  <option value="<?= htmlspecialchars($m['ten_mon']) ?>">
  <?php endforeach; ?>
</datalist>

<div class="form-row">
  <div class="form-group"><label class="form-label">Môn học *</label>
    <input type="text" class="form-input" name="ten_mon" list="monHocList" required placeholder="Nhập tên môn học..." value="<?= htmlspecialchars($d_ten_mon) ?>" autocomplete="off">
  </div>
  <div class="form-group"><label class="form-label">Ngành *</label>
    <select class="form-select" name="khoa" required>
      <option value="">-- Chọn ngành --</option>
      <?php global $NGANH_LIST; foreach($NGANH_LIST as $ng): ?>
      <option value="<?= htmlspecialchars($ng) ?>" <?= ($d&&$d['khoa']==$ng)?'selected':'' ?>><?= htmlspecialchars($ng) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>
<div class="form-row">
  <div class="form-group"><label class="form-label">Giảng viên</label>
    <select class="form-select" name="giang_vien_id">
      <option value="">-- Chọn GV --</option>
      <?php foreach($gvList as $g): ?>
      <option value="<?= $g['id'] ?>" <?= ($d&&$d['giang_vien_id']==$g['id'])?'selected':'' ?>><?= htmlspecialchars($g['ho_ten']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label class="form-label">Thứ *</label>
    <select class="form-select" name="thu" required>
      <?php foreach($days as $n=>$t): ?>
      <option value="<?= $n ?>" <?= ($d&&$d['thu']==$n)?'selected':'' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>
<div class="form-row">
  <div class="form-group"><label class="form-label">Tiết bắt đầu *</label>
    <input class="form-input" name="tiet_bat_dau" type="number" min="1" max="10" required value="<?= $d['tiet_bat_dau']??'' ?>">
  </div>
  <div class="form-group"><label class="form-label">Tiết kết thúc *</label>
    <input class="form-input" name="tiet_ket_thuc" type="number" min="1" max="10" required value="<?= $d['tiet_ket_thuc']??'' ?>">
  </div>
</div>
<div class="form-row">
  <div class="form-group"><label class="form-label">Phòng học</label>
    <input class="form-input" name="phong_hoc" placeholder="A101" value="<?= $d['phong_hoc']??'' ?>">
  </div>
  <div class="form-group"><label class="form-label">Học kỳ</label>
    <select class="form-select" name="hoc_ky">
      <option <?= ($d&&$d['hoc_ky']=='HK1')?'selected':'' ?>>HK1</option>
      <option <?= ($d&&$d['hoc_ky']=='HK2')?'selected':'' ?>>HK2</option>
    </select>
  </div>
</div>
<div class="form-group"><label class="form-label">Năm học</label>
  <input class="form-input" name="nam_hoc" placeholder="2026-2027" value="<?= $d['nam_hoc']??'2026-2027' ?>">
</div>
<?php }; ?>

<!-- Modal Thêm -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-title">Thêm lịch học</div>
    <div class="modal-sub">Thêm tiết học vào thời khóa biểu</div>
    <form method="POST"><input type="hidden" name="action" value="add">
      <?php $formFields(); ?>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('addModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Sửa -->
<?php if ($editRow): ?>
<div class="modal-overlay show">
  <div class="modal-box">
    <div class="modal-title">Sửa lịch học</div>
    <div class="modal-sub">ID: <?= $editRow['id'] ?></div>
    <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= $editRow['id'] ?>">
      <?php $formFields($editRow); ?>
      <div class="modal-footer">
        <a href="/tkb/admin/tkb.php" class="btn btn-ghost">Hủy</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function toggleModal(id){document.getElementById(id).classList.toggle('show');}
</script>
</body>
</html>
