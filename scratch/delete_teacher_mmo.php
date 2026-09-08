<?php
// Xóa file local nếu còn tồn tại
$local_file = __DIR__ . '/../teacher/quanly_ai_accounts.php';
if (file_exists($local_file)) {
    unlink($local_file);
    echo "Deleted local teacher/quanly_ai_accounts.php\n";
} else {
    echo "Local teacher/quanly_ai_accounts.php does not exist.\n";
}

// Xóa file trên FTP hosting
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 30);
if ($conn_id && @ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    @ftp_pasv($conn_id, true);
    $remote_paths = [
        '/htdocs/tkb/teacher/quanly_ai_accounts.php',
        '/viethan.free.nf/htdocs/tkb/teacher/quanly_ai_accounts.php'
    ];
    foreach ($remote_paths as $rpath) {
        if (@ftp_delete($conn_id, $rpath)) {
            echo "Deleted remote: $rpath\n";
        } else {
            echo "Remote not found or already deleted: $rpath\n";
        }
    }
    ftp_close($conn_id);
}
echo "Finished cleaning up teacher MMO.\n";
