<?php
set_time_limit(180);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

echo "Connecting to FTP $ftp_server...\n";
$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP connection/login failed!\n");
}
@ftp_pasv($conn_id, true);

$local_base = 'c:/xampp/htdocs/tkb';
$files = [
    'config.php',
    'teacher/mmo.php',
    'admin/quanly_ai_accounts.php',
    'student/shop_ai.php',
    'teacher/quiz.php',
    'teacher/in_de_thi.php',
    'student/quiz.php',
    'api/db_sync_data.sql'
];

$remote_bases = [
    '/htdocs/tkb',
    '/viethan.free.nf/htdocs/tkb'
];

foreach ($remote_bases as $remote_base) {
    echo "=== Syncing to $remote_base ===\n";
    foreach ($files as $rel) {
        $local = $local_base . '/' . $rel;
        $remote = $remote_base . '/' . $rel;
        if (@ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
            echo "SUCCESS: $rel -> $remote\n";
        } else {
            echo "FAILED: $rel -> $remote\n";
        }
    }
}
ftp_close($conn_id);
echo "Sync finished 100%!\n";
