<?php
require_once '../config.php';
requireStudent();
$db = getDB();
$sv_id = $_SESSION['student_id'] ?? 0;

// Fetch student info
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $sv_id);
$stmt->execute();
$sv = $stmt->get_result()->fetch_assoc();
$lop = $sv['lop'] ?? '';
$khoa = $sv['khoa'] ?? '';

// 1. Get Subject Grades (diem table) - DELETED
$grades = [];
$gpa = 0;

// 2. Get Attendance Statistics (diem_danh table)
$attendance_summary = [];
$total_present = 0;
$total_classes = 0;
if ($sv_id) {
    $stmt_att = $db->prepare("
        SELECT m.ten_mon, 
               SUM(CASE WHEN dd.trang_thai = 'Có mặt' THEN 1 ELSE 0 END) as present_count,
               COUNT(dd.id) as total_count
        FROM diem_danh dd
        JOIN mon_hoc m ON dd.mon_hoc_id = m.id
        WHERE dd.student_id = ?
        GROUP BY dd.mon_hoc_id
    ");
    $stmt_att->bind_param("i", $sv_id);
    $stmt_att->execute();
    $attendance_summary = $stmt_att->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($attendance_summary as $att) {
        $total_present += $att['present_count'];
        $total_classes += $att['total_count'];
    }
}
$attendance_rate = $total_classes > 0 ? round(($total_present / $total_classes) * 100, 1) : 100;

// 3. Get Assignments Progress (assignments & submissions)
$total_assigns = 0;
$submitted_assigns = 0;
if ($lop) {
    // Count total assignments for class
    $stmt_ta = $db->prepare("SELECT COUNT(*) FROM assignments WHERE lop = ?");
    $stmt_ta->bind_param("s", $lop);
    $stmt_ta->execute();
    $total_assigns = $stmt_ta->get_result()->fetch_row()[0];
    
    // Count submitted
    $stmt_sa = $db->prepare("
        SELECT COUNT(s.id) 
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        WHERE s.student_id = ? AND a.lop = ?
    ");
    $stmt_sa->bind_param("is", $sv_id, $lop);
    $stmt_sa->execute();
    $submitted_assigns = $stmt_sa->get_result()->fetch_row()[0];
}

// 4. Get Quiz Attempts
$attempts = [];
if ($sv_id) {
    $stmt_qa = $db->prepare("
        SELECT qa.*, q.tieu_de, m.ten_mon
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        JOIN mon_hoc m ON q.mon_hoc_id = m.id
        WHERE qa.student_id = ?
        ORDER BY qa.id DESC
    ");
    $stmt_qa->bind_param("i", $sv_id);
    $stmt_qa->execute();
    $attempts = $stmt_qa->get_result()->fetch_all(MYSQLI_ASSOC);
}

// 5. Get Coding Practice Submissions & Grades
$practice_results = [];
if ($sv_id) {
    $stmt_pr = $db->prepare("
        SELECT ps.id AS session_id, ps.mo_ta, ps.start_time, ps.end_time,
               sub.diem, sub.nhan_xet, sub.submitted_at
        FROM practice_sessions ps
        LEFT JOIN practice_submissions sub ON ps.id = sub.session_id AND sub.student_id = ?
        WHERE ps.lop = ? OR ps.lop = 'ALL'
        ORDER BY ps.start_time DESC
    ");
    $stmt_pr->bind_param("is", $sv_id, $lop);
    $stmt_pr->execute();
    $practice_results = $stmt_pr->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_pr->close();
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theo dõi tiến độ - Sinh viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        .stat-val {
            font-size: 28px;
            font-weight: 900;
            color: var(--accent);
            margin: 8px 0;
        }
        .stat-lbl {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text2);
        }
        .progress-section {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 30px;
            align-items: start;
        }
    </style>
</head>
<body>
    <?php include '../includes/student_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-chart-line" style="color:var(--accent)"></i> Báo Cáo Tiến Độ Học Tập</h1>
            <p style="color: var(--text2); margin-top: 5px;">Thống kê chuyên cần điểm danh, và tỉ lệ nộp bài tập</p>
        </div>
    </div>

    <!-- Overall Statistics Summary -->
    <div class="stat-grid">

        <div class="stat-card">
            <div style="color: #10b981; font-size: 20px;"><i class="fa-solid fa-user-check"></i></div>
            <div class="stat-val"><?= $attendance_rate ?>%</div>
            <div class="stat-lbl">Tỉ lệ chuyên cần</div>
        </div>
        <div class="stat-card">
            <div style="color: #3b82f6; font-size: 20px;"><i class="fa-solid fa-file-pen"></i></div>
            <div class="stat-val"><?= $submitted_assigns ?> / <?= $total_assigns ?></div>
            <div class="stat-lbl">Bài tập hoàn thành</div>
        </div>
        <div class="stat-card">
            <div style="color: #f59e0b; font-size: 20px;"><i class="fa-solid fa-brain"></i></div>
            <div class="stat-val"><?= count($attempts) ?></div>
            <div class="stat-lbl">Bài Quiz đã làm</div>
        </div>
    </div>

    <div class="progress-section">
        <!-- Left: Academic Transcript & Attendance Detailed -->
        <div style="display:flex; flex-direction:column; gap:24px;">


            <!-- Coding Practice Results -->
            <div class="card" id="ket-qua-code">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-code"></i> Kết quả thực hành Code / Thi lập trình</span>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($practice_results)): ?>
                        <p style="text-align: center; color: var(--text2); padding: 25px;">Chưa có dữ liệu bài thực hành nào cho lớp của bạn.</p>
                    <?php else: ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background:rgba(0,0,0,0.01);">
                                    <th style="padding: 12px 15px; text-align: left;">Bài thực hành / Kiểm tra</th>
                                    <th style="padding: 12px 15px; text-align: center; width: 110px;">Trạng thái</th>
                                    <th style="padding: 12px 15px; text-align: center; width: 80px;">Điểm số</th>
                                    <th style="padding: 12px 15px; text-align: left; min-width: 150px;">Nhận xét của GV</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($practice_results as $pr): ?>
                                    <tr style="border-bottom: 1px solid var(--border);">
                                        <td style="padding: 12px 15px;">
                                            <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($pr['mo_ta']) ?></div>
                                            <div style="font-size: 11px; color: var(--text2); margin-top: 3px;">
                                                Hạn chót: <?= date('d/m/Y H:i', strtotime($pr['end_time'])) ?>
                                            </div>
                                        </td>
                                        <td style="padding: 12px 15px; text-align: center;">
                                            <?php if ($pr['submitted_at']): ?>
                                                <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; font-weight: 700; font-size: 11px; border: none; padding: 3px 8px;">Đã nộp</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; font-weight: 700; font-size: 11px; border: none; padding: 3px 8px;">Chưa nộp</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px 15px; text-align: center; font-weight: 800; color: var(--accent); font-size: 15px;">
                                            <?= $pr['diem'] !== null ? number_format($pr['diem'], 1) : '<span style="color:var(--text2); font-weight:normal;">-</span>' ?>
                                        </td>
                                        <td style="padding: 12px 15px; font-size: 13px; color: var(--text);">
                                            <?= $pr['nhan_xet'] ? htmlspecialchars($pr['nhan_xet']) : '<span style="color:var(--text2); font-style:italic;">Chưa có nhận xét</span>' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Attendance checkin detailed -->
            <div class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-clipboard-user"></i> Thống kê chuyên cần chi tiết</span>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($attendance_summary)): ?>
                        <p style="text-align: center; color: var(--text2); padding: 25px;">Chưa có dữ liệu điểm danh.</p>
                    <?php else: ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background:rgba(0,0,0,0.01);">
                                    <th style="padding: 12px 15px; text-align: left;">Môn học</th>
                                    <th style="padding: 12px 15px; text-align: center; width: 120px;">Số buổi có mặt</th>
                                    <th style="padding: 12px 15px; text-align: center; width: 120px;">Tỉ lệ chuyên cần</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance_summary as $att): 
                                    $rate = $att['total_count'] > 0 ? round(($att['present_count'] / $att['total_count']) * 100) : 100;
                                ?>
                                    <tr style="border-bottom: 1px solid var(--border);">
                                        <td style="padding: 12px 15px; font-weight: 700; color: var(--text);"><?= htmlspecialchars($att['ten_mon']) ?></td>
                                        <td style="padding: 12px 15px; text-align: center; color: var(--text2); font-weight:600;"><?= $att['present_count'] ?> / <?= $att['total_count'] ?> buổi</td>
                                        <td style="padding: 12px 15px; text-align: center; font-weight:800; color: <?= $rate >= 80 ? '#10b981' : '#ef4444' ?>;"><?= $rate ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Quiz logs -->
        <div class="card">
            <div class="card-head">
                <span class="card-title"><i class="fa-solid fa-history"></i> Lịch sử làm bài trắc nghiệm Quiz</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($attempts)): ?>
                    <p style="text-align: center; color: var(--text2); padding: 40px;">Bạn chưa thực hiện bài Quiz trắc nghiệm nào.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background:rgba(0,0,0,0.01);">
                                <th style="padding: 12px 15px; text-align: left;">Bài Quiz / Học phần</th>
                                <th style="padding: 12px 15px; text-align: center; width: 100px;">Kết quả</th>
                                <th style="padding: 12px 15px; text-align: center; width: 100px;">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attempts as $at): ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 12px 15px;">
                                        <div style="font-weight: 700; color: var(--text); font-size:13.5px;"><?= htmlspecialchars($at['tieu_de']) ?></div>
                                        <div style="font-size: 11px; color: var(--text2); margin-top: 3px;"><?= htmlspecialchars($at['ten_mon']) ?></div>
                                    </td>
                                    <td style="padding: 12px 15px; text-align: center; font-weight:800; color: var(--accent); font-size:14.5px;">
                                        <?= round(($at['score'] / $at['total_questions']) * 10, 2) ?> / 10
                                    </td>
                                    <td style="padding: 12px 15px; text-align: center; color: var(--text2); font-size: 11.5px;">
                                        <?= date('d/m H:i', strtotime($at['attempted_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
