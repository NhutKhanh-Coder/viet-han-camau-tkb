<?php
set_time_limit(300);
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
    'includes/admin_nav.php',
    'includes/student_nav.php',
    'admin/quanly_ai_accounts.php',
    'admin/mmo_events.php',
    'api/claim_mmo_event.php',
    'student/shop_ai.php',
    'student/events.php',
    'scratch/debug_mmo_events.php'
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
        if (!file_exists($local)) {
            echo "SKIP (Not found): $local\n";
            continue;
        }

        // Đảm bảo thư mục cha tồn tại trên FTP
        $remote_dir = dirname($remote);
        $dir_parts = array_filter(explode('/', str_replace('\\', '/', $remote_dir)));
        $cur_path = '';
        foreach ($dir_parts as $part) {
            $cur_path .= '/' . $part;
            @ftp_mkdir($conn_id, $cur_path);
        }

        if (@ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
            echo "SUCCESS: $rel -> $remote\n";
        } else {
            echo "FAILED: $rel -> $remote\n";
        }
    }
}
ftp_close($conn_id);
echo "Sync MMO Events feature to InfinityFree finished 100%!\n";
