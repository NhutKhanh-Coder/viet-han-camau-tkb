<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("FTP connection failed\n");
}
@ftp_pasv($conn_id, true);

$local_base = 'c:/xampp/htdocs/tkb';
$files = [
    'config.php',
    'includes/admin_nav.php',
    'admin/dashboard.php',
    'admin/students.php',
    'admin/giangvien.php',
    'admin/baidang.php',
    'assets/img/avatar_tuyen.jpg',
    'assets/img/teacher_desk_hero.jpg'
];

$remotes = ['/htdocs/tkb', '/viethan.free.nf/htdocs/tkb'];
foreach ($remotes as $rem) {
    foreach ($files as $f) {
        $loc = $local_base . '/' . $f;
        $r = $rem . '/' . $f;
        if (file_exists($loc)) {
            if (@ftp_put($conn_id, $r, $loc, FTP_BINARY)) {
                echo "SUCCESS: $f -> $r\n";
            } else {
                echo "FAILED: $f -> $r\n";
            }
        }
    }
}
ftp_close($conn_id);
echo "Sync complete 100%!\n";
