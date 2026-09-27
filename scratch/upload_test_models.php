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

ob_start();
try {
    include __DIR__ . "/admin/quanly_ai_models.php";
    $output = ob_get_clean();
    echo "SUCCESS: Length " . strlen($output) . "\n";
    echo "Snippet: " . substr(strip_tags($output), 0, 200) . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
';
file_put_contents('scratch/_test_models_exec.php', $script);
ftp_put($conn, '/htdocs/tkb/_test_models_exec.php', 'scratch/_test_models_exec.php', FTP_BINARY);
ftp_put($conn, '/viethan.free.nf/htdocs/tkb/_test_models_exec.php', 'scratch/_test_models_exec.php', FTP_BINARY);
ftp_close($conn);

echo "Uploaded _test_models_exec.php\n";
