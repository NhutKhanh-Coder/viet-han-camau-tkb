<?php
/**
 * ====================================================================
 * ANTI-DDOS & CLOUDFLARE-STYLE FIREWALL SHIELD v2.0
 * CHẾ ĐỘ BẢO VỆ TƯỜNG LỬA CHỐNG DDOS & BROWSER CHALLENGE
 * THIẾT KẾ BỞI: LE NHUT KHANH (DEVELOPER KERIA)
 * ====================================================================
 */

// 1. THIẾT LẬP SECURITY HEADERS
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// 2. CHẶN BAD BOTS & SCANNER TOOLS
$user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$bad_bots = ['sqlmap', 'nikto', 'w3af', 'acunetix', 'havij', 'nmap', 'dirbuster', 'gobuster', 'python-requests/2', 'zgrab'];
foreach ($bad_bots as $bot) {
    if ($bot !== '' && strpos($user_agent, $bot) !== false) {
        http_response_code(403);
        die('<h1 style="color:red;text-align:center;margin-top:20%;">403 Forbidden: Access Denied by Anti-DDoS Security Shield</h1>');
    }
}

// 3. THU THẬP THÔNG TIN IP & CẤU HÌNH FIREWALL
$ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$ip = filter_var(explode(',', $ip)[0], FILTER_VALIDATE_IP) ?: '127.0.0.1';

$cache_dir = __DIR__ . '/../temp_runs/rate_limit';
if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0777, true);
}

// Đọc cài đặt Firewall
$settings_file = $cache_dir . '/ddos_settings.json';
$firewall_settings = [
    'enabled' => true,
    'under_attack_mode' => false,
    'max_requests' => 60,
    'time_frame' => 10
];

if (file_exists($settings_file)) {
    $conf = @json_decode(file_get_contents($settings_file), true);
    if (is_array($conf)) {
        $firewall_settings = array_merge($firewall_settings, $conf);
    }
}

// Nếu Tường lửa bị tắt -> Bỏ qua
if (!$firewall_settings['enabled']) {
    return;
}

