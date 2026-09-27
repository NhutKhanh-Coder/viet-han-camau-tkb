<?php
$c = ftp_connect('ftpupload.net');
ftp_login($c, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($c, true);
@ftp_delete($c, '/htdocs/tkb/student/mock_female_view.php');
@ftp_delete($c, '/viethan.free.nf/htdocs/tkb/student/mock_female_view.php');
ftp_close($c);
echo "Cleaned remote mock file!\n";
