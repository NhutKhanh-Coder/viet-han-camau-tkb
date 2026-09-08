<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<pre>\n=== ALTER TABLE mmo_events ===\n";
// Add gift_type & coupon_code if missing
$cols = [];
$resCols = $db->query("SHOW COLUMNS FROM mmo_events");
while ($c = $resCols->fetch_assoc()) {
    $cols[] = $c['Field'];
}

if (!in_array('gift_type', $cols)) {
    $db->query("ALTER TABLE mmo_events ADD COLUMN gift_type VARCHAR(50) DEFAULT 'custom' AFTER banner_url");
    echo "Added gift_type\n";
}
if (!in_array('coupon_code', $cols)) {
    $db->query("ALTER TABLE mmo_events ADD COLUMN coupon_code VARCHAR(100) NULL AFTER store_id");
    echo "Added coupon_code\n";
}

echo "\n=== INSERTING CHALLENGE EVENTS ===\n";

// 1. Quiz AI
$ai_data = json_encode([
    'type' => 'quiz_ai',
    'question' => "Trong kỹ thuật Prompt Engineering, phương pháp nào yêu cầu người dùng cung cấp một vài ví dụ mẫu (Input - Output) trước khi đặt câu hỏi chính để mô hình AI học theo ngữ cảnh?",
    'options' => [
        'A' => "Zero-shot Prompting (Không cần ví dụ)",
        'B' => "Few-shot Prompting (Cung cấp ví dụ mẫu)",
        'C' => "Chain-of-Thought không có ví dụ",
        'D' => "System Role Framing đơn thuần"
    ],
    'correct_answer' => 'B',
    'explanation' => "Few-shot Prompting là kỹ thuật cung cấp 1 hoặc nhiều cặp ví dụ minh họa trong prompt để định hướng cách thức và văn phong trả lời cho LLM."
], JSON_UNESCAPED_UNICODE);

$title1 = "🧠 Đố Vui Kiến Thức AI 2026 — Nhận Key Gemini Advanced 1 Năm";
$desc1  = "Trả lời chính xác câu hỏi về Prompt Engineering & AI để mở khóa ngay tài khoản Gemini Advanced bản quyền 1 năm tài trợ bởi Thầy Lê Nhựt Khánh.";
$gift1  = "Tài Khoản Gemini Advanced (Google One AI 2TB) 1 Năm";
$info1  = "Tài khoản: gemini.k24@viethan-mmo.edu.vn | Pass: GeminiKhanh@2026 | Kích hoạt Google One AI Premium 2TB & Gemini Advanced 2.0 (Bảo hành 1 năm bởi ThS. Lê Nhựt Khánh)";

$q1 = "INSERT INTO mmo_events (title, description, banner_url, gift_type, custom_gift_info, gift_name, claim_type, total_gifts, status, creator_name, event_type, reward_name, reward_data, max_claims, challenge_data) VALUES ('" . $db->real_escape_string($title1) . "', '" . $db->real_escape_string($desc1) . "', 'https://images.unsplash.com/photo-1677442136019-21780efad99a?w=800&auto=format&fit=crop', 'custom', '" . $db->real_escape_string($info1) . "', '" . $db->real_escape_string($gift1) . "', 'quiz_ai', 60, 'active', 'ThS. Lê Nhựt Khánh', 'quiz_ai', '" . $db->real_escape_string($gift1) . "', '" . $db->real_escape_string($info1) . "', 60, '" . $db->real_escape_string($ai_data) . "')";
$ok1 = $db->query($q1);
echo "Quiz AI: " . ($ok1 ? "SUCCESS (ID: " . $db->insert_id . ")" : "FAIL: " . $db->error) . "\n";

// 2. Quiz Code
$code_data = json_encode([
    'type' => 'quiz_code',
    'question' => "Cho đoạn mã JavaScript xử lý mảng sau. Giá trị được in ra màn hình Console là bao nhiêu?",
    'code_snippet' => "const nums = [1, 2, 3, 4, 5];\nconst result = nums\n    .filter(x => x % 2 === 0)\n    .map(x => x * 10);\nconsole.log(result[0]);",
    'options' => [
        'A' => "10",
        'B' => "20",
        'C' => "4",
        'D' => "undefined"
    ],
    'correct_answer' => 'B',
    'explanation' => "Mảng filter các số chẵn được [2, 4], sau đó map nhân 10 được [20, 40]. Do đó result[0] có giá trị là 20."
], JSON_UNESCAPED_UNICODE);

