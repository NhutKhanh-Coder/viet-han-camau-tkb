<?php
$conn = ftp_connect('ftpupload.net', 21, 30);
ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($conn, true);

$script = '<?php
echo "PHP_SELF: " . $_SERVER["PHP_SELF"] . "\n";
echo "SCRIPT_NAME: " . $_SERVER["SCRIPT_NAME"] . "\n";
echo "REQUEST_URI: " . $_SERVER["REQUEST_URI"] . "\n";
echo "basename: " . basename($_SERVER["PHP_SELF"]) . "\n";
unlink(__FILE__);
';
file_put_contents('scratch/_test_server.php', $script);
ftp_put($conn, '/htdocs/tkb/admin/_test_server.php', 'scratch/_test_server.php', FTP_BINARY);
ftp_put($conn, '/viethan.free.nf/htdocs/tkb/admin/_test_server.php', 'scratch/_test_server.php', FTP_BINARY);
ftp_close($conn);

echo "Uploaded server test\n";
