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

$files = [
    'admin/ai_studio.php',
    'includes/admin_cosmic_bot.php'
];

$remote_bases = [
    '/htdocs/tkb',
    '/viethan.free.nf/htdocs/tkb'
];

foreach ($remote_bases as $base) {
    foreach ($files as $f) {
        $local = 'c:/xampp/htdocs/tkb/' . $f;
        $remote = $base . '/' . $f;
        if (@ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
            echo "[OK] $f -> $remote\n";
        } else {
            echo "[FAILED] $f -> $remote\n";
        }
    }
}

ftp_close($conn_id);
echo "All files synced successfully!\n";
