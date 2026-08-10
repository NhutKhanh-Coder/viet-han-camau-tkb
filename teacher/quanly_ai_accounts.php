<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
requireTeacher();

$db = getDB();

// Tự động khởi tạo và nâng cấp các bảng DB
try {
    $db->query("
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

    $db->query("
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


    $db->query("
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

    @$db->query("ALTER TABLE `ai_account_orders` ADD COLUMN `chat_messages` TEXT DEFAULT NULL AFTER `status`");
} catch (Throwable $e) {}

$teacher_uid = $_SESSION['user_id'] ?? 0;
$teacher_name = $_SESSION['ho_ten'] ?? 'Giảng viên';

$msg = $_GET['msg'] ?? '';
$error = $_GET['err'] ?? '';

// Xử lý XÁC NHẬN ĐÃ NHẬN TIỀN VÀ BÀN GIAO TÀI KHOẢN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_order') {
    $ord_id = (int)($_POST['order_id'] ?? 0);
    $new_account_info = $db->real_escape_string(trim($_POST['account_info'] ?? ''));
    $chat_message = trim($_POST['chat_message'] ?? '');
    
    if ($ord_id > 0) {
        $db->query("UPDATE ai_account_orders SET status = 'completed', account_info = '$new_account_info' WHERE id = $ord_id");
        
        if (!empty($chat_message)) {
            $ordRow = $db->query("SELECT chat_messages FROM ai_account_orders WHERE id = $ord_id")->fetch_assoc();
            if ($ordRow) {
                $chatHistory = json_decode($ordRow['chat_messages'] ?? '[]', true) ?: [];
                $chatHistory[] = [
                    'sender' => 'teacher',
                    'name' => $teacher_name,
                    'text' => $chat_message,
                    'time' => date('Y-m-d H:i:s')
                ];
                $newChatJson = $db->real_escape_string(json_encode($chatHistory, JSON_UNESCAPED_UNICODE));
                $db->query("UPDATE ai_account_orders SET chat_messages = '$newChatJson' WHERE id = $ord_id");
            }
        }
        
        header("Location: quanly_ai_accounts.php?msg=" . urlencode("Đã phê duyệt và bàn giao tài khoản cho đơn hàng #MMO-$ord_id thành công!"));
        exit;
    }
}


// Xử lý HỦY ĐƠN HÀNG
if (isset($_GET['action']) && $_GET['action'] === 'cancel_order' && isset($_GET['id'])) {
    $ord_id = (int)$_GET['id'];
    $ord = $db->query("SELECT store_id, quantity, status FROM ai_account_orders WHERE id = $ord_id")->fetch_assoc();
    if ($ord && $ord['status'] !== 'cancelled') {
        $store_id = (int)$ord['store_id'];
        $qty = (int)$ord['quantity'];
        $db->query("UPDATE ai_accounts_store SET stock = stock + $qty WHERE id = $store_id");
        $db->query("UPDATE ai_account_orders SET status = 'cancelled' WHERE id = $ord_id");
        header("Location: quanly_ai_accounts.php?msg=" . urlencode("Đã hủy đơn hàng #MMO-$ord_id và hoàn trả $qty sản phẩm vào kho hàng!"));
        exit;
    }
}

// Xử lý XÓA ĐƠN HÀNG
if (isset($_GET['action']) && $_GET['action'] === 'delete_order' && isset($_GET['id'])) {
    $ord_id = (int)$_GET['id'];
    $db->query("DELETE FROM ai_account_orders WHERE id = $ord_id");
    header("Location: quanly_ai_accounts.php?msg=" . urlencode("Đã xóa vĩnh viễn đơn hàng #MMO-$ord_id thành công!"));
    exit;
}

// Xử lý GỬI TIN NHẮN (CHAT)
// Xử lý GỬI TIN NHẮN (CHAT) TRONG ĐƠN HÀNG (HOẶC AJAX GIẢNG VIÊN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['fetch_chat_ajax', 'send_chat_ajax', 'fetch', 'send', 'send_chat'])) {
    $chat_order_id = (int)($_POST['order_id'] ?? 0);
    $chat_msg = trim($_POST['message'] ?? '');
    $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || in_array($_POST['action'], ['fetch_chat_ajax', 'send_chat_ajax', 'fetch', 'send']);

    if ($chat_order_id > 0) {
        $checkOrdRes = $db->query("SELECT * FROM ai_account_orders WHERE id = $chat_order_id");
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
                    'sender' => 'teacher',
                    'name' => $teacher_name,
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
                    header("Location: quanly_ai_accounts.php?msg=" . urlencode("Đã phản hồi tin nhắn thành công!"));
                    exit;
                }
            } elseif ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Tin nhắn không được trống']);
                exit;
            }
        } else if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Đơn hàng không tồn tại']);
            exit;
        } else {
            $error = "Đơn hàng không tồn tại để chat.";
        }
    }
}

// Xử lý XÓA MÃ GIẢM GIÁ
if (isset($_GET['action']) && $_GET['action'] === 'delete_coupon' && isset($_GET['id'])) {
    $cpn_id = (int)$_GET['id'];
    $db->query("DELETE FROM mmo_coupons WHERE id = $cpn_id");
    header("Location: quanly_ai_accounts.php?msg=" . urlencode("Đã xóa mã giảm giá thành công!"));
    exit;
}

// Xử lý THÊM MÃ GIẢM GIÁ MỚI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_coupon') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discount_percent = (int)($_POST['discount_percent'] ?? 10);
    $max_uses = (int)($_POST['max_uses'] ?? 100);

    if (!empty($code) && $discount_percent > 0 && $discount_percent <= 100) {
        $stmt = $db->prepare("INSERT INTO mmo_coupons (code, discount_percent, max_uses, status) VALUES (?, ?, ?, 'active') ON DUPLICATE KEY UPDATE discount_percent = VALUES(discount_percent), max_uses = VALUES(max_uses)");
        $stmt->bind_param("sii", $code, $discount_percent, $max_uses);
        if ($stmt->execute()) {
            header("Location: quanly_ai_accounts.php?msg=" . urlencode("Đã thêm mã giảm giá $code thành công!"));
            exit;
        } else {
            $error = "Lỗi tạo mã giảm giá: " . $db->error;
        }
    } else {
        $error = "Vui lòng nhập Mã giảm giá và Phần trăm giảm hợp lệ (1-100%)!";
    }
}

