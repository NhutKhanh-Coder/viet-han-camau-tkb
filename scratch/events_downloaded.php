<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
requireStudent();

$db = getDB();

$student_id = (int)($_SESSION['student_id'] ?? ($_SESSION['user_id'] ?? 0));
$student_name = $_SESSION['ho_ten'] ?? ($_SESSION['user_name'] ?? 'Sinh viên');
$student_code = $_SESSION['username'] ?? ($_SESSION['ma_sv'] ?? 'SV');

$msg = '';
$awarded_gift = null;

// Lấy thông tin sinh viên đầy đủ
$stInfo = $db->query("SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = $student_id LIMIT 1")->fetch_assoc();
if ($stInfo) {
    if (!empty($stInfo['ho_ten'])) $student_name = $stInfo['ho_ten'];
    if (!empty($stInfo['ma_sv'])) $student_code = $stInfo['ma_sv'];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. XỬ LÝ NHẬN QUÀ TRỰC TIẾP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'claim_direct') {
    $ev_id = (int)($_POST['event_id'] ?? 0);
    
    $ev = $db->query("SELECT * FROM mmo_events WHERE id = $ev_id AND status = 'active' LIMIT 1")->fetch_assoc();
    if (!$ev) {
        $msg = "error:Sự kiện không tồn tại hoặc đã tạm dừng!";
    } elseif ($ev['claimed_count'] >= $ev['max_claims']) {
        $msg = "error:Rất tiếc! Số lượng phần quà của sự kiện này đã được phát hết.";
    } elseif (!empty($ev['end_time']) && strtotime($ev['end_time']) < time()) {
        $msg = "error:Sự kiện này đã kết thúc thời gian nhận quà.";
    } else {
        // Kiểm tra sinh viên đã nhận chưa
        $chk = $db->query("SELECT id FROM mmo_event_claims WHERE event_id = $ev_id AND student_id = $student_id LIMIT 1")->fetch_assoc();
        if ($chk) {
            $msg = "error:Bạn đã nhận phần quà từ sự kiện này rồi! Vui lòng kiểm tra tab \"Quà MMO Của Tôi\".";
        } else {
            $db->begin_transaction();
            try {
                $stmt = $db->prepare("INSERT INTO mmo_event_claims (event_id, student_id, student_name, student_code, reward_name, reward_info) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissss", $ev_id, $student_id, $student_name, $student_code, $ev['reward_name'], $ev['reward_data']);
                $stmt->execute();
                $stmt->close();

                $db->query("UPDATE mmo_events SET claimed_count = claimed_count + 1 WHERE id = $ev_id");
                $db->commit();

                writeSystemLog("Sinh viên $student_name ($student_code) nhận quà MMO: {$ev['reward_name']}");
                $awarded_gift = [
                    'event_title' => $ev['title'],
                    'reward_name' => $ev['reward_name'],
                    'reward_info' => $ev['reward_data']
                ];
                $msg = "success:Chúc mừng bạn đã nhận quà thành công từ ThS. Lê Nhựt Khánh!";
            } catch (Exception $e) {
                $db->rollback();
                $msg = "error:Lỗi xử lý nhận quà: " . $e->getMessage();
            }
        }
    }
}

// 2. XỬ LÝ QUAY VÒNG QUAY MAY MẮN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'claim_wheel') {
    $ev_id = (int)($_POST['event_id'] ?? 0);
    
    $ev = $db->query("SELECT * FROM mmo_events WHERE id = $ev_id AND status = 'active' LIMIT 1")->fetch_assoc();
    if (!$ev) {
        $msg = "error:Sự kiện không tồn tại hoặc đã tạm dừng!";
    } elseif ($ev['claimed_count'] >= $ev['max_claims']) {
        $msg = "error:Sự kiện vòng quay đã hết quà tặng.";
    } else {
        $chk = $db->query("SELECT id FROM mmo_event_claims WHERE event_id = $ev_id AND student_id = $student_id LIMIT 1")->fetch_assoc();
        if ($chk) {
            $msg = "error:Bạn đã quay thưởng sự kiện này rồi! Xem lại quà tại tab \"Quà MMO Của Tôi\".";
        } else {
            $db->begin_transaction();
            try {
                $stmt = $db->prepare("INSERT INTO mmo_event_claims (event_id, student_id, student_name, student_code, reward_name, reward_info) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissss", $ev_id, $student_id, $student_name, $student_code, $ev['reward_name'], $ev['reward_data']);
                $stmt->execute();
                $stmt->close();

                $db->query("UPDATE mmo_events SET claimed_count = claimed_count + 1 WHERE id = $ev_id");
                $db->commit();

                writeSystemLog("Sinh viên $student_name ($student_code) quay trúng quà MMO: {$ev['reward_name']}");
                $awarded_gift = [
                    'event_title' => $ev['title'],
                    'reward_name' => $ev['reward_name'],
                    'reward_info' => $ev['reward_data']
                ];
                $msg = "success:Chúc mừng bạn đã quay trúng quà tặng từ ThS. Lê Nhựt Khánh!";
            } catch (Exception $e) {
                $db->rollback();
                $msg = "error:Lỗi quay thưởng: " . $e->getMessage();
            }
        }
    }
}

