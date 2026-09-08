<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn = @ftp_connect($ftp_server, 21, 20);
if (!$conn) die("Cannot connect to FTP\n");

if (@ftp_login($conn, $ftp_user, $ftp_pass)) {
    @ftp_pasv($conn, true);
    echo "FTP Connected!\n";

    $local_base = 'c:/xampp/htdocs/tkb';
    $remote_base = 'viethan.free.nf/htdocs/tkb';

    $files = [
        'student/ai.php',
        'student/code_ide.php',
        'api/login.php',
        'api/workspace_files.php',
        'includes/student_nav.php',
    ];

    foreach ($files as $rel) {
        $local = "$local_base/$rel";
        $remote = "$remote_base/$rel";
        if (file_exists($local)) {
            if (@ftp_put($conn, $remote, $local, FTP_BINARY)) {
                echo "OK: $rel\n";
            } else {
                echo "FAIL: $rel\n";
            }
        } else {
            echo "NOT FOUND: $local\n";
        }
    }
    @ftp_close($conn);
    echo "Done!\n";
} else {
    echo "FTP Login failed!\n";
}
