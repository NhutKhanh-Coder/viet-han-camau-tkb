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
$khoa = $sv['khoa'] ?? '';

$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$quiz_to_take = null;
$questions = [];
$attempt_result = null;
$related_assignments = [];
$current_ma_de = '101';
$current_exam_code_id = 0;

if ($action === 'submit_quiz') {
    $qid = (int)$_POST['quiz_id'];
    $submitted_ma_de = trim($_POST['ma_de'] ?? '101');
    $submitted_code_id = (int)($_POST['exam_code_id'] ?? 0);
    
    // Check if student already has an attempt to prevent double submission
    $stmt_check = $db->prepare("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?");
    $stmt_check->bind_param("ii", $qid, $sv_id);
    $stmt_check->execute();
    $already_attempted = ($stmt_check->get_result()->num_rows > 0);
    $stmt_check->close();
    
    if ($already_attempted) {
        $msg = "error:Bạn đã nộp bài trắc nghiệm này rồi, không thể nộp lại.";
        $action = '';
    } else {
        $score = 0;
        $total = 0;

        // Grade according to assigned exam code if available
        if ($submitted_code_id > 0) {
            $stmt_ec = $db->prepare("SELECT matrix_data, ma_de FROM quiz_exam_codes WHERE id = ? AND quiz_id = ?");
            $stmt_ec->bind_param("ii", $submitted_code_id, $qid);
            $stmt_ec->execute();
            $ec_row = $stmt_ec->get_result()->fetch_assoc();
            
            if ($ec_row) {
                $submitted_ma_de = $ec_row['ma_de'];
                $code_questions = json_decode($ec_row['matrix_data'], true) ?: [];
                $total = count($code_questions);
                foreach ($code_questions as $idx => $q_item) {
                    $q_key = 'q_' . $idx;
                    $user_choice = $_POST[$q_key] ?? $_POST['question_' . ($q_item['id'] ?? $idx)] ?? '';
                    if (strtoupper($user_choice) === strtoupper($q_item['dap_an_dung'])) {
                        $score++;
                    }
                }
            }
        }

        // Fallback to base questions
        if ($total === 0) {
            $stmt_ans = $db->prepare("SELECT id, dap_an_dung FROM quiz_questions WHERE quiz_id = ?");
            $stmt_ans->bind_param("i", $qid);
            $stmt_ans->execute();
            $correct_answers = $stmt_ans->get_result()->fetch_all(MYSQLI_ASSOC);
            $total = count($correct_answers);
            foreach ($correct_answers as $ans) {
                $user_choice = $_POST['question_' . $ans['id']] ?? '';
                if (strtoupper($user_choice) === strtoupper($ans['dap_an_dung'])) {
                    $score++;
                }
            }
        }
    
        // Fetch quiz title
        $stmt_title = $db->prepare("SELECT tieu_de FROM quizzes WHERE id = ?");
        $stmt_title->bind_param("i", $qid);
        $stmt_title->execute();
        $quiz_title = $stmt_title->get_result()->fetch_assoc()['tieu_de'] ?? 'Kiểm tra';

        $prompt = "Bạn là trợ lý giảng dạy AI thông minh. Học sinh vừa hoàn thành bài Quiz trắc nghiệm \"$quiz_title\" (Mã đề thi: $submitted_ma_de).
Kết quả làm bài:
- Số câu đúng: $score
- Tổng số câu hỏi: $total
- Điểm tỷ lệ: " . ($total > 0 ? round(($score / $total) * 10, 1) : 0) . "/10

Hãy đưa ra một nhận xét ngắn gọn (khoảng 2-3 câu), thân thiện, khuyến khích học sinh bằng Tiếng Việt dựa trên điểm số này. Hãy đưa ra lời khuyên cụ thể để học sinh cải thiện kết quả. Nhận xét trực tiếp cho học sinh (xưng hô bạn - tôi hoặc thầy/cô - em).";

        $nhan_xet = '';
        $AI_KEY = 'sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48';
        $ch = curl_init('https://api.xkiro.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model' => 'deepseek/deepseek-chat-v3.1',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => 250
            ]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $AI_KEY
            ],
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $resData = json_decode($response, true);
            $nhan_xet = trim($resData['choices'][0]['message']['content'] ?? '');
        }
        
        if (empty($nhan_xet)) {
            $nhan_xet = "Cảm ơn em đã hoàn thành bài làm. Hãy tiếp tục phát huy nhé!";
        }

        // Save attempt with ma_de
        $stmt_save = $db->prepare("INSERT INTO quiz_attempts (quiz_id, student_id, score, total_questions, nhan_xet, ma_de) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_save->bind_param("iiiiss", $qid, $sv_id, $score, $total, $nhan_xet, $submitted_ma_de);
        if ($stmt_save->execute()) {
            unset($_SESSION['quiz_start_time_' . $qid]);
            $diem_10 = round(($score / $total) * 10, 2);
            $msg = "success:Nộp bài Quiz thành công (Mã đề: $submitted_ma_de)! Điểm số của bạn: $diem_10 / 10 (Đúng $score / $total câu)";
            $_SESSION['last_quiz_feedback'] = [
                'score' => $score,
                'total' => $total,
                'nhan_xet' => $nhan_xet,
                'ma_de' => $submitted_ma_de
            ];
            $attempt_result = [
                'score' => $score,
                'total' => $total,
                'answers' => $_POST
            ];
        } else {
            $msg = "error:Lỗi lưu kết quả làm bài: " . $db->error;
        }
    }
    $action = ''; // Go back to quiz list
} elseif ($action === 'take') {
    $qid = (int)$_GET['quiz_id'];
    
    // Check if student has already attempted this quiz
    $stmt_att_check = $db->prepare("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?");
    $stmt_att_check->bind_param("ii", $qid, $sv_id);
    $stmt_att_check->execute();
    $already_attempted = ($stmt_att_check->get_result()->num_rows > 0);
    
    if ($already_attempted) {
        $msg = "error:Bạn đã làm bài kiểm tra này rồi, không được phép làm lại!";
        $action = '';
    } else {
        // Fetch quiz info
        $stmt_q = $db->prepare("SELECT q.*, m.ten_mon FROM quizzes q JOIN mon_hoc m ON q.mon_hoc_id = m.id WHERE q.id = ?");
        $stmt_q->bind_param("i", $qid);
        $stmt_q->execute();
        $quiz_to_take = $stmt_q->get_result()->fetch_assoc();
        
        if ($quiz_to_take) {
            // Setup quiz duration and anti-cheat session start timestamp
            $quiz_duration_minutes = max(1, (int)($quiz_to_take['thoi_gian_lam_bai'] ?? 15));
            $duration_seconds = $quiz_duration_minutes * 60;
            if (!isset($_SESSION['quiz_start_time_' . $qid])) {
                $_SESSION['quiz_start_time_' . $qid] = time();
            }
            $start_time = $_SESSION['quiz_start_time_' . $qid];
            $elapsed = time() - $start_time;
            $remaining_seconds = max(0, $duration_seconds - $elapsed);

            // Check if teacher generated exam codes for this quiz
            $stmt_ec = $db->prepare("SELECT * FROM quiz_exam_codes WHERE quiz_id = ? ORDER BY id ASC");
            $stmt_ec->bind_param("i", $qid);
            $stmt_ec->execute();
            $available_exam_codes = $stmt_ec->get_result()->fetch_all(MYSQLI_ASSOC);

            if (!empty($available_exam_codes)) {
                // Distribute exam code stably based on student ID
                $code_index = abs((int)$sv_id + (int)$qid) % count($available_exam_codes);
                $assigned_exam = $available_exam_codes[$code_index];
                $current_ma_de = $assigned_exam['ma_de'];
                $current_exam_code_id = $assigned_exam['id'];
                $questions = json_decode($assigned_exam['matrix_data'], true) ?: [];
            } else {
                // Fallback to base questions
                $current_ma_de = '101';
                $current_exam_code_id = 0;
                $stmt_qs = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
                $stmt_qs->bind_param("i", $qid);
                $stmt_qs->execute();
                $questions = $stmt_qs->get_result()->fetch_all(MYSQLI_ASSOC);
            }
            
            // Fetch related assignments
            $stmt_ass = $db->prepare("SELECT * FROM assignments WHERE quiz_id = ?");
            $stmt_ass->bind_param("i", $qid);
            $stmt_ass->execute();
            $related_assignments = $stmt_ass->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $msg = "error:Không tìm thấy bài Quiz yêu cầu.";
            $action = '';
        }
    }
}

