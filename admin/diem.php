<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$db = getDB();
$msg = '';

// Helper for safe query executions without risking fatal errors
function safeQueryRow($db, $sql) {
    try {
        $r = @$db->query($sql);
        if ($r && method_exists($r, 'fetch_row')) return $r->fetch_row();
    } catch (Throwable $e) {}
    return [0, 0];
}
function safeQueryCount($db, $sql) {
    $r = safeQueryRow($db, $sql);
    return (int)($r[0] ?? 0);
}

// Handle Actions (Update & Delete Grades by Admin)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. Update Quiz Attempt (Sửa điểm & nhận xét Quiz)
if ($action === 'update_quiz_attempt') {
    $att_id = (int)($_POST['attempt_id'] ?? 0);
    $score = (int)($_POST['score'] ?? 0);
    $total = max(1, (int)($_POST['total_questions'] ?? 10));
    $nhan_xet = trim($_POST['nhan_xet'] ?? '');

    $st = $db->prepare("UPDATE quiz_attempts SET score = ?, total_questions = ?, nhan_xet = ? WHERE id = ?");
    if ($st) {
        $st->bind_param("iisi", $score, $total, $nhan_xet, $att_id);
        if ($st->execute()) {
            $msg = "success:Đã cập nhật kết quả bài thi Quiz thành công!";
            writeSystemLog("Admin cập nhật kết quả Quiz attempt ID $att_id: $score/$total câu");
        } else {
            $msg = "error:Lỗi cập nhật: " . $db->error;
        }
        $st->close();
    }
}

// 2. Delete Quiz Attempt (Xóa lượt thi Quiz)
if ($action === 'delete_quiz_attempt') {
    $att_id = (int)($_POST['attempt_id'] ?? $_GET['id'] ?? 0);
    if ($att_id > 0) {
        if ($db->query("DELETE FROM quiz_attempts WHERE id = $att_id")) {
            $msg = "success:Đã xóa kết quả lượt thi Quiz thành công! Sinh viên có thể làm lại bài này.";
            writeSystemLog("Admin xóa kết quả Quiz attempt ID $att_id");
        } else {
            $msg = "error:Lỗi khi xóa: " . $db->error;
        }
    }
}

// 3. Update Assignment Grade
if ($action === 'update_assignment_grade') {
    $sid = (int)($_POST['student_id'] ?? 0);
    $aid = (int)($_POST['assignment_id'] ?? 0);
    $grade = ($_POST['grade'] !== '') ? (float)$_POST['grade'] : null;
    $feedback = trim($_POST['feedback'] ?? '');

    $chk_res = $db->query("SELECT id FROM submissions WHERE student_id = $sid AND assignment_id = $aid");
    $chk = $chk_res ? $chk_res->fetch_assoc() : null;
    if ($chk) {
        $st = $db->prepare("UPDATE submissions SET grade = ?, feedback = ? WHERE student_id = ? AND assignment_id = ?");
        if ($st) {
            $st->bind_param("dsii", $grade, $feedback, $sid, $aid);
            $st->execute();
            $st->close();
        }
    } else {
        $st = $db->prepare("INSERT INTO submissions (student_id, assignment_id, grade, feedback, submitted_at) VALUES (?, ?, ?, ?, NOW())");
        if ($st) {
            $st->bind_param("iids", $sid, $aid, $grade, $feedback);
            $st->execute();
            $st->close();
        }
    }
    $msg = "success:Đã cập nhật điểm bài tập thành công!";
    writeSystemLog("Admin cập nhật điểm bài tập ID $aid cho SV ID $sid: $grade đ");
}

// 4. Delete Assignment Submission
if ($action === 'delete_assignment_submission') {
    $sub_id = (int)($_POST['submission_id'] ?? $_GET['id'] ?? 0);
    if ($sub_id > 0) {
        if ($db->query("DELETE FROM submissions WHERE id = $sub_id")) {
            $msg = "success:Đã xóa bài tập nộp thành công!";
            writeSystemLog("Admin xóa assignment submission ID $sub_id");
        } else {
            $msg = "error:Lỗi khi xóa: " . $db->error;
        }
    }
}

// 5. Update Practice Grade
if ($action === 'update_practice_grade') {
    $sub_id = (int)($_POST['submission_id'] ?? 0);
    $score = ($_POST['score'] !== '') ? (float)$_POST['score'] : null;
    $feedback = trim($_POST['teacher_feedback'] ?? '');

    $st = $db->prepare("UPDATE practice_submissions SET diem = ?, nhan_xet = ? WHERE id = ?");
    if ($st) {
        $st->bind_param("dsi", $score, $feedback, $sub_id);
        $st->execute();
        $st->close();
    }
    $msg = "success:Đã cập nhật điểm thực hành thành công!";
    writeSystemLog("Admin cập nhật điểm thực hành submission ID $sub_id: $score đ");
}

// 6. Delete Practice Submission
if ($action === 'delete_practice_submission') {
    $sub_id = (int)($_POST['submission_id'] ?? $_GET['id'] ?? 0);
    if ($sub_id > 0) {
        if ($db->query("DELETE FROM practice_submissions WHERE id = $sub_id")) {
            $msg = "success:Đã xóa bài nộp thực hành thành công!";
            writeSystemLog("Admin xóa practice submission ID $sub_id");
        } else {
            $msg = "error:Lỗi khi xóa: " . $db->error;
        }
    }
}

// 7. Update Project Grade
if ($action === 'update_project_grade') {
    $da_id = (int)($_POST['do_an_id'] ?? 0);
    $diem = ($_POST['diem'] !== '') ? (float)$_POST['diem'] : null;
    $nhan_xet = trim($_POST['nhan_xet'] ?? '');
    $trang_thai = trim($_POST['trang_thai'] ?? 'Đang thực hiện');

    $st = $db->prepare("UPDATE do_an SET diem = ?, nhan_xet = ?, trang_thai = ? WHERE id = ?");
    if ($st) {
        $st->bind_param("dssi", $diem, $nhan_xet, $trang_thai, $da_id);
        $st->execute();
        $st->close();
    }
    $msg = "success:Đã cập nhật điểm & tiến độ đồ án thành công!";
    writeSystemLog("Admin cập nhật điểm đồ án ID $da_id: $diem đ");
}

// 8. Delete / Reset Project Grade
if ($action === 'delete_project_grade') {
    $da_id = (int)($_POST['do_an_id'] ?? $_GET['id'] ?? 0);
    if ($da_id > 0) {
        if ($db->query("UPDATE do_an SET diem = NULL, nhan_xet = NULL, trang_thai = 'Chưa bắt đầu' WHERE id = $da_id")) {
            $msg = "success:Đã đặt lại điểm đồ án về ban đầu!";
            writeSystemLog("Admin reset điểm đồ án ID $da_id");
        } else {
            $msg = "error:Lỗi khi đặt lại điểm: " . $db->error;
        }
    }
}

