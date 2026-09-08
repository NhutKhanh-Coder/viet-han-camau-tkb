<?php
require_once '../config.php';
require_once '../includes/code_access.php';
requireStudent();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Phương thức yêu cầu không hợp lệ.']);
    exit;
}

$session_id = intval($_POST['session_id'] ?? 0);
$sv_id = $_SESSION['student_id'] ?? 0;

if ($session_id <= 0 || $sv_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Thiếu thông tin phiên hoặc sinh viên.']);
    exit;
}

if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Không nhận được tệp tin nén bài làm.']);
    exit;
}

if ($_FILES['zip_file']['size'] > 50 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Tệp nộp bài không được vượt quá 50 MB.']);
    exit;
}

$zip_name = strtolower($_FILES['zip_file']['name'] ?? '');
if (substr($zip_name, -4) !== '.zip') {
    echo json_encode(['success' => false, 'error' => 'Bài nộp phải là tệp ZIP.']);
    exit;
}

function remove_accents_vietnamese($str) {
    $unicode = array(
        'a'=>'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd'=>'đ',
        'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i'=>'í|ì|ỉ|ĩ|ị',
        'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y'=>'ý|ỳ|ỷ|ỹ|ị',
        'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'D'=>'Đ',
        'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
        'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ị',
    );
    foreach($unicode as $nonUnicode=>$uni){
        $str = preg_replace("/($uni)/i", $nonUnicode, $str);
    }
    return $str;
}

$db = getDB();

$stmt_sv = $db->prepare("SELECT ma_sv, ho_ten, lop FROM students WHERE id = ?");
$stmt_sv->bind_param("i", $sv_id);
$stmt_sv->execute();
$sv_info = $stmt_sv->get_result()->fetch_assoc();
$stmt_sv->close();

$ma_sv = $sv_info['ma_sv'] ?? 'sv_' . $sv_id;
$ho_ten = $sv_info['ho_ten'] ?? 'unknown';
$lop = $sv_info['lop'] ?? '';
$ho_ten_clean = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', remove_accents_vietnamese($ho_ten)));

$active_session = getValidatedActivePracticeSession($db, $sv_id, $session_id, $lop);
if (!$active_session) {
    $db->close();
    echo json_encode(['success' => false, 'error' => 'Phiên thi không hợp lệ, đã kết thúc hoặc chưa đến giờ làm bài.']);
    exit;
}

// Check if submission already exists (NO editing/re-submitting allowed once submitted)
$stmt_check = $db->prepare("SELECT id FROM practice_submissions WHERE student_id = ? AND session_id = ?");
$stmt_check->bind_param("ii", $sv_id, $session_id);
$stmt_check->execute();
$res = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

if ($res) {
    $db->close();
    echo json_encode(['success' => false, 'error' => 'Bạn đã nộp bài rồi. Theo quy định, bài thực hành đã nộp không được phép chỉnh sửa hoặc nộp lại.']);
    exit;
}

// 1. Create upload folder
$upload_dir = '../assets/uploads/practice_submissions/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 2. Build unique filename containing student's code and name
$filename = $ma_sv . '_' . $ho_ten_clean . '_session_' . $session_id . '_' . time() . '.zip';
$file_path = '/tkb/assets/uploads/practice_submissions/' . $filename;
$target_file = $upload_dir . $filename;

if (move_uploaded_file($_FILES['zip_file']['tmp_name'], $target_file)) {
    // 3. Save to database (Insert or Update if exists)
    // Insert
    $stmt_ins = $db->prepare("INSERT INTO practice_submissions (session_id, student_id, file_path) VALUES (?, ?, ?)");
    $stmt_ins->bind_param("iis", $session_id, $sv_id, $file_path);
    $stmt_ins->execute();
    $stmt_ins->close();
    
    $is_early = (time() < strtotime($active_session['end_time']));
    $db->close();
    echo json_encode([
        'success' => true,
        'early' => $is_early,
        'message' => 'Nộp bài thành công. Bài làm của bạn đã được ghi nhận và không thể chỉnh sửa.',
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Không thể lưu file nộp bài lên máy chủ.']);
}