// Xử lý SỬA MÃ GIẢM GIÁ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_coupon') {
    $cpn_id = (int)($_POST['cpn_id'] ?? 0);
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discount_percent = (int)($_POST['discount_percent'] ?? 10);
    $max_uses = (int)($_POST['max_uses'] ?? 100);
    $status = trim($_POST['status'] ?? 'active');

    if ($cpn_id > 0 && !empty($code) && $discount_percent > 0 && $discount_percent <= 100) {
        $stmt = $db->prepare("UPDATE mmo_coupons SET code = ?, discount_percent = ?, max_uses = ?, status = ? WHERE id = ?");
        $stmt->bind_param("siisi", $code, $discount_percent, $max_uses, $status, $cpn_id);
        if ($stmt->execute()) {
            header("Location: quanly_ai_accounts.php?msg=" . urlencode("Đã cập nhật chỉnh sửa mã giảm giá $code thành công!"));
            exit;
        } else {
            $error = "Lỗi cập nhật mã giảm giá: " . $db->error;
        }
    }
}

// Hàm hỗ trợ upload file ảnh từ máy tính
function handleBannerUpload() {
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/mmo_banners/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $filename = 'mmo_' . time() . '_' . sprintf('%04d', rand(1000, 9999)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $filename)) {
                return '/tkb/uploads/mmo_banners/' . $filename;
            }
        }
    }
    return null;
}

// Xử lý THÊM MỚI SẢN PHẨM MMO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_mmo_product') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'AI');
    $ai_type = trim($_POST['ai_type'] ?? 'Tài khoản');
    $price = (int)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 1);
    $account_info = trim($_POST['account_info'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $bank_info = trim($_POST['bank_info'] ?? 'MBBank - 0392826609 - LE NHUT KHANH');
    $raw_variants = trim($_POST['variants_input'] ?? '');

    $uploadedUrl = handleBannerUpload();
    $image_url = $uploadedUrl ?: trim($_POST['image_url'] ?? '');

    $variants_arr = [];
    if (!empty($raw_variants)) {
        $lines = explode("\n", $raw_variants);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = explode('|', $line);
            $vName = trim($parts[0]);
            $vPrice = isset($parts[1]) ? (int)preg_replace('/[^0-9]/', '', $parts[1]) : $price;
            if ($vName) {
                $variants_arr[] = [
                    'name' => $vName,
                    'price' => $vPrice > 0 ? $vPrice : $price
                ];
            }
        }
    }
    $variants_json = !empty($variants_arr) ? json_encode($variants_arr, JSON_UNESCAPED_UNICODE) : null;

    if (empty($title) || empty($account_info) || $price <= 0) {
        $error = "Vui lòng nhập đầy đủ Tiêu đề, Giá bán và Thông tin bàn giao!";
    } else {
        $stmt = $db->prepare("INSERT INTO ai_accounts_store (title, category, ai_type, image_url, price, variants, stock, account_info, description, bank_info, teacher_id, teacher_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssisississ", $title, $category, $ai_type, $image_url, $price, $variants_json, $stock, $account_info, $description, $bank_info, $teacher_uid, $teacher_name);
        if ($stmt->execute()) {
            header("Location: quanly_ai_accounts.php?msg=" . urlencode("Đã đăng bán sản phẩm MMO thành công!"));
            exit;
        } else {
            $error = "Lỗi lưu DB: " . $db->error;
        }
    }
}