// 4. Update Subject Grade (Table diem)
if ($action === 'update_subject_grade') {
    $sid = (int)($_POST['student_id'] ?? 0);
    $mid = (int)($_POST['mon_hoc_id'] ?? 0);
    $cc = ($_POST['diem_chuyen_can'] !== '') ? (float)$_POST['diem_chuyen_can'] : null;
    $gk = ($_POST['diem_giua_ky'] !== '') ? (float)$_POST['diem_giua_ky'] : null;
    $ck = ($_POST['diem_cuoi_ky'] !== '') ? (float)$_POST['diem_cuoi_ky'] : null;
    $tk = ($_POST['diem_tong_ket'] !== '') ? (float)$_POST['diem_tong_ket'] : null;
    $note = trim($_POST['ghi_chu'] ?? '');

    // Auto calculate if empty
    if ($tk === null && $gk !== null && $ck !== null) {
        $tk = round(($gk * 0.4) + ($ck * 0.6), 1);
    }

    $chk_table = @$db->query("SHOW TABLES LIKE 'diem'");
    if (!$chk_table || $chk_table->num_rows == 0) {
        @$db->query("CREATE TABLE IF NOT EXISTS `diem` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `student_id` int(11) NOT NULL,
            `mon_hoc_id` int(11) NOT NULL,
            `diem_chuyen_can` decimal(4,1) DEFAULT NULL,
            `diem_giua_ky` decimal(4,1) DEFAULT NULL,
            `diem_cuoi_ky` decimal(4,1) DEFAULT NULL,
            `diem_tong_ket` decimal(4,1) DEFAULT NULL,
            `ghi_chu` text DEFAULT NULL,
            `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `sv_mon` (`student_id`,`mon_hoc_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    $chk_res = $db->query("SELECT id FROM diem WHERE student_id = $sid AND mon_hoc_id = $mid");
    $chk = $chk_res ? $chk_res->fetch_assoc() : null;
    if ($chk) {
        $st = $db->prepare("UPDATE diem SET diem_chuyen_can=?, diem_giua_ky=?, diem_cuoi_ky=?, diem_tong_ket=?, ghi_chu=? WHERE student_id=? AND mon_hoc_id=?");
        if ($st) {
            $st->bind_param("ddddsii", $cc, $gk, $ck, $tk, $note, $sid, $mid);
            $st->execute();
            $st->close();
        }
    } else {
        $st = $db->prepare("INSERT INTO diem (student_id, mon_hoc_id, diem_chuyen_can, diem_giua_ky, diem_cuoi_ky, diem_tong_ket, ghi_chu) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($st) {
            $st->bind_param("iidddds", $sid, $mid, $cc, $gk, $ck, $tk, $note);
            $st->execute();
            $st->close();
        }
    }
    $msg = "success:Đã cập nhật bảng điểm môn học thành công!";
    writeSystemLog("Admin cập nhật điểm học phần môn $mid cho SV $sid");
}

// 5. Export CSV
if ($action === 'export_csv') {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bang_diem_tong_hop_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Mã SV', 'Họ Tên', 'Lớp', 'Khoa / Ngành', 'Điểm Quiz TB', 'Điểm Thực Hành TB', 'Bài Tập Đã Nộp', 'Đồ Án']);

    $q_export = "SELECT s.id, s.ma_sv, s.ho_ten, s.lop, s.khoa,
        (SELECT AVG(score) FROM quiz_attempts WHERE student_id = s.id) as avg_quiz,
        (SELECT AVG(diem) FROM practice_submissions WHERE student_id = s.id AND diem IS NOT NULL) as avg_prac,
        (SELECT COUNT(id) FROM submissions WHERE student_id = s.id) as total_sub,
        (SELECT diem FROM do_an WHERE sinh_vien_id = s.id LIMIT 1) as diem_doan
        FROM students s ORDER BY s.lop ASC, s.ho_ten ASC";
    $res_exp = $db->query($q_export);
    if ($res_exp) {
        while ($r = $res_exp->fetch_assoc()) {
            fputcsv($output, [
                $r['ma_sv'],
                $r['ho_ten'],
                $r['lop'],
                $r['khoa'],
                $r['avg_quiz'] !== null ? round($r['avg_quiz'], 1) : '',
                $r['avg_prac'] !== null ? round($r['avg_prac'], 1) : '',
                $r['total_sub'],
                $r['diem_doan'] !== null ? $r['diem_doan'] : ''
            ]);
        }
    }
    fclose($output);
    $db->close();
    exit();
}

// Parameters
$cur_tab = $_GET['tab'] ?? 'tonghop'; // tonghop | quiz | thuchanh | baitap | doan | hocphan
$filter_lop = trim($_GET['lop'] ?? '');
$filter_mon = (int)($_GET['mon_hoc_id'] ?? 0);
$filter_quiz = (int)($_GET['quiz_id'] ?? 0);
$search = trim($_GET['q'] ?? '');

// Fetch Class list
$classes = [];
$res_c = $db->query("SELECT DISTINCT lop FROM students WHERE lop IS NOT NULL AND lop != '' ORDER BY lop");
if ($res_c) {
    while ($rc = $res_c->fetch_assoc()) $classes[] = $rc['lop'];
}

// Fetch Subjects list
$subjects = [];
$res_m = $db->query("SELECT id, ma_mon, ten_mon FROM mon_hoc ORDER BY ten_mon");
if ($res_m) {
    while ($rm = $res_m->fetch_assoc()) $subjects[] = $rm;
}

// Fetch Quizzes list
$quizzes_list = [];
$res_ql = $db->query("SELECT q.id, q.tieu_de, m.ten_mon FROM quizzes q LEFT JOIN mon_hoc m ON q.mon_hoc_id = m.id ORDER BY q.id DESC");
if ($res_ql) {
    while ($rq = $res_ql->fetch_assoc()) $quizzes_list[] = $rq;
}

// Auto clean up orphaned records so numbers are always 100% consistent across system
@$db->query("DELETE ps FROM practice_submissions ps LEFT JOIN students s ON ps.student_id = s.id WHERE s.id IS NULL");
@$db->query("DELETE ps FROM practice_submissions ps LEFT JOIN practice_sessions sess ON ps.session_id = sess.id WHERE sess.id IS NULL");
@$db->query("DELETE qa FROM quiz_attempts qa LEFT JOIN students s ON qa.student_id = s.id WHERE s.id IS NULL");
@$db->query("DELETE qa FROM quiz_attempts qa LEFT JOIN quizzes q ON qa.quiz_id = q.id WHERE q.id IS NULL");
@$db->query("DELETE sub FROM submissions sub LEFT JOIN students s ON sub.student_id = s.id WHERE s.id IS NULL");
@$db->query("DELETE sub FROM submissions sub LEFT JOIN assignments a ON sub.assignment_id = a.id WHERE a.id IS NULL");
@$db->query("DELETE d FROM do_an d LEFT JOIN students s ON d.sinh_vien_id = s.id WHERE s.id IS NULL");

// Top KPI Statistics safely
$kpi_total_sv = safeQueryCount($db, "SELECT COUNT(id) FROM students");
$kpi_quiz_att = safeQueryRow($db, "SELECT COUNT(id), AVG(score) FROM quiz_attempts");
$kpi_total_quiz = $kpi_quiz_att[0] ?? 0;
$kpi_avg_quiz = $kpi_quiz_att[1] !== null ? round($kpi_quiz_att[1], 1) : 0;

$kpi_prac_sub = safeQueryRow($db, "SELECT COUNT(id), AVG(diem) FROM practice_submissions");
$kpi_total_prac = $kpi_prac_sub[0] ?? 0;
$kpi_avg_prac = $kpi_prac_sub[1] !== null ? round($kpi_prac_sub[1], 1) : 0;

$kpi_total_assign = safeQueryCount($db, "SELECT COUNT(id) FROM submissions");
$kpi_total_doan = safeQueryCount($db, "SELECT COUNT(id) FROM do_an");

// TAB 1: TỔNG HỢP (Overview Transcript)
$data_tonghop = [];
if ($cur_tab === 'tonghop') {
    $sql_th = "
        SELECT s.id, s.ma_sv, s.ho_ten, s.lop, s.khoa, s.avatar,
            (SELECT AVG(score) FROM quiz_attempts WHERE student_id = s.id) as avg_quiz,
            (SELECT COUNT(id) FROM quiz_attempts WHERE student_id = s.id) as count_quiz,
            (SELECT AVG(diem) FROM practice_submissions WHERE student_id = s.id AND diem IS NOT NULL) as avg_prac,
            (SELECT COUNT(id) FROM practice_submissions WHERE student_id = s.id) as count_prac,
            (SELECT COUNT(id) FROM submissions WHERE student_id = s.id) as count_assign,
            (SELECT AVG(grade) FROM submissions WHERE student_id = s.id AND grade IS NOT NULL) as avg_assign,
            (SELECT diem FROM do_an WHERE sinh_vien_id = s.id LIMIT 1) as diem_doan,
            (SELECT ten_do_an FROM do_an WHERE sinh_vien_id = s.id LIMIT 1) as ten_doan
        FROM students s
        WHERE 1=1
    ";
    if (!empty($filter_lop)) {
        $sql_th .= " AND s.lop = '" . $db->real_escape_string($filter_lop) . "'";
    }
    if (!empty($search)) {
        $esc_s = $db->real_escape_string($search);
        $sql_th .= " AND (s.ho_ten LIKE '%$esc_s%' OR s.ma_sv LIKE '%$esc_s%' OR s.lop LIKE '%$esc_s%')";
    }
    $sql_th .= " ORDER BY s.lop ASC, s.ho_ten ASC";
    $res_th = $db->query($sql_th);
    if ($res_th) $data_tonghop = $res_th->fetch_all(MYSQLI_ASSOC);
}

// TAB 2: QUIZ & TRẮC NGHIỆM
$data_quiz = [];
if ($cur_tab === 'quiz') {
    $sql_q = "
        SELECT qa.*, s.ma_sv, s.ho_ten, s.lop, s.avatar, q.tieu_de as quiz_title, m.ten_mon as subject_name
        FROM quiz_attempts qa
        LEFT JOIN students s ON qa.student_id = s.id
        LEFT JOIN quizzes q ON qa.quiz_id = q.id
        LEFT JOIN mon_hoc m ON q.mon_hoc_id = m.id
        WHERE 1=1
    ";
    if (!empty($filter_lop)) $sql_q .= " AND s.lop = '" . $db->real_escape_string($filter_lop) . "'";
    if ($filter_quiz > 0) $sql_q .= " AND qa.quiz_id = " . $filter_quiz;
    if ($filter_mon > 0) $sql_q .= " AND q.mon_hoc_id = " . $filter_mon;
    if (!empty($search)) {
        $esc_s = $db->real_escape_string($search);
        $sql_q .= " AND (s.ho_ten LIKE '%$esc_s%' OR s.ma_sv LIKE '%$esc_s%')";
    }
    $sql_q .= " ORDER BY qa.attempted_at DESC";
    $res_q = $db->query($sql_q);
    if ($res_q) $data_quiz = $res_q->fetch_all(MYSQLI_ASSOC);
}

// TAB 3: THỰC HÀNH LẬP TRÌNH
$data_prac = [];
if ($cur_tab === 'thuchanh') {
    $sql_p = "
        SELECT ps.*, ps.diem as score, ps.nhan_xet as teacher_feedback, 
               s.ma_sv, s.ho_ten, s.lop, s.avatar, sess.mo_ta as session_title, sess.start_time, sess.end_time
        FROM practice_submissions ps
        LEFT JOIN students s ON ps.student_id = s.id
        LEFT JOIN practice_sessions sess ON ps.session_id = sess.id
        WHERE 1=1
    ";
    if (!empty($filter_lop)) $sql_p .= " AND s.lop = '" . $db->real_escape_string($filter_lop) . "'";
    if (!empty($search)) {
        $esc_s = $db->real_escape_string($search);
        $sql_p .= " AND (s.ho_ten LIKE '%$esc_s%' OR s.ma_sv LIKE '%$esc_s%')";
    }
    $sql_p .= " ORDER BY ps.submitted_at DESC";
    $res_p = $db->query($sql_p);
    if ($res_p) $data_prac = $res_p->fetch_all(MYSQLI_ASSOC);
}

// TAB 4: BÀI TẬP VỀ NHÀ
$data_assign = [];
if ($cur_tab === 'baitap') {
    $sql_a = "
        SELECT sub.*, s.ma_sv, s.ho_ten, s.lop, a.tieu_de as assignment_title, m.ten_mon as subject_name, g.ho_ten as teacher_name
        FROM submissions sub
        LEFT JOIN students s ON sub.student_id = s.id
        LEFT JOIN assignments a ON sub.assignment_id = a.id
        LEFT JOIN mon_hoc m ON a.mon_hoc_id = m.id
        LEFT JOIN giang_vien g ON a.giang_vien_id = g.id
        WHERE 1=1
    ";
    if (!empty($filter_lop)) $sql_a .= " AND s.lop = '" . $db->real_escape_string($filter_lop) . "'";
    if ($filter_mon > 0) $sql_a .= " AND a.mon_hoc_id = " . $filter_mon;
    if (!empty($search)) {
        $esc_s = $db->real_escape_string($search);
        $sql_a .= " AND (s.ho_ten LIKE '%$esc_s%' OR s.ma_sv LIKE '%$esc_s%')";
    }
    $sql_a .= " ORDER BY sub.submitted_at DESC";
    $res_a = $db->query($sql_a);
    if ($res_a) $data_assign = $res_a->fetch_all(MYSQLI_ASSOC);
}

// TAB 5: ĐỒ ÁN / KHÓA LUẬN
$data_doan = [];
if ($cur_tab === 'doan') {
    $sql_da = "
        SELECT d.*, s.ma_sv, s.ho_ten, s.lop, g.ho_ten as teacher_name, n.ten_nhom
        FROM do_an d
        LEFT JOIN students s ON d.sinh_vien_id = s.id
        LEFT JOIN giang_vien g ON d.giang_vien_id = g.id
        LEFT JOIN nhom_do_an n ON d.nhom_id = n.id
        WHERE 1=1
    ";
    if (!empty($filter_lop)) $sql_da .= " AND s.lop = '" . $db->real_escape_string($filter_lop) . "'";
    if (!empty($search)) {
        $esc_s = $db->real_escape_string($search);
        $sql_da .= " AND (s.ho_ten LIKE '%$esc_s%' OR s.ma_sv LIKE '%$esc_s%' OR d.ten_do_an LIKE '%$esc_s%')";
    }
    $sql_da .= " ORDER BY d.id DESC";
    $res_da = $db->query($sql_da);
    if ($res_da) $data_doan = $res_da->fetch_all(MYSQLI_ASSOC);
}

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản Lý Điểm Số Toàn Trường - Quản Trị Viên</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
<style>
.grade-tab-btn {
    padding: 10px 18px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 13px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.03);
    color: #a79bb7;
}
.grade-tab-btn:hover {
    background: rgba(168,85,247,0.12);
    color: #f3e8ff;
    border-color: rgba(168,85,247,0.3);
}
.grade-tab-btn.active {
    background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
    color: #ffffff;
    border-color: #a855f7;
    box-shadow: 0 4px 15px rgba(147, 51, 234, 0.35);
}
.kpi-card-admin {
    background: rgba(26, 17, 48, 0.7);
    border: 1px solid rgba(168, 85, 247, 0.2);
    border-radius: 16px;
    padding: 18px 20px;
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}
.kpi-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.score-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 800;
    font-size: 12px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}
