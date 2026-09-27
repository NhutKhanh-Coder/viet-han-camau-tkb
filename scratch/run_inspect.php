<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("FTP failed\n");
}
@ftp_pasv($conn_id, true);

$local = 'c:/xampp/htdocs/tkb/scratch/inspect_quiz67.php';
$remotes = ['/htdocs/tkb/inspect_quiz67.php', '/viethan.free.nf/htdocs/tkb/inspect_quiz67.php'];
foreach ($remotes as $r) {
    @ftp_put($conn_id, $r, $local, FTP_BINARY);
}
ftp_close($conn_id);
echo "Uploaded inspect_quiz67.php\n";

$url = 'https://viethan.free.nf/tkb/inspect_quiz67.php';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
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
    echo "=== RESPONSE ===\n" . $res . "\n";
} else {
    echo "Could not compute cookie. HTML:\n" . substr($html, 0, 300) . "\n";
}