// Xử lý CẬP NHẬT SẢN PHẨM MMO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_mmo_product') {
    $edit_id = (int)($_POST['edit_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'AI');
    $ai_type = trim($_POST['ai_type'] ?? 'Tài khoản');
    $price = (int)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 1);
    $account_info = trim($_POST['account_info'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $bank_info = trim($_POST['bank_info'] ?? 'MBBank - 0392826609 - LE NHUT KHANH');
    $raw_variants = trim($_POST['variants_input'] ?? '');

    $uploadedUrl = handleBannerUpload();
    $image_url = $uploadedUrl ?: trim($_POST['existing_image_url'] ?? '');

    $variants_arr = [];
    if (!empty($raw_variants)) {
        $lines = explode("\n", $raw_variants);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = explode('|', $line);
            $vName = trim($parts[0]);
            $vPrice = isset($parts[1]) ? (int)preg_replace('/[^0-9]/', '', $parts[1]) : $price;
            if ($vName) {
                $variants_arr[] = [
                    'name' => $vName,
                    'price' => $vPrice > 0 ? $vPrice : $price
                ];
            }
        }
    }
    $variants_json = !empty($variants_arr) ? json_encode($variants_arr, JSON_UNESCAPED_UNICODE) : null;

    if ($edit_id > 0 && !empty($title) && !empty($account_info) && $price > 0) {
        $stmt = $db->prepare("UPDATE ai_accounts_store SET title = ?, category = ?, ai_type = ?, image_url = ?, price = ?, variants = ?, stock = ?, account_info = ?, description = ?, bank_info = ? WHERE id = ?");
        $stmt->bind_param("ssssisisssi", $title, $category, $ai_type, $image_url, $price, $variants_json, $stock, $account_info, $description, $bank_info, $edit_id);
        if ($stmt->execute()) {
            header("Location: quanly_ai_accounts.php?msg=" . urlencode("Đã cập nhật chỉnh sửa sản phẩm MMO thành công!"));
            exit;
        } else {
            $error = "Lỗi cập nhật DB: " . $db->error;
        }
    }
}

// Lấy danh sách sản phẩm MMO
$mmoProducts = $db->query("SELECT * FROM ai_accounts_store ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách đơn hàng
$ordersList = $db->query("SELECT * FROM ai_account_orders ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách mã giảm giá
$couponsList = $db->query("SELECT * FROM mmo_coupons ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/teacher_nav.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Chợ MMO, Sửa Mã Giảm Giá & Phê Duyệt — Giảng Viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .mmo-container { max-width: 1100px; margin: 0 auto; }
        .mmo-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .mmo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        .mmo-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mmo-title i { color: #ef4444; }
        .mmo-form-group { margin-bottom: 16px; }
        .mmo-label {
            display: block;
            font-size: 13.5px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }
        .mmo-sublabel {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .mmo-input, .mmo-select, .mmo-textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .mmo-textarea { min-height: 80px; resize: vertical; }
        .mmo-input:focus, .mmo-select:focus, .mmo-textarea:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        .mmo-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }

        .mmo-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .mmo-btn-primary {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        }
        .mmo-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(239, 68, 68, 0.35);
        }
        .mmo-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .mmo-table th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-weight: 700;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }
        .mmo-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }
        .mmo-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .badge-ai { background: #ede9fe; color: #6d28d9; }
        .badge-social { background: #dbeafe; color: #1d4ed8; }
        .badge-vps { background: #fef3c7; color: #b45309; }
        .badge-tool { background: #fee2e2; color: #dc2626; }
        .badge-gift { background: #d1fae5; color: #059669; }
        .badge-pending { background: #fef3c7; color: #b45309; border: 1px solid #f59e0b; }
        .badge-completed { background: #d1fae5; color: #047857; border: 1px solid #10b981; }
        .badge-cancelled { background: #fee2e2; color: #b91c1c; border: 1px solid #ef4444; }

        .mmo-alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mmo-alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .mmo-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

        .mmo-modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999; opacity: 0; pointer-events: none; transition: opacity 0.25s;
        }
        .mmo-modal-backdrop.active { opacity: 1; pointer-events: auto; }
        .mmo-modal-box {
            background: #ffffff;
            border-radius: 18px;
            width: 90%; max-width: 680px;
            padding: 26px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-height: 90vh; overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="mmo-container">
    
    <!-- Đăng bán sản phẩm MMO mới -->
    <div class="mmo-card">
        <div class="mmo-header">
            <div class="mmo-title">
                <i class="fa-solid fa-store"></i>
                <span>Đăng Bán Sản Phẩm MMO & Upload File Ảnh Banner</span>
            </div>
            <span style="font-size: 13px; color: #64748b;">
                <i class="fa-solid fa-upload"></i> Chọn file ảnh trực tiếp từ máy tính (PNG, JPG, WEBP)
            </span>
        </div>

        <?php if ($msg): ?>
            <div class="mmo-alert mmo-alert-success">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mmo-alert mmo-alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_mmo_product">

            <div class="mmo-grid-3">
                <div class="mmo-form-group">
                    <label class="mmo-label">Tên sản phẩm chính <span style="color:red;">*</span></label>
                    <input type="text" name="title" class="mmo-input" placeholder="Ví dụ: Gemini Pro 3 Tháng, ChatGPT Plus..." required>
                </div>

                <div class="mmo-form-group">
                    <label class="mmo-label">Danh mục MMO</label>
                    <select name="category" class="mmo-select">
                        <option value="AI">🤖 Tài Khoản AI & Premium (ChatGPT, Gemini, Claude, Cursor...)</option>
                        <option value="SOCIAL">📲 Acc Mạng Xã Hội (FB Via, TikTok Beta, Youtube...)</option>
                        <option value="VPS_PROXY">💻 VPS & Proxy MMO (VPS Win/Linux, Socks5...)</option>
                        <option value="SOFTWARE">⚙️ Tool / Code Script MMO (Auto Reg, Crawl...)</option>
                        <option value="GIFTCARD">🎁 Thẻ Giftcard & Premium (Netflix, Spotify, Discord...)</option>
                    </select>
                </div>

                <div class="mmo-form-group">
                    <label class="mmo-label">Thương hiệu / Loại chi tiết</label>
                    <input type="text" name="ai_type" class="mmo-input" placeholder="Ví dụ: Gemini, Via FB, Proxy IPv4...">
                </div>
            </div>

            <div class="mmo-grid-3">
                <div class="mmo-form-group">
                    <label class="mmo-label">Giá chuẩn (VNĐ) <span style="color:red;">*</span></label>
                    <input type="number" name="price" class="mmo-input" placeholder="Ví dụ: 180000..." value="180000" min="1000" required>
                </div>

                <div class="mmo-form-group">
                    <label class="mmo-label">Số lượng kho hàng</label>
                    <input type="number" name="stock" class="mmo-input" value="1333" min="1" required>
                </div>

                <div class="mmo-form-group">
                    <label class="mmo-label"><i class="fa-solid fa-cloud-arrow-up" style="color:#ef4444;"></i> Upload Ảnh Banner (Từ máy tính)</label>
                    <input type="file" name="image_file" accept="image/*" class="mmo-input" style="padding:7px 10px;">
                </div>
            </div>

            <div class="mmo-form-group">
                <label class="mmo-label">Tài khoản Ngân hàng nhận tiền (VietQR MB Bank)</label>
                <input type="text" name="bank_info" class="mmo-input" value="MBBank - 0392826609 - LE NHUT KHANH">
            </div>

            <div class="mmo-form-group" style="background:#f8fafc; border:2px dashed #ef4444; padding:16px; border-radius:12px;">
                <label class="mmo-label" style="color:#ef4444; font-size:14.5px;">
                    <i class="fa-solid fa-list-check"></i> ĐIỀN CÁC GÓI TÙY CHỌN BÁN (DÙNG CHO PHẦN "CHỌN GÓI" POPUP SINH VIÊN MUA)
                </label>
                <div class="mmo-sublabel">
                    📌 <strong>Hướng dẫn điền:</strong> Mỗi dòng nhập 1 gói theo định dạng <code>Tên gói | Giá VNĐ</code>.
                </div>
                <textarea name="variants_input" class="mmo-textarea" style="min-height:90px; font-family:monospace; background:#fff;" placeholder="Ví dụ nhập:
Gemini 3 Tháng | 180000
Gemini 1 Năm Full BH | 350000"></textarea>
            </div>

            <div class="mmo-form-group">
                <label class="mmo-label">Thông tin bàn giao (Bàn giao tự động khi phê duyệt tiền) <span style="color:red;">*</span></label>
                <textarea name="account_info" class="mmo-textarea" placeholder="Nhập Email | Password | Cookie | IP VPS | Key License | Link Download Code..." required></textarea>
            </div>

            <div class="mmo-form-group">
                <label class="mmo-label">Mô tả tính năng, bảo hành & Hướng dẫn sử dụng</label>
                <textarea name="description" class="mmo-textarea" placeholder="Mô tả chi tiết tính năng sản phẩm, thời hạn bảo hành, hướng dẫn đăng nhập..."></textarea>
            </div>

            <button type="submit" class="mmo-btn mmo-btn-primary">
                <i class="fa-solid fa-plus"></i> Đăng Bán Sản Phẩm MMO Ngay
            </button>
        </form>
    </div>

    <!-- KHU VỰC QUẢN LÝ MÃ GIẢM GIÁ (CÓ CHỨC NĂNG SỬA & XÓA) -->
    <div class="mmo-card">
        <div class="mmo-header">
            <div class="mmo-title">
                <i class="fa-solid fa-ticket" style="color:#ef4444;"></i>
                <span>Quản Lý Mã Giảm Giá (Coupons) Chợ MMO</span>
            </div>
            <span class="mmo-badge badge-gift">Tổng số: <?= count($couponsList) ?> Mã giảm giá</span>
        </div>

        <!-- Form tạo mã giảm giá mới -->
        <form action="" method="POST" style="background:#f8fafc; border:1px solid #e2e8f0; padding:16px; border-radius:12px; margin-bottom:20px;">
            <input type="hidden" name="action" value="add_coupon">
            <div class="mmo-grid-3">
                <div class="mmo-form-group" style="margin:0;">
                    <label class="mmo-label">Mã giảm giá (Code) <span style="color:red;">*</span></label>
                    <input type="text" name="code" class="mmo-input" style="text-transform:uppercase; font-weight:800; color:#ef4444;" placeholder="VD: SINHVIEN10, VKC20..." required>
                </div>
                <div class="mmo-form-group" style="margin:0;">
                    <label class="mmo-label">Phần trăm giảm (%) <span style="color:red;">*</span></label>
                    <input type="number" name="discount_percent" class="mmo-input" placeholder="VD: 10, 20, 50..." min="1" max="100" value="10" required>
                </div>
                <div class="mmo-form-group" style="margin:0;">
                    <label class="mmo-label">Số lượt dùng tối đa</label>
                    <input type="number" name="max_uses" class="mmo-input" value="500" min="1">
                </div>
            </div>
            <div style="margin-top:14px; text-align:right;">
                <button type="submit" class="mmo-btn mmo-btn-primary" style="padding:9px 20px; font-size:13px;">
                    <i class="fa-solid fa-plus"></i> Tạo Mã Giảm Giá Ngay
                </button>
            </div>
        </form>

        <!-- Danh sách mã giảm giá -->
        <?php if (empty($couponsList)): ?>
            <p style="text-align:center; color:#94a3b8; font-size:13.5px; margin:0;">Chưa có mã giảm giá nào.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="mmo-table">
                    <thead>
                        <tr>
                            <th>Mã Giảm Giá</th>
                            <th>Mức Giảm Giá</th>
                            <th>Lượt Đã Dùng / Tối Đa</th>
                            <th>Trạng Thái</th>
                            <th style="text-align: right;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($couponsList as $cpn): 
                            $stt = $cpn['status'] ?? 'active';
                        ?>
                            <tr>
                                <td>
                                    <code style="background:#fee2e2; color:#dc2626; padding:6px 12px; border-radius:8px; font-weight:900; font-size:14px;">
                                        <?= htmlspecialchars($cpn['code']) ?>
                                    </code>
                                </td>
                                <td><strong style="color:#10b981; font-size:15px;">Giảm <?= $cpn['discount_percent'] ?>%</strong></td>
                                <td><?= $cpn['used_count'] ?> / <?= $cpn['max_uses'] ?> lượt</td>
                                <td>
                                    <?php if ($stt === 'active'): ?>
                                        <span class="mmo-badge badge-completed"><i class="fa-solid fa-circle-check"></i> Hoạt động</span>
                                    <?php else: ?>
                                        <span class="mmo-badge badge-cancelled"><i class="fa-solid fa-pause"></i> Tạm dừng</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" onclick="openEditCouponModal(<?= htmlspecialchars(json_encode($cpn), ENT_QUOTES, 'UTF-8') ?>)" class="mmo-btn" style="padding:5px 10px; font-size:12px; background:#e0f2fe; color:#0284c7; margin-right:4px;">
                                        <i class="fa-solid fa-pen-to-square"></i> Sửa
                                    </button>
                                    <a href="?action=delete_coupon&id=<?= $cpn['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa mã giảm giá này?');" class="mmo-btn" style="padding:5px 10px; font-size:12px; background:#fee2e2; color:#ef4444;">
                                        <i class="fa-solid fa-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Danh sách lịch sử đơn hàng cần phê duyệt -->
    <div class="mmo-card">
        <div class="mmo-header">
            <div class="mmo-title">
                <i class="fa-solid fa-clipboard-check"></i>
                <span>Phê Duyệt Đơn Hàng Thanh Toán Sinh Viên</span>
            </div>
            <?php 
                $pendingCount = count(array_filter($ordersList, fn($o) => ($o['status'] ?? 'pending') === 'pending'));
            ?>
            <span class="mmo-badge badge-pending" style="font-size:13px; padding:6px 16px;">
                <i class="fa-solid fa-clock"></i> Đang chờ duyệt: <?= $pendingCount ?> đơn
            </span>
        </div>

        <?php if (empty($ordersList)): ?>
            <div style="text-align: center; padding: 30px; color: #94a3b8;">
                <p style="margin: 0; font-size: 14px;">Chưa có đơn hàng mua sản phẩm MMO nào từ sinh viên.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="mmo-table">
                    <thead>
                        <tr>
                            <th>Mã ĐH</th>
                            <th>Sinh viên mua</th>
                            <th>Sản phẩm & Gói đã chọn</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Note gửi tài khoản cho SV</th>
                            <th style="text-align: right;">Hành động Phê duyệt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordersList as $ord): 
                            $status = $ord['status'] ?? 'pending';
                        ?>
                            <tr style="<?= $status === 'pending' ? 'background: #fffbeb;' : '' ?>">
                                <td>
                                    <span style="background: #f1f5f9; color: #dc2626; border: 1px solid #fca5a5; padding: 4px 8px; border-radius: 6px; font-weight: 800; font-family: monospace; font-size: 13px;">
                                        #MMO-<?= sprintf('%05d', $ord['id']) ?>
                                    </span>
                                </td>
                                <td><span style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($ord['student_name']) ?></span></td>
                                <td><strong><?= htmlspecialchars($ord['account_title']) ?></strong> (x<?= $ord['quantity'] ?? 1 ?>)</td>
                                <td><strong style="color: #ef4444; font-size: 15px;"><?= number_format($ord['price']) ?>đ</strong></td>
                                <td>
                                    <?php if ($status === 'pending'): ?>
                                        <span class="mmo-badge badge-pending"><i class="fa-solid fa-spinner fa-spin"></i> Chờ nhận tiền</span>
                                    <?php elseif ($status === 'completed'): ?>
                                        <span class="mmo-badge badge-completed"><i class="fa-solid fa-check"></i> Đã duyệt & Giao acc</span>
                                    <?php else: ?>
                                        <span class="mmo-badge badge-cancelled"><i class="fa-solid fa-xmark"></i> Đã hủy</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <span style="background: #f1f5f9; padding: 5px 8px; border-radius: 6px; font-size: 12px; max-width: 160px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; border: 1px solid #cbd5e1; color: #0f172a; font-family: monospace; font-weight: 600;">
                                            <?= htmlspecialchars($ord['account_info'] ?: '(Chưa có note)') ?>
                                        </span>
                                        <button type="button" class="mmo-btn" style="padding: 5px 8px; font-size: 11px; background: #6366f1; color: #fff; border: none; cursor: pointer; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; font-weight: 700;" title="Nhập Note gửi tài khoản cho SV" onclick='openApproveModal(<?= htmlspecialchars(json_encode([
                                            "id" => $ord["id"],
                                            "student" => $ord["student_name"],
                                            "price" => $ord["price"],
                                            "account_info" => $ord["account_info"]
                                        ]), ENT_QUOTES, "UTF-8") ?>)'>
                                            <i class="fa-solid fa-note-sticky"></i> Note gửi SV
                                        </button>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($status === 'pending'): ?>
                                        <button class="mmo-btn" style="padding: 7px 14px; font-size: 12px; background: #10b981; color: #fff; border: none; cursor: pointer;" onclick='openApproveModal(<?= htmlspecialchars(json_encode([
                                            "id" => $ord["id"],
                                            "student" => $ord["student_name"],
                                            "price" => $ord["price"],
                                            "account_info" => $ord["account_info"]
                                        ]), ENT_QUOTES, "UTF-8") ?>)'>
                                            <i class="fa-solid fa-check-double"></i> Duyệt & Bàn giao
                                        </button>
                                        <a href="?action=cancel_order&id=<?= $ord['id'] ?>" class="mmo-btn" style="padding: 7px 10px; font-size: 12px; background: #f59e0b; color: #fff;" onclick="return confirm('Hủy đơn hàng này và hoàn kho?');">
                                            <i class="fa-solid fa-xmark"></i> Hủy
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #94a3b8; margin-right: 10px;"><?= date('d/m H:i', strtotime($ord['created_at'])) ?></span>
                                        <?php if ($status === 'completed'): ?>
                                        <button class="mmo-btn" style="padding: 7px 10px; font-size: 12px; background: #64748b; color: #fff; border: none; cursor: pointer; margin-right: 4px;" onclick='openApproveModal(<?= htmlspecialchars(json_encode([
                                            "id" => $ord["id"],
                                            "student" => $ord["student_name"],
                                            "price" => $ord["price"],
                                            "account_info" => $ord["account_info"]
                                        ]), ENT_QUOTES, "UTF-8") ?>)'>
                                            <i class="fa-solid fa-pen"></i> Sửa bàn giao
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <button class="mmo-btn" style="padding: 7px 10px; font-size: 12px; background: #3b82f6; color: #fff; margin-right: 4px;" onclick='openChatModal(<?= htmlspecialchars(json_encode([
                                        "id" => $ord["id"],
                                        "title" => $ord["account_title"],
                                        "student" => $ord["student_name"],
                                        "messages" => json_decode($ord["chat_messages"] ?? "[]", true)
                                    ]), ENT_QUOTES, "UTF-8") ?>)'>
                                        <i class="fa-solid fa-message"></i> Nhắn tin
                                    </button>
                                    
                                    <a href="?action=delete_order&id=<?= $ord['id'] ?>" class="mmo-btn" style="padding: 7px 10px; font-size: 12px; background: #ef4444; color: #fff;" onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn đơn hàng này khỏi hệ thống?');">
                                        <i class="fa-solid fa-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Danh sách sản phẩm MMO đang bán -->
    <div class="mmo-card">
        <div class="mmo-header">
            <div class="mmo-title">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>Danh Sách Sản Phẩm MMO Trong Kho</span>
            </div>
            <span class="mmo-badge badge-ai">Tổng số: <?= count($mmoProducts) ?> sản phẩm</span>
        </div>

        <?php if (empty($mmoProducts)): ?>
            <div style="text-align: center; padding: 30px; color: #94a3b8;">
                <p style="margin: 0; font-size: 14px;">Chưa có sản phẩm MMO nào được đăng bán.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="mmo-table">
                    <thead>
                        <tr>
                            <th>Ảnh Banner</th>
                            <th>Danh mục</th>
                            <th>Tên sản phẩm MMO</th>
                            <th>Giá bán</th>
                            <th>Kho</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mmoProducts as $prod): 
                            $badgeCls = 'badge-ai';
                            $cat = $prod['category'] ?? 'AI';
                            if ($cat === 'SOCIAL') $badgeCls = 'badge-social';
                            elseif ($cat === 'VPS_PROXY') $badgeCls = 'badge-vps';
                            elseif ($cat === 'SOFTWARE') $badgeCls = 'badge-tool';
                            elseif ($cat === 'GIFTCARD') $badgeCls = 'badge-gift';
                        ?>
                            <tr>
                                <td>
                                    <div style="width:54px; height:36px; border-radius:8px; overflow:hidden; background:#0f172a; border:1px solid #e2e8f0;">
                                        <img src="<?= htmlspecialchars($prod['image_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=200&auto=format&fit=crop') ?>" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                </td>
                                <td><span class="mmo-badge <?= $badgeCls ?>"><?= htmlspecialchars($cat) ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($prod['title']) ?></strong>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                        Loại: <?= htmlspecialchars($prod['ai_type'] ?: 'Tài khoản') ?>
                                    </div>
                                </td>
                                <td><strong style="color: #ef4444; font-size: 15px;"><?= number_format($prod['price']) ?>đ</strong></td>
                                <td>
                                    <span style="font-weight: 700; color: <?= $prod['stock'] > 0 ? '#059669' : '#dc2626' ?>;">
                                        <?= $prod['stock'] ?> còn
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($prod), ENT_QUOTES, 'UTF-8') ?>)" class="mmo-btn" style="padding: 6px 12px; font-size: 12px; background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; margin-right: 4px;">
                                        <i class="fa-solid fa-pen-to-square"></i> Sửa
                                    </button>
                                    <a href="?action=delete&id=<?= $prod['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm MMO này?');" class="mmo-btn" style="padding: 6px 12px; font-size: 12px; background: #fef2f2; color: #ef4444; border: 1px solid #fecaca;">
                                        <i class="fa-solid fa-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL SỬA SẢN PHẨM MMO -->
