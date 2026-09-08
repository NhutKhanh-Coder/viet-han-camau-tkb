<?php
require_once __DIR__ . '/config.php';
$conn = getDB();

// 1. Update live database
$conn->query("UPDATE students SET banner = NULL, tiktok_video = NULL");
$conn->query("UPDATE sinh_vien SET banner = NULL, tiktok_video = NULL");

// 2. Update db_sync_data.sql if present
$file = __DIR__ . '/api/db_sync_data.sql';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $content = preg_replace("/'banner_[^']+'/", 'NULL', $content);
    $content = preg_replace("/'video_[^']+'/", 'NULL', $content);
    file_put_contents($file, $content);
}

// 3. Clear session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['banner']);
unset($_SESSION['tiktok_video']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xóa Dữ Liệu Banner & Video</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-align: center; padding: 50px; background: #f8fafc; color: #1e293b;">
    <div style="max-width: 500px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
        <h2 style="color: #10b981;">🧹 Đang làm sạch dữ liệu...</h2>
        <p style="color: #64748b; font-size: 14px;">Đang xóa sạch Banner, Video trong Database, Session, LocalStorage và IndexedDB trên trình duyệt.</p>
    </div>
    <script>
    try {
        localStorage.clear();
    } catch(e){}
    try {
        var req = indexedDB.deleteDatabase('TkbMediaDB');
        req.onsuccess = function() { console.log('TkbMediaDB deleted'); };
        req.onerror = function() { console.warn('TkbMediaDB delete failed'); };
    } catch(e){}
    setTimeout(function() {
        alert('✅ Đã xóa sạch toàn bộ banner & video! Tài khoản sinh viên bây giờ hoàn toàn trống để sinh viên tự thêm video.');
        window.location.href = '/tkb/student/dashboard.php';
    }, 900);
    </script>
</body>
</html>
