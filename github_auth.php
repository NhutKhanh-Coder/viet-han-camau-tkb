<?php
require_once __DIR__ . '/config.php';

// Cấu hình URL OAuth của GitHub
$authUrl = 'https://github.com/login/oauth/authorize';
$tokenUrl = 'https://github.com/login/oauth/access_token';
$userInfoUrl = 'https://api.github.com/user';
$emailsUrl = 'https://api.github.com/user/emails';

// Kiểm tra xem đã cài đặt Client ID chưa (Nếu là giá trị mẫu thì chạy chế độ Sandbox / Demo)
$isDemo = (empty(GITHUB_CLIENT_ID) || GITHUB_CLIENT_ID === 'YOUR_GITHUB_CLIENT_ID_HERE');

if ($isDemo) {
    // Chế độ mô phỏng GitHub Login cho môi trường thử nghiệm
    $email = 'github.student@viethan.edu.vn';
    $name = 'Sinh Viên GitHub Test';
    $picture = 'https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.png';
    $github_id = 'github_demo_1029384756';
} else {
    // 1. Chuyển hướng người dùng đến GitHub để đăng nhập
    if (!isset($_GET['code'])) {
        $params = [
            'client_id' => GITHUB_CLIENT_ID,
            'redirect_uri' => GITHUB_REDIRECT_URI,
            'scope' => 'user:email',
            'state' => bin2hex(random_bytes(16))
        ];
        
        $redirectUrl = $authUrl . '?' . http_build_query($params);
        header('Location: ' . filter_var($redirectUrl, FILTER_SANITIZE_URL));
        exit();
    }

    // 2. Nhận Authorization Code từ GitHub
    $code = $_GET['code'];

    if (empty($code)) {
        die('Lỗi: Không nhận được mã xác thực từ GitHub.');
    }

    // 3. Đổi Authorization Code lấy Access Token từ GitHub
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => GITHUB_CLIENT_ID,
        'client_secret' => GITHUB_CLIENT_SECRET,
        'code' => $code,
        'redirect_uri' => GITHUB_REDIRECT_URI
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'User-Agent: VKC-TKB-App'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('Lỗi cURL GitHub Token: ' . curl_error($ch));
    }
    curl_close($ch);

    $tokenData = json_decode($response, true);

    if (isset($tokenData['error'])) {
        if (isset($_GET['code'])) {
            header("Location: /tkb/github_auth.php");
            exit();
        }
        die('Lỗi lấy GitHub Access Token: ' . htmlspecialchars($tokenData['error_description'] ?? $tokenData['error']));
    }

    $accessToken = $tokenData['access_token'] ?? null;
    if (!$accessToken) {
        die('Lỗi: Không lấy được Access Token từ GitHub.');
    }

    // 4. Lấy thông tin tài khoản người dùng từ GitHub API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'User-Agent: VKC-TKB-App',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $userInfo = json_decode($response, true);

    if (!isset($userInfo['id'])) {
        die('Lỗi: Không thể lấy thông tin người dùng từ GitHub.');
    }

    $github_id = (string)$userInfo['id'];
    $name = $userInfo['name'] ?? $userInfo['login'] ?? '';
    $email = $userInfo['email'] ?? '';
    $picture = $userInfo['avatar_url'] ?? '';

    // Nếu email riêng tư, lấy qua GitHub API /user/emails
    if (empty($email)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $emailsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'User-Agent: VKC-TKB-App',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resEmails = curl_exec($ch);
        curl_close($ch);

        $emails = json_decode($resEmails, true);
        if (is_array($emails)) {
            foreach ($emails as $em) {
                if (!empty($em['primary']) && !empty($em['email'])) {
                    $email = $em['email'];
                    break;
                }
            }
            if (empty($email) && isset($emails[0]['email'])) {
                $email = $emails[0]['email'];
            }
        }
    }
}

// 5. Xử lý Đăng Nhập / Đăng Ký
try {
    $db = getDB();
    $git_id_esc = $db->real_escape_string($github_id);
    $email_esc = !empty($email) ? $db->real_escape_string($email) : '';

    // Kiểm tra theo github_id hoặc email trong bảng students
    $query = "SELECT user_id, id as student_id, ma_sv, ho_ten, gioi_tinh, banner, tiktok_video, banner_pos, banner_fit FROM students WHERE github_id = '$git_id_esc'";
    if (!empty($email_esc)) {
        $query .= " OR email = '$email_esc'";
    }
    $query .= " LIMIT 1";

    $res = $db->query($query);

    if ($res && $res->num_rows > 0) {
        // TÀI KHOẢN ĐÃ TỒN TẠI -> TIẾN HÀNH ĐĂNG NHẬP
        $sv = $res->fetch_assoc();
        $user_id = $sv['user_id'];

        // Cập nhật github_id nếu chưa có
        if (!empty($git_id_esc)) {
            @$db->query("UPDATE students SET github_id = '$git_id_esc' WHERE id = " . $sv['student_id']);
            @$db->query("UPDATE users SET github_id = '$git_id_esc' WHERE id = $user_id");
        }

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
                writeSystemLog("Đăng nhập hệ thống qua GitHub (Vai trò: " . $user['role'] . ")");
            }

            header("Location: /tkb/student/dashboard.php");
            exit();
        } else {
            die('Lỗi: Dữ liệu tài khoản không đồng bộ.');
        }
    } else {
        // TÀI KHOẢN CHƯA TỒN TẠI -> CHUYỂN HƯỚNG SANG TRANG ĐĂNG KÝ
        $_SESSION['github_reg_info'] = [
            'github_id' => $github_id,
            'name' => $name,
            'email' => $email,
            'picture' => $picture
        ];

        header("Location: /tkb/register.php?github=1");
        exit();
    }
} catch (Exception $e) {
    die("Lỗi cơ sở dữ liệu: " . $e->getMessage());
}