$title2 = "💻 Trắc Nghiệm Lập Trình Web — Rinh Bản Quyền GitHub Copilot Pro";
$desc2  = "Kiểm tra kiến thức JavaScript ES6+ array methods. Phân tích đúng kết quả đoạn mã để rinh tài khoản GitHub Copilot Pro lập trình thông minh!";
$gift2  = "Tài Khoản GitHub Copilot Pro Kèm Mã Kích Hoạt IDE";
$info2  = "Tài khoản GitHub: copilot.k24@viethan-mmo.edu.vn | Pass: GithubPro@2026 | Đăng nhập tại github.com và kích hoạt trên VS Code.";

$q2 = "INSERT INTO mmo_events (title, description, banner_url, gift_type, custom_gift_info, gift_name, claim_type, total_gifts, status, creator_name, event_type, reward_name, reward_data, max_claims, challenge_data) VALUES ('" . $db->real_escape_string($title2) . "', '" . $db->real_escape_string($desc2) . "', 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=800&auto=format&fit=crop', 'custom', '" . $db->real_escape_string($info2) . "', '" . $db->real_escape_string($gift2) . "', 'quiz_code', 50, 'active', 'ThS. Lê Nhựt Khánh', 'quiz_code', '" . $db->real_escape_string($gift2) . "', '" . $db->real_escape_string($info2) . "', 50, '" . $db->real_escape_string($code_data) . "')";
$ok2 = $db->query($q2);
echo "Quiz Code: " . ($ok2 ? "SUCCESS (ID: " . $db->insert_id . ")" : "FAIL: " . $db->error) . "\n";

// 3. Code Challenge
$challenge_data = json_encode([
    'type' => 'code_challenge',
    'title' => "Viết hàm tính tổng các số chẵn từ 1 đến N",
    'task' => "Hoàn thiện hàm tinhTongSoChan(n) nhận vào số nguyên dương n.\nHàm trả về tổng của tất cả các số chẵn từ 1 đến n.\nVí dụ:\n- n = 6: Các số chẵn là 2, 4, 6 -> Kết quả trả về 12.\n- n = 10: Các số chẵn là 2, 4, 6, 8, 10 -> Kết quả trả về 30.",
    'lang' => 'javascript',
    'starter_code' => "function tinhTongSoChan(n) {\n    let tong = 0;\n    // Viết code xử lý của bạn ở đây:\n    for (let i = 1; i <= n; i++) {\n        if (i % 2 === 0) {\n            tong += i;\n        }\n    }\n    return tong;\n}\n\n// Chạy thử kiểm tra:\nconsole.log('Tổng số chẵn với n = 6 là:', tinhTongSoChan(6));\nconsole.log('Tổng số chẵn với n = 10 là:', tinhTongSoChan(10));",
    'expected_output' => "12",
    'test_cases' => "tinhTongSoChan(6) === 12 && tinhTongSoChan(10) === 30"
], JSON_UNESCAPED_UNICODE);

$title3 = "⚡ Thực Hành Viết Code — Nhận Key Bản Quyền Cursor AI Pro VIP";
$desc3  = "Thực hành giải thuật toán cơ bản bằng trình biên dịch code trực tiếp. Chạy code đạt chuẩn test case của Thầy Khánh để nhận key Cursor AI Pro xịn sò!";
$gift3  = "License Key Bản Quyền Cursor AI Pro 6 Tháng";
$info3  = "Cursor Pro Key: CURSOR-PRO-VKC-2026-NHUTKHANH-AI-DEV | Nhập key tại cursor.com -> Settings -> Billing -> Redeem Code (Bảo hành 6 tháng).";

