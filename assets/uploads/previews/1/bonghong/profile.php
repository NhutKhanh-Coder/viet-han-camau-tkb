<?php
require_once 'config.php';

// Force user login
if (!isLoggedIn()) {
    $_SESSION['toast'] = [
        'message' => 'Vui lòng đăng nhập để xem thông tin tài khoản.',
        'type' => 'error'
    ];
    redirect('auth.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'Khác';
    $birthday = $_POST['birthday'] ?? null;
    $address = trim($_POST['address'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($fullname) || empty($phone)) {
        $error = 'Họ tên và Số điện thoại là bắt buộc.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Số điện thoại phải gồm đúng 10 chữ số.';
    } else {
        try {
            $update_pw = false;
            if (!empty($new_password)) {
                // Password strength validation
                $has_min_len = strlen($new_password) >= 8;
                $has_lower   = preg_match('/[a-z]/', $new_password);
                $has_upper   = preg_match('/[A-Z]/', $new_password);
                $has_number  = preg_match('/[0-9]/', $new_password);
                $has_special = preg_match('/[^A-Za-z0-9]/', $new_password);
                
                if (!$has_min_len || !$has_lower || !$has_upper || !$has_number || !$has_special) {
                    $error = 'Mật khẩu mới phải chứa ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, chữ số và ký tự đặc biệt.';
                } else {
                    $update_pw = true;
                    $hashed_pw = password_hash($new_password, PASSWORD_DEFAULT);
                }
            }
            
            if (empty($error)) {
                if ($update_pw) {
                    $stmt = $pdo->prepare("UPDATE Users SET fullname = ?, phone = ?, gender = ?, birthday = ?, address = ?, password = ? WHERE id = ?");
                    $stmt->execute([$fullname, $phone, $gender, empty($birthday) ? null : $birthday, $address, $hashed_pw, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE Users SET fullname = ?, phone = ?, gender = ?, birthday = ?, address = ? WHERE id = ?");
                    $stmt->execute([$fullname, $phone, $gender, empty($birthday) ? null : $birthday, $address, $user_id]);
                }
                
                // Update session
                $_SESSION['user_fullname'] = $fullname;
                
                $_SESSION['toast'] = [
                    'message' => 'Cập nhật thông tin tài khoản thành công.',
                    'type' => 'success'
                ];
                redirect('profile.php');
            }
        } catch (PDOException $e) {
            $error = 'Cập nhật thất bại: ' . $e->getMessage();
        }
    }
}

// Fetch current user details
try {
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = null;
}

if (!$user) {
    die("Lỗi hệ thống: Không thể tìm thấy tài khoản người dùng.");
}

include 'header.php';
?>

<div class="container" style="max-width: 800px;">
    <h1 class="section-title">Thông Tin Cá Nhân</h1>
    
    <div style="background: var(--bg-white); border-radius: var(--radius-lg); padding: 40px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
        <?php if (!empty($error)): ?>
            <div style="background-color: var(--primary-light); color: var(--danger); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 24px; font-weight: 500; font-size: 0.95rem;">
                <?= sanitize($error) ?>
            </div>
        <?php endif; ?>
        
        <form id="profile-form" action="profile.php" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="username">Tên đăng nhập (Không thể thay đổi)</label>
                    <input type="text" id="username" class="input-control" disabled value="<?= sanitize($user['username']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email (Không thể thay đổi)</label>
                    <input type="email" id="email" class="input-control" disabled value="<?= sanitize($user['email']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="fullname">Họ và tên <span style="color: var(--danger)">*</span></label>
                    <input type="text" id="fullname" name="fullname" class="input-control" required value="<?= sanitize($user['fullname']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Số điện thoại <span style="color: var(--danger)">*</span></label>
                    <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" class="input-control" required value="<?= sanitize($user['phone']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="gender">Giới tính</label>
                    <select id="gender" name="gender" class="select-control">
                        <option value="Nam" <?= $user['gender'] === 'Nam' ? 'selected' : '' ?>>Nam</option>
                        <option value="Nữ" <?= $user['gender'] === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                        <option value="Khác" <?= $user['gender'] === 'Khác' ? 'selected' : '' ?>>Khác</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="birthday">Ngày sinh</label>
                    <input type="date" id="birthday" name="birthday" class="input-control" value="<?= sanitize($user['birthday'] ?? '') ?>">
                </div>
                
                <div class="form-group full-width">
                    <label for="address">Địa chỉ</label>
                    <input type="text" id="address" name="address" class="input-control" value="<?= sanitize($user['address']) ?>">
                </div>
                
                <div class="form-group full-width" style="border-top: 1px solid var(--border-color); padding-top: 24px; margin-top: 12px;">
                    <label for="register-password">Mật khẩu mới (Để trống nếu giữ nguyên)</label>
                    <input type="password" id="register-password" name="new_password" class="input-control" placeholder="Tối thiểu 8 ký tự, gồm chữ hoa, thường, số, ký tự đặc biệt">
                    
                    <!-- Password strength visualizer -->
                    <div class="password-strength-meter">
                        <div class="strength-bar-container">
                            <div id="strength-bar" class="strength-bar"></div>
                        </div>
                        <span id="strength-text" class="strength-text"></span>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 32px; display: flex; gap: 16px; justify-content: flex-end;">
                <a href="index.php" class="btn btn-secondary">Quay lại trang chủ</a>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
// Attach the same password validation trigger to registration password field in profile
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profile-form');
    const newPasswordInput = document.getElementById('register-password');
    
    if (profileForm && newPasswordInput) {
        profileForm.addEventListener('submit', function(e) {
            const password = newPasswordInput.value;
            if (password.length > 0) {
                const hasMinLength = password.length >= 8;
                const hasLowercase = /[a-z]/.test(password);
                const hasUppercase = /[A-Z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[^A-Za-z0-9]/.test(password);
                
                if (!hasMinLength || !hasLowercase || !hasUppercase || !hasNumber || !hasSpecial) {
                    e.preventDefault();
                    if (window.showToast) {
                        window.showToast('Mật khẩu mới chưa đủ mạnh! Phải chứa ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.', 'error');
                    } else {
                        alert('Mật khẩu mới chưa đủ mạnh! Phải chứa ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.');
                    }
                }
            }
        });
    }
});
</script>

<?php
include 'footer.php';
?>
