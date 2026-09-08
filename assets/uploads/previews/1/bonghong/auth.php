<?php
require_once 'config.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    redirect('index.php');
}

$register_error = '';
$login_error = '';

// Handle Post Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Handle Registration
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gender = $_POST['gender'] ?? 'Khác';
        $birthday = $_POST['birthday'] ?? null;
        $address = trim($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Simple Server-Side Validations
        if (empty($fullname) || empty($username) || empty($email) || empty($phone) || empty($password)) {
            $register_error = 'Vui lòng điền đầy đủ các thông tin bắt buộc.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $register_error = 'Email không hợp lệ.';
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $register_error = 'Số điện thoại phải gồm đúng 10 chữ số.';
        } else {
            // Password strength check
            // - At least 8 characters
            // - At least 1 lowercase letter
            // - At least 1 uppercase letter
            // - At least 1 number
            // - At least 1 special character
            $has_min_len = strlen($password) >= 8;
            $has_lower   = preg_match('/[a-z]/', $password);
            $has_upper   = preg_match('/[A-Z]/', $password);
            $has_number  = preg_match('/[0-9]/', $password);
            $has_special = preg_match('/[^A-Za-z0-9]/', $password);
            
            if (!$has_min_len || !$has_lower || !$has_upper || !$has_number || !$has_special) {
                $register_error = 'Mật khẩu phải chứa ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, chữ số và ký tự đặc biệt.';
            } else {
                try {
                    // Check if username already exists
                    $stmt = $pdo->prepare("SELECT id FROM Users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $register_error = 'Tên đăng nhập đã tồn tại.';
                    } else {
                        // Check if email already exists
                        $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $register_error = 'Email đã được đăng ký sử dụng.';
                        } else {
                            // Insert new user
                            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("INSERT INTO Users (fullname, username, email, phone, gender, birthday, address, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'customer', 1)");
                            $stmt->execute([
                                $fullname,
                                $username,
                                $email,
                                $phone,
                                $gender,
                                empty($birthday) ? null : $birthday,
                                $address,
                                $hashed_pw
                            ]);
                            
                            // Auto-login after registration
                            $user_id = $pdo->lastInsertId();
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['user_username'] = $username;
                            $_SESSION['user_fullname'] = $fullname;
                            $_SESSION['user_role'] = 'customer';
                            
                            $_SESSION['toast'] = [
                                'message' => 'Đăng ký thành viên thành công! Chào mừng ' . $fullname,
                                'type' => 'success'
                            ];
                            redirect('index.php');
                        }
                    }
                } catch (PDOException $e) {
                    $register_error = 'Có lỗi xảy ra trong quá trình đăng ký: ' . $e->getMessage();
                }
            }
        }
    }
    
    // 2. Handle Login
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $login_identity = trim($_POST['login_identity'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($login_identity) || empty($password)) {
            $login_error = 'Vui lòng nhập tên đăng nhập/email và mật khẩu.';
        } else {
            try {
                // Fetch user by username OR email
                $stmt = $pdo->prepare("SELECT * FROM Users WHERE (username = ? OR email = ?) LIMIT 1");
                $stmt->execute([$login_identity, $login_identity]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] == 0) {
                        $login_error = 'Tài khoản của bạn đã bị khóa.';
                    } else {
                        // Login successful
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_username'] = $user['username'];
                        $_SESSION['user_fullname'] = $user['fullname'];
                        $_SESSION['user_role'] = $user['role'];
                        
                        $_SESSION['toast'] = [
                            'message' => 'Đăng nhập thành công! Chào mừng quay trở lại ' . $user['fullname'],
                            'type' => 'success'
                        ];
                        redirect('index.php');
                    }
                } else {
                    $login_error = 'Tên đăng nhập/Email hoặc Mật khẩu không chính xác.';
                }
            } catch (PDOException $e) {
                $login_error = 'Có lỗi hệ thống xảy ra: ' . $e->getMessage();
            }
        }
    }
}

include 'header.php';
?>

