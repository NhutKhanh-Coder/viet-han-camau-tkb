<?php
require_once '../config.php';
requireAdmin();
$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');
    $system_email = trim($_POST['system_email'] ?? '');
    $default_theme = trim($_POST['default_theme'] ?? 'light');
    $default_lang = trim($_POST['default_lang'] ?? 'vi');
    $timezone = trim($_POST['timezone'] ?? 'Asia/Ho_Chi_Minh');
    $site_logo = trim($_POST['site_logo'] ?? '');
    $site_banner = trim($_POST['site_banner'] ?? '');
    
    $settings = [
        'site_name' => $site_name,
        'system_email' => $system_email,
        'default_theme' => $default_theme,
        'default_lang' => $default_lang,
        'timezone' => $timezone,
        'site_logo' => $site_logo,
        'site_banner' => $site_banner
    ];
    
    $stmt = $db->prepare("INSERT INTO system_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
    foreach ($settings as $k => $v) {
        $stmt->bind_param("sss", $k, $v, $v);
        $stmt->execute();
    }
    // Lưu cấu hình Anti-DDoS Firewall
    $ddos_enabled = isset($_POST['ddos_enabled']);
    $under_attack_mode = isset($_POST['under_attack_mode']);
    $max_requests = (int)($_POST['max_requests'] ?? 60);

    $cache_dir = __DIR__ . '/../temp_runs/rate_limit';
    if (!is_dir($cache_dir)) @mkdir($cache_dir, 0777, true);
    @file_put_contents($cache_dir . '/ddos_settings.json', json_encode([
        'enabled' => $ddos_enabled,
        'under_attack_mode' => $under_attack_mode,
        'max_requests' => $max_requests,
        'time_frame' => 10
    ]));

    writeSystemLog("Cập nhật cài đặt hệ thống & Tường lửa Anti-DDoS");
    $msg = "success:Lưu cấu hình hệ thống & Tường lửa Anti-DDoS thành công!";
}

