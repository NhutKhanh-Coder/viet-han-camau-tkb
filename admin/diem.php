<?php
require_once '../config.php';
requireAdmin();
$db  = getDB();
$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'save') {
    $sid = (int)$_POST['student_id'];
    $mid = (int)$_POST['mon_hoc_id'];
    $hk  = trim($_POST['hoc_ky'] ?? '');
    $nh  = trim($_POST['nam_hoc'] ?? '');
    $gk  = $_POST['diem_giua_ky'] !== '' ? (float)$_POST['diem_giua_ky'] : null;
    $ck  = $_POST['diem_cuoi_ky'] !== '' ? (float)$_POST['diem_cuoi_ky'] : null;
    $tk  = ($gk !== null && $ck !== null) ? round($gk * 0.3 + $ck * 0.7, 2) : null;
    $gc  = trim($_POST['ghi_chu'] ?? '');
    $st  = $db->prepare("INSERT INTO diem (student_id,mon_hoc_id,hoc_ky,nam_hoc,diem_giua_ky,diem_cuoi_ky,diem_tong_ket,ghi_chu)
                         VALUES(?,?,?,?,?,?,?,?)
                         ON DUPLICATE KEY UPDATE diem_giua_ky=?,diem_cuoi_ky=?,diem_tong_ket=?,ghi_chu=?");
    $st->bind_param("iissdddsddds",$sid,$mid,$hk,$nh,$gk,$ck,$tk,$gc,$gk,$ck,$tk,$gc);
    if ($st->execute()) $msg='success:Đã lưu điểm thành công!';
    else $msg='error:Lỗi lưu điểm: '.$db->error;
}
if ($action === 'delete') {
    $id = (int)$_GET['id'];
    $db->query("DELETE FROM diem WHERE id=$id");
    $msg = 'success:Đã xóa điểm!';
}

global $NGANH_LIST;
$fkhoa = $_GET['khoa'] ?? '';
$flop  = $_GET['lop']  ?? '';
$fsv   = $_GET['sv']   ?? ''; 
$fhk   = $_GET['hk']   ?? ''; 
$fnh   = $_GET['nh']   ?? '';

$lopList = [];
if ($fkhoa) {
    $stLop = $db->prepare("SELECT DISTINCT lop FROM students WHERE khoa = ? ORDER BY lop");
    $stLop->bind_param("s", $fkhoa);
    $stLop->execute();
    $lopList = $stLop->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $lopList = $db->query("SELECT DISTINCT lop FROM students ORDER BY lop")->fetch_all(MYSQLI_ASSOC);
}

$svSql = "SELECT id,ma_sv,ho_ten,lop FROM students WHERE 1=1";
$svParams = []; $svTypes = "";
if ($flop) {
    $svSql .= " AND lop=?"; $svParams[] = $flop; $svTypes .= "s";
} else if ($fkhoa) {
    $svSql .= " AND khoa=?"; $svParams[] = $fkhoa; $svTypes .= "s";
}
$svSql .= " ORDER BY lop, ho_ten";
if (!empty($svParams)) {
    $stSv = $db->prepare($svSql); $stSv->bind_param($svTypes, ...$svParams); $stSv->execute();
    $svList = $stSv->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $svList = $db->query($svSql)->fetch_all(MYSQLI_ASSOC);
}

$monList = $db->query("SELECT * FROM mon_hoc ORDER BY ten_mon")->fetch_all(MYSQLI_ASSOC);

$sql  = "SELECT d.*,s.ma_sv,s.ho_ten,s.lop,s.khoa,m.ten_mon,m.ma_mon FROM diem d 
         JOIN students s ON d.student_id=s.id JOIN mon_hoc m ON d.mon_hoc_id=m.id WHERE 1=1";

if ($fsv) { 
    $sql.=" AND d.student_id=" . (int)$fsv; 
} else {
    if ($flop) {
        $sql .= " AND s.lop='" . $db->real_escape_string($flop) . "'";
    } else if ($fkhoa) {
        $sql .= " AND s.khoa='" . $db->real_escape_string($fkhoa) . "'";
    }
}
if ($fhk) { $sql.=" AND d.hoc_ky='".addslashes($fhk)."'"; }
if ($fnh) { $sql.=" AND d.nam_hoc='".addslashes($fnh)."'"; }
$sql .= " ORDER BY s.lop,s.ho_ten,m.ten_mon";
$list = $db->query($sql)->fetch_all(MYSQLI_ASSOC);

function dBg($d) {
    if($d===null) return 'color:var(--text2)';
    if($d>=8.5) return 'color:#c084fc';
    if($d>=7.0) return 'color:#f43f6d';
    if($d>=5.0) return 'color:#4ade80';
    return 'color:#f87171';
}

$msgType=$msgText=''; if($msg) [$msgType,$msgText]=explode(':',$msg,2);
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Điểm</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/admin_nav.php'; ?>
<div class="main-content">
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-star-half-stroke" style="color:var(--accent)"></i> Quản lý Điểm</h1></div>
    <button class="btn btn-primary" onclick="toggleModal('addModal')"><i class="fa-solid fa-plus"></i> Nhập điểm</button>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div>
  <?php endif; ?>

  <div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
      <select name="khoa" class="form-select" onchange="this.form.lop.value=''; this.form.sv.value=''; this.form.submit()">
        <option value="">-- Tất cả ngành --</option>
        <?php foreach ($NGANH_LIST as $ng): ?>
        <option <?= $fkhoa==$ng?'selected':'' ?>><?= htmlspecialchars($ng) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="lop" class="form-select" onchange="this.form.sv.value=''; this.form.submit()">
        <option value="">-- Tất cả lớp --</option>
        <?php foreach ($lopList as $l): ?>
        <option <?= $flop==$l['lop']?'selected':'' ?>><?= htmlspecialchars($l['lop']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="sv" class="form-select" onchange="this.form.submit()">
        <option value="">-- Tất cả Sinh viên --</option>
        <?php foreach($svList as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $fsv==$s['id']?'selected':'' ?>>[<?= $s['lop'] ?>] <?= htmlspecialchars($s['ho_ten']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="hk" class="form-select" onchange="this.form.submit()">
        <option value="">-- HK --</option>
        <option <?= $fhk=='HK1'?'selected':'' ?>>HK1</option>
        <option <?= $fhk=='HK2'?'selected':'' ?>>HK2</option>
      </select>
      <select name="nh" class="form-select" onchange="this.form.submit()">
        <option value="">-- Năm học --</option>
        <option <?= $fnh=='2024-2025'?'selected':'' ?>>2024-2025</option>
        <option <?= $fnh=='2023-2024'?'selected':'' ?>>2023-2024</option>
      </select>
    </form>
    <span style="color:var(--text2);font-size:13px;align-self:center">Tổng: <b style="color:var(--text)"><?= count($list) ?></b> bản ghi</span>
  </div>

  <div class="card">
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>#</th><th>Sinh viên</th><th>Lớp</th><th>Môn học</th><th>HK/NH</th><th style="text-align:center">GK</th><th style="text-align:center">CK</th><th style="text-align:center">Tổng kết</th><th>Ghi chú</th><th>Thao tác</th></tr></thead>
        <tbody>
          <?php if(empty($list)): ?>
          <tr><td colspan="10" style="text-align:center;color:var(--text2);padding:30px">Không có dữ liệu</td></tr>
          <?php else: foreach($list as $i=>$d): ?>
          <tr>
            <td style="color:var(--text2)"><?= $i+1 ?></td>
            <td><b><?= htmlspecialchars($d['ho_ten']) ?></b><br><code style="font-size:11px;color:#f43f6d"><?= $d['ma_sv'] ?></code></td>
            <td><?= $d['lop'] ?></td>
            <td><?= htmlspecialchars($d['ten_mon']) ?><br><code style="font-size:11px;color:var(--text2)"><?= $d['ma_mon'] ?></code></td>
            <td style="font-size:12px;color:var(--text2)"><?= $d['hoc_ky'] ?>/<?= $d['nam_hoc'] ?></td>
            <td style="text-align:center;font-weight:600;<?= dBg($d['diem_giua_ky']) ?>"><?= $d['diem_giua_ky'] ?? '-' ?></td>
            <td style="text-align:center;font-weight:600;<?= dBg($d['diem_cuoi_ky']) ?>"><?= $d['diem_cuoi_ky'] ?? '-' ?></td>
            <td style="text-align:center;font-weight:700;font-size:15px;<?= dBg($d['diem_tong_ket']) ?>"><?= $d['diem_tong_ket'] ?? '-' ?></td>
            <td style="font-size:12px;color:var(--text2)"><?= $d['ghi_chu'] ?: '-' ?></td>
            <td>
              <button class="btn btn-edit btn-sm" onclick="editDiem(<?= htmlspecialchars(json_encode($d)) ?>)"><i class="fa-solid fa-pen"></i></button>
              <a href="?action=delete&id=<?= $d['id'] ?>&sv=<?= $fsv ?>&hk=<?= $fhk ?>&nh=<?= $fnh ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa điểm?')"><i class="fa-solid fa-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Nhập/Sửa điểm -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-title" id="modalTitle">Nhập điểm</div>
    <div class="modal-sub">Điểm tổng kết = GK×30% + CK×70%</div>
    <form method="POST">
      <input type="hidden" name="action" value="save">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Sinh viên *</label>
          <select class="form-select" name="student_id" id="selSV" required>
            <option value="">-- Chọn SV --</option>
            <?php foreach($svList as $s): ?>
            <option value="<?= $s['id'] ?>">[<?= $s['lop'] ?>] <?= htmlspecialchars($s['ho_ten']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Môn học *</label>
          <select class="form-select" name="mon_hoc_id" id="selMon" required>
            <option value="">-- Chọn môn --</option>
            <?php foreach($monList as $m): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['ten_mon']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Học kỳ</label>
          <select class="form-select" name="hoc_ky" id="selHK">
            <option>HK1</option><option>HK2</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Năm học</label>
          <input class="form-input" name="nam_hoc" id="inpNH" placeholder="2024-2025" value="2024-2025">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Điểm giữa kỳ (0-10)</label>
          <input class="form-input" name="diem_giua_ky" id="inpGK" type="number" step="0.1" min="0" max="10" placeholder="--">
        </div>
        <div class="form-group"><label class="form-label">Điểm cuối kỳ (0-10)</label>
          <input class="form-input" name="diem_cuoi_ky" id="inpCK" type="number" step="0.1" min="0" max="10" placeholder="--">
        </div>
      </div>
      <div class="form-group"><label class="form-label">Ghi chú</label>
        <input class="form-input" name="ghi_chu" id="inpGC" placeholder="Ghi chú...">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('addModal')">Hủy</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu điểm</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id){document.getElementById(id).classList.toggle('show');}
function editDiem(d) {
  document.getElementById('modalTitle').textContent = 'Sửa điểm - ' + d.ho_ten;
  document.getElementById('selSV').value  = d.student_id;
  document.getElementById('selMon').value = d.mon_hoc_id;
  document.getElementById('selHK').value  = d.hoc_ky;
  document.getElementById('inpNH').value  = d.nam_hoc;
  document.getElementById('inpGK').value  = d.diem_giua_ky ?? '';
  document.getElementById('inpCK').value  = d.diem_cuoi_ky ?? '';
  document.getElementById('inpGC').value  = d.ghi_chu ?? '';
  toggleModal('addModal');
}
</script>
</body>
</html>
