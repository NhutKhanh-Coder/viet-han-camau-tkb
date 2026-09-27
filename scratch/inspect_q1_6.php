<?php
$phpCode = '<?php
ini_set("display_errors", 1);
require_once __DIR__ . "/config.php";
header("Content-Type: text/plain; charset=utf-8");
$db = getDB();

$targetQuizId = 69;
$res = $db->query("SELECT id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung FROM quiz_questions WHERE quiz_id = $targetQuizId ORDER BY id ASC LIMIT 6");
if ($res) {
    $i = 1;
    while ($r = $res->fetch_assoc()) {
        echo "Q$i (ID {$r["id"]}): {$r["cau_hoi"]}\n";
        echo "   Ans: [{$r["dap_an_dung"]}]\n";
        echo "   A: {$r["dap_an_a"]}\n";
        echo "   B: {$r["dap_an_b"]}\n";
        echo "   C: {$r["dap_an_c"]}\n";
        echo "   D: {$r["dap_an_d"]}\n\n";
        $i++;
    }
}
';

file_put_contents('c:/xampp/htdocs/tkb/scratch/dump_q1_6.php', $phpCode);

$conn_id = @ftp_connect('ftpupload.net', 21, 30);
if ($conn_id && @ftp_login($conn_id, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    @ftp_put($conn_id, '/viethan.free.nf/htdocs/tkb/dump_q1_6.php', 'c:/xampp/htdocs/tkb/scratch/dump_q1_6.php', FTP_BINARY);
    @ftp_close($conn_id);
}

$url = 'https://viethan.free.nf/tkb/dump_q1_6.php';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$html = curl_exec($ch);
curl_close($ch);

$cookieVal = null;
if (preg_match('/toNumbers\("([a-f0-9]+)"\),b=toNumbers\("([a-f0-9]+)"\),c=toNumbers\("([a-f0-9]+)"\)/i', $html, $m)) {
    $key = hex2bin($m[1]);
    $iv  = hex2bin($m[2]);
    $ct  = hex2bin($m[3]);
    $dec = openssl_decrypt($ct, 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    $cookieVal = bin2hex($dec);
}

if ($cookieVal) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    curl_setopt($ch, CURLOPT_COOKIE, "__test=$cookieVal; path=/");
    $res = curl_exec($ch);
    curl_close($ch);
    echo $res;
}

$conn_id = @ftp_connect('ftpupload.net', 21, 30);
if ($conn_id && @ftp_login($conn_id, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    @ftp_delete($conn_id, '/viethan.free.nf/htdocs/tkb/dump_q1_6.php');
    @ftp_close($conn_id);
}
