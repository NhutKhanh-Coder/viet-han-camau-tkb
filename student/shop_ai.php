<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
requireStudent();

$db = getDB();

try {
    // Tự động khởi tạo bảng nếu chưa có
    @$db->query("
    CREATE TABLE IF NOT EXISTS `ai_accounts_store` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(255) NOT NULL,
      `ai_type` VARCHAR(50) DEFAULT 'ChatGPT',
      `image_url` VARCHAR(500) DEFAULT NULL,
      `category` VARCHAR(50) DEFAULT 'AI',
      `price` INT NOT NULL DEFAULT 50000,
      `variants` TEXT DEFAULT NULL,
      `stock` INT NOT NULL DEFAULT 1,
      `account_info` TEXT NOT NULL,
      `description` TEXT DEFAULT NULL,
      `bank_info` VARCHAR(255) DEFAULT 'MBBank - 0392826609 - LE NHUT KHANH',
      `teacher_id` INT DEFAULT 0,
      `teacher_name` VARCHAR(100) DEFAULT 'Giảng viên',
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    @$db->query("
    CREATE TABLE IF NOT EXISTS `mmo_coupons` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `code` VARCHAR(50) NOT NULL UNIQUE,
      `discount_percent` INT NOT NULL DEFAULT 10,
      `max_uses` INT DEFAULT 100,
      `used_count` INT DEFAULT 0,
      `status` ENUM('active', 'inactive') DEFAULT 'active',
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    @$db->query("
    CREATE TABLE IF NOT EXISTS `ai_account_orders` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `store_id` INT NOT NULL,
      `student_id` INT NOT NULL,
      `student_name` VARCHAR(100) DEFAULT 'Sinh viên',
      `account_title` VARCHAR(255) NOT NULL,
      `price` INT NOT NULL,
      `quantity` INT NOT NULL DEFAULT 1,
      `account_info` TEXT NOT NULL,
      `payment_method` VARCHAR(50) DEFAULT 'momo_qr',
      `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    @$db->query("ALTER TABLE `ai_account_orders` ADD COLUMN `quantity` INT NOT NULL DEFAULT 1 AFTER `price`");
    @$db->query("ALTER TABLE `ai_account_orders` ADD COLUMN `chat_messages` TEXT DEFAULT NULL AFTER `status`");
} catch (Throwable $e) {}

$student_id = $_SESSION['student_id'] ?? ($_SESSION['user_id'] ?? 0);
$student_name = $_SESSION['user_name'] ?? ($_SESSION['ho_ten'] ?? 'Sinh viên');

$msg = '';
$error = '';
$boughtPending = false;

// Xử lý XÁC NHẬN ĐÃ THANH TOÁN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy_ai_account') {
    $store_id = (int)($_POST['store_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $variant_title = trim($_POST['variant_title'] ?? '');
    $coupon_code = strtoupper(trim($_POST['applied_coupon_code'] ?? ''));

    $resProd = $db->query("SELECT * FROM ai_accounts_store WHERE id = $store_id");
    $prod = $resProd ? $resProd->fetch_assoc() : null;

    if (!$prod) {
        $error = "Sản phẩm MMO không tồn tại hoặc đã bị gỡ!";
    } elseif ($prod['stock'] < $quantity) {
        $error = "Sản phẩm MMO này không đủ số lượng trong kho! Còn lại: " . $prod['stock'];
    } else {
        $final_title = $variant_title ?: $prod['title'];
        $unit_price = (int)($_POST['variant_price'] ?? $prod['price']);
        $total_price = $unit_price * $quantity;

        // Nếu có mã giảm giá hợp lệ từ DB
        if (!empty($coupon_code)) {
            $safe_code = $db->real_escape_string($coupon_code);
            $cpnRes = $db->query("SELECT * FROM mmo_coupons WHERE code = '$safe_code' AND status = 'active'");
            $cpnObj = $cpnRes ? $cpnRes->fetch_assoc() : null;
            if ($cpnObj) {
                $pct = (int)$cpnObj['discount_percent'];
                $total_price = max(0, (int)round($total_price * (1 - $pct / 100)));
                $db->query("UPDATE mmo_coupons SET used_count = used_count + 1 WHERE id = " . (int)$cpnObj['id']);
                $final_title .= " (Mã " . $cpnObj['code'] . " -$pct%)";
            }
        }

        $db->query("UPDATE ai_accounts_store SET stock = stock - $quantity WHERE id = $store_id AND stock >= $quantity");

        $safe_title = $db->real_escape_string($final_title);
        $safe_info = $db->real_escape_string($prod['account_info']);
        $safe_name = $db->real_escape_string($student_name);
        
        $sql = "INSERT INTO ai_account_orders (store_id, student_id, student_name, account_title, price, quantity, account_info, payment_method, status) 
                VALUES ($store_id, $student_id, '$safe_name', '$safe_title', $total_price, $quantity, '$safe_info', 'momo_qr', 'pending')";
        
        try {
            if ($db->query($sql)) {
                $inserted_id = $db->insert_id;
                $order_code = "#MMO-" . sprintf('%05d', $inserted_id);
                $msg = "🎉 Tạo thành công Đơn hàng " . $order_code . "! Đã gửi thông báo thanh toán, đang chờ Thầy/Cô kiểm tra số tiền chuyển khoản để phê duyệt bàn giao tài khoản cho bạn.";
                $boughtPending = true;
            } else {
                $error = "Lỗi cấu trúc CSDL: Không thể tạo đơn (bảng đơn hàng có thể chưa tồn tại). Chi tiết: " . $db->error;
            }
        } catch (Throwable $ex) {
            $error = "Lỗi Database: " . $ex->getMessage();
        }
    }
}

// Xử lý GỬI TIN NHẮN (CHAT) TRONG ĐƠN HÀNG (HOẶC AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['fetch_chat_ajax', 'send_chat_ajax', 'fetch', 'send', 'send_chat'])) {
    $chat_order_id = (int)($_POST['order_id'] ?? 0);
    $chat_msg = trim($_POST['message'] ?? '');
    $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || in_array($_POST['action'], ['fetch_chat_ajax', 'send_chat_ajax', 'fetch', 'send']);

    if ($chat_order_id > 0) {
        $checkOrdRes = $db->query("SELECT * FROM ai_account_orders WHERE id = $chat_order_id AND student_id = $student_id");
        $ordRow = $checkOrdRes ? $checkOrdRes->fetch_assoc() : null;
        
        if ($ordRow) {
            $chatHistory = json_decode($ordRow['chat_messages'] ?? '[]', true);
            if (!is_array($chatHistory)) $chatHistory = [];
            
            if ($_POST['action'] === 'fetch' || $_POST['action'] === 'fetch_chat_ajax') {
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'messages' => $chatHistory]);
                    exit;
                }
            }

            if (!empty($chat_msg)) {
                $chatHistory[] = [
                    'sender' => 'student',
                    'name' => $student_name,
                    'text' => $chat_msg,
                    'time' => date('Y-m-d H:i:s')
                ];
                
                $newChatJson = $db->real_escape_string(json_encode($chatHistory, JSON_UNESCAPED_UNICODE));
                $db->query("UPDATE ai_account_orders SET chat_messages = '$newChatJson' WHERE id = $chat_order_id");
                
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'messages' => $chatHistory]);
                    exit;
                } else {
                    header("Location: shop_ai.php?msg=" . urlencode("Đã gửi tin nhắn!"));
                    exit;
                }
            } elseif ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Tin nhắn không được trống']);
                exit;
            }
        } else if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập đơn hàng']);
            exit;
        } else {
            $error = "Bạn không có quyền chat trong đơn hàng này.";
        }
    }
}

// Lấy danh sách sản phẩm MMO khả dụng
$productsRes = $db->query("SELECT * FROM ai_accounts_store ORDER BY id DESC");
$products = [];
if ($productsRes) {
    if (method_exists($productsRes, 'fetch_all')) {
        $products = $productsRes->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $productsRes->fetch_assoc()) {
            $products[] = $row;
        }
    }
}

// Lấy danh sách tài khoản MMO sinh viên này đã mua
$myOrdersRes = $db->query("SELECT * FROM ai_account_orders WHERE student_id = $student_id ORDER BY id DESC");
$myOrders = [];
if ($myOrdersRes) {
    if (method_exists($myOrdersRes, 'fetch_all')) {
        $myOrders = $myOrdersRes->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $myOrdersRes->fetch_assoc()) {
            $myOrders[] = $row;
        }
    }
}

// Lấy danh sách mã giảm giá đang hoạt động
$activeCouponsRes = $db->query("SELECT code, discount_percent FROM mmo_coupons WHERE status = 'active'");
$activeCouponsRaw = [];
if ($activeCouponsRes) {
    if (method_exists($activeCouponsRes, 'fetch_all')) {
        $activeCouponsRaw = $activeCouponsRes->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $activeCouponsRes->fetch_assoc()) {
            $activeCouponsRaw[] = $row;
        }
    }
}
$couponDict = [];
foreach ($activeCouponsRaw as $c) {
    $couponDict[strtoupper($c['code'])] = (int)$c['discount_percent'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chợ Sản Phẩm & Tài Khoản MMO — VKC Tube Divine</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* =========================================================
   SUPER PREMIUM MMO MARKETPLACE — DARK DIVINE THEME
   ========================================================= */

.mmo-shop-page { padding: 10px 0; }

.mmo-shop-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #450a0a 100%);
    border-radius: 20px;
    padding: 30px 32px 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    color: #fff;
    border: 1px solid rgba(239, 68, 68, 0.25);
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.mmo-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid rgba(239, 68, 68, 0.4);
    border-radius: 50px;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #fca5a5;
}

.mmo-shop-hero h1 { font-size: 26px; font-weight: 900; margin: 0 0 8px; }
.mmo-shop-hero p { font-size: 13.5px; opacity: 0.85; margin: 0; max-width: 650px; line-height: 1.5; }

/* CATEGORY FILTER CHIPS BAR */
.mmo-chips-bar {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 12px;
    margin-bottom: 20px;
    scrollbar-width: none;
}
.mmo-chips-bar::-webkit-scrollbar { display: none; }

.mmo-chip {
    padding: 10px 20px;
    background: #181825;
    border: 1px solid #334155;
    color: #cbd5e1;
    border-radius: 14px;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    display: flex; align-items: center; gap: 8px;
}
.mmo-chip:hover { border-color: #ef4444; color: #ef4444; }
.mmo-chip.active {
    background: #ef4444;
    color: #fff;
    border-color: #ef4444;
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4);
}

/* SEARCH INPUT BOX */
.mmo-search-box {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    background: #181825;
    border: 1px solid #334155;
    border-radius: 14px;
    padding: 10px 18px;
}
.mmo-search-input {
    flex: 1;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 14.5px;
    outline: none;
}

/* NAVIGATION TABS */
.ai-nav-tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    border-bottom: 2px solid #2a2a3e;
    padding-bottom: 14px;
}

.ai-tab-btn {
    padding: 11px 22px;
    border-radius: 12px;
    background: #181825;
    border: 1px solid #334155;
    color: #94a3b8;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.ai-tab-btn:hover {
    color: #fff;
    border-color: #ef4444;
}

.ai-tab-btn.active {
    background: #ef4444;
    color: #ffffff;
    border-color: #ef4444;
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4);
}

/* DIVINE PRODUCT CARD GRID */
.mmo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

.divine-product-card {
    background: #181825;
    border: 1px solid #2a2a3e;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    position: relative;
}

.divine-product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(239, 68, 68, 0.25);
    border-color: #ef4444;
}

.divine-card-banner {
    width: 100%;
    height: 190px;
    position: relative;
    background: #0f172a;
    overflow: hidden;
}

.divine-card-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.divine-product-card:hover .divine-card-banner img {
    transform: scale(1.08);
}

.divine-card-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    flex: 1;
    background: #181825;
}

.divine-card-title {
    font-size: 17px;
    font-weight: 800;
    color: #f8fafc;
    margin: 0 0 6px;
    line-height: 1.35;
    text-transform: lowercase;
}
.divine-card-title::first-letter { text-transform: uppercase; }

.divine-card-sub {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 16px;
    font-weight: 600;
}

.divine-card-footer-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-top: 4px;
}

.divine-card-price {
    font-size: 20px;
    font-weight: 900;
    color: #ef4444;
    line-height: 1.1;
    margin-bottom: 8px;
}
.divine-card-price u {
    text-decoration: underline;
    font-size: 14px;
    text-underline-offset: 2px;
}

.divine-card-meta {
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
}

.divine-star-rating {
    color: #f59e0b;
    font-weight: 800;
    display: flex; align-items: center; gap: 3px;
}

.divine-bag-btn {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    border: 1.5px solid #ef4444;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    cursor: pointer;
    transition: all 0.25s ease;
}

.divine-bag-btn:hover {
    background: #ef4444;
    color: #ffffff;
    border-color: #ef4444;
    transform: scale(1.1);
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4);
}

/* MODAL DIVINE SHOP STYLE */
.ai-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.85);
    backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    z-index: 999999; opacity: 0; pointer-events: none;
    transition: opacity 0.25s ease;
}
.ai-modal-backdrop.active { opacity: 1 !important; pointer-events: auto !important; display: flex !important; }

.ai-modal {
    background: #181825;
    border: 2px solid #ef4444;
    border-radius: 20px;
    width: 92%; max-width: 500px;
    padding: 24px 26px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.8);
    color: #f8fafc;
    position: relative;
    max-height: 90vh; overflow-y: auto;
}

.ai-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px; padding-bottom: 12px;
    border-bottom: 1px solid #2a2a3e;
}
.ai-modal-header h3 { font-size: 18px; font-weight: 800; margin: 0; color: #fff; }
.ai-modal-close {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(255,255,255,0.1); border: none; color: #fff;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
}

.divine-summary-box { margin-bottom: 16px; }
.divine-summary-label { font-size: 13.5px; font-weight: 700; color: #94a3b8; margin-bottom: 4px; }
.divine-summary-price { font-size: 32px; font-weight: 900; color: #ef4444; line-height: 1.1; display: flex; align-items: baseline; gap: 4px; }
.divine-summary-price u { text-decoration: underline; font-size: 20px; text-underline-offset: 3px; }
.divine-stock-badge { font-size: 12.5px; font-weight: 700; color: #10b981; margin-top: 6px; display: flex; align-items: center; gap: 6px; }

.divine-options-sec { margin-bottom: 18px; }
.divine-sec-title { font-size: 14px; font-weight: 800; color: #f8fafc; margin-bottom: 10px; }

.divine-option-card {
    border: 2px solid #334155;
    background: #11111b;
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex; align-items: flex-start; gap: 12px;
}
.divine-option-card:hover { border-color: #ef4444; }
.divine-option-card.selected { border-color: #ef4444; background: rgba(239, 68, 68, 0.08); box-shadow: 0 0 0 1px #ef4444; }

.divine-opt-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(239, 68, 68, 0.12); color: #ef4444;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0; margin-top: 2px;
}
.divine-option-card.selected .divine-opt-icon { background: #ef4444; color: #fff; }
.divine-opt-info { flex: 1; }
.divine-opt-title { font-size: 14px; font-weight: 800; color: #f8fafc; margin-bottom: 4px; line-height: 1.35; }
.divine-opt-sub { font-size: 12px; color: #94a3b8; }
.divine-opt-price { font-size: 16px; font-weight: 800; color: #ef4444; white-space: nowrap; }

.divine-qty-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.divine-qty-stepper { display: inline-flex; align-items: center; background: #11111b; border: 1px solid #334155; border-radius: 10px; overflow: hidden; }
.divine-qty-btn { width: 38px; height: 38px; background: transparent; border: none; color: #f8fafc; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.divine-qty-val { width: 46px; text-align: center; font-size: 15px; font-weight: 800; color: #fff; border: none; background: transparent; }

.divine-action-group { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.divine-btn-buy { width: 100%; padding: 14px; background: #ef4444; color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 16px rgba(239, 68, 68, 0.35); }
.divine-btn-cart { width: 100%; padding: 12px; background: rgba(239, 68, 68, 0.08); color: #ef4444; border: 1.5px solid #ef4444; border-radius: 12px; font-size: 15px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }

.divine-coupon-box { background: #11111b; border: 1px solid #334155; border-radius: 12px; padding: 12px 14px; }
.divine-coupon-header { display: flex; align-items: center; justify-content: space-between; font-size: 13.5px; font-weight: 700; color: #f8fafc; cursor: pointer; }
.divine-coupon-form { display: flex; gap: 8px; margin-top: 10px; }
.divine-coupon-input { flex: 1; padding: 8px 12px; background: #1e1e2e; border: 1px solid #334155; border-radius: 8px; color: #fff; font-size: 13px; outline: none; }
.divine-coupon-btn { padding: 8px 14px; background: #ef4444; color: #fff; border: none; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; }

.my-account-card { background: #1e1e2e; border: 1px solid #2a2a3e; border-radius: 16px; padding: 20px; margin-bottom: 16px; }
.my-account-title { font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; }
.my-account-code { background: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 12px 16px; font-family: monospace; font-size: 14px; color: #38bdf8; margin-top: 10px; word-break: break-all; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.copy-btn { padding: 4px 10px; background: #38bdf8; color: #0f172a; border: none; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap; }

.alert-box { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; }
.alert-danger { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; }
</style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/student_nav.php'; ?>

<div class="mmo-shop-page">

    <!-- Hero Banner -->
    <div class="mmo-shop-hero">
        <div class="mmo-hero-badge"><i class="fa-solid fa-fire"></i> VKC MMO Digital Marketplace</div>
        <h1>🛒 Chợ Sản Phẩm & Dịch Vụ MMO All-In-One</h1>
        <p>Thanh toán trực tiếp bằng mã VietQR MB Bank. Sau khi chuyển khoản, Thầy/Cô kiểm tra và phê duyệt bàn giao tài khoản / key dịch vụ tức thì!</p>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg): ?>
        <div class="alert-box alert-success">
            <i class="fa-solid fa-clock-rotate-left"></i> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-box alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Category Filter Chips Bar -->
    <div class="mmo-chips-bar">
        <button class="mmo-chip active" onclick="filterMmoCat('ALL', this)"><i class="fa-solid fa-store"></i> Tất Cả MMO (<?= count($products) ?>)</button>
        <button class="mmo-chip" onclick="filterMmoCat('AI', this)"><i class="fa-solid fa-robot"></i> 🤖 Tài Khoản AI & Premium</button>
        <button class="mmo-chip" onclick="filterMmoCat('SOCIAL', this)"><i class="fa-solid fa-thumbs-up"></i> 📲 Acc Mạng Xã Hội</button>
        <button class="mmo-chip" onclick="filterMmoCat('VPS_PROXY', this)"><i class="fa-solid fa-server"></i> 💻 VPS & Proxy MMO</button>
        <button class="mmo-chip" onclick="filterMmoCat('SOFTWARE', this)"><i class="fa-solid fa-code"></i> ⚙️ Tool & Code Script</button>
        <button class="mmo-chip" onclick="filterMmoCat('GIFTCARD', this)"><i class="fa-solid fa-gift"></i> 🎁 Giftcard & Premium</button>
    </div>

    <!-- Search Input Bar -->
    <div class="mmo-search-box">
        <i class="fa-solid fa-magnifying-glass" style="color:#ef4444;"></i>
        <input type="text" id="mmoSearchInput" class="mmo-search-input" placeholder="Tìm kiếm sản phẩm MMO, tài khoản AI, Via FB, VPS Windows..." oninput="doMmoSearch()">
    </div>

    <!-- Navigation Tabs -->
    <div class="ai-nav-tabs">
        <button class="ai-tab-btn active" id="tabShopBtn" onclick="switchTab('shop')">
            <i class="fa-solid fa-store"></i> Cửa Hàng Gói MMO (<?= count($products) ?>)
        </button>
        <button class="ai-tab-btn" id="tabMyBtn" onclick="switchTab('my')">
            <i class="fa-solid fa-key"></i> Tài Khoản Đã Mua & Chờ Duyệt (<?= count($myOrders) ?>)
        </button>
    </div>

    <!-- TAB 1: CỬA HÀNG SẢN PHẨM MMO -->
    <div id="tabShop">
        <?php if (empty($products)): ?>
            <div style="text-align:center; padding:60px 20px; background:#181825; border-radius:18px; color:#94a3b8;">
                <i class="fa-solid fa-store" style="font-size:50px; color:#ef4444; margin-bottom:14px;"></i>
                <h3 style="color:#fff; margin:0 0 8px;">Chưa Có Sản Phẩm MMO Nào Được Đăng Bán</h3>
                <p style="margin:0;">Các Thầy/Cô chưa đăng bán sản phẩm MMO nào. Bạn quay lại sau nhé!</p>
            </div>
        <?php else: ?>
            <div class="mmo-grid" id="mmoGrid">
                <?php foreach ($products as $prod): 
                    $cat = $prod['category'] ?? 'AI';
                    $vParsed = json_decode($prod['variants'] ?? '', true);
                    
                    $posterImg = $prod['image_url'] ?? '';
                    if (empty($posterImg)) {
                        $titleLower = strtolower($prod['title']);
                        if (strpos($titleLower, 'gemini') !== false) {
                            $posterImg = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&auto=format&fit=crop';
                        } elseif (strpos($titleLower, 'chatgpt') !== false) {
                            $posterImg = 'https://images.unsplash.com/photo-1677442136019-21780efad99a?w=600&auto=format&fit=crop';
                        } elseif ($cat === 'SOCIAL') {
                            $posterImg = 'https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&auto=format&fit=crop';
                        } elseif ($cat === 'VPS_PROXY') {
                            $posterImg = 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=600&auto=format&fit=crop';
                        } elseif ($cat === 'SOFTWARE') {
                            $posterImg = 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&auto=format&fit=crop';
                        } else {
                            $posterImg = 'https://images.unsplash.com/photo-1579202673506-ca3ce28943ef?w=600&auto=format&fit=crop';
                        }
                    }

                    $subTitleText = $prod['ai_type'] ?: ($cat === 'SOCIAL' ? 'Tài khoản Mạng Xã Hội' : 'Tài Khoản Premium');
                ?>
                    <div class="divine-product-card" data-cat="<?= htmlspecialchars($cat) ?>" data-title="<?= htmlspecialchars(strtolower($prod['title'])) ?>" data-teacher="<?= htmlspecialchars(strtolower($prod['teacher_name'])) ?>">
                        <div class="divine-card-banner">
                            <img src="<?= htmlspecialchars($posterImg) ?>" alt="<?= htmlspecialchars($prod['title']) ?>">
                        </div>
                        
                        <div class="divine-card-body">
                            <div>
                                <h3 class="divine-card-title"><?= htmlspecialchars($prod['title']) ?></h3>
                                <div class="divine-card-sub"><?= htmlspecialchars($subTitleText) ?></div>
                            </div>

                            <div>
                                <div class="divine-card-price"><?= number_format($prod['price']) ?> <u>đ</u></div>
                                
                                <div class="divine-card-footer-row">
                                    <div class="divine-card-meta">
                                        <span class="divine-star-rating"><i class="fa-solid fa-star"></i> 0.0</span>
                                        <span>•</span>
                                        <span>Còn <?= $prod['stock'] ?></span>
                                        <span>•</span>
                                        <span>Đã bán 0</span>
                                    </div>

                                    <?php if ($prod['stock'] > 0): ?>
                                        <button class="divine-bag-btn" onclick="openBuyModal(<?= htmlspecialchars(json_encode([
                                            'id' => $prod['id'],
                                            'title' => $prod['title'],
                                            'base_price' => $prod['price'],
                                            'variants' => $vParsed ?: [],
                                            'stock' => $prod['stock'],
                                            'teacher' => $prod['teacher_name'],
                                            'bank' => $prod['bank_info'] ?: 'MBBank - 0392826609 - LE NHUT KHANH'
                                        ]), ENT_QUOTES, 'UTF-8') ?>)" title="Mua ngay">
                                            <i class="fa-solid fa-bag-shopping"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="divine-bag-btn" disabled style="opacity:0.4; cursor:not-allowed;">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- TAB 2: TÀI KHOẢN ĐÃ MUA & CHỜ DUYỆT TIỀN -->
    <div id="tabMy" style="display: none;">
        <?php if (empty($myOrders)): ?>
            <div style="text-align:center; padding:50px 20px; background:#181825; border-radius:18px; color:#94a3b8;">
                <i class="fa-solid fa-key" style="font-size:44px; color:#94a3b8; margin-bottom:12px;"></i>
                <h3 style="color:#fff; margin:0 0 6px;">Bạn Chưa Mua Sản Phẩm MMO Nào</h3>
                <p style="margin:0;">Vui lòng chuyển qua tab Cửa hàng để chọn mua nhé.</p>
            </div>
        <?php else: ?>
            <?php foreach ($myOrders as $idx => $ord): 
                $status = $ord['status'] ?? 'pending';
                $orderCode = "#MMO-" . sprintf('%05d', $ord['id']);
            ?>
                <div class="my-account-card" style="<?= $status === 'pending' ? 'border: 2px dashed #f59e0b; background: rgba(245, 158, 11, 0.05);' : '' ?>">
                    <div class="my-account-title" style="flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span style="background: linear-gradient(135deg, #ef4444, #b91c1c); color: #ffffff; padding: 4px 10px; border-radius: 8px; font-weight: 900; font-family: monospace; font-size: 13.5px; letter-spacing: 0.5px; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4); display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-receipt"></i> <?= $orderCode ?>
                            </span>
                            <span style="color:#fff; font-weight:800;">📦 <?= htmlspecialchars($ord['account_title']) ?> (x<?= $ord['quantity'] ?>)</span>
                        </div>
                        <span style="font-size:16px; color:#ef4444; font-weight:900;"><?= number_format($ord['price']) ?>đ</span>
                    </div>
                    <div style="font-size:12px; color:#94a3b8; margin-top: 6px; margin-bottom:10px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                        <span><i class="fa-regular fa-clock"></i> Ngày mua: <?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></span>
                        <div>
                            Trạng thái: 
                            <?php if ($status === 'pending'): ?>
                                <span style="color:#f59e0b; font-weight:800;"><i class="fa-solid fa-clock fa-spin"></i> CHỜ THẦY/CÔ XÁC NHẬN THANH TOÁN</span>
                            <?php elseif ($status === 'completed'): ?>
                                <span style="color:#34d399; font-weight:800;"><i class="fa-solid fa-check-double"></i> ĐÃ DUYỆT TIỀN & BÀN GIAO</span>
                            <?php else: ?>
                                <span style="color:#f87171; font-weight:800;"><i class="fa-solid fa-xmark"></i> ĐÃ HỦY</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($status === 'completed'): ?>
                        <div style="margin-top: 14px;">
                            <button type="button" class="divine-btn-buy" style="width: 100%; border-radius: 12px; background: linear-gradient(135deg, #0284c7, #0369a1); font-size: 14.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 13px; box-shadow: 0 4px 16px rgba(2, 132, 199, 0.4); cursor: pointer; border: none;" onclick="toggleNoteCard(<?= (int)$ord['id'] ?>)">
                                <i class="fa-solid fa-envelope-open-text" style="font-size: 17px;"></i> 📝 Mở Note Lấy Tài Khoản Bàn Giao
                            </button>
                        </div>

                        <!-- KHUNG NOTE MỞ RA TRỰC TIẾP -->
                        <div id="note_card_box_<?= $ord['id'] ?>" style="display: none; margin-top: 12px; background: rgba(14, 165, 233, 0.08); border: 1.5px solid #0284c7; border-radius: 14px; padding: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.4);">
                            <div style="font-size: 13.5px; font-weight: 800; color: #38bdf8; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                                <span><i class="fa-solid fa-key"></i> 🔑 Tài khoản Giảng viên gửi cho bạn:</span>
                                <button type="button" class="copy-btn" onclick="copyText('note_text_<?= $ord['id'] ?>')" style="background: #0284c7; color: #fff; padding: 6px 14px; border-radius: 6px; font-weight: 700; cursor: pointer; border: none; font-size: 12.5px;"><i class="fa-solid fa-copy"></i> Sao chép tất cả</button>
                            </div>
                            <div id="note_text_<?= $ord['id'] ?>" style="background: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 14px; font-family: 'Consolas', 'Courier New', monospace; font-size: 14px; color: #38bdf8; white-space: pre-wrap; word-break: break-word; line-height: 1.6; font-weight: 600;">
<?= htmlspecialchars($ord['account_info'] ?: '(Chưa có thông tin tài khoản bàn giao)') ?>
                            </div>
                        </div>
                    <?php elseif ($status === 'pending'): ?>
                        <div style="background:#181825; border:1px solid #f59e0b; border-radius:10px; padding:12px; font-size:13px; color:#fde68a; margin-bottom: 12px;">
                            <i class="fa-solid fa-lock" style="margin-right:6px;"></i> Thông tin tài khoản / key đang được ẩn. Thầy/Cô sẽ đối soát số tiền chuyển khoản cho đơn hàng <strong><?= $orderCode ?></strong> và mở khóa thông tin tài khoản cho bạn trong giây lát!
                        </div>
                    <?php else: ?>
                        <div style="color:#f87171; font-size:13px; margin-bottom: 12px;">Đơn hàng <?= $orderCode ?> này đã bị hủy.</div>
                    <?php endif; ?>
                    
                    <button class="mmo-chip" style="margin-top: 8px; width: 100%; border-radius: 8px; justify-content: center; background: #334155; color: #fff; border: 1px solid #475569;" onclick='openChatModal(<?= htmlspecialchars(json_encode([
                        "id" => $ord["id"],
                        "title" => $orderCode . " - " . $ord["account_title"],
                        "messages" => json_decode($ord["chat_messages"] ?? "[]", true)
                    ]), ENT_QUOTES, "UTF-8") ?>)'>
                        <i class="fa-solid fa-message"></i> Trao đổi với Giảng viên về Đơn <?= $orderCode ?>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL CHỌN GÓI & THANH TOÁN VIETQR MB BANK -->
<div class="ai-modal-backdrop" id="buyModal">
    <div class="ai-modal">
        <div class="ai-modal-header">
            <h3><i class="fa-solid fa-box-open" style="color:#ef4444;"></i> Chọn Gói & Thanh Toán MMO</h3>
            <button class="ai-modal-close" onclick="closeBuyModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <!-- STEP 1: CHỌN GÓI & SỐ LƯỢNG (DIVINE STYLE) -->
        <div id="stepSelection">
            <div class="divine-summary-box">
                <div class="divine-summary-label">Gói đã chọn</div>
                <div class="divine-summary-price" id="displaySummaryPrice">180.000 <u>đ</u></div>
                <div class="divine-stock-badge"><i class="fa-solid fa-circle-check"></i> Còn hàng (Kho: <span id="displayStock">1333</span>)</div>
            </div>

            <div class="divine-options-sec">
                <div class="divine-sec-title">Chọn gói</div>
                <div id="variantListContainer">
                    <!-- Dynamic option cards -->
                </div>
            </div>

            <div class="divine-qty-row">
                <div style="font-size:14px; font-weight:800; color:#fff;">Số lượng</div>
                <div class="divine-qty-stepper">
                    <button class="divine-qty-btn" onclick="changeQty(-1)">-</button>
                    <input type="text" id="qtyInput" class="divine-qty-val" value="1" readonly>
                    <button class="divine-qty-btn" onclick="changeQty(1)">+</button>
                </div>
            </div>

            <div class="divine-action-group">
                <button type="button" class="divine-btn-buy" onclick="goToPaymentStep()">
                    Mua ngay
                </button>
                <button type="button" class="divine-btn-cart" onclick="addToCartMsg()">
                    <i class="fa-solid fa-cart-shopping"></i> Thêm vào giỏ
                </button>
            </div>

            <!-- Có mã giảm giá Accordion (TÍCH HỢP DB MMOCOUPOONS) -->
            <div class="divine-coupon-box">
                <div class="divine-coupon-header" onclick="toggleCouponForm()">
                    <span><i class="fa-solid fa-ticket" style="color:#ef4444;"></i> Có mã giảm giá?</span>
                    <i class="fa-solid fa-chevron-down" id="couponIcon"></i>
                </div>
                <div class="divine-coupon-form" id="couponForm" style="display:none;">
                    <input type="text" id="couponCode" class="divine-coupon-input" placeholder="Nhập mã SINHVIEN10, VKC20...">
                    <button type="button" class="divine-coupon-btn" onclick="applyCoupon()">Áp dụng</button>
                </div>
            </div>
        </div>

        <!-- STEP 2: MA QR THANH TOAN CHUAN VIETQR MB BANK -->
        <div id="stepPayment" style="display:none;">
            <form action="" method="POST">
                <input type="hidden" name="action" value="buy_ai_account">
                <input type="hidden" name="store_id" id="modalStoreId">
                <input type="hidden" name="quantity" id="formQuantity" value="1">
                <input type="hidden" name="variant_title" id="formVariantTitle">
                <input type="hidden" name="variant_price" id="formVariantPrice">
                <input type="hidden" name="applied_coupon_code" id="formAppliedCoupon">

                <div style="font-size:15px; font-weight:800; color:#fff; margin-bottom:4px;" id="modalProductTitle">Tên sản phẩm MMO</div>
                <div style="font-size:16px; color:#ef4444; font-weight:900; margin-bottom:14px;" id="modalProductPrice">Tổng tiền: 180.000đ</div>

                <div style="text-align:center; background:#ffffff; padding:16px; border-radius:16px; margin-bottom:16px; border:2px solid #ef4444;">
                    <div style="font-size:12px; font-weight:800; color:#1e1b4b; margin-bottom:8px;">
                        <i class="fa-solid fa-qrcode" style="color:#ef4444;"></i> MA QR THANH TOAN VIETQR MB BANK
                    </div>
                    <img id="modalQrImg" src="/tkb/assets/img/qr_payment.png" alt="Mã QR VietQR MB Bank" style="width:240px; height:auto; max-height:320px; object-fit:contain; border-radius:8px;">
                </div>

                <div style="text-align:center; font-size:13.5px; margin-bottom:18px; line-height:1.6; background:#11111b; padding:14px; border-radius:12px; border:1px solid #334155;">
                    <div style="color:#94a3b8;">Quét mã <strong>VietQR MB Bank</strong> với số tiền chính xác:</div>
                    <div style="color:#f59e0b; font-weight:900; font-size:15px; margin-top:2px;" id="modalBankInfo">MB Bank — 0392826609 — LE NHUT KHANH</div>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px dashed #ef4444; border-radius: 8px; padding: 8px 10px; margin-top: 10px; font-size: 12.5px; color: #fca5a5;">
                        <i class="fa-solid fa-note-sticky"></i> Nội dung chuyển khoản ghi: <strong style="color: #f59e0b; font-family: monospace; font-size: 13.5px;">MMO <?= htmlspecialchars($student_name) ?></strong>
                    </div>
                    <div style="font-size:12px; color:#ef4444; font-weight:700; margin-top:8px;">Lưu ý: Thầy/Cô sẽ duyệt và gửi tài khoản ngay khi nhận được số tiền!</div>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="button" style="padding:12px 16px; background:#334155; color:#fff; border:none; border-radius:10px; font-weight:700; cursor:pointer;" onclick="backToStep1()">
                        <i class="fa-solid fa-arrow-left"></i> Quay lại
                    </button>
                    <button type="submit" class="divine-btn-buy" style="flex:1;">
                        <i class="fa-solid fa-paper-plane"></i> Tôi Đã Thanh Toán — Gửi Yêu Cầu Duyệt
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
const couponDict = <?= json_encode($couponDict) ?>;
let currentProduct = null;
let selectedPrice = 180000;
let selectedTitle = '';
let currentQty = 1;
let discountPercent = 0;
let appliedCode = '';

function switchTab(tab) {
    if (tab === 'shop') {
        document.getElementById('tabShop').style.display = 'block';
        document.getElementById('tabMy').style.display = 'none';
        document.getElementById('tabShopBtn').classList.add('active');
        document.getElementById('tabMyBtn').classList.remove('active');
    } else {
        document.getElementById('tabShop').style.display = 'none';
        document.getElementById('tabMy').style.display = 'block';
        document.getElementById('tabMyBtn').classList.add('active');
        document.getElementById('tabShopBtn').classList.remove('active');
    }
}

function filterMmoCat(cat, chipEl) {
    document.querySelectorAll('.mmo-chip').forEach(c => c.classList.remove('active'));
    chipEl.classList.add('active');

    document.querySelectorAll('#mmoGrid .divine-product-card').forEach(card => {
        if (cat === 'ALL' || card.dataset.cat === cat) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function doMmoSearch() {
    const q = document.getElementById('mmoSearchInput').value.toLowerCase().trim();
    document.querySelectorAll('#mmoGrid .divine-product-card').forEach(card => {
        const title = card.dataset.title || '';
        const teacher = card.dataset.teacher || '';
        if (title.includes(q) || teacher.includes(q)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function openBuyModal(item) {
    currentProduct = item;
    currentQty = 1;
    discountPercent = 0;
    appliedCode = '';
    document.getElementById('formAppliedCoupon').value = '';

    document.getElementById('modalStoreId').value = item.id;
    document.getElementById('displayStock').textContent = item.stock;

    const container = document.getElementById('variantListContainer');
    container.innerHTML = '';

    let list = item.variants && item.variants.length > 0 ? item.variants : [
        { name: item.title, price: item.base_price }
    ];

    list.forEach((v, idx) => {
        const div = document.createElement('div');
        div.className = 'divine-option-card' + (idx === 0 ? ' selected' : '');
        div.onclick = () => selectDynamicVariant(div, v.price, v.name);
        div.innerHTML = `
            <div class="divine-opt-icon"><i class="fa-solid fa-box"></i></div>
            <div class="divine-opt-info">
                <div class="divine-opt-title">${v.name}</div>
                <div class="divine-opt-sub">Xử lý tiêu chuẩn</div>
            </div>
            <div class="divine-opt-price">${formatMoney(v.price)} <u>đ</u></div>
        `;
        container.appendChild(div);

        if (idx === 0) {
            selectDynamicVariant(div, v.price, v.name);
        }
    });

    document.getElementById('stepSelection').style.display = 'block';
    document.getElementById('stepPayment').style.display = 'none';
    document.getElementById('buyModal').classList.add('active');
}

function selectDynamicVariant(cardEl, price, title) {
    selectedPrice = price;
    selectedTitle = title;

    document.querySelectorAll('#variantListContainer .divine-option-card').forEach(c => c.classList.remove('selected'));
    cardEl.classList.add('selected');

    updateTotalPrice();
}

function changeQty(delta) {
    const maxStock = currentProduct ? currentProduct.stock : 10;
    currentQty = Math.max(1, Math.min(maxStock, currentQty + delta));
    document.getElementById('qtyInput').value = currentQty;
    updateTotalPrice();
}

function updateTotalPrice() {
    let total = selectedPrice * currentQty;
    if (discountPercent > 0) {
        total = Math.round(total * (1 - discountPercent / 100));
    }
    document.getElementById('displaySummaryPrice').innerHTML = formatMoney(total) + ' <u>đ</u>';
}

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount);
}

function goToPaymentStep() {
    document.getElementById('formQuantity').value = currentQty;
    document.getElementById('formVariantTitle').value = selectedTitle;
    document.getElementById('formVariantPrice').value = selectedPrice;
    document.getElementById('formAppliedCoupon').value = appliedCode;

    const total = Math.round((selectedPrice * currentQty) * (1 - discountPercent / 100));
    document.getElementById('modalProductTitle').textContent = `${selectedTitle} (x${currentQty})${appliedCode ? ' ['+appliedCode+' -'+discountPercent+'%]' : ''}`;
    document.getElementById('modalProductPrice').textContent = 'Tổng tiền thanh toán: ' + formatMoney(total) + 'đ';
    document.getElementById('modalBankInfo').textContent = currentProduct ? currentProduct.bank : 'MB Bank — 0392826609 — LE NHUT KHANH';

    document.getElementById('stepSelection').style.display = 'none';
    document.getElementById('stepPayment').style.display = 'block';
}

function backToStep1() {
    document.getElementById('stepSelection').style.display = 'block';
    document.getElementById('stepPayment').style.display = 'none';
}

function closeBuyModal() {
    document.getElementById('buyModal').classList.remove('active');
}

function toggleCouponForm() {
    const form = document.getElementById('couponForm');
    form.style.display = (form.style.display === 'none' || !form.style.display) ? 'flex' : 'none';
}

function applyCoupon() {
    const code = document.getElementById('couponCode').value.trim().toUpperCase();
    if (couponDict[code]) {
        discountPercent = couponDict[code];
        appliedCode = code;
        alert(`Áp dụng mã giảm giá "${code}" (-${discountPercent}%) thành công!`);
        updateTotalPrice();
    } else {
        alert('Mã giảm giá không tồn tại hoặc đã hết hạn!');
    }
}

function addToCartMsg() {
    alert(`Đã thêm ${currentQty} sản phẩm "${selectedTitle}" vào giỏ hàng của bạn!`);
}

function copyText(elementId) {
    const el = document.getElementById(elementId);
    const text = el ? el.innerText : '';
    navigator.clipboard.writeText(text).then(() => {
        alert('🎉 Đã sao chép thông tin tài khoản thành công!');
    }).catch(err => {
        alert('Đã sao chép: ' + text);
    });
}

function toggleNoteCard(id) {
    const box = document.getElementById('note_card_box_' + id);
    if (box) {
        if (box.style.display === 'none' || !box.style.display) {
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
        }
    }
}

let chatPollInterval = null;
let currentChatOrderId = 0;

function renderChatMessages(messages) {
    const chatBox = document.getElementById('chatMessagesBox');
    const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 50;
    
    chatBox.innerHTML = '';
    
    if (!messages || messages.length === 0) {
        chatBox.innerHTML = '<div style="text-align:center; padding: 20px; color: #94a3b8; font-size: 13px;">Chưa có tin nhắn nào. Bắt đầu nhắn tin với Giảng viên!</div>';
    } else {
        messages.forEach(msg => {
            const isMe = msg.sender === 'student';
            const msgDiv = document.createElement('div');
            msgDiv.style.display = 'flex';
            msgDiv.style.flexDirection = 'column';
            msgDiv.style.alignItems = isMe ? 'flex-end' : 'flex-start';
            msgDiv.style.marginBottom = '12px';
            
            msgDiv.innerHTML = `
                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px; padding: 0 4px;">
                    ${isMe ? 'Bạn' : 'Giảng viên (' + msg.name + ')'} • ${msg.time}
                </div>
                <div style="max-width: 80%; padding: 10px 14px; border-radius: 14px; font-size: 13.5px; line-height: 1.5; ${
                    isMe 
                    ? 'background: #3b82f6; color: #fff; border-bottom-right-radius: 4px;' 
                    : 'background: #334155; color: #f8fafc; border-bottom-left-radius: 4px;'
                }">
                    ${msg.text.replace(/\n/g, '<br>')}
                </div>
            `;
            chatBox.appendChild(msgDiv);
        });
    }
    
    if (isAtBottom) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

function fetchChatMessages() {
    if (currentChatOrderId <= 0) return;
    
    const formData = new FormData();
    formData.append('action', 'fetch');
    formData.append('order_id', currentChatOrderId);
    
    fetch('shop_ai.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            renderChatMessages(data.messages);
        }
    })
    .catch(err => console.error(err));
}

function openChatModal(orderData) {
    currentChatOrderId = orderData.id;
    document.getElementById('chatOrderId').value = orderData.id;
    document.getElementById('chatOrderTitle').textContent = orderData.title;
    
    renderChatMessages(orderData.messages);
    
    document.getElementById('chatModal').classList.add('active');
    setTimeout(() => {
        const chatBox = document.getElementById('chatMessagesBox');
        chatBox.scrollTop = chatBox.scrollHeight;
        document.getElementById('chatInputMessage').focus();
    }, 100);
    
    if (chatPollInterval) clearInterval(chatPollInterval);
    chatPollInterval = setInterval(fetchChatMessages, 3000);
}

function closeChatModal() {
    document.getElementById('chatModal').classList.remove('active');
    if (chatPollInterval) clearInterval(chatPollInterval);
    currentChatOrderId = 0;
}

<?php if ($boughtPending || (isset($_GET['tab']) && $_GET['tab'] === 'my')): ?>
document.addEventListener('DOMContentLoaded', function() {
    switchTab('my');
});
<?php endif; ?>
</script>

<!-- MODAL CHAT -->
<div class="ai-modal-backdrop" id="chatModal">
    <div class="ai-modal" style="display: flex; flex-direction: column; max-height: 90vh;">
        <div class="ai-modal-header" style="padding-bottom: 15px; border-bottom: 1px solid #334155;">
            <div>
                <h3 style="margin-bottom: 4px;"><i class="fa-solid fa-comments" style="color:#38bdf8;"></i> Chat với Giảng viên</h3>
                <div style="font-size: 13px; color: #94a3b8; font-weight: normal;" id="chatOrderTitle"></div>
            </div>
            <button class="ai-modal-close" onclick="closeChatModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div id="chatMessagesBox" style="flex: 1; overflow-y: auto; padding: 16px 0; min-height: 300px; display: flex; flex-direction: column;">
            <!-- Messages go here -->
        </div>

        <form id="chatForm" style="margin-top: 10px; border-top: 1px solid #334155; padding-top: 16px; display: flex; gap: 10px;" action="javascript:void(0);">
            <input type="hidden" name="order_id" id="chatOrderId">
            <input type="text" name="message" id="chatInputMessage" style="flex: 1; background: #0f172a; border: 1px solid #334155; border-radius: 20px; padding: 10px 16px; color: #fff; outline: none; font-size: 14px;" placeholder="Nhập tin nhắn..." required autocomplete="off">
            <button type="submit" style="background: #3b82f6; border: none; color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<!-- MODAL XEM NOTE BÀN GIAO TÀI KHOẢN -->
<div class="ai-modal-backdrop" id="viewNoteModal">
    <div class="ai-modal" style="max-width: 540px; border-color: #0284c7;">
        <div class="ai-modal-header" style="border-color: rgba(2, 132, 199, 0.3);">
            <h3 style="color: #38bdf8;"><i class="fa-solid fa-envelope-open-text"></i> Note Bàn Giao Tài Khoản</h3>
            <button class="ai-modal-close" onclick="closeNoteModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div style="margin-bottom: 16px;">
            <div style="font-size: 13px; color: #94a3b8; margin-bottom: 4px;">Đơn hàng: <strong id="noteOrderCode" style="color: #fff; font-family: monospace;"></strong></div>
            <div style="font-size: 16px; font-weight: 800; color: #f8fafc;" id="noteOrderTitle"></div>
        </div>

        <div style="background: rgba(14, 165, 233, 0.08); border: 1.5px solid #0284c7; border-radius: 14px; padding: 16px; margin-bottom: 18px;">
            <div style="font-size: 13.5px; font-weight: 800; color: #38bdf8; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                <span><i class="fa-solid fa-key"></i> Thông tin tài khoản & Mật khẩu:</span>
                <button class="copy-btn" onclick="copyNoteContent()" style="background: #0284c7; color: #fff; padding: 6px 14px; border-radius: 6px; font-weight: 700; cursor: pointer; border: none; font-size: 12.5px;"><i class="fa-solid fa-copy"></i> Sao chép tất cả</button>
            </div>
            <div id="noteContentBox" style="background: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 14px; font-family: 'Consolas', 'Courier New', monospace; font-size: 14px; color: #38bdf8; white-space: pre-wrap; word-break: break-word; line-height: 1.6; max-height: 250px; overflow-y: auto; font-weight: 600;"></div>
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="button" class="divine-btn-buy" style="background: #0284c7; padding: 11px 24px; font-size: 14px; border-radius: 10px; font-weight: 800;" onclick="closeNoteModal()">
                <i class="fa-solid fa-check"></i> Đã nhận được tài khoản
            </button>
        </div>
    </div>
</div>

<script>
function openNoteModalById(id) {
    const rawEl = document.getElementById('note_raw_' + id);
    const codeEl = document.getElementById('note_code_' + id);
    const titleEl = document.getElementById('note_title_' + id);
    
    document.getElementById('noteOrderCode').innerText = codeEl ? codeEl.innerText : ('#MMO-' + id);
    document.getElementById('noteOrderTitle').innerText = titleEl ? titleEl.innerText : 'Sản phẩm MMO';
    document.getElementById('noteContentBox').innerText = rawEl ? rawEl.innerText : '(Chưa có nội dung note)';
    
    const modal = document.getElementById('viewNoteModal');
    if (modal) {
        modal.classList.add('active');
    }
}

function openNoteModal(data) {
    if (typeof data === 'number' || typeof data === 'string') {
        openNoteModalById(data);
        return;
    }
    document.getElementById('noteOrderCode').innerText = data.order_code || '';
    document.getElementById('noteOrderTitle').innerText = data.title || '';
    document.getElementById('noteContentBox').innerText = data.account_info || '';
    const modal = document.getElementById('viewNoteModal');
    if (modal) {
        modal.classList.add('active');
    }
}

function closeNoteModal() {
    const modal = document.getElementById('viewNoteModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function copyNoteContent() {
    const text = document.getElementById('noteContentBox').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('🎉 Đã sao chép thông tin tài khoản thành công!');
    }).catch(() => {
        alert('Đã sao chép: ' + text);
    });
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chatForm');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('chatInputMessage');
            const msg = input.value.trim();
            if (!msg) return;
            
            input.value = '';
            
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('order_id', currentChatOrderId);
            formData.append('message', msg);
            
            fetch('shop_ai.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => {
                if (!res.ok) throw new Error('Mã phản hồi HTTP: ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    renderChatMessages(data.messages);
                    const chatBox = document.getElementById('chatMessagesBox');
                    chatBox.scrollTop = chatBox.scrollHeight;
                } else {
                    alert('Lỗi: ' + (data.error || 'Không thể gửi tin nhắn'));
                }
            })
            .catch(err => {
                console.error('Lỗi chat:', err);
                alert('Lỗi khi gửi tin nhắn: ' + (err.message || 'Lỗi kết nối'));
            });
        });
    }
});
</script>

</div><!-- close main-content from nav -->
</body>
</html>
