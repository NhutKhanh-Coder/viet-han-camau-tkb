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

$local = 'c:/xampp/htdocs/tkb/admin/ai_studio.php';
$remotes = [
    '/htdocs/tkb/admin/ai_studio.php',
    '/viethan.free.nf/htdocs/tkb/admin/ai_studio.php'
];

foreach ($remotes as $remote) {
    if (@ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
        echo "✔ SUCCESS: $remote (" . round(filesize($local)/1024, 1) . " KB)\n";
    } else {
        echo "✖ FAILED: $remote\n";
    }
}
@ftp_close($conn_id);
echo "DONE!\n";