// Đảm bảo session đã sẵn sàng để lưu trạng thái xác thực cf_verified
if (session_status() === PHP_SESSION_NONE) {
    session_name('TKB_SESSID');
    if (PHP_VERSION_ID >= 70300) {
        @session_set_cookie_params([
            'path' => '/tkb',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        @session_set_cookie_params(0, '/tkb', '', false, true);
    }
    @session_start();
}

$now = time();

// 4. CHẾ ĐỘ CLOUDFLARE "UNDER ATTACK" (CHALLENGE 5 GIÂY CHO MỖI LẦN LOAD TRANG)
$req_path = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
$is_api_or_ajax = (strpos($req_path, '/api/') !== false) || 
                  (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($firewall_settings['under_attack_mode']) {
    $is_verified = false;
    if (isset($_SESSION['cf_verified']) && $_SESSION['cf_verified'] > ($now - 7200)) {
        $is_verified = true;
    } elseif (isset($_GET['cf_verify_token']) && $_GET['cf_verify_token'] === md5($ip . 'vhcm_salt_2026')) {
        $_SESSION['cf_verified'] = time();
        $is_verified = true;
        if (!$is_api_or_ajax) {
            echo '<script>if(window.history && window.history.replaceState){ window.history.replaceState({}, document.title, ' . json_encode($req_path) . '); }</script>';
        }
    }

    if (!$is_verified) {
        if ($is_api_or_ajax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Tường lửa Anti-DDoS đang bật. Vui lòng tải lại trang chính để xác thực.']);
            exit;
        } else {
            renderCloudflareChallengePage($ip);
            exit;
        }
    }
}

// 5. XỬ LÝ RATE LIMIT THEO IP
$is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');
$limit = $is_post ? max(10, (int)($firewall_settings['max_requests'] / 3)) : (int)$firewall_settings['max_requests'];
$time_frame = (int)$firewall_settings['time_frame'];

$ip_filename = $cache_dir . '/ip_' . md5($ip) . '.json';
$data = ['count' => 0, 'start_time' => $now, 'blocked_until' => 0];

if (file_exists($ip_filename)) {
    $raw = @file_get_contents($ip_filename);
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) {
        $data = array_merge($data, $parsed);
    }
}

// Nếu IP đang bị khóa
if (isset($data['blocked_until']) && $now < $data['blocked_until']) {
    $remaining = $data['blocked_until'] - $now;
    renderDDoSShieldPage($ip, $remaining);
    exit;
}

// Cập nhật số đếm
if ($now - $data['start_time'] < $time_frame) {
    $data['count']++;
} else {
    $data['count'] = 1;
    $data['start_time'] = $now;
}

// Vượt quá giới hạn -> Khóa 15 giây
if ($data['count'] > $limit) {
    $data['blocked_until'] = $now + 15;
    @file_put_contents($ip_filename, json_encode($data));
    renderDDoSShieldPage($ip, 15);
    exit;
}

@file_put_contents($ip_filename, json_encode($data));

/**
 * Hiển thị màn hình Cloudflare Challenge "Under Attack Mode"
 */
function renderCloudflareChallengePage($user_ip) {
    $verify_token = md5($user_ip . 'vhcm_salt_2026');
    $path = strtok($_SERVER['REQUEST_URI'], '?');
    $target_url = $path . '?cf_verify_token=' . $verify_token;
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🛡️ Cloudflare Security Check | Trường Cao Đẳng Cà Mau</title>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: 'Outfit', sans-serif;
                background: #0b0f19;
                color: #f8fafc;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .cf-card {
                background: rgba(15, 23, 42, 0.85);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 24px;
                padding: 40px;
                max-width: 480px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 50px rgba(0,0,0,0.6);
            }
            .spinner-ring {
                width: 70px;
                height: 70px;
                border: 4px solid rgba(255, 255, 255, 0.1);
                border-top: 4px solid #ff0055;
                border-right: 4px solid #00ccff;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 24px;
            }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            .cf-title { font-size: 20px; font-weight: 800; margin-bottom: 8px; }
            .cf-sub { font-size: 13px; color: #94a3b8; margin-bottom: 24px; line-height: 1.5; }
            .progress-bar-wrap {
                background: rgba(255,255,255,0.08);
                border-radius: 10px;
                height: 8px;
                overflow: hidden;
                margin-bottom: 20px;
            }
            .progress-bar-fill {
                height: 100%;
                width: 0%;
                background: linear-gradient(90deg, #ff0055, #ffcc00, #00ff66);
                transition: width 0.1s linear;
            }
            .top-led-text {
                background: linear-gradient(90deg, #ff0055, #ff5000, #ffcc00, #00ff66, #00ccff, #7000ff, #ff00cc, #ff0055);
                background-size: 300% 100%;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                font-weight: 800;
            }
        </style>
    </head>
    <body>
        <div class="cf-card">
            <div class="spinner-ring"></div>
            <h1 class="cf-title">ĐANG KIỂM TRA BẢO MẬT TRÌNH DUYỆT</h1>
            <p class="cf-sub">Hệ thống đang xác minh kết nối an toàn trước khi cho phép truy cập Website. Quá trình tự động mất khoảng 3 - 5 giây...</p>
            
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" id="pfill"></div>
            </div>

            <div style="font-size: 11px; color: #64748b; margin-top: 15px;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 5px; margin-bottom: 4px;">
                    <img src="https://flagcdn.com/w40/vn.png" style="height: 12px; border-radius: 2px;">
                    <span class="top-led-text">TRƯỜNG CAO ĐẲNG CÀ MAU</span>
                </div>
                <div>SMARTEDU AI · CLOUDFLARE SHIELD V2.0</div>
            </div>
        </div>

        <script>
            let progress = 0;
            const pfill = document.getElementById('pfill');
            const interval = setInterval(() => {
                progress += 2.5;
                pfill.style.width = progress + '%';
                if (progress >= 100) {
                    clearInterval(interval);
                    window.location.href = <?= json_encode($target_url) ?>;
                }
            }, 100);
        </script>
    </body>
    </html>
    <?php
}

/**
 * Hiển thị màn hình lá chắn Anti-DDoS khi bị khóa tạm thời
 */
function renderDDoSShieldPage($user_ip, $retry_after_seconds) {
    http_response_code(429);
    header("Retry-After: $retry_after_seconds");
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🛡️ Anti-DDoS Protection Firewall | Trường Cao Đẳng Cà Mau</title>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: 'Outfit', sans-serif;
                background: #0f172a;
                color: #f8fafc;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                position: relative;
                overflow: hidden;
            }
            .glow-bg {
                position: absolute;
                width: 400px;
                height: 400px;
                background: radial-gradient(circle, rgba(217, 27, 67, 0.35) 0%, rgba(112, 0, 255, 0.2) 50%, transparent 70%);
                border-radius: 50%;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                filter: blur(50px);
                z-index: 0;
                animation: pulseGlow 4s ease-in-out infinite alternate;
            }
            @keyframes pulseGlow {
                0% { transform: translate(-50%, -50%) scale(0.9); opacity: 0.7; }
                100% { transform: translate(-50%, -50%) scale(1.15); opacity: 1; }
            }
            .shield-card {
                position: relative;
                z-index: 10;
                background: rgba(30, 41, 59, 0.75);
                backdrop-filter: blur(25px);
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 28px;
                padding: 45px 35px;
                max-width: 520px;
                width: 100%;
                text-align: center;
                box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5), 0 0 30px rgba(217, 27, 67, 0.2);
            }
            .shield-icon-wrap {
                position: relative;
                width: 90px;
                height: 90px;
                margin: 0 auto 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(217, 27, 67, 0.12);
                border-radius: 50%;
                border: 2px solid rgba(217, 27, 67, 0.4);
                box-shadow: 0 0 25px rgba(217, 27, 67, 0.3);
            }
            .shield-icon-wrap i {
                font-size: 42px;
                color: #f43f6d;
                animation: shieldPulse 1.8s infinite;
            }
            @keyframes shieldPulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); color: #ff0055; }
            }
            .title {
                font-size: 22px;
                font-weight: 850;
                color: #ffffff;
                margin-bottom: 8px;
                letter-spacing: 0.5px;
            }
            .subtitle {
                font-size: 13px;
                color: #94a3b8;
                margin-bottom: 24px;
                line-height: 1.5;
            }
            .timer-box {
                background: rgba(15, 23, 42, 0.7);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 18px;
                padding: 18px;
                margin-bottom: 24px;
            }
            .timer-num {
                font-size: 46px;
                font-weight: 900;
                background: linear-gradient(90deg, #ff0055, #ffcc00, #00ff66, #00ccff);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .timer-label {
                font-size: 12px;
                color: #cbd5e1;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin-top: 4px;
                font-weight: 600;
            }
            .ip-badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: rgba(255, 255, 255, 0.06);
                padding: 8px 18px;
                border-radius: 50px;
                font-size: 12px;
                color: #e2e8f0;
                border: 1px solid rgba(255, 255, 255, 0.1);
                margin-bottom: 20px;
            }
            .ip-dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px #22c55e; }
            .brand-footer {
                font-size: 11px;
                color: #64748b;
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                padding-top: 18px;
                margin-top: 10px;
            }
            .top-led-text {
                background: linear-gradient(90deg, #ff0055, #ff5000, #ffcc00, #00ff66, #00ccff, #7000ff, #ff00cc, #ff0055);
                background-size: 300% 100%;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                font-weight: 800;
            }
        </style>
    </head>
    <body>
        <div class="glow-bg"></div>
        <div class="shield-card">
            <div class="shield-icon-wrap">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h1 class="title">BẢO VỆ AN NINH ANTI-DDOS</h1>
            <p class="subtitle">Phát hiện lượt truy cập liên tục quá nhanh từ địa chỉ IP của bạn. Hệ thống tự động kích hoạt lá chắn bảo vệ an toàn cho Server.</p>
            
            <div class="timer-box">
                <div class="timer-num" id="countdown"><?= (int)$retry_after_seconds ?></div>
                <div class="timer-label">Tự động mở khóa sau (giây)</div>
            </div>

            <div class="ip-badge">
                <span class="ip-dot"></span>
                <span>Địa chỉ IP của bạn: <strong><?= htmlspecialchars($user_ip) ?></strong></span>
            </div>

            <div class="brand-footer">
                <div style="display: flex; align-items: center; justify-content: center; gap: 5px; margin-bottom: 4px;">
                    <img src="https://flagcdn.com/w40/vn.png" style="height: 12px; border-radius: 2px;">
                    <span class="top-led-text">TRƯỜNG CAO ĐẲNG CÀ MAU</span>
                </div>
                <div>SMARTEDU AI · FIREWALL V2.0</div>
            </div>
        </div>

        <script>
            let seconds = <?= (int)$retry_after_seconds ?>;
            const el = document.getElementById('countdown');
            const timer = setInterval(() => {
                seconds--;
                if (seconds <= 0) {
                    clearInterval(timer);
                    el.innerText = '0';
                    window.location.reload();
                } else {
                    el.innerText = seconds;
                }
            }, 1000);
        </script>
    </body>
    </html>
    <?php
}
