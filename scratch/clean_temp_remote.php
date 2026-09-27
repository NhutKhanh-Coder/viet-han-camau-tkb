<?php
$conn = ftp_connect('ftpupload.net');
ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI');
@ftp_delete($conn, '/htdocs/tkb/api/remote_test_matrix_quiz69.php');
@ftp_delete($conn, '/viethan.free.nf/htdocs/tkb/api/remote_test_matrix_quiz69.php');
ftp_close($conn);
echo "Cleaned up!\n";
