<?php
set_time_limit(60);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

echo "Connecting to $ftp_server...\n";
$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP connection/login failed!\n");
}
@ftp_pasv($conn_id, true);

$local = 'c:/xampp/htdocs/tkb/includes/admin_nav.php';
$remote_bases = ['/htdocs/tkb', '/viethan.free.nf/htdocs/tkb'];

foreach ($remote_bases as $base) {
    $remote = $base . '/includes/admin_nav.php';
    if (@ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
        echo "SUCCESS: $remote\n";
    } else {
        echo "FAILED: $remote\n";
    }
}
ftp_close($conn_id);
echo "Done!\n";
