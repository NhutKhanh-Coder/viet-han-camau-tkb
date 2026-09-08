<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 30);
if ($conn_id && @ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    @ftp_pasv($conn_id, true);
    $local_base = 'c:/xampp/htdocs/tkb';
    $files = [
        'includes/admin_nav.php',
        'includes/teacher_nav.php',
        'admin/youtube.php',
        'admin/dashboard.php',
        'admin/phanquyen.php',
        'admin/quanly_ai_accounts.php',
        'teacher/mmo.php',
        'teacher/tailieu.php',
        'student/hoc_bai.php',
        'student/shop_ai.php',
        'api/login.php',
        'teacher/quanly_nhac.php',
        'student/ai.php',
        'student/quiz.php',
        'teacher/quiz.php',
        'includes/public_footer.php'
    ];
    $remotes = ['/htdocs/tkb', '/viethan.free.nf/htdocs/tkb'];
    foreach ($remotes as $rem) {
        foreach ($files as $f) {
            $loc = $local_base . '/' . $f;
            $r = $rem . '/' . $f;
            if (file_exists($loc)) {
                @ftp_put($conn_id, $r, $loc, FTP_BINARY);
            }
        }
    }
    ftp_close($conn_id);
}
echo "Synced admin_nav and cleaned files.\n";