// 3. XỬ LÝ NHẬP GIFTCODE BÍ MẬT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'claim_code') {
    $ev_id = (int)($_POST['event_id'] ?? 0);
    $input_code = strtoupper(trim($_POST['secret_code'] ?? ''));

    $ev = $db->query("SELECT * FROM mmo_events WHERE id = $ev_id AND status = 'active' LIMIT 1")->fetch_assoc();
    if (!$ev) {
        $msg = "error:Sự kiện không tồn tại hoặc đã tạm dừng!";
    } elseif (strtoupper(trim($ev['secret_code'] ?? '')) !== $input_code) {
        $msg = "error:Mã Giftcode không chính xác! Vui lòng kiểm tra lại mã Admin Lê Nhựt Khánh đã phát.";
    } elseif ($ev['claimed_count'] >= $ev['max_claims']) {
        $msg = "error:Mã Giftcode này đã hết lượt kích hoạt quà tặng.";
    } else {
        $chk = $db->query("SELECT id FROM mmo_event_claims WHERE event_id = $ev_id AND student_id = $student_id LIMIT 1")->fetch_assoc();
        if ($chk) {
            $msg = "error:Bạn đã kích hoạt Giftcode này rồi!";
        } else {
            $db->begin_transaction();
            try {
                $stmt = $db->prepare("INSERT INTO mmo_event_claims (event_id, student_id, student_name, student_code, reward_name, reward_info) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissss", $ev_id, $student_id, $student_name, $student_code, $ev['reward_name'], $ev['reward_data']);
                $stmt->execute();
                $stmt->close();

                $db->query("UPDATE mmo_events SET claimed_count = claimed_count + 1 WHERE id = $ev_id");
                $db->commit();

                writeSystemLog("Sinh viên $student_name ($student_code) nhập code nhận quà MMO: {$ev['reward_name']}");
                $awarded_gift = [
                    'event_title' => $ev['title'],
                    'reward_name' => $ev['reward_name'],
                    'reward_info' => $ev['reward_data']
                ];
                $msg = "success:Kích hoạt Giftcode thành công! Bạn đã nhận được quà tặng từ ThS. Lê Nhựt Khánh.";
            } catch (Exception $e) {
                $db->rollback();
                $msg = "error:Lỗi kích hoạt: " . $e->getMessage();
            }
        }
    }
}

// Lấy danh sách sự kiện đang mở
$events = [];
$resEv = $db->query("SELECT * FROM mmo_events WHERE status = 'active' ORDER BY id DESC");
if ($resEv) $events = $resEv->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách ID các sự kiện sinh viên đã nhận quà
$my_claimed_event_ids = [];
$resMyClaimed = $db->query("SELECT event_id FROM mmo_event_claims WHERE student_id = $student_id");
if ($resMyClaimed) {
    while ($r = $resMyClaimed->fetch_assoc()) {
        $my_claimed_event_ids[] = (int)$r['event_id'];
    }
}

// Lấy toàn bộ quà tặng sinh viên đã nhận (Hòm đồ cá nhân)
$my_gifts = [];
$resGifts = $db->query("SELECT c.*, e.title as event_title, e.event_type FROM mmo_event_claims c LEFT JOIN mmo_events e ON c.event_id = e.id WHERE c.student_id = $student_id ORDER BY c.id DESC");
if ($resGifts) $my_gifts = $resGifts->fetch_all(MYSQLI_ASSOC);

