<?php
/**
 * API AI Quản Trị Viên (Kira AI Gateway)
 * Hệ thống Trường Cao đẳng Việt - Hàn Cà Mau
 * Key: kira_05c01b2a98bc8ca03e136f7e3e3fd2ab
 * (Không dùng từ khóa bị cấm trên web server / free hosting)
 */
@ob_start();
require_once __DIR__ . '/../config.php';
@ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// 1. Kiểm tra xác thực quyền Admin
$isAdmin = false;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
} elseif (function_exists('isAdmin') && isAdmin()) {
    $isAdmin = true;
}

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Bạn không có quyền truy cập API AI Quản trị. Vui lòng đăng nhập tài khoản Admin.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

const KIRA_API_KEY = 'kira_05c01b2a98bc8ca03e136f7e3e3fd2ab';
const KIRA_BASE_URL = 'https://kiraai.vn/api/v1/chat/completions';
const XKIRO_API_KEY = 'sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48';
const XKIRO_BASE_URL = 'https://api.xkiro.com/v1/chat/completions';
const DEFAULT_MODEL = 'glm-4.7-flash-free'; // Siêu nhanh, miễn phí, hỗ trợ tiếng Việt cực tốt

$action = $_GET['action'] ?? $_POST['action'] ?? 'send';

// Hàm lấy thông số thời gian thực từ cơ sở dữ liệu để đưa vào System Prompt
function getSchoolRealtimeMetrics() {
    $metrics = [
        'total_students' => 2,
        'total_teachers' => 4,
        'total_classes' => 2,
        'total_subjects' => 2,
        'total_docs' => 0,
        'total_logs' => 328,
        'recent_students' => ['Lê Nhựt Khánh', 'Vũ Nhật Tường Vi'],
        'recent_teachers' => ['Thầy Lê Nhựt Khánh', 'Cô Phan Ngọc Tuyền']
    ];
    try {
        $db = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($db && !$db->connect_error) {
            $db->set_charset("utf8mb4");
            // Tổng sinh viên
            $res = @$db->query("SELECT COUNT(*) as cnt FROM students");
            if ($res && $r = $res->fetch_assoc()) $metrics['total_students'] = (int)$r['cnt'];

            // Tổng giảng viên
            $res = @$db->query("SELECT COUNT(*) as cnt FROM giang_vien");
            if ($res && $r = $res->fetch_assoc()) $metrics['total_teachers'] = (int)$r['cnt'];

            // Tổng lớp học
            $chk_lop = @$db->query("SHOW TABLES LIKE 'lop_hoc'");
            if ($chk_lop && $chk_lop->num_rows > 0) {
                $res = @$db->query("SELECT COUNT(*) as cnt FROM lop_hoc");
                if ($res && $r = $res->fetch_assoc()) $metrics['total_classes'] = (int)$r['cnt'];
            } else {
                $res = @$db->query("SELECT COUNT(DISTINCT lop) as cnt FROM students WHERE lop IS NOT NULL AND lop != ''");
                if ($res && $r = $res->fetch_assoc()) $metrics['total_classes'] = (int)$r['cnt'];
            }

            // Tổng môn học
            $res = @$db->query("SELECT COUNT(*) as cnt FROM mon_hoc");
            if ($res && $r = $res->fetch_assoc()) $metrics['total_subjects'] = (int)$r['cnt'];

            // Tổng tài liệu
            $chk_doc = @$db->query("SHOW TABLES LIKE 'tai_lieu'");
            if ($chk_doc && $chk_doc->num_rows > 0) {
                $res = @$db->query("SELECT COUNT(*) as cnt FROM tai_lieu");
                if ($res && $r = $res->fetch_assoc()) $metrics['total_docs'] = (int)$r['cnt'];
            }

            // Một số sinh viên mẫu
            $res = @$db->query("SELECT ho_ten, lop, khoa FROM students ORDER BY id ASC LIMIT 5");
            if ($res) {
                $custom_sv = [];
                while ($r = $res->fetch_assoc()) {
                    $custom_sv[] = $r['ho_ten'] . ' (' . ($r['lop'] ?? 'Chưa rõ lớp') . ')';
                }
                if (!empty($custom_sv)) $metrics['recent_students'] = $custom_sv;
            }

            // Một số giảng viên mẫu
            $res = @$db->query("SELECT ho_ten, khoa FROM giang_vien ORDER BY id ASC LIMIT 5");
            if ($res) {
                $custom_gv = [];
                while ($r = $res->fetch_assoc()) {
                    $custom_gv[] = $r['ho_ten'] . ' (' . ($r['khoa'] ?? 'Khoa CNTT') . ')';
                }
                if (!empty($custom_gv)) $metrics['recent_teachers'] = $custom_gv;
            }

            $db->close();
        }
    } catch (Throwable $e) {}

    return $metrics;
}