// Fetch all subjects in student's department
$subjects = [];
if ($khoa) {
    $stmt_mon = $db->prepare("
        SELECT DISTINCT m.id, m.ten_mon, m.ma_mon
        FROM thoi_khoa_bieu tkb
        JOIN mon_hoc m ON tkb.mon_hoc_id = m.id
        WHERE LOWER(tkb.khoa) = LOWER(?)
        ORDER BY m.ten_mon
    ");
    $stmt_mon->bind_param("s", $khoa);
    $stmt_mon->execute();
    $subjects = $stmt_mon->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch all quizzes and check student's attempts
$quizzes = [];
if ($khoa) {
    // Quizzes for subjects in the student's department or enrolled in timetable
    $stmt_quiz = $db->prepare("
        SELECT q.id, q.tieu_de, q.thoi_gian_lam_bai, q.created_at, m.ten_mon, 
               (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) as q_count,
               (SELECT COUNT(*) FROM assignments WHERE quiz_id = q.id) as has_assignment,
               qa.score, qa.total_questions, qa.attempted_at
        FROM quizzes q
        JOIN mon_hoc m ON q.mon_hoc_id = m.id
        LEFT JOIN (
            SELECT quiz_id, MAX(score) as score, total_questions, MAX(attempted_at) as attempted_at
            FROM quiz_attempts 
            WHERE student_id = ?
            GROUP BY quiz_id
        ) qa ON qa.quiz_id = q.id
        WHERE (LOWER(m.khoa) = LOWER(?) OR m.id IN (SELECT DISTINCT mon_hoc_id FROM thoi_khoa_bieu WHERE LOWER(khoa) = LOWER(?)))
        GROUP BY q.id
        ORDER BY q.id DESC
    ");
    $stmt_quiz->bind_param("iss", $sv_id, $khoa, $khoa);
    $stmt_quiz->execute();
    $quizzes = $stmt_quiz->get_result()->fetch_all(MYSQLI_ASSOC);
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Làm Quiz - Cổng sinh viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        .quiz-card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.25s ease;
        }
        .quiz-card:hover {
            border-color: var(--accent);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .q-question-card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            transition: all 0.2s ease;
        }
        .q-question-card:hover {
            border-color: rgba(225, 29, 72, 0.3);
        }
        .q-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-top: 12px;
            cursor: pointer;
            transition: all 0.15s ease;
            background: var(--bg3);
        }
        .q-option:hover {
            border-color: var(--accent);
            background: rgba(217, 27, 67, 0.04);
        }
        .q-option input[type="radio"] {
            accent-color: var(--accent);
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        /* Sticky Floating Exam Timer */
        .qz-sticky-timer {
            position: sticky;
            top: 15px;
            z-index: 9999;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(225, 29, 72, 0.4);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4), 0 0 20px rgba(225, 29, 72, 0.2);
            border-radius: 16px;
            padding: 12px 20px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .qz-sticky-timer.warning {
            border-color: #f59e0b;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.35);
        }
        .qz-sticky-timer.critical {
            border-color: #ef4444;
            background: rgba(35, 10, 20, 0.95);
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.5);
            animation: timerPulse 1.2s infinite;
        }
        @keyframes timerPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 20px rgba(239,68,68,0.4); }
            50% { transform: scale(1.01); box-shadow: 0 0 30px rgba(239,68,68,0.8); }
        }
        .qz-timer-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .qz-timer-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #e11d48, #be123c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.4);
        }
        .qz-timer-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #94a3b8;
            text-transform: uppercase;
        }
        .qz-timer-val {
            font-size: 24px;
            font-weight: 900;
            color: #ffffff;
            font-family: 'Outfit', monospace, sans-serif;
            letter-spacing: 1.5px;
        }
        .qz-timer-center {
            flex: 1;
            min-width: 180px;
            max-width: 340px;
        }
        .qz-progress-track {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            overflow: hidden;
        }
        .qz-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #e11d48);
            border-radius: 10px;
            transition: width 1s linear;
        }
        .qz-badge-status {
            font-size: 12px;
            font-weight: 700;
            color: #fca5a5;
            background: rgba(225, 29, 72, 0.18);
            border: 1px solid rgba(225, 29, 72, 0.35);
            padding: 6px 14px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .anti-cheat-toast {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 10000;
            background: rgba(239, 68, 68, 0.95);
            color: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4);
            display: none;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.3s ease;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <?php include '../includes/student_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-brain" style="color:var(--accent)"></i> Hệ Thống Kiểm Tra Quiz</h1>
            <p style="color: var(--text2); margin-top: 5px;">Thử sức với các bài trắc nghiệm nhanh để ôn luyện kiến thức công nghệ thông tin</p>
        </div>
    </div>

    <!-- Feedback messages -->
    <?php if ($msg): 
        $parts = explode(':', $msg);
        $type = $parts[0];
        $text = $parts[1];
    ?>
        <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>" style="display: block; margin-bottom: 20px;">
            <?= htmlspecialchars($text) ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'take' && $quiz_to_take): ?>
        <!-- Anti-Cheat Warning Toast -->
        <div id="anti-cheat-toast" class="anti-cheat-toast">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>CẢNH BÁO: Bạn vừa rời khỏi màn hình thi! Hãy tập trung làm bài.</span>
        </div>

        <!-- Sticky Countdown Timer Bar -->
        <div id="quiz-floating-timer" class="qz-sticky-timer">
            <div class="qz-timer-left">
                <div class="qz-timer-icon-wrap" id="timer-icon-wrap">
                    <i class="fa-solid fa-stopwatch"></i>
                </div>
                <div>
                    <div class="qz-timer-label">THỜI GIAN LÀM BÀI</div>
                    <div class="qz-timer-val" id="timer-display">--:--</div>
                </div>
            </div>
            <div class="qz-timer-center">
                <div class="qz-progress-track">
                    <div class="qz-progress-fill" id="timer-progress" style="width: 100%;"></div>
                </div>
            </div>
            <div class="qz-timer-right">
                <div class="qz-badge-status">
                    <i class="fa-solid fa-lock"></i> Chế độ thi: Đang khóa màn hình
                </div>
            </div>
        </div>

        <!-- Quiz taking interface header -->
        <div class="card" style="margin-bottom: 24px; padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge" style="background: rgba(225, 29, 72, 0.1); color: var(--accent); font-weight:700;"><?= htmlspecialchars($quiz_to_take['ten_mon']) ?></span>
                        <span class="badge" style="background: var(--accent); color: #fff; font-weight: 800; font-size: 11px; padding: 4px 10px; border-radius: 6px;">
                            <i class="fa-solid fa-shuffle"></i> MÃ ĐỀ: <?= htmlspecialchars($current_ma_de) ?>
                        </span>
                        <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; font-weight: 700; font-size: 11px; padding: 4px 10px; border-radius: 6px;">
                            <i class="fa-solid fa-clock"></i> <?= $quiz_duration_minutes ?> Phút
                        </span>
                    </div>
                    <h2 style="font-size: 20px; font-weight: 800; color: var(--text); margin-top: 8px;"><?= htmlspecialchars($quiz_to_take['tieu_de']) ?></h2>
                    <p style="font-size: 12px; color: var(--text2); margin-top: 4px;"><i class="fa-solid fa-circle-info"></i> Các câu hỏi và đáp án được xáo trộn tự động theo Mã đề <?= htmlspecialchars($current_ma_de) ?>. Bạn không được rời khỏi phòng thi khi chưa nộp bài.</p>
                </div>
                <div>
                    <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); font-weight: 800; font-size: 12px; padding: 8px 16px; border-radius: 8px;">
                        <i class="fa-solid fa-shield-halved"></i> Đang Trong Giờ Thi
                    </span>
                </div>
            </div>
        </div>

        <?php if (!empty($related_assignments)): ?>
            <div class="card" style="margin-bottom: 24px; border: 1px solid var(--accent); background: rgba(225, 29, 72, 0.01); padding: 20px; border-radius: 16px;">
                <h3 style="font-size: 16px; font-weight: 800; color: var(--accent); display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                    <i class="fa-solid fa-circle-exclamation"></i> ĐỀ BÀI CÓ PHẦN TỰ LUẬN ĐI KÈM
                </h3>
                <p style="font-size: 13.5px; color: var(--text2); line-height: 1.5; margin-bottom: 12px;">
                    Đề thi này yêu cầu bạn hoàn thành cả phần Trắc nghiệm (bên dưới) và phần Tự luận thực hành (đi kèm).
                </p>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($related_assignments as $as): ?>
                        <div style="background: var(--bg3); border: 1px solid var(--border); padding: 12px 15px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span style="font-weight: 700; color: var(--text); font-size: 13.5px;"><?= htmlspecialchars($as['tieu_de']) ?></span>
                                <span style="font-size: 11px; color: var(--text2); display: block; margin-top: 3px;"><i class="fa-solid fa-clock"></i> Hạn nộp: <?= date('d/m/Y H:i', strtotime($as['han_nop'])) ?></span>
                            </div>
                            <span style="color: var(--text2); font-size: 12px; font-style: italic;">
                                Làm sau khi nộp trắc nghiệm
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($questions)): ?>
            <div class="card" style="padding: 40px; text-align: center; color: var(--text2);">
                Chưa có câu hỏi nào trong bài Quiz này.
            </div>
        <?php else: ?>
            <form id="quiz-exam-form" method="POST" action="?action=submit_quiz" onsubmit="return handleQuizSubmit(this);">
                <input type="hidden" name="quiz_id" value="<?= $quiz_to_take['id'] ?>">
                <input type="hidden" name="ma_de" value="<?= htmlspecialchars($current_ma_de) ?>">
                <input type="hidden" name="exam_code_id" value="<?= (int)$current_exam_code_id ?>">
                
                <?php foreach ($questions as $index => $q): 
                    $qid_val = $q['id'] ?? $index;
                ?>
                    <div class="q-question-card" id="card_q_<?= $index ?>">
                        <h4 style="font-size: 15.5px; font-weight: 800; color: var(--text); line-height: 1.6; margin-bottom: 15px;">
                            Câu <?= $index + 1 ?>: <?= htmlspecialchars($q['cau_hoi']) ?>
                        </h4>
                        
                        <label class="q-option">
                            <input type="radio" name="q_<?= $index ?>" value="A" required>
                            <span><strong>A.</strong> <?= htmlspecialchars($q['dap_an_a']) ?></span>
                        </label>
                        <label class="q-option">
                            <input type="radio" name="q_<?= $index ?>" value="B">
                            <span><strong>B.</strong> <?= htmlspecialchars($q['dap_an_b']) ?></span>
                        </label>
                        <?php if (!empty($q['dap_an_c'])): ?>
                            <label class="q-option">
                                <input type="radio" name="q_<?= $index ?>" value="C">
                                <span><strong>C.</strong> <?= htmlspecialchars($q['dap_an_c']) ?></span>
                            </label>
                        <?php endif; ?>
                        <?php if (!empty($q['dap_an_d'])): ?>
                            <label class="q-option">
                                <input type="radio" name="q_<?= $index ?>" value="D">
                                <span><strong>D.</strong> <?= htmlspecialchars($q['dap_an_d']) ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" id="btn-submit-exam" class="btn-submit" style="width: 100%; padding: 16px; font-size: 16px; font-weight: 800; border-radius: 12px; margin-bottom: 40px; box-shadow: 0 6px 20px rgba(225,29,72,0.35);">
                    <i class="fa-solid fa-paper-plane"></i> Nộp Bài Làm Quiz (Mã đề: <?= htmlspecialchars($current_ma_de) ?>)
                </button>
            </form>
        <?php endif; ?>

    <?php else: ?>
        <!-- Quiz List -->
        <?php 
        if (isset($_SESSION['last_quiz_feedback'])): 
            $fb = $_SESSION['last_quiz_feedback'];
            unset($_SESSION['last_quiz_feedback']);
        ?>
            <div class="card" style="margin-bottom: 25px; border-color: #10b981; background: rgba(16, 185, 129, 0.02); padding: 24px; position: relative; border-radius: 16px;">
                <h3 style="font-size: 18px; font-weight: 800; color: #10b981; display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <i class="fa-solid fa-circle-check"></i> Hoàn thành bài kiểm tra!
                </h3>
                <div style="font-size: 20px; font-weight: 800; color: var(--text); margin-bottom: 12px;">
                    Điểm số của bạn: <span style="color: var(--accent);"><?= round(($fb['score'] / $fb['total']) * 10, 2) ?></span> / 10 (Đúng <?= $fb['score'] ?> / <?= $fb['total'] ?> câu) 
                    <?php if (!empty($fb['ma_de'])): ?>
                        <span class="badge" style="background: var(--accent); color:#fff; font-size:12px; margin-left: 10px;">Mã đề: <?= htmlspecialchars($fb['ma_de']) ?></span>
                    <?php endif; ?>
                </div>
                <div style="background: var(--bg3); border: 1px solid var(--border); padding: 15px; border-radius: 12px; font-size: 13.5px; line-height: 1.6; color: var(--text2);">
                    <strong style="color: var(--text); display: block; margin-bottom: 5px;"><i class="fa-solid fa-robot"></i> Nhận xét từ AI:</strong>
                    <?= htmlspecialchars($fb['nhan_xet']) ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if (empty($quizzes)): ?>
            <div class="card" style="padding: 60px; text-align: center; color: var(--text2);">
                <i class="fa-solid fa-box-open" style="font-size: 40px; color: var(--accent); margin-bottom: 20px; display: block;"></i>
                Chưa có bài kiểm tra Quiz nào được tạo cho học phần của bạn.
            </div>
        <?php else: foreach ($quizzes as $q): 
            $has_attempt = ($q['score'] !== null);
            $q_time = max(1, (int)($q['thoi_gian_lam_bai'] ?? 15));
        ?>
            <div class="quiz-card">
                <div>
                    <span class="badge" style="background: rgba(225, 29, 72, 0.08); color: var(--accent); font-weight: 700; font-size: 10.5px;"><?= htmlspecialchars($q['ten_mon']) ?></span>
                    <h3 style="font-size: 16.5px; font-weight: 800; color: var(--text); margin: 6px 0;"><?= htmlspecialchars($q['tieu_de']) ?></h3>
                    <div style="font-size: 12px; color: var(--text2); display:flex; gap: 15px; margin-top: 5px; flex-wrap: wrap;">
                        <span><i class="fa-solid fa-circle-question"></i> <?= $q['q_count'] ?> câu hỏi</span>
                        <span><i class="fa-solid fa-stopwatch"></i> <?= $q_time ?> phút</span>
                        <?php if ($q['has_assignment'] > 0): ?>
                            <span style="color:var(--accent); font-weight:700;"><i class="fa-solid fa-file-signature"></i> Có tự luận đi kèm</span>
                        <?php endif; ?>
                        <?php if ($has_attempt): ?>
                            <span style="color:#10b981; font-weight: 700;"><i class="fa-solid fa-square-poll-vertical"></i> Điểm cao nhất: <?= round(($q['score'] / $q['total_questions']) * 10, 2) ?> / 10</span>
                        <?php else: ?>
                            <span style="color:#64748b;"><i class="fa-solid fa-circle-minus"></i> Chưa làm</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <?php if ($has_attempt): ?>
                        <button class="btn-submit" disabled style="opacity: 0.6; cursor: not-allowed; padding: 10px 18px; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; background: #64748b;">
                            <i class="fa-solid fa-circle-check"></i> Đã làm bài
                        </button>
                    <?php elseif ($q['q_count'] > 0): ?>
                        <a href="?action=take&quiz_id=<?= $q['id'] ?>" class="btn-submit" style="text-decoration: none; padding: 10px 18px; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-play"></i> Bắt đầu làm (<?= $q_time ?>p)
                        </a>
                    <?php else: ?>
                        <button class="btn-submit" disabled style="opacity: 0.4; cursor: not-allowed; padding: 10px 18px; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px;">
                            Không có câu hỏi
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
    <?php endif; ?>
    
    <script>
    let isSubmittingQuiz = false;

    function handleQuizSubmit(form) {
        isSubmittingQuiz = true;
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang nộp bài và AI phân tích kết quả...';
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
        }
        return true;
    }

    <?php if ($action === 'take' && $quiz_to_take): ?>
    // ══════════════════════════════════════════════════════════════════════════
    // ANTI-EXIT LOCK-IN MODE & REALTIME COUNTDOWN TIMER
    // ══════════════════════════════════════════════════════════════════════════
    (function() {
        let remainingSeconds = <?= (int)$remaining_seconds ?>;
        const totalDuration = <?= (int)$duration_seconds ?>;
        const timerDisplay = document.getElementById('timer-display');
        const timerProgress = document.getElementById('timer-progress');
        const timerBar = document.getElementById('quiz-floating-timer');
        const examForm = document.getElementById('quiz-exam-form');
        const antiCheatToast = document.getElementById('anti-cheat-toast');

        function formatTime(sec) {
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }

        function updateTimerUI() {
            if (timerDisplay) {
                timerDisplay.textContent = formatTime(remainingSeconds);
            }
            if (timerProgress && totalDuration > 0) {
                const pct = Math.max(0, Math.min(100, (remainingSeconds / totalDuration) * 100));
                timerProgress.style.width = pct + '%';
            }

            if (timerBar) {
                if (remainingSeconds <= 120) {
                    timerBar.className = 'qz-sticky-timer critical';
                } else if (remainingSeconds <= 300) {
                    timerBar.className = 'qz-sticky-timer warning';
                } else {
                    timerBar.className = 'qz-sticky-timer';
                }
            }
        }

        updateTimerUI();

        // 1. Countdown timer interval
        const timerInterval = setInterval(function() {
            if (isSubmittingQuiz) {
                clearInterval(timerInterval);
                return;
            }

            remainingSeconds--;
            updateTimerUI();

            if (remainingSeconds <= 0) {
                clearInterval(timerInterval);
                isSubmittingQuiz = true;
                alert('⏰ ĐÃ HẾT THỜI GIAN LÀM BÀI!\n\nHệ thống đang tự động nộp bài làm của bạn.');
                if (examForm) {
                    // Remove required attributes on radios so auto-submit succeeds even if some questions are unanswered
                    examForm.querySelectorAll('input[type="radio"]').forEach(r => r.removeAttribute('required'));
                    examForm.submit();
                }
            }
        }, 1000);

        // 2. Prevent page reload / closing tab (beforeunload)
        window.addEventListener('beforeunload', function(e) {
            if (!isSubmittingQuiz) {
                e.preventDefault();
                e.returnValue = '⚠️ Bạn đang trong phòng thi Quiz. Nếu thoát ra, bài làm sẽ bị hủy hoặc mất kết quả!';
                return e.returnValue;
            }
        });

        // 3. Prevent browser Back / Forward buttons (History pushState trap)
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            if (!isSubmittingQuiz) {
                history.pushState(null, null, location.href);
                alert('⚠️ CẢNH BÁO PHÒNG THI:\nBạn đang trong thời gian làm bài thi trắc nghiệm. Không được phép quay lại hoặc rời khỏi bài thi!');
            }
        };

        // 4. Trap all sidebar / header navigation links to prevent accidentally exiting
        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!isSubmittingQuiz) {
                    const href = this.getAttribute('href');
                    if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                        e.preventDefault();
                        const confirmExit = confirm('⚠️ CẢNH BÁO PHÒNG THI:\nBạn đang làm bài kiểm tra trắc nghiệm. Nếu rời khỏi trang, bài làm chưa nộp sẽ bị mất!\n\nBạn có chắc chắn muốn bỏ bài thi và thoát không?');
                        if (confirmExit) {
                            isSubmittingQuiz = true;
                            window.location.href = href;
                        }
                    }
                }
            });
        });

        // 5. Tab switch / Visibility change detector
        let switchCount = 0;
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && !isSubmittingQuiz) {
                switchCount++;
                if (antiCheatToast) {
                    antiCheatToast.style.display = 'flex';
                    antiCheatToast.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <span>CẢNH BÁO: Bạn vừa chuyển tab (lần ' + switchCount + ')! Hệ thống đang ghi nhận quá trình thi.</span>';
                    setTimeout(() => {
                        antiCheatToast.style.display = 'none';
                    }, 4000);
                }
            }
        });
    })();
    <?php endif; ?>
    </script>
</body>
</html>
