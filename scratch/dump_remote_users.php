<?php
$conn = ftp_connect('ftpupload.net', 21, 30);
ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($conn, true);

$script = '<?php
require_once __DIR__ . "/config.php";
$db = getDB();
$res = $db->query("SELECT id, username, ho_ten, role FROM users");
$out = [];
while ($r = $res->fetch_assoc()) {
    $out[] = $r;
}
file_put_contents(__DIR__ . "/_users_dump.json", json_encode($out, JSON_PRETTY_PRINT));
echo "DONE";
';
file_put_contents('scratch/_dump_users.php', $script);
ftp_put($conn, '/htdocs/tkb/_dump_users.php', 'scratch/_dump_users.php', FTP_BINARY);
ftp_put($conn, '/viethan.free.nf/htdocs/tkb/_dump_users.php', 'scratch/_dump_users.php', FTP_BINARY);
ftp_close($conn);

// Execute via runner
include 'scratch/run_remote.py';
