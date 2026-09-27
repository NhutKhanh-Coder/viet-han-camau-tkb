<?php
$conn = ftp_connect('ftpupload.net');
ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI');
ftp_pasv($conn, true);
ftp_put($conn, '/htdocs/tkb/api/remote_test_matrix_quiz69.php', 'c:/xampp/htdocs/tkb/scratch/remote_test_matrix_quiz69.php', FTP_BINARY);
ftp_put($conn, '/viethan.free.nf/htdocs/tkb/api/remote_test_matrix_quiz69.php', 'c:/xampp/htdocs/tkb/scratch/remote_test_matrix_quiz69.php', FTP_BINARY);
ftp_close($conn);
echo "Uploaded!\n";

$url = "https://viethan.free.nf/tkb/api/remote_test_matrix_quiz69.php";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0'
]);
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
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_COOKIE => "__test=$cookieVal; path=/"
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    echo "=== RESPONSE ===\n" . $res . "\n";
} else {
    echo "Could not compute cookie:\n" . substr($html, 0, 300) . "\n";
}
