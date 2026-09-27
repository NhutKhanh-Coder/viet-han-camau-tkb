<?php
set_time_limit(180);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

echo "Connecting to FTP...\n";
$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP login failed!\n");
}
@ftp_pasv($conn_id, true);

$files = [
    'assets/sakura_fall.js',
    'admin/dashboard.php',
    'includes/admin_nav.php'
];
$bases = [
    '/htdocs/tkb',
    '/viethan.free.nf/htdocs/tkb'
];

foreach ($bases as $b) {
    foreach ($files as $f) {
        $local = 'c:/xampp/htdocs/tkb/' . $f;
        $remote = $b . '/' . $f;
        if (@ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
            echo "SUCCESS: $f -> $remote\n";
        } else {
            echo "FAILED: $f -> $remote\n";
        }
    }
}
ftp_close($conn_id);
echo "Sync Sakura finished!\n";
