<?php
$ftp = ftp_connect('ftpupload.net', 21, 30);
ftp_login($ftp, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($ftp, true);

foreach (['/htdocs/tkb/includes/admin_nav.php', '/viethan.free.nf/htdocs/tkb/includes/admin_nav.php'] as $path) {
    $h = fopen('php://memory', 'r+');
    if (ftp_fget($ftp, $h, $path, FTP_BINARY)) {
        rewind($h);
        $c = stream_get_contents($h);
        echo "$path:\n";
        echo " - Contains diemdanh: " . (strpos($c, 'diemdanh.php') !== false ? 'YES' : 'NO') . "\n";
        echo " - Contains Diem danh: " . (strpos($c, 'Điểm danh') !== false ? 'YES' : 'NO') . "\n";
    } else {
        echo "Could not read $path\n";
    }
    fclose($h);
}
ftp_close($ftp);