$q3 = "INSERT INTO mmo_events (title, description, banner_url, gift_type, custom_gift_info, gift_name, claim_type, total_gifts, status, creator_name, event_type, reward_name, reward_data, max_claims, challenge_data) VALUES ('" . $db->real_escape_string($title3) . "', '" . $db->real_escape_string($desc3) . "', 'https://images.unsplash.com/photo-1542831371-29b0f74f9713?w=800&auto=format&fit=crop', 'custom', '" . $db->real_escape_string($info3) . "', '" . $db->real_escape_string($gift3) . "', 'code_challenge', 40, 'active', 'ThS. Lê Nhựt Khánh', 'code_challenge', '" . $db->real_escape_string($gift3) . "', '" . $db->real_escape_string($info3) . "', 40, '" . $db->real_escape_string($challenge_data) . "')";
$ok3 = $db->query($q3);
echo "Code Challenge: " . ($ok3 ? "SUCCESS (ID: " . $db->insert_id . ")" : "FAIL: " . $db->error) . "\n";

// 4. Lucky Wheel
$title4 = "🎡 Vòng Quay May Mắn MMO — Quay Là Trúng Quà 100%";
$desc4  = "Mỗi sinh viên được tặng 1 lượt quay vòng quay may mắn hoàn toàn miễn phí từ Thầy Lê Nhựt Khánh. Cơ hội trúng ngay tài khoản ChatGPT Plus, Canva Pro, CapCut Pro!";
$gift4  = "Tài Khoản ChatGPT Plus GPT-4o Bản Quyền VIP";
$info4  = "Tài khoản ChatGPT: chatgpt.vip.k24@viethan-mmo.edu.vn | Pass: ChatGptPro@2026 | Kích hoạt GPT-4o, DALL-E 3, Voice Mode (Tài trợ bởi ThS. Lê Nhựt Khánh)";
$ok4 = $db->query("INSERT INTO mmo_events (title, description, banner_url, gift_type, custom_gift_info, gift_name, claim_type, total_gifts, status, creator_name, event_type, reward_name, reward_data, max_claims) VALUES ('" . $db->real_escape_string($title4) . "', '" . $db->real_escape_string($desc4) . "', 'https://images.unsplash.com/photo-1513151233558-d860c5398176?w=800&auto=format&fit=crop', 'custom', '" . $db->real_escape_string($info4) . "', '" . $db->real_escape_string($gift4) . "', 'wheel', 100, 'active', 'ThS. Lê Nhựt Khánh', 'wheel', '" . $db->real_escape_string($gift4) . "', '" . $db->real_escape_string($info4) . "', 100)");
echo "Lucky Wheel: " . ($ok4 ? "SUCCESS (ID: " . $db->insert_id . ")" : "FAIL: " . $db->error) . "\n";

// 5. Giftcode
$title5 = "🔑 Nhập Giftcode Thầy Khánh Tặng — Nhận Tài Khoản Canva Pro";
$desc5  = "Nhập mã Giftcode bí mật [LENHUTKHANH_PRO] được Thầy Khánh chia sẻ để mở khóa gói Canva Pro Edu Full Tính Năng vĩnh viễn!";
$gift5  = "Tài Khoản Canva Pro Edu Không Giới Hạn Bản Quyền";
$info5  = "Canva Pro Invite Link: https://canva.com/brand/join?token=VKC_CANVA_PRO_2026_LENHUTKHANH | Bấm link đăng nhập bằng email trường để nâng cấp Pro ngay.";
$ok5 = $db->query("INSERT INTO mmo_events (title, description, banner_url, gift_type, custom_gift_info, gift_name, claim_type, secret_code, total_gifts, status, creator_name, event_type, reward_name, reward_data, max_claims) VALUES ('" . $db->real_escape_string($title5) . "', '" . $db->real_escape_string($desc5) . "', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop', 'custom', '" . $db->real_escape_string($info5) . "', '" . $db->real_escape_string($gift5) . "', 'secret_code', 'LENHUTKHANH_PRO', 100, 'active', 'ThS. Lê Nhựt Khánh', 'code', '" . $db->real_escape_string($gift5) . "', '" . $db->real_escape_string($info5) . "', 100)");
echo "Giftcode: " . ($ok5 ? "SUCCESS (ID: " . $db->insert_id . ")" : "FAIL: " . $db->error) . "\n";

echo "=== FINISHED ===\n</pre>";
