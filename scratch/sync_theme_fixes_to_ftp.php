<?php
set_time_limit(300);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 45);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP login failed!\n");
}
@ftp_pasv($conn_id, true);

$local_base = 'c:/xampp/htdocs/tkb';

$files_to_sync = [
    'assets/sakura_fall.js',
    'includes/admin_nav.php',
    'admin/ai_studio.php',
    'admin/backup.php',
    'admin/baidang.php',
    'admin/baitap.php',
    'admin/caidat.php',
    'admin/dashboard.php',
    'admin/diem.php',
    'admin/giangvien.php',
    'admin/lop.php',
    'admin/monhoc.php',
    'admin/nhatky.php',
    'admin/phanquyen.php',
    'admin/profile.php',
    'admin/quanly_ai_models.php',
    'admin/students.php',
    'admin/tailieu.php',
    'admin/youtube.php',
    'teacher/baocao_tien_do.php'
];

$roots = ['/htdocs/tkb', '/viethan.free.nf/htdocs/tkb'];

$success = 0;
$failed = 0;

foreach ($files_to_sync as $rel) {
    $local_path = $local_base . '/' . $rel;
    if (!file_exists($local_path)) {
        echo "SKIPPED (not found): $rel\n";
        continue;
    }
    foreach ($roots as $root) {
        $remote_path = $root . '/' . $rel;
        if (@ftp_put($conn_id, $remote_path, $local_path, FTP_BINARY)) {
            echo "OK: $remote_path\n";
            $success++;
        } else {
            echo "FAIL: $remote_path\n";
            $failed++;
        }
    }
}

ftp_close($conn_id);
echo "Sync completed! Success: $success, Failed: $failed\n";
