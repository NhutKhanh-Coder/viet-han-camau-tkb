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
    'includes/public_header.php',
    'includes/public_footer.php',
    'includes/anti_ddos.php',
    'includes/student_nav.php',
    'login.php',
    'register.php',
    'index.php',
    'gioi_thieu.php',
    'dao_tao.php',
    'tuyen_sinh.php',
    'huong_dan.php',
    'student/dashboard.php',
    'student/code_ide.php',
    'teacher/cham_code.php',
    'assets/home.css',
    'assets/img/campus_showcase.png',
    'assets/img/news_le_thanh_lap.jpg',
    'api/run_code.php',
    'teacher/profile.php',
    'teacher/quiz.php',
    'student/quiz.php',
    'admin/ai_studio.php'
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
        if (@ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
            echo "SUCCESS: $rel -> $remote\n";
        } else {
            echo "FAILED: $rel -> $remote\n";
        }
    }
}
ftp_close($conn_id);
echo "Sync to InfinityFree finished 100%!\n";