.score-high { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); }
.score-mid  { background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.35); }
.score-low  { background: rgba(244, 63, 94, 0.2); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.35); }
.score-none { background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2); }
</style>
</head>
<body class="admin-portal <?= (isset($_COOKIE['adm_theme']) && $_COOKIE['adm_theme'] === 'light') ? 'adm-light-mode' : '' ?>">
<?php include '../includes/admin_nav.php'; ?>

<div class="main-content">
  
  <!-- Header -->
  <div class="page-header">
    <div>
      <h1 class="page-title" style="display:flex; align-items:center; gap:10px;">
        <i class="fa-solid fa-graduation-cap" style="color: #10b981;"></i> Quản Lý Điểm Số Sinh Viên
      </h1>
      <p class="page-sub">Trung tâm theo dõi, chấm điểm và đánh giá kết quả học tập toàn trường dưới quyền Quản Trị Viên (Admin)</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a href="?action=export_csv" class="btn btn-ghost" style="background:rgba(16,185,129,0.15); color:#34d399; border:1px solid rgba(16,185,129,0.3);">
        <i class="fa-solid fa-file-excel"></i> Xuất Bảng Điểm (CSV/Excel)
      </a>
      <a href="/tkb/admin/students.php" class="btn btn-ghost">
        <i class="fa-solid fa-users"></i> Quản lý Sinh viên
      </a>
    </div>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px;">
    <i class="fa-solid <?= $msgType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
    <?= htmlspecialchars($msgText) ?>
  </div>
  <?php endif; ?>

  <!-- KPI Cards -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:16px; margin-bottom:24px;">
    
    <div class="kpi-card-admin">
      <div class="kpi-icon-box" style="background:rgba(16, 185, 129, 0.15); color:#10b981;">
        <i class="fa-solid fa-user-graduate"></i>
      </div>
      <div>
        <div style="font-size:12px; color:#a79bb7; font-weight:700; text-transform:uppercase;">Tổng Sinh Viên</div>
        <div style="font-size:24px; font-weight:900; color:#f3e8ff; font-family:'Outfit', sans-serif;"><?= number_format($kpi_total_sv) ?></div>
        <div style="font-size:11px; color:#34d399; font-weight:600;">Toàn hệ thống</div>
      </div>
    </div>

    <div class="kpi-card-admin">
      <div class="kpi-icon-box" style="background:rgba(168, 85, 247, 0.15); color:#a855f7;">
        <i class="fa-solid fa-brain"></i>
      </div>
      <div>
        <div style="font-size:12px; color:#a79bb7; font-weight:700; text-transform:uppercase;">Trắc Nghiệm Quiz</div>
        <div style="font-size:24px; font-weight:900; color:#f3e8ff; font-family:'Outfit', sans-serif;"><?= number_format($kpi_total_quiz) ?> <span style="font-size:12px; color:#c4b5fd;">lượt</span></div>
        <div style="font-size:11px; color:#c084fc; font-weight:700;">Điểm TB: <?= $kpi_avg_quiz ?> đ</div>
      </div>
    </div>

    <div class="kpi-card-admin">
      <div class="kpi-icon-box" style="background:rgba(56, 189, 248, 0.15); color:#38bdf8;">
        <i class="fa-solid fa-code"></i>
      </div>
      <div>
        <div style="font-size:12px; color:#a79bb7; font-weight:700; text-transform:uppercase;">Thực Hành Code</div>
        <div style="font-size:24px; font-weight:900; color:#f3e8ff; font-family:'Outfit', sans-serif;"><?= number_format($kpi_total_prac) ?> <span style="font-size:12px; color:#7dd3fc;">bài</span></div>
        <div style="font-size:11px; color:#38bdf8; font-weight:700;">Điểm TB: <?= $kpi_avg_prac ?> đ</div>
      </div>
    </div>

    <div class="kpi-card-admin">
      <div class="kpi-icon-box" style="background:rgba(236, 72, 153, 0.15); color:#ec4899;">
        <i class="fa-solid fa-pen-to-square"></i>
      </div>
      <div>
        <div style="font-size:12px; color:#a79bb7; font-weight:700; text-transform:uppercase;">Bài Tập &amp; Đồ Án</div>
        <div style="font-size:24px; font-weight:900; color:#f3e8ff; font-family:'Outfit', sans-serif;"><?= number_format($kpi_total_assign + $kpi_total_doan) ?></div>
        <div style="font-size:11px; color:#f472b6; font-weight:600;"><?= $kpi_total_assign ?> bài nộp • <?= $kpi_total_doan ?> đồ án</div>
      </div>
    </div>

  </div>

  <!-- Tab Bar -->
  <div style="display:flex; gap:10px; margin-bottom:20px; overflow-x:auto; padding-bottom:6px;">
    <a href="?tab=tonghop<?= $filter_lop ? '&lop='.urlencode($filter_lop) : '' ?>" class="grade-tab-btn <?= $cur_tab === 'tonghop' ? 'active' : '' ?>">
      <i class="fa-solid fa-table-list"></i> Bảng Điểm Tổng Hợp
    </a>
    <a href="?tab=quiz<?= $filter_lop ? '&lop='.urlencode($filter_lop) : '' ?>" class="grade-tab-btn <?= $cur_tab === 'quiz' ? 'active' : '' ?>">
      <i class="fa-solid fa-brain"></i> Điểm Trắc Nghiệm Quiz (<?= number_format($kpi_total_quiz) ?>)
    </a>
    <a href="?tab=thuchanh<?= $filter_lop ? '&lop='.urlencode($filter_lop) : '' ?>" class="grade-tab-btn <?= $cur_tab === 'thuchanh' ? 'active' : '' ?>">
      <i class="fa-solid fa-code"></i> Điểm Thực Hành Code (<?= number_format($kpi_total_prac) ?>)
    </a>
    <a href="?tab=baitap<?= $filter_lop ? '&lop='.urlencode($filter_lop) : '' ?>" class="grade-tab-btn <?= $cur_tab === 'baitap' ? 'active' : '' ?>">
      <i class="fa-solid fa-pen-to-square"></i> Điểm Bài Tập (<?= number_format($kpi_total_assign) ?>)
    </a>
    <a href="?tab=doan<?= $filter_lop ? '&lop='.urlencode($filter_lop) : '' ?>" class="grade-tab-btn <?= $cur_tab === 'doan' ? 'active' : '' ?>">
      <i class="fa-solid fa-file-code"></i> Điểm Đồ Án (<?= number_format($kpi_total_doan) ?>)
    </a>
  </div>

  <!-- Filters -->
  <div class="filter-bar" style="background: rgba(26, 17, 48, 0.7); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 14px; padding: 14px 18px; margin-bottom: 24px;">
    <form method="GET" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; width:100%;">
      <input type="hidden" name="tab" value="<?= htmlspecialchars($cur_tab) ?>">

      <div class="search-wrap" style="flex:1; min-width:240px; max-width:380px;">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input class="search-input" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm tên sinh viên, mã SV...">
      </div>

      <!-- Select Lớp -->
      <div style="min-width:160px;">
        <select class="form-select" name="lop" onchange="this.form.submit()" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; padding:8px 14px; border-radius:10px; font-size:13px;">
          <option value="">-- Tất cả các Lớp --</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= ($filter_lop === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if ($cur_tab === 'quiz' && !empty($quizzes_list)): ?>
      <div style="min-width:200px;">
        <select class="form-select" name="quiz_id" onchange="this.form.submit()" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; padding:8px 14px; border-radius:10px; font-size:13px;">
          <option value="">-- Tất cả bài Quiz --</option>
          <?php foreach ($quizzes_list as $qz): ?>
            <option value="<?= $qz['id'] ?>" <?= ($filter_quiz == $qz['id']) ? 'selected' : '' ?>><?= htmlspecialchars($qz['tieu_de']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <?php if (in_array($cur_tab, ['baitap', 'quiz']) && !empty($subjects)): ?>
      <div style="min-width:180px;">
        <select class="form-select" name="mon_hoc_id" onchange="this.form.submit()" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); color:#f3e8ff; padding:8px 14px; border-radius:10px; font-size:13px;">
          <option value="">-- Môn học --</option>
          <?php foreach ($subjects as $sb): ?>
            <option value="<?= $sb['id'] ?>" <?= ($filter_mon == $sb['id']) ? 'selected' : '' ?>><?= htmlspecialchars($sb['ten_mon']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-ghost" style="background:rgba(168,85,247,0.2); color:#f3e8ff; border:1px solid rgba(168,85,247,0.35);">
        <i class="fa-solid fa-filter"></i> Lọc
      </button>

      <?php if ($filter_lop || $search || $filter_quiz || $filter_mon): ?>
      <a href="?tab=<?= urlencode($cur_tab) ?>" class="btn btn-ghost" style="color:#fb7185;">
        <i class="fa-solid fa-xmark"></i> Xóa lọc
      </a>
      <?php endif; ?>
    </form>
  </div>

  <!-- CONTENT TABLES BASED ON TAB -->
  <div class="card" style="background: rgba(26, 17, 48, 0.7); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
    
    <!-- ==================== TAB 1: BẢNG ĐIỂM TỔNG HỢP ==================== -->
    <?php if ($cur_tab === 'tonghop'): ?>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th style="width:45px; text-align:center;">#</th>
            <th style="width:130px;">Mã SV</th>
            <th>Họ tên Sinh viên</th>
            <th style="width:120px;">Lớp</th>
            <th style="width:120px; text-align:center;">Đ. Quiz (TB)</th>
            <th style="width:130px; text-align:center;">Đ. Thực Hành (TB)</th>
            <th style="width:120px; text-align:center;">Bài Tập (Nộp)</th>
            <th style="width:110px; text-align:center;">Đồ Án</th>
            <th style="width:110px; text-align:center;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data_tonghop)): ?>
          <tr><td colspan="9" style="text-align:center; color:#94a3b8; padding:40px;">Không có dữ liệu sinh viên phù hợp với bộ lọc.</td></tr>
          <?php else: foreach ($data_tonghop as $i => $sv): 
            $qz_score = $sv['avg_quiz'] !== null ? round($sv['avg_quiz'], 1) : null;
            $pr_score = $sv['avg_prac'] !== null ? round($sv['avg_prac'], 1) : null;
            $da_score = $sv['diem_doan'] !== null ? round($sv['diem_doan'], 1) : null;
          ?>
          <tr>
            <td style="text-align:center; color:#94a3b8; font-weight:600;"><?= $i+1 ?></td>
            <td>
              <span style="font-family:ui-monospace, monospace; font-size:12.5px; font-weight:700; background:rgba(255,255,255,0.06); color:#f3e8ff; padding:4px 8px; border-radius:6px; border:1px solid rgba(255,255,255,0.1);">
                <?= htmlspecialchars($sv['ma_sv']) ?>
              </span>
            </td>
            <td>
              <div style="display:flex; align-items:center; gap:10px;">
                <?php if(!empty($sv['avatar'])): ?>
                  <img src="/tkb/assets/img/avatars/<?= htmlspecialchars($sv['avatar']) ?>" style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:1.5px solid rgba(168,85,247,0.3);" alt="Avatar">
                <?php else: ?>
                  <div style="width:34px; height:34px; border-radius:50%; background:rgba(168,85,247,0.2); color:#c084fc; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px; border:1px solid rgba(168,85,247,0.3);">
                    <?= mb_strtoupper(mb_substr($sv['ho_ten'], 0, 1, 'UTF-8'), 'UTF-8') ?>
                  </div>
                <?php endif; ?>
                <div>
                  <span style="font-weight:700; color:#f3e8ff;"><?= htmlspecialchars($sv['ho_ten']) ?></span>
                  <div style="font-size:11px; color:#a79bb7;"><?= htmlspecialchars($sv['khoa'] ?: 'Chưa phân ngành') ?></div>
                </div>
              </div>
            </td>
            <td>
              <span style="padding:4px 10px; border-radius:6px; background:rgba(56,189,248,0.15); color:#38bdf8; font-weight:700; font-size:12px; border:1px solid rgba(56,189,248,0.3);">
                <?= htmlspecialchars($sv['lop']) ?>
              </span>
            </td>
            <td style="text-align:center;">
              <?php if ($qz_score !== null): ?>
                <span class="score-badge <?= $qz_score >= 8 ? 'score-high' : ($qz_score >= 5 ? 'score-mid' : 'score-low') ?>"><?= $qz_score ?> đ</span>
                <div style="font-size:10.5px; color:#a79bb7; margin-top:2px;"><?= $sv['count_quiz'] ?> bài</div>
              <?php else: ?>
                <span class="score-badge score-none">-</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <?php if ($pr_score !== null): ?>
                <span class="score-badge <?= $pr_score >= 8 ? 'score-high' : ($pr_score >= 5 ? 'score-mid' : 'score-low') ?>"><?= $pr_score ?> đ</span>
                <div style="font-size:10.5px; color:#a79bb7; margin-top:2px;"><?= $sv['count_prac'] ?> bài</div>
              <?php else: ?>
                <span class="score-badge score-none">-</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <span style="font-weight:700; color:#f3e8ff;"><?= $sv['count_assign'] ?></span> <span style="font-size:11px; color:#a79bb7;">bài</span>
              <?php if ($sv['avg_assign'] !== null): ?>
                <div style="font-size:11px; color:#38bdf8; font-weight:700;">TB: <?= round($sv['avg_assign'], 1) ?> đ</div>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <?php if ($da_score !== null): ?>
                <span class="score-badge <?= $da_score >= 8 ? 'score-high' : ($da_score >= 5 ? 'score-mid' : 'score-low') ?>"><?= $da_score ?> đ</span>
              <?php else: ?>
                <span class="score-badge score-none">-</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <button type="button" onclick="openStudentGradesModal(<?= $sv['id'] ?>, '<?= htmlspecialchars(addslashes($sv['ho_ten'])) ?>', '<?= htmlspecialchars(addslashes($sv['ma_sv'])) ?>', '<?= htmlspecialchars(addslashes($sv['lop'])) ?>')" class="btn btn-sm" style="background:rgba(16,185,129,0.2); color:#34d399; border:1px solid rgba(16,185,129,0.35); border-radius:8px; padding:4px 10px; font-weight:700; font-size:11.5px;" title="Xem toàn bộ bảng điểm chi tiết">
                <i class="fa-solid fa-eye"></i> Chi tiết
              </button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- ==================== TAB 2: ĐIỂM QUIZ TRẮC NGHIỆM ==================== -->
    <?php if ($cur_tab === 'quiz'): ?>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th style="width:45px; text-align:center;">#</th>
            <th style="width:130px;">Mã SV</th>
            <th>Sinh viên</th>
            <th style="width:110px;">Lớp</th>
            <th>Bài Quiz / Môn học</th>
            <th style="width:80px; text-align:center;">Mã Đề</th>
            <th style="width:90px; text-align:center;">Đúng/Tổng</th>
            <th style="width:85px; text-align:center;">Điểm</th>
            <th style="width:140px;">Thời Gian</th>
            <th style="width:130px; text-align:center;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data_quiz)): ?>
          <tr><td colspan="10" style="text-align:center; color:#94a3b8; padding:40px;">Không có kết quả bài thi trắc nghiệm nào.</td></tr>
          <?php else: foreach ($data_quiz as $i => $att): 
            $sc = (float)$att['score'];
            $total = (int)($att['total_questions'] ?: 10);
            $diem10 = $total > 0 ? round(($sc / $total) * 10, 1) : $sc;
          ?>
          <tr>
            <td style="text-align:center; color:#94a3b8; font-weight:600;"><?= $i+1 ?></td>
            <td><span style="font-family:monospace; font-weight:700; color:#f3e8ff;"><?= htmlspecialchars($att['ma_sv']) ?></span></td>
            <td><strong style="color:#f3e8ff;"><?= htmlspecialchars($att['ho_ten']) ?></strong></td>
            <td><span style="color:#38bdf8; font-weight:700; font-size:12px;"><?= htmlspecialchars($att['lop']) ?></span></td>
            <td>
              <div style="font-weight:700; color:#c084fc;"><?= htmlspecialchars($att['quiz_title']) ?></div>
              <div style="font-size:11px; color:#a79bb7;"><?= htmlspecialchars($att['subject_name'] ?: '-') ?></div>
            </td>
            <td style="text-align:center;"><span style="font-family:monospace; background:rgba(255,255,255,0.08); padding:2px 6px; border-radius:4px; font-size:11px; color:#e9d5ff;"><?= htmlspecialchars($att['ma_de'] ?? 'Chuẩn') ?></span></td>
            <td style="text-align:center; font-weight:800; color:#38bdf8;"><?= $att['score'] ?>/<?= $att['total_questions'] ?></td>
            <td style="text-align:center;">
              <span class="score-badge <?= $diem10 >= 8 ? 'score-high' : ($diem10 >= 5 ? 'score-mid' : 'score-low') ?>"><?= $diem10 ?> đ</span>
            </td>
            <td style="color:#a79bb7; font-size:12px;"><?= $att['attempted_at'] ? date('d/m/Y H:i', strtotime($att['attempted_at'])) : '-' ?></td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:5px; align-items:center;">
                <!-- Xem chi tiết -->
                <button type="button" onclick="viewQuizModal(<?= htmlspecialchars(json_encode($att), ENT_QUOTES, 'UTF-8') ?>)" class="btn btn-sm" style="background:rgba(56,189,248,0.2); color:#38bdf8; border:1px solid rgba(56,189,248,0.35); border-radius:6px; padding:3px 7px; font-size:11px;" title="Xem chi tiết bài thi">
                  <i class="fa-solid fa-eye"></i>
                </button>
                <!-- Sửa điểm & nhận xét -->
                <button type="button" onclick="editQuizModal(<?= $att['id'] ?>, '<?= htmlspecialchars(addslashes($att['ho_ten'])) ?>', '<?= htmlspecialchars(addslashes($att['quiz_title'])) ?>', <?= (int)$att['score'] ?>, <?= (int)$att['total_questions'] ?>, '<?= htmlspecialchars(addslashes($att['nhan_xet'] ?? '')) ?>')" class="btn btn-sm" style="background:rgba(168,85,247,0.2); color:#d8b4fe; border:1px solid rgba(168,85,247,0.35); border-radius:6px; padding:3px 7px; font-size:11px;" title="Sửa điểm & nhận xét">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <!-- Xóa lượt làm bài -->
                <a href="?tab=quiz&action=delete_quiz_attempt&id=<?= $att['id'] ?>" onclick="return confirm('⚠️ Bạn có chắc chắn muốn XÓA kết quả bài thi này của sinh viên <?= htmlspecialchars(addslashes($att['ho_ten'])) ?>?\n\nSau khi xóa, sinh viên có thể làm lại bài kiểm tra này.')" class="btn btn-sm" style="background:rgba(239,68,68,0.2); color:#f87171; border:1px solid rgba(239,68,68,0.35); border-radius:6px; padding:3px 7px; font-size:11px; text-decoration:none;" title="Xóa kết quả thi">
                  <i class="fa-solid fa-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- ==================== TAB 3: THỰC HÀNH LẬP TRÌNH ==================== -->
    <?php if ($cur_tab === 'thuchanh'): ?>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th style="width:45px; text-align:center;">#</th>
            <th style="width:130px;">Mã SV</th>
            <th>Sinh viên</th>
            <th style="width:110px;">Lớp</th>
            <th>Phiên Thực Hành</th>
            <th style="width:90px; text-align:center;">Điểm</th>
            <th>Nhận Xét Của GV/Admin</th>
            <th style="width:140px;">Thời Gian</th>
            <th style="width:140px; text-align:center;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data_prac)): ?>
          <tr><td colspan="9" style="text-align:center; color:#94a3b8; padding:40px;">Không có bài thực hành lập trình nào.</td></tr>
          <?php else: foreach ($data_prac as $i => $pr): 
            $sc = ($pr['score'] !== null) ? (float)$pr['score'] : null;
          ?>
          <tr>
            <td style="text-align:center; color:#94a3b8; font-weight:600;"><?= $i+1 ?></td>
            <td><span style="font-family:monospace; font-weight:700; color:#f3e8ff;"><?= htmlspecialchars($pr['ma_sv']) ?></span></td>
            <td><strong style="color:#f3e8ff;"><?= htmlspecialchars($pr['ho_ten']) ?></strong></td>
            <td><span style="color:#38bdf8; font-weight:700; font-size:12px;"><?= htmlspecialchars($pr['lop']) ?></span></td>
            <td><strong style="color:#34d399;"><?= htmlspecialchars($pr['session_title'] ?: ('Phiên #' . $pr['session_id'])) ?></strong></td>
            <td style="text-align:center;">
              <?php if ($sc !== null): ?>
                <span class="score-badge <?= $sc >= 8 ? 'score-high' : ($sc >= 5 ? 'score-mid' : 'score-low') ?>"><?= $sc ?> đ</span>
              <?php else: ?>
                <span class="score-badge score-none">Chưa chấm</span>
              <?php endif; ?>
            </td>
            <td style="color:#c4b5fd; font-size:12px;"><?= htmlspecialchars($pr['teacher_feedback'] ?: '-') ?></td>
            <td style="color:#a79bb7; font-size:12px;"><?= $pr['submitted_at'] ? date('d/m/Y H:i', strtotime($pr['submitted_at'])) : '-' ?></td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:6px; align-items:center;">
                <a href="/tkb/teacher/cham_code.php?submission_id=<?= $pr['id'] ?>" target="_blank" class="btn btn-sm" style="background:#7c3aed; color:#fff; border-radius:6px; font-size:11px; padding:3px 8px; text-decoration:none;" title="Chấm Code">
                  <i class="fa-solid fa-code"></i> Chấm
                </a>
                <button type="button" onclick="editPracticeModal(<?= $pr['id'] ?>, '<?= htmlspecialchars(addslashes($pr['ho_ten'])) ?>', <?= $sc !== null ? $sc : "''" ?>, '<?= htmlspecialchars(addslashes($pr['teacher_feedback'] ?? '')) ?>')" class="btn btn-sm" style="background:rgba(255,255,255,0.08); color:#f3e8ff; border-radius:6px; font-size:11px; padding:3px 8px;" title="Sửa điểm">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <a href="?tab=thuchanh&action=delete_practice_submission&id=<?= $pr['id'] ?>" onclick="return confirm('⚠️ Bạn có chắc chắn muốn XÓA bài nộp thực hành này của <?= htmlspecialchars(addslashes($pr['ho_ten'])) ?>?')" class="btn btn-sm" style="background:rgba(239,68,68,0.2); color:#f87171; border:1px solid rgba(239,68,68,0.35); border-radius:6px; padding:3px 7px; font-size:11px; text-decoration:none;" title="Xóa bài nộp">
                  <i class="fa-solid fa-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- ==================== TAB 4: BÀI TẬP VỀ NHÀ ==================== -->
    <?php if ($cur_tab === 'baitap'): ?>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th style="width:45px; text-align:center;">#</th>
            <th style="width:130px;">Mã SV</th>
            <th>Sinh viên</th>
            <th style="width:110px;">Lớp</th>
            <th>Tên Bài Tập &amp; Môn Học</th>
            <th style="width:90px; text-align:center;">Điểm</th>
            <th>Nhận Xét</th>
            <th style="width:140px;">Thời Gian Nộp</th>
            <th style="width:130px; text-align:center;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data_assign)): ?>
          <tr><td colspan="9" style="text-align:center; color:#94a3b8; padding:40px;">Không có bài tập nộp nào.</td></tr>
          <?php else: foreach ($data_assign as $i => $as): 
            $gr = ($as['grade'] !== null) ? (float)$as['grade'] : null;
          ?>
          <tr>
            <td style="text-align:center; color:#94a3b8; font-weight:600;"><?= $i+1 ?></td>
            <td><span style="font-family:monospace; font-weight:700; color:#f3e8ff;"><?= htmlspecialchars($as['ma_sv']) ?></span></td>
            <td><strong style="color:#f3e8ff;"><?= htmlspecialchars($as['ho_ten']) ?></strong></td>
            <td><span style="color:#38bdf8; font-weight:700; font-size:12px;"><?= htmlspecialchars($as['lop']) ?></span></td>
            <td>
              <div style="font-weight:700; color:#38bdf8;"><?= htmlspecialchars($as['assignment_title']) ?></div>
              <div style="font-size:11px; color:#a79bb7;">Môn: <?= htmlspecialchars($as['subject_name'] ?: '-') ?> • GV: <?= htmlspecialchars($as['teacher_name'] ?: '-') ?></div>
            </td>
            <td style="text-align:center;">
              <?php if ($gr !== null): ?>
                <span class="score-badge <?= $gr >= 8 ? 'score-high' : ($gr >= 5 ? 'score-mid' : 'score-low') ?>"><?= $gr ?> đ</span>
              <?php else: ?>
                <span class="score-badge score-none">Chờ chấm</span>
              <?php endif; ?>
            </td>
            <td style="color:#c4b5fd; font-size:12px;"><?= htmlspecialchars($as['feedback'] ?: '-') ?></td>
            <td style="color:#a79bb7; font-size:12px;"><?= $as['submitted_at'] ? date('d/m/Y H:i', strtotime($as['submitted_at'])) : '-' ?></td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:6px; align-items:center;">
                <button type="button" onclick="editAssignmentModal(<?= $as['student_id'] ?>, <?= $as['assignment_id'] ?>, '<?= htmlspecialchars(addslashes($as['ho_ten'])) ?>', '<?= htmlspecialchars(addslashes($as['assignment_title'])) ?>', <?= $gr !== null ? $gr : "''" ?>, '<?= htmlspecialchars(addslashes($as['feedback'] ?? '')) ?>')" class="btn btn-sm" style="background:rgba(56,189,248,0.2); color:#38bdf8; border:1px solid rgba(56,189,248,0.35); border-radius:6px; padding:3px 8px; font-size:11px;" title="Sửa điểm">
                  <i class="fa-solid fa-pen-to-square"></i> Sửa
                </button>
                <a href="?tab=baitap&action=delete_assignment_submission&id=<?= $as['id'] ?>" onclick="return confirm('⚠️ Bạn có chắc chắn muốn XÓA bài nộp này của <?= htmlspecialchars(addslashes($as['ho_ten'])) ?>?')" class="btn btn-sm" style="background:rgba(239,68,68,0.2); color:#f87171; border:1px solid rgba(239,68,68,0.35); border-radius:6px; padding:3px 7px; font-size:11px; text-decoration:none;" title="Xóa bài nộp">
                  <i class="fa-solid fa-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- ==================== TAB 5: ĐỒ ÁN / KHÓA LUẬN ==================== -->
    <?php if ($cur_tab === 'doan'): ?>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th style="width:45px; text-align:center;">#</th>
            <th style="width:130px;">Mã SV</th>
            <th>Sinh viên / Nhóm</th>
            <th style="width:110px;">Lớp</th>
            <th>Tên Đề Tài Đồ Án</th>
            <th style="width:140px;">GV Hướng Dẫn</th>
            <th style="width:110px; text-align:center;">Trạng Thái</th>
            <th style="width:85px; text-align:center;">Điểm</th>
            <th style="width:120px; text-align:center;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data_doan)): ?>
          <tr><td colspan="9" style="text-align:center; color:#94a3b8; padding:40px;">Không có đề tài đồ án nào.</td></tr>
          <?php else: foreach ($data_doan as $i => $da): 
            $sc = ($da['diem'] !== null) ? (float)$da['diem'] : null;
          ?>
          <tr>
            <td style="text-align:center; color:#94a3b8; font-weight:600;"><?= $i+1 ?></td>
            <td><span style="font-family:monospace; font-weight:700; color:#f3e8ff;"><?= htmlspecialchars($da['ma_sv']) ?></span></td>
            <td>
              <strong style="color:#f3e8ff;"><?= htmlspecialchars($da['ho_ten']) ?></strong>
              <?php if ($da['ten_nhom']): ?>
                <div style="font-size:11px; color:#ec4899;"><i class="fa-solid fa-users"></i> <?= htmlspecialchars($da['ten_nhom']) ?></div>
              <?php endif; ?>
            </td>
            <td><span style="color:#38bdf8; font-weight:700; font-size:12px;"><?= htmlspecialchars($da['lop']) ?></span></td>
            <td><strong style="color:#f472b6;"><?= htmlspecialchars($da['ten_do_an']) ?></strong></td>
            <td style="color:#a79bb7; font-size:12.5px;"><?= htmlspecialchars($da['teacher_name'] ?: 'Chưa phân công') ?></td>
            <td style="text-align:center;">
              <span style="display:inline-block; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:rgba(236,72,153,0.15); color:#f472b6; border:1px solid rgba(236,72,153,0.3);">
                <?= htmlspecialchars($da['trang_thai'] ?: 'Đang làm') ?>
              </span>
            </td>
            <td style="text-align:center;">
              <?php if ($sc !== null): ?>
                <span class="score-badge <?= $sc >= 8 ? 'score-high' : ($sc >= 5 ? 'score-mid' : 'score-low') ?>"><?= $sc ?> đ</span>
              <?php else: ?>
                <span class="score-badge score-none">-</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:6px; align-items:center;">
                <button type="button" onclick="editProjectModal(<?= $da['id'] ?>, '<?= htmlspecialchars(addslashes($da['ten_do_an'])) ?>', '<?= htmlspecialchars(addslashes($da['trang_thai'])) ?>', <?= $sc !== null ? $sc : "''" ?>, '<?= htmlspecialchars(addslashes($da['nhan_xet'] ?? '')) ?>')" class="btn btn-sm" style="background:rgba(236,72,153,0.2); color:#f472b6; border:1px solid rgba(236,72,153,0.35); border-radius:6px; padding:3px 8px; font-size:11px;" title="Chấm & sửa đồ án">
                  <i class="fa-solid fa-pen"></i> Chấm
                </button>
                <a href="?tab=doan&action=delete_project_grade&id=<?= $da['id'] ?>" onclick="return confirm('⚠️ Bạn có chắc chắn muốn ĐẶT LẠI / XÓA điểm đồ án này?')" class="btn btn-sm" style="background:rgba(239,68,68,0.2); color:#f87171; border:1px solid rgba(239,68,68,0.35); border-radius:6px; padding:3px 7px; font-size:11px; text-decoration:none;" title="Reset điểm đồ án">
                  <i class="fa-solid fa-rotate-left"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div>

</div>

<!-- MODAL XEM CHI TIẾT KẾT QUẢ QUIZ -->
<div class="modal-overlay" id="viewQuizModal">
  <div class="modal-box" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); border-radius:18px; color:#f3e8ff; max-width:550px;">
    <div class="modal-title" style="color:#38bdf8; display:flex; align-items:center; gap:8px;">
      <i class="fa-solid fa-brain"></i> Chi Tiết Kết Quả Bài Thi Quiz
    </div>
    <div class="modal-sub" id="viewQuizMeta" style="white-space:pre-line; line-height:1.4;">Sinh viên: ...</div>
    
    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:16px; margin:16px 0;">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
        <div style="background:rgba(168,85,247,0.12); padding:10px 14px; border-radius:10px; border:1px solid rgba(168,85,247,0.25);">
          <div style="font-size:11px; color:#c4b5fd; text-transform:uppercase; font-weight:700;">Điểm Hệ 10</div>
          <div id="viewQuizDiem10" style="font-size:24px; font-weight:900; color:#34d399; font-family:'Outfit',sans-serif;">0 đ</div>
        </div>
        <div style="background:rgba(56,189,248,0.12); padding:10px 14px; border-radius:10px; border:1px solid rgba(56,189,248,0.25);">
          <div style="font-size:11px; color:#7dd3fc; text-transform:uppercase; font-weight:700;">Số Câu Đúng</div>
          <div id="viewQuizScoreRatio" style="font-size:24px; font-weight:900; color:#38bdf8; font-family:'Outfit',sans-serif;">0 / 0</div>
        </div>
      </div>
      
      <div style="display:flex; justify-content:space-between; font-size:12px; color:#a79bb7; padding:4px 0; border-top:1px solid rgba(255,255,255,0.06);">
        <span>Mã đề thi:</span>
        <span id="viewQuizMaDe" style="font-weight:700; color:#f3e8ff; font-family:monospace;">-</span>
      </div>
      <div style="display:flex; justify-content:space-between; font-size:12px; color:#a79bb7; padding:4px 0;">
        <span>Thời gian nộp bài:</span>
        <span id="viewQuizTime" style="font-weight:600; color:#f3e8ff;">-</span>
      </div>
    </div>

    <div style="margin-bottom:16px;">
      <div style="font-size:12px; font-weight:700; color:#c4b5fd; margin-bottom:6px;"><i class="fa-solid fa-robot"></i> Đánh Giá / Lời Khuyên Của AI &amp; Giảng Viên:</div>
      <div id="viewQuizNhanXet" style="background:#1f1338; border:1px solid rgba(168,85,247,0.25); border-radius:10px; padding:12px 14px; font-size:12.5px; color:#e9d5ff; line-height:1.5; white-space:pre-wrap; max-height:160px; overflow-y:auto;">
        Chưa có nhận xét
      </div>
    </div>

    <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
      <button type="button" class="btn btn-ghost" onclick="toggleModal('viewQuizModal')" style="color:#a79bb7;">Đóng</button>
      <button type="button" class="btn btn-primary" id="btnEditFromView" style="background:#7c3aed; border:none;"><i class="fa-solid fa-pen"></i> Chỉnh Sửa Điểm</button>
    </div>
  </div>
</div>

<!-- MODAL SỬA ĐIỂM & NHẬN XÉT QUIZ -->
<div class="modal-overlay" id="editQuizModal">
  <div class="modal-box" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); border-radius:18px; color:#f3e8ff; max-width:550px;">
    <div class="modal-title" style="color:#c084fc;"><i class="fa-solid fa-pen-to-square"></i> Cập Nhật Kết Quả Quiz (Admin)</div>
    <div class="modal-sub" id="editQuizMeta">Sinh viên: ...</div>
    <form method="POST">
      <input type="hidden" name="action" value="update_quiz_attempt">
      <input type="hidden" name="attempt_id" id="editQuizAttId">
      <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:16px;">
        <div class="form-group">
          <label class="form-label">Số Câu Đúng *</label>
          <input class="form-input" name="score" id="editQuizScore" type="number" min="0" required placeholder="10" oninput="calcQuizDiem10()" style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;">
        </div>
        <div class="form-group">
          <label class="form-label">Tổng Số Câu *</label>
          <input class="form-input" name="total_questions" id="editQuizTotal" type="number" min="1" required placeholder="40" oninput="calcQuizDiem10()" style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;">
        </div>
      </div>
      <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.25); border-radius:8px; padding:8px 12px; margin-bottom:14px; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-size:12px; color:#6ee7b7; font-weight:600;">Điểm Quy Đổi Thang 10:</span>
        <span id="editQuizDiem10Preview" style="font-size:16px; font-weight:800; color:#34d399;">0.0 đ</span>
      </div>
      <div class="form-group">
        <label class="form-label">Lời Nhận Xét / Lời Khuyên</label>
        <textarea class="form-input" name="nhan_xet" id="editQuizNhanXet" rows="4" placeholder="Nhập lời nhận xét hoặc lời khuyên cho sinh viên..." style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;"></textarea>
      </div>
      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('editQuizModal')" style="color:#a79bb7;">Hủy</button>
        <button type="submit" class="btn btn-primary" style="background:#7c3aed; border:none;"><i class="fa-solid fa-floppy-disk"></i> Lưu Kết Quả</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL SỬA ĐIỂM BÀI TẬP -->
