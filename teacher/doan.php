<?php
require_once '../config.php';
requireTeacher();
$db = getDB();
$gv_id = $_SESSION['giang_vien_id'] ?? 0;
$msg = '';

// Tự động kiểm tra và khởi tạo cấu trúc bảng đồ án nếu chưa có
@$db->query("CREATE TABLE IF NOT EXISTS `nhom_do_an` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ten_nhom` varchar(100) NOT NULL,
    `giang_vien_id` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

@$db->query("CREATE TABLE IF NOT EXISTS `nhom_do_an_thanh_vien` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nhom_id` int(11) NOT NULL,
    `sinh_vien_id` int(11) NOT NULL,
    `vai_tro` varchar(50) DEFAULT 'Thành viên',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_sv_nhom` (`nhom_id`,`sinh_vien_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

@$db->query("CREATE TABLE IF NOT EXISTS `do_an` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `giang_vien_id` int(11) NOT NULL,
    `ten_do_an` varchar(255) NOT NULL,
    `sinh_vien_id` int(11) DEFAULT NULL,
    `nhom_id` int(11) DEFAULT NULL,
    `trang_thai` varchar(50) DEFAULT 'Chưa bắt đầu',
    `diem` decimal(4,2) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `link_nop_bai` varchar(255) DEFAULT NULL,
    `file_nop_bai` varchar(255) DEFAULT NULL,
    `nhan_xet` text DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ===================== THÊM ĐỒ ÁN (cá nhân hoặc nhóm) =====================
if ($action === 'add') {
    $title   = trim($_POST['ten_do_an'] ?? '');
    $loai    = $_POST['loai'] ?? 'ca_nhan'; // ca_nhan | nhom
    $sv_ids  = $_POST['sinh_vien_ids'] ?? [];  // array
    $ten_nhom = trim($_POST['ten_nhom'] ?? '');

    if (!$title || empty($sv_ids)) {
        $msg = "error:Vui lòng nhập tên đề tài và chọn ít nhất một sinh viên.";
    } else {
        $db->begin_transaction();
        try {
            if ($loai === 'nhom' && count($sv_ids) > 1) {
                // Tạo nhóm
                $tn = $ten_nhom ?: ('Nhóm ' . count($sv_ids) . ' SV - ' . $title);
                $stmt_n = $db->prepare("INSERT INTO nhom_do_an (ten_nhom, giang_vien_id) VALUES (?, ?)");
                $stmt_n->bind_param("si", $tn, $gv_id);
                $stmt_n->execute();
                $nhom_id = $db->insert_id;

                // Thêm thành viên nhóm
                $stmt_tv = $db->prepare("INSERT INTO nhom_do_an_thanh_vien (nhom_id, sinh_vien_id, vai_tro) VALUES (?, ?, ?)");
                foreach ($sv_ids as $idx => $sv_id) {
                    $sv_id = (int)$sv_id;
                    $vai_tro = ($idx === 0) ? 'Nhóm trưởng' : 'Thành viên';
                    $stmt_tv->bind_param("iis", $nhom_id, $sv_id, $vai_tro);
                    $stmt_tv->execute();
                }

                // Tạo 1 do_an cho nhóm (sv_id là nhóm trưởng)
                $sv_truong = (int)$sv_ids[0];
                $null_sv = null;
                $stmt_da = $db->prepare("INSERT INTO do_an (giang_vien_id, ten_do_an, sinh_vien_id, nhom_id) VALUES (?, ?, ?, ?)");
                $stmt_da->bind_param("isii", $gv_id, $title, $sv_truong, $nhom_id);
                $stmt_da->execute();
                $msg = "success:Phân công đề tài nhóm thành công! Nhóm: " . htmlspecialchars($tn);
            } else {
                // Cá nhân — tạo 1 bản ghi mỗi SV
                $stmt_da = $db->prepare("INSERT INTO do_an (giang_vien_id, ten_do_an, sinh_vien_id) VALUES (?, ?, ?)");
                foreach ($sv_ids as $sv_id) {
                    $sv_id = (int)$sv_id;
                    $stmt_da->bind_param("isi", $gv_id, $title, $sv_id);
                    $stmt_da->execute();
                }
                $msg = "success:Phân công đề tài cá nhân thành công cho " . count($sv_ids) . " sinh viên!";
            }
            writeSystemLog("Phân công đề tài: $title");
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            $msg = "error:Lỗi: " . $e->getMessage();
        }
    }
}

// ===================== CẬP NHẬT / CHẤM ĐIỂM =====================
if ($action === 'update_status') {
    $da_id   = (int)$_POST['do_an_id'];
    $status  = trim($_POST['trang_thai'] ?? 'Chưa bắt đầu');
    $diem    = ($_POST['diem'] !== '') ? (float)$_POST['diem'] : null;
    $nhan_xet = trim($_POST['nhan_xet'] ?? '');

    if (isAdmin()) {
        $stmt = $db->prepare("UPDATE do_an SET trang_thai=?, diem=?, nhan_xet=? WHERE id=?");
        $stmt->bind_param("sdsi", $status, $diem, $nhan_xet, $da_id);
    } else {
        $stmt = $db->prepare("UPDATE do_an SET trang_thai=?, diem=?, nhan_xet=? WHERE id=? AND giang_vien_id=?");
        $stmt->bind_param("sdsii", $status, $diem, $nhan_xet, $da_id, $gv_id);
    }
    if ($stmt->execute()) {
        $msg = "success:Cập nhật tiến độ & chấm điểm đồ án thành công!";
    } else {
        $msg = "error:Lỗi cập nhật: " . $db->error;
    }
}

// ===================== XÓA =====================
if ($action === 'delete') {
    $da_id = (int)$_GET['do_an_id'];
    // Lấy nhom_id để xóa nhóm nếu có
    if (isAdmin()) {
        $row = $db->query("SELECT nhom_id FROM do_an WHERE id=$da_id")->fetch_assoc();
        $stmt = $db->prepare("DELETE FROM do_an WHERE id=?");
        $stmt->bind_param("i", $da_id);
    } else {
        $row = $db->query("SELECT nhom_id FROM do_an WHERE id=$da_id AND giang_vien_id=$gv_id")->fetch_assoc();
        $stmt = $db->prepare("DELETE FROM do_an WHERE id=? AND giang_vien_id=?");
        $stmt->bind_param("ii", $da_id, $gv_id);
    }
    if ($stmt->execute()) {
        // Xóa nhóm nếu có (CASCADE xóa thành viên)
        if (!empty($row['nhom_id'])) {
            if (isAdmin()) {
                $db->query("DELETE FROM nhom_do_an WHERE id={$row['nhom_id']}");
            } else {
                $db->query("DELETE FROM nhom_do_an WHERE id={$row['nhom_id']} AND giang_vien_id=$gv_id");
            }
        }
        $msg = "success:Đã xóa đồ án thành công!";
    }
}

// ===================== LẤY DỮ LIỆU =====================
// Lấy khoa giảng viên
$st_t = $db->prepare("SELECT khoa FROM giang_vien WHERE id=?");
$st_t->bind_param("i", $gv_id);
$st_t->execute();
$teacher_khoa = $st_t->get_result()->fetch_assoc()['khoa'] ?? '';

// Danh sách SV (để chọn trong form)
if ($teacher_khoa) {
    $stmt_sv = $db->prepare("SELECT id, ma_sv, ho_ten, lop FROM students WHERE LOWER(khoa)=LOWER(?) ORDER BY ho_ten");
    $stmt_sv->bind_param("s", $teacher_khoa);
    $stmt_sv->execute();
    $students = $stmt_sv->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $students = $db->query("SELECT id, ma_sv, ho_ten, lop FROM students ORDER BY ho_ten")->fetch_all(MYSQLI_ASSOC);
}

// Danh sách đồ án của GV (kèm thành viên nhóm nếu có)
$projects = [];
if (isAdmin()) {
    $res_proj = $db->query("
        SELECT d.*, s.ho_ten as sv_name, s.ma_sv, s.lop,
               n.ten_nhom, g.ho_ten as ten_giang_vien
        FROM do_an d
        JOIN students s ON d.sinh_vien_id = s.id
        LEFT JOIN nhom_do_an n ON d.nhom_id = n.id
        LEFT JOIN giang_vien g ON d.giang_vien_id = g.id
        ORDER BY d.id DESC
    ");
    $projects = $res_proj ? $res_proj->fetch_all(MYSQLI_ASSOC) : [];
} else {
    $stmt_proj = $db->prepare("
        SELECT d.*, s.ho_ten as sv_name, s.ma_sv, s.lop,
               n.ten_nhom
        FROM do_an d
        JOIN students s ON d.sinh_vien_id = s.id
        LEFT JOIN nhom_do_an n ON d.nhom_id = n.id
        WHERE d.giang_vien_id = ?
        ORDER BY d.id DESC
    ");
    $stmt_proj->bind_param("i", $gv_id);
    $stmt_proj->execute();
    $projects = $stmt_proj->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Lấy thành viên nhóm cho từng đồ án nhóm
$nhom_members = [];
foreach ($projects as $pr) {
    if (!empty($pr['nhom_id'])) {
        $nid = (int)$pr['nhom_id'];
        if (!isset($nhom_members[$nid])) {
            $res_m = $db->query("
                SELECT s.ho_ten, s.ma_sv, tv.vai_tro
                FROM nhom_do_an_thanh_vien tv
                JOIN students s ON tv.sinh_vien_id = s.id
                WHERE tv.nhom_id = $nid
                ORDER BY tv.id ASC
            ");
            $nhom_members[$nid] = $res_m->fetch_all(MYSQLI_ASSOC);
        }
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Đồ Án - Giảng viên</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
<style>
.form-control { background:var(--bg3); border:1px solid var(--border); color:var(--text); padding:10px 15px; border-radius:8px; width:100%; font-size:14px; outline:none; margin-bottom:12px; transition:border 0.2s; }
.form-control:focus { border-color:var(--accent); }
.loai-tabs { display:flex; gap:8px; margin-bottom:16px; }
.loai-tab { flex:1; padding:10px; border:2px solid var(--border); border-radius:10px; background:var(--bg2); cursor:pointer; text-align:center; font-weight:600; font-size:13px; color:var(--text2); transition:all 0.2s; }
.loai-tab.active { border-color:var(--accent); background:rgba(217,27,67,0.06); color:var(--accent); }
.sv-checklist { max-height:220px; overflow-y:auto; border:1px solid var(--border); border-radius:8px; background:var(--bg3); padding:8px; }
.sv-check-item { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:6px; cursor:pointer; transition:background 0.15s; }
.sv-check-item:hover { background:rgba(217,27,67,0.05); }
.sv-check-item input { accent-color:var(--accent); width:16px; height:16px; cursor:pointer; }
.member-tag { display:inline-flex; align-items:center; gap:5px; background:rgba(217,27,67,0.07); border:1px solid rgba(217,27,67,0.15); color:var(--accent); border-radius:20px; padding:3px 10px; font-size:11.5px; font-weight:600; margin:2px; }
.member-tag.lead { background:rgba(234,179,8,0.1); border-color:rgba(234,179,8,0.3); color:#ca8a04; }
.nhom-badge { background:rgba(99,102,241,0.1); color:#6366f1; border:1px solid rgba(99,102,241,0.2); border-radius:20px; padding:2px 10px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:5px; }
.btn-submit { background:var(--accent); color:#fff; border:none; padding:11px 22px; border-radius:9px; font-weight:700; font-size:14px; cursor:pointer; transition:0.15s; display:inline-flex; align-items:center; gap:8px; width:100%; justify-content:center; }
.btn-submit:hover { opacity:0.9; transform:translateY(-1px); }
.search-sv { width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:7px; background:var(--bg2); color:var(--text); font-size:13px; outline:none; margin-bottom:8px; }
</style>
</head>
<body class="<?= isAdmin() ? 'admin-portal' : '' ?>">
<?php if (isAdmin()): ?>
    <?php include '../includes/admin_nav.php'; ?>
<?php else: ?>
    <?php include '../includes/teacher_nav.php'; ?>
<?php endif; ?>

<div class="main-content">
<div class="content-pad" style="max-width: 1400px; margin: 0 auto; width: 100%;">

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-folder-open" style="color:var(--accent)"></i> Hướng Dẫn Đồ Án</h1>
        <p style="color:var(--text2); margin-top:5px;">Phân công đề tài cá nhân hoặc theo nhóm, theo dõi tiến độ và chấm điểm</p>
    </div>
</div>

<?php if ($msg):
    $parts = explode(':', $msg, 2);
    $type = $parts[0]; $text = $parts[1] ?? '';
?>
<div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>" style="display:block; margin-bottom:20px;"><?= htmlspecialchars($text) ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:360px 1fr; gap:28px; align-items:start;">
    <!-- ===== FORM PHÂN CÔNG ===== -->
    <div class="card">
        <div class="card-head">
            <span class="card-title"><i class="fa-solid fa-file-signature"></i> Phân công đề tài mới</span>
        </div>
        <div class="card-body">
            <form method="POST" action="?action=add" id="addForm">

                <div class="form-group">
                    <label class="form-label">Tên đề tài *</label>
                    <input type="text" name="ten_do_an" class="form-control" placeholder="Ví dụ: Xây dựng Website Quản lý đào tạo" required>
                </div>

                <div class="form-group">
                    <label class="form-label" style="margin-bottom:8px; display:block;">Hình thức thực hiện</label>
                    <div class="loai-tabs">
                        <button type="button" class="loai-tab active" id="tabCaNhan" onclick="switchLoai('ca_nhan')">
                            <i class="fa-solid fa-user" style="margin-right:5px;"></i>Cá nhân
                        </button>
                        <button type="button" class="loai-tab" id="tabNhom" onclick="switchLoai('nhom')">
                            <i class="fa-solid fa-users" style="margin-right:5px;"></i>Theo nhóm
                        </button>
                    </div>
                    <input type="hidden" name="loai" id="inputLoai" value="ca_nhan">
                </div>

                <!-- Tên nhóm (chỉ hiện khi chọn nhóm) -->
                <div class="form-group" id="tenNhomWrap" style="display:none;">
                    <label class="form-label">Tên nhóm (tùy chọn)</label>
                    <input type="text" name="ten_nhom" class="form-control" placeholder="Ví dụ: Nhóm 1 - CNTT24A">
                </div>

                <div class="form-group">
                    <label class="form-label" id="labelSv">Chọn sinh viên thực hiện *</label>
                    <p id="hintSv" style="font-size:12px; color:var(--text2); margin-bottom:8px; display:none;">Sinh viên đầu tiên được chọn sẽ là <strong>Nhóm trưởng</strong>.</p>
                    <input type="text" class="search-sv" id="searchSv" placeholder="🔍 Tìm sinh viên..." oninput="filterSv(this.value)">
                    <div class="sv-checklist" id="svList">
                        <?php foreach ($students as $sv): ?>
                        <label class="sv-check-item" data-name="<?= strtolower(htmlspecialchars($sv['ho_ten'])) ?> <?= strtolower($sv['ma_sv']) ?>">
                            <input type="checkbox" name="sinh_vien_ids[]" value="<?= $sv['id'] ?>" onchange="onSvChange()">
                            <div>
                                <div style="font-weight:600; font-size:13px;"><?= htmlspecialchars($sv['ho_ten']) ?></div>
                                <div style="font-size:11px; color:var(--text2);"><?= htmlspecialchars($sv['ma_sv']) ?> | <?= htmlspecialchars($sv['lop']) ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div id="selectedCount" style="font-size:12px; color:var(--text2); margin-top:6px;"></div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> Giao đề tài
                </button>
            </form>
        </div>
    </div>

    <!-- ===== DANH SÁCH ĐỒ ÁN ===== -->
    <div class="card">
        <div class="card-head">
            <span class="card-title"><i class="fa-solid fa-list-check"></i> Đồ án đang hướng dẫn (<?= count($projects) ?>)</span>
        </div>
        <div class="card-body" style="padding:0;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:rgba(0,0,0,0.02);">
                        <th style="padding:14px 15px; text-align:left;">Đề tài & Thành viên</th>
                        <th style="padding:14px 15px; text-align:left;">Bài nộp</th>
                        <th style="padding:14px 15px; text-align:center;">Trạng thái</th>
                        <th style="padding:14px 15px; text-align:center;">Điểm</th>
                        <th style="padding:14px 15px; text-align:center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($projects)): ?>
                    <tr><td colspan="5" style="text-align:center; color:var(--text2); padding:40px;">Bạn chưa nhận hướng dẫn đồ án nào.</td></tr>
                <?php else: foreach ($projects as $pr):
                    $is_nhom = !empty($pr['nhom_id']);
                    $members = $is_nhom ? ($nhom_members[$pr['nhom_id']] ?? []) : [];
                    $col = '#64748b';
                    if ($pr['trang_thai'] === 'Đang thực hiện') $col = '#3b82f6';
                    elseif ($pr['trang_thai'] === 'Hoàn thành') $col = '#f59e0b';
                    elseif ($pr['trang_thai'] === 'Đã nghiệm thu') $col = '#10b981';
                ?>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:15px;">
                            <div style="font-weight:700; color:var(--text); font-size:14px; margin-bottom:6px;">
                                <?= htmlspecialchars($pr['ten_do_an']) ?>
                            </div>
                            <?php if ($is_nhom): ?>
                                <div style="margin-bottom:6px;">
                                    <span class="nhom-badge"><i class="fa-solid fa-users"></i> <?= htmlspecialchars($pr['ten_nhom'] ?: 'Nhóm') ?></span>
                                </div>
                                <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:4px;">
                                    <?php foreach ($members as $m): ?>
                                        <span class="member-tag <?= $m['vai_tro'] === 'Nhóm trưởng' ? 'lead' : '' ?>">
                                            <?= $m['vai_tro'] === 'Nhóm trưởng' ? '<i class="fa-solid fa-crown"></i>' : '<i class="fa-solid fa-user"></i>' ?>
                                            <?= htmlspecialchars($m['ho_ten']) ?>
                                            <span style="opacity:0.65;">(<?= htmlspecialchars($m['ma_sv']) ?>)</span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div style="font-size:12px; color:var(--text2);">
                                    <i class="fa-solid fa-user" style="color:var(--accent);"></i>
                                    <strong style="color:var(--accent)"><?= htmlspecialchars($pr['sv_name']) ?></strong>
                                    (<?= htmlspecialchars($pr['ma_sv']) ?>) | Lớp: <?= htmlspecialchars($pr['lop']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:15px; color:var(--text2); font-size:12.5px;">
                            <?php if (!empty($pr['file_nop_bai'])): ?>
                                <a href="<?= htmlspecialchars($pr['file_nop_bai']) ?>" target="_blank" style="color:var(--accent); font-weight:700;"><i class="fa-solid fa-file-arrow-down"></i> Tải báo cáo</a><br>
                            <?php endif; ?>
                            <?php if (!empty($pr['link_nop_bai'])): ?>
                                <a href="<?= htmlspecialchars($pr['link_nop_bai']) ?>" target="_blank" style="color:#3b82f6; font-weight:700;"><i class="fa-solid fa-link"></i> Link Repo/Drive</a>
                            <?php endif; ?>
                            <?php if (empty($pr['file_nop_bai']) && empty($pr['link_nop_bai'])): ?>
                                <span style="color:#aaa;">Chưa nộp</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:15px; text-align:center;">
                            <span class="badge" style="background:<?= $col ?>1d; color:<?= $col ?>; border:1px solid <?= $col ?>33; font-weight:700; font-size:11px;">
                                <?= htmlspecialchars($pr['trang_thai']) ?>
                            </span>
                        </td>
                        <td style="padding:15px; text-align:center; font-weight:800; font-size:15px; color:<?= $pr['diem'] !== null ? '#10b981' : 'var(--text2)' ?>;">
                            <?= $pr['diem'] !== null ? number_format($pr['diem'], 1) : '—' ?>
                        </td>
                        <td style="padding:15px; text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <button class="btn-ghost" style="padding:5px 10px; font-size:11px; border-radius:6px;"
                                    onclick="openReview(<?= $pr['id'] ?>, '<?= addslashes(htmlspecialchars($pr['ten_do_an'])) ?>', '<?= addslashes($is_nhom ? ($pr['ten_nhom'] ?: 'Nhóm') : $pr['sv_name']) ?>', '<?= addslashes($pr['trang_thai']) ?>', '<?= $pr['diem'] ?? '' ?>', '<?= addslashes($pr['nhan_xet'] ?? '') ?>', <?= $is_nhom ? 'true' : 'false' ?>)">
                                    <i class="fa-solid fa-graduation-cap"></i> Đánh giá
                                </button>
                                <a href="?action=delete&do_an_id=<?= $pr['id'] ?>" onclick="return confirm('Xóa đồ án này?')" class="btn-ghost" style="color:#ef4444; border-color:rgba(239,68,68,0.15); padding:5px 8px; font-size:11px;"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== MODAL ĐÁNH GIÁ ===== -->
<div class="modal-overlay" id="reviewModal" onclick="if(event.target==this) closeReview()">
    <div class="modal-box" style="width:520px; max-width:95vw;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; padding-bottom:14px; border-bottom:1px solid rgba(217,27,67,0.15);">
            <h3 class="modal-title" style="margin:0;"><i class="fa-solid fa-check-double" style="color:var(--accent);"></i> Đánh giá & Chấm điểm</h3>
            <button onclick="closeReview()" style="background:none; border:none; color:var(--text2); font-size:20px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="?action=update_status">
            <input type="hidden" name="do_an_id" id="rev_id">
            <div class="form-group">
                <label class="form-label">Đề tài</label>
                <input type="text" id="rev_title" class="form-control" readonly style="background:var(--bg3); opacity:0.75;">
            </div>
            <div class="form-group">
                <label class="form-label" id="rev_sv_label">Sinh viên / Nhóm</label>
                <input type="text" id="rev_sv" class="form-control" readonly style="background:var(--bg3); opacity:0.75;">
            </div>
            <div class="form-group">
                <label class="form-label">Trạng thái tiến độ</label>
                <select name="trang_thai" id="rev_status" class="form-control" style="cursor:pointer;">
                    <option value="Chưa bắt đầu">Chưa bắt đầu</option>
                    <option value="Đang thực hiện">Đang thực hiện</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Đã nghiệm thu">Đã nghiệm thu</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Điểm số (0 – 10)</label>
                <input type="number" step="0.1" min="0" max="10" name="diem" id="rev_score" class="form-control" placeholder="Nhập điểm nếu có...">
            </div>
            <div class="form-group">
                <label class="form-label">Nhận xét / Hướng dẫn</label>
                <textarea name="nhan_xet" id="rev_feedback" class="form-control" rows="4" placeholder="Nhập nhận xét của giáo viên hướng dẫn..."></textarea>
            </div>
            <button type="submit" class="btn-submit"><i class="fa-solid fa-floppy-disk"></i> Lưu đánh giá</button>
        </form>
    </div>
</div>

<script>
// ===== LOẠI TAB (Cá nhân / Nhóm) =====
function switchLoai(loai) {
    document.getElementById('inputLoai').value = loai;
    document.getElementById('tabCaNhan').classList.toggle('active', loai === 'ca_nhan');
    document.getElementById('tabNhom').classList.toggle('active', loai === 'nhom');
    document.getElementById('tenNhomWrap').style.display = loai === 'nhom' ? 'block' : 'none';
    document.getElementById('hintSv').style.display = loai === 'nhom' ? 'block' : 'none';
    document.getElementById('labelSv').textContent = loai === 'nhom'
        ? 'Chọn các thành viên nhóm *'
        : 'Chọn sinh viên thực hiện *';
    // Reset checkboxes sang radio-like khi cá nhân
    const checks = document.querySelectorAll('#svList input[name="sinh_vien_ids[]"]');
    checks.forEach(cb => {
        if (loai === 'ca_nhan') cb.type = 'radio';
        else cb.type = 'checkbox';
    });
    onSvChange();
}

// Đếm SV đã chọn
function onSvChange() {
    const selected = document.querySelectorAll('#svList input:checked').length;
    const loai = document.getElementById('inputLoai').value;
    const el = document.getElementById('selectedCount');
    if (selected === 0) { el.textContent = ''; return; }
    el.textContent = `✔ Đã chọn ${selected} sinh viên${loai === 'nhom' ? ' (người đầu tiên tick = Nhóm trưởng)' : ''}`;
    el.style.color = selected > 0 ? 'var(--accent)' : 'var(--text2)';
}

// Tìm kiếm SV
function filterSv(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.sv-check-item').forEach(item => {
        item.style.display = item.dataset.name.includes(q) ? 'flex' : 'none';
    });
}

// ===== MODAL ĐÁNH GIÁ =====
function openReview(id, title, svOrGroup, status, score, feedback, isNhom) {
    document.getElementById('rev_id').value = id;
    document.getElementById('rev_title').value = title;
    document.getElementById('rev_sv').value = svOrGroup;
    document.getElementById('rev_sv_label').textContent = isNhom ? 'Nhóm thực hiện' : 'Sinh viên';
    document.getElementById('rev_status').value = status;
    document.getElementById('rev_score').value = score;
    document.getElementById('rev_feedback').value = feedback;
    document.getElementById('reviewModal').classList.add('show');
}
function closeReview() {
    document.getElementById('reviewModal').classList.remove('show');
}
</script>

</div><!-- /content-pad -->
</div><!-- /main-content -->

</body>
</html>
