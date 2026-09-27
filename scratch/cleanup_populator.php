<?php
$c = ftp_connect('ftpupload.net', 21, 30);
if ($c && ftp_login($c, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    ftp_pasv($c, true);
    @ftp_delete($c, '/htdocs/tkb/student/populate_quiz57_40q.php');
    @ftp_delete($c, '/viethan.free.nf/htdocs/tkb/student/populate_quiz57_40q.php');
    @ftp_delete($c, '/htdocs/student/populate_quiz57_40q.php');
    @ftp_delete($c, '/viethan.free.nf/htdocs/student/populate_quiz57_40q.php');
    ftp_close($c);
    echo "Remote populator scripts cleaned up successfully.\n";
}
