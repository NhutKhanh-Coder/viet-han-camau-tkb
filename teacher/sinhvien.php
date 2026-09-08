<?php
require_once '../config.php';
requireTeacher();
$db = getDB();
$gv_id = $_SESSION['giang_vien_id'] ?? 0;

// Fetch teacher department
$st_t = $db->prepare("SELECT khoa FROM giang_vien WHERE id = ?");
$st_t->bind_param("i", $gv_id);
$st_t->execute();
$t_row = $st_t->get_result()->fetch_assoc();
$teacher_khoa = $t_row['khoa'] ?? '';

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$lop_filter = trim($_GET['lop'] ?? '');

// Fetch all classes for filter dropdown
$classes = [];
if ($teacher_khoa) {
    $stmt = $db->prepare("SELECT DISTINCT lop FROM students WHERE LOWER(khoa) = LOWER(?) ORDER BY lop");
    $stmt->bind_param("s", $teacher_khoa);
    $stmt->execute();
    $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $res = $db->query("SELECT DISTINCT lop FROM students ORDER BY lop");
    $classes = $res->fetch_all(MYSQLI_ASSOC);
}

// Build query
$query = "SELECT id, ma_sv, ho_ten, lop, email, sdt FROM students WHERE 1=1";
$params = [];
$types = "";

if ($teacher_khoa) {
    $query .= " AND LOWER(khoa) = LOWER(?)";
    $params[] = $teacher_khoa;
    $types .= "s";
}

if ($search) {
    $query .= " AND (ma_sv LIKE ? OR ho_ten LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if ($lop_filter) {
    $query .= " AND lop = ?";
    $params[] = $lop_filter;
    $types .= "s";
}

$query .= " ORDER BY ho_ten ASC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$selected_student_id = (int)($_GET['student_id'] ?? 0);
$selected_student = null;
$student_grades = [];
if ($selected_student_id) {
    // Fetch details of selected student
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $selected_student_id);
    $stmt->execute();
    $selected_student = $stmt->get_result()->fetch_assoc();
    
    if ($selected_student) {
        $student_grades = [];
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách Sinh viên - Giảng viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        .search-row {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .search-input {
            background: var(--bg3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            flex: 1;
            min-width: 200px;
        }
        .search-select {
            background: var(--bg3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            min-width: 150px;
            cursor: pointer;
        }
        .btn-search {
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
        .btn-search:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <?php include '../includes/teacher_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-users" style="color:var(--accent)"></i> Tra Cứu Sinh Viên</h1>
            <p style="color: var(--text2); margin-top: 5px;">Khoa phụ trách: <span class="badge" style="background:rgba(56,189,248,0.12); color:#38bdf8; border:1px solid rgba(56,189,248,0.2)"><?= htmlspecialchars($teacher_khoa ?: 'Tất cả khoa') ?></span></p>
        </div>
    </div>

    <!-- Search Form -->
    <form method="GET" class="search-row">
        <input type="text" name="search" class="search-input" placeholder="Tìm theo Mã SV hoặc Họ tên..." value="<?= htmlspecialchars($search) ?>">
        <select name="lop" class="search-select">
            <option value="">-- Tất cả lớp --</option>
            <?php foreach ($classes as $cl): ?>
                <option value="<?= htmlspecialchars($cl['lop']) ?>" <?= $lop_filter === $cl['lop'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['lop']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</button>
    </form>

    <div style="display: grid; grid-template-columns: 1.5fr 2fr; gap: 30px; align-items: start;">
        <!-- Students list -->
        <div class="card">
            <div class="card-head">
                <span class="card-title"><i class="fa-solid fa-users-viewfinder"></i> Kết quả tìm kiếm (<?= count($students) ?>)</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 12px 15px;">Mã SV</th>
                            <th style="padding: 12px 15px;">Họ tên</th>
                            <th style="padding: 12px 15px;">Lớp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: var(--text2); padding: 20px;">Không tìm thấy sinh viên nào.</td>
                            </tr>
                        <?php else: foreach ($students as $st): ?>
                            <tr style="cursor: pointer; background: <?= $selected_student_id === $st['id'] ? 'rgba(255,255,255,0.03)' : 'transparent' ?>" onclick="window.location.href='?student_id=<?= $st['id'] ?>&search=<?= urlencode($search) ?>&lop=<?= urlencode($lop_filter) ?>'">
                                <td style="padding: 12px 15px; font-weight: 700; color: var(--accent);"><?= htmlspecialchars($st['ma_sv']) ?></td>
                                <td style="padding: 12px 15px; font-weight: 600; color: var(--text);"><?= htmlspecialchars($st['ho_ten']) ?></td>
                                <td style="padding: 12px 15px; color: var(--text2);"><?= htmlspecialchars($st['lop']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Student Inspector Panel -->
        <div class="card">
            <div class="card-head">
                <span class="card-title"><i class="fa-solid fa-address-card"></i> Hồ sơ chi tiết</span>
            </div>
            <div class="card-body">
                <?php if (!$selected_student): ?>
                    <div style="text-align: center; padding: 40px; color: var(--text2);">
                        <i class="fa-solid fa-circle-user" style="font-size: 40px; color: var(--accent); margin-bottom: 15px; display: block;"></i>
                        Chọn một sinh viên từ danh sách để xem hồ sơ và điểm số học tập.
                    </div>
                <?php else: ?>
                    <div style="display: flex; gap: 20px; margin-bottom: 25px; align-items: center;">
                        <div style="background: var(--bg3); width: 70px; height: 70px; border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border);">
                            <i class="fa-solid fa-user-graduate" style="font-size: 32px; color: var(--accent);"></i>
                        </div>
                        <div>
                            <h2 style="font-size: 20px; font-weight: 800; color: var(--text);"><?= htmlspecialchars($selected_student['ho_ten']) ?></h2>
                            <p style="color: var(--text2); font-size: 13px; margin-top: 3px;">Mã SV: <strong style="color:var(--accent)"><?= htmlspecialchars($selected_student['ma_sv']) ?></strong> | Lớp: <strong><?= htmlspecialchars($selected_student['lop']) ?></strong></p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                        <div style="background: rgba(255,255,255,0.01); border: 1px solid var(--border); padding: 12px; border-radius: 8px;">
                            <div style="font-size: 11px; color: var(--text2); font-weight: 700; margin-bottom: 4px; text-transform: uppercase;">Email</div>
                            <div style="font-size: 13.5px; font-weight: 600; color: var(--text);"><?= htmlspecialchars($selected_student['email'] ?: 'Chưa cập nhật') ?></div>
                        </div>
                        <div style="background: rgba(255,255,255,0.01); border: 1px solid var(--border); padding: 12px; border-radius: 8px;">
                            <div style="font-size: 11px; color: var(--text2); font-weight: 700; margin-bottom: 4px; text-transform: uppercase;">Số điện thoại</div>
                            <div style="font-size: 13.5px; font-weight: 600; color: var(--text);"><?= htmlspecialchars($selected_student['sdt'] ?: 'Chưa cập nhật') ?></div>
                        </div>
                    </div>


                <?php endif; ?>
            </div>
        </div>
    </div>

    </div> <!-- content-pad -->
    </div> <!-- main-content -->
</body>
</html>