<div class="modal-overlay" id="editAssignmentModal">
  <div class="modal-box" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); border-radius:18px; color:#f3e8ff;">
    <div class="modal-title" style="color:#38bdf8;"><i class="fa-solid fa-pen-to-square"></i> Cập Nhật Điểm Bài Tập (Admin)</div>
    <div class="modal-sub" id="editAssMeta">Sinh viên: ...</div>
    <form method="POST">
      <input type="hidden" name="action" value="update_assignment_grade">
      <input type="hidden" name="student_id" id="editAssStudentId">
      <input type="hidden" name="assignment_id" id="editAssId">
      <div class="form-group" style="margin-top:16px;">
        <label class="form-label">Điểm số (Thang điểm 10) *</label>
        <input class="form-input" name="grade" id="editAssGrade" type="number" step="0.1" min="0" max="10" required placeholder="8.5" style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;">
      </div>
      <div class="form-group">
        <label class="form-label">Lời nhận xét / Đánh giá</label>
        <textarea class="form-input" name="feedback" id="editAssFeedback" rows="3" placeholder="Nhập lời nhận xét cho sinh viên..." style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;"></textarea>
      </div>
      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('editAssignmentModal')" style="color:#a79bb7;">Hủy</button>
        <button type="submit" class="btn btn-primary" style="background:#0284c7; border:none;"><i class="fa-solid fa-floppy-disk"></i> Lưu Điểm Số</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL SỬA ĐIỂM THỰC HÀNH -->
