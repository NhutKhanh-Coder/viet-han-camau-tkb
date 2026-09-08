<?php
require_once '../config.php';
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$db = getDB();
$student_id = (int)($_SESSION['student_id'] ?? $_SESSION['user_id'] ?? 1);

// Đảm bảo bảng tồn tại
@$db->query("CREATE TABLE IF NOT EXISTS `student_code_storage` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `ten_du_an` VARCHAR(255) NOT NULL,
    `ngon_ngu` VARCHAR(50) NOT NULL DEFAULT 'python',
    `mo_ta` TEXT DEFAULT NULL,
    `ma_nguon` LONGTEXT NOT NULL,
    `la_cong_khai` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Nếu POST thì lưu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ten_du_an'])) {
    $ten = $db->real_escape_string($_POST['ten_du_an']);
    $lang = $db->real_escape_string($_POST['ngon_ngu'] ?? 'python');
    $mota = $db->real_escape_string($_POST['mo_ta'] ?? '');
    $code = $db->real_escape_string($_POST['ma_nguon'] ?? '');
    $sql = "INSERT INTO student_code_storage (student_id, ten_du_an, ngon_ngu, mo_ta, ma_nguon) VALUES ($student_id, '$ten', '$lang', '$mota', '$code')";
    if ($db->query($sql)) {
        $msg = "✅ Đã lưu thành công! ID=" . $db->insert_id;
    } else {
        $msg = "❌ Lỗi DB: " . $db->error;
    }
}

// Đếm
$count = 0;
$r = $db->query("SELECT COUNT(*) as cnt FROM student_code_storage WHERE student_id=$student_id");
if ($r && ($row=$r->fetch_assoc())) $count = $row['cnt'];
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Test Upload Code</title></head>
<body style="font-family:sans-serif; max-width:800px; margin:40px auto; background:#1e293b; color:#fff; padding:20px;">
<h2>🧪 Test Upload File Code (student_id=<?= $student_id ?>)</h2>
<p>Số bài code trong DB: <b><?= $count ?></b></p>
<?php if (!empty($msg)): ?>
<div style="padding:15px; background:#10b981; border-radius:8px; margin:15px 0; font-weight:bold;"><?= $msg ?></div>
<?php endif; ?>

<h3>1. Chọn file code từ máy tính:</h3>
<input type="file" id="fileInput" style="font-size:16px; padding:10px;">
<div id="result" style="margin-top:15px; padding:15px; background:#334155; border-radius:8px; display:none;"></div>

<h3>2. Hoặc nhập code thủ công:</h3>
<form method="POST" style="display:flex; flex-direction:column; gap:10px;">
    <input name="ten_du_an" placeholder="Tên bài làm" value="Test Upload" style="padding:10px; border-radius:6px; border:none;">
    <select name="ngon_ngu" style="padding:10px; border-radius:6px; border:none;">
        <option value="python">Python</option><option value="html">HTML</option><option value="cpp">C++</option>
    </select>
    <input name="mo_ta" placeholder="Mô tả" value="Test" style="padding:10px; border-radius:6px; border:none;">
    <textarea name="ma_nguon" rows="5" placeholder="Code..." style="padding:10px; border-radius:6px; border:none; font-family:monospace;">print("Hello World")</textarea>
    <button type="submit" style="padding:12px; background:#a855f7; color:#fff; border:none; border-radius:8px; font-size:16px; cursor:pointer;">💾 Lưu Vào CSDL (POST form)</button>
</form>

<script>
document.getElementById('fileInput').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file) return;
    
    var div = document.getElementById('result');
    div.style.display = 'block';
    div.innerHTML = '⏳ Đang đọc file: ' + file.name + ' (' + file.size + ' bytes)...';
    
    var reader = new FileReader();
    reader.onload = function(evt) {
        var text = evt.target.result;
        div.innerHTML = '✅ Đã đọc file: <b>' + file.name + '</b><br>Kích thước: ' + text.length + ' ký tự<br><br><b>Nội dung 500 ký tự đầu:</b><br><pre style="background:#0f172a;padding:10px;border-radius:6px;overflow:auto;max-height:200px;">' + text.substring(0,500).replace(/</g,'&lt;') + '</pre>';
        
        // Auto fill form
        document.querySelector('input[name="ten_du_an"]').value = 'Tệp: ' + file.name;
        document.querySelector('textarea[name="ma_nguon"]').value = text;
        
        var ext = file.name.split('.').pop().toLowerCase();
        var lang = 'python';
        if (ext === 'html' || ext === 'htm' || ext === 'css' || ext === 'js') lang = 'html';
        else if (ext === 'cpp' || ext === 'c') lang = 'cpp';
        document.querySelector('select[name="ngon_ngu"]').value = lang;
    };
    reader.onerror = function() {
        div.innerHTML = '❌ Lỗi đọc file!';
    };
    reader.readAsText(file);
});
</script>
</body></html>
