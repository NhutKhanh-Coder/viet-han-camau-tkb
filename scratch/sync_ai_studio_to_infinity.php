<?php
set_time_limit(180);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 60);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("FTP login failed\n");
}
@ftp_pasv($conn_id, true);

$local_file = 'c:/xampp/htdocs/tkb/admin/ai_studio.php';
$remote_bases = [
    '/htdocs/tkb/admin/ai_studio.php',
    '/viethan.free.nf/htdocs/tkb/admin/ai_studio.php'
];

foreach ($remote_bases as $r) {
    if (@ftp_put($conn_id, $r, $local_file, FTP_BINARY)) {
        echo "[OK] admin/ai_studio.php -> $r\n";
    } else {
        echo "[FAILED] admin/ai_studio.php -> $r\n";
    }
}

ftp_close($conn_id);
echo "Synced ai_studio.php successfully!\n";
