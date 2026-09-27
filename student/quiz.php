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
$st_av = !empty($sv['avatar']) ? (strpos($sv['avatar'], 'http') === 0 || strpos($sv['avatar'], '/') === 0 ? $sv['avatar'] : '/tkb/assets/img/avatars/' . htmlspecialchars($sv['avatar'])) : '/tkb/assets/img/avatar_khanh.png';

$msg = $_GET['msg'] ?? '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$quiz_to_take = null;
$questions = [];
$attempt_result = null;
$related_assignments = [];
$current_ma_de = '101';
$current_exam_code_id = 0;

$normalize_answer = function($val) {
    $val = trim((string)$val);
    if (preg_match('/^\[?([A-Da-d])[\.\)\:]?/i', $val, $m)) {
        return strtoupper($m[1]);
    }
    return strtoupper($val);
};

if ($action === 'submit_quiz') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: quiz.php");
        exit;
    }
    $qid = (int)($_POST['quiz_id'] ?? 0);
    if ($qid <= 0) {
        header("Location: quiz.php");
        exit;
    }
    $submitted_ma_de = trim($_POST['ma_de'] ?? '101');
    $submitted_code_id = (int)($_POST['exam_code_id'] ?? 0);
    
    // Check if student already has an attempt
    $stmt_check = $db->prepare("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?");
    $stmt_check->bind_param("ii", $qid, $sv_id);
    $stmt_check->execute();
    $existing_att = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();
    
    if ($existing_att) {
        header("Location: quiz.php?msg=" . urlencode("error:Bạn đã hoàn thành bài thi này rồi. Mỗi sinh viên chỉ được nộp bài 1 lần duy nhất!"));
        exit;
    }
    
    $score = 0;
    $total = 0;

    // Grade according to assigned exam code if available
    $ec_row = null;
    if ($submitted_code_id > 0) {
        $stmt_ec = $db->prepare("SELECT matrix_data, ma_de FROM quiz_exam_codes WHERE id = ? AND quiz_id = ?");
        $stmt_ec->bind_param("ii", $submitted_code_id, $qid);
        $stmt_ec->execute();
        $ec_row = $stmt_ec->get_result()->fetch_assoc();
        $stmt_ec->close();
    }
    if (!$ec_row && !empty($submitted_ma_de)) {
        $stmt_ec2 = $db->prepare("SELECT matrix_data, ma_de FROM quiz_exam_codes WHERE ma_de = ? AND quiz_id = ? LIMIT 1");
        $stmt_ec2->bind_param("si", $submitted_ma_de, $qid);
        $stmt_ec2->execute();
        $ec_row = $stmt_ec2->get_result()->fetch_assoc();
        $stmt_ec2->close();
    }
    
    if ($ec_row) {
        $submitted_ma_de = $ec_row['ma_de'];
        $code_questions = json_decode($ec_row['matrix_data'], true) ?: [];
        $total = count($code_questions);
        foreach ($code_questions as $idx => $q_item) {
            $q_key = 'q_' . $idx;
            $item_id = $q_item['id'] ?? $q_item['orig_id'] ?? $idx;
            $user_choice = $_POST[$q_key] 
                        ?? $_POST['question_' . $item_id] 
                        ?? $_POST['question_' . $idx] 
                        ?? $_POST['q_' . $item_id] 
                        ?? '';
            $correct = $normalize_answer($q_item['dap_an_dung'] ?? '');
            $selected = $normalize_answer($user_choice);
            if ($selected !== '' && $selected === $correct) {
                $score++;
            }
        }
    }

    // Fallback to base questions
    if ($total === 0) {
        $stmt_ans = $db->prepare("SELECT id, dap_an_dung FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
        $stmt_ans->bind_param("i", $qid);
        $stmt_ans->execute();
        $correct_answers = $stmt_ans->get_result()->fetch_all(MYSQLI_ASSOC);
        $total = count($correct_answers);
        foreach ($correct_answers as $idx => $ans) {
            $q_key = 'q_' . $idx;
            $user_choice = $_POST[$q_key] 
                        ?? $_POST['q_' . $ans['id']] 
                        ?? $_POST['question_' . $ans['id']] 
                        ?? $_POST['question_' . $idx] 
                        ?? '';
            $correct = $normalize_answer($ans['dap_an_dung'] ?? '');
            $selected = $normalize_answer($user_choice);
            if ($selected !== '' && $selected === $correct) {
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
        if ($score >= ($total * 0.8)) {
            $nhan_xet = "Xuất sắc! Em đã nắm rất vững kiến thức bài học. Hãy tiếp tục duy trì phong độ này nhé!";
        } elseif ($score >= ($total * 0.5)) {
            $nhan_xet = "Khá tốt! Em đã nắm được các nội dung cơ bản. Hãy xem lại các câu chưa đúng để hoàn thiện hơn.";
        } else {
            $nhan_xet = "Em cần ôn tập kỹ hơn nội dung lý thuyết để củng cố kiến thức cho các kỳ thi sau nhé. Cố gắng lên!";
        }
    }
    $vi_pham = (int)($_POST['vi_pham_count'] ?? 0);
    $phone_detect = (int)($_POST['phone_detected_count'] ?? 0);
    if ($vi_pham > 0 || $phone_detect > 0) {
        $nhan_xet .= "\n[Giám sát AI: Ghi nhận " . ($vi_pham > 0 ? "$vi_pham lần rời màn hình thi" : "") . (($vi_pham > 0 && $phone_detect > 0) ? ", " : "") . ($phone_detect > 0 ? "$phone_detect lần đưa điện thoại/camera trước màn hình" : "") . "].";
    }

    // Save attempt (update if already exists, else insert)
    if ($existing_att) {
        $stmt_save = $db->prepare("UPDATE quiz_attempts SET score = ?, total_questions = ?, nhan_xet = ?, ma_de = ?, attempted_at = NOW() WHERE id = ?");
        $stmt_save->bind_param("iissi", $score, $total, $nhan_xet, $submitted_ma_de, $existing_att['id']);
    } else {
        $stmt_save = $db->prepare("INSERT INTO quiz_attempts (quiz_id, student_id, score, total_questions, nhan_xet, ma_de) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_save->bind_param("iiiiss", $qid, $sv_id, $score, $total, $nhan_xet, $submitted_ma_de);
    }

    if ($stmt_save->execute()) {
        unset($_SESSION['quiz_start_time_' . $qid]);
        $diem_10 = round(($score / max(1, $total)) * 10, 2);
        $msg = "success:Nộp bài Quiz thành công (Mã đề: $submitted_ma_de)! Điểm số của bạn: $diem_10 / 10 (Đúng $score / $total câu)";
        $_SESSION['last_quiz_feedback'] = [
            'quiz_id' => $qid,
            'score' => $score,
            'total' => $total,
            'nhan_xet' => $nhan_xet,
            'ma_de' => $submitted_ma_de
        ];
        header("Location: quiz.php?msg=" . urlencode($msg));
        exit;
    } else {
        $msg = "error:Lỗi lưu kết quả làm bài: " . $db->error;
        header("Location: quiz.php?msg=" . urlencode($msg));
        exit;
    }
} elseif ($action === 'retake') {
    header("Location: quiz.php?msg=" . urlencode("warning:Bài thi trắc nghiệm chỉ được phép làm 1 lần duy nhất. Bạn đã nộp bài rồi và không thể làm lại!"));
    exit;
}

if ($action === 'take') {
    $qid = (int)$_GET['quiz_id'];
    
    // Check if student has already attempted this quiz
    $stmt_att_check = $db->prepare("SELECT id, score FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?");
    $stmt_att_check->bind_param("ii", $qid, $sv_id);
    $stmt_att_check->execute();
    $att_row = $stmt_att_check->get_result()->fetch_assoc();
    $stmt_att_check->close();
    
    if ($att_row) {
        $msg = "warning:Bạn đã hoàn thành và nộp bài kiểm tra này rồi. Mỗi sinh viên chỉ được phép làm bài 1 lần duy nhất!";
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
$is_exam = ($action === 'take' && !empty($quiz_to_take));
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
        /* Sticky Floating Exam Timer (Tone Trắng Thanh Lịch) */
        .qz-sticky-timer {
            position: sticky;
            top: 15px;
            z-index: 9999;
            background: rgba(255, 255, 255, 0.96) !important;
            backdrop-filter: blur(16px);
            border: 1.5px solid #e2e8f0 !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06), 0 2px 8px rgba(0, 0, 0, 0.03) !important;
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
            border-color: #f59e0b !important;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.15) !important;
        }
        .qz-sticky-timer.critical {
            border-color: #ef4444 !important;
            background: #fff5f5 !important;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.2) !important;
            animation: timerPulse 1.2s infinite;
        }
        @keyframes timerPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 20px rgba(239,68,68,0.2); }
            50% { transform: scale(1.01); box-shadow: 0 0 30px rgba(239,68,68,0.35); }
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
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);
        }
        .qz-timer-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #64748b !important;
            text-transform: uppercase;
        }
        .qz-timer-val {
            font-size: 24px;
            font-weight: 900;
            color: #0f172a !important;
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
            background: #e2e8f0 !important;
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
            font-weight: 800;
            color: #e11d48 !important;
            background: #fff1f2 !important;
            border: 1px solid #fecdd3 !important;
            padding: 6px 14px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        /* Anti-cheat Toast (Cảnh báo mép trình duyệt nổi bật) */
        .anti-cheat-toast {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100005;
            background: rgba(220, 38, 38, 0.95);
            color: #fff;
            padding: 14px 28px;
            border-radius: 16px;
            font-weight: 800;
            font-size: 13.5px;
            box-shadow: 0 15px 45px rgba(220, 38, 38, 0.45), 0 0 30px rgba(220, 38, 38, 0.2);
            display: none;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            animation: slideDownFade 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            max-width: 90vw;
        }
        @keyframes slideDownFade {
            from { transform: translate(-50%, -30px); opacity: 0; }
            to { transform: translate(-50%, 0); opacity: 1; }
        }

        /* ═══ KIOSK EXAM THEME: PURE WHITE & ACCENT CRIMSON (TONE TRẮNG HIỆN ĐẠI) ═══ */
        body.exam-lockdown-active {
            display: block !important;
            flex-direction: column !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100vw !important;
            min-height: 100vh !important;
            background: #f8fafc !important;
            color: #0f172a !important;
            user-select: none !important;
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
            overflow-x: hidden !important;
            font-family: 'Outfit', sans-serif !important;
        }
        body.exam-lockdown-active .sidebar,
        body.exam-lockdown-active .top-header,
        body.exam-lockdown-active .mc-sidebar,
        body.exam-lockdown-active .student-nav,
        body.exam-lockdown-active aside,
        body.exam-lockdown-active nav,
        body.exam-lockdown-active .page-header {
            display: none !important;
        }
        body.exam-lockdown-active .main-content {
            margin: 0 auto !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            display: block !important;
        }

        /* Kiosk Topbar (Header Phòng Thi Chuẩn Tone Trắng) */
        .exam-kiosk-topbar {
            width: 100%;
            background: rgba(255, 255, 255, 0.96) !important;
            border-bottom: 2px solid #e2e8f0 !important;
            backdrop-filter: blur(20px) !important;
            padding: 14px 32px !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.02) !important;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-sizing: border-box;
        }
        .exam-kiosk-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .exam-kiosk-logo-badge {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #e11d48, #be123c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.3);
        }
        .exam-kiosk-school-name {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a !important;
            letter-spacing: 0.8px;
        }
        .exam-kiosk-exam-title {
            font-size: 12px;
            font-weight: 700;
            color: #e11d48 !important;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 3px;
        }
        .exam-kiosk-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .exam-kiosk-student-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #ffffff !important;
            border: 1.5px solid #e2e8f0 !important;
            padding: 6px 18px 6px 8px;
            border-radius: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04) !important;
        }
        .exam-kiosk-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e11d48;
            box-shadow: 0 2px 8px rgba(225, 29, 72, 0.25);
        }
        .exam-kiosk-student-name {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a !important;
        }
        .exam-kiosk-student-id {
            font-size: 11.5px;
            color: #64748b !important;
        }
        .exam-kiosk-student-id strong {
            color: #1e293b;
        }

        /* Container Bài Thi */
        body.exam-lockdown-active .exam-kiosk-container {
            display: block !important;
            width: 95% !important;
            max-width: 1240px !important;
            margin: 0 auto !important;
            padding: 24px 15px 120px !important;
            box-sizing: border-box !important;
        }

        /* Hero Banner Bài Thi (Tone Trắng) */
        .exam-hero-card {
            background: #ffffff !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 22px !important;
            padding: 26px 32px !important;
            margin-bottom: 24px !important;
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02) !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .exam-hero-title {
            font-size: 24px !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            margin: 10px 0 6px !important;
            letter-spacing: 0.3px;
        }
        .exam-hero-desc {
            font-size: 13.5px !important;
            color: #475569 !important;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Ma Trận Điều Hướng Câu Hỏi Nhanh (Question Palette) */
        .exam-nav-palette {
            background: #ffffff !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 18px !important;
            padding: 16px 24px !important;
            margin-bottom: 26px !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02) !important;
        }
        .exam-palette-title {
            font-size: 13px;
            font-weight: 800;
            color: #0284c7 !important;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .exam-palette-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .exam-palette-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #334155 !important;
            font-weight: 800;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .exam-palette-btn:hover {
            border-color: #e11d48 !important;
            color: #e11d48 !important;
            background: #fff1f2 !important;
            transform: translateY(-2px);
        }
        .exam-palette-btn.answered {
            background: linear-gradient(135deg, #059669, #10b981) !important;
            border-color: #059669 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.35) !important;
        }

        /* Thẻ Câu Hỏi (Tone Trắng Thanh Lịch, Sắc Nét) */
        .q-cyber-card {
            background: #ffffff !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 22px !important;
            padding: 30px 34px !important;
            margin-bottom: 28px !important;
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02) !important;
            transition: all 0.25s ease;
        }
        .q-cyber-card:hover {
            border-color: #cbd5e1 !important;
            box-shadow: 0 14px 30px -4px rgba(0, 0, 0, 0.08) !important;
        }
        .q-header-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .q-number-pill {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #4338ca !important;
            background: #eef2ff !important;
            border: 1px solid #c7d2fe !important;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .q-point-pill {
            font-size: 11.5px;
            font-weight: 700;
            color: #64748b !important;
        }
        .q-content-text {
            font-size: 19.5px !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            line-height: 1.6 !important;
            margin-bottom: 22px !important;
            text-shadow: none !important;
        }
        .q-choices-grid {
            display: flex;
            flex-direction: column;
            gap: 13px;
        }
        .q-choice-card {
            display: block;
            cursor: pointer;
            margin: 0;
        }
        .q-radio-hidden {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .q-choice-inner {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 24px;
            border-radius: 14px;
            background: #ffffff !important;
            border: 1.5px solid #e2e8f0 !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .q-choice-card:hover .q-choice-inner {
            border-color: #cbd5e1 !important;
            background: #f8fafc !important;
            transform: translateX(4px);
        }
        .q-choice-letter {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #334155 !important;
            font-weight: 800;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        .q-choice-text {
            flex: 1;
            font-size: 16px;
            font-weight: 500;
            color: #1e293b !important;
            line-height: 1.5;
        }
        .q-choice-state {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1.5px solid #cbd5e1 !important;
            background: #ffffff !important;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            font-size: 11px;
            transition: all 0.2s ease;
        }
        /* Checked State cho lựa chọn được click */
        .q-choice-card input:checked + .q-choice-inner {
            background: #fff1f2 !important;
            border-color: #e11d48 !important;
            box-shadow: 0 4px 18px rgba(225, 29, 72, 0.15) !important;
        }
        .q-choice-card input:checked + .q-choice-inner .q-choice-letter {
            background: linear-gradient(135deg, #e11d48, #be123c) !important;
            border-color: transparent !important;
            color: #ffffff !important;
            box-shadow: 0 3px 10px rgba(225, 29, 72, 0.35);
        }
        .q-choice-card input:checked + .q-choice-inner .q-choice-text {
            color: #9f1239 !important;
            font-weight: 700 !important;
        }
        .q-choice-card input:checked + .q-choice-inner .q-choice-state {
            background: #10b981 !important;
            border-color: #10b981 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.35);
        }

        /* Nút Nộp Bài Thi */
        .btn-submit-exam-kiosk {
            width: 100%;
            padding: 18px;
            font-size: 17px;
            font-weight: 800;
            border-radius: 16px;
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: #ffffff;
            border: none;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(225, 29, 72, 0.35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 50px;
            transition: all 0.25s ease;
        }
        .btn-submit-exam-kiosk:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(225, 29, 72, 0.5);
        }

        #top-edge-tripwire {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 10px;
            z-index: 9999999;
            background: transparent;
        }

        /* Mandatory Exam Gate (Màn hình khóa ban đầu - Tone Trắng) */
        .exam-lockdown-gate {
            position: fixed;
            inset: 0;
            z-index: 999999;
            background: rgba(241, 245, 249, 0.94);
            backdrop-filter: blur(25px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .lockdown-gate-card {
            background: #ffffff !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 24px;
            padding: 38px 34px;
            max-width: 620px;
            width: 100%;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.12), 0 0 30px rgba(225, 29, 72, 0.08) !important;
            text-align: center;
            color: #0f172a !important;
            animation: zoomInGate 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes zoomInGate {
            from { opacity: 0; transform: scale(0.92); }
            to { opacity: 1; transform: scale(1); }
        }
        .lockdown-gate-icon {
            width: 78px;
            height: 78px;
            margin: 0 auto 18px;
            border-radius: 22px;
            background: linear-gradient(135deg, #e11d48, #be123c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 36px;
            box-shadow: 0 10px 25px rgba(225, 29, 72, 0.35);
        }
        .lockdown-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff1f2 !important;
            color: #e11d48 !important;
            border: 1px solid #fecdd3 !important;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        .lockdown-gate-card h2 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 8px;
            color: #0f172a !important;
        }
        .lockdown-gate-subtitle {
            color: #64748b !important;
            font-size: 13.5px;
            margin-bottom: 22px;
        }
        .lockdown-rules-box {
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 26px;
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px;
            padding: 16px 18px;
        }
        .lockdown-rule {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            font-size: 13px;
            line-height: 1.5;
        }
        .lockdown-rule i {
            font-size: 17px;
            margin-top: 3px;
            flex-shrink: 0;
        }
        .text-danger { color: #ef4444; }
        .text-warning { color: #f59e0b; }
        .text-info { color: #0284c7; }
        .lockdown-rule strong {
            display: block;
            color: #0f172a !important;
            font-size: 13px;
            margin-bottom: 2px;
        }
        .lockdown-rule span {
            color: #475569 !important;
            font-size: 12px;
        }
        .btn-enter-lockdown {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(225, 29, 72, 0.35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s ease;
        }
        .btn-enter-lockdown:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(225, 29, 72, 0.5);
        }

        /* Emergency Anti-AI Lockout Screen */
        .exam-anti-ai-lockout {
            position: fixed;
            inset: 0;
            z-index: 1000000;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(30px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            animation: fadeInLockout 0.15s ease;
        }
        @keyframes fadeInLockout {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .lockout-alert-box {
            background: #ffffff;
            border: 2.5px solid #ef4444;
            border-radius: 24px;
            padding: 36px 30px;
            max-width: 560px;
            width: 100%;
            box-shadow: 0 25px 70px rgba(239, 68, 68, 0.3), 0 0 50px rgba(239, 68, 68, 0.15);
            text-align: center;
            color: #0f172a;
            animation: shakeAlert 0.4s ease;
        }
        .lockout-alert-box h2 {
            color: #dc2626;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        @keyframes shakeAlert {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-8px); }
            40%, 80% { transform: translateX(8px); }
        }
        .lockout-siren-icon {
            width: 76px;
            height: 76px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: #fee2e2;
            border: 2px solid #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ef4444;
            font-size: 36px;
            animation: sirenPulse 1s infinite;
        }
        @keyframes sirenPulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.8); }
            70% { box-shadow: 0 0 0 22px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .lockout-warning-badge {
            display: inline-block;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 6px 18px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 1px;
            margin: 10px 0 16px;
        }
        .lockout-msg {
            font-size: 13.5px;
            color: #475569;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .lockout-timer-pill {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 12px;
            font-size: 14px;
            font-weight: 800;
            color: #991b1b;
            margin-bottom: 24px;
        }
        .countdown-num {
            font-size: 22px;
            color: #dc2626;
            font-family: monospace;
            padding: 0 4px;
        }
        .btn-unlock-anti-ai {
            width: 100%;
            padding: 15px;
            background: #ef4444;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: 800;
            font-size: 14.5px;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-unlock-anti-ai:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* AI Proctoring HUD (Tone Trắng) */
        .ai-proctoring-hud {
            position: fixed !important;
            bottom: 20px !important;
            right: 24px !important;
            z-index: 99998 !important;
            width: 330px !important;
            background: rgba(255, 255, 255, 0.96) !important;
            border: 1.5px solid #e2e8f0 !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 20px !important;
            padding: 12px 14px !important;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12) !important;
            transition: all 0.3s ease !important;
        }
        .ai-proctoring-hud.alert-phone {
            border-color: #ef4444 !important;
            box-shadow: 0 0 35px rgba(239, 68, 68, 0.4) !important;
            animation: hudWarningPulse 0.6s infinite alternate !important;
        }
        @keyframes hudWarningPulse {
            from { transform: scale(1); }
            to { transform: scale(1.04); }
        }
        .ai-hud-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 800;
        }
        .ai-hud-title {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #0f172a !important;
        }
        .ai-hud-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: blinkDot 1s infinite;
        }
        @keyframes blinkDot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .ai-video-wrap {
            position: relative;
            width: 100%;
            height: 205px !important;
            background: #0f172a;
            border-radius: 12px;
            overflow: hidden;
        }
        #proctor-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }
        #proctor-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            transform: scaleX(-1);
        }
        .ai-scanner-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #38bdf8, transparent);
            box-shadow: 0 0 8px #38bdf8;
            animation: scanAnimation 2.2s infinite ease-in-out;
        }
        @keyframes scanAnimation {
            0% { top: 0; }
            50% { top: calc(100% - 2px); }
            100% { top: 0; }
        }
        .ai-hud-badge {
            position: absolute;
            bottom: 6px;
            left: 6px;
            right: 6px;
            background: rgba(239, 68, 68, 0.95);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            padding: 4px 6px;
            border-radius: 6px;
            text-align: center;
            animation: bounceBadge 0.5s ease;
        }
        .ai-hud-footer {
            margin-top: 6px;
            font-size: 11px;
            color: #64748b !important;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        /* Dynamic Watermark Matrix (Mờ nhẹ tinh tế trên nền trắng) */
        .exam-watermark-matrix {
            position: fixed;
            inset: 0;
            z-index: 99995;
            pointer-events: none;
            user-select: none;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(5, 1fr);
            padding: 30px;
            opacity: 0.04;
            overflow: hidden;
        }
        .wm-item {
            font-size: 13px;
            font-weight: 800;
            color: #64748b;
            transform: rotate(-25deg);
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Anti-Peep Blur Shield (Tone Trắng) */
        .anti-peep-shield {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: rgba(241, 245, 249, 0.94);
            backdrop-filter: blur(25px);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px;
        }
        .peep-shield-content {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 20px;
            padding: 36px 30px;
            max-width: 480px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
            color: #0f172a;
        }
        .peep-icon {
            font-size: 42px;
            color: #0284c7;
            margin-bottom: 15px;
        }
        .peep-shield-content h3 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .peep-shield-content p {
            font-size: 13.5px;
            color: #475569;
            line-height: 1.6;
        }
        .qz-badge-violation {
            font-size: 11.5px;
            font-weight: 800;
            color: #dc2626 !important;
            background: #fef2f2 !important;
            border: 1px solid #fecaca !important;
            padding: 5px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
    <?php if ($action === 'take' && $quiz_to_take): ?>
        <!-- Lightweight TFJS and COCO-SSD for AI Phone Detection -->
        <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.20.0/dist/tf.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.3/dist/coco-ssd.min.js"></script>
    <?php endif; ?>
</head>
<body <?= $is_exam ? 'class="exam-lockdown-active"' : '' ?>>
<?php if (!$is_exam): ?>
    <?php include '../includes/student_nav.php'; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-brain" style="color:var(--accent)"></i> Hệ Thống Kiểm Tra Quiz</h1>
            <p style="color: var(--text2); margin-top: 5px;">Thử sức với các bài trắc nghiệm nhanh để ôn luyện kiến thức công nghệ thông tin</p>
        </div>
    </div>

    <!-- Feedback messages -->
    <?php if ($msg): 
        $parts = explode(':', $msg, 2);
        $type = $parts[0];
        $text = $parts[1] ?? '';
    ?>
        <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>" style="display: block; margin-bottom: 20px;">
            <?= htmlspecialchars($text) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['last_quiz_feedback'])): 
        $fb = $_SESSION['last_quiz_feedback'];
        unset($_SESSION['last_quiz_feedback']);
        $fb_diem = round(($fb['score'] / max(1, $fb['total'])) * 10, 2);
    ?>
        <div id="quiz-result-modal" style="position: fixed; inset: 0; z-index: 999999; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; padding: 20px;">
            <div style="background: #ffffff; border-radius: 24px; padding: 36px 30px; max-width: 520px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1.5px solid #e2e8f0; text-align: center; position: relative;">
                <div style="width: 76px; height: 76px; border-radius: 50%; background: <?= $fb_diem >= 5 ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #f59e0b, #d97706)' ?>; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #fff; font-size: 34px; box-shadow: 0 8px 24px <?= $fb_diem >= 5 ? 'rgba(16, 185, 129, 0.35)' : 'rgba(245, 158, 11, 0.35)' ?>;">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <h2 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 6px;">KẾT QUẢ BÀI THI QUIZ</h2>
                <div style="font-size: 13.5px; color: #64748b; margin-bottom: 20px;">Mã đề thi: <strong style="color: #0f172a;"><?= htmlspecialchars($fb['ma_de']) ?></strong></div>

                <div style="display: flex; gap: 14px; justify-content: center; margin-bottom: 22px;">
                    <div style="flex: 1; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 14px 10px;">
                        <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Điểm Số</div>
                        <div style="font-size: 32px; font-weight: 900; color: <?= $fb_diem >= 5 ? '#10b981' : '#ef4444' ?>;"><?= $fb_diem ?><span style="font-size: 16px; color: #94a3b8;">/10</span></div>
                    </div>
                    <div style="flex: 1; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 14px 10px;">
                        <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Số Câu Đúng</div>
                        <div style="font-size: 32px; font-weight: 900; color: #0f172a;"><?= $fb['score'] ?><span style="font-size: 16px; color: #94a3b8;">/<?= $fb['total'] ?></span></div>
                    </div>
                </div>

                <?php if (!empty($fb['nhan_xet'])): ?>
                    <div style="text-align: left; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; border-radius: 12px; padding: 14px 16px; margin-bottom: 24px; font-size: 13.5px; color: #1e293b; line-height: 1.6;">
                        <div style="font-weight: 800; font-size: 12px; color: #2563eb; text-transform: uppercase; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-robot"></i> Đánh giá AI tự động
                        </div>
                        <?= nl2br(htmlspecialchars($fb['nhan_xet'])) ?>
                    </div>
                <?php endif; ?>

                <button type="button" onclick="document.getElementById('quiz-result-modal').remove()" style="width: 100%; padding: 14px; border-radius: 14px; background: linear-gradient(135deg, #e11d48, #be123c); color: #fff; border: none; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 8px 20px rgba(225, 29, 72, 0.35); transition: transform 0.2s ease;">
                    <i class="fa-solid fa-check"></i> Hoàn Tất & Xem Danh Sách
                </button>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Top Edge Tripwire (Phát hiện chuột chạm mép đỉnh Chrome Fullscreen) -->
    <div id="top-edge-tripwire"></div>

    <!-- KIOSK EXAM TOPBAR (ĐỘC LẬP 100%, KHÔNG CÓ SIDEBAR HAY LINK NGOÀI) -->
    <header class="exam-kiosk-topbar">
        <div class="exam-kiosk-left">
            <div class="exam-kiosk-logo-badge">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <div class="exam-kiosk-school-name">TRƯỜNG CAO ĐẲNG CÀ MAU</div>
                <div class="exam-kiosk-exam-title"><i class="fa-solid fa-shield-halved"></i> PHÒNG THI TRỰC TUYẾN CHỐNG GIAN LẬN AI</div>
            </div>
        </div>
        <div class="exam-kiosk-right">
            <div class="exam-kiosk-student-card">
                <img src="<?= $st_av ?>" alt="Avatar" class="exam-kiosk-avatar">
                <div class="exam-kiosk-student-info">
                    <div class="exam-kiosk-student-name"><?= htmlspecialchars($sv['ho_ten'] ?? 'Sinh viên') ?></div>
                    <div class="exam-kiosk-student-id">Mã SV: <strong><?= htmlspecialchars($sv['ma_sv'] ?? 'SV2026') ?></strong> • Lớp: <strong><?= htmlspecialchars($sv['lop'] ?? 'K24CDCNTT') ?></strong></div>
                </div>
            </div>
        </div>
    </header>
    <main class="exam-kiosk-container">
<?php endif; ?>

    <?php if ($is_exam): ?>
        <!-- 1. FULLSCREEN EXAM LOCKDOWN GATE (MÀN HÌNH KHÓA BẮT BUỘC TRƯỚC KHI LÀM BÀI) -->
        <div id="exam-lockdown-gate" class="exam-lockdown-gate">
            <div class="lockdown-gate-card">
                <div class="lockdown-gate-icon">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div class="lockdown-status-badge">
                    <i class="fa-solid fa-shield-halved"></i> CHẾ ĐỘ THI CỐ ĐỊNH - CHỐNG TRA CỨU AI
                </div>
                <h2><?= htmlspecialchars($quiz_to_take['tieu_de']) ?></h2>
                <p class="lockdown-gate-subtitle">Môn: <strong><?= htmlspecialchars($quiz_to_take['ten_mon']) ?></strong> • Thời gian: <strong><?= $quiz_duration_minutes ?> phút</strong> • Mã đề: <strong><?= htmlspecialchars($current_ma_de) ?></strong></p>
                
                <div class="lockdown-rules-box">
                    <div class="lockdown-rule">
                        <i class="fa-solid fa-ban text-danger"></i>
                        <div>
                            <strong>CẤM CHUYỂN TAB HOẶC MỞ TAB KHÁC ĐỂ TRA AI:</strong>
                            <span>Hệ thống khóa toàn màn hình. Rời khỏi tab làm bài quá <strong>5 giây</strong> hoặc vi phạm đến lần thứ <strong>3</strong> sẽ bị <strong>ĐÌNH CHỈ THI & TỰ ĐỘNG THU BÀI NGAY LẬP TỨC</strong>.</span>
                        </div>
                    </div>
                    <div class="lockdown-rule">
                        <i class="fa-solid fa-mobile-screen-button text-warning"></i>
                        <div>
                            <strong>CHỐNG DÙNG CAMERA ĐIỆN THOẠI ĐỂ TRA CỨU:</strong>
                            <span>Webcam AI giám sát nhận diện camera điện thoại trước màn hình. Phát hiện đưa điện thoại vi phạm quá <strong>3 lần</strong> sẽ bị đình chỉ thi và nộp bài.</span>
                        </div>
                    </div>
                    <div class="lockdown-rule">
                        <i class="fa-solid fa-keyboard text-info"></i>
                        <div>
                            <strong>CHỐNG SAO CHÉP (ANTI-COPY TO AI):</strong>
                            <span>Vô hiệu hóa Copy/Paste, chuột phải và phím tắt chuyển cửa sổ. Giới hạn vi phạm: tối đa <strong>3 lần</strong>.</span>
                        </div>
                    </div>
                </div>

                <button type="button" id="btn-enter-lockdown" class="btn-enter-lockdown">
                    <i class="fa-solid fa-expand"></i> BẬT KHÓA TOÀN MÀN HÌNH & BẮT ĐẦU LÀM BÀI
                </button>
                <div style="font-size: 11px; color: #64748b; margin-top: 12px;">
                    <i class="fa-solid fa-circle-info"></i> Câu hỏi thi chỉ được hiển thị sau khi đã kích hoạt chế độ Khóa Màn Hình.
                </div>
            </div>
        </div>

        <!-- 2. EMERGENCY ANTI-AI LOCKOUT OVERLAY (KHI CỐ TÌNH CHUYỂN TAB / ALT-TAB SANG AI) -->
        <div id="exam-anti-ai-lockout" class="exam-anti-ai-lockout" style="display: none;">
            <div class="lockout-alert-box">
                <div class="lockout-siren-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h2>🚨 PHÁT HIỆN RỜI PHÒNG THI / CHUYỂN TAB!</h2>
                <div class="lockout-warning-badge" id="lockout-violation-count">LẦN VI PHẠM: 1 / 3</div>
                <p class="lockout-msg">
                    Bạn vừa rời khỏi màn hình làm bài (chuyển tab hoặc mở ứng dụng khác). Toàn bộ nội dung bài thi đã bị ẩn để ngăn chặn hành vi tra cứu AI!
                </p>
                <div class="lockout-timer-pill">
                    TỰ ĐỘNG HỦY VÀ THU HỒI BÀI THI SAU: <span id="anti-ai-countdown" class="countdown-num">5</span>s
                </div>
                <button type="button" id="btn-unlock-anti-ai" class="btn-unlock-anti-ai">
                    <i class="fa-solid fa-arrow-rotate-left"></i> QUAY LẠI PHÒNG THI & KHÓA LẠI TOÀN MÀN HÌNH
                </button>
            </div>
        </div>

        <!-- 3. WATERMARK MATRIX (CHỐNG CHỤP HÌNH BẰNG ĐIỆN THOẠI) -->
        <div id="exam-watermark-matrix" class="exam-watermark-matrix" aria-hidden="true">
            <?php 
                $wm_text = htmlspecialchars($sv['ho_ten'] ?? 'Sinh viên') . ' • ' . htmlspecialchars($sv['ma_sv'] ?? 'SV2026') . ' • ĐỀ: ' . htmlspecialchars($current_ma_de);
                for ($wmi = 0; $wmi < 15; $wmi++): 
            ?>
                <div class="wm-item"><?= $wm_text ?> • <span class="wm-clock">--:--:--</span></div>
            <?php endfor; ?>
        </div>

        <!-- 4. ANTI-PEEP SHIELD (KHI CHUỘT RỜI ĐỂ CẦM ĐIỆN THOẠI) -->
        <div id="anti-peep-shield" class="anti-peep-shield" style="display: none;">
            <div class="peep-shield-content">
                <i class="fa-solid fa-eye-slash peep-icon"></i>
                <h3>MÀN HÌNH ĐÃ ĐƯỢC ẨN ĐỂ BẢO VỆ ĐỀ THI</h3>
                <p>Phát hiện con trỏ chuột rời khỏi màn hình làm bài. Vui lòng đưa chuột trở lại để tiếp tục làm bài!</p>
            </div>
        </div>

        <!-- 5. AI PROCTORING WEBCAM HUD -->
        <div id="ai-proctoring-hud" class="ai-proctoring-hud">
            <div class="ai-hud-header">
                <div class="ai-hud-title">
                    <span class="ai-hud-dot"></span>
                    <span id="ai-hud-status-text">AI Giám Sát: Đang quét</span>
                </div>
                <div class="ai-hud-badge" id="ai-phone-warning-badge" style="display: none;">
                    <i class="fa-solid fa-triangle-exclamation"></i> PHÁT HIỆN ĐIỆN THOẠI!
                </div>
            </div>
            <div class="ai-video-wrap">
                <video id="proctor-video" autoplay playsinline muted></video>
                <canvas id="proctor-canvas"></canvas>
                <div class="ai-scanner-line"></div>
            </div>
            <div class="ai-hud-footer">
                <span id="ai-scan-detail"><i class="fa-solid fa-shield-virus"></i> Chống camera điện thoại & tra cứu</span>
            </div>
        </div>

        <!-- 6. KHU VỰC NỘI DUNG THI THỰC TẾ (BẬT CHẾ ĐỘ KHÓA MÀN HÌNH MỚI HIỆN RA) -->
        <div id="exam-active-content" class="exam-active-content" style="display: none;">
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
                <div class="qz-timer-right" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <span class="qz-badge-violation" id="qz-violation-indicator">
                        <i class="fa-solid fa-triangle-exclamation"></i> Vi phạm: <span id="val-vi-pham-counter">0</span> / 3
                    </span>
                    <div class="qz-badge-status">
                        <i class="fa-solid fa-lock"></i> Khóa màn hình: BẬT
                    </div>
                </div>
            </div>

        <!-- 7. CYBER EXAM HERO BANNER -->
        <div class="exam-hero-card">
            <div style="flex: 1; min-width: 280px;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span class="badge" style="background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; font-weight: 800; padding: 6px 14px; border-radius: 10px; font-size: 12px;">
                        <i class="fa-solid fa-book-open"></i> <?= htmlspecialchars($quiz_to_take['ten_mon']) ?>
                    </span>
                    <span class="badge" style="background: linear-gradient(135deg, #e11d48, #be123c); color: #fff; font-weight: 800; font-size: 12px; padding: 6px 14px; border-radius: 10px; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);">
                        <i class="fa-solid fa-shuffle"></i> MÃ ĐỀ: <?= htmlspecialchars($current_ma_de) ?>
                    </span>
                    <span class="badge" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-weight: 800; font-size: 12px; padding: 6px 14px; border-radius: 10px;">
                        <i class="fa-solid fa-clock"></i> <?= $quiz_duration_minutes ?> Phút
                    </span>
                    <span class="badge" style="background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; font-weight: 800; font-size: 12px; padding: 6px 14px; border-radius: 10px;">
                        <i class="fa-solid fa-list-check"></i> <?= count($questions) ?> Câu hỏi
                    </span>
                </div>
                <h1 class="exam-hero-title"><?= htmlspecialchars($quiz_to_take['tieu_de']) ?></h1>
                <div class="exam-hero-desc">
                    <i class="fa-solid fa-shield-halved" style="color: #0284c7;"></i> Đề thi và các phương án được mã hóa & xáo trộn ngẫu nhiên. Chế độ giám sát AI chống camera và tra cứu kích hoạt 100%.
                </div>
            </div>
            <div>
                <span class="badge" style="background: #fef2f2; color: #dc2626; border: 1.5px solid #fecaca; font-weight: 800; font-size: 13px; padding: 10px 22px; border-radius: 14px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 10px rgba(220, 38, 38, 0.08);">
                    <span style="width: 9px; height: 9px; background: #ef4444; border-radius: 50%; box-shadow: 0 0 12px #ef4444;"></span>
                    ĐANG TRONG GIỜ THI
                </span>
            </div>
        </div>

        <!-- 8. QUESTION NAVIGATION PALETTE (MA TRẬN ĐIỀU HƯỚNG CÂU HỎI) -->
        <div class="exam-nav-palette">
            <div class="exam-palette-title">
                <i class="fa-solid fa-border-all"></i> Danh Sách Câu Hỏi (<?= count($questions) ?>)
            </div>
            <div class="exam-palette-grid">
                <?php foreach ($questions as $index => $q): ?>
                    <button type="button" class="exam-palette-btn" id="palette_q_<?= $index ?>" onclick="scrollToQuestion(<?= $index ?>)" title="Câu <?= $index + 1 ?>">
                        <?= $index + 1 ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($related_assignments)): ?>
            <div class="card" style="margin-bottom: 26px; border: 1.5px solid #fecdd3; background: #ffffff; padding: 22px; border-radius: 18px; color: #0f172a; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);">
                <h3 style="font-size: 16px; font-weight: 800; color: #e11d48; display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                    <i class="fa-solid fa-circle-exclamation"></i> ĐỀ BÀI CÓ PHẦN TỰ LUẬN ĐI KÈM
                </h3>
                <p style="font-size: 13.5px; color: #64748b; line-height: 1.5; margin-bottom: 12px;">
                    Đề thi này yêu cầu bạn hoàn thành cả phần Trắc nghiệm (bên dưới) và phần Tự luận thực hành (đi kèm).
                </p>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($related_assignments as $as): ?>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px 18px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span style="font-weight: 700; color: #0f172a; font-size: 14px;"><?= htmlspecialchars($as['tieu_de']) ?></span>
                                <span style="font-size: 11.5px; color: #64748b; display: block; margin-top: 3px;"><i class="fa-solid fa-clock"></i> Hạn nộp: <?= date('d/m/Y H:i', strtotime($as['han_nop'])) ?></span>
                            </div>
                            <span style="color: #64748b; font-size: 12px; font-style: italic;">
                                Làm sau khi nộp trắc nghiệm
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($questions)): ?>
            <div class="card" style="padding: 50px; text-align: center; color: #64748b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);">
                Chưa có câu hỏi nào trong bài Quiz này.
            </div>
        <?php else: ?>
            <form id="quiz-exam-form" method="POST" action="?action=submit_quiz" onsubmit="return handleQuizSubmit(this);">
                <input type="hidden" name="quiz_id" value="<?= $quiz_to_take['id'] ?>">
                <input type="hidden" name="ma_de" value="<?= htmlspecialchars($current_ma_de) ?>">
                <input type="hidden" name="exam_code_id" value="<?= (int)$current_exam_code_id ?>">
                <input type="hidden" name="vi_pham_count" id="input_vi_pham_count" value="0">
                <input type="hidden" name="phone_detected_count" id="input_phone_detected_count" value="0">
                
                <?php foreach ($questions as $index => $q): 
                    $qid_val = $q['id'] ?? $index;
                ?>
                    <input type="hidden" name="q_id_<?= $index ?>" value="<?= htmlspecialchars($qid_val) ?>">
                    <input type="hidden" name="question_ids[<?= $index ?>]" value="<?= htmlspecialchars($qid_val) ?>">
                    <div class="q-cyber-card" id="card_q_<?= $index ?>">
                        <div class="q-header-strip">
                            <span class="q-number-pill">
                                <i class="fa-solid fa-circle-question"></i> CÂU HỎI <?= sprintf('%02d', $index + 1) ?>
                            </span>
                            <span class="q-point-pill">1.0 Điểm</span>
                        </div>

                        <div class="q-content-text">
                            <?= htmlspecialchars($q['cau_hoi']) ?>
                        </div>
                        
                        <div class="q-choices-grid">
                            <label class="q-choice-card">
                                <input type="radio" class="q-radio-hidden" name="q_<?= $index ?>" value="A" required onchange="markQuestionAnswered(<?= $index ?>)">
                                <div class="q-choice-inner">
                                    <div class="q-choice-letter">A</div>
                                    <div class="q-choice-text"><?= htmlspecialchars($q['dap_an_a']) ?></div>
                                    <div class="q-choice-state"><i class="fa-solid fa-check"></i></div>
                                </div>
                            </label>

                            <label class="q-choice-card">
                                <input type="radio" class="q-radio-hidden" name="q_<?= $index ?>" value="B" onchange="markQuestionAnswered(<?= $index ?>)">
                                <div class="q-choice-inner">
                                    <div class="q-choice-letter">B</div>
                                    <div class="q-choice-text"><?= htmlspecialchars($q['dap_an_b']) ?></div>
                                    <div class="q-choice-state"><i class="fa-solid fa-check"></i></div>
                                </div>
                            </label>

                            <?php if (!empty($q['dap_an_c'])): ?>
                                <label class="q-choice-card">
                                    <input type="radio" class="q-radio-hidden" name="q_<?= $index ?>" value="C" onchange="markQuestionAnswered(<?= $index ?>)">
                                    <div class="q-choice-inner">
                                        <div class="q-choice-letter">C</div>
                                        <div class="q-choice-text"><?= htmlspecialchars($q['dap_an_c']) ?></div>
                                        <div class="q-choice-state"><i class="fa-solid fa-check"></i></div>
                                    </div>
                                </label>
                            <?php endif; ?>

                            <?php if (!empty($q['dap_an_d'])): ?>
                                <label class="q-choice-card">
                                    <input type="radio" class="q-radio-hidden" name="q_<?= $index ?>" value="D" onchange="markQuestionAnswered(<?= $index ?>)">
                                    <div class="q-choice-inner">
                                        <div class="q-choice-letter">D</div>
                                        <div class="q-choice-text"><?= htmlspecialchars($q['dap_an_d']) ?></div>
                                        <div class="q-choice-state"><i class="fa-solid fa-check"></i></div>
                                    </div>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" id="btn-submit-exam" class="btn-submit-exam-kiosk">
                    <i class="fa-solid fa-paper-plane"></i> NỘP BÀI LÀM QUIZ (MÃ ĐỀ: <?= htmlspecialchars($current_ma_de) ?>)
                </button>
            </form>
        <?php endif; ?>
        </div> <!-- /#exam-active-content -->
    </main> <!-- /.exam-kiosk-container -->

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
                    Điểm số của bạn: <span style="color: var(--accent);"><?= round(($fb['score'] / max(1, $fb['total'])) * 10, 2) ?></span> / 10 (Đúng <?= $fb['score'] ?> / <?= $fb['total'] ?> câu) 
                    <?php if (!empty($fb['ma_de'])): ?>
                        <span class="badge" style="background: var(--accent); color:#fff; font-size:12px; margin-left: 10px;">Mã đề: <?= htmlspecialchars($fb['ma_de']) ?></span>
                    <?php endif; ?>
                </div>
                <div style="background: var(--bg3); border: 1px solid var(--border); padding: 15px; border-radius: 12px; font-size: 13.5px; line-height: 1.6; color: var(--text2);">
                    <strong style="color: var(--text); display: block; margin-bottom: 5px;"><i class="fa-solid fa-robot"></i> Nhận xét từ AI:</strong>
                    <?= htmlspecialchars($fb['nhan_xet']) ?>
                </div>
                <div style="margin-top: 14px; display: flex; gap: 10px; align-items: center;">
                    <span style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.25); font-weight: 700; font-size: 13px; padding: 8px 16px; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-lock"></i> Đã hoàn thành (Mỗi sinh viên chỉ làm 1 lần)
                    </span>
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
                            <span style="color:#10b981; font-weight: 700;"><i class="fa-solid fa-square-poll-vertical"></i> Điểm bài thi: <?= round(($q['score'] / max(1, $q['total_questions'])) * 10, 2) ?> / 10</span>
                        <?php else: ?>
                            <span style="color:#64748b;"><i class="fa-solid fa-circle-minus"></i> Chưa làm</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <?php if ($has_attempt): ?>
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <span style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1.5px solid rgba(16, 185, 129, 0.25); font-weight: 700; font-size: 13px; padding: 8px 16px; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-circle-check"></i> Đã làm
                            </span>
                        </div>
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
    </div> <!-- Đóng .main-content của student_nav.php khi ở Quiz List -->
    <?php endif; ?>
    
    <script>
    let isSubmittingQuiz = false;

    function handleQuizSubmit(form) {
        isSubmittingQuiz = true;
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            setTimeout(() => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang nộp bài và AI phân tích kết quả...';
                btn.style.opacity = '0.7';
                btn.style.cursor = 'not-allowed';
            }, 20);
        }
        return true;
    }

    // ── Question Palette Helpers ──────────────────────────
    window.markQuestionAnswered = function(index) {
        const btn = document.getElementById('palette_q_' + index);
        if (btn) {
            btn.classList.add('answered');
            btn.innerHTML = '<i class="fa-solid fa-check" style="font-size: 11px;"></i>';
        }
    };

    window.scrollToQuestion = function(index) {
        const card = document.getElementById('card_q_' + index);
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.style.borderColor = '#e11d48';
            setTimeout(() => { card.style.borderColor = ''; }, 1200);
        }
    };

    <?php if ($action === 'take' && $quiz_to_take): ?>
    // ══════════════════════════════════════════════════════════════════════════
    // ANTI-AI HARD LOCKDOWN ENGINE: FULLSCREEN, POINTER LOCK & SIREN DISQUALIFY
    // ══════════════════════════════════════════════════════════════════════════
    (function() {
        let remainingSeconds = <?= (int)$remaining_seconds ?>;
        const totalDuration = <?= (int)$duration_seconds ?>;
        const timerDisplay = document.getElementById('timer-display');
        const timerProgress = document.getElementById('timer-progress');
        const timerBar = document.getElementById('quiz-floating-timer');
        const examForm = document.getElementById('quiz-exam-form');
        const antiCheatToast = document.getElementById('anti-cheat-toast');
        const gateOverlay = document.getElementById('exam-lockdown-gate');
        const activeContent = document.getElementById('exam-active-content');
        const lockoutOverlay = document.getElementById('exam-anti-ai-lockout');
        const cdSpan = document.getElementById('anti-ai-countdown');
        const btnEnterLockdown = document.getElementById('btn-enter-lockdown');
        const btnUnlockAntiAi = document.getElementById('btn-unlock-anti-ai');
        const inputViPham = document.getElementById('input_vi_pham_count');
        const inputPhoneDetected = document.getElementById('input_phone_detected_count');
        const valViPhamCounter = document.getElementById('val-vi-pham-counter');
        const badgeLockoutCount = document.getElementById('lockout-violation-count');
        const antiPeepShield = document.getElementById('anti-peep-shield');
        const proctorHud = document.getElementById('ai-proctoring-hud');
        const phoneWarningBadge = document.getElementById('ai-phone-warning-badge');

        let examStarted = false;
        let isLockedOut = false;
        let violationCount = 0;
        let phoneViolationCount = 0;
        let awaySecondsLeft = 5;
        let awayTimer = null;
        let timerInterval = null;
        let sirenInterval = null;
        let lastPhoneAlertTime = 0;

        // 1. WEB AUDIO API SIREN ALARM
        function playSirenBeep() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(960, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(480, ctx.currentTime + 0.32);
                gain.gain.setValueAtTime(0.35, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.32);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.34);
            } catch(e) {}
        }

        function startSirenLoop() {
            stopSirenLoop();
            playSirenBeep();
            sirenInterval = setInterval(playSirenBeep, 450);
        }

        function stopSirenLoop() {
            if (sirenInterval) {
                clearInterval(sirenInterval);
                sirenInterval = null;
            }
        }

        // 2. FULLSCREEN API HELPER
        function enterFullscreen() {
            const el = document.documentElement;
            if (el.requestFullscreen) {
                el.requestFullscreen().catch(err => console.log('Fullscreen error:', err));
            } else if (el.webkitRequestFullscreen) {
                el.webkitRequestFullscreen();
            } else if (el.mozRequestFullScreen) {
                el.mozRequestFullScreen();
            } else if (el.msRequestFullscreen) {
                el.msRequestFullscreen();
            }
        }

        function formatTime(sec) {
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }

        function updateTimerUI() {
            if (timerDisplay) timerDisplay.textContent = formatTime(remainingSeconds);
            if (timerProgress && totalDuration > 0) {
                const pct = Math.max(0, Math.min(100, (remainingSeconds / totalDuration) * 100));
                timerProgress.style.width = pct + '%';
            }
            if (timerBar) {
                if (remainingSeconds <= 120) timerBar.className = 'qz-sticky-timer critical';
                else if (remainingSeconds <= 300) timerBar.className = 'qz-sticky-timer warning';
                else timerBar.className = 'qz-sticky-timer';
            }
        }

        // 2b. KEYBOARD & POINTER LOCK API (CHROME / CHROMIUM KIOSK LOCK)
        async function lockKeyboardAndPointer() {
            try {
                if (navigator.keyboard && navigator.keyboard.lock) {
                    await navigator.keyboard.lock(['Escape', 'KeyT', 'KeyN', 'KeyW', 'Tab', 'F11', 'F12']);
                }
            } catch(e) {
                console.log('Keyboard lock API note:', e);
            }
        }

        // 3. ENTER LOCKDOWN BUTTON (START EXAM)
        if (btnEnterLockdown) {
            btnEnterLockdown.addEventListener('click', async function() {
                examStarted = true;
                enterFullscreen();
                await lockKeyboardAndPointer();

                if (gateOverlay) {
                    gateOverlay.style.opacity = '0';
                    setTimeout(() => gateOverlay.style.display = 'none', 300);
                }
                if (activeContent) {
                    activeContent.style.display = 'block';
                    activeContent.style.visibility = 'visible';
                }

                // Start countdown timer
                startCountdownTimer();

                // Initialize AI Webcam proctoring
                initWebcamProctoring();
            });
        }

        // 4. COUNTDOWN TIMER
        function startCountdownTimer() {
            updateTimerUI();
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(function() {
                if (isSubmittingQuiz) {
                    clearInterval(timerInterval);
                    return;
                }
                remainingSeconds--;
                updateTimerUI();

                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                    submitAutoDisqualified('Hết thời gian làm bài thi');
                }
            }, 1000);
        }

        // 5. AWAY FROM EXAM DETECTOR (MỞ TAB MỚI / CHUYỂN TAB / MẤT FOCUS / THOÁT FULLSCREEN)
        function handleAwayFromExam(reason) {
            if (!examStarted || isSubmittingQuiz || isLockedOut) return;

            isLockedOut = true;

            // 1. TĂNG NGAY SỐ LẦN VI PHẠM TỨC THÌ (GHI NHẬN NGAY KHI RỜI ĐI)
            violationCount++;
            if (inputViPham) inputViPham.value = violationCount;
            if (valViPhamCounter) valViPhamCounter.textContent = violationCount;
            if (badgeLockoutCount) badgeLockoutCount.textContent = 'LẦN VI PHẠM: ' + violationCount + ' / 3';

            // 2. Ẩn toàn bộ nội dung đề thi ngay lập tức (không cho nhìn đề để gõ vào AI)
            if (activeContent) activeContent.style.visibility = 'hidden';

            // 3. Hú còi báo động dồn dập
            startSirenLoop();

            // 4. Hiện màn hình cảnh báo khóa đỏ
            if (lockoutOverlay) lockoutOverlay.style.display = 'flex';

            document.title = '🚨 [CẢNH BÁO VI PHẠM THI] QUAY LẠI PHÒNG THI NGAY!';

            // 5. NẾU VI PHẠM LẦN 3 ➔ THU BÀI & ĐÌNH CHỈ THI NGAY LẬP TỨC (DISQUALIFIED)
            if (violationCount >= 3) {
                if (awayTimer) clearInterval(awayTimer);
                setTimeout(() => {
                    submitAutoDisqualified('Vi phạm mở tab mới / rời phòng thi lần 3 (' + reason + ')');
                }, 700);
                return;
            }

            // 6. CÁC LẦN TRƯỚC: Đếm ngược 5 giây. Nếu sau 5 giây không quay lại ➔ Hủy bài & thu bài luôn
            awaySecondsLeft = 5;
            if (cdSpan) cdSpan.textContent = awaySecondsLeft;

            if (awayTimer) clearInterval(awayTimer);
            awayTimer = setInterval(() => {
                awaySecondsLeft--;
                if (cdSpan) cdSpan.textContent = awaySecondsLeft;

                if (awaySecondsLeft <= 0) {
                    clearInterval(awayTimer);
                    stopSirenLoop();
                    submitAutoDisqualified('Gian lận: Rời phòng thi / mở tab mới quá 5 giây (' + reason + ')');
                }
            }, 1000);
        }

        // 6. RETURN TO EXAM HANDLER
        function handleReturnToExam() {
            if (!examStarted || isSubmittingQuiz || !isLockedOut) return;

            stopSirenLoop();
            if (awayTimer) {
                clearInterval(awayTimer);
                awayTimer = null;
            }

            if (badgeLockoutCount) badgeLockoutCount.textContent = 'LẦN VI PHẠM: ' + violationCount + ' / 3';

            if (violationCount >= 3) {
                submitAutoDisqualified('Vi phạm mở tab mới / rời phòng thi lần 3');
            }
        }

        // 7. UNLOCK BUTTON AFTER RETURNING
        if (btnUnlockAntiAi) {
            btnUnlockAntiAi.addEventListener('click', async function() {
                if (violationCount >= 3) {
                    submitAutoDisqualified('Vi phạm mở tab mới / rời phòng thi lần 3');
                    return;
                }

                isLockedOut = false;
                enterFullscreen();
                await lockKeyboardAndPointer();
                if (lockoutOverlay) lockoutOverlay.style.display = 'none';
                if (activeContent) activeContent.style.visibility = 'visible';
                document.title = 'Làm Quiz - Cổng sinh viên';
            });
        }

        // 8. AUTO-SUBMIT DISQUALIFIED FUNCTION
        function submitAutoDisqualified(reason) {
            isSubmittingQuiz = true;
            stopSirenLoop();
            alert('🚨 ĐÌNH CHỈ THI & TỰ ĐỘNG THU BÀI!\n\nLý do: ' + reason + '.\nToàn bộ kết quả bài làm đang được thu hồi về hệ thống.');
            if (examForm) {
                examForm.querySelectorAll('input[type="radio"]').forEach(r => r.removeAttribute('required'));
                examForm.submit();
            }
        }

        // 9. EVENT LISTENERS FOR TAB SWITCHING & BLUR & VISIBILITY
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                handleAwayFromExam('Bạn vừa chuyển sang tab khác / mở tab mới');
            } else {
                handleReturnToExam();
            }
        });

        window.addEventListener('blur', function() {
            handleAwayFromExam('Bạn vừa chuyển khỏi cửa sổ bài thi');
        });

        window.addEventListener('focus', function() {
            handleReturnToExam();
        });

        window.addEventListener('pagehide', function() {
            handleAwayFromExam('Cửa sổ bài thi bị ẩn');
        });

        document.addEventListener('fullscreenchange', function() {
            if (examStarted && !document.fullscreenElement && !isSubmittingQuiz) {
                handleAwayFromExam('Bạn vừa thoát khỏi chế độ Toàn Màn Hình');
            }
        });

        // 10. TOP EDGE TRIPWIRE & TAB BAR PROTECTION
        const topTripwire = document.getElementById('top-edge-tripwire');
        if (topTripwire) {
            topTripwire.addEventListener('mouseenter', function() {
                if (examStarted && !isLockedOut) {
                    playSirenBeep();
                    if (antiCheatToast) {
                        antiCheatToast.style.display = 'flex';
                        antiCheatToast.innerHTML = '<i class="fa-solid fa-lock"></i> <span>⚠️ CẢNH BÁO: Không được di chuột lên mép đỉnh để mở tab mới!</span>';
                        setTimeout(() => antiCheatToast.style.display = 'none', 2500);
                    }
                }
            });
        }

        window.addEventListener('mousemove', function(e) {
            if (examStarted && !isLockedOut && e.clientY <= 6) {
                if (antiCheatToast) {
                    antiCheatToast.style.display = 'flex';
                    antiCheatToast.innerHTML = '<i class="fa-solid fa-lock"></i> <span>⚠️ CẢNH BÁO: Không được chạm mép trình duyệt! Bài thi sẽ bị khóa.</span>';
                    setTimeout(() => antiCheatToast.style.display = 'none', 2000);
                }
            }
        });

        // 11. ANTI-PEEP BLUR SHIELD (WHEN MOUSE LEAVES SCREEN TO HOLD PHONE)
        document.addEventListener('mouseleave', function() {
            if (examStarted && !isSubmittingQuiz && !isLockedOut) {
                if (antiPeepShield) antiPeepShield.style.display = 'flex';
            }
        });
        document.addEventListener('mouseenter', function() {
            if (antiPeepShield) antiPeepShield.style.display = 'none';
        });

        // 12. DYNAMIC WATERMARK CLOCK
        function updateWatermarkClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('vi-VN');
            document.querySelectorAll('.wm-clock').forEach(el => el.textContent = timeString);
        }
        setInterval(updateWatermarkClock, 1000);
        updateWatermarkClock();

        // 13. AI WEBCAM PROCTORING
        function initWebcamProctoring() {
            const video = document.getElementById('proctor-video');
            const canvas = document.getElementById('proctor-canvas');
            if (!video || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;

            navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240, facingMode: 'user' }, audio: false })
                .then(stream => {
                    video.srcObject = stream;
                    video.play();

                    if (typeof cocoSsd !== 'undefined') {
                        cocoSsd.load().then(model => {
                            const hudStatusText = document.getElementById('ai-hud-status-text');
                            if (hudStatusText) hudStatusText.textContent = 'AI Giám Sát: Tích cực';
                            const ctx = canvas ? canvas.getContext('2d') : null;

                            setInterval(async () => {
                                if (isSubmittingQuiz || !examStarted || isLockedOut) return;
                                try {
                                    if (video.readyState === 4) {
                                        const predictions = await model.detect(video);
                                        if (ctx && canvas) {
                                            canvas.width = video.videoWidth;
                                            canvas.height = video.videoHeight;
                                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                                        }

                                        let detectedPhone = false;
                                        let detectScoreText = '';
                                        for (let p of predictions) {
                                            if (['cell phone', 'remote', 'camera', 'telephone'].includes(p.class) && p.score >= 0.35) {
                                                detectedPhone = true;
                                                detectScoreText = Math.round(p.score * 100) + '%';
                                                if (ctx) {
                                                    ctx.strokeStyle = '#ef4444';
                                                    ctx.lineWidth = 4;
                                                    ctx.strokeRect(...p.bbox);
                                                    ctx.fillStyle = 'rgba(239, 68, 68, 0.85)';
                                                    ctx.fillRect(p.bbox[0], p.bbox[1] > 22 ? p.bbox[1] - 24 : 0, Math.max(140, p.bbox[2]), 24);
                                                    ctx.fillStyle = '#ffffff';
                                                    ctx.font = 'bold 13px Outfit, sans-serif';
                                                    ctx.fillText('🚨 ĐIỆN THOẠI (' + detectScoreText + ')', p.bbox[0] + 6, p.bbox[1] > 22 ? p.bbox[1] - 7 : 16);
                                                }
                                                break;
                                            }
                                        }

                                        if (detectedPhone) {
                                            handlePhoneDetected(detectScoreText);
                                        }
                                    }
                                } catch(e){}
                            }, 500);
                        }).catch(err => console.log('COCO-SSD model error:', err));
                    }
                })
                .catch(err => console.log('Webcam permission error:', err));
        }

        // 14. PHONE DETECTION HANDLER (CỘNG TRỰC TIẾP VÀO VI PHẠM BÀI THI)
        function handlePhoneDetected(scoreText) {
            const now = Date.now();
            if (now - lastPhoneAlertTime < 2800) return;
            lastPhoneAlertTime = now;
            phoneViolationCount++;

            // Cộng vào vi phạm chung phòng thi
            violationCount++;
            if (inputViPham) inputViPham.value = violationCount;
            if (valViPhamCounter) valViPhamCounter.textContent = violationCount;
            if (badgeLockoutCount) badgeLockoutCount.textContent = 'LẦN VI PHẠM: ' + violationCount + ' / 3';

            if (inputPhoneDetected) inputPhoneDetected.value = phoneViolationCount;
            playSirenBeep();

            if (proctorHud) proctorHud.classList.add('alert-phone');
            if (phoneWarningBadge) {
                phoneWarningBadge.style.display = 'block';
                phoneWarningBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> PHÁT HIỆN CAMERA ĐIỆN THOẠI! (' + (scoreText || 'CẢNH BÁO') + ')';
            }

            if (antiCheatToast) {
                antiCheatToast.style.display = 'flex';
                antiCheatToast.style.background = 'rgba(239, 68, 68, 0.98)';
                antiCheatToast.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="font-size:22px;"></i> <div><strong style="font-size:14px;display:block;">🚨 PHÁT HIỆN CAMERA ĐIỆN THOẠI TRƯỚC MÀN HÌNH!</strong><span style="font-size:12px;">Đã ghi nhận vi phạm: ' + violationCount + ' / 3. Vi phạm lần 3 sẽ bị đình chỉ thi ngay!</span></div>';
            }

            // Nếu đạt 3 lần vi phạm ➔ Tự động đình chỉ thi và nộp bài
            if (violationCount >= 3) {
                setTimeout(() => {
                    submitAutoDisqualified('Sử dụng camera điện thoại trước màn hình lần 3');
                }, 800);
                return;
            }

            setTimeout(() => {
                if (proctorHud) proctorHud.classList.remove('alert-phone');
                if (phoneWarningBadge) phoneWarningBadge.style.display = 'none';
                if (antiCheatToast) antiCheatToast.style.display = 'none';
            }, 3500);
        }

        // 15. BLOCK DANGEROUS SHORTCUTS, COPY, DEVTOOLS
        window.addEventListener('beforeunload', function(e) {
            if (!isSubmittingQuiz && examStarted) {
                e.preventDefault();
                e.returnValue = '⚠️ Bạn đang trong phòng thi. Nếu thoát ra, bài làm sẽ bị hủy!';
                return e.returnValue;
            }
        });

        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            if (!isSubmittingQuiz && examStarted) {
                history.pushState(null, null, location.href);
                handleAwayFromExam('Không được nhấn nút Back / Forward rời khỏi bài thi!');
            }
        };

        // 15. BLOCK DANGEROUS SHORTCUTS, COPY, DEVTOOLS & NEW TAB (CAPTURE PHASE)
        window.addEventListener('keydown', function(e) {
            if (!examStarted) return;
            const key = e.key.toLowerCase();

            // Block F11, F12, PrintScreen
            if (e.key === 'F11' || e.key === 'F12' || e.key === 'PrintScreen') {
                e.preventDefault();
                e.stopPropagation();
                try { navigator.clipboard.writeText(''); } catch(ex){}
                handleAwayFromExam('Phát hiện phím chức năng cấm (' + e.key + ')');
                return false;
            }

            // Block Escape key (prevent exiting fullscreen to inspect or open tabs)
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                handleAwayFromExam('Bấm phím ESC để thoát toàn màn hình');
                return false;
            }

            // Block Ctrl/Cmd combinations:
            // Ctrl+T (New tab), Ctrl+N (New window), Ctrl+W (Close tab), Ctrl+Tab (Next tab), Ctrl+Shift+N (Incognito)
            // Ctrl+Shift+I / Ctrl+Shift+J (DevTools), Ctrl+U (View source), Ctrl+P (Print), Ctrl+S (Save)
            // Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+A (Copy/Paste to AI), Ctrl+L / Alt+D (Address bar)
            if (e.ctrlKey || e.metaKey) {
                if (['t','n','w','tab','u','p','s','c','v','x','a','j','h','l','r'].includes(key) || (e.shiftKey && ['n','t','i','c','j'].includes(key))) {
                    e.preventDefault();
                    e.stopPropagation();
                    handleAwayFromExam('Cố tình sử dụng phím tắt mở tab mới hoặc trình duyệt (' + (e.ctrlKey ? 'Ctrl+' : '') + (e.shiftKey ? 'Shift+' : '') + key.toUpperCase() + ')');
                    return false;
                }
            }

            // Block Alt combinations: Alt+Tab, Alt+F4, Alt+Left, Alt+Right
            if (e.altKey) {
                e.preventDefault();
                e.stopPropagation();
                handleAwayFromExam('Phát hiện phím tắt Alt chuyển cửa sổ hoặc trang');
                return false;
            }
        }, true);

        // Block middle click (auxclick button 1 = open in new tab)
        document.addEventListener('auxclick', function(e) {
            if (examStarted) {
                e.preventDefault();
                e.stopPropagation();
                handleAwayFromExam('Thao tác chuột giữa (mở tab mới)');
            }
        }, true);

        // Block right click context menu
        document.addEventListener('contextmenu', function(e) {
            if (examStarted) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);

        // Block text selection & copy/drag
        document.addEventListener('copy', function(e) {
            if (examStarted) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);

        document.addEventListener('cut', function(e) {
            if (examStarted) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);

        document.addEventListener('paste', function(e) {
            if (examStarted) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);

        document.addEventListener('dragstart', function(e) {
            if (examStarted) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    })();
    <?php endif; ?>
    </script>
</body>
</html>
