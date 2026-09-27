<?php
$ch = curl_init('https://api.xkiro.com/v1/models');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48'],
    CURLOPT_SSL_VERIFYPEER => false
]);
$res = curl_exec($ch);
curl_close($ch);
$data = json_decode($res, true);
if (isset($data['data'])) {
    foreach ($data['data'] as $m) {
        echo $m['id'] . "\n";
    }
} else {
    echo $res;
}
