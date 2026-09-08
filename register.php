<?php
require_once __DIR__ . '/config.php';

$error = '';
$success = '';

$googleInfo = $_SESSION['google_reg_info'] ?? null;
$githubInfo = $_SESSION['github_reg_info'] ?? null;

$prefill_username  = $_POST['username'] ?? '';
$prefill_ho_ten    = $_POST['ho_ten'] ?? '';
$prefill_email     = $_POST['email'] ?? '';
$prefill_sdt       = $_POST['sdt'] ?? '';
$prefill_google_id = $_POST['google_id'] ?? ($googleInfo['google_id'] ?? '');
$prefill_github_id = $_POST['github_id'] ?? ($githubInfo['github_id'] ?? '');

if (empty($_POST)) {
    if ($googleInfo) {
        $prefill_email = $googleInfo['email'] ?? '';
        $prefill_ho_ten = $googleInfo['name'] ?? '';
        $parts = explode('@', $prefill_email);
        $prefill_username = !empty($parts[0]) ? $parts[0] . '_' . rand(100, 999) : 'sv' . rand(10000, 99999);
    } elseif ($githubInfo) {
        $prefill_email = $githubInfo['email'] ?? '';
        $prefill_ho_ten = $githubInfo['name'] ?? '';
        if (!empty($prefill_email)) {
            $parts = explode('@', $prefill_email);
            $prefill_username = $parts[0] . '_' . rand(100, 999);
        } else {
            $prefill_username = 'git_' . substr($githubInfo['github_id'], -6);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $ho_ten     = trim($_POST['ho_ten'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $sdt        = trim($_POST['sdt'] ?? '');
    $khoa       = trim($_POST['khoa'] ?? '');
    $gioi_tinh  = trim($_POST['gioi_tinh'] ?? 'Nam');
    $google_id  = trim($_POST['google_id'] ?? '');
    $github_id  = trim($_POST['github_id'] ?? '');

    if (empty($username) || empty($password) || empty($ho_ten) || empty($khoa)) {
        $error = 'Vui lòng điền đầy đủ các thông tin bắt buộc (*)!';
    } else {
        try {
            $db = getDB();

            // 1. Kiểm tra username đã tồn tại trong users chưa
            $exists = false;
            $u_check = $db->real_escape_string($username);
            $chk_res = @$db->query("SELECT id FROM users WHERE username = '$u_check' LIMIT 1");
            if ($chk_res && method_exists($chk_res, 'fetch_assoc') && $chk_res->fetch_assoc()) {
                $exists = true;
            }

            if ($exists) {
                $error = 'Mã sinh viên / Tên đăng nhập này đã tồn tại trên hệ thống!';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $u_username = $db->real_escape_string($username);
                $u_password = $db->real_escape_string($hashed_password);
                $u_hoten    = $db->real_escape_string($ho_ten);
                $g_id_esc   = $db->real_escape_string($google_id);
                $git_id_esc = $db->real_escape_string($github_id);

                // 2. Chèn vào bảng users
                @$db->query("INSERT INTO users (username, password, role, google_id, github_id) VALUES ('$u_username', '$u_password', 'student', '$g_id_esc', '$git_id_esc')");
                $user_id = (int)$db->insert_id;

                if (!$user_id) {
                    $res_u = @$db->query("SELECT id FROM users WHERE username='$u_username' LIMIT 1");
                    if ($res_u && method_exists($res_u, 'fetch_assoc') && ($r_u = $res_u->fetch_assoc())) {
                        $user_id = (int)$r_u['id'];
                    }
                }

                if ($user_id > 0) {
                    // 3. Chèn vào bảng students
                    $ma_sv     = $u_username;
                    $email_esc = $db->real_escape_string($email);
                    $sdt_esc   = $db->real_escape_string($sdt);
                    $khoa_esc  = $db->real_escape_string($khoa);
                    $gt_esc    = $db->real_escape_string($gioi_tinh);
                    
                    $khoa_lop_map = [
                        'Công nghệ thông tin'   => 'CNTT24A',
                        'Cơ khí ô tô'          => 'CKOT24A',
                        'Điện - Điện tử'       => 'DDT24A',
                        'Quản trị doanh nghiệp' => 'QTDN24A'
                    ];
                    $lop = $khoa_lop_map[$khoa] ?? 'CHUNG24A';
                    $lop_esc = $db->real_escape_string($lop);

                    // Chèn vào students
                    $ins_st = @$db->query("INSERT INTO students (user_id, ma_sv, ho_ten, email, sdt, khoa, lop, gioi_tinh, google_id, github_id) VALUES ($user_id, '$ma_sv', '$u_hoten', '$email_esc', '$sdt_esc', '$khoa_esc', '$lop_esc', '$gt_esc', '$g_id_esc', '$git_id_esc')");
                    if (!$ins_st) {
                        @$db->query("INSERT INTO students (user_id, ma_sv, ho_ten, email, sdt, khoa, lop) VALUES ($user_id, '$ma_sv', '$u_hoten', '$email_esc', '$sdt_esc', '$khoa_esc', '$lop_esc')");
                    }

                    $student_id = 0;
                    $res_st = @$db->query("SELECT id FROM students WHERE user_id=$user_id LIMIT 1");
                    if ($res_st && method_exists($res_st, 'fetch_assoc') && ($r_st = $res_st->fetch_assoc())) {
                        $student_id = (int)$r_st['id'];
                    } else {
                        $student_id = $user_id;
                    }

                    // 4. Lưu Session Đăng Nhập Tự Động
                    $_SESSION['user_id']      = $user_id;
                    $_SESSION['username']     = $username;
                    $_SESSION['role']         = 'student';
                    $_SESSION['student_id']   = $student_id;
                    $_SESSION['ho_ten']       = $ho_ten;
                    $_SESSION['ma_sv']        = $ma_sv;
                    $_SESSION['gioi_tinh']     = $gioi_tinh;
                    $_SESSION['banner']        = '';
                    $_SESSION['tiktok_video']  = '';

                    if (function_exists('writeSystemLog')) {
                        writeSystemLog("Đăng ký tài khoản sinh viên mới: $ho_ten ($ma_sv)");
                    }

                    session_write_close();
                    header("Location: /tkb/student/dashboard.php");
                    exit();
                } else {
                    $error = 'Không thể khởi tạo mã người dùng trong cơ sở dữ liệu.';
                }
            }

        } catch (Exception $e) {
            if (isset($db)) @$db->rollback();
            $error = 'Có lỗi xảy ra khi tạo tài khoản: ' . $e->getMessage();
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    $dashboardUrl = getDashboardUrlByRole($_SESSION['role'] ?? '');
    header("Location: $dashboardUrl");
    exit();
}

require_once __DIR__ . '/includes/public_header.php';
?>

<style>
:root {
    --red-main: #d91b43;
    --red-dark: #b81335;
    --red-gradient: linear-gradient(135deg, #d91b43 0%, #ff4d6d 100%);
    --bg-gray: #f8fafc;
}

body {
    background: #f1f5f9;
    font-family: 'Outfit', 'Inter', sans-serif;
    color: #1e293b;
}

.reg-container {
    max-width: 620px;
    margin: 40px auto;
    background: #ffffff;
    border-radius: 24px;
    padding: 36px 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
}

.reg-header {
    text-align: center;
    margin-bottom: 28px;
}

.reg-title {
    font-size: 22px;
    font-weight: 850;
    color: var(--red-main);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.reg-sub {
    font-size: 13px;
    color: #64748b;
    font-weight: 600;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-full {
    grid-column: span 2;
}

.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-size: 12.5px;
    font-weight: 800;
    color: #334155;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.form-label span {
    color: var(--red-main);
}

.input-wrap {
    position: relative;
}

.input-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}

.form-input, .form-select {
    width: 100%;
    padding: 11px 14px 11px 40px;
    background: var(--bg-gray);
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 600;
    color: #0f172a;
    outline: none;
    transition: all 0.2s;
}

.form-select {
    padding-left: 40px;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2#94a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 16px;
}

.form-input:focus, .form-select:focus {
    border-color: var(--red-main);
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(217, 27, 67, 0.1);
}

.gender-box {
    display: flex;
    gap: 12px;
}

.gender-opt {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 700;
    font-size: 13px;
    color: #475569;
    background: var(--bg-gray);
    transition: all 0.2s;
}

.gender-opt input {
    display: none;
}

.gender-opt.active-nam {
    border-color: #2563eb;
    background: #eff6ff;
    color: #1d4ed8;
}

.gender-opt.active-nu {
    border-color: #ec4899;
    background: #fdf2f8;
    color: #db2777;
}

.btn-reg {
    width: 100%;
    padding: 13px;
    background: var(--red-gradient);
    border: none;
    border-radius: 14px;
    color: #ffffff;
    font-size: 15px;
    font-weight: 850;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(217, 27, 67, 0.25);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
}

.btn-reg:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 25px rgba(217, 27, 67, 0.35);
}

.alert-box {
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    color: #dc2626;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
</style>

<div class="reg-container">
    <div class="reg-header">
        <div style="font-size: 36px; margin-bottom: 8px;">🎓</div>
        <div class="reg-title">Tạo Tài Khoản Sinh Viên mới</div>
        <div class="reg-sub">Trường Cao Đẳng Cà Mau</div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-box">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($googleInfo || $githubInfo): ?>
        <div class="alert-box" style="background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8;">
            <i class="fa-solid fa-circle-check"></i>
            <span>Thông tin từ <?= $googleInfo ? 'Google' : 'GitHub' ?> đã được điền tự động. Vui lòng hoàn tất các mục còn lại để tạo tài khoản.</span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/tkb/register.php">
        <input type="hidden" name="google_id" value="<?= htmlspecialchars($prefill_google_id) ?>">
        <input type="hidden" name="github_id" value="<?= htmlspecialchars($prefill_github_id) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">MÃ SINH VIÊN / TÊN ĐĂNG NHẬP <span>*</span></label>
                <div class="input-wrap">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" name="username" class="form-input" placeholder="Ví dụ: sv2024001" required value="<?= htmlspecialchars($prefill_username) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">MẬT KHẨU <span>*</span></label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" class="form-input" placeholder="Nhập mật khẩu..." required>
                </div>
            </div>

            <div class="form-group form-full">
                <label class="form-label">HỌ VÀ TÊN SINH VIÊN <span>*</span></label>
                <div class="input-wrap">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="ho_ten" class="form-input" placeholder="Ví dụ: Nguyễn Văn An" required value="<?= htmlspecialchars($prefill_ho_ten) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">KHOA ĐÀO TẠO <span>*</span></label>
                <div class="input-wrap">
                    <i class="fa-solid fa-building-columns"></i>
                    <select name="khoa" class="form-select" required>
                        <option value="">-- Chọn khoa --</option>
                        <option value="Công nghệ thông tin">Công nghệ thông tin</option>
                        <option value="Cơ khí ô tô">Cơ khí ô tô</option>
                        <option value="Điện - Điện tử">Điện - Điện tử</option>
                        <option value="Quản trị doanh nghiệp">Quản trị doanh nghiệp</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">GIỚI TÍNH (GIAO DIỆN) <span>*</span></label>
                <div class="gender-box">
                    <label class="gender-opt active-nam" id="optNam" onclick="setGender('Nam')">
                        <input type="radio" name="gioi_tinh" value="Nam" checked>
                        <i class="fa-solid fa-mars"></i> Nam (Dark Mode)
                    </label>
                    <label class="gender-opt" id="optNu" onclick="setGender('Nữ')">
                        <input type="radio" name="gioi_tinh" value="Nữ">
                        <i class="fa-solid fa-venus"></i> Nữ (Pastel Soft)
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">EMAIL GIÊN HỆ</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" class="form-input" placeholder="sv@viethan.edu.vn" value="<?= htmlspecialchars($prefill_email) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">SỐ ĐIỆN THOẠI</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-phone"></i>
                    <input type="tel" name="sdt" class="form-input" placeholder="0912345678" value="<?= htmlspecialchars($prefill_sdt) ?>">
                </div>
            </div>
        </div>

        <button type="submit" class="btn-reg">
            <i class="fa-solid fa-user-plus"></i> HOÀN TẤT ĐĂNG KÝ
        </button>
        
        <div style="display: flex; align-items: center; margin: 20px 0;">
            <div style="flex: 1; height: 1px; background: #e2e8f0;"></div>
            <div style="padding: 0 15px; color: #94a3b8; font-size: 13px; font-weight: 600;">HOẶC ĐĂNG KÝ VỚI MẠNG XÃ HỘI</div>
            <div style="flex: 1; height: 1px; background: #e2e8f0;"></div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <a href="/tkb/google_auth.php" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; color: #475569; font-size: 13.5px; font-weight: 700; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.borderColor='#ea4335'; this.style.color='#ea4335'; this.style.background='#fef2f2';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569'; this.style.background='#ffffff';">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" style="width: 18px; height: 18px;">
                Google
            </a>
            <a href="/tkb/github_auth.php" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; color: #475569; font-size: 13.5px; font-weight: 700; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.borderColor='#24292e'; this.style.color='#24292e'; this.style.background='#f6f8fa';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569'; this.style.background='#ffffff';">
                <i class="fa-brands fa-github" style="font-size: 18px; color: #24292e;"></i>
                GitHub
            </a>
        </div>
    </form>

    <div style="text-align: center; margin-top: 20px; font-size: 13px; color: #64748b;">
        Đã có tài khoản sinh viên? <a href="/tkb/login.php" style="color: var(--red-main); font-weight: 800; text-decoration: none;">Đăng nhập ngay</a>
    </div>
</div>

<?php 
if ($googleInfo) unset($_SESSION['google_reg_info']);
if ($githubInfo) unset($_SESSION['github_reg_info']);
?>

<script>
function setGender(g) {
    var optNam = document.getElementById('optNam');
    var optNu = document.getElementById('optNu');
    if (g === 'Nam') {
        optNam.className = 'gender-opt active-nam';
        optNu.className = 'gender-opt';
        optNam.querySelector('input').checked = true;
    } else {
        optNam.className = 'gender-opt';
        optNu.className = 'gender-opt active-nu';
        optNu.querySelector('input').checked = true;
    }
}
</script>