$tab = $_GET['tab'] ?? 'active';

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sự Kiện &amp; Quà Tặng MMO - ThS. Lê Nhựt Khánh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">
    <!-- Confetti library for winning celebration -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        .mmo-hero {
            background: linear-gradient(135deg, #1e1035 0%, #2e1065 50%, #3b0764 100%);
            border: 1.5px solid rgba(192, 132, 252, 0.35);
            border-radius: 24px;
            padding: 28px 32px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
        }
        .mmo-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.35), transparent 70%);
            pointer-events: none;
        }
        .mmo-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 14px;
            flex-wrap: wrap;
        }
        .mmo-tab-link {
            padding: 10px 22px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 800;
            color: #cbd5e1;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .mmo-tab-link:hover {
            color: #ffffff;
            background: rgba(168, 85, 247, 0.25);
            border-color: rgba(168, 85, 247, 0.45);
        }
        .mmo-tab-link.active {
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
            color: #ffffff !important;
            border-color: transparent;
            box-shadow: 0 4px 20px rgba(236, 72, 153, 0.35);
        }

        .event-card {
            background: rgba(20, 13, 38, 0.88);
            border: 1px solid rgba(168, 85, 247, 0.25);
            border-radius: 20px;
            overflow: hidden;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
        }
        .event-card:hover {
            transform: translateY(-4px);
            border-color: rgba(236, 72, 153, 0.45);
            box-shadow: 0 16px 40px rgba(168, 85, 247, 0.25);
        }
        .event-card-banner {
            height: 140px;
            background: linear-gradient(135deg, #3b0764, #1e1b4b);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .event-card-body {
            padding: 20px 22px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Lucky Wheel styles */
        .wheel-container {
            position: relative;
            width: 280px;
            height: 280px;
            margin: 0 auto 20px;
        }
        #wheelCanvas {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            box-shadow: 0 0 25px rgba(236, 72, 153, 0.5);
            transition: transform 4s cubic-bezier(0.17, 0.67, 0.12, 0.99);
        }
        .wheel-pointer {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 14px solid transparent;
            border-right: 14px solid transparent;
            border-top: 24px solid #ef4444;
            z-index: 10;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.5));
        }
    </style>
