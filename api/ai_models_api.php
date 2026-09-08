<?php
/**
 * API Quản Lý Đóng / Mở Models AI Bot Chat Cho Sinh Viên
 * Hỗ trợ lưu cấu hình vào DB system_settings và cache file temp_runs/disabled_ai_models.json
 */
@ob_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/ai_models_list.php';
@ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

$cache_dir = __DIR__ . '/../temp_runs';
if (!is_dir($cache_dir)) @mkdir($cache_dir, 0777, true);
$cache_file = $cache_dir . '/disabled_ai_models.json';

// Danh sách tất cả 36 models mặc định hệ thống
if (!function_exists('getAllSystemModels')) {
function getAllSystemModels() {
    global $SYSTEM_AI_MODELS;
    return !empty($SYSTEM_AI_MODELS) ? $SYSTEM_AI_MODELS : [];
}
}

if (!function_exists('getDisabledAiModels')) {
function getDisabledAiModels() {
    global $cache_file;
    if (file_exists($cache_file)) {
        $data = @json_decode(@file_get_contents($cache_file), true);
        if (is_array($data)) return $data;
    }
    // Đọc từ CSDL
    try {
        $db = getDB();
        $res = @$db->query("SELECT `value` FROM system_settings WHERE `key` = 'disabled_ai_models' LIMIT 1");
        if ($res && ($row = $res->fetch_assoc())) {
            $data = json_decode($row['value'], true);
            if (is_array($data)) {
                @file_put_contents($cache_file, json_encode($data, JSON_UNESCAPED_UNICODE));
                $db->close();
                return $data;
            }
        }
        $db->close();
    } catch (Throwable $e) {}
    return [];
}
}

