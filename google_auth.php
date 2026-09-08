<?php
require_once __DIR__ . '/config.php';

// Cấu hình URL OAuth của Google
$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
$tokenUrl = 'https://oauth2.googleapis.com/token';
$userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';

// Kiểm tra xem đã cài đặt Client ID chưa (Nếu là gía trị mẫu thì chạy chế độ Sandbox / Demo)
$isDemo = (empty(GOOGLE_CLIENT_ID) || GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID_HERE');

if ($isDemo) {
    // Chế độ mô phỏng Google Login cho môi trường thử nghiệm
    $email = 'google.student@viethan.edu.vn';
    $name = 'Sinh Viên Google Test';
    $picture = 'https://www.svgrepo.com/show/475656/google-color.svg';
    $google_id = 'google_demo_1029384756';
} else {
    // 1. Chuyển hướng người dùng đến Google để đăng nhập
    if (!isset($_GET['code'])) {
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'online',
            'prompt' => 'select_account'
        ];
        
        $redirectUrl = $authUrl . '?' . http_build_query($params);
        header('Location: ' . filter_var($redirectUrl, FILTER_SANITIZE_URL));
        exit();
    }

    // 2. Nhận Authorization Code từ Google
    $code = $_GET['code'];

    if (empty($code)) {
        die('Lỗi: Không nhận được mã xác thực từ Google.');
    }

    // 3. Đổi Mã xác thực (Authorization Code) lấy Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
        'code' => $code
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('Lỗi cURL: ' . curl_error($ch));
    }
    curl_close($ch);

    $tokenData = json_decode($response, true);

    if (isset($tokenData['error'])) {
        // Mã Google code chỉ dùng được 1 lần duy nhất. Nếu F5 hoặc dùng lại mã cũ -> tự quay lại đăng nhập lại
        if (isset($_GET['code'])) {
            header("Location: /tkb/google_auth.php");
            exit();
        }
        die('Lỗi lấy Access Token: ' . htmlspecialchars($tokenData['error_description'] ?? $tokenData['error']));
    }

    $accessToken = $tokenData['access_token'];

    // 4. Lấy thông tin người dùng từ Google
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $userInfo = json_decode($response, true);

    if (!isset($userInfo['email'])) {
        die('Lỗi: Không thể lấy thông tin email từ Google.');
    }

    $email = $userInfo['email'];
    $name = $userInfo['name'] ?? '';
    $picture = $userInfo['picture'] ?? '';
    $google_id = $userInfo['sub'] ?? '';
}

// 5. Xử lý đăng nhập / đăng ký
try {
    $db = getDB();
    $email_esc = $db->real_escape_string($email);
    $g_id_esc = $db->real_escape_string($google_id);
    
    // Kiểm tra xem email hoặc google_id đã tồn tại trong bảng students chưa
    $query = "SELECT user_id, id as student_id, ma_sv, ho_ten, gioi_tinh, banner, tiktok_video, banner_pos, banner_fit FROM students WHERE google_id = '$g_id_esc'";
    if (!empty($email_esc)) {
        $query .= " OR email = '$email_esc'";
    }
    $query .= " LIMIT 1";

    $res = $db->query($query);
    
    if ($res && $res->num_rows > 0) {
        // NGƯỜI DÙNG ĐÃ TỒN TẠI -> TIẾN HÀNH ĐĂNG NHẬP
        $sv = $res->fetch_assoc();
        $user_id = $sv['user_id'];

        // Cập nhật google_id nếu chưa có
        if (!empty($g_id_esc)) {
            @$db->query("UPDATE students SET google_id = '$g_id_esc' WHERE id = " . $sv['student_id']);
            @$db->query("UPDATE users SET google_id = '$g_id_esc' WHERE id = $user_id");
        }
        
        // Lấy thông tin user (để lấy username và role)
        $u_res = $db->query("SELECT username, role FROM users WHERE id = $user_id LIMIT 1");
        if ($u_res && $u_res->num_rows > 0) {
            $user = $u_res->fetch_assoc();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['student_id'] = $sv['student_id'];
            $_SESSION['ho_ten'] = $sv['ho_ten'];
            $_SESSION['ma_sv'] = $sv['ma_sv'];
            $_SESSION['gioi_tinh'] = $sv['gioi_tinh'] ?? 'Nam';
            
            // Xử lý banner và video như login.php
            $banner_clean = trim($sv['banner'] ?? '');
            $banner_lower = strtolower($banner_clean);
            if (!empty($banner_clean) && $banner_lower !== 'banner.jpg' && $banner_lower !== 'default.jpg' && $banner_lower !== 'default.png') {
                $_SESSION['banner'] = $banner_clean;
            } else {
                $_SESSION['banner'] = '';
            }
            
            $video_clean = trim($sv['tiktok_video'] ?? '');
            $video_lower = strtolower($video_clean);
            if (!empty($video_clean) && $video_lower !== 'video.mp4' && $video_lower !== 'default.mp4') {
                $_SESSION['tiktok_video'] = $video_clean;
            } else {
                $_SESSION['tiktok_video'] = '';
            }

            $_SESSION['banner_pos'] = $sv['banner_pos'] ?? 'center center';
            $_SESSION['banner_fit'] = $sv['banner_fit'] ?? 'cover';
            
            if (function_exists('writeSystemLog')) { 
                writeSystemLog("Đăng nhập hệ thống qua Google (Vai trò: " . $user['role'] . ")"); 
            }
            
            header("Location: /tkb/student/dashboard.php");
            exit();
        } else {
            die('Lỗi: Dữ liệu tài khoản không đồng bộ.');
        }
    } else {
        // NGƯỜI DÙNG CHƯA TỒN TẠI -> CHUYỂN HƯỚNG SANG TRANG ĐĂNG KÝ
        $_SESSION['google_reg_info'] = [
            'email' => $email,
            'name' => $name,
            'picture' => $picture,
            'google_id' => $google_id
        ];
        
        header("Location: /tkb/register.php?google=1");
        exit();
    }
} catch (Exception $e) {
    die("Lỗi cơ sở dữ liệu: " . $e->getMessage());
}
