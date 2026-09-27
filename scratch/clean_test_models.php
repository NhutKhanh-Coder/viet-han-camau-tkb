<?php
$conn = ftp_connect('ftpupload.net', 21, 30);
ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($conn, true);
ftp_delete($conn, '/htdocs/tkb/_test_models_exec.php');
ftp_delete($conn, '/viethan.free.nf/htdocs/tkb/_test_models_exec.php');
ftp_close($conn);
echo "Cleaned!";