<div class="auth-container">
    <!-- REGISTRATION PANEL (Left Column) -->
    <div class="auth-panel" id="register">
        <h2 class="auth-title">Đăng ký thành viên</h2>
        
        <?php if (!empty($register_error)): ?>
            <div style="background-color: var(--primary-light); color: var(--danger); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem; font-weight: 500;">
                <?= sanitize($register_error) ?>
            </div>
        <?php endif; ?>
        
        <form id="register-form" action="auth.php#register" method="POST">
            <input type="hidden" name="action" value="register">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="fullname">Họ và tên <span style="color: var(--danger)">*</span></label>
                    <input type="text" id="fullname" name="fullname" class="input-control" required placeholder="Họ và tên của bạn">
                </div>
                
                <div class="form-group">
                    <label for="username">Tên đăng nhập <span style="color: var(--danger)">*</span></label>
                    <input type="text" id="username" name="username" class="input-control" required placeholder="Tên đăng nhập">
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span style="color: var(--danger)">*</span></label>
                    <input type="email" id="email" name="email" class="input-control" required placeholder="example@mail.com">
                </div>
                
                <div class="form-group">
                    <label for="phone">Số điện thoại <span style="color: var(--danger)">*</span></label>
                    <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" title="Số điện thoại phải gồm đúng 10 chữ số." class="input-control" required placeholder="09xxxxxxxx">
                </div>
                
                <div class="form-group">
                    <label for="gender">Giới tính</label>
                    <select id="gender" name="gender" class="select-control">
                        <option value="Nam">Nam</option>
                        <option value="Nữ">Nữ</option>
                        <option value="Khác" selected>Khác</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="birthday">Ngày sinh</label>
                    <input type="date" id="birthday" name="birthday" class="input-control">
                </div>
                
                <div class="form-group full-width">
                    <label for="address">Địa chỉ</label>
                    <input type="text" id="address" name="address" class="input-control" placeholder="Số nhà, đường, phường/xã...">
                </div>
                
                <div class="form-group full-width">
                    <label for="register-password">Mật khẩu <span style="color: var(--danger)">*</span></label>
                    <input type="password" id="register-password" name="password" class="input-control" required placeholder="Tối thiểu 8 ký tự">
                    <!-- Password strength visualizer -->
                    <div class="password-strength-meter">
                        <div class="strength-bar-container">
                            <div id="strength-bar" class="strength-bar"></div>
                        </div>
                        <span id="strength-text" class="strength-text"></span>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 16px;">Đăng ký</button>
        </form>
    </div>
    
    <!-- LOGIN PANEL (Right Column) -->
    <div class="auth-panel" id="login">
        <h2 class="auth-title">Đăng nhập</h2>
        
        <?php if (!empty($login_error)): ?>
            <div style="background-color: var(--primary-light); color: var(--danger); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem; font-weight: 500;">
                <?= sanitize($login_error) ?>
            </div>
        <?php endif; ?>
        
        <form action="auth.php#login" method="POST">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label for="login_identity">Tên đăng nhập hoặc Email <span style="color: var(--danger)">*</span></label>
                <input type="text" id="login_identity" name="login_identity" class="input-control" required placeholder="phamduycuong@camauvkc.edu.vn">
            </div>
            
            <div class="form-group">
                <label for="login_password">Mật khẩu <span style="color: var(--danger)">*</span></label>
                <input type="password" id="login_password" name="password" class="input-control" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 16px;">Đăng nhập</button>
        </form>
        
        <div style="margin-top: 32px; text-align: center; font-size: 0.9rem; color: var(--text-muted);">
            <p>Tài khoản thử nghiệm khách hàng:</p>
            <p style="font-weight: 600; color: var(--primary);">phamduycuong@camauvkc.edu.vn / Cường@12345</p>
            <p style="margin-top: 8px;">Tài khoản thử nghiệm Admin:</p>
            <p style="font-weight: 600; color: var(--primary);">admin / Admin@12345</p>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>