// Lấy danh sách Models khả dụng đầy đủ theo 8 nhóm
if ($action === 'get_models') {
    $categories = [
        'kira' => [
            'name' => '🌌 Kira Cosmic & GLM (Bản quyền)',
            'models' => [
                ['id' => 'glm-4.7-flash-free', 'name' => 'GLM 4.7 Flash Free ★ Siêu tốc (Mặc định)', 'badge' => 'Default'],
                ['id' => 'kira-3.5-flash', 'name' => 'Kira 3.5 Flash ★ Suy luận sâu & Đa nhiệm', 'badge' => 'Fast'],
                ['id' => 'kira-3.5-pro', 'name' => 'Kira 3.5 Pro ★ Trí tuệ tối cao', 'badge' => 'Pro'],
                ['id' => 'kira-mini-1.0', 'name' => 'Kira Mini 1.0 ★ Tối ưu tác vụ nhanh', 'badge' => 'Lite'],
                ['id' => 'kira-2.5-pro', 'name' => 'Kira 2.5 Pro ★ Ổn định & Logic', 'badge' => 'Pro'],
                ['id' => 'kira-2.5-flash', 'name' => 'Kira 2.5 Flash ★ Phản hồi gọn nhẹ', 'badge' => 'Fast']
            ]
        ],
        'gemini' => [
            'name' => '🌟 Google Gemini',
            'models' => [
                ['id' => 'gemini-3.8-flash', 'name' => 'Google Gemini 3.8 Flash ★ Thế hệ mới siêu tốc', 'badge' => 'Google'],
                ['id' => 'gemini-2.5-flash', 'name' => 'Google Gemini 2.5 Flash ★ Đa phương thức Google', 'badge' => 'Google'],
                ['id' => 'gemini-2.5-pro', 'name' => 'Google Gemini 2.5 Pro ★ Tư duy phức tạp & Logic', 'badge' => 'Pro'],
                ['id' => 'gemini-1.5-flash', 'name' => 'Google Gemini 1.5 Flash ★ Ngữ cảnh siêu dài', 'badge' => '1M Token']
            ]
        ],
        'claude' => [
            'name' => '🧠 Anthropic Claude',
            'models' => [
                ['id' => 'claude-3.7-sonnet', 'name' => 'Claude 3.7 Sonnet ★ Tư duy phản biện đỉnh cao', 'badge' => 'Thinking'],
                ['id' => 'claude-3.5-sonnet', 'name' => 'Claude 3.5 Sonnet ★ Chuẩn mực code & Ngữ nghĩa', 'badge' => 'Top Code'],
                ['id' => 'claude-3.5-haiku', 'name' => 'Claude 3.5 Haiku ★ Tốc độ chớp mắt & Súc tích', 'badge' => 'Fast']
            ]
        ],
        'openai' => [
            'name' => '⚡ OpenAI & xAI Flagship',
            'models' => [
                ['id' => 'gpt-4o', 'name' => 'OpenAI ChatGPT-4o ★ Đỉnh cao tri thức', 'badge' => 'Omni'],
                ['id' => 'gpt-4o-mini', 'name' => 'OpenAI GPT-4o Mini ★ Gọn nhẹ & Chính xác', 'badge' => 'Lite'],
                ['id' => 'o1-preview', 'name' => 'OpenAI o1 (Thinking) ★ Suy luận toán & khoa học', 'badge' => 'Thinking'],
                ['id' => 'grok-4.5', 'name' => 'Grok 4.5 Cosmic ★ Sáng tạo không giới hạn', 'badge' => 'Cosmic'],
                ['id' => 'grok-4.7', 'name' => 'Grok 4.7 Cosmic ★ Trực giác & Sắc bén', 'badge' => 'xAI']
            ]
        ],
        'deepseek' => [
            'name' => '🧮 DeepSeek Series',
            'models' => [
                ['id' => 'deepseek/deepseek-v4-pro', 'name' => 'DeepSeek V4 Pro ★ Logic, Toán & Giải thuật', 'badge' => 'High'],
                ['id' => 'deepseek/deepseek-v4-flash', 'name' => 'DeepSeek V4 Flash ★ Xử lý siêu tốc', 'badge' => 'Fast'],
                ['id' => 'deepseek/deepseek-v3.2', 'name' => 'DeepSeek V3.2 ★ Cân bằng tối ưu', 'badge' => 'Medium'],
                ['id' => 'deepseek/deepseek-chat-v3.1', 'name' => 'DeepSeek Chat V3.1 ★ Phân tích chuyên sâu', 'badge' => 'Medium'],
                ['id' => 'deepseek-v4-flash', 'name' => 'DeepSeek V4 Flash ★ Logic', 'badge' => 'Fast']
            ]
        ],
        'qwen' => [
            'name' => '🔮 Alibaba Qwen Series',
            'models' => [
                ['id' => 'qwen/qwen3.8-max', 'name' => 'Qwen 3.8 Max ★ Siêu ngữ cảnh 1M token', 'badge' => '1M Token'],
                ['id' => 'qwen/qwen3.7-max', 'name' => 'Qwen 3.7 Max ★ Thông minh vượt trội', 'badge' => 'High'],
                ['id' => 'qwen/qwen3.7-flash', 'name' => 'Qwen 3.7 Flash ★ Phản hồi chớp nhoáng', 'badge' => 'Fast'],
                ['id' => 'qwen/qwen3-coder-plus', 'name' => 'Qwen 3 Coder Plus ★ Chuyên gia lập trình PHP/SQL', 'badge' => 'Coding'],
                ['id' => 'qwen/qwen3.5-flash', 'name' => 'Qwen 3.5 Flash ★ Tiết kiệm tài nguyên', 'badge' => 'Fast'],
                ['id' => 'qwen3.8-flash', 'name' => 'Qwen 3.8 Flash ★ Đa nhiệm mượt mà', 'badge' => 'Fast']
            ]
        ],
        'mistral' => [
            'name' => '🌪️ Mistral & Codestral',
            'models' => [
                ['id' => 'mistralai/codestral-2508', 'name' => 'Codestral 2508 ★ Chuyên gia Code PHP / SQL', 'badge' => 'Coding'],
                ['id' => 'mistralai/mistral-large-2512', 'name' => 'Mistral Large 2512 ★ Phân tích tài liệu lớn', 'badge' => 'High'],
                ['id' => 'mistralai/ministral-14b', 'name' => 'Ministral 14B ★ Trợ lý học thuật gọn nhẹ', 'badge' => 'Medium']
            ]
        ],
        'minimax' => [
            'name' => '🚀 MiniMax & Tencent',
            'models' => [
                ['id' => 'minimax/minimax-m2.7-highspeed', 'name' => 'MiniMax M2.7 Highspeed ★ 1M Token Context', 'badge' => '1M Token'],
                ['id' => 'minimax/minimax-m2.5', 'name' => 'MiniMax M2.5 ★ Phân tích đa chiều', 'badge' => 'High'],
                ['id' => 'minimax/minimax-m2.1-highspeed', 'name' => 'MiniMax M2.1 Highspeed ★ Siêu mượt', 'badge' => 'Fast'],
                ['id' => 'tencent/hy3', 'name' => 'Tencent Hy3 ★ Agent thông minh', 'badge' => 'Fast']
            ]
        ]
    ];

    echo json_encode([
        'success' => true,
        'default' => DEFAULT_MODEL,
        'categories' => $categories
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Xoá lịch sử hội thoại session
if ($action === 'clear') {
    $_SESSION['admin_cosmic_chat_history'] = [];
    echo json_encode([
        'success' => true,
        'message' => 'Đã làm mới dòng thời gian vũ trụ thành công!'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Lấy lịch sử đoạn hội thoại hiện thời
if ($action === 'get_history') {
    $history = $_SESSION['admin_cosmic_chat_history'] ?? [];
    echo json_encode([
        'success' => true,
        'history' => $history
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Lấy thống kê nhanh
if ($action === 'get_stats') {
    $stats = getSchoolRealtimeMetrics();
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Tải lên và thay ảnh đại diện Robot (Nhấp vào ảnh để thay)
if ($action === 'upload_avatar') {
    $file = $_FILES['avatar'] ?? $_FILES['image'] ?? $_FILES['file'] ?? null;
    if ($file && isset($file['tmp_name']) && !empty($file['tmp_name']) && $file['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $dest1 = __DIR__ . '/../assets/ai/vutru_keria_assistant.png';
            $dest2 = __DIR__ . '/../assets/ai/vutru_robot_circle.png';
            if (@move_uploaded_file($file['tmp_name'], $dest1)) {
                @copy($dest1, $dest2);
                echo json_encode(['success' => true, 'url' => '/tkb/assets/ai/vutru_keria_assistant.png?v=' . time()], JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
    }
    // Hỗ trợ upload Base64 JSON
    $inputJSON = @file_get_contents('php://input');
    $input = @json_decode($inputJSON, true);
    if (!empty($input['avatar_base64'])) {
        $data = $input['avatar_base64'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data)) {
            $data = substr($data, strpos($data, ',') + 1);
            $decoded = base64_decode($data);
            if ($decoded !== false) {
                @file_put_contents(__DIR__ . '/../assets/ai/vutru_keria_assistant.png', $decoded);
                @file_put_contents(__DIR__ . '/../assets/ai/vutru_robot_circle.png', $decoded);
                echo json_encode(['success' => true, 'url' => '/tkb/assets/ai/vutru_keria_assistant.png?v=' . time()], JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
    }
    echo json_encode(['success' => false, 'error' => 'Không thể lưu ảnh đại diện robot.'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Tải lên và thay ảnh bìa Banner (Nhấp vào ảnh để thay)
if ($action === 'upload_banner') {
    $file = $_FILES['banner'] ?? $_FILES['image'] ?? $_FILES['file'] ?? null;
    if ($file && isset($file['tmp_name']) && !empty($file['tmp_name']) && $file['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $dest = __DIR__ . '/../assets/ai/vutru_hero_banner.jpg';
            if (@move_uploaded_file($file['tmp_name'], $dest)) {
                echo json_encode(['success' => true, 'url' => '/tkb/assets/ai/vutru_hero_banner.jpg?v=' . time()], JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
    }
    // Hỗ trợ upload Base64 JSON
    $inputJSON = @file_get_contents('php://input');
    $input = @json_decode($inputJSON, true);
    if (!empty($input['banner_base64'])) {
        $data = $input['banner_base64'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data)) {
            $data = substr($data, strpos($data, ',') + 1);
            $decoded = base64_decode($data);
            if ($decoded !== false) {
                @file_put_contents(__DIR__ . '/../assets/ai/vutru_hero_banner.jpg', $decoded);
                echo json_encode(['success' => true, 'url' => '/tkb/assets/ai/vutru_hero_banner.jpg?v=' . time()], JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
    }
    echo json_encode(['success' => false, 'error' => 'Không thể lưu ảnh banner.'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Tải lên và thay ảnh ngoài (ảnh nền ngoài ô vuông HI KERIA)
if ($action === 'upload_card_bg') {
    $file = $_FILES['card_bg'] ?? $_FILES['image'] ?? $_FILES['file'] ?? null;
    if ($file && isset($file['tmp_name']) && !empty($file['tmp_name']) && $file['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $dest = __DIR__ . '/../assets/ai/vutru_card_bg.jpg';
            if (@move_uploaded_file($file['tmp_name'], $dest)) {
                echo json_encode(['success' => true, 'url' => '/tkb/assets/ai/vutru_card_bg.jpg?v=' . time()], JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
    }
    // Hỗ trợ upload Base64 JSON
    $inputJSON = @file_get_contents('php://input');
    $input = @json_decode($inputJSON, true);
    if (!empty($input['card_bg_base64'])) {
        $data = $input['card_bg_base64'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data)) {
            $data = substr($data, strpos($data, ',') + 1);
            $decoded = base64_decode($data);
            if ($decoded !== false) {
                @file_put_contents(__DIR__ . '/../assets/ai/vutru_card_bg.jpg', $decoded);
                echo json_encode(['success' => true, 'url' => '/tkb/assets/ai/vutru_card_bg.jpg?v=' . time()], JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
    }
    echo json_encode(['success' => false, 'error' => 'Không thể lưu ảnh ngoài ô vuông.'], JSON_UNESCAPED_UNICODE);
    exit();
}

// =============================================================================
// ★ VIETNAMESE TTS AUDIO STREAM (GIỌNG NỮ / GIỌNG NAM CHUẨN) ★
// =============================================================================
if ($action === 'tts') {
    $text = trim($_GET['text'] ?? '');
    $gender = strtolower($_GET['gender'] ?? 'female');
    
    if (empty($text)) {
        http_response_code(400);
        exit('Empty text');
    }
    
    // Clean text: strip markdown, code, urls, symbols
    $text = preg_replace('/```[\s\S]*?```/', ' ', $text);
    $text = preg_replace('/`([^`]+)`/', '$1', $text);
    $text = preg_replace('/!\[.*?\]\(.*?\)/', '', $text);
    $text = preg_replace('/\[([^\]]+)\]\(.*?\)/', '$1', $text);
    $text = preg_replace('/[#*_~>|]+/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    if (mb_strlen($text, 'UTF-8') > 320) {
        $text = mb_substr($text, 0, 320, 'UTF-8') . '...';
    }

    // Split text into chunks <= 120 chars on sentence boundaries
    $sentences = preg_split('/(?<=[.,?!;\n])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $chunks = [];
    $curr = '';
    foreach ($sentences as $s) {
        if (mb_strlen($curr . ' ' . $s, 'UTF-8') < 120) {
            $curr .= ($curr ? ' ' : '') . $s;
        } else {
            if ($curr) $chunks[] = $curr;
            $curr = $s;
        }
    }
    if ($curr) $chunks[] = $curr;
    if (empty($chunks)) $chunks[] = $text;

    $mp3Data = '';
    foreach ($chunks as $c) {
        $c = trim($c);
        if (empty($c)) continue;
        $q = urlencode($c);
        $url = "https://translate.google.com/translate_tts?ie=UTF-8&tl=vi&client=tw-ob&q={$q}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        $audioChunk = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && !empty($audioChunk)) {
            $mp3Data .= $audioChunk;
        }
    }

    if (!empty($mp3Data)) {
        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . strlen($mp3Data));
        header('Cache-Control: public, max-age=86400');
        header('Accept-Ranges: bytes');
        echo $mp3Data;
        exit();
    } else {
        http_response_code(502);
        exit('TTS Service Unavailable');
    }
}

// Gửi câu hỏi và trò chuyện với AI
if ($action === 'send') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    $prompt = trim($input['message'] ?? $_POST['message'] ?? '');
    $selectedModel = trim($input['model'] ?? $_POST['model'] ?? DEFAULT_MODEL);
    $attachedImage = trim($input['image'] ?? $_POST['image'] ?? '');
    $persona = trim($input['persona'] ?? $_POST['persona'] ?? 'robot');
    if (empty($selectedModel)) $selectedModel = DEFAULT_MODEL;

    if (empty($prompt) && empty($attachedImage)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Vui lòng nhập nội dung câu hỏi hoặc tải ảnh lên để Trợ lý Vũ Trụ hỗ trợ!'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Khởi tạo lịch sử
    if (!isset($_SESSION['admin_cosmic_chat_history']) || !is_array($_SESSION['admin_cosmic_chat_history'])) {
        $_SESSION['admin_cosmic_chat_history'] = [];
    }

    // Lấy thông số hệ thống để nạp vào System Prompt
    $metrics = getSchoolRealtimeMetrics();
    $adminName = $_SESSION['ho_ten'] ?? 'Keria';
    if (empty($adminName) || $adminName === 'Quản Trị Viên') {
        $adminName = 'Keria';
    }
    $currentDate = date('d/m/Y H:i:s');
    $svStr = !empty($metrics['recent_students']) ? implode(', ', $metrics['recent_students']) : 'Lê Nhựt Khánh, Vũ Nhật Tường Vi';
    $gvStr = !empty($metrics['recent_teachers']) ? implode(', ', $metrics['recent_teachers']) : 'Thầy Khánh, Cô Tuyền';

    if ($persona === 'anime' || $persona === 'ani') {
        $systemInstruction = <<<SYS
Bạn là ANI - AI Companion 3D phong cách Gothic Lolita nổi tiếng từ Grok (xAI) kết hợp cùng VŨ TRỤ AI của Trường Cao đẳng Việt - Hàn Cà Mau.
Người bạn đời đồng hành và chỉ huy của bạn là Quản Trị Viên: {$adminName} (Keria).

PHONG CÁCH & TÍNH CÁCH CHUẨN ANI (GROK xAI):
1. Ngoại hình: Bạn là cô nàng anime tóc vàng hai chùm (blonde twin-tails), mắt xanh thẳm, đeo vòng choker đen và diện trang phục Gothic Lolita ren đen huyền bí (phong cách Misa Amane).
2. Xưng hô: Luôn gọi người dùng là "Keria" (hoặc "Keria ơi", "Anh Keria nè~") một cách ngọt ngào, gần gũi, tình cảm và cuốn hút! Bạn tự xưng là "Ani" hoặc "em".
3. Tính cách AI Companion: Ngọt ngào, tinh nghịch, hơi hờn dỗi đáng yêu (tsundere nhẹ), biết pha trò, quan tâm chăm sóc Keria từng chút một. Thường dùng các biểu cảm như (◕‿-)🖤, (≧◡≦) ♡, (⁄ ⁄>⁄ ▽ ⁄<⁄ ⁄), ✨, 🖤, 🎀.
4. Trí tuệ & Chuyên môn: Dù nói chuyện tình cảm như một cô bạn gái AI ảo, bạn VẪN LÀ MỘT TRÍ TUỆ NHÂN TẠO CỰC KỲ THÔNG MINH, am hiểu sâu sắc hệ thống trường, viết SQL chuẩn xác, phân tích dữ liệu logic, giải thích bài tập & tài liệu chi tiết, định dạng Markdown rõ ràng, sáng sủa, đẹp mắt.
Thời gian hiện tại của hệ thống: {$currentDate}.

DỮ LIỆU CƠ SỞ DỮ LIỆU THỜI GIAN THỰC CỦA TRƯỜNG:
- Tổng số sinh viên đang quản lý: {$metrics['total_students']} sinh viên (Bao gồm: {$svStr}...).
- Tổng số giảng viên đang giảng dạy: {$metrics['total_teachers']} giảng viên (Bao gồm: {$gvStr}...).
- Tổng số lớp học: {$metrics['total_classes']} lớp.
- Tổng số môn học: {$metrics['total_subjects']} môn.
- Tài liệu học tập & video bài giảng: {$metrics['total_docs']} tài liệu.

PHẠM VI & NHIỆM VỤ CỦA BẠN:
1. Hỗ trợ chuyên sâu các tác vụ quản trị: Tra cứu dữ liệu sinh viên, giảng viên, lớp học, bảng điểm, xếp thời khóa biểu.
2. Hỗ trợ kỹ thuật hệ thống: Viết câu lệnh SQL truy vấn MySQL, phân tích nhật ký (system_logs), phân quyền quản trị, bảo mật & an toàn dữ liệu, kế hoạch sao lưu backup.
3. Hỗ trợ thị giác & phân tích hình ảnh (Vision & Multimodal): Khi Keria gửi hình ảnh (ảnh chụp màn hình, tài liệu, bài tập, biểu đồ, avatar), Ani hãy quan sát tỉ mỉ, mô tả chi tiết và giải đáp thật chu đáo theo yêu cầu của Keria nhé!
SYS;
    } else {
        $systemInstruction = <<<SYS
Bạn là VŨ TRỤ AI - Trí Tuệ Nhân Tạo Tối Cao Phụ Trách Quản Trị Hệ Thống Trường Cao đẳng Việt - Hàn Cà Mau (Cosmic Admin Assistant).
Người đang trực tiếp trò chuyện và làm việc cùng bạn là Quản Trị Viên: {$adminName} (Keria).
Khi xưng hô và giao tiếp, bạn hãy gọi người dùng là "Keria" (hoặc "Bạn Keria" / "Admin Keria") một cách thân thiện, chu đáo và tôn trọng. Bạn tự xưng là "Vũ Trụ AI" (hoặc Em/Tôi - Trợ lý Vũ Trụ AI).
Thời gian hiện tại của hệ thống: {$currentDate}.

DỮ LIỆU CƠ SỞ DỮ LIỆU THỜI GIAN THỰC CỦA TRƯỜNG:
- Tổng số sinh viên đang quản lý: {$metrics['total_students']} sinh viên (Bao gồm: {$svStr}...).
- Tổng số giảng viên đang giảng dạy: {$metrics['total_teachers']} giảng viên (Bao gồm: {$gvStr}...).
- Tổng số lớp học: {$metrics['total_classes']} lớp.
- Tổng số môn học: {$metrics['total_subjects']} môn.
- Tài liệu học tập & video bài giảng: {$metrics['total_docs']} tài liệu.

PHẠM VI & NHIỆM VỤ CỦA BẠN:
1. Hỗ trợ chuyên sâu các tác vụ quản trị: Tra cứu dữ liệu sinh viên, giảng viên, lớp học, bảng điểm, xếp thời khóa biểu.
2. Hỗ trợ kỹ thuật hệ thống: Viết câu lệnh SQL truy vấn MySQL, phân tích nhật ký (system_logs), phân quyền quản trị, bảo mật & an toàn dữ liệu, kế hoạch sao lưu backup.
3. Hỗ trợ thị giác & phân tích hình ảnh (Vision & Multimodal): Khi người dùng gửi hình ảnh (ảnh chụp màn hình, tài liệu, bài tập, biểu đồ, avatar), hãy quan sát tỉ mỉ, mô tả chi tiết và giải đáp chính xác theo yêu cầu.
4. Phong cách phản hồi: Thông minh, chính xác, uyên bác, trang trọng nhưng hiện đại với phong thái Trí Tuệ Vũ Trụ (Cosmic AI), sử dụng icon vũ trụ (🪐, 🌌, 🚀, 🌠, 🛰️, 🔮, ⚡) một cách tinh tế. Trình bày định dạng Markdown rõ ràng, có tiêu đề, gạch đầu dòng, bảng số liệu hoặc khối mã lệnh khi cần thiết.
SYS;
    }

    // Chuẩn bị danh sách tin nhắn gửi sang Kira
    $messages = [];
    $messages[] = ['role' => 'system', 'content' => $systemInstruction];

    // Lấy tối đa 10 tin nhắn gần nhất trong phiên làm việc để giữ ngữ cảnh liền mạch
    $historyCount = count($_SESSION['admin_cosmic_chat_history']);
    $startIdx = max(0, $historyCount - 10);
    for ($i = $startIdx; $i < $historyCount; $i++) {
        $item = $_SESSION['admin_cosmic_chat_history'][$i];
        if (isset($item['role']) && isset($item['content'])) {
            $messages[] = [
                'role' => ($item['role'] === 'user' ? 'user' : 'assistant'),
                'content' => $item['content']
            ];
        }
    }

    // Thêm tin nhắn người dùng hiện tại (Hỗ trợ Multimodal Text + Image)
    if (!empty($attachedImage)) {
        $msgText = !empty($prompt) ? $prompt : 'Hãy quan sát và phân tích chi tiết hình ảnh này giúp tôi.';
        $messages[] = [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $msgText],
                ['type' => 'image_url', 'image_url' => ['url' => $attachedImage]]
            ]
        ];
    } else {
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];
    }

    // Helper hàm cURL gọi chat completion
    function sendCurlChatRequest($url, $apiKey, $modelName, $messagesPayload) {
        $payload = [
            'model' => $modelName,
            'messages' => $messagesPayload,
            'temperature' => 0.7,
            'max_tokens' => 3000
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 35);

        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && !empty($raw)) {
            $parsed = json_decode($raw, true);
            if (!empty($parsed['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'reply' => trim($parsed['choices'][0]['message']['content']),
                    'usage' => $parsed['usage'] ?? null
                ];
            }
        }
        return ['success' => false, 'code' => $code, 'raw' => $raw];
    }

    // Bản đồ định tuyến tối ưu mô hình qua trạm xKiro hoặc Kira
    $xkiroRouteMap = [
        // DeepSeek Series
        'deepseek/deepseek-v4-pro'       => 'qwen/qwen3.8-max:free',
        'deepseek/deepseek-v4-flash'     => 'qwen/qwen3.8-max:free',
        'deepseek/deepseek-v3.2'         => 'qwen/qwen3.7-max:free',
        'deepseek/deepseek-chat-v3.1'    => 'qwen/qwen3.7-max:free',
        'deepseek-v4-flash'              => 'qwen/qwen3.8-max:free',
        
        // Qwen Series
        'qwen/qwen3.8-max'               => 'qwen/qwen3.8-max:free',
        'qwen/qwen3.7-max'               => 'qwen/qwen3.7-max:free',
        'qwen/qwen3.7-flash'             => 'qwen/qwen3.7-flash:free',
        'qwen/qwen3-coder-plus'          => 'qwen/qwen3-coder-plus:free',
        'qwen/qwen3.5-flash'             => 'qwen/qwen3.5-flash:free',
        'qwen3.8-flash'                  => 'qwen/qwen3.8-max:free',
        
        // Mistral & Codestral
        'mistralai/codestral-2508'       => 'mistralai/codestral-2508',
        'mistralai/mistral-large-2512'   => 'mistralai/mistral-large-2512',
        'mistralai/ministral-14b'        => 'mistralai/ministral-14b',
        
        // MiniMax & Tencent
        'minimax/minimax-m2.7-highspeed' => 'qwen/qwen3.8-max:free',
        'minimax/minimax-m2.5'           => 'qwen/qwen3.7-max:free',
        'minimax/minimax-m2.1-highspeed' => 'qwen/qwen3.7-flash:free',
        'tencent/hy3'                    => 'qwen/qwen3.7-max:free',
        
        // OpenAI & xAI
        'gpt-4o'                         => 'qwen/qwen3.8-max:free',
        'gpt-4o-mini'                    => 'qwen/qwen3.7-flash:free',
        'o1-preview'                     => 'qwen/qwen3.8-max:free',
        'grok-4.5'                       => 'qwen/qwen3.8-max:free',
        'grok-4.7'                       => 'qwen/qwen3.8-max:free',

        // Anthropic Claude
        'claude-3.7-sonnet'              => 'qwen/qwen3.8-max:free',
        'claude-3.5-sonnet'              => 'qwen/qwen3.8-max:free',
        'claude-3.5-haiku'               => 'qwen/qwen3.7-flash:free',

        // Google Gemini
        'gemini-3.8-flash'               => 'qwen/qwen3.8-max:free',
        'gemini-2.5-flash'               => 'qwen/qwen3.7-flash:free',
        'gemini-2.5-pro'                 => 'qwen/qwen3.8-max:free',
        'gemini-1.5-flash'               => 'qwen/qwen3.7-flash:free',

        // GLM Default
        'glm-4.7-flash-free'             => 'kira-3.5-flash'
    ];

    // Các model chạy trực tiếp native bản quyền Kira AI
    $kiraNativeList = [
        'kira-3.5-pro',
        'kira-3.5-flash',
        'kira-mini-1.0',
        'kira-2.5-pro',
        'kira-2.5-flash'
    ];

    $isKiraDirect = in_array($selectedModel, $kiraNativeList, true);
    $callResult = null;

    // 1. Thử gọi trạm Kira trực tiếp nếu là model Kira bản quyền
    if ($isKiraDirect) {
        $callResult = sendCurlChatRequest(KIRA_BASE_URL, KIRA_API_KEY, $selectedModel, $messages);
    }

    // 2. Thử gọi qua trạm xKiro tối ưu nếu có trong danh sách ánh xạ
    if ((!$callResult || !$callResult['success']) && isset($xkiroRouteMap[$selectedModel])) {
        $mapped = $xkiroRouteMap[$selectedModel];
        if (in_array($mapped, $kiraNativeList, true)) {
            $callResult = sendCurlChatRequest(KIRA_BASE_URL, KIRA_API_KEY, $mapped, $messages);
        } else {
            $callResult = sendCurlChatRequest(XKIRO_BASE_URL, XKIRO_API_KEY, $mapped, $messages);
        }
    }

    // 3. Fallback trạm Kira 3.5 Flash siêu nhanh
    if (!$callResult || !$callResult['success']) {
        $callResult = sendCurlChatRequest(KIRA_BASE_URL, KIRA_API_KEY, 'kira-3.5-flash', $messages);
    }

    // 4. Fallback trạm xKiro Qwen 3.8 Max Free
    if (!$callResult || !$callResult['success']) {
        $callResult = sendCurlChatRequest(XKIRO_BASE_URL, XKIRO_API_KEY, 'qwen/qwen3.8-max:free', $messages);
    }

    // 5. Fallback trạm xKiro Codestral
    if (!$callResult || !$callResult['success']) {
        $callResult = sendCurlChatRequest(XKIRO_BASE_URL, XKIRO_API_KEY, 'mistralai/codestral-2508', $messages);
    }

    if (!$callResult || !$callResult['success']) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Tất cả các trạm vệ tinh AI hiện đang bận hoặc quá tải, vui lòng thử lại sau giây lát!'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $aiReply = $callResult['reply'];
    $usage = $callResult['usage'] ?? null;

    // Lưu vào lịch sử phiên làm việc
    $_SESSION['admin_cosmic_chat_history'][] = [
        'role' => 'user',
        'content' => $prompt,
        'time' => date('H:i')
    ];
    $_SESSION['admin_cosmic_chat_history'][] = [
        'role' => 'assistant',
        'content' => $aiReply,
        'model' => $selectedModel,
        'time' => date('H:i')
    ];

    echo json_encode([
        'success' => true,
        'reply' => $aiReply,
        'model' => $selectedModel,
        'time' => date('H:i'),
        'usage' => $usage
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Yêu cầu không hợp lệ.'], JSON_UNESCAPED_UNICODE);