</head>
<body class="student-portal">
    <?php include '../includes/student_nav.php'; ?>

    <div class="main-content" style="max-width:1200px; margin:0 auto; padding:24px 20px;">
        
        <!-- HERO BANNER -->
        <div class="mmo-hero">
            <div style="position:relative; z-index:2;">
                <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(236,72,153,0.25); border:1px solid rgba(236,72,153,0.45); color:#fbcfe8; padding:5px 14px; border-radius:30px; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:12px;">
                    <i class="fa-solid fa-sparkles"></i> Trung Tâm MMO &bull; Quà Tặng Độc Quyền
                </div>
                <h1 style="font-size:28px; font-weight:900; color:#ffffff; margin:0 0 8px; text-shadow:0 2px 10px rgba(0,0,0,0.3);">
                    🎁 Sự Kiện &amp; Trao Quà Từ ThS. Lê Nhựt Khánh
                </h1>
                <p style="font-size:14px; color:#e9d5ff; max-width:680px; line-height:1.6; margin:0 0 16px;">
                    Chào mừng bạn đến với sự kiện quà tặng từ <strong>Trung Tâm MMO</strong>! Hãy tham gia nhận tài khoản bản quyền ChatGPT Plus, Canva Pro, voucher giảm giá và phần quà công nghệ hoàn toàn miễn phí.
                </p>
                <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap; font-size:12.5px; color:#cbd5e1;">
                    <span><i class="fa-solid fa-circle-check" style="color:#34d399;"></i> 100% Tài khoản chính hãng</span>
                    <span><i class="fa-solid fa-bolt" style="color:#facc15;"></i> Bàn giao tự động tức thì</span>
                    <span><i class="fa-solid fa-shield-heart" style="color:#f472b6;"></i> Bảo hành &amp; hỗ trợ từ Admin</span>
                </div>
            </div>
        </div>

        <!-- Alert Notification -->
        <?php if ($msg): 
            $parts = explode(':', $msg, 2);
            $type = $parts[0];
            $text = $parts[1] ?? '';
        ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>" style="padding:14px 18px; border-radius:14px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:10px; <?= $type==='success'?'background:rgba(16,185,129,0.2); border:1px solid rgba(16,185,129,0.4); color:#6ee7b7;':'background:rgba(239,68,68,0.2); border:1px solid rgba(239,68,68,0.4); color:#fca5a5;' ?>">
                <i class="fa-solid <?= $type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <?= htmlspecialchars($text) ?>
            </div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="mmo-tabs">
            <a href="?tab=active" class="mmo-tab-link <?= $tab === 'active' ? 'active' : '' ?>">
                <i class="fa-solid fa-fire"></i> Sự Kiện Đang Mở (<?= count($events) ?>)
            </a>
            <a href="?tab=my_gifts" class="mmo-tab-link <?= $tab === 'my_gifts' ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-packing"></i> Hòm Đồ Quà MMO Của Tôi (<?= count($my_gifts) ?>)
            </a>
            <a href="/tkb/student/shop_ai.php" class="mmo-tab-link" style="margin-left:auto; background:rgba(245,158,11,0.15); border-color:rgba(245,158,11,0.35); color:#fbbf24;">
                <i class="fa-solid fa-store"></i> Chợ MMO & Tài khoản AI &rarr;
            </a>
        </div>

        <!-- TAB 1: SỰ KIỆN ĐANG MỞ -->
        <?php if ($tab === 'active'): ?>
            <?php if (empty($events)): ?>
                <div class="card" style="padding:50px 20px; text-align:center; color:#a79bb7;">
                    <i class="fa-solid fa-gift" style="font-size:48px; color:#a855f7; margin-bottom:16px;"></i>
                    <h3 style="color:#f3e8ff; font-weight:800; margin-bottom:6px;">Hiện chưa có sự kiện nào đang diễn ra</h3>
                    <p style="font-size:13px; max-width:400px; margin:0 auto 16px;">Admin Lê Nhựt Khánh sẽ sớm phát hành các sự kiện tặng quà MMO mới. Hãy quay lại sau nhé!</p>
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:22px;">
                    <?php foreach ($events as $ev): 
                        $is_claimed = in_array((int)$ev['id'], $my_claimed_event_ids, true);
                        $is_out_of_stock = ($ev['claimed_count'] >= $ev['max_claims']);
                        $remaining = max(0, $ev['max_claims'] - $ev['claimed_count']);
                        $pct = ($ev['max_claims'] > 0) ? min(100, round(($ev['claimed_count'] / $ev['max_claims']) * 100)) : 0;
                    ?>
                        <div class="event-card">
                            <div class="event-card-banner">
                                <div style="position:absolute; top:12px; left:12px; z-index:2;">
                                    <?php if ($ev['event_type'] === 'direct'): ?>
                                        <span style="background:rgba(59,130,246,0.85); color:#ffffff; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                                            <i class="fa-solid fa-hand-holding-heart"></i> Nhận Ngay
                                        </span>
                                    <?php elseif ($ev['event_type'] === 'wheel'): ?>
                                        <span style="background:rgba(236,72,153,0.85); color:#ffffff; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                                            <i class="fa-solid fa-dharmachakra"></i> Vòng Quay May Mắn
                                        </span>
                                    <?php elseif ($ev['event_type'] === 'code'): ?>
                                        <span style="background:rgba(245,158,11,0.85); color:#ffffff; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                                            <i class="fa-solid fa-key"></i> Giftcode Bí Mật
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="position:absolute; top:12px; right:12px; z-index:2;">
                                    <?php if ($is_claimed): ?>
                                        <span style="background:rgba(16,185,129,0.9); color:#ffffff; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:800;">
                                            <i class="fa-solid fa-check"></i> Đã nhận
                                        </span>
                                    <?php elseif ($is_out_of_stock): ?>
                                        <span style="background:rgba(239,68,68,0.9); color:#ffffff; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:800;">
                                            Hết quà
                                        </span>
                                    <?php else: ?>
                                        <span style="background:rgba(168,85,247,0.9); color:#ffffff; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:800;">
                                            Còn <?= $remaining ?> suất
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:46px; color:rgba(255,255,255,0.85); filter:drop-shadow(0 4px 10px rgba(0,0,0,0.4));">
                                    <?php if ($ev['event_type'] === 'wheel'): ?>
                                        <i class="fa-solid fa-dharmachakra fa-spin" style="animation-duration:15s;"></i>
                                    <?php elseif ($ev['event_type'] === 'code'): ?>
                                        <i class="fa-solid fa-key"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-gift"></i>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="event-card-body">
                                <h3 style="font-size:16px; font-weight:800; color:#f3e8ff; margin:0 0 8px; line-height:1.4;">
                                    <?= htmlspecialchars($ev['title']) ?>
                                </h3>
                                
                                <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.25); border-radius:10px; padding:10px 12px; margin-bottom:12px;">
                                    <div style="font-size:11px; color:#6ee7b7; font-weight:700; text-transform:uppercase;">Phần quà nhận được:</div>
                                    <div style="font-size:13.5px; font-weight:800; color:#34d399; margin-top:2px;">
                                        <i class="fa-solid fa-award"></i> <?= htmlspecialchars($ev['reward_name']) ?>
                                    </div>
                                </div>

                                <?php if (!empty($ev['description'])): ?>
                                    <p style="font-size:12.5px; color:#a79bb7; line-height:1.5; margin:0 0 16px; flex:1;">
                                        <?= htmlspecialchars($ev['description']) ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Progress bar -->
                                <div style="margin-bottom:16px;">
                                    <div style="display:flex; justify-content:space-between; font-size:11px; color:#cbd5e1; font-weight:700; margin-bottom:4px;">
                                        <span>Tiến độ phát quà</span>
                                        <span>Đã trao: <b style="color:#38bdf8;"><?= $ev['claimed_count'] ?></b>/<?= $ev['max_claims'] ?></span>
                                    </div>
                                    <div style="background:rgba(255,255,255,0.08); border-radius:8px; height:6px; overflow:hidden;">
                                        <div style="height:100%; width:<?= $pct ?>%; background:linear-gradient(90deg, #10b981, #38bdf8); border-radius:8px;"></div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <?php if ($is_claimed): ?>
                                    <a href="?tab=my_gifts" class="btn btn-ghost" style="width:100%; text-align:center; background:rgba(16,185,129,0.15) !important; color:#6ee7b7 !important; border-color:rgba(16,185,129,0.35) !important;">
                                        <i class="fa-solid fa-box-open"></i> Xem Quà Đã Nhận Trong Hòm Đồ
                                    </a>
                                <?php elseif ($is_out_of_stock): ?>
                                    <button type="button" class="btn btn-ghost" disabled style="width:100%; opacity:0.5; cursor:not-allowed;">
                                        <i class="fa-solid fa-ban"></i> Đã Hết Số Lượng Quà
                                    </button>
                                <?php else: ?>
                                    <?php if ($ev['event_type'] === 'direct'): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="action" value="claim_direct">
                                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                            <button type="submit" class="btn btn-primary" style="width:100%; background:linear-gradient(135deg, #10b981, #059669); border:none; box-shadow:0 4px 15px rgba(16,185,129,0.4);">
                                                <i class="fa-solid fa-hand-holding-heart"></i> Nhận Quà Ngay (Miễn Phí)
                                            </button>
                                        </form>
                                    <?php elseif ($ev['event_type'] === 'wheel'): ?>
                                        <button type="button" onclick="openLuckyWheelModal(<?= $ev['id'] ?>, '<?= htmlspecialchars(addslashes($ev['title'])) ?>', '<?= htmlspecialchars(addslashes($ev['reward_name'])) ?>')" class="btn btn-primary" style="width:100%; background:linear-gradient(135deg, #ec4899, #be185d); border:none; box-shadow:0 4px 15px rgba(236,72,153,0.4);">
                                            <i class="fa-solid fa-dharmachakra"></i> Quay Thưởng May Mắn 🎡
                                        </button>
                                    <?php elseif ($ev['event_type'] === 'code'): ?>
                                        <button type="button" onclick="openCodeModal(<?= $ev['id'] ?>, '<?= htmlspecialchars(addslashes($ev['title'])) ?>', '<?= htmlspecialchars(addslashes($ev['reward_name'])) ?>')" class="btn btn-primary" style="width:100%; background:linear-gradient(135deg, #f59e0b, #d97706); border:none; box-shadow:0 4px 15px rgba(245,158,11,0.4);">
                                            <i class="fa-solid fa-key"></i> Nhập Giftcode Bí Mật
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- TAB 2: HÒM ĐỒ QUÀ CỦA TÔI -->
        <?php if ($tab === 'my_gifts'): ?>
            <div class="card">
                <div class="card-head">
                    <div class="card-title"><i class="fa-solid fa-boxes-packing" style="color:#ec4899;"></i> Các Phần Quà MMO Bạn Đã Nhận (<?= count($my_gifts) ?>)</div>
                    <span style="font-size:12px; color:#a79bb7;">Tài khoản được lưu an toàn tại đây để bạn xem lại bất cứ lúc nào</span>
                </div>

                <?php if (empty($my_gifts)): ?>
                    <div style="text-align:center; padding:50px 20px; color:#a79bb7;">
                        <i class="fa-solid fa-box-open" style="font-size:48px; color:#64748b; margin-bottom:14px;"></i>
                        <h4 style="color:#f3e8ff; margin-bottom:6px;">Hòm đồ hiện đang trống</h4>
                        <p style="font-size:13px; margin:0 0 16px;">Bạn chưa nhận phần quà nào. Hãy chuyển sang tab "Sự Kiện Đang Mở" để rinh quà nhé!</p>
                        <a href="?tab=active" class="btn btn-primary btn-sm"><i class="fa-solid fa-gift"></i> Đến Sự Kiện Nhận Quà</a>
                    </div>
                <?php else: ?>
                    <div style="padding:20px; display:flex; flex-direction:column; gap:16px;">
                        <?php foreach ($my_gifts as $g): ?>
                            <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(168,85,247,0.25); border-radius:16px; padding:18px 20px;">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
                                    <div>
                                        <div style="font-size:11.5px; color:#a79bb7; font-weight:700;">Sự kiện: <?= htmlspecialchars($g['event_title'] ?: 'Sự kiện MMO') ?></div>
                                        <h3 style="font-size:17px; font-weight:800; color:#34d399; margin:3px 0 0;">
                                            <i class="fa-solid fa-award"></i> <?= htmlspecialchars($g['reward_name']) ?>
                                        </h3>
                                    </div>
                                    <span style="font-size:12px; color:#a79bb7; background:rgba(0,0,0,0.3); padding:4px 10px; border-radius:20px;">
                                        <i class="fa-solid fa-clock"></i> Nhận lúc: <?= date('d/m/Y H:i', strtotime($g['claimed_at'])) ?>
                                    </span>
                                </div>

                                <!-- Box thông tin bàn giao -->
                                <div style="background:rgba(0,0,0,0.35); border:1px solid rgba(52,211,153,0.3); border-radius:12px; padding:14px; position:relative;">
                                    <div style="font-size:11px; font-weight:800; color:#6ee7b7; text-transform:uppercase; margin-bottom:6px; display:flex; justify-content:space-between; align-items:center;">
                                        <span><i class="fa-solid fa-key"></i> THÔNG TIN TÀI KHOẢN &amp; HƯỚNG DẪN BÀN GIAO:</span>
                                        <button type="button" onclick="copyGiftText(this, '<?= htmlspecialchars(addslashes($g['reward_info'])) ?>')" style="background:rgba(52,211,153,0.2); border:1px solid rgba(52,211,153,0.4); color:#34d399; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px; cursor:pointer;">
                                            <i class="fa-solid fa-copy"></i> Sao chép
                                        </button>
                                    </div>
                                    <pre style="font-family:monospace; font-size:13px; color:#f3e8ff; margin:0; white-space:pre-wrap; word-break:break-all; line-height:1.5;"><?= htmlspecialchars($g['reward_info']) ?></pre>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- POPUP CHÚC MỪNG TRÚNG QUÀ THÀNH CÔNG -->
    <?php if ($awarded_gift): ?>
    <div class="modal-overlay show" id="successClaimModal">
        <div class="modal-box" style="max-width:520px; text-align:center; background:#1c1033 !important; border-color:#ec4899; box-shadow:0 0 50px rgba(236,72,153,0.4) !important;">
            <div style="width:70px; height:70px; border-radius:50%; background:linear-gradient(135deg, #ec4899, #8b5cf6); margin:0 auto 16px; display:flex; align-items:center; justify-content:center; font-size:32px; color:#fff; box-shadow:0 0 25px rgba(236,72,153,0.6);">
                <i class="fa-solid fa-gift"></i>
            </div>
            <h2 style="font-size:22px; font-weight:900; color:#f3e8ff; margin:0 0 6px;">🎉 CHÚC MỪNG BẠN NHẬN QUÀ!</h2>
            <p style="font-size:13px; color:#a79bb7; margin:0 0 16px;">Phần quà đặc biệt được tài trợ 100% bởi ThS. Lê Nhựt Khánh</p>

            <div style="background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.35); border-radius:14px; padding:12px; margin-bottom:16px;">
                <div style="font-size:11px; color:#6ee7b7; font-weight:700;">TÊN PHẦN QUÀ:</div>
                <div style="font-size:16px; font-weight:900; color:#34d399; margin-top:2px;">
                    <?= htmlspecialchars($awarded_gift['reward_name']) ?>
                </div>
            </div>

            <div style="background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:14px; text-align:left; margin-bottom:20px; position:relative;">
                <div style="font-size:11px; font-weight:800; color:#c084fc; text-transform:uppercase; margin-bottom:6px;">Thông tin tài khoản:</div>
                <pre id="awardedInfoText" style="font-family:monospace; font-size:12.5px; color:#f3e8ff; margin:0; white-space:pre-wrap; word-break:break-all; line-height:1.5;"><?= htmlspecialchars($awarded_gift['reward_info']) ?></pre>
                <button type="button" onclick="copyAwardedInfo()" style="margin-top:10px; background:linear-gradient(135deg, #ec4899, #8b5cf6); color:#fff; border:none; border-radius:8px; padding:6px 14px; font-size:12px; font-weight:800; cursor:pointer; width:100%;">
                    <i class="fa-solid fa-copy"></i> Sao chép toàn bộ thông tin
                </button>
            </div>

            <div style="display:flex; justify-content:center; gap:10px;">
                <a href="?tab=my_gifts" class="btn btn-primary" style="background:linear-gradient(135deg, #10b981, #059669); border:none;">
                    <i class="fa-solid fa-boxes-packing"></i> Xem Trong Hòm Đồ
                </a>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('successClaimModal').classList.remove('show')">Đóng</button>
            </div>
        </div>
    </div>
    <script>
    // Fire Confetti explosion on load
    window.addEventListener('DOMContentLoaded', function() {
        if (typeof confetti === 'function') {
            confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
            setTimeout(function() {
                confetti({ particleCount: 50, angle: 60, spread: 55, origin: { x: 0 } });
                confetti({ particleCount: 50, angle: 120, spread: 55, origin: { x: 1 } });
            }, 300);
        }
    });
    function copyAwardedInfo() {
        var txt = document.getElementById('awardedInfoText').innerText;
        navigator.clipboard.writeText(txt).then(function() {
            alert('✓ Đã sao chép thông tin tài khoản thành công!');
        });
    }
    </script>
    <?php endif; ?>

    <!-- MODAL VÒNG QUAY MAY MẮN (LUCKY WHEEL) -->
    <div class="modal-overlay" id="luckyWheelModal">
        <div class="modal-box" style="max-width:440px; text-align:center;">
            <div class="modal-title" id="wheelEventTitle" style="color:#f472b6;">🎡 Vòng Quay May Mắn MMO</div>
            <div class="modal-sub" id="wheelRewardSub">Quay để trúng quà tài khoản bản quyền</div>

            <div class="wheel-container">
                <div class="wheel-pointer"></div>
                <canvas id="wheelCanvas" width="280" height="280"></canvas>
            </div>

            <form method="POST" id="wheelClaimForm">
                <input type="hidden" name="action" value="claim_wheel">
                <input type="hidden" name="event_id" id="wheelEventId" value="">
                <button type="button" id="spinWheelBtn" onclick="spinWheelAnimation()" class="btn btn-primary" style="width:100%; font-size:15px; font-weight:900; background:linear-gradient(135deg, #ec4899, #8b5cf6); border:none; padding:12px; box-shadow:0 6px 20px rgba(236,72,153,0.5);">
                    <i class="fa-solid fa-dharmachakra fa-spin"></i> QUAY NGAY (1 LƯỢT MIỄN PHÍ)
                </button>
            </form>

            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleModal('luckyWheelModal')" style="margin-top:12px;">Đóng</button>
        </div>
    </div>

    <!-- MODAL NHẬP GIFTCODE BÍ MẬT -->
    <div class="modal-overlay" id="codeModal">
        <div class="modal-box" style="max-width:420px;">
            <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-key" style="color:#f59e0b;"></i> Nhập Giftcode Bí Mật
            </div>
            <div class="modal-sub" id="codeEventTitle">Mở khóa phần quà tài trợ từ ThS. Lê Nhựt Khánh</div>

            <form method="POST">
                <input type="hidden" name="action" value="claim_code">
                <input type="hidden" name="event_id" id="codeEventId" value="">

                <div class="form-group" style="margin:18px 0;">
                    <label class="form-label">Nhập mã Giftcode của bạn *</label>
                    <input type="text" name="secret_code" class="form-input" placeholder="Ví dụ: LENHUTKHANH_MMO" required style="font-family:monospace; font-size:15px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase; text-align:center;">
                    <div style="font-size:11.5px; color:#a79bb7; margin-top:6px; text-align:center;">Gợi ý: Mã được Admin chia sẻ trên lớp hoặc qua thông báo.</div>
                </div>

                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-ghost" onclick="toggleModal('codeModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg, #f59e0b, #d97706); border:none;"><i class="fa-solid fa-unlock"></i> Mở Khóa Quà</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleModal(id) {
        var el = document.getElementById(id);
        if (el) {
            el.classList.toggle('show');
            el.classList.toggle('open');
            el.classList.toggle('active');
        }
    }

    function openCodeModal(evId, title, reward) {
        document.getElementById('codeEventId').value = evId;
        document.getElementById('codeEventTitle').innerText = 'Sự kiện: ' + title;
        toggleModal('codeModal');
    }

    function copyGiftText(btn, text) {
        navigator.clipboard.writeText(text).then(function() {
            var oldHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Đã chép!';
            setTimeout(function() { btn.innerHTML = oldHtml; }, 2000);
        });
    }

    // Interactive Lucky Wheel Canvas Logic
    var wheelPrizes = ['ChatGPT 4o', 'Canva Pro', 'Gemini Pro', 'CapCut Pro', 'Netflix', 'Spotify'];
    var wheelColors = ['#ec4899', '#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'];
    var currentWheelRotation = 0;

    function drawWheel() {
        var canvas = document.getElementById('wheelCanvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var num = wheelPrizes.length;
        var arc = (2 * Math.PI) / num;
        var cx = 140, cy = 140, r = 135;

        ctx.clearRect(0, 0, 280, 280);

        for (var i = 0; i < num; i++) {
            var angle = i * arc;
            ctx.beginPath();
            ctx.fillStyle = wheelColors[i];
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, r, angle, angle + arc);
            ctx.lineTo(cx, cy);
            ctx.fill();
            ctx.stroke();

            // Text
            ctx.save();
            ctx.translate(cx, cy);
            ctx.rotate(angle + arc / 2);
            ctx.textAlign = 'right';
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 12px Plus Jakarta Sans, sans-serif';
            ctx.fillText(wheelPrizes[i], r - 15, 4);
            ctx.restore();
        }

        // Center hub
        ctx.beginPath();
        ctx.arc(cx, cy, 22, 0, 2 * Math.PI);
        ctx.fillStyle = '#1e1035';
        ctx.fill();
        ctx.lineWidth = 3;
        ctx.strokeStyle = '#f3e8ff';
        ctx.stroke();

        ctx.fillStyle = '#ec4899';
        ctx.font = 'bold 10px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('MMO', cx, cy + 4);
    }

    function openLuckyWheelModal(evId, title, reward) {
        document.getElementById('wheelEventId').value = evId;
        document.getElementById('wheelEventTitle').innerText = '🎡 ' + title;
        document.getElementById('wheelRewardSub').innerText = 'Trúng thưởng: ' + reward;
        toggleModal('luckyWheelModal');
        setTimeout(drawWheel, 100);
    }

    function spinWheelAnimation() {
        var btn = document.getElementById('spinWheelBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> ĐANG QUAY THƯỞNG...';

        var canvas = document.getElementById('wheelCanvas');
        var randomRot = 1800 + Math.floor(Math.random() * 360);
        currentWheelRotation += randomRot;
        canvas.style.transform = 'rotate(' + currentWheelRotation + 'deg)';

        setTimeout(function() {
            document.getElementById('wheelClaimForm').submit();
        }, 4200);
    }
    </script>
</body>
</html>
