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
$remotes = ['/htdocs/tkb', '/viethan.free.nf/htdocs/tkb'];

$files = [
    'assets/img/school_logo.png',
    'assets/img/logo.png',
    'includes/admin_nav.php'
];

foreach ($remotes as $rem) {
    @ftp_mkdir($conn_id, $rem . '/assets');
    @ftp_mkdir($conn_id, $rem . '/assets/img');
    
    foreach ($files as $f) {
        $loc = $local_base . '/' . $f;
        $r = $rem . '/' . $f;
        if (file_exists($loc)) {
            if (@ftp_put($conn_id, $r, $loc, FTP_BINARY)) {
                echo "Uploaded: $r\n";
            } else {
                echo "Failed upload: $r\n";
            }
        }
    }
}

ftp_close($conn_id);
echo "Synced official school logo to FTP 100%!\n";
