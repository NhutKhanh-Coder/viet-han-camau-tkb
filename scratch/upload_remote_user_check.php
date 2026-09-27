<?php
$conn = ftp_connect('ftpupload.net', 21, 30);
ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($conn, true);

// Create a small remote inspection script
$script = '<?php
require_once "config.php";
$db = getDB();
$res = $db->query("SELECT id, username, ho_ten, role FROM users WHERE role=\'admin\'");
while ($r = $res->fetch_assoc()) {
    echo "ID: " . $r["id"] . " | User: " . $r["username"] . " | Name: " . $r["ho_ten"] . "<br>";
}
unlink(__FILE__);
';
file_put_contents('scratch/_inspect_users_remote.php', $script);
ftp_put($conn, '/htdocs/tkb/_inspect_users_remote.php', 'scratch/_inspect_users_remote.php', FTP_BINARY);
ftp_put($conn, '/viethan.free.nf/htdocs/tkb/_inspect_users_remote.php', 'scratch/_inspect_users_remote.php', FTP_BINARY);
ftp_close($conn);

echo "Uploaded _inspect_users_remote.php\n";
