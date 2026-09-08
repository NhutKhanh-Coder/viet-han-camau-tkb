<?php
require_once __DIR__ . '/config.php';

// Cấu hình URL OAuth của Facebook Graph API (v18.0)
$authUrl = 'https://www.facebook.com/v18.0/dialog/oauth';
$tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token';
$userInfoUrl = 'https://graph.facebook.com/v18.0/me';

// Kiểm tra xem đã cài đặt App ID chưa (Nếu là giá trị mẫu thì chạy chế độ Sandbox / Demo)
$isDemo = (empty(FACEBOOK_APP_ID) || FACEBOOK_APP_ID === 'YOUR_FACEBOOK_APP_ID_HERE');

if ($isDemo) {
    // Chế độ mô phỏng Facebook Login cho môi trường thử nghiệm
    $facebook_id = 'facebook_demo_9876543210';
    $name = 'Sinh Viên Facebook Test';
    $email = 'facebook.student@viethan.edu.vn';
    $picture = '';
} else {
    // 1. Chuyển hướng người dùng đến Facebook để xác thực
    if (!isset($_GET['code'])) {
        $params = [
            'client_id' => FACEBOOK_APP_ID,
            'redirect_uri' => FACEBOOK_REDIRECT_URI,
            'scope' => 'email,public_profile',
            'response_type' => 'code'
        ];
        
        $redirectUrl = $authUrl . '?' . http_build_query($params);
        header('Location: ' . filter_var($redirectUrl, FILTER_SANITIZE_URL));
        exit();
    }

    // 2. Nhận Authorization Code từ Facebook
    $code = $_GET['code'];

    if (empty($code)) {
        die('Lỗi: Không nhận được mã xác thực từ Facebook.');
    }

    // 3. Đổi Authorization Code lấy Access Token từ Facebook Graph API
    $tokenParams = [
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'redirect_uri' => FACEBOOK_REDIRECT_URI,
        'code' => $code
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl . '?' . http_build_query($tokenParams));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('Lỗi cURL Facebook Token: ' . curl_error($ch));
    }
    curl_close($ch);

    $tokenData = json_decode($response, true);

    if (isset($tokenData['error'])) {
        $errMessage = $tokenData['error']['message'] ?? 'Lỗi không xác định khi lấy Facebook Access Token';
        die('Lỗi Facebook OAuth: ' . htmlspecialchars($errMessage));
    }

    $accessToken = $tokenData['access_token'] ?? null;
    if (!$accessToken) {
        die('Lỗi: Không lấy được Access Token từ Facebook.');
    }

    // 4. Lấy thông tin tài khoản người dùng từ Facebook Graph API
    $userParams = [
        'fields' => 'id,name,email,picture.type(large)',
        'access_token' => $accessToken
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userInfoUrl . '?' . http_build_query($userParams));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('Lỗi cURL Facebook UserInfo: ' . curl_error($ch));
    }
    curl_close($ch);

    $userInfo = json_decode($response, true);

    if (isset($userInfo['error'])) {
        die('Lỗi lấy thông tin Facebook: ' . htmlspecialchars($userInfo['error']['message'] ?? 'Thất bại'));
    }

    $facebook_id = $userInfo['id'] ?? '';
    $name = $userInfo['name'] ?? '';
    $email = $userInfo['email'] ?? '';
    $picture = $userInfo['picture']['data']['url'] ?? '';

    if (empty($facebook_id)) {
        die('Lỗi: Không thể lấy ID người dùng từ Facebook.');
    }
}

// 5. Xử lý Đăng Nhập / Đăng Ký
try {
    $db = getDB();
    $fb_id_esc = $db->real_escape_string($facebook_id);
    $email_esc = !empty($email) ? $db->real_escape_string($email) : '';

    // Kiểm tra theo facebook_id hoặc email trong bảng students
    $query = "SELECT user_id, id as student_id, ma_sv, ho_ten, gioi_tinh, banner, tiktok_video, banner_pos, banner_fit FROM students WHERE facebook_id = '$fb_id_esc'";
    if (!empty($email_esc)) {
        $query .= " OR email = '$email_esc'";
    }
    $query .= " LIMIT 1";

    $res = $db->query($query);

    if ($res && $res->num_rows > 0) {
        // TÀI KHOẢN ĐÃ TỒN TẠI -> TIẾN HÀNH ĐĂNG NHẬP
        $sv = $res->fetch_assoc();
        $user_id = $sv['user_id'];

        // Cập nhật facebook_id nếu chưa có
        @$db->query("UPDATE students SET facebook_id = '$fb_id_esc' WHERE id = " . $sv['student_id']);
        @$db->query("UPDATE users SET facebook_id = '$fb_id_esc' WHERE id = $user_id");

        // Lấy thông tin user (username & role)
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

            // Xử lý banner và video
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
                writeSystemLog("Đăng nhập hệ thống qua Facebook (Vai trò: " . $user['role'] . ")");
            }

            header("Location: /tkb/student/dashboard.php");
            exit();
        } else {
            die('Lỗi: Dữ liệu tài khoản không đồng bộ.');
        }
    } else {
        // TÀI KHOẢN CHƯA TỒN TẠI -> CHUYỂN HƯỚNG SANG TRANG ĐĂNG KÝ
        $_SESSION['facebook_reg_info'] = [
            'facebook_id' => $facebook_id,
            'name' => $name,
            'email' => $email,
            'picture' => $picture
        ];

        header("Location: /tkb/register.php?facebook=1");
        exit();
    }
} catch (Exception $e) {
    die("Lỗi cơ sở dữ liệu: " . $e->getMessage());
}