// Fetch settings
$settings = [];
$res = $db->query("SELECT * FROM system_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['key']] = $row['value'];
}
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu Hình Hệ Thống - Hệ Thống Quản Trị</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body class="admin-portal <?= (isset($_COOKIE['adm_theme']) && $_COOKIE['adm_theme'] === 'light') ? 'adm-light-mode' : '' ?>">
    <?php include '../includes/admin_nav.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fa-solid fa-gears"></i> Cấu Hình Hệ Thống</h1>
                <p class="page-sub">Tùy chỉnh thông tin website, logo thương hiệu, banner trang chủ, cấu hình email và ngôn ngữ mặc định</p>
            </div>
        </div>

        <!-- Feedback messages -->
        <?php if ($msg): 
            $parts = explode(':', $msg);
            $type = $parts[0];
            $text = $parts[1];
        ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>">
                <i class="fa-solid <?= $type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <?= htmlspecialchars($text) ?>
            </div>
        <?php endif; ?>

        <!-- Shortcut to AI Models Management -->
        <div style="max-width: 900px; margin-bottom: 20px;">
            <a href="/tkb/admin/quanly_ai_models.php" style="display:flex; align-items:center; justify-content:space-between; padding:18px 24px; background:linear-gradient(135deg, rgba(168,85,247,0.18), rgba(56,189,248,0.12)); border:1px solid rgba(168,85,247,0.35); border-radius:16px; text-decoration:none; color:#f3e8ff; transition:all 0.25s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.borderColor='#c084fc';" onmouseout="this.style.transform='none'; this.style.borderColor='rgba(168,85,247,0.35)';">
                <div style="display:flex; align-items:center; gap:16px;">
                    <div style="width:48px; height:48px; border-radius:14px; background:rgba(168,85,247,0.25); display:flex; align-items:center; justify-content:center; font-size:22px; color:#c084fc;">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div>
                        <div style="font-size:15.5px; font-weight:800; color:#ffffff; margin-bottom:3px;">
                            Quản Lý Đóng / Mở Models AI Bot Chat (Sinh Viên)
                        </div>
                        <div style="font-size:12px; color:#c4b5fd;">
                            Bật / Tắt DeepSeek V4, Qwen, Mistral và các mô hình AI để điều phối sử dụng
                        </div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; font-weight:700; font-size:13px; color:#38bdf8;">
                    <span>Truy cập</span> <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>
        </div>

        <div style="max-width: 900px;">
            <form method="POST" class="card">
                <div class="card-head">
                    <span class="card-title"><i class="fa-solid fa-sliders"></i> Cài đặt chung website</span>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">Tên Website / Hệ thống *</label>
                            <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? 'Hệ thống Quản lý LMS VKC') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Hệ Thống *</label>
                            <input type="email" name="system_email" class="form-control" value="<?= htmlspecialchars($settings['system_email'] ?? 'admin@vkc.edu.vn') ?>" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">URL Ảnh Logo</label>
                            <input type="text" name="site_logo" class="form-control" placeholder="Dán link ảnh logo..." value="<?= htmlspecialchars($settings['site_logo'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">URL Banner</label>
                            <input type="text" name="site_banner" class="form-control" placeholder="Dán link ảnh banner trang chủ..." value="<?= htmlspecialchars($settings['site_banner'] ?? '') ?>">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Chế độ giao diện</label>
                            <select name="default_theme" class="form-select">
                                <option value="light" <?= ($settings['default_theme'] ?? '') === 'light' ? 'selected' : '' ?>>Chế độ sáng (Light Mode)</option>
                                <option value="dark" <?= ($settings['default_theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Chế độ tối (Dark Mode)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ngôn ngữ mặc định</label>
                            <select name="default_lang" class="form-select">
                                <option value="vi" <?= ($settings['default_lang'] ?? '') === 'vi' ? 'selected' : '' ?>>Tiếng Việt (vi)</option>
                                <option value="en" <?= ($settings['default_lang'] ?? '') === 'en' ? 'selected' : '' ?>>English (en)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Múi giờ hệ thống</label>
                            <select name="timezone" class="form-select">
                                <option value="Asia/Ho_Chi_Minh" <?= ($settings['timezone'] ?? '') === 'Asia/Ho_Chi_Minh' ? 'selected' : '' ?>>Asia/Ho_Chi_Minh (UTC+7)</option>
                                <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC Time</option>
                            </select>
                        </div>
                    </div>

                    <?php
                    $cache_dir = __DIR__ . '/../temp_runs/rate_limit';
                    $settings_file = $cache_dir . '/ddos_settings.json';
                    $ddos_conf = ['enabled' => true, 'under_attack_mode' => false, 'max_requests' => 60];
                    if (file_exists($settings_file)) {
                        $c = @json_decode(file_get_contents($settings_file), true);
                        if (is_array($c)) $ddos_conf = array_merge($ddos_conf, $c);
                    }
                    ?>
                    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 24px 0 20px;">
                    <div style="margin-bottom: 16px;">
                        <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-shield-halved" style="color:#e11d48;"></i> Cấu hình Tường Lửa Anti-DDoS & Cloudflare Challenge
                        </h3>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; background: #f8fafc; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Tường lửa Rate Limit</label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 8px;">
                                <input type="checkbox" name="ddos_enabled" value="1" <?= $ddos_conf['enabled'] ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #e11d48;">
                                <span style="font-size: 13px; font-weight: 600; color:#0f172a;">Kích hoạt Tường Lửa</span>
                            </label>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Chế độ Under Attack</label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 8px;">
                                <input type="checkbox" name="under_attack_mode" value="1" <?= $ddos_conf['under_attack_mode'] ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #7000ff;">
                                <span style="font-size: 13px; font-weight: 600; color: #7000ff;">Kích hoạt Challenge 5s</span>
                            </label>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Tối đa Request / 10s</label>
                            <input type="number" name="max_requests" class="form-control" value="<?= (int)$ddos_conf['max_requests'] ?>" min="10" max="500">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu cấu hình</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
