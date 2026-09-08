<?php
require_once '../config.php';
requireStudent();
$db = getDB();
$sv_id = $_SESSION['student_id'] ?? 0;

// Tự động kiểm tra và khởi tạo bảng nếu chưa có
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

$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'submit_project') {
    $da_id = (int)$_POST['do_an_id'];
    $link = trim($_POST['link_nop_bai'] ?? '');
    $status = trim($_POST['trang_thai'] ?? 'Đang thực hiện');
    
    $file_url = '';
    if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/projects/';
        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);
        $filename = time() . '_project_' . basename($_FILES['project_file']['name']);
        if (move_uploaded_file($_FILES['project_file']['tmp_name'], $upload_dir . $filename)) {
            $file_url = '/tkb/assets/uploads/projects/' . $filename;
        } else {
            $msg = "error:Lỗi không thể tải lên file sản phẩm.";
        }
    }
    
    if (!$msg) {
        // Kiểm tra quyền: hảy cả sinh_vien_id trực tiếp lẫn thành viên nhóm
        $stmt_chk = $db->prepare("
            SELECT d.id FROM do_an d
            WHERE d.id = ? AND (
                d.sinh_vien_id = ?
                OR d.nhom_id IN (
                    SELECT nhom_id FROM nhom_do_an_thanh_vien WHERE sinh_vien_id = ?
                )
            )
        ");
        $stmt_chk->bind_param("iii", $da_id, $sv_id, $sv_id);
        $stmt_chk->execute();
        
        if ($stmt_chk->get_result()->num_rows > 0) {
            if ($file_url !== '') {
                $stmt_up = $db->prepare("UPDATE do_an SET link_nop_bai=?, file_nop_bai=?, trang_thai=? WHERE id=?");
                $stmt_up->bind_param("sssi", $link, $file_url, $status, $da_id);
            } else {
                $stmt_up = $db->prepare("UPDATE do_an SET link_nop_bai=?, trang_thai=? WHERE id=?");
                $stmt_up->bind_param("ssi", $link, $status, $da_id);
            }
            if ($stmt_up->execute()) {
                $msg = "success:Nộp sản phẩm đồ án và cập nhật trạng thái thành công!";
            } else {
                $msg = "error:Lỗi lưu đồ án: " . $db->error;
            }
        } else {
            $msg = "error:Bạn không thuộc đề tài đồ án này!";
        }
    }
}

