<?php
$phpCode = '<?php
require_once __DIR__ . "/config.php";
$db = getDB();
$r = $db->query("SELECT COUNT(*) as c FROM quiz_questions WHERE quiz_id = 67");
$row = $r->fetch_assoc();
echo "QUIZ 67 QUESTIONS COUNT: " . $row["c"] . "\n";
';

file_put_contents('c:/xampp/htdocs/tkb/scratch/temp_cnt.php', $phpCode);

$conn_id = @ftp_connect('ftpupload.net', 21, 30);
if ($conn_id && @ftp_login($conn_id, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    @ftp_put($conn_id, '/htdocs/tkb/temp_cnt.php', 'c:/xampp/htdocs/tkb/scratch/temp_cnt.php', FTP_BINARY);
    @ftp_close($conn_id);
}

$url = 'https://viethan.free.nf/tkb/temp_cnt.php';
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
    @ftp_delete($conn_id, '/htdocs/tkb/temp_cnt.php');
    @ftp_close($conn_id);
}
