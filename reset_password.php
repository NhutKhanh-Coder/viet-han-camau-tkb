<?php
require_once 'config.php';

$token = trim($_GET['token'] ?? '');
$msg = '';
$success = false;
$validToken = false;
$user = null;

if (!$token) {
    $msg = 'error:Yêu cầu không hợp lệ! Không tìm thấy mã khôi phục.';
} else {
    $db = getDB();
    $now = date('Y-m-d H:i:s');
    
    // Validate token
    $st = $db->prepare("SELECT id, username, email FROM users WHERE reset_token = ? AND reset_token_expires > ?");
    $st->bind_param("ss", $token, $now);
    $st->execute();
    $res = $st->get_result();
    
    if ($res->num_rows === 0) {
        $msg = 'error:Liên kết khôi phục mật khẩu không hợp lệ hoặc đã hết hạn!';
    } else {
        $validToken = true;
        $user = $res->fetch_assoc();
    }
    
    // Process form submission
    if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (strlen($password) < 6) {
            $msg = 'error:Mật khẩu mới phải có ít nhất 6 ký tự!';
        } elseif ($password !== $confirm_password) {
            $msg = 'error:Mật khẩu nhập lại không khớp!';
        } else {
            // Update password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $uid = $user['id'];
            
            $st_up = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $st_up->bind_param("si", $hashed, $uid);
            
            if ($st_up->execute()) {
                $msg = 'success:Đặt lại mật khẩu thành công! Bạn có thể đăng nhập bằng mật khẩu mới.';
                $success = true;
                $validToken = false; // Do not show form anymore
            } else {
                $msg = 'error:Đã xảy ra lỗi hệ thống khi cập nhật mật khẩu!';
            }
        }
    }
    
    $db->close();
}

$msgType = $msgText = '';
if ($msg) [$msgType, $msgText] = explode(':', $msg, 2);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lại Mật Khẩu</title>
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
        .toggle-pass {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--ink-soft);
            cursor: pointer; padding: 4px;
            transition: color 0.2s;
        }
        .toggle-pass:hover { color: var(--ink); }
    </style>
</head>
<body>
    <div class="reset-box">
        <div class="logo-wrap">
            <i class="fa-solid fa-lock-open"></i>
        </div>
        <h2 class="box-title">Đặt Lại Mật Khẩu</h2>
        
        <?php if ($validToken && $user): ?>
            <p class="box-sub">Tài khoản: <strong><?= htmlspecialchars($user['username']) ?></strong> (<?= htmlspecialchars($user['email']) ?>)<br>Nhập mật khẩu mới cho tài khoản của bạn bên dưới.</p>
        <?php else: ?>
            <p class="box-sub">Thiết lập lại thông tin đăng nhập hệ thống.</p>
        <?php endif; ?>
        
        <?php if ($msgText): ?>
            <div class="alert alert-<?= $msgType ?>">
                <?= $msgText ?>
            </div>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <form method="POST" onsubmit="return validatePasswords()">
                <div class="form-group">
                    <label class="form-label">Mật khẩu mới</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-key"></i>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Tối thiểu 6 ký tự" required>
                        <button type="button" class="toggle-pass" onclick="togglePass('password', 'eyeIcon1')">
                            <i class="fa-regular fa-eye" id="eyeIcon1"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nhập lại mật khẩu mới</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-key"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Xác nhận mật khẩu mới" required>
                        <button type="button" class="toggle-pass" onclick="togglePass('confirm_password', 'eyeIcon2')">
                            <i class="fa-regular fa-eye" id="eyeIcon2"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-check"></i> XÁC NHẬN ĐỔI MẬT KHẨU
                </button>
            </form>
        <?php endif; ?>

        <?php if ($success): ?>
            <a href="/tkb/login.php" class="btn-submit" style="text-decoration:none; margin-top:20px;">
                <i class="fa-solid fa-right-to-bracket"></i> ĐĂNG NHẬP NGAY
            </a>
        <?php else: ?>
            <a href="/tkb/login.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Quay lại trang đăng nhập</a>
        <?php endif; ?>
    </div>

    <script>
    function togglePass(inputId, eyeId) {
        var p = document.getElementById(inputId);
        var ic = document.getElementById(eyeId);
        if (p.type === 'password') {
            p.type = 'text';
            ic.className = 'fa-regular fa-eye-slash';
        } else {
            p.type = 'password';
            ic.className = 'fa-regular fa-eye';
        }
    }

    function validatePasswords() {
        var p = document.getElementById('password').value;
        var cp = document.getElementById('confirm_password').value;
        if (p.length < 6) {
            alert('Mật khẩu phải chứa ít nhất 6 ký tự!');
            return false;
        }
        if (p !== cp) {
            alert('Mật khẩu nhập lại không khớp!');
            return false;
        }
        return true;
    }
    </script>
</body>
</html>
