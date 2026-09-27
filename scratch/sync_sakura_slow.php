<?php
set_time_limit(120);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP login failed!\n");
}
@ftp_pasv($conn_id, true);

$local = 'c:/xampp/htdocs/tkb/assets/sakura_fall.js';
$remotes = [
    '/htdocs/tkb/assets/sakura_fall.js',
    '/viethan.free.nf/htdocs/tkb/assets/sakura_fall.js'
];

foreach ($remotes as $r) {
    if (@ftp_put($conn_id, $r, $local, FTP_BINARY)) {
        echo "SUCCESS: $r\n";
    } else {
        echo "FAILED: $r\n";
    }
}
ftp_close($conn_id);
echo "Sync sakura_fall.js completed!\n";