// Lấy đồ án của sinh viên (cả cá nhân lẫn nhóm) - ưu tiên đồ án mới nhất
$project = null;
$nhom_members = [];
$stmt_da = $db->prepare("
    SELECT d.*, g.ho_ten as gv_name, g.khoa as gv_khoa, n.ten_nhom
    FROM do_an d
    LEFT JOIN giang_vien g ON d.giang_vien_id = g.id
    LEFT JOIN nhom_do_an n ON d.nhom_id = n.id
    WHERE d.sinh_vien_id = ?
       OR d.nhom_id IN (
           SELECT nhom_id FROM nhom_do_an_thanh_vien WHERE sinh_vien_id = ?
       )
    ORDER BY d.id DESC
    LIMIT 1
");
$stmt_da->bind_param("ii", $sv_id, $sv_id);
$stmt_da->execute();
$project = $stmt_da->get_result()->fetch_assoc();

// Lấy danh sách thành viên nhóm nếu là đồ án nhóm
if ($project && !empty($project['nhom_id'])) {
    $nid = (int)$project['nhom_id'];
    $res_m = $db->query("
        SELECT s.ho_ten, s.ma_sv, tv.vai_tro
        FROM nhom_do_an_thanh_vien tv
        JOIN students s ON tv.sinh_vien_id = s.id
        WHERE tv.nhom_id = $nid
        ORDER BY tv.id ASC
    ");
    $nhom_members = $res_m->fetch_all(MYSQLI_ASSOC);
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đồ án - Cổng sinh viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        .form-control {
            background: var(--bg3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 15px;
            border-radius: 8px;
            width: 100%;
            font-size: 14px;
            outline: none;
            margin-bottom: 15px;
        }
        .btn-submit {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.15s;
        }
        .btn-submit:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php include '../includes/student_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-folder-open" style="color:var(--accent)"></i> Tiến Độ Đồ Án Môn Học</h1>
            <p style="color: var(--text2); margin-top: 5px;">Cập nhật tiến độ đề tài, nộp sản phẩm đồ án và xem phản hồi chấm điểm từ giảng viên hướng dẫn</p>
        </div>
    </div>

    <!-- Alert message -->
    <?php if ($msg): 
        $parts = explode(':', $msg);
        $type = $parts[0];
        $text = $parts[1];
    ?>
        <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>" style="display: block; margin-bottom: 20px;">
            <?= htmlspecialchars($text) ?>
        </div>
    <?php endif; ?>

    <?php if (!$project): ?>
        <div class="card" style="padding: 60px; text-align: center; color: var(--text2);">
            <i class="fa-solid fa-folder-open" style="font-size: 40px; color: var(--accent); margin-bottom: 20px; display: block;"></i>
            Bạn chưa được phân công đề tài đồ án nào trong học kỳ này.
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; align-items: start;">
            <!-- Left: Project Info & Submission Form -->
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-file-invoice"></i> Thông tin Đề tài: <?= htmlspecialchars($project['ten_do_an']) ?></span>
                </div>
                
                <div class="card-body">
                    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:20px; background:var(--bg3); padding:15px; border-radius:10px;">
                        <div>
                            <span style="font-size:11px; text-transform:uppercase; color:var(--text2); font-weight:700;">Giảng viên hướng dẫn</span>
                            <div style="font-weight:800; color:var(--text); font-size:14px; margin-top:3px;"><?= htmlspecialchars($project['gv_name'] ?? 'TBA') ?></div>
                            <span style="font-size:11px; color:var(--text2);"><?= htmlspecialchars($project['gv_khoa'] ?? 'Khoa Công nghệ thông tin') ?></span>
                        </div>
                        <div>
                            <span style="font-size:11px; text-transform:uppercase; color:var(--text2); font-weight:700;">Trạng thái đồ án</span>
                            <div style="margin-top:3px;">
                                <?php
                                $col = '#64748b'; // Gray for Chưa bắt đầu
                                if ($project['trang_thai'] === 'Đang thực hiện') $col = '#3b82f6';
                                elseif ($project['trang_thai'] === 'Hoàn thành') $col = '#f59e0b';
                                elseif ($project['trang_thai'] === 'Đã nghiệm thu') $col = '#10b981';
                                ?>
                                <span class="badge" style="background: <?= $col ?>1d; color: <?= $col ?>; border: 1px solid <?= $col ?>33; font-weight:700; font-size:11px;">
                                    <?= htmlspecialchars($project['trang_thai']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($project['nhom_id']) && !empty($nhom_members)): ?>
                    <div style="background:rgba(99,102,241,0.05); border:1px solid rgba(99,102,241,0.15); border-radius:10px; padding:14px; margin-bottom:18px;">
                        <div style="font-size:11px; text-transform:uppercase; color:#6366f1; font-weight:700; margin-bottom:10px;">
                            <i class="fa-solid fa-users"></i> Đồ án nhóm — <?= htmlspecialchars($project['ten_nhom'] ?: 'Nhóm') ?>
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                            <?php foreach ($nhom_members as $m): ?>
                                <span style="display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;
                                    background:<?= $m['vai_tro'] === 'Nhóm trưởng' ? 'rgba(234,179,8,0.1)' : 'rgba(99,102,241,0.08)' ?>;
                                    color:<?= $m['vai_tro'] === 'Nhóm trưởng' ? '#ca8a04' : '#6366f1' ?>;
                                    border:1px solid <?= $m['vai_tro'] === 'Nhóm trưởng' ? 'rgba(234,179,8,0.25)' : 'rgba(99,102,241,0.2)' ?>;">
                                    <?= $m['vai_tro'] === 'Nhóm trưởng' ? '<i class="fa-solid fa-crown"></i>' : '<i class="fa-solid fa-user"></i>' ?>
                                    <?= htmlspecialchars($m['ho_ten']) ?> <span style="opacity:0.6;">(<?= htmlspecialchars($m['ma_sv']) ?>)</span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>


                    <form method="POST" action="?action=submit_project" enctype="multipart/form-data">
                        <input type="hidden" name="do_an_id" value="<?= $project['id'] ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Cập nhật tiến độ dự án</label>
                            <select name="trang_thai" class="form-control" style="cursor: pointer;">
                                <option value="Chưa bắt đầu" <?= $project['trang_thai'] === 'Chưa bắt đầu' ? 'selected' : '' ?>>Chưa bắt đầu</option>
                                <option value="Đang thực hiện" <?= $project['trang_thai'] === 'Đang thực hiện' ? 'selected' : '' ?>>Đang thực hiện</option>
                                <option value="Hoàn thành" <?= $project['trang_thai'] === 'Hoàn thành' ? 'selected' : '' ?>>Hoàn thành</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Đường dẫn sản phẩm (GitHub Repo, Google Drive báo cáo...)</label>
                            <input type="text" name="link_nop_bai" class="form-control" placeholder="https://github.com/username/project-repo" value="<?= htmlspecialchars($project['link_nop_bai'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label class="form-label">Tải lên file báo cáo / Mã nguồn nén (.Zip, .Rar, .Pdf)</label>
                            <input type="file" name="project_file" class="form-control" style="padding: 8px 12px; font-size:12px; cursor: pointer;">
                            <?php if (!empty($project['file_nop_bai'])): ?>
                                <div style="font-size:12px; color:var(--text2); margin-top:5px;">
                                    Sản phẩm đã nộp: <a href="<?= htmlspecialchars($project['file_nop_bai']) ?>" target="_blank" style="color:var(--accent); font-weight:700;"><i class="fa-solid fa-paperclip"></i> Tải về file sản phẩm đã nộp</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn-submit" <?= $project['trang_thai'] === 'Đã nghiệm thu' ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                            <i class="fa-solid fa-cloud-arrow-up"></i> Lưu &amp; Nộp sản phẩm
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Evaluation Results from Adviser -->
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-star"></i> Kết quả Nghiệm thu &amp; Đánh giá</span>
                </div>
                <div class="card-body">
                    <?php if ($project['diem'] === null): ?>
                        <div style="text-align: center; padding: 40px; color: var(--text2);">
                            <i class="fa-regular fa-clock" style="font-size: 32px; color: var(--accent); margin-bottom: 15px; display: block;"></i>
                            Đồ án của bạn đang trong tiến trình thực hiện. Hãy liên tục cập nhật tiến độ cho Giảng viên hướng dẫn chấm điểm và nhận xét nhé!
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; margin-bottom: 25px;">
                            <span style="font-size: 12px; color: var(--text2); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 8px;">Điểm số nghiệm thu</span>
                            <span style="font-size: 48px; font-weight: 900; color: #10b981; line-height:1;"><?= number_format($project['diem'], 2) ?></span>
                            <span style="font-size:14px; color:#10b981; display:block; margin-top:5px; font-weight:700;">Đồ án đã nghiệm thu thành công!</span>
                        </div>
                        
                        <div style="border-top: 1px solid var(--border); padding-top: 20px;">
                            <strong style="font-size: 13.5px; color: var(--text); display: block; margin-bottom: 8px;"><i class="fa-solid fa-comment-dots"></i> Nhận xét từ giáo viên hướng dẫn:</strong>
                            <div style="background: rgba(16, 185, 129, 0.03); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 15px; font-size: 13.5px; color: var(--text2); line-height: 1.6; white-space: pre-wrap;"><?= htmlspecialchars($project['nhan_xet'] ?: 'Đồ án hoàn thành tốt, đúng hạn và đáp ứng đầy đủ yêu cầu kĩ thuật.') ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    </div><!-- /content-pad -->
</div><!-- /main-content -->
</body>
</html>
