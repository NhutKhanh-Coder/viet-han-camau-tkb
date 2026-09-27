<?php
set_time_limit(120);

$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

function getFtpConn($server, $user, $pass) {
    for ($i = 0; $i < 3; $i++) {
        $conn = @ftp_connect($server, 21, 30);
        if ($conn && @ftp_login($conn, $user, $pass)) {
            @ftp_pasv($conn, true);
            return $conn;
        }
        if ($conn) @ftp_close($conn);
        sleep(1);
    }
    return null;
}

$conn = getFtpConn($ftp_server, $ftp_user, $ftp_pass);
if (!$conn) {
    die("FATAL: Could not connect to FTP!\n");
}

$files = [
    'includes/teacher_nav.php',
    'includes/student_nav.php'
];

$roots = ['/htdocs/tkb', '/viethan.free.nf/htdocs/tkb'];

foreach ($files as $rel) {
    $local_path = 'c:/xampp/htdocs/tkb/' . $rel;
    foreach ($roots as $root) {
        $remote_path = $root . '/' . $rel;
        if (@ftp_put($conn, $remote_path, $local_path, FTP_BINARY)) {
            echo "SUCCESS: $remote_path\n";
        } else {
            echo "FAILED: $remote_path\n";
        }
    }
}

ftp_close($conn);
echo "Done!\n";
