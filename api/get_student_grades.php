<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập!']);
    exit;
}

$db = getDB();
$student_id = (int)($_GET['student_id'] ?? 0);

if ($student_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Thiếu ID sinh viên!']);
    exit;
}

// Check permission: Admin, Teacher, or the Student themselves
if (!isAdmin() && !isTeacher()) {
    $sess_sv_id = (int)($_SESSION['student_id'] ?? ($_SESSION['sv_id'] ?? 0));
    if ($sess_sv_id !== $student_id) {
        echo json_encode(['success' => false, 'error' => 'Bạn không có quyền xem điểm của sinh viên này!']);
        exit;
    }
}

// 1. Fetch Student Info
$stmt_sv = $db->prepare("SELECT s.*, u.username FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ? LIMIT 1");
$stmt_sv->bind_param("i", $student_id);
$stmt_sv->execute();
$student = $stmt_sv->get_result()->fetch_assoc();
$stmt_sv->close();

if (!$student) {
    echo json_encode(['success' => false, 'error' => 'Không tìm thấy sinh viên!']);
    exit;
}

// 2. Fetch Quiz Attempts
$quiz_attempts = [];
$stmt_quiz = $db->prepare("
    SELECT qa.*, q.tieu_de as quiz_title, m.ten_mon as subject_name
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    LEFT JOIN mon_hoc m ON q.mon_hoc_id = m.id
    WHERE qa.student_id = ?
    ORDER BY qa.attempted_at DESC
");
if ($stmt_quiz) {
    $stmt_quiz->bind_param("i", $student_id);
    $stmt_quiz->execute();
    $quiz_attempts = $stmt_quiz->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_quiz->close();
}

// 3. Fetch Practice Submissions
$practice_subs = [];
$stmt_prac = $db->prepare("
    SELECT ps.*, sess.mo_ta as session_title, sess.start_time, sess.end_time
    FROM practice_submissions ps
    JOIN practice_sessions sess ON ps.session_id = sess.id
    WHERE ps.student_id = ?
    ORDER BY ps.submitted_at DESC
");
if ($stmt_prac) {
    $stmt_prac->bind_param("i", $student_id);
    $stmt_prac->execute();
    $practice_subs = $stmt_prac->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_prac->close();
}

// 4. Fetch Assignment Submissions
$assignment_subs = [];
$stmt_ass = $db->prepare("
    SELECT sub.*, a.tieu_de as assignment_title, m.ten_mon as subject_name, a.han_nop, g.ho_ten as teacher_name
    FROM submissions sub
    JOIN assignments a ON sub.assignment_id = a.id
    LEFT JOIN mon_hoc m ON a.mon_hoc_id = m.id
    LEFT JOIN giang_vien g ON a.giang_vien_id = g.id
    WHERE sub.student_id = ?
    ORDER BY sub.submitted_at DESC
");
if ($stmt_ass) {
    $stmt_ass->bind_param("i", $student_id);
    $stmt_ass->execute();
    $assignment_subs = $stmt_ass->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_ass->close();
}

// 5. Fetch Projects (Do An)
$projects = [];
$stmt_da = $db->prepare("
    SELECT d.*, g.ho_ten as teacher_name, n.ten_nhom
    FROM do_an d
    LEFT JOIN giang_vien g ON d.giang_vien_id = g.id
    LEFT JOIN nhom_do_an n ON d.nhom_id = n.id
    WHERE d.sinh_vien_id = ?
    ORDER BY d.id DESC
");
if ($stmt_da) {
    $stmt_da->bind_param("i", $student_id);
    $stmt_da->execute();
    $projects = $stmt_da->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_da->close();
}

// 6. Fetch Subject Grades (Table diem if exists)
$subject_grades = [];
$chk_diem = @$db->query("SHOW TABLES LIKE 'diem'");
if ($chk_diem && $chk_diem->num_rows > 0) {
    $stmt_d = $db->prepare("
        SELECT d.*, m.ten_mon, m.so_tin_chi
        FROM diem d
        JOIN mon_hoc m ON d.mon_hoc_id = m.id
        WHERE d.student_id = ?
        ORDER BY m.ten_mon ASC
    ");
    if ($stmt_d) {
        $stmt_d->bind_param("i", $student_id);
        $stmt_d->execute();
        $subject_grades = $stmt_d->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_d->close();
    }
}

$db->close();

echo json_encode([
    'success' => true,
    'student' => [
        'id' => $student['id'],
        'ma_sv' => $student['ma_sv'],
        'ho_ten' => $student['ho_ten'],
        'lop' => $student['lop'],
        'khoa' => $student['khoa'],
        'email' => $student['email'],
        'avatar' => $student['avatar']
    ],
    'quiz_attempts' => $quiz_attempts,
    'practice_submissions' => $practice_subs,
    'assignment_submissions' => $assignment_subs,
    'projects' => $projects,
    'subject_grades' => $subject_grades
], JSON_UNESCAPED_UNICODE);
