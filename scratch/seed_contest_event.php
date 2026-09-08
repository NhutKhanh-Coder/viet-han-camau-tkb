<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<pre>\n=== SEEDING CONTEST TRIATHLON EVENT ===\n";

$contest_data = [
    'type' => 'contest_triathlon',
    'title' => "🏆 Cuộc Thi Đấu Trường Công Nghệ AI & Code 2026 — ThS. Lê Nhựt Khánh",
    'round1' => [
        'title' => "Vòng 1: Đố Vui Kiến Thức AI & Prompt Engineering",
        'question' => "Trong kỹ thuật Prompt Engineering, phương pháp nào yêu cầu người dùng cung cấp một vài cặp ví dụ mẫu (Input - Output) trước khi đặt câu hỏi chính để mô hình AI học theo ngữ cảnh trả lời?",
        'options' => [
            'A' => "Zero-shot Prompting (Không ví dụ)",
            'B' => "Few-shot Prompting (Cung cấp ví dụ mẫu)",
            'C' => "Chain-of-Thought thuần túy",
            'D' => "System Role Framing đơn thuần"
        ],
        'correct_answer' => 'B',
        'points' => 30,
        'explanation' => "Few-shot Prompting là kỹ thuật cung cấp các ví dụ mẫu cụ thể trong prompt giúp mô hình LLM hiểu chính xác định dạng và ngữ cảnh đầu ra mong muốn."
    ],
    'round2' => [
        'title' => "Vòng 2: Trắc Nghiệm Đọc Hiểu & Tối Ưu Mã Nguồn",
        'question' => "Cho đoạn mã JavaScript xử lý mảng bên dưới. Giá trị được in ra màn hình Console tại lệnh cuối cùng là bao nhiêu?",
        'code_snippet' => "const nums = [1, 2, 3, 4, 5];\nconst result = nums\n    .filter(x => x % 2 === 0)\n    .map(x => x * 10);\nconsole.log(result[0]);",
        'options' => [
            'A' => "10",
            'B' => "20",
            'C' => "4",
            'D' => "undefined"
        ],
        'correct_answer' => 'B',
        'points' => 30,
        'explanation' => "Mảng filter các số chẵn sẽ còn [2, 4]. Sau đó hàm map nhân 10 sẽ được [20, 40]. Do đó phần tử đầu tiên result[0] có giá trị là 20."
    ],
    'round3' => [
        'title' => "Vòng 3: Thực Hành Viết Code Giải Thuật Trực Tiếp",
        'task' => "Hoàn thiện hàm tinhTongSoChan(n) nhận vào số nguyên dương n.\nHàm cần trả về tổng của tất cả các số chẵn từ 1 đến n.\nVí dụ:\n- Với n = 6: Các số chẵn là 2, 4, 6 -> Kết quả trả về 12.\n- Với n = 10: Các số chẵn là 2, 4, 6, 8, 10 -> Kết quả trả về 30.",
        'lang' => 'javascript',
        'starter_code' => "function tinhTongSoChan(n) {\n    let tong = 0;\n    // Viết code xử lý của bạn ở đây:\n    for (let i = 1; i <= n; i++) {\n        if (i % 2 === 0) {\n            tong += i;\n        }\n    }\n    return tong;\n}\n\n// Chạy thử kiểm tra:\nconsole.log('Kết quả với n = 6:', tinhTongSoChan(6));\nconsole.log('Kết quả với n = 10:', tinhTongSoChan(10));",
        'expected_output' => "12",
        'test_cases' => "tinhTongSoChan(6) === 12 && tinhTongSoChan(10) === 30",
        'points' => 40
    ],
    'prizes' => [
        'first' => [
            'name' => "🥇 GIẢI NHẤT: Tài Khoản ChatGPT Plus GPT-4o 1 Năm VIP",
            'data' => "Tài khoản ChatGPT Plus: chatgpt.first.k24@viethan-mmo.edu.vn | Mật khẩu: FirstPrize@2026 | Kích hoạt GPT-4o Plus 1 năm (Tài trợ độc quyền bởi ThS. Lê Nhựt Khánh)"
        ],
        'second' => [
            'name' => "🥈 GIẢI NHÌ: Key Bản Quyền Cursor AI Pro 1 Năm",
            'data' => "Cursor Pro Key: CURSOR-PRO-SECOND-VKC-2026-LENHUTKHANH | Nhập key tại cursor.com -> Settings -> Billing -> Redeem Code (Bảo hành 1 năm bởi ThS. Lê Nhựt Khánh)"
        ],
        'third' => [
            'name' => "🥉 GIẢI BA: Tài Khoản GitHub Copilot Pro 6 Tháng",
            'data' => "Tài khoản GitHub: copilot.third.k24@viethan-mmo.edu.vn | Mật khẩu: ThirdPrize@2026 | Đăng nhập github.com và kích hoạt trên VS Code (Tài trợ bởi ThS. Lê Nhựt Khánh)"
        ]
    ]
];

$ch_json = $db->real_escape_string(json_encode($contest_data, JSON_UNESCAPED_UNICODE));
$title = "🏆 Đấu Trường Công Nghệ AI & Code 2026 — ThS. Lê Nhựt Khánh";
$desc  = "Cuộc thi liên hoàn 3 vòng: Vòng 1 (Đố vui AI) -> Vòng 2 (Trắc nghiệm Code) -> Vòng 3 (Live Code IDE). Admin Thầy Khánh chấm điểm và tổng hợp xếp hạng trao giải Nhất, Nhì, Ba!";
$banner = "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=1000&auto=format&fit=crop";
$gift_name = "Cơ cấu giải: 🥇 Nhất (ChatGPT Plus) - 🥈 Nhì (Cursor Pro) - 🥉 Ba (Copilot Pro)";
$gift_info = "Trao thưởng trực tiếp theo bảng xếp hạng tổng điểm 3 vòng thi bởi ThS. Lê Nhựt Khánh";

// Kiểm tra xem đã có event contest_triathlon chưa
$chk = $db->query("SELECT id FROM mmo_events WHERE event_type = 'contest_triathlon' OR claim_type = 'contest_triathlon' LIMIT 1");
if ($chk && $row = $chk->fetch_assoc()) {
    $eid = (int)$row['id'];
    $db->query("UPDATE mmo_events SET title = '$title', description = '$desc', banner_url = '$banner', gift_name = '$gift_name', custom_gift_info = '$gift_info', reward_name = '$gift_name', reward_data = '$gift_info', status = 'active', challenge_data = '$ch_json' WHERE id = $eid");
    echo "Updated existing Contest Triathlon Event ID: $eid\n";
} else {
    $db->query("INSERT INTO mmo_events 
        (title, description, banner_url, gift_type, custom_gift_info, gift_name, claim_type, total_gifts, status, creator_name, event_type, reward_name, reward_data, max_claims, challenge_data) 
        VALUES 
        ('$title', '$desc', '$banner', 'custom', '$gift_info', '$gift_name', 'contest_triathlon', 50, 'active', 'ThS. Lê Nhựt Khánh', 'contest_triathlon', '$gift_name', '$gift_info', 50, '$ch_json')");
    echo "Created Contest Triathlon Event ID: " . $db->insert_id . "\n";
}

echo "=== SEEDING COMPLETED ===\n</pre>";
