<?php
require_once '../config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$descriptor = $input['descriptor'] ?? null;

if (!$descriptor || !is_array($descriptor)) {
    echo json_encode(['success'=>false,'message'=>'Dữ liệu khuôn mặt không hợp lệ']); exit();
}

$role = $input['role'] ?? null;

$db = getDB();
$res_students = $db->query("SELECT s.id, s.ma_sv, s.ho_ten, s.face_descriptor, u.id as user_id, u.role 
                   FROM students s JOIN users u ON s.user_id=u.id 
                   WHERE s.face_descriptor IS NOT NULL AND s.face_descriptor != ''");

$res_users = $db->query("SELECT id as user_id, username as ma_sv, ho_ten, face_descriptor, role 
                   FROM users 
                   WHERE role IN ('admin', 'teacher', 'principal') AND face_descriptor IS NOT NULL AND face_descriptor != ''");

$threshold = 0.6; // Tăng độ bao dung (tolerance) để dễ nhận diện hơn
$bestMatch = null;
$bestDist  = PHP_FLOAT_MAX;
$hasFaceData = false;

$all_faces = [];
if ($role === 'student' || !$role) {
    while ($row = $res_students->fetch_assoc()) $all_faces[] = $row;
}
if (in_array($role, ['admin', 'teacher', 'principal']) || !$role) {
    while ($row = $res_users->fetch_assoc()) {
        if (!$role || $row['role'] === $role) {
            $all_faces[] = $row;
        }
    }
}

foreach ($all_faces as $row) {
    $saved = json_decode($row['face_descriptor'], true);
    if (!$saved || !is_array($saved)) continue;
    $hasFaceData = true;
    // Euclidean distance
    $dist = 0;
    foreach ($descriptor as $i => $val) {
        $diff = $val - ($saved[$i] ?? 0);
        $dist += $diff * $diff;
    }
    $dist = sqrt($dist);
    if ($dist < $bestDist) {
        $bestDist  = $dist;
        $bestMatch = $row;
    }
}

if (!$hasFaceData) {
    echo json_encode(['success'=>false,'message'=>'Hệ thống chưa có dữ liệu khuôn mặt nào. Bạn cần đăng nhập bằng mật khẩu và đăng ký khuôn mặt trước!']);
} elseif ($bestMatch && $bestDist < $threshold) {
    $_SESSION['user_id']  = $bestMatch['user_id'];
    $_SESSION['username'] = $bestMatch['ma_sv'];
    $_SESSION['role']     = $bestMatch['role'];
    $_SESSION['ho_ten']   = $bestMatch['ho_ten'];
    
    if ($bestMatch['role'] === 'student') {
        $_SESSION['student_id'] = $bestMatch['id'];
        $_SESSION['ma_sv']      = $bestMatch['ma_sv'];
        $redirect = '/tkb/student/dashboard.php';
    } elseif ($bestMatch['role'] === 'teacher') {
        $st = $db->prepare("SELECT id FROM giang_vien WHERE user_id=?");
        $st->bind_param("i", $bestMatch['user_id']);
        $st->execute();
        $gv = $st->get_result()->fetch_assoc();
        if ($gv) {
            $_SESSION['giang_vien_id'] = $gv['id'];
        }
        $redirect = '/tkb/teacher/dashboard.php';
    } elseif ($bestMatch['role'] === 'principal') {
        $redirect = '/tkb/principal/dashboard.php';
    } else {
        $redirect = '/tkb/admin/dashboard.php';
    }

    echo json_encode([
        'success'  => true,
        'name'     => $bestMatch['ho_ten'],
        'redirect' => $redirect
    ]);
} else {
    echo json_encode(['success'=>false,'message'=>'Không nhận ra khuôn mặt (Độ lệch: '.round($bestDist, 2).'). Vui lòng thử lại!']);
}
$db->close();
?>