<div class="modal-overlay" id="editPracticeModal">
  <div class="modal-box" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); border-radius:18px; color:#f3e8ff;">
    <div class="modal-title" style="color:#10b981;"><i class="fa-solid fa-code"></i> Cập Nhật Điểm Thực Hành (Admin)</div>
    <div class="modal-sub" id="editPracMeta">Sinh viên: ...</div>
    <form method="POST">
      <input type="hidden" name="action" value="update_practice_grade">
      <input type="hidden" name="submission_id" id="editPracSubId">
      <div class="form-group" style="margin-top:16px;">
        <label class="form-label">Điểm Thực Hành (Thang điểm 10) *</label>
        <input class="form-input" name="score" id="editPracScore" type="number" step="0.1" min="0" max="10" required placeholder="9.0" style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;">
      </div>
      <div class="form-group">
        <label class="form-label">Nhận xét của Giáo viên / Admin</label>
        <textarea class="form-input" name="teacher_feedback" id="editPracFeedback" rows="3" placeholder="Code tối ưu, cấu trúc chuẩn..." style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;"></textarea>
      </div>
      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('editPracticeModal')" style="color:#a79bb7;">Hủy</button>
        <button type="submit" class="btn btn-primary" style="background:#10b981; border:none;"><i class="fa-solid fa-floppy-disk"></i> Lưu Điểm</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL SỬA ĐIỂM ĐỒ ÁN -->
