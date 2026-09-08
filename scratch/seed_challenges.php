<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<pre>\n=== SEEDING CHALLENGE EVENTS FOR THS. LE NHUT KHANH ===\n";

// 1. Event: Câu hỏi kiến thức AI
$chk1 = $db->query("SELECT id FROM mmo_events WHERE claim_type = 'quiz_ai' OR event_type = 'quiz_ai' LIMIT 1");
if (!$chk1 || $chk1->num_rows === 0) {
    $ai_challenge = [
        'type' => 'quiz_ai',
        'question' => "Trong kỹ thuật Prompt Engineering, phương pháp nào yêu cầu người dùng cung cấp một vài ví dụ mẫu (Input - Output) trước khi đặt câu hỏi chính để mô hình AI học theo ngữ cảnh?",
        'options' => [
            'A' => "Zero-shot Prompting (Không cần ví dụ)",
            'B' => "Few-shot Prompting (Cung cấp ví dụ mẫu)",
            'C' => "Chain-of-Thought không có ví dụ",
            'D' => "System Role Framing đơn thuần"
        ],
        'correct_answer' => 'B',
        'explanation' => "Few-shot Prompting là kỹ thuật cung cấp 1 hoặc nhiều cặp ví dụ minh họa (kèm đáp án mẫu) trong prompt để định hướng văn phong và cấu trúc trả lời cho LLM."
    ];
    $ch_json1 = $db->real_escape_string(json_encode($ai_challenge, JSON_UNESCAPED_UNICODE));
    $title1 = "🧠 Đố Vui Kiến Thức AI 2026 — Nhận Key Gemini Advanced 1 Năm";
    $desc1 = "Trả lời chính xác câu hỏi về kỹ thuật Prompt Engineering & Trí tuệ nhân tạo để mở khóa ngay tài khoản Gemini Advanced bản quyền 1 năm tài trợ bởi Thầy Lê Nhựt Khánh.";
    $gift1 = "Tài Khoản Gemini Advanced (Google One AI 2TB) 1 Năm";
    $info1 = "Tài khoản: gemini.ai.k24@viethan-mmo.edu.vn | Pass: GeminiKhanh@2026 | Kích hoạt Google One AI Premium 2TB & Gemini Advanced 2.0 (Bảo hành 1 năm bởi ThS. Lê Nhựt Khánh)";
    
    $db->query("INSERT INTO mmo_events 
        (title, description, banner_url, gift_type, custom_gift_info, gift_name, claim_type, total_gifts, status, creator_name, event_type, reward_name, reward_data, max_claims, challenge_data) 
        VALUES 
        ('$title1', '$desc1', 'https://images.unsplash.com/photo-1677442136019-21780efad99a?w=800&auto=format&fit=crop', 'custom', '$info1', '$gift1', 'quiz_ai', 60, 'active', 'ThS. Lê Nhựt Khánh', 'quiz_ai', '$gift1', '$info1', 60, '$ch_json1')");
    echo "Created Event: Quiz AI (ID: " . $db->insert_id . ")\n";
} else {
    echo "Quiz AI Event already exists.\n";
}

// 2. Event: Trắc nghiệm lập trình
$chk2 = $db->query("SELECT id FROM mmo_events WHERE claim_type = 'quiz_code' OR event_type = 'quiz_code' LIMIT 1");
if (!$chk2 || $chk2->num_rows === 0) {
    $code_challenge = [
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
        'explanation' => "Mảng ban đầu filter các số chẵn sẽ còn [2, 4]. Sau đó map nhân 10 sẽ được [20, 40]. Do đó result[0] mang giá trị là 20."
    ];
    $ch_json2 = $db->real_escape_string(json_encode($code_challenge, JSON_UNESCAPED_UNICODE));
    $title2 = "💻 Trắc Nghiệm Lập Trình Web — Rinh Bản Quyền GitHub Copilot Pro";
    $desc2 = "Kiểm tra kiến thức JavaScript hiện đại (ES6+ array methods). Phân tích đúng kết quả đoạn mã để rinh ngay tài khoản GitHub Copilot Pro lập trình thông minh!";
    $gift2 = "Tài Khoản GitHub Copilot Pro Kèm Mã Kích Hoạt IDE";
    $info2 = "Tài khoản GitHub: copilot.k24@viethan-mmo.edu.vn | Pass: GithubPro@2026 | Hướng dẫn: Đăng nhập tại github.com và bật extension GitHub Copilot trên VS Code.";
    
    $db->query("INSERT INTO mmo_events 
        (title, description, banner_url, gift_type, custom_gift_info, gift_name, claim_type, total_gifts, status, creator_name, event_type, reward_name, reward_data, max_claims, challenge_data) 
        VALUES 
        ('$title2', '$desc2', 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=800&auto=format&fit=crop', 'custom', '$info2', '$gift2', 'quiz_code', 50, 'active', 'ThS. Lê Nhựt Khánh', 'quiz_code', '$gift2', '$info2', 50, '$ch_json2')");
    echo "Created Event: Quiz Code (ID: " . $db->insert_id . ")\n";
} else {
    echo "Quiz Code Event already exists.\n";
}

// 3. Event: Thực hành viết code
$chk3 = $db->query("SELECT id FROM mmo_events WHERE claim_type = 'code_challenge' OR event_type = 'code_challenge' LIMIT 1");
if (!$chk3 || $chk3->num_rows === 0) {
    $live_code = [
        'type' => 'code_challenge',
        'title' => "Viết hàm tính tổng các số chẵn từ 1 đến N",
        'task' => "Hoàn thiện hàm tinhTongSoChan(n) nhận vào một số nguyên dương n. Hàm cần trả về tổng của tất cả các số chẵn từ 1 đến n.\nVí dụ:\n- Với n = 6: Các số chẵn là 2, 4, 6 -> Kết quả trả về 12.\n- Với n = 10: Các số chẵn là 2, 4, 6, 8, 10 -> Kết quả trả về 30.",
        'lang' => 'javascript',
        'starter_code' => "function tinhTongSoChan(n) {\n    let tong = 0;\n    // Viết code xử lý của bạn ở đây:\n    for (let i = 1; i <= n; i++) {\n        if (i % 2 === 0) {\n            tong += i;\n        }\n    }\n    return tong;\n}\n\n// Chạy thử kiểm tra:\nconsole.log('Kết quả với n = 6:', tinhTongSoChan(6));\nconsole.log('Kết quả với n = 10:', tinhTongSoChan(10));",
        'expected_output' => "12",
        'test_cases' => "tinhTongSoChan(6) === 12 && tinhTongSoChan(10) === 30"
    ];
    $ch_json3 = $db->real_escape_string(json_encode($live_code, JSON_UNESCAPED_UNICODE));
    $title3 = "⚡ Thực Hành Viết Code — Nhận Key Bản Quyền Cursor AI Pro VIP";
    $desc3 = "Thực hành giải thuật toán cơ bản bằng trình biên dịch code trực tiếp. Chạy code đạt chuẩn test case của Thầy Khánh để nhận ngay key Cursor AI Pro xịn sò!";
    $gift3 = "License Key Bản Quyền Cursor AI Pro 6 Tháng";
    $info3 = "Cursor Pro Key: CURSOR-PRO-VKC-2026-NHUTKHANH-AI-DEV | Đăng ký tại cursor.com | Nhập key tại mục Settings -> Billing -> Redeem Code (Bảo hành 6 tháng).";
    
    $db->query("INSERT INTO mmo_events 
        (title, description, banner_url, gift_type, custom_gift_info, gift_name, claim_type, total_gifts, status, creator_name, event_type, reward_name, reward_data, max_claims, challenge_data) 
        VALUES 
        ('$title3', '$desc3', 'https://images.unsplash.com/photo-1542831371-29b0f74f9713?w=800&auto=format&fit=crop', 'custom', '$info3', '$gift3', 'code_challenge', 40, 'active', 'ThS. Lê Nhựt Khánh', 'code_challenge', '$gift3', '$info3', 40, '$ch_json3')");
    echo "Created Event: Code Challenge (ID: " . $db->insert_id . ")\n";
} else {
    echo "Code Challenge Event already exists.\n";
}

echo "=== SEEDING COMPLETED ===\n</pre>";
