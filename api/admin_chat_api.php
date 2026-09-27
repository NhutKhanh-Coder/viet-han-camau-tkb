<?php
/**
 * API Chatbot AI Quản Trị Viên (Kira AI Gateway)
 * Hệ thống Trường Cao đẳng Việt - Hàn Cà Mau
 * Key: kira_05c01b2a98bc8ca03e136f7e3e3fd2ab
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
        'error' => 'Bạn không có quyền truy cập API Chatbot Quản trị. Vui lòng đăng nhập tài khoản Admin.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

const KIRA_API_KEY = 'kira_05c01b2a98bc8ca03e136f7e3e3fd2ab';
const KIRA_BASE_URL = 'https://kiraai.vn/api/v1/chat/completions';
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

// Lấy danh sách Models khả dụng từ Kira
if ($action === 'get_models') {
    $models = [
        // Kira & GLM
        ['id' => 'glm-4.7-flash-free', 'name' => 'GLM 4.7 Flash Free (Siêu tốc - Khuyên dùng)', 'type' => 'Free', 'speed' => 'Chớp nhoáng'],
        ['id' => 'kira-3.5-flash', 'name' => 'Kira 3.5 Flash (Suy luận sâu & đa nhiệm)', 'type' => 'Pro', 'speed' => 'Nhanh'],
        ['id' => 'kira-3.5-pro', 'name' => 'Kira 3.5 Pro (Mô hình trí tuệ tối cao)', 'type' => 'Pro', 'speed' => 'Tiêu chuẩn'],
        ['id' => 'kira-mini-1.0', 'name' => 'Kira Mini 1.0 (Bản quyền Kira - Nhanh)', 'type' => 'Free', 'speed' => 'Chớp nhoáng'],
        ['id' => 'kira-2.5-pro', 'name' => 'Kira 2.5 Pro (Ổn định & Logic)', 'type' => 'Pro', 'speed' => 'Nhanh'],
        ['id' => 'kira-2.5-flash', 'name' => 'Kira 2.5 Flash (Gọn nhẹ & Tối ưu)', 'type' => 'Free', 'speed' => 'Rất nhanh'],

        // Google Gemini
        ['id' => 'gemini-3.8-flash', 'name' => 'Gemini 3.8 Flash (Google Thế hệ mới)', 'type' => 'Pro', 'speed' => 'Chớp nhoáng'],
        ['id' => 'gemini-2.5-flash', 'name' => 'Gemini 2.5 Flash (Đa phương thức Google)', 'type' => 'Pro', 'speed' => 'Nhanh'],
        ['id' => 'gemini-2.5-pro', 'name' => 'Gemini 2.5 Pro (Tư duy phức tạp)', 'type' => 'Pro', 'speed' => 'Tiêu chuẩn'],
        ['id' => 'gemini-1.5-flash', 'name' => 'Gemini 1.5 Flash (Ngữ cảnh 1M Token)', 'type' => 'Pro', 'speed' => 'Nhanh'],

        // Anthropic Claude
        ['id' => 'claude-3.7-sonnet', 'name' => 'Claude 3.7 Sonnet (Tư duy phản biện đỉnh cao)', 'type' => 'Pro', 'speed' => 'Tiêu chuẩn'],
        ['id' => 'claude-3.5-sonnet', 'name' => 'Claude 3.5 Sonnet (Chuẩn mực lập trình & ngữ nghĩa)', 'type' => 'Pro', 'speed' => 'Tiêu chuẩn'],
        ['id' => 'claude-3.5-haiku', 'name' => 'Claude 3.5 Haiku (Tốc độ chớp mắt & súc tích)', 'type' => 'Pro', 'speed' => 'Chớp nhoáng'],

        // OpenAI & xAI
        ['id' => 'gpt-4o', 'name' => 'ChatGPT-4o Omni (Đỉnh cao toàn năng)', 'type' => 'Pro', 'speed' => 'Nhanh'],
        ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini (Gọn nhẹ, chính xác)', 'type' => 'Free', 'speed' => 'Chớp nhoáng'],
        ['id' => 'o1-preview', 'name' => 'OpenAI o1 (Suy luận toán & khoa học)', 'type' => 'Pro', 'speed' => 'Suy luận'],
        ['id' => 'grok-4.5', 'name' => 'Grok 4.5 Cosmic (Sáng tạo & sắc bén)', 'type' => 'Pro', 'speed' => 'Tiêu chuẩn'],
        ['id' => 'grok-4.7', 'name' => 'Grok 4.7 Cosmic (Trực giác mở rộng xAI)', 'type' => 'Pro', 'speed' => 'Tiêu chuẩn'],

        // DeepSeek
        ['id' => 'deepseek/deepseek-v4-pro', 'name' => 'DeepSeek V4 Pro (Logic & Giải thuật cao cấp)', 'type' => 'Pro', 'speed' => 'Tiêu chuẩn'],
        ['id' => 'deepseek/deepseek-v4-flash', 'name' => 'DeepSeek V4 Flash (Xử lý siêu tốc)', 'type' => 'Free', 'speed' => 'Chớp nhoáng'],
        ['id' => 'deepseek/deepseek-v3.2', 'name' => 'DeepSeek V3.2 (Cân bằng tối ưu)', 'type' => 'Pro', 'speed' => 'Nhanh'],
        ['id' => 'deepseek/deepseek-chat-v3.1', 'name' => 'DeepSeek Chat V3.1 (Đối thoại & Học thuật)', 'type' => 'Free', 'speed' => 'Nhanh'],
        ['id' => 'deepseek-v4-flash', 'name' => 'DeepSeek V4 Flash (Chuyên gia lập trình & logic)', 'type' => 'Free', 'speed' => 'Rất nhanh'],

        // Alibaba Qwen
        ['id' => 'qwen/qwen3.8-max', 'name' => 'Qwen 3.8 Max (Siêu ngữ cảnh 1M token)', 'type' => 'Pro', 'speed' => 'Nhanh'],
        ['id' => 'qwen/qwen3.7-max', 'name' => 'Qwen 3.7 Max (Thông minh vượt trội)', 'type' => 'Pro', 'speed' => 'Nhanh'],
        ['id' => 'qwen/qwen3.7-flash', 'name' => 'Qwen 3.7 Flash (Phản hồi chớp nhoáng)', 'type' => 'Free', 'speed' => 'Chớp nhoáng'],
        ['id' => 'qwen/qwen3-coder-plus', 'name' => 'Qwen 3 Coder Plus (Lập trình PHP/SQL)', 'type' => 'Pro', 'speed' => 'Nhanh'],
        ['id' => 'qwen/qwen3.5-flash', 'name' => 'Qwen 3.5 Flash (Tiết kiệm tài nguyên)', 'type' => 'Free', 'speed' => 'Chớp nhoáng'],
        ['id' => 'qwen3.8-flash', 'name' => 'Qwen 3.8 Flash (Thông minh & mượt mà)', 'type' => 'Free', 'speed' => 'Nhanh'],

        // Mistral & Codestral
        ['id' => 'mistralai/codestral-2508', 'name' => 'Codestral 2508 (Chuyên gia viết mã & fix lỗi)', 'type' => 'Free', 'speed' => 'Siêu tốc'],
        ['id' => 'mistralai/mistral-large-2512', 'name' => 'Mistral Large 2512 (Phân tích tài liệu lớn)', 'type' => 'Pro', 'speed' => 'Nhanh'],
        ['id' => 'mistralai/ministral-14b', 'name' => 'Ministral 14B (Trợ lý học thuật gọn nhẹ)', 'type' => 'Free', 'speed' => 'Rất nhanh'],

        // MiniMax & Tencent
        ['id' => 'minimax/minimax-m2.7-highspeed', 'name' => 'MiniMax M2.7 Highspeed (1M Token Context)', 'type' => 'Pro', 'speed' => 'Nhanh'],
        ['id' => 'minimax/minimax-m2.5', 'name' => 'MiniMax M2.5 (Phân tích đa chiều)', 'type' => 'Pro', 'speed' => 'Nhanh'],
        ['id' => 'minimax/minimax-m2.1-highspeed', 'name' => 'MiniMax M2.1 Highspeed (Siêu mượt)', 'type' => 'Free', 'speed' => 'Chớp nhoáng'],
        ['id' => 'tencent/hy3', 'name' => 'Tencent Hy3 (Agent thông minh)', 'type' => 'Free', 'speed' => 'Rất nhanh']
    ];

    echo json_encode([
        'success' => true,
        'default' => DEFAULT_MODEL,
        'models' => $models
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Xoá lịch sử chat session
if ($action === 'clear') {
    $_SESSION['admin_cosmic_chat_history'] = [];
    echo json_encode([
        'success' => true,
        'message' => 'Đã làm mới dòng thời gian vũ trụ thành công!'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Lấy lịch sử đoạn chat hiện thời
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

// Gửi câu hỏi và trò chuyện với AI
if ($action === 'send') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    $prompt = trim($input['message'] ?? $_POST['message'] ?? '');
    $selectedModel = trim($input['model'] ?? $_POST['model'] ?? DEFAULT_MODEL);
    if (empty($selectedModel)) $selectedModel = DEFAULT_MODEL;

    if (empty($prompt)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Vui lòng nhập nội dung câu hỏi hoặc yêu cầu cho Trợ lý Vũ Trụ!'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Khởi tạo lịch sử chat
    if (!isset($_SESSION['admin_cosmic_chat_history']) || !is_array($_SESSION['admin_cosmic_chat_history'])) {
        $_SESSION['admin_cosmic_chat_history'] = [];
    }

    // Lấy thông số hệ thống để nạp vào System Prompt
    $metrics = getSchoolRealtimeMetrics();
    $adminName = $_SESSION['ho_ten'] ?? 'Quản Trị Viên';
    $currentDate = date('d/m/Y H:i:s');
    $svStr = !empty($metrics['recent_students']) ? implode(', ', $metrics['recent_students']) : 'Lê Nhựt Khánh, Vũ Nhật Tường Vi';
    $gvStr = !empty($metrics['recent_teachers']) ? implode(', ', $metrics['recent_teachers']) : 'Thầy Khánh, Cô Tuyền';

    $systemInstruction = <<<SYS
Bạn là KIRA COSMIC AI - Trí Tuệ Nhân Tạo Tối Cao Phụ Trách Quản Trị Hệ Thống Trường Cao đẳng Việt - Hàn Cà Mau (Cosmic Admin Assistant).
Bạn đang giao tiếp trực tiếp với Quản Trị Viên Cấp Cao: {$adminName}.
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
3. Hỗ trợ soạn thảo đào tạo: Soạn thảo thông báo khẩn, quy chế đào tạo, đề cương môn học, tạo ngân hàng câu hỏi Quiz, kế hoạch tổ chức thi.
4. Phong cách phản hồi: Thông minh, chính xác, uyên bác, trang trọng nhưng hiện đại với phong thái Trí Tuệ Vũ Trụ (Cosmic AI), sử dụng icon vũ trụ (🪐, 🌌, 🚀, 🌠, 🛰️, 🔮, ⚡) một cách tinh tế. Trình bày định dạng Markdown rõ ràng, có tiêu đề, gạch đầu dòng, bảng số liệu hoặc khối mã lệnh khi cần thiết.
SYS;

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

    // Thêm tin nhắn người dùng hiện tại
    $messages[] = [
        'role' => 'user',
        'content' => $prompt
    ];

    $payload = [
        'model' => $selectedModel,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 3000
    ];

    $ch = curl_init(KIRA_BASE_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . KIRA_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $apiResponse = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErr) {
        http_response_code(502);
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi kết nối tới trạm AI Vũ Trụ: ' . $curlErr
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $responseData = json_decode($apiResponse, true);

    if ($httpCode !== 200 || !isset($responseData['choices'][0]['message']['content'])) {
        $errorMessage = $responseData['error']['message'] ?? $responseData['error'] ?? 'Trạm AI Kira phản hồi mã lỗi HTTP: ' . $httpCode;
        
        // Thử fallback sang GLM 4.7 Flash Free nếu model ban đầu gặp lỗi
        if ($selectedModel !== DEFAULT_MODEL) {
            $payload['model'] = DEFAULT_MODEL;
            $ch = curl_init(KIRA_BASE_URL);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . KIRA_API_KEY,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $fallbackRes = curl_exec($ch);
            $fbCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $fbData = json_decode($fallbackRes, true);
            if ($fbCode === 200 && isset($fbData['choices'][0]['message']['content'])) {
                $responseData = $fbData;
                $selectedModel = DEFAULT_MODEL . ' (Tự động chuyển tiếp dự phòng)';
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Không thể hoàn tất yêu cầu từ AI: ' . $errorMessage
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Không thể hoàn tất yêu cầu từ AI: ' . $errorMessage
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    $aiReply = trim($responseData['choices'][0]['message']['content']);
    $usage = $responseData['usage'] ?? null;

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
