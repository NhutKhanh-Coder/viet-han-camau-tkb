<?php
$ftp = ftp_connect('ftpupload.net', 21, 30);
ftp_login($ftp, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($ftp, true);

echo "=== /htdocs/tkb/student ===\n";
$list = ftp_nlist($ftp, '/htdocs/tkb/student');
print_r($list);

echo "=== /viethan.free.nf/htdocs/tkb/student ===\n";
$list2 = ftp_nlist($ftp, '/viethan.free.nf/htdocs/tkb/student');
print_r($list2);

// Tải thử student/events.php nếu có
$tmp = __DIR__ . '/events_downloaded.php';
if (ftp_get($ftp, $tmp, '/htdocs/tkb/student/events.php', FTP_BINARY)) {
    echo "Downloaded /htdocs/tkb/student/events.php successfully!\n";
} elseif (ftp_get($ftp, $tmp, '/viethan.free.nf/htdocs/tkb/student/events.php', FTP_BINARY)) {
    echo "Downloaded /viethan.free.nf/htdocs/tkb/student/events.php successfully!\n";
} else {
    echo "student/events.php not found on FTP.\n";
}
