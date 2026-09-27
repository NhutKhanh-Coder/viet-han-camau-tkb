<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn = @ftp_connect($ftp_server, 21, 30);
if (!$conn || !@ftp_login($conn, $ftp_user, $ftp_pass)) {
    die("FTP Connection failed\n");
}
@ftp_pasv($conn, true);

$remotes = [
    '/htdocs/tkb/student/quiz.php',
    '/viethan.free.nf/htdocs/tkb/student/quiz.php',
    '/htdocs/student/quiz.php'
];

foreach ($remotes as $rem) {
    $temp = tempnam(sys_get_temp_dir(), 'ftp_');
    if (@ftp_get($conn, $temp, $rem, FTP_BINARY)) {
        $c = file_get_contents($temp);
        echo "Check $rem:\n";
        echo "  - Size: " . strlen($c) . " bytes\n";
        echo "  - Has 'KIOSK EXAM THEME: PURE WHITE': " . (strpos($c, 'KIOSK EXAM THEME: PURE WHITE') !== false ? 'YES' : 'NO') . "\n";
        echo "  - Has 'DEEP SPACE AURORA': " . (strpos($c, 'DEEP SPACE AURORA') !== false ? 'YES' : 'NO') . "\n";
    } else {
        echo "Check $rem: FAILED TO DOWNLOAD\n";
    }
    @unlink($temp);
}

ftp_close($conn);
