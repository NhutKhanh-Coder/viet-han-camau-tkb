<?php
// Test what is on the live server right now
$ch = curl_init('https://viethan.free.nf/tkb/api/login.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0'
]);
$res = curl_exec($ch);
curl_close($ch);
echo "login.php accessible\n";

// Let's check FTP file timestamp and size on remote
$ftp = ftp_connect('ftpupload.net', 21, 30);
if ($ftp && ftp_login($ftp, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    ftp_pasv($ftp, true);
    $size = ftp_size($ftp, 'htdocs/student/ai.php');
    $mdtm = ftp_mdtm($ftp, 'htdocs/student/ai.php');
    echo "Remote ai.php size: $size bytes\n";
    echo "Remote ai.php last modified: " . date('Y-m-d H:i:s', $mdtm) . "\n";
    ftp_close($ftp);
} else {
    echo "FTP connection failed!\n";
}