<div class="modal-overlay" id="editProjectModal">
  <div class="modal-box" style="background:#140d27; border:1px solid rgba(168,85,247,0.3); border-radius:18px; color:#f3e8ff;">
    <div class="modal-title" style="color:#f472b6;"><i class="fa-solid fa-file-code"></i> Cập Nhật Điểm Đồ Án (Admin)</div>
    <div class="modal-sub" id="editProjMeta">Đề tài: ...</div>
    <form method="POST">
      <input type="hidden" name="action" value="update_project_grade">
      <input type="hidden" name="do_an_id" id="editProjId">
      <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:16px;">
        <div class="form-group">
          <label class="form-label">Điểm Đồ Án (Thang 10)</label>
          <input class="form-input" name="diem" id="editProjDiem" type="number" step="0.1" min="0" max="10" placeholder="8.5" style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;">
        </div>
        <div class="form-group">
          <label class="form-label">Trạng Thái Đồ Án</label>
          <select class="form-select" name="trang_thai" id="editProjStatus" style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;">
            <option value="Chưa bắt đầu">Chưa bắt đầu</option>
            <option value="Đang thực hiện">Đang thực hiện</option>
            <option value="Chờ duyệt báo cáo">Chờ duyệt báo cáo</option>
            <option value="Đã hoàn thành">Đã hoàn thành</option>
            <option value="Đã chấm điểm">Đã chấm điểm</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Đánh giá &amp; Nhận xét của Hội đồng / Admin</label>
        <textarea class="form-input" name="nhan_xet" id="editProjNhanXet" rows="3" placeholder="Đề tài hoàn thành tốt các mục tiêu..." style="background:#1f1338; border:1px solid rgba(168,85,247,0.3); color:#fff;"></textarea>
      </div>
      <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
        <button type="button" class="btn btn-ghost" onclick="toggleModal('editProjectModal')" style="color:#a79bb7;">Hủy</button>
        <button type="submit" class="btn btn-primary" style="background:#ec4899; border:none;"><i class="fa-solid fa-floppy-disk"></i> Lưu Đánh Giá</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL XEM CHI TIẾT TOÀN BỘ ĐIỂM CỦA 1 SINH VIÊN (AJAX POPUP) -->