if (!function_exists('saveDisabledAiModels')) {
function saveDisabledAiModels($list) {
    global $cache_dir, $cache_file;
    $list = array_values(array_unique(array_filter($list)));
    if (!is_dir($cache_dir)) @mkdir($cache_dir, 0777, true);
    @file_put_contents($cache_file, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Cập nhật CSDL
    try {
        $db = getDB();
        $jsonStr = json_encode($list, JSON_UNESCAPED_UNICODE);
        $stmt = $db->prepare("INSERT INTO system_settings (`key`, `value`) VALUES ('disabled_ai_models', ?) ON DUPLICATE KEY UPDATE `value` = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $jsonStr, $jsonStr);
            $stmt->execute();
            $stmt->close();
        }
        $db->close();
    } catch (Throwable $e) {}
    return true;
}
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_status';

// 1. API CÔNG KHAI CHO SINH VIÊN / FRONTEND (ĐỌC DANH SÁCH BỊ KHÓA)
if ($action === 'get_status') {
    $disabled = getDisabledAiModels();
    echo json_encode([
        'success' => true,
        'disabled_models' => $disabled
    ]);
    exit();
}

// CÁC HÀNH ĐỘNG DƯỚI ĐÂY YÊU CẦU QUYỀN ADMIN
// Không dùng requireAdmin() vì nó redirect HTML → AJAX fetch() nhận HTML thay vì JSON → parse fail
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Bạn không có quyền Admin để thực hiện hành động này. Vui lòng đăng nhập lại với tài khoản Admin.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 2. LẤY TOÀN BỘ MODELS VÀ TRẠNG THÁI CHO TRANG ADMIN
if ($action === 'get_all') {
    $allModels = getAllSystemModels();
    $disabled = getDisabledAiModels();
    $result = [];
    foreach ($allModels as $m) {
        $m['enabled'] = !in_array($m['id'], $disabled, true);
        $result[] = $m;
    }
    echo json_encode([
        'success' => true,
        'models' => $result,
        'disabled_models' => $disabled,
        'total' => count($result),
        'enabled_count' => count($result) - count($disabled),
        'disabled_count' => count($disabled)
    ]);
    exit();
}

// 3. TOGGLE BẬT / TẮT 1 MODEL CỤ THỂ
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $model_id = trim($data['model_id'] ?? $_POST['model_id'] ?? '');
    $enabled = isset($data['enabled']) ? (bool)$data['enabled'] : (isset($_POST['enabled']) ? (bool)$_POST['enabled'] : true);

    if (empty($model_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Thiếu model_id']);
        exit();
    }

    $disabled = getDisabledAiModels();
    if ($enabled) {
        // Mở lại model -> Xóa khỏi danh sách disabled
        $disabled = array_values(array_diff($disabled, [$model_id]));
    } else {
        // Đóng model -> Thêm vào danh sách disabled
        if (!in_array($model_id, $disabled, true)) {
            $disabled[] = $model_id;
        }
    }

    saveDisabledAiModels($disabled);
    writeSystemLog(($enabled ? "Mở khóa model AI: " : "Đóng khóa model AI: ") . $model_id);

    echo json_encode([
        'success' => true,
        'model_id' => $model_id,
        'enabled' => $enabled,
        'disabled_models' => $disabled,
        'message' => $enabled ? "Đã mở mô hình $model_id" : "Đã đóng mô hình $model_id"
    ]);
    exit();
}

// 4. BATCH ACTIONS: KHÓA/MỞ HÀNG LOẠT (VÍ DỤ: DEEPSEEK V4, TOÀN BỘ DEEPSEEK, BẬT TẤT CẢ, TẮT TẤT CẢ)
if ($action === 'batch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $batch_type = trim($data['type'] ?? $_POST['type'] ?? '');
    $allModels = getAllSystemModels();
    $disabled = getDisabledAiModels();

    if ($batch_type === 'disable_deepseek_v4') {
        // Khóa tất cả DeepSeek V4 (V4 Pro & V4 Flash)
        foreach ($allModels as $m) {
            if (stripos($m['id'], 'deepseek-v4') !== false || stripos($m['name'], 'V4') !== false) {
                if (!in_array($m['id'], $disabled, true)) {
                    $disabled[] = $m['id'];
                }
            }
        }
        $msg = "Đã khóa toàn bộ mô hình DeepSeek V4 cho sinh viên";
    } elseif ($batch_type === 'enable_deepseek_v4') {
        // Mở lại DeepSeek V4
        $v4_ids = [];
        foreach ($allModels as $m) {
            if (stripos($m['id'], 'deepseek-v4') !== false || stripos($m['name'], 'V4') !== false) {
                $v4_ids[] = $m['id'];
            }
        }
        $disabled = array_values(array_diff($disabled, $v4_ids));
        $msg = "Đã mở lại mô hình DeepSeek V4";
    } elseif ($batch_type === 'disable_provider') {
        $prov = strtolower(trim($data['provider'] ?? ''));
        foreach ($allModels as $m) {
            if (strtolower($m['provider'] ?? '') === $prov) {
                if (!in_array($m['id'], $disabled, true)) {
                    $disabled[] = $m['id'];
                }
            }
        }
        $msg = "Đã khóa toàn bộ mô hình thuộc nhà cung cấp " . ucfirst($prov);
    } elseif ($batch_type === 'enable_provider') {
        $prov = strtolower(trim($data['provider'] ?? ''));
        $p_ids = [];
        foreach ($allModels as $m) {
            if (strtolower($m['provider'] ?? '') === $prov) {
                $p_ids[] = $m['id'];
            }
        }
        $disabled = array_values(array_diff($disabled, $p_ids));
        $msg = "Đã mở lại toàn bộ mô hình thuộc nhà cung cấp " . ucfirst($prov);
    } elseif ($batch_type === 'enable_all') {
        $disabled = [];
        $msg = "Đã mở toàn bộ mô hình AI";
    } elseif ($batch_type === 'disable_all') {
        $disabled = array_column($allModels, 'id');
        $msg = "Đã khóa toàn bộ mô hình AI";
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Lệnh batch không hợp lệ']);
        exit();
    }

    saveDisabledAiModels($disabled);
    writeSystemLog("Batch AI Models: $msg");

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'disabled_models' => $disabled
    ]);
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
