<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $khoa = trim($_POST['khoa'] ?? '');

    if (empty($username) || empty($password) || empty($ho_ten) || empty($khoa)) {
        echo "<script>alert('Vui lòng điền đầy đủ thông tin bắt buộc!'); window.history.back();</script>";
        exit;
    }

    try {
        $db = getDB();

        // Kiểm tra username đã tồn tại chưa
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_assoc()) {
            echo "<script>alert('Tên đăng nhập đã tồn tại! Vui lòng chọn tên khác.'); window.history.back();</script>";
            exit;
        }

        $db->begin_transaction();

        // 1. Tạo user mới
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student';
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);
        $stmt->execute();
        $user_id = $db->insert_id;

        // 2. Tạo student mới
        // ma_sv mặc định là username
        $ma_sv = $username;
        
        $khoa_lop_map = [
            'Công nghệ thông tin' => 'CNTT24A',
            'Cơ khí ô tô' => 'CKOT24A',
            'Điện - Điện tử' => 'DDT24A',
            'Quản trị doanh nghiệp' => 'QTDN24A'
        ];
        $lop = $khoa_lop_map[$khoa] ?? 'CHUNG24A';

        $stmt = $db->prepare("INSERT INTO students (user_id, ma_sv, ho_ten, email, sdt, khoa, lop) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $user_id, $ma_sv, $ho_ten, $email, $sdt, $khoa, $lop);
        $stmt->execute();
        $student_id = $db->insert_id;

        $db->commit();
        $db->close();

        // 3. Tự động đăng nhập
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'student';
        $_SESSION['student_id'] = $student_id;

        // Redirect đến dashboard
        header("Location: /tkb/student/dashboard.php");
        exit;

    } catch (Exception $e) {
        if (isset($db)) $db->rollback();
        echo "<script>alert('Có lỗi xảy ra: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
} else {
    header("Location: /tkb/index.php");
    exit;
}
?>
