<?php
$conn = ftp_connect('ftpupload.net', 21, 30);
ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($conn, true);
echo 'Size in /htdocs/tkb/includes/ai_models_list.php: ' . ftp_size($conn, '/htdocs/tkb/includes/ai_models_list.php') . PHP_EOL;
echo 'Size in /viethan.free.nf/htdocs/tkb/includes/ai_models_list.php: ' . ftp_size($conn, '/viethan.free.nf/htdocs/tkb/includes/ai_models_list.php') . PHP_EOL;
echo 'Size in /htdocs/tkb/admin/quanly_ai_models.php: ' . ftp_size($conn, '/htdocs/tkb/admin/quanly_ai_models.php') . PHP_EOL;
echo 'Size in /viethan.free.nf/htdocs/tkb/admin/quanly_ai_models.php: ' . ftp_size($conn, '/viethan.free.nf/htdocs/tkb/admin/quanly_ai_models.php') . PHP_EOL;
ftp_close($conn);
