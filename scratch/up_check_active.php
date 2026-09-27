<?php
$conn = ftp_connect('ftpupload.net', 21, 30);
ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($conn, true);

$script = '<?php
session_start();
$_SESSION["user_id"] = 1;
$_SESSION["username"] = "admin";
$_SESSION["role"] = "admin";
$_SESSION["ho_ten"] = "Lê Nhựt Khánh";

$_SERVER["PHP_SELF"] = "/tkb/admin/quanly_ai_models.php";
$_SERVER["SCRIPT_NAME"] = "/tkb/admin/quanly_ai_models.php";

ob_start();
include __DIR__ . "/admin/quanly_ai_models.php";
$html = ob_get_clean();

preg_match_all("/<li class=\"adm-nav-item\s*([^\"*]*)\"[^>]*>[\s\S]*?<span>(.*?)<\/span>/i", $html, $matches);
for ($i = 0; $i < count($matches[0]); $i++) {
    if (strpos($matches[1][$i], "active") !== false) {
        echo "ACTIVE ITEM: " . $matches[2][$i] . " (class: " . $matches[1][$i] . ")\n";
    }
}
unlink(__FILE__);
';
file_put_contents('scratch/_check_active.php', $script);
ftp_put($conn, '/htdocs/tkb/_check_active.php', 'scratch/_check_active.php', FTP_BINARY);
ftp_put($conn, '/viethan.free.nf/htdocs/tkb/_check_active.php', 'scratch/_check_active.php', FTP_BINARY);
ftp_close($conn);

echo "Uploaded active checker\n";
