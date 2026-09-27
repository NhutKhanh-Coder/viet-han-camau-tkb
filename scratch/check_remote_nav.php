<?php
$conn = ftp_connect('ftpupload.net', 21, 30);
ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($conn, true);
echo 'Local size: ' . filesize('c:/xampp/htdocs/tkb/includes/admin_nav.php') . PHP_EOL;
echo 'Size htdocs: ' . ftp_size($conn, '/htdocs/tkb/includes/admin_nav.php') . PHP_EOL;
echo 'Size viethan: ' . ftp_size($conn, '/viethan.free.nf/htdocs/tkb/includes/admin_nav.php') . PHP_EOL;
ftp_close($conn);