<div class="modal-overlay" id="gradesModal" style="z-index: 99999;">
  <div class="modal-box" style="max-width: 900px; width: 95%; max-height: 85vh; display: flex; flex-direction: column; padding: 0; overflow: hidden; background: #140d27; border: 1px solid rgba(168,85,247,0.3); box-shadow: 0 20px 60px rgba(0,0,0,0.7); border-radius: 20px;">
    
    <div style="padding: 20px 24px; border-bottom: 1px solid rgba(168,85,247,0.18); display: flex; align-items: center; justify-content: space-between; background: rgba(20,13,38,0.95);">
      <div>
        <div style="font-family:'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #f3e8ff; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-graduation-cap" style="color: #10b981;"></i> Bảng Điểm Toàn Diện Của Sinh Viên
        </div>
        <div id="gradesStudentMeta" style="font-size: 12.5px; color: #c4b5fd; margin-top: 4px; font-weight: 600;">
          Đang tải dữ liệu...
        </div>
      </div>
      <button type="button" onclick="toggleModal('gradesModal')" style="background: rgba(255,255,255,0.08); border: none; width: 32px; height: 32px; border-radius: 8px; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div id="gradesModalContent" style="padding: 24px; overflow-y: auto; flex: 1; color: #e9d5ff;">
      <div style="text-align: center; padding: 40px 20px; color: #a79bb7;">
        <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 28px; color: #a855f7; margin-bottom: 12px;"></i>
        <div>Đang truy vấn bảng điểm toàn diện của sinh viên...</div>
      </div>
    </div>

    <div style="padding: 14px 24px; border-top: 1px solid rgba(168,85,247,0.18); background: rgba(20,13,38,0.95); display: flex; justify-content: flex-end; gap: 10px;">
      <button type="button" class="btn btn-ghost" onclick="toggleModal('gradesModal')" style="background: rgba(168,85,247,0.15); color: #e9d5ff; border: 1px solid rgba(168,85,247,0.25);">Đóng</button>
    </div>

  </div>
</div>

<script>
function toggleModal(id){
  const m=document.getElementById(id);
  if(m) m.classList.toggle('show');
}

var currentQuizAttemptData = null;

function viewQuizModal(att) {
  if (!att) return;
  currentQuizAttemptData = att;
  var metaEl = document.getElementById('viewQuizMeta');
  if (metaEl) {
    metaEl.innerText = 'Sinh viên: ' + (att.ho_ten || '') + ' (' + (att.ma_sv || '') + ') • Lớp: ' + (att.lop || '') + '\nBài thi: ' + (att.quiz_title || '') + (att.subject_name ? ' (' + att.subject_name + ')' : '');
  }
  var sc = parseFloat(att.score) || 0;
  var total = parseInt(att.total_questions) || 10;
  var d10 = total > 0 ? ((sc / total) * 10).toFixed(1) : sc;
  
  document.getElementById('viewQuizDiem10').innerText = d10 + ' đ';
  document.getElementById('viewQuizScoreRatio').innerText = sc + ' / ' + total + ' câu';
  document.getElementById('viewQuizMaDe').innerText = att.ma_de || 'Chuẩn';
  document.getElementById('viewQuizTime').innerText = att.attempted_at ? att.attempted_at : '-';
  document.getElementById('viewQuizNhanXet').innerText = att.nhan_xet || 'Chưa có nhận xét từ hệ thống.';
  
  var btnEdit = document.getElementById('btnEditFromView');
  if (btnEdit) {
    btnEdit.onclick = function() {
      toggleModal('viewQuizModal');
      editQuizModal(att.id, att.ho_ten, att.quiz_title, sc, total, att.nhan_xet || '');
    };
  }
  
  toggleModal('viewQuizModal');
}

function editQuizModal(attemptId, studentName, quizTitle, score, totalQuestions, nhanXet) {
  document.getElementById('editQuizAttId').value = attemptId;
  document.getElementById('editQuizMeta').innerText = 'Sinh viên: ' + studentName + ' • Bài thi: ' + quizTitle;
  document.getElementById('editQuizScore').value = score;
  document.getElementById('editQuizTotal').value = totalQuestions || 40;
  document.getElementById('editQuizNhanXet').value = nhanXet;
  calcQuizDiem10();
  toggleModal('editQuizModal');
}

function calcQuizDiem10() {
  var sc = parseFloat(document.getElementById('editQuizScore').value) || 0;
  var total = parseFloat(document.getElementById('editQuizTotal').value) || 1;
  var d10 = total > 0 ? ((sc / total) * 10).toFixed(1) : 0;
  var prevEl = document.getElementById('editQuizDiem10Preview');
  if (prevEl) prevEl.innerText = d10 + ' đ';
}

function editAssignmentModal(studentId, assignmentId, studentName, title, currentGrade, feedback) {
  document.getElementById('editAssStudentId').value = studentId;
  document.getElementById('editAssId').value = assignmentId;
  document.getElementById('editAssMeta').innerText = 'Sinh viên: ' + studentName + ' • Bài tập: ' + title;
  document.getElementById('editAssGrade').value = currentGrade;
  document.getElementById('editAssFeedback').value = feedback;
  toggleModal('editAssignmentModal');
}

function editPracticeModal(submissionId, studentName, currentScore, feedback) {
  document.getElementById('editPracSubId').value = submissionId;
  document.getElementById('editPracMeta').innerText = 'Sinh viên: ' + studentName;
  document.getElementById('editPracScore').value = currentScore;
  document.getElementById('editPracFeedback').value = feedback;
  toggleModal('editPracticeModal');
}

function editProjectModal(doAnId, title, status, diem, nhanXet) {
  document.getElementById('editProjId').value = doAnId;
  document.getElementById('editProjMeta').innerText = 'Đề tài: ' + title;
  document.getElementById('editProjDiem').value = diem;
  document.getElementById('editProjStatus').value = status || 'Đang thực hiện';
  document.getElementById('editProjNhanXet').value = nhanXet;
  toggleModal('editProjectModal');
}

