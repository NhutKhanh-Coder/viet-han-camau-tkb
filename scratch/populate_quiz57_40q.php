<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
header('Content-Type: text/plain; charset=utf-8');
echo "=== RE-POPULATING QUIZ 57 WITH 40 QUESTIONS ===\n";

$qCheck = $db->query("SELECT id, tieu_de FROM quizzes WHERE id = 57");
if (!$qCheck || $qCheck->num_rows === 0) {
    die("ERROR: Quiz 57 not found in database!\n");
}
$quizRow = $qCheck->fetch_assoc();
echo "Target Quiz: ID {$quizRow['id']} - {$quizRow['tieu_de']}\n";

// Clear existing questions and attempts
$db->query("DELETE FROM quiz_questions WHERE quiz_id = 57");
echo "Cleared old questions. Affected: " . $db->affected_rows . "\n";
$db->query("DELETE FROM quiz_attempts WHERE quiz_id = 57");
echo "Cleared old attempts. Affected: " . $db->affected_rows . "\n";

$questions = array (
  0 => 
  array (
    'cau_hoi' => 'Visual Studio .Net là:',
    'dap_an_a' => 'Hệ điều hành mới của Microsoft',
    'dap_an_b' => 'Trình soạn thảo văn bản',
    'dap_an_c' => 'Môi trường phát triển tích hợp (IDE)',
    'dap_an_d' => 'Trình duyệt web',
    'dap_an_dung' => 'C',
  ),
  1 => 
  array (
    'cau_hoi' => 'Phiên bản nào của Visual Studio .Net hỗ trợ phát triển ứng dụng đa nền tảng?',
    'dap_an_a' => 'Visual Studio 2010',
    'dap_an_b' => 'Visual Studio 2013',
    'dap_an_c' => 'Visual Studio 2017',
    'dap_an_d' => 'Visual Studio 2015',
    'dap_an_dung' => 'C',
  ),
  2 => 
  array (
    'cau_hoi' => 'Trong quá trình cài đặt Visual Studio .Net, bước nào sau đây là cần thiết?',
    'dap_an_a' => 'Chọn các ngôn ngữ lập trình và công cụ phát triển',
    'dap_an_b' => 'Cài đặt thư viện Python',
    'dap_an_c' => 'Cấu hình trình duyệt web',
    'dap_an_d' => 'Cập nhật trình điều khiển đồ họa',
    'dap_an_dung' => 'A',
  ),
  3 => 
  array (
    'cau_hoi' => 'Thành phần nào bắt buộc phải cài đặt để Visual Studio .Net hoạt động đúng cách?',
    'dap_an_a' => '.Net Framework',
    'dap_an_b' => 'SQL Server',
    'dap_an_c' => 'Microsoft Office',
    'dap_an_d' => 'Adobe Flash',
    'dap_an_dung' => 'A',
  ),
  4 => 
  array (
    'cau_hoi' => 'Phiên bản miễn phí của Visual Studio .Net được gọi là:',
    'dap_an_a' => 'Visual Studio Community',
    'dap_an_b' => 'Visual Studio Professional',
    'dap_an_c' => 'Visual Studio Ultimate',
    'dap_an_d' => 'Visual Studio Code',
    'dap_an_dung' => 'A',
  ),
  5 => 
  array (
    'cau_hoi' => 'Trong quá trình cài đặt Visual Studio .Net, tùy chọn Modify cho phép:',
    'dap_an_a' => 'Gỡ bỏ Visual Studio',
    'dap_an_b' => 'Thêm hoặc bớt các thành phần sau khi cài đặt',
    'dap_an_c' => 'Tăng dung lượng ổ đĩa',
    'dap_an_d' => 'Thay đổi ngôn ngữ lập trình',
    'dap_an_dung' => 'B',
  ),
  6 => 
  array (
    'cau_hoi' => 'Môi trường phát triển tích hợp (IDE) trong Visual Studio .Net bao gồm các công cụ chính nào?',
    'dap_an_a' => 'Trình soạn thảo mã, trình gỡ lỗi, và trình biên dịch',
    'dap_an_b' => 'Trình phát video và nhạc',
    'dap_an_c' => 'Trình quản lý file hệ thống',
    'dap_an_d' => 'Trình duyệt web',
    'dap_an_dung' => 'A',
  ),
  7 => 
  array (
    'cau_hoi' => 'Trong Visual Studio, cửa sổ nào hiển thị các lỗi sau khi biên dịch?',
    'dap_an_a' => 'Solution Explorer',
    'dap_an_b' => 'Error List',
    'dap_an_c' => 'Output',
    'dap_an_d' => 'Properties',
    'dap_an_dung' => 'B',
  ),
  8 => 
  array (
    'cau_hoi' => 'Solution Explorer trong Visual Studio .Net có vai trò gì?',
    'dap_an_a' => 'Quản lý các tệp và dự án trong giải pháp',
    'dap_an_b' => 'Hiển thị mã nguồn',
    'dap_an_c' => 'Tạo và sửa lỗi lập trình',
    'dap_an_d' => 'Quản lý cơ sở dữ liệu',
    'dap_an_dung' => 'A',
  ),
  9 => 
  array (
    'cau_hoi' => 'Khi lập trình trong Visual Studio, bạn có thể dùng phím tắt nào để chạy chương trình?',
    'dap_an_a' => 'Ctrl + F4',
    'dap_an_b' => 'F5',
    'dap_an_c' => 'F12',
    'dap_an_d' => 'Alt + Tab',
    'dap_an_dung' => 'B',
  ),
  10 => 
  array (
    'cau_hoi' => 'Cửa sổ Properties trong Visual Studio dùng để:',
    'dap_an_a' => 'Hiển thị và chỉnh sửa các thuộc tính của đối tượng được chọn',
    'dap_an_b' => 'Quản lý các thư viện mã nguồn',
    'dap_an_c' => 'Hiển thị kết quả biên dịch',
    'dap_an_d' => 'Kiểm tra lỗi lập trình',
    'dap_an_dung' => 'A',
  ),
  11 => 
  array (
    'cau_hoi' => '.NET Framework là một:',
    'dap_an_a' => 'Bộ thư viện mã nguồn mở',
    'dap_an_b' => 'Nền tảng phát triển ứng dụng đa ngôn ngữ',
    'dap_an_c' => 'Trình duyệt web',
    'dap_an_d' => 'Phần mềm diệt virus',
    'dap_an_dung' => 'B',
  ),
  12 => 
  array (
    'cau_hoi' => '.NET Framework hoạt động trên hệ điều hành nào?',
    'dap_an_a' => 'Chỉ Windows',
    'dap_an_b' => 'Windows và macOS',
    'dap_an_c' => 'Đa nền tảng (Windows, macOS, và Linux)',
    'dap_an_d' => 'Chỉ Linux',
    'dap_an_dung' => 'A',
  ),
  13 => 
  array (
    'cau_hoi' => '.NET Framework hỗ trợ những ngôn ngữ lập trình nào sau đây?',
    'dap_an_a' => 'C# và VB.Net',
    'dap_an_b' => 'Python và Java',
    'dap_an_c' => 'HTML và CSS',
    'dap_an_d' => 'SQL và MongoDB',
    'dap_an_dung' => 'A',
  ),
  14 => 
  array (
    'cau_hoi' => 'Để tạo một Project mới trong VB.Net, ta cần thực hiện thao tác nào đầu tiên?',
    'dap_an_a' => 'Chọn File > New > Project',
    'dap_an_b' => 'Chọn View > Toolbox',
    'dap_an_c' => 'Chọn Edit > New File',
    'dap_an_d' => 'Chọn Tools > Options',
    'dap_an_dung' => 'A',
  ),
  15 => 
  array (
    'cau_hoi' => 'Khi tạo Project mới trong VB.Net, bạn cần chọn loại dự án nào để tạo ứng dụng Windows Form?',
    'dap_an_a' => 'Console App',
    'dap_an_b' => 'Windows Forms App',
    'dap_an_c' => 'Class Library',
    'dap_an_d' => 'WPF Application',
    'dap_an_dung' => 'B',
  ),
  16 => 
  array (
    'cau_hoi' => 'Trong Windows Form Designer, công cụ nào giúp bạn kéo và thả các thành phần giao diện?',
    'dap_an_a' => 'Solution Explorer',
    'dap_an_b' => 'Toolbox',
    'dap_an_c' => 'Properties Window',
    'dap_an_d' => 'Output Window',
    'dap_an_dung' => 'B',
  ),
  17 => 
  array (
    'cau_hoi' => 'Để tạo một dự án sử dụng nhiều Forms, làm thế nào để mở một Form từ một Form khác?',
    'dap_an_a' => 'Sử dụng lệnh Form2.Show()',
    'dap_an_b' => 'Sử dụng lệnh Form2.Display()',
    'dap_an_c' => 'Sử dụng lệnh Form2.Open()',
    'dap_an_d' => 'Sử dụng lệnh Form2.Load()',
    'dap_an_dung' => 'A',
  ),
  18 => 
  array (
    'cau_hoi' => 'Kiểu dữ liệu nào trong VB.Net được sử dụng để lưu trữ chuỗi ký tự?',
    'dap_an_a' => 'String',
    'dap_an_b' => 'Char',
    'dap_an_c' => 'Integer',
    'dap_an_d' => 'DateTime',
    'dap_an_dung' => 'A',
  ),
  19 => 
  array (
    'cau_hoi' => 'Cú pháp để khai báo biến trong VB.Net là gì?',
    'dap_an_a' => 'Set tên biến as Kiểu dữ liệu',
    'dap_an_b' => 'Declare tên biến Kiểu dữ liệu',
    'dap_an_c' => 'Dim tên biến As Kiểu dữ liệu',
    'dap_an_d' => 'Var tên biến = Kiểu dữ liệu',
    'dap_an_dung' => 'C',
  ),
  20 => 
  array (
    'cau_hoi' => 'Sự khác biệt giữa tham trị (ByVal) và tham chiếu (ByRef) trong việc truyền biến vào hàm là gì?',
    'dap_an_a' => 'ByVal truyền địa chỉ của biến, ByRef truyền giá trị',
    'dap_an_b' => 'ByVal truyền bản sao của biến, ByRef truyền địa chỉ của biến',
    'dap_an_c' => 'ByRef sử dụng ít bộ nhớ hơn ByVal',
    'dap_an_d' => 'ByRef không thể thay đổi giá trị gốc',
    'dap_an_dung' => 'B',
  ),
  21 => 
  array (
    'cau_hoi' => 'Mảng trong VB.Net là gì?',
    'dap_an_a' => 'Một tập hợp các biến có kiểu dữ liệu khác nhau',
    'dap_an_b' => 'Một tập hợp các biến có cùng kiểu dữ liệu',
    'dap_an_c' => 'Một hàm chứa nhiều giá trị',
    'dap_an_d' => 'Một đối tượng lưu trữ duy nhất một giá trị',
    'dap_an_dung' => 'B',
  ),
  22 => 
  array (
    'cau_hoi' => 'Cú pháp để khai báo mảng trong VB.Net là gì?',
    'dap_an_a' => 'Dim arrayName As datatype()',
    'dap_an_b' => 'Var arrayName = datatype()',
    'dap_an_c' => 'Define arrayName As datatype()',
    'dap_an_d' => 'Declare arrayName As datatype()',
    'dap_an_dung' => 'A',
  ),
  23 => 
  array (
    'cau_hoi' => 'Cú pháp để truy cập phần tử thứ 3 của mảng trong VB.Net là gì?',
    'dap_an_a' => 'arrayName(2)',
    'dap_an_b' => 'arrayName(3)',
    'dap_an_c' => 'arrayName[3]',
    'dap_an_d' => 'arrayName[2]',
    'dap_an_dung' => 'A',
  ),
  24 => 
  array (
    'cau_hoi' => 'Câu lệnh nào dùng để thay đổi kích thước của mảng trong VB.Net?',
    'dap_an_a' => 'Resize(arrayName, newSize)',
    'dap_an_b' => 'Redim arrayName(newSize)',
    'dap_an_c' => 'Array.Resize(arrayName, newSize)',
    'dap_an_d' => 'Array.ReSize(arrayName)',
    'dap_an_dung' => 'B',
  ),
  25 => 
  array (
    'cau_hoi' => 'Trong VB.Net, toán tử nào dùng để tính lũy thừa?',
    'dap_an_a' => '^',
    'dap_an_b' => '*',
    'dap_an_c' => '%',
    'dap_an_d' => '+',
    'dap_an_dung' => 'A',
  ),
  26 => 
  array (
    'cau_hoi' => 'Toán tử Mod trong VB.Net dùng để làm gì?',
    'dap_an_a' => 'Chia lấy phần nguyên',
    'dap_an_b' => 'Chia lấy phần dư',
    'dap_an_c' => 'Tính lũy thừa',
    'dap_an_d' => 'Nhân hai số',
    'dap_an_dung' => 'B',
  ),
  27 => 
  array (
    'cau_hoi' => 'Kết quả của biểu thức 5 + 2 * 3 trong VB.Net là gì?',
    'dap_an_a' => '21',
    'dap_an_b' => '11',
    'dap_an_c' => '7',
    'dap_an_d' => '10',
    'dap_an_dung' => 'B',
  ),
  28 => 
  array (
    'cau_hoi' => 'Câu lệnh If trong VB.Net dùng để làm gì?',
    'dap_an_a' => 'Lặp lại một khối lệnh',
    'dap_an_b' => 'Thực hiện khối lệnh khi điều kiện đúng',
    'dap_an_c' => 'Thực hiện phép tính toán học',
    'dap_an_d' => 'Tạo một biến',
    'dap_an_dung' => 'B',
  ),
  29 => 
  array (
    'cau_hoi' => 'Câu lệnh If...Else có cú pháp nào đúng?',
    'dap_an_a' => 'If condition Then...Else...End If',
    'dap_an_b' => 'If condition...Else...End',
    'dap_an_c' => 'If condition Then...Else If...End',
    'dap_an_d' => 'If condition Then...Else Then...End',
    'dap_an_dung' => 'A',
  ),
  30 => 
  array (
    'cau_hoi' => 'Điều kiện x = 10 trong If sẽ thực hiện khối lệnh nào sau đây? If x = 10 Then Console.WriteLine("Equal to 10") Else Console.WriteLine("Not equal to 10") End If',
    'dap_an_a' => 'Hiển thị "Equal to 10"',
    'dap_an_b' => 'Hiển thị "Not equal to 10"',
    'dap_an_c' => 'Lỗi cú pháp',
    'dap_an_d' => 'Không có gì hiển thị',
    'dap_an_dung' => 'A',
  ),
  31 => 
  array (
    'cau_hoi' => 'Câu lệnh sau sẽ hiển thị kết quả gì nếu x = 7? If x > 10 Then Console.WriteLine("Greater than 10") ElseIf x > 5 Then Console.WriteLine("Greater than 5") Else Console.WriteLine("Less than or equal to 5") End If',
    'dap_an_a' => 'Hiển thị "Greater than 10"',
    'dap_an_b' => 'Hiển thị "Greater than 5"',
    'dap_an_c' => 'Hiển thị "Less than or equal to 5"',
    'dap_an_d' => 'Lỗi chương trình',
    'dap_an_dung' => 'B',
  ),
  32 => 
  array (
    'cau_hoi' => 'Câu lệnh If lồng nhau là gì?',
    'dap_an_a' => 'Câu lệnh If có nhiều điều kiện',
    'dap_an_b' => 'Câu lệnh If nằm trong một câu lệnh If khác',
    'dap_an_c' => 'Câu lệnh If nằm trong vòng lặp',
    'dap_an_d' => 'Câu lệnh If với toán tử logic',
    'dap_an_dung' => 'B',
  ),
  33 => 
  array (
    'cau_hoi' => 'Kết quả của câu lệnh If lồng nhau này là gì nếu x = 3 và y = 10? If x > 0 Then If y > 5 Then Console.WriteLine("Both conditions true") Else Console.WriteLine("x is true, y is false") End If Else Console.WriteLine("x is false") End If',
    'dap_an_a' => '"Both conditions true"',
    'dap_an_b' => '"x is true, y is false"',
    'dap_an_c' => '"x is false"',
    'dap_an_d' => 'Lỗi chương trình',
    'dap_an_dung' => 'A',
  ),
  34 => 
  array (
    'cau_hoi' => 'Câu lệnh Select Case nào dưới đây là đúng cú pháp?',
    'dap_an_a' => 'Select Case x Case 1 MessageBox.Show("One") Case 2 MessageBox.Show ("Two") End Select',
    'dap_an_b' => 'Switch Case x Case 1 MessageBox.Show("One") End Case',
    'dap_an_c' => 'Select x Case 1 MessageBox.Show("One") End Select',
    'dap_an_d' => 'If Case 1 Then MessageBox.Show("One") End Select',
    'dap_an_dung' => 'A',
  ),
  35 => 
  array (
    'cau_hoi' => 'Câu lệnh Select Case kiểm tra giá trị của x = 3 sẽ cho kết quả gì? Select Case x Case 1 MessageBox.Show("One") Case 2 MessageBox.Show("Two") Case 3 MessageBox.Show("Three") Case Else MessageBox.Show("Other") End Select',
    'dap_an_a' => 'Hiển thị "One"',
    'dap_an_b' => 'Hiển thị "Two"',
    'dap_an_c' => 'Hiển thị "Three"',
    'dap_an_d' => 'Hiển thị "Other"',
    'dap_an_dung' => 'C',
  ),
  36 => 
  array (
    'cau_hoi' => 'Từ khóa nào trong Select Case được sử dụng để kiểm tra trường hợp mặc định khi không có điều kiện nào thỏa mãn?',
    'dap_an_a' => 'Else',
    'dap_an_b' => 'Otherwise',
    'dap_an_c' => 'Case Else',
    'dap_an_d' => 'Default',
    'dap_an_dung' => 'C',
  ),
  37 => 
  array (
    'cau_hoi' => 'Trong vòng lặp For i = 1 To 5, giá trị ban đầu của biến đếm là:',
    'dap_an_a' => '1',
    'dap_an_b' => '5',
    'dap_an_c' => '0',
    'dap_an_d' => '-1',
    'dap_an_dung' => 'A',
  ),
  38 => 
  array (
    'cau_hoi' => 'Trong câu lệnh For i = 1 To 10 Step 2, giá trị của i tăng mỗi lần lặp là:',
    'dap_an_a' => '1',
    'dap_an_b' => '2',
    'dap_an_c' => '3',
    'dap_an_d' => '4',
    'dap_an_dung' => 'B',
  ),
  39 => 
  array (
    'cau_hoi' => 'Lệnh For i = 10 To 1 Step -1 sẽ:',
    'dap_an_a' => 'Lặp từ 1 đến 10',
    'dap_an_b' => 'Lặp từ 10 đến 1',
    'dap_an_c' => 'Lặp từ 1 đến 10 với bước nhảy 1',
    'dap_an_d' => 'Lặp từ 1 đến 10 với bước nhảy -1',
    'dap_an_dung' => 'B',
  ),
);

$stmt = $db->prepare("INSERT INTO quiz_questions (quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES (?, ?, ?, ?, ?, ?, ?)");
$inserted = 0;
foreach ($questions as $q) {
    $qid = 57;
    $stmt->bind_param('issssss', $qid, $q['cau_hoi'], $q['dap_an_a'], $q['dap_an_b'], $q['dap_an_c'], $q['dap_an_d'], $q['dap_an_dung']);
    if ($stmt->execute()) {
        $inserted++;
    } else {
        echo "Error inserting question: " . $stmt->error . "\n";
    }
}
$stmt->close();

echo "Total questions inserted: $inserted / " . count($questions) . "\n";
$cntRes = $db->query("SELECT count(*) as c FROM quiz_questions WHERE quiz_id = 57");
$cntRow = $cntRes->fetch_assoc();
echo "VERIFIED IN DATABASE: Quiz 57 now has " . $cntRow['c'] . " questions!\n";
