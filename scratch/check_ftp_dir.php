<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = ftp_connect($ftp_server, 21, 15);
ftp_login($conn_id, $ftp_user, $ftp_pass);
ftp_pasv($conn_id, true);

echo "=== ROOT DIRECTORY LIST ===\n";
$list = ftp_nlist($conn_id, ".");
print_r($list);

echo "\n=== /htdocs LIST ===\n";
$list_htdocs = ftp_nlist($conn_id, "htdocs");
print_r($list_htdocs);

echo "\n=== /viethan.free.nf/htdocs LIST ===\n";
$list_v = ftp_nlist($conn_id, "viethan.free.nf/htdocs");
print_r($list_v);

echo "\n=== /htdocs/tkb LIST ===\n";
$list_tkb = ftp_nlist($conn_id, "htdocs/tkb");
print_r($list_tkb);

echo "\n=== /viethan.free.nf/htdocs/tkb LIST ===\n";
$list_vtkb = ftp_nlist($conn_id, "viethan.free.nf/htdocs/tkb");
print_r($list_vtkb);

ftp_close($conn_id);
