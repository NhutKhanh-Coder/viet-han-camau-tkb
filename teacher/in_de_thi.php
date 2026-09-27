<?php
require_once '../config.php';
requireTeacher();
$db = getDB();
$gv_id = $_SESSION['giang_vien_id'] ?? 0;

$quiz_id = (int)($_GET['quiz_id'] ?? 0);
$code_id = (int)($_GET['code_id'] ?? 0);
$mode = $_GET['mode'] ?? 'single'; // single, all, matrix, answers

if (!$quiz_id) {
    die("Thiếu thông tin bài kiểm tra Quiz.");
}

// Check ownership of the quiz
$stmt_chk = $db->prepare("
    SELECT q.*, m.ten_mon, m.ma_mon 
    FROM quizzes q 
    JOIN mon_hoc m ON q.mon_hoc_id = m.id 
    WHERE q.id = ? AND q.giang_vien_id = ?
");
$stmt_chk->bind_param("ii", $quiz_id, $gv_id);
$stmt_chk->execute();
$quiz = $stmt_chk->get_result()->fetch_assoc();

if (!$quiz) {
    die("Không tìm thấy bộ Quiz hoặc bạn không có quyền truy cập.");
}

// Fetch exam codes
$exam_codes = [];
if ($code_id) {
    $stmt_c = $db->prepare("SELECT * FROM quiz_exam_codes WHERE id = ? AND quiz_id = ?");
    $stmt_c->bind_param("ii", $code_id, $quiz_id);
    $stmt_c->execute();
    $row = $stmt_c->get_result()->fetch_assoc();
    if ($row) {
        $exam_codes[] = $row;
    }
} else {
    $stmt_c = $db->prepare("SELECT * FROM quiz_exam_codes WHERE quiz_id = ? ORDER BY ma_de ASC");
    $stmt_c->bind_param("i", $quiz_id);
    $stmt_c->execute();
    $exam_codes = $stmt_c->get_result()->fetch_all(MYSQLI_ASSOC);
}

if (empty($exam_codes) && $mode !== 'raw_base') {
    // If no exam codes generated yet, use base questions as Code 101
    $stmt_qs = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
    $stmt_qs->bind_param("i", $quiz_id);
    $stmt_qs->execute();
    $raw_qs = $stmt_qs->get_result()->fetch_all(MYSQLI_ASSOC);
    $formatted = [];
    foreach ($raw_qs as $idx => $rq) {
        $formatted[] = [
            'stt' => $idx + 1,
            'id' => $rq['id'],
            'cau_hoi' => $rq['cau_hoi'],
            'dap_an_a' => $rq['dap_an_a'],
            'dap_an_b' => $rq['dap_an_b'],
            'dap_an_c' => $rq['dap_an_c'],
            'dap_an_d' => $rq['dap_an_d'],
            'dap_an_dung' => $rq['dap_an_dung']
        ];
    }
    $exam_codes[] = [
        'id' => 0,
        'quiz_id' => $quiz_id,
        'ma_de' => 'GỐC (101)',
        'title' => 'Mã đề Gốc - ' . $quiz['tieu_de'],
        'question_count' => count($formatted),
        'matrix_data' => json_encode($formatted, JSON_UNESCAPED_UNICODE)
    ];
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In Đề Thi &amp; Đáp Án - <?= htmlspecialchars($quiz['tieu_de']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Times+New+Roman&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13pt;
            color: #000;
            background: #e2e8f0;
            line-height: 1.35;
        }

        /* Floating Toolbar */
        .no-print-toolbar {
            position: fixed;
            top: 15px;
            right: 20px;
            background: #1e293b;
            color: #fff;
            padding: 10px 18px;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
        }
        .btn-action {
            background: #e11d48;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 30px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: 0.15s;
        }
        .btn-action:hover {
            background: #be123c;
            transform: scale(1.03);
        }
        .btn-secondary {
            background: #334155;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #475569;
        }

        /* Paper sheet simulation */
        .paper-container {
            max-width: 210mm;
            margin: 30px auto;
            background: #fff;
            padding: 20mm 20mm 20mm 20mm;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            min-height: 297mm;
        }

        .exam-sheet {
            page-break-after: always;
        }
        .exam-sheet:last-child {
            page-break-after: auto;
        }

        /* Header layout */
        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-left {
            text-align: center;
            width: 48%;
        }
        .header-left h3 {
            font-size: 11.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header-left h2 {
            font-size: 12.5pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .header-left .divider {
            width: 80px;
            height: 1px;
            background: #000;
            margin: 4px auto;
        }

        .header-right {
            text-align: center;
            width: 48%;
        }
        .header-right h2 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header-right .subject-name {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .header-right .exam-meta {
            font-size: 11pt;
            font-style: italic;
            margin-top: 2px;
        }

        .exam-code-box {
            display: inline-block;
            border: 2px solid #000;
            padding: 3px 12px;
            font-weight: bold;
            font-size: 13pt;
            margin-top: 5px;
            text-transform: uppercase;
            background: #f8fafc;
        }

        /* Student info box */
        .student-info-grid {
            border: 1px dashed #000;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 11.5pt;
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 6px;
        }
        .dotted-line {
            display: inline-block;
            border-bottom: 1px dotted #000;
            min-width: 140px;
            height: 14px;
        }

        /* Bubble Answer Matrix on Paper */
        .quick-answer-sheet {
            border: 1px solid #000;
            padding: 8px 12px;
            margin-bottom: 15px;
            background: #fafafa;
        }
        .quick-answer-sheet-title {
            font-weight: bold;
            font-size: 10.5pt;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .bubble-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 4px 8px;
            font-size: 10pt;
        }
        .bubble-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2px 4px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background: #fff;
        }
        .bubble-circles {
            display: flex;
            gap: 3px;
        }
        .bubble-circle {
            width: 15px;
            height: 15px;
            border: 1px solid #475569;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7.5pt;
            font-weight: bold;
        }

        /* Question listing */
        .questions-wrapper {
            margin-top: 10px;
        }
        .q-item {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .q-title {
            font-weight: bold;
            margin-bottom: 4px;
            text-align: justify;
        }
        .q-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 15px;
            padding-left: 15px;
        }
        .q-opt {
            display: flex;
            gap: 4px;
        }
        .q-opt-key {
            font-weight: bold;
        }

        .exam-footer {
            margin-top: 25px;
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            border-top: 1px solid #000;
            padding-top: 10px;
        }

        /* Master Matrix Table */
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 11pt;
            text-align: center;
        }
        .matrix-table th, .matrix-table td {
            border: 1px solid #000;
            padding: 6px 4px;
        }
        .matrix-table th {
            background: #f1f5f9;
            font-weight: bold;
        }

        /* Print styles */
        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
                font-size: 12pt;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .paper-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
            .exam-sheet {
                page-break-after: always !important;
                padding: 12mm 15mm !important;
                min-height: 275mm;
            }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

    <!-- Floating action buttons -->
    <div class="no-print-toolbar">
        <span style="font-weight: 700; color: #cbd5e1;"><i class="fa-solid fa-print"></i> Xem &amp; In Đề Thi</span>
        <button onclick="window.print()" class="btn-action">
            <i class="fa-solid fa-print"></i> IN NGAY (Ctrl+P)
        </button>
        <a href="quiz.php?quiz_id=<?= $quiz_id ?>" class="btn-action btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Quay lại Quiz
        </a>
    </div>

    <div class="paper-container">

        <?php if ($mode === 'matrix'): 
            // Master Answer Key Matrix
            $all_questions_count = 0;
            $matrix_codes_data = [];
            foreach ($exam_codes as $ec) {
                $qs = json_decode($ec['matrix_data'], true) ?: [];
                $matrix_codes_data[$ec['ma_de']] = array_column($qs, 'dap_an_dung');
                if (count($qs) > $all_questions_count) {
                    $all_questions_count = count($qs);
                }
            }
        ?>
            <div class="exam-sheet">
                <div class="exam-header">
                    <div class="header-left">
                        <h2>TRƯỜNG CAO ĐẲNG CÀ MAU</h2>
                        <p style="font-size: 10.5pt; font-weight: bold; margin-top: 3px;">HỘI ĐỒNG THI &amp; KIỂM TRA</p>
                        <div class="divider"></div>
                    </div>
                    <div class="header-right">
                        <h2>BẢNG MA TRẬN ĐÁP ÁN TỔNG HỢP</h2>
                        <div class="subject-name">MÔN: <?= htmlspecialchars($quiz['ten_mon']) ?></div>
                        <div class="exam-meta">Bộ đề: <?= htmlspecialchars($quiz['tieu_de']) ?></div>
                    </div>
                </div>

                <div style="text-align: center; margin: 15px 0 10px 0;">
                    <h3 style="font-size: 13pt; text-transform: uppercase;">ĐỐI CHIẾU ĐÁP ÁN TẤT CẢ CÁC MÃ ĐỀ TRẮC NGHIỆM</h3>
                    <p style="font-size: 11pt; font-style: italic;">(Dùng cho Giảng viên &amp; Cán bộ coi thi chấm bài)</p>
                </div>

                <table class="matrix-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Câu hỏi</th>
                            <?php foreach ($exam_codes as $ec): ?>
                                <th>Mã đề <?= htmlspecialchars($ec['ma_de']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < $all_questions_count; $i++): ?>
                            <tr>
                                <td style="font-weight: bold; background: #f8fafc;">Câu <?= $i + 1 ?></td>
                                <?php foreach ($exam_codes as $ec): 
                                    $ans = $matrix_codes_data[$ec['ma_de']][$i] ?? '-';
                                ?>
                                    <td style="font-weight: bold; font-size: 12pt; color: #000;"><?= $ans ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <div class="exam-footer" style="margin-top: 40px; display: flex; justify-content: space-around; border: none;">
                    <div>
                        <p><strong>CÁN BỘ CHẤM THI 1</strong></p>
                        <p style="font-style: italic; font-size: 10pt; margin-top: 4px;">(Ký và ghi rõ họ tên)</p>
                    </div>
                    <div>
                        <p><strong>CÁN BỘ CHẤM THI 2</strong></p>
                        <p style="font-style: italic; font-size: 10pt; margin-top: 4px;">(Ký và ghi rõ họ tên)</p>
                    </div>
                    <div>
                        <p><strong>TRƯỞNG BỘ MÔN / KHOA</strong></p>
                        <p style="font-style: italic; font-size: 10pt; margin-top: 4px;">(Ký và ghi rõ họ tên)</p>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Normal Exam Sheets (Single or All) -->
            <?php foreach ($exam_codes as $ec_idx => $ec): 
                $qs = json_decode($ec['matrix_data'], true) ?: [];
                $q_count = count($qs);
            ?>
                <div class="exam-sheet">
                    <!-- Header -->
                    <div class="exam-header">
                        <div class="header-left">
                            <h2>TRƯỜNG CAO ĐẲNG CÀ MAU</h2>
                            <p style="font-size: 10.5pt; font-weight: bold; margin-top: 3px;">KHOA CÔNG NGHỆ THÔNG TIN</p>
                            <div class="divider"></div>
                        </div>
                        <div class="header-right">
                            <h2>ĐỀ THI KIỂM TRA TRẮC NGHIỆM</h2>
                            <div class="subject-name">MÔN: <?= htmlspecialchars($quiz['ten_mon']) ?></div>
                            <div class="exam-meta">Thời gian làm bài: 45 phút (Không kể phát đề)</div>
                            <div class="exam-code-box">MÃ ĐỀ THI: <?= htmlspecialchars($ec['ma_de']) ?></div>
                        </div>
                    </div>

                    <!-- Student Info box -->
                    <div class="student-info-grid">
                        <div>Họ và tên thí sinh: <span class="dotted-line" style="min-width: 220px;"></span></div>
                        <div>Số báo danh: <span class="dotted-line"></span></div>
                        <div>Lớp: <span class="dotted-line" style="min-width: 140px;"></span></div>
                        <div>Phòng thi: <span class="dotted-line"></span></div>
                        <div style="grid-column: span 2; font-style: italic; font-size: 10.5pt; color: #333; margin-top: 2px;">
                            (Thí sinh làm bài trực tiếp vào phiếu trả lời hoặc khoanh tròn trực tiếp vào đề thi)
                        </div>
                    </div>

                    <?php if ($mode === 'with_bubbles' || $q_count <= 40): ?>
                        <!-- Mini Quick Answer Sheet -->
                        <div class="quick-answer-sheet">
                            <div class="quick-answer-sheet-title">PHIẾU TÔ ĐÁP ÁN TRẮC NGHIỆM (MÃ ĐỀ <?= htmlspecialchars($ec['ma_de']) ?>)</div>
                            <div class="bubble-grid">
                                <?php for ($b = 1; $b <= $q_count; $b++): ?>
                                    <div class="bubble-item">
                                        <span style="font-weight:bold;"><?= $b ?></span>
                                        <div class="bubble-circles">
                                            <span class="bubble-circle">A</span>
                                            <span class="bubble-circle">B</span>
                                            <span class="bubble-circle">C</span>
                                            <span class="bubble-circle">D</span>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Questions List -->
                    <div class="questions-wrapper">
                        <?php foreach ($qs as $idx => $q): ?>
                            <div class="q-item">
                                <div class="q-title">
                                    <strong>Câu <?= $idx + 1 ?>:</strong> <?= htmlspecialchars($q['cau_hoi']) ?>
                                </div>
                                <div class="q-options-grid">
                                    <div class="q-opt">
                                        <span class="q-opt-key">A.</span>
                                        <span><?= htmlspecialchars($q['dap_an_a']) ?></span>
                                    </div>
                                    <div class="q-opt">
                                        <span class="q-opt-key">B.</span>
                                        <span><?= htmlspecialchars($q['dap_an_b']) ?></span>
                                    </div>
                                    <?php if (!empty($q['dap_an_c'])): ?>
                                        <div class="q-opt">
                                            <span class="q-opt-key">C.</span>
                                            <span><?= htmlspecialchars($q['dap_an_c']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($q['dap_an_d'])): ?>
                                        <div class="q-opt">
                                            <span class="q-opt-key">D.</span>
                                            <span><?= htmlspecialchars($q['dap_an_d']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="exam-footer">
                        --- HẾT ---<br>
                        <span style="font-size: 10pt; font-style: italic; font-weight: normal;">(Cán bộ coi thi không giải thích gì thêm)</span>
                    </div>

                    <?php if ($mode === 'answers'): ?>
                        <!-- Separate Answer Key Sheet for this code -->
                        <div style="page-break-before: always; margin-top: 30px;">
                            <div class="exam-header">
                                <div class="header-left">
                                    <h2>TRƯỜNG CAO ĐẲNG CÀ MAU</h2>
                                </div>
                                <div class="header-right">
                                    <h2>ĐÁP ÁN ĐỀ THI TRẮC NGHIỆM</h2>
                                    <div class="exam-code-box">MÃ ĐỀ: <?= htmlspecialchars($ec['ma_de']) ?></div>
                                </div>
                            </div>
                            <table class="matrix-table" style="max-width: 500px; margin: 20px auto;">
                                <thead>
                                    <tr>
                                        <th>Câu số</th>
                                        <th>Đáp án đúng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($qs as $idx => $q): ?>
                                        <tr>
                                            <td style="font-weight: bold;">Câu <?= $idx + 1 ?></td>
                                            <td style="font-weight: bold; font-size: 13pt; color: #e11d48;"><?= htmlspecialchars($q['dap_an_dung']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</body>
</html>
