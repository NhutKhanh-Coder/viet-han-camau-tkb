<?php
$c = ftp_connect('ftpupload.net', 21, 15);
ftp_login($c, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($c, true);
$h = fopen('php://temp', 'r+');
if (@ftp_fget($c, $h, 'viethan.free.nf/htdocs/index.php', FTP_BINARY)) {
    rewind($h);
    echo "CONTENT of viethan.free.nf/htdocs/index.php:\n" . stream_get_contents($h) . "\n";
}
ftp_close($c);
