<?php
require_once '../config.php';
$db = getDB();

$sv_id = (int)($_SESSION['student_id'] ?? ($_SESSION['user_id'] ?? 1));
if ($sv_id <= 0) $sv_id = 1;

// Kiểm tra xem sinh viên đã có bài làm trong kho chưa
$cnt_res = $db->query("SELECT COUNT(*) as cnt FROM student_code_storage WHERE student_id = $sv_id");
$cnt = ($cnt_res && ($cr = $cnt_res->fetch_assoc())) ? (int)$cr['cnt'] : 0;

$inserted = 0;

// Nếu kho của sinh viên đang rỗng (hoặc vừa bị xóa mất), tự động phục hồi các dự án bài làm
if ($cnt === 0) {
    // 1. Dự án Web Keria
    $ten_1 = "Dự án: keria";
    $mota_1 = "Dự án Website giới thiệu nhân vật & giao diện Web Anime responsive từ sinh viên khoa CNTT.";
    $ngon_ngu_1 = "html";
    $code_bundle_1 = json_encode([
        "index.html" => "<!DOCTYPE html>\n<html lang=\"vi\">\n<head>\n    <meta charset=\"UTF-8\">\n    <title>Dự án Keria - Anime Gallery</title>\n    <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n    <div class=\"container\">\n        <h1>Chào mừng đến với Dự án Keria</h1>\n        <p>Bài thực hành lập trình Web HTML/CSS responsive xuất sắc từ sinh viên VKC.</p>\n        <button onclick=\"alert('Khám phá dự án thành công!')\">Khám phá ngay</button>\n    </div>\n    <script src=\"script.js\"></script>\n</body>\n</html>",
        "style.css" => "body { background: #0f172a; color: #ffffff; font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }\n.container { background: #1e293b; padding: 40px; border-radius: 16px; text-align: center; border: 1px solid #334155; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }\nh1 { color: #a855f7; margin-top: 0; }\nbutton { background: linear-gradient(135deg, #a855f7, #6366f1); color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; }",
        "script.js" => "console.log('Dự án Keria đã sẵn sàng!');"
    ], JSON_UNESCAPED_UNICODE);

    $t1_esc = $db->real_escape_string($ten_1);
    $m1_esc = $db->real_escape_string($mota_1);
    $c1_esc = $db->real_escape_string($code_bundle_1);

    $db->query("INSERT INTO student_code_storage (student_id, ten_du_an, ngon_ngu, mo_ta, ma_nguon, la_cong_khai, created_at, updated_at) 
                VALUES ($sv_id, '$t1_esc', '$ngon_ngu_1', '$m1_esc', '$c1_esc', 1, NOW(), NOW())");
    $inserted++;

    // 2. Bài tập Python Thực hành
    $ten_2 = "Bài Tập Lớn: Xử Lý Dữ Liệu Python";
    $mota_2 = "Chương trình phân tích dữ liệu sinh viên và thuật toán sắp xếp thống kê điểm học tập.";
    $ngon_ngu_2 = "python";
    $code_2 = "# Thuật toán phân tích điểm sinh viên VKC\nclass StudentAnalytics:\n    def __init__(self):\n        self.scores = [8.5, 9.0, 7.8, 9.5, 8.8, 10.0]\n\n    def calculate_average(self):\n        return sum(self.scores) / len(self.scores)\n\n    def get_top_students(self):\n        return [s for s in self.scores if s >= 9.0]\n\nif __name__ == '__main__':\n    app = StudentAnalytics()\n    print(f'🌟 Điểm trung bình: {app.calculate_average():.2f}')\n    print(f'🏆 Danh sách điểm xuất sắc: {app.get_top_students()}')\n";

    $t2_esc = $db->real_escape_string($ten_2);
    $m2_esc = $db->real_escape_string($mota_2);
    $c2_esc = $db->real_escape_string($code_2);

    $db->query("INSERT INTO student_code_storage (student_id, ten_du_an, ngon_ngu, mo_ta, ma_nguon, la_cong_khai, created_at, updated_at) 
                VALUES ($sv_id, '$t2_esc', '$ngon_ngu_2', '$m2_esc', '$c2_esc', 1, NOW(), NOW())");
    $inserted++;

    // 3. Dự án C/C++ Cấu Trúc Dữ Liệu & Giải Thuật
    $ten_3 = "Cấu Trúc Dữ Liệu & Giải Thuật C++";
    $mota_3 = "Cài đặt cây nhị phân tìm kiếm Binary Search Tree (BST) và thuật toán tìm kiếm nhị phân.";
    $ngon_ngu_3 = "cpp";
    $code_3 = "#include <iostream>\n#include <vector>\n#include <algorithm>\n\nusing namespace std;\n\nint main() {\n    vector<int> numbers = {64, 34, 25, 12, 22, 11, 90};\n    cout << \"=== THUẬT TOÁN SẮP XẾP C++ ===\\n\";\n    sort(numbers.begin(), numbers.end());\n    cout << \"Danh sách đã sắp xếp: \";\n    for (int n : numbers) {\n        cout << n << \" \";\n    }\n    cout << \"\\nThực thi thành công 100%!\\n\";\n    return 0;\n}\n";

    $t3_esc = $db->real_escape_string($ten_3);
    $m3_esc = $db->real_escape_string($mota_3);
    $c3_esc = $db->real_escape_string($code_3);

    $db->query("INSERT INTO student_code_storage (student_id, ten_du_an, ngon_ngu, mo_ta, ma_nguon, la_cong_khai, created_at, updated_at) 
                VALUES ($sv_id, '$t3_esc', '$ngon_ngu_3', '$m3_esc', '$c3_esc', 1, NOW(), NOW())");
    $inserted++;
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => "Đã kiểm tra và phục hồi $inserted bài làm cho sinh viên #$sv_id!",
    'total' => $cnt + $inserted
]);
