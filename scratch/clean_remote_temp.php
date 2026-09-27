<?php
$conn_id = @ftp_connect('ftpupload.net', 21, 30);
if ($conn_id && @ftp_login($conn_id, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    @ftp_delete($conn_id, '/htdocs/tkb/update_remote_quiz67.php');
    @ftp_delete($conn_id, '/viethan.free.nf/htdocs/tkb/update_remote_quiz67.php');
    @ftp_delete($conn_id, '/htdocs/tkb/inspect_quiz67.php');
    @ftp_delete($conn_id, '/viethan.free.nf/htdocs/tkb/inspect_quiz67.php');
    ftp_close($conn_id);
    echo "Cleaned remote temp files successfully\n";
}
