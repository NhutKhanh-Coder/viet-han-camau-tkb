<?php
require_once 'config.php';
$msg = '';
$simulatedLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (!$username || !$email) {
        $msg = 'error:Vui lòng điền đầy đủ thông tin!';
    } else {
        $db = getDB();
        $st = $db->prepare("SELECT id, username, email FROM users WHERE username = ? AND email = ?");
        $st->bind_param("ss", $username, $email);
        $st->execute();
        $res = $st->get_result();
        
        if ($res->num_rows === 0) {
            $msg = 'error:Tên đăng nhập hoặc email không khớp với hệ thống!';
        } else {
            $user = $res->fetch_assoc();
            $uid = $user['id'];
            
            // Generate token
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token
            $st_tok = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $st_tok->bind_param("ssi", $token, $expires, $uid);
            if ($st_tok->execute()) {
                $msg = 'success:Yêu cầu khôi phục mật khẩu đã được xử lý thành công!';
                $simulatedLink = "http://localhost/tkb/reset_password.php?token=" . $token;
            } else {
                $msg = 'error:Lỗi hệ thống khi tạo yêu cầu khôi phục!';
            }
        }
        $db->close();
    }
}

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <style>
        :root {
            --red-main:   #d91b43;
            --red-dark:   #a8122e;
            --red-soft:   rgba(217, 27, 67, 0.08);
            --red-border: rgba(217, 27, 67, 0.2);
            --ink:        #1a1a2e;
            --ink-mid:    #475569;
            --ink-soft:   #94a3b8;
            --bg-white:   #ffffff;
            --bg-gray:    #f8fafc;
        }
        body {
            background: var(--bg-gray);
            font-family: 'Outfit', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .reset-box {
            background: #fff;
            border: 1px solid rgba(217, 27, 67, 0.12);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            text-align: center;
        }
        .logo-wrap {
            margin-bottom: 24px;
        }
        .logo-wrap i {
            font-size: 48px;
            color: var(--red-main);
        }
        .box-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--ink);
            margin: 0 0 10px;
        }
        .box-sub {
            font-size: 13.5px;
            color: var(--ink-soft);
            margin: 0 0 30px;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-mid);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 16px; top: 50%;
            transform: translateY(-50%);
            color: var(--ink-soft);
            font-size: 14px;
        }
        .form-input {
            width: 100%;
            padding: 13px 16px 13px 46px;
            background: var(--bg-gray);
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: all 0.25s;
            box-sizing: border-box;
            color: var(--ink);
            font-family: inherit;
        }
        .form-input:focus {
            border-color: var(--red-main);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(217, 27, 67, 0.1);
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--red-main);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 14.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 25px rgba(217, 27, 67, 0.25);
            box-sizing: border-box;
        }
        .btn-submit:hover {
            background: var(--red-dark);
            transform: translateY(-1px);
        }
        .back-link {
            display: inline-block;
            margin-top: 24px;
            color: var(--ink-soft);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: var(--red-main);
        }
        .alert {
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 24px;
            font-weight: 500;
            text-align: left;
            line-height: 1.4;
        }
        .alert-error {
            background: rgba(220, 38, 38, 0.06);
            border: 1px solid rgba(220, 38, 38, 0.15);
            color: #b91c1c;
        }
        .alert-success {
            background: rgba(22, 163, 74, 0.06);
            border: 1px solid rgba(22, 163, 74, 0.15);
            color: #15803d;
        }
        .simulated-box {
            background: #fffbeb;
            border: 1px dashed #f59e0b;
            padding: 15px;
            border-radius: 12px;
            margin-top: 20px;
            text-align: left;
        }
        .simulated-box h4 {
            margin: 0 0 6px;
            font-size: 13px;
            color: #b45309;
            font-weight: 700;
        }
        .simulated-box p {
            margin: 0 0 10px;
            font-size: 12px;
            color: #d97706;
            line-height: 1.4;
        }
        .simulated-box a {
            word-break: break-all;
            font-size: 12.5px;
            color: var(--red-main);
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="reset-box">
        <div class="logo-wrap">
            <i class="fa-solid fa-key"></i>
        </div>
        <h2 class="box-title">Quên Mật Khẩu?</h2>
        <p class="box-sub">Nhập tên đăng nhập của bạn và địa chỉ email đã liên kết để nhận liên kết khôi phục mật khẩu.</p>
        
        <?php if ($msgText): ?>
            <div class="alert alert-<?= $msgType ?>">
                <?= htmlspecialchars($msgText) ?>
            </div>
        <?php endif; ?>

        <?php if ($simulatedLink): ?>
            <div class="simulated-box">
                <h4><i class="fa-solid fa-circle-info"></i> Mô phỏng gửi email (Localhost)</h4>
                <p>Do đang chạy thử nghiệm trên máy chủ local, link khôi phục mật khẩu sẽ được hiển thị trực tiếp tại đây:</p>
                <a href="<?= $simulatedLink ?>"><?= $simulatedLink ?></a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Tên đăng nhập</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" name="username" class="form-input" placeholder="Mã sinh viên, giáo viên hoặc admin" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email đã đăng ký</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" name="email" class="form-input" placeholder="Ví dụ: name@vkc.edu.vn" required>
                    </div>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> GỬI YÊU CẦU KHÔI PHỤC
                </button>
            </form>
        <?php endif; ?>

        <a href="/tkb/login.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Quay lại trang đăng nhập</a>
    </div>
</body>
</html>