<div class="mmo-modal-backdrop" id="editModal">
    <div class="mmo-modal-box">
        <div class="mmo-header">
            <div class="mmo-title">
                <i class="fa-solid fa-pen-to-square" style="color:#0284c7;"></i>
                <span>Chỉnh Sửa Sản Phẩm MMO</span>
            </div>
            <button type="button" style="border:none; background:transparent; font-size:18px; cursor:pointer;" onclick="closeEditModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_mmo_product">
            <input type="hidden" name="edit_id" id="edit_id">
            <input type="hidden" name="existing_image_url" id="edit_existing_image_url">

            <div class="mmo-grid-3">
                <div class="mmo-form-group">
                    <label class="mmo-label">Tên sản phẩm chính <span style="color:red;">*</span></label>
                    <input type="text" name="title" id="edit_title" class="mmo-input" required>
                </div>

                <div class="mmo-form-group">
                    <label class="mmo-label">Danh mục MMO</label>
                    <select name="category" id="edit_category" class="mmo-select">
                        <option value="AI">🤖 Tài Khoản AI & Premium</option>
                        <option value="SOCIAL">📲 Acc Mạng Xã Hội</option>
                        <option value="VPS_PROXY">💻 VPS & Proxy MMO</option>
                        <option value="SOFTWARE">⚙️ Tool / Code Script MMO</option>
                        <option value="GIFTCARD">🎁 Thẻ Giftcard & Premium</option>
                    </select>
                </div>

                <div class="mmo-form-group">
                    <label class="mmo-label">Thương hiệu / Loại</label>
                    <input type="text" name="ai_type" id="edit_ai_type" class="mmo-input">
                </div>
            </div>

            <div class="mmo-grid-3">
                <div class="mmo-form-group">
                    <label class="mmo-label">Giá chuẩn (VNĐ) <span style="color:red;">*</span></label>
                    <input type="number" name="price" id="edit_price" class="mmo-input" required>
                </div>

                <div class="mmo-form-group">
                    <label class="mmo-label">Số lượng kho hàng</label>
                    <input type="number" name="stock" id="edit_stock" class="mmo-input" required>
                </div>

                <div class="mmo-form-group">
                    <label class="mmo-label"><i class="fa-solid fa-upload" style="color:#0284c7;"></i> Chọn File Ảnh Banner Mới</label>
                    <input type="file" name="image_file" accept="image/*" class="mmo-input" style="padding:7px 10px;">
                </div>
            </div>

            <div id="edit_img_preview_box" style="margin-bottom:14px; display:none; align-items:center; gap:12px; background:#f8fafc; padding:10px; border-radius:10px; border:1px solid #e2e8f0;">
                <span style="font-size:12.5px; font-weight:700; color:#475569;">Ảnh banner hiện tại:</span>
                <img id="edit_img_preview" src="" style="height:44px; border-radius:6px; object-fit:cover; border:1px solid #cbd5e1;">
            </div>

            <div class="mmo-form-group">
                <label class="mmo-label">Tài khoản Ngân hàng (VietQR MB Bank)</label>
                <input type="text" name="bank_info" id="edit_bank_info" class="mmo-input">
            </div>

            <div class="mmo-form-group" style="background:#f8fafc; border:2px dashed #0284c7; padding:16px; border-radius:12px;">
                <label class="mmo-label" style="color:#0284c7; font-size:14px;">
                    <i class="fa-solid fa-list-check"></i> CÁC GÓI TÙY CHỌN BÁN (Mỗi dòng 1 gói: Tên gói | Giá VNĐ)
                </label>
                <textarea name="variants_input" id="edit_variants_input" class="mmo-textarea" style="min-height:90px; font-family:monospace; background:#fff;"></textarea>
            </div>

            <div class="mmo-form-group">
                <label class="mmo-label">Thông tin bàn giao <span style="color:red;">*</span></label>
                <textarea name="account_info" id="edit_account_info" class="mmo-textarea" required></textarea>
            </div>

            <div class="mmo-form-group">
                <label class="mmo-label">Mô tả tính năng, bảo hành</label>
                <textarea name="description" id="edit_description" class="mmo-textarea"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">
                <button type="button" class="mmo-btn" style="background:#cbd5e1; color:#334155;" onclick="closeEditModal()">Hủy</button>
                <button type="submit" class="mmo-btn mmo-btn-primary" style="background:linear-gradient(135deg, #0284c7, #0369a1);">
                    <i class="fa-solid fa-floppy-disk"></i> Lưu Thay Đổi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SỬA MÃ GIẢM GIÁ (EDIT COUPON MODAL) -->
