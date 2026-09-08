<?php
set_time_limit(120);
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
    'student/luu_tru_code.php',
    'student/kho_code_cong_dong.php',
    'api/code_storage_api.php',
    'api/deploy_web.php',
    'api/remote_inspect_id53.php',
    'scratch/test_api51.php',
    'assets/code_storage_v1.js',
    'assets/img/code_community_hero.png',
    'includes/student_nav.php',
    'includes/teacher_nav.php',
    'student/code_ide.php',
    'student/dashboard.php',
    'student/kho_code.php',
    'scratch/remote_debug_id53.php'
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
echo "Sync to InfinityFree finished 100%!\n";