function openStudentGradesModal(studentId, name, msv, lop) {
  var metaEl = document.getElementById('gradesStudentMeta');
  var contentEl = document.getElementById('gradesModalContent');
  if (metaEl) {
    metaEl.innerHTML = '<span style="color:#f3e8ff; font-weight:700;">' + name + '</span> • Mã SV: <span style="font-family:monospace; background:rgba(168,85,247,0.2); padding:2px 6px; border-radius:4px; color:#c084fc;">' + msv + '</span> • Lớp: <span style="color:#38bdf8;">' + (lop || 'Chưa phân lớp') + '</span>';
  }
  if (contentEl) {
    contentEl.innerHTML = '<div style="text-align: center; padding: 40px 20px; color: #a79bb7;">' +
      '<i class="fa-solid fa-circle-notch fa-spin" style="font-size: 28px; color: #a855f7; margin-bottom: 12px;"></i>' +
      '<div>Đang truy vấn bảng điểm toàn diện của sinh viên...</div>' +
    '</div>';
  }
  
  toggleModal('gradesModal');

  fetch('/tkb/api/get_student_grades.php?student_id=' + studentId)
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (!data || !data.success) {
        contentEl.innerHTML = '<div style="text-align:center; padding:30px; color:#f87171;"><i class="fa-solid fa-triangle-exclamation" style="font-size:24px; margin-bottom:8px;"></i><div>' + (data && data.error ? data.error : 'Không thể lấy dữ liệu điểm của sinh viên!') + '</div></div>';
        return;
      }

      var quizzes = data.quiz_attempts || [];
      var practice = data.practice_submissions || [];
      var assignments = data.assignment_submissions || [];
      var projects = data.projects || [];

      var quizAvg = '-';
      if (quizzes.length > 0) {
        var totalQ = 0;
        quizzes.forEach(function(q) { totalQ += parseFloat(q.score) || 0; });
        quizAvg = (totalQ / quizzes.length).toFixed(1) + ' đ';
      }

      var pracAvg = '-';
      var pracGradedCount = 0;
      var pracTotal = 0;
      practice.forEach(function(p) {
        if (p.score !== null && p.score !== undefined && p.score !== '') {
          pracTotal += parseFloat(p.score) || 0;
          pracGradedCount++;
        }
      });
      if (pracGradedCount > 0) {
        pracAvg = (pracTotal / pracGradedCount).toFixed(1) + ' đ';
      }

      var html = '';

      // KPI Summary
      html += '<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:24px;">' +
        '<div style="background: rgba(168,85,247,0.12); border: 1px solid rgba(168,85,247,0.25); border-radius:14px; padding:14px 16px;">' +
          '<div style="font-size:11.5px; color:#c4b5fd; font-weight:700;"><i class="fa-solid fa-brain"></i> Trắc Nghiệm Quiz</div>' +
          '<div style="font-size:22px; font-weight:800; color:#f3e8ff; margin:6px 0 2px;">' + quizzes.length + ' <span style="font-size:12px; color:#a79bb7; font-weight:600;">lượt</span></div>' +
          '<div style="font-size:11px; color:#34d399; font-weight:700;">Điểm TB: ' + quizAvg + '</div>' +
        '</div>' +
        '<div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); border-radius:14px; padding:14px 16px;">' +
          '<div style="font-size:11.5px; color:#6ee7b7; font-weight:700;"><i class="fa-solid fa-code"></i> Thực Hành Lập Trình</div>' +
          '<div style="font-size:22px; font-weight:800; color:#f3e8ff; margin:6px 0 2px;">' + practice.length + ' <span style="font-size:12px; color:#a79bb7; font-weight:600;">bài nộp</span></div>' +
          '<div style="font-size:11px; color:#34d399; font-weight:700;">Điểm TB: ' + pracAvg + '</div>' +
        '</div>' +
        '<div style="background: rgba(56,189,248,0.12); border: 1px solid rgba(56,189,248,0.25); border-radius:14px; padding:14px 16px;">' +
          '<div style="font-size:11.5px; color:#7dd3fc; font-weight:700;"><i class="fa-solid fa-pen-to-square"></i> Bài Tập Về Nhà</div>' +
          '<div style="font-size:22px; font-weight:800; color:#f3e8ff; margin:6px 0 2px;">' + assignments.length + ' <span style="font-size:12px; color:#a79bb7; font-weight:600;">bài</span></div>' +
          '<div style="font-size:11px; color:#38bdf8; font-weight:700;">Đã chấm: ' + assignments.filter(function(a){ return a.grade !== null; }).length + ' bài</div>' +
        '</div>' +
        '<div style="background: rgba(236,72,153,0.12); border: 1px solid rgba(236,72,153,0.25); border-radius:14px; padding:14px 16px;">' +
          '<div style="font-size:11.5px; color:#f472b6; font-weight:700;"><i class="fa-solid fa-file-code"></i> Đồ Án / Khóa Luận</div>' +
          '<div style="font-size:22px; font-weight:800; color:#f3e8ff; margin:6px 0 2px;">' + projects.length + ' <span style="font-size:12px; color:#a79bb7; font-weight:600;">đề tài</span></div>' +
          '<div style="font-size:11px; color:#ec4899; font-weight:700;">' + (projects.length > 0 ? (projects[0].trang_thai || 'Đang thực hiện') : 'Chưa phân công') + '</div>' +
        '</div>' +
      '</div>';

      // Practice Section
      html += '<div style="margin-bottom:24px;">' +
        '<div style="font-size:14px; font-weight:800; color:#34d399; margin-bottom:10px;"><i class="fa-solid fa-code"></i> 1. Thực Hành Lập Trình (' + practice.length + ')</div>';
      if (practice.length === 0) {
        html += '<div style="background:rgba(255,255,255,0.03); padding:12px; border-radius:10px; font-size:12px; color:#94a3b8; text-align:center;">Chưa nộp bài thực hành nào.</div>';
      } else {
        html += '<div style="overflow-x:auto;"><table style="width:100%; border-collapse:collapse; font-size:12.5px;">' +
          '<thead><tr style="background:rgba(16,185,129,0.15); color:#6ee7b7; text-align:left;">' +
            '<th style="padding:8px 12px; border-radius:8px 0 0 8px;">Phiên Thực Hành</th>' +
            '<th style="padding:8px 12px; width:120px;">Thời Gian</th>' +
            '<th style="padding:8px 12px; width:80px; text-align:center;">Điểm</th>' +
            '<th style="padding:8px 12px;">Nhận Xét</th>' +
            '<th style="padding:8px 12px; width:90px; text-align:center; border-radius:0 8px 8px 0;">Xem Code</th>' +
          '</tr></thead><tbody>';
        practice.forEach(function(p) {
          var scoreBadge = (p.score !== null && p.score !== undefined && p.score !== '') ? ('<span style="font-weight:800; color:#34d399; background:rgba(16,185,129,0.2); padding:3px 8px; border-radius:6px;">' + p.score + ' đ</span>') : '<span style="color:#94a3b8; font-size:11px;">Chưa chấm</span>';
          html += '<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">' +
            '<td style="padding:10px 12px; font-weight:700; color:#f3e8ff;">' + (p.session_title || ('Phiên #' + p.session_id)) + '</td>' +
            '<td style="padding:10px 12px; color:#a79bb7; font-size:11.5px;">' + (p.submitted_at ? p.submitted_at.substring(0, 16) : '-') + '</td>' +
            '<td style="padding:10px 12px; text-align:center;">' + scoreBadge + '</td>' +
            '<td style="padding:10px 12px; color:#c4b5fd; font-size:12px;">' + (p.teacher_feedback || '<i style="color:#64748b;">Chưa có nhận xét</i>') + '</td>' +
            '<td style="padding:10px 12px; text-align:center;"><a href="/tkb/teacher/cham_code.php?submission_id=' + p.id + '" target="_blank" style="background:#7c3aed; color:#fff; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700; text-decoration:none; display:inline-block;"><i class="fa-solid fa-code"></i> Chấm</a></td>' +
          '</tr>';
        });
        html += '</tbody></table></div>';
      }
      html += '</div>';

      // Quiz Section
      html += '<div style="margin-bottom:24px;">' +
        '<div style="font-size:14px; font-weight:800; color:#c084fc; margin-bottom:10px;"><i class="fa-solid fa-brain"></i> 2. Trắc Nghiệm Quiz (' + quizzes.length + ')</div>';
      if (quizzes.length === 0) {
        html += '<div style="background:rgba(255,255,255,0.03); padding:12px; border-radius:10px; font-size:12px; color:#94a3b8; text-align:center;">Chưa làm bài Quiz nào.</div>';
      } else {
        html += '<div style="overflow-x:auto;"><table style="width:100%; border-collapse:collapse; font-size:12.5px;">' +
          '<thead><tr style="background:rgba(168,85,247,0.15); color:#d8b4fe; text-align:left;">' +
            '<th style="padding:8px 12px; border-radius:8px 0 0 8px;">Bài Quiz</th>' +
            '<th style="padding:8px 12px; width:80px; text-align:center;">Mã Đề</th>' +
            '<th style="padding:8px 12px; width:90px; text-align:center;">Đúng/Tổng</th>' +
            '<th style="padding:8px 12px; width:80px; text-align:center;">Điểm</th>' +
            '<th style="padding:8px 12px; width:120px;">Ngày Làm</th>' +
            '<th style="padding:8px 12px; border-radius:0 8px 8px 0;">Nhận Xét</th>' +
          '</tr></thead><tbody>';
        quizzes.forEach(function(q) {
          html += '<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">' +
            '<td style="padding:10px 12px; font-weight:700; color:#f3e8ff;">' + (q.quiz_title || 'Bài Quiz') + '<div style="font-size:11px; color:#a79bb7;">' + (q.subject_name || '') + '</div></td>' +
            '<td style="padding:10px 12px; text-align:center;"><span style="font-family:monospace; background:rgba(255,255,255,0.08); padding:2px 6px; border-radius:4px; font-size:11px;">' + (q.ma_de || 'Chuẩn') + '</span></td>' +
            '<td style="padding:10px 12px; text-align:center; font-weight:700; color:#38bdf8;">' + (q.score || 0) + '/' + (q.total_questions || 0) + '</td>' +
            '<td style="padding:10px 12px; text-align:center;"><span style="font-weight:800; color:#34d399; background:rgba(16,185,129,0.2); padding:3px 8px; border-radius:6px;">' + (q.score || 0) + ' đ</span></td>' +
            '<td style="padding:10px 12px; color:#a79bb7; font-size:11.5px;">' + (q.attempted_at ? q.attempted_at.substring(0, 16) : '-') + '</td>' +
            '<td style="padding:10px 12px; color:#c4b5fd; font-size:11.5px;">' + (q.nhan_xet || '<i style="color:#64748b;">Đã nộp bài</i>') + '</td>' +
          '</tr>';
        });
        html += '</tbody></table></div>';
      }
      html += '</div>';

      // Assignments & Projects Grid
      html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">' +
        '<div>' +
          '<div style="font-size:14px; font-weight:800; color:#38bdf8; margin-bottom:10px;"><i class="fa-solid fa-pen-to-square"></i> 3. Bài Tập (' + assignments.length + ')</div>';
      if (assignments.length === 0) {
        html += '<div style="background:rgba(255,255,255,0.03); padding:12px; border-radius:10px; font-size:12px; color:#94a3b8; text-align:center;">Chưa nộp bài tập nào.</div>';
      } else {
        html += '<div style="display:flex; flex-direction:column; gap:8px;">';
        assignments.forEach(function(a) {
          var gradeDisplay = (a.grade !== null && a.grade !== undefined) ? ('<span style="color:#34d399; font-weight:800; background:rgba(16,185,129,0.2); padding:2px 6px; border-radius:4px;">' + a.grade + ' đ</span>') : '<span style="color:#94a3b8; font-size:11px;">Chờ chấm</span>';
          html += '<div style="background:rgba(56,189,248,0.06); border:1px solid rgba(56,189,248,0.15); border-radius:10px; padding:10px 12px;">' +
            '<div style="display:flex; justify-content:space-between; align-items:center;">' +
              '<strong style="font-size:12.5px; color:#f3e8ff;">' + (a.assignment_title || 'Bài tập') + '</strong>' +
              gradeDisplay +
            '</div>' +
            '<div style="font-size:11px; color:#94a3b8; margin-top:2px;">Môn: ' + (a.subject_name || '-') + ' • GV: ' + (a.teacher_name || '-') + '</div>' +
            (a.feedback ? ('<div style="font-size:11.5px; color:#c4b5fd; margin-top:4px;">' + a.feedback + '</div>') : '') +
          '</div>';
        });
        html += '</div>';
      }
      html += '</div>' +
      '<div>' +
        '<div style="font-size:14px; font-weight:800; color:#f472b6; margin-bottom:10px;"><i class="fa-solid fa-file-code"></i> 4. Đồ Án (' + projects.length + ')</div>';
      if (projects.length === 0) {
        html += '<div style="background:rgba(255,255,255,0.03); padding:12px; border-radius:10px; font-size:12px; color:#94a3b8; text-align:center;">Chưa có đề tài đồ án.</div>';
      } else {
        html += '<div style="display:flex; flex-direction:column; gap:8px;">';
        projects.forEach(function(pr) {
          var pGrade = (pr.diem !== null && pr.diem !== undefined) ? ('<span style="color:#34d399; font-weight:800; background:rgba(16,185,129,0.2); padding:2px 6px; border-radius:4px;">' + pr.diem + ' đ</span>') : '<span style="color:#94a3b8; font-size:11px;">Chờ chấm</span>';
          html += '<div style="background:rgba(236,72,153,0.06); border:1px solid rgba(236,72,153,0.15); border-radius:10px; padding:10px 12px;">' +
            '<div style="display:flex; justify-content:space-between; align-items:center;">' +
              '<strong style="font-size:12.5px; color:#f3e8ff;">' + (pr.ten_do_an || 'Đề tài') + '</strong>' +
              pGrade +
            '</div>' +
            '<div style="font-size:11px; color:#94a3b8; margin-top:2px;">Trạng thái: <span style="color:#f472b6; font-weight:700;">' + (pr.trang_thai || 'Đang làm') + '</span> • GVHD: ' + (pr.teacher_name || '-') + '</div>' +
            (pr.nhan_xet ? ('<div style="font-size:11.5px; color:#c4b5fd; margin-top:4px;">' + pr.nhan_xet + '</div>') : '') +
          '</div>';
        });
        html += '</div>';
      }
      html += '</div></div>';

      contentEl.innerHTML = html;
    })
    .catch(function(err) {
      contentEl.innerHTML = '<div style="text-align:center; padding:30px; color:#f87171;"><i class="fa-solid fa-circle-exclamation" style="font-size:24px; margin-bottom:8px;"></i><div>Lỗi kết nối máy chủ khi tải bảng điểm!</div></div>';
    });
}
</script>
</body>
</html>
<?php
$db->close();
?>
