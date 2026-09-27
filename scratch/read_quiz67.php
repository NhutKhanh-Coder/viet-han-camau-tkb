<?php
function getInfinityFreeCookie($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    $html = curl_exec($ch);
    curl_close($ch);

    if (preg_match('/toNumbers\("([a-f0-9]+)"\),b=toNumbers\("([a-f0-9]+)"\),c=toNumbers\("([a-f0-9]+)"\)/i', $html, $m)) {
        $key = hex2bin($m[1]);
        $iv  = hex2bin($m[2]);
        $ct  = hex2bin($m[3]);
        $dec = openssl_decrypt($ct, 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        return bin2hex($dec);
    }
    return null;
}

$url = 'https://viethan.free.nf/tkb/inspect_quiz67.php';
$cookieVal = getInfinityFreeCookie($url);
echo "Computed __test cookie: $cookieVal\n";

if ($cookieVal) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    curl_setopt($ch, CURLOPT_COOKIE, "__test=$cookieVal; path=/");
    $res = curl_exec($ch);
    curl_close($ch);
    echo "=== DATA ===\n" . $res;
}