<div class="mmo-modal-backdrop" id="editCouponModal">
    <div class="mmo-modal-box" style="max-width:500px;">
        <div class="mmo-header">
            <div class="mmo-title">
                <i class="fa-solid fa-ticket" style="color:#0284c7;"></i>
                <span>Chỉnh Sửa Mã Giảm Giá</span>
            </div>
            <button type="button" style="border:none; background:transparent; font-size:18px; cursor:pointer;" onclick="closeEditCouponModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="" method="POST">
            <input type="hidden" name="action" value="edit_coupon">
            <input type="hidden" name="cpn_id" id="edit_cpn_id">

            <div class="mmo-form-group">
                <label class="mmo-label">Mã giảm giá (Code) <span style="color:red;">*</span></label>
                <input type="text" name="code" id="edit_cpn_code" class="mmo-input" style="text-transform:uppercase; font-weight:800; color:#ef4444;" required>
            </div>

            <div class="mmo-grid-2">
                <div class="mmo-form-group">
                    <label class="mmo-label">Phần trăm giảm (%) <span style="color:red;">*</span></label>
                    <input type="number" name="discount_percent" id="edit_cpn_discount" class="mmo-input" min="1" max="100" required>
                </div>
                <div class="mmo-form-group">
                    <label class="mmo-label">Số lượt dùng tối đa</label>
                    <input type="number" name="max_uses" id="edit_cpn_max_uses" class="mmo-input" min="1" required>
                </div>
            </div>

            <div class="mmo-form-group">
                <label class="mmo-label">Trạng thái mã</label>
                <select name="status" id="edit_cpn_status" class="mmo-select">
                    <option value="active">🟢 Cho phép hoạt động</option>
                    <option value="inactive">🔴 Tạm dừng mã</option>
                </select>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
                <button type="button" class="mmo-btn" style="background:#cbd5e1; color:#334155;" onclick="closeEditCouponModal()">Hủy</button>
                <button type="submit" class="mmo-btn mmo-btn-primary" style="background:linear-gradient(135deg, #0284c7, #0369a1);">
                    <i class="fa-solid fa-floppy-disk"></i> Lưu Thay Đổi Mã
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(prod) {
    document.getElementById('edit_id').value = prod.id;
    document.getElementById('edit_title').value = prod.title || '';
    document.getElementById('edit_category').value = prod.category || 'AI';
    document.getElementById('edit_ai_type').value = prod.ai_type || '';
    document.getElementById('edit_existing_image_url').value = prod.image_url || '';
    document.getElementById('edit_price').value = prod.price || 0;
    document.getElementById('edit_stock').value = prod.stock || 1;
    document.getElementById('edit_bank_info').value = prod.bank_info || 'MBBank - 0392826609 - LE NHUT KHANH';
    document.getElementById('edit_account_info').value = prod.account_info || '';
    document.getElementById('edit_description').value = prod.description || '';

    if (prod.image_url) {
        document.getElementById('edit_img_preview').src = prod.image_url;
        document.getElementById('edit_img_preview_box').style.display = 'flex';
    } else {
        document.getElementById('edit_img_preview_box').style.display = 'none';
    }

    let textLines = '';
    if (prod.variants) {
        try {
            const arr = JSON.parse(prod.variants);
            if (Array.isArray(arr)) {
                textLines = arr.map(v => `${v.name} | ${v.price}`).join('\n');
            }
        } catch(e){}
    }
    document.getElementById('edit_variants_input').value = textLines;

    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

function openEditCouponModal(cpn) {
    document.getElementById('edit_cpn_id').value = cpn.id;
    document.getElementById('edit_cpn_code').value = cpn.code;
    document.getElementById('edit_cpn_discount').value = cpn.discount_percent;
    document.getElementById('edit_cpn_max_uses').value = cpn.max_uses;
    document.getElementById('edit_cpn_status').value = cpn.status || 'active';
    document.getElementById('editCouponModal').classList.add('active');
}

function closeEditCouponModal() {
    document.getElementById('editCouponModal').classList.remove('active');
}

let chatPollInterval = null;
let currentChatOrderId = 0;
let currentChatStudentName = '';

function renderChatMessages(messages) {
    const chatBox = document.getElementById('chatMessagesBox');
    const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 50;
    
    chatBox.innerHTML = '';
    
    if (!messages || messages.length === 0) {
        chatBox.innerHTML = '<div style="text-align:center; padding: 20px; color: #94a3b8; font-size: 13px;">Chưa có tin nhắn nào. Bắt đầu nhắn tin với sinh viên!</div>';
    } else {
        messages.forEach(msg => {
            const isMe = msg.sender === 'teacher';
            const msgDiv = document.createElement('div');
            msgDiv.style.display = 'flex';
            msgDiv.style.flexDirection = 'column';
            msgDiv.style.alignItems = isMe ? 'flex-end' : 'flex-start';
            msgDiv.style.marginBottom = '12px';
            
            msgDiv.innerHTML = `
                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px; padding: 0 4px;">
                    ${isMe ? 'Bạn' : 'Sinh viên (' + msg.name + ')'} • ${msg.time}
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
    
    fetch('quanly_ai_accounts.php', {
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
    currentChatStudentName = orderData.student;
    
    document.getElementById('chatOrderId').value = orderData.id;
    document.getElementById('chatOrderTitle').textContent = orderData.title + ' (với ' + orderData.student + ')';
    
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

function openApproveModal(ord) {
    document.getElementById('approveOrderId').value = ord.id;
    document.getElementById('approveOrderTitle').innerText = 'Đơn hàng #MMO-' + String(ord.id).padStart(5, '0') + ' của ' + ord.student;
    document.getElementById('approvePriceText').innerText = new Intl.NumberFormat('vi-VN').format(ord.price) + 'đ';
    document.getElementById('approveAccountInfo').value = ord.account_info;
    document.getElementById('approveModal').classList.add('active');
}

function closeApproveModal() {
    document.getElementById('approveModal').classList.remove('active');
}
</script>

<!-- MODAL APPROVE & HANDOVER -->
<div class="mmo-modal-backdrop" id="approveModal">
    <div class="mmo-modal-box" style="max-width: 540px;">
        <div class="mmo-header">
            <div class="mmo-title">
                <i class="fa-solid fa-note-sticky" style="color:#6366f1;"></i>
                <span>Note Bàn Giao Tài Khoản Cho Sinh Viên</span>
            </div>
            <button type="button" style="border:none; background:transparent; font-size:18px; cursor:pointer;" onclick="closeApproveModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="" method="POST">
            <input type="hidden" name="action" value="approve_order">
            <input type="hidden" name="order_id" id="approveOrderId">
            
            <div style="background: #ecfdf5; border: 1px solid #10b981; padding: 12px; border-radius: 8px; margin-bottom: 16px; color: #065f46; font-size: 13.5px;">
                <i class="fa-solid fa-circle-check"></i> <strong id="approveOrderTitle"></strong> — Số tiền: <strong id="approvePriceText" style="color: #ef4444; font-size: 15px;"></strong>
            </div>

            <div class="mmo-form-group">
                <label class="mmo-label" style="font-weight: 800; color: #0f172a;">📝 Note thông tin tài khoản gửi cho sinh viên: <span style="color:red;">*</span></label>
                <textarea name="account_info" id="approveAccountInfo" class="mmo-textarea" required style="min-height: 110px; font-family: 'Consolas', monospace; font-size: 13.5px; border: 2px solid #6366f1;" placeholder="Nhập: Tài khoản | Mật khẩu | Cookie | Link kích hoạt | Hướng dẫn sử dụng cho sinh viên..."></textarea>
                <small style="color: #64748b; font-size: 12px; margin-top: 4px; display: block;">💡 Nội dung note này sẽ hiển thị trực tiếp trong mục <strong>'Note bàn giao tài khoản'</strong> của sinh viên kèm nút Copy.</small>
            </div>

            <div class="mmo-form-group">
                <label class="mmo-label" style="font-weight: 700;">💬 Gửi thêm tin nhắn chat thông báo (Tùy chọn):</label>
                <textarea name="chat_message" class="mmo-textarea" style="min-height: 60px;" placeholder="Ví dụ: Thầy vừa gửi tài khoản trong phần Note bàn giao, em kiểm tra và đăng nhập nhé!"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
                <button type="button" class="mmo-btn" style="background:#cbd5e1; color:#334155;" onclick="closeApproveModal()">Đóng</button>
                <button type="submit" class="mmo-btn mmo-btn-primary" style="background:linear-gradient(135deg, #6366f1, #4f46e5); font-weight: 800;">
                    <i class="fa-solid fa-paper-plane"></i> Lưu Note & Bàn Giao
                </button>
            </div>
        </form>
    </div>
</div>


<!-- MODAL CHAT -->
<div class="mmo-modal-backdrop" id="chatModal">
    <div class="mmo-modal-box" style="display: flex; flex-direction: column; max-height: 90vh;">
        <div class="mmo-header" style="margin-bottom: 0;">
            <div class="mmo-title">
                <i class="fa-solid fa-comments" style="color:#38bdf8;"></i>
                <div style="display:flex; flex-direction:column;">
                    <span>Chat với Sinh viên</span>
                    <span style="font-size: 12px; color: #64748b; font-weight: normal; margin-top: 2px;" id="chatOrderTitle"></span>
                </div>
            </div>
            <button type="button" style="border:none; background:transparent; font-size:18px; cursor:pointer;" onclick="closeChatModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div id="chatMessagesBox" style="flex: 1; overflow-y: auto; padding: 16px 0; min-height: 300px; display: flex; flex-direction: column;">
            <!-- Messages go here -->
        </div>

        <form id="chatForm" style="margin-top: 10px; border-top: 1px solid #e2e8f0; padding-top: 16px; display: flex; gap: 10px;" action="javascript:void(0);">
            <input type="hidden" name="order_id" id="chatOrderId">
            <input type="text" name="message" id="chatInputMessage" style="flex: 1; border: 1px solid #cbd5e1; border-radius: 20px; padding: 10px 16px; outline: none; font-size: 14px;" placeholder="Nhập tin nhắn..." required autocomplete="off">
            <button type="submit" style="background: #3b82f6; border: none; color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

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
            
            fetch('quanly_ai_accounts.php', {
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
</body>
</html>
