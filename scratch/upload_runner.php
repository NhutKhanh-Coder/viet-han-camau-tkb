<?php
$conn = ftp_connect('ftpupload.net', 21, 30);
ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($conn, true);

$script = '<?php
require_once __DIR__ . "/config.php";
$db = getDB();
$res = $db->query("SELECT id, username, ho_ten, role FROM users WHERE role=\'admin\'");
$out = "";
while ($r = $res->fetch_assoc()) {
    $out .= "ID: " . $r["id"] . " | User: " . $r["username"] . " | Name: " . $r["ho_ten"] . "\n";
}
file_put_contents(__DIR__ . "/_inspect_users_remote.txt", $out);
unlink(__FILE__);
';
file_put_contents('scratch/_runner.php', $script);
ftp_put($conn, '/htdocs/tkb/_runner.php', 'scratch/_runner.php', FTP_BINARY);
ftp_put($conn, '/viethan.free.nf/htdocs/tkb/_runner.php', 'scratch/_runner.php', FTP_BINARY);
ftp_close($conn);
echo "Uploaded runner\n";
