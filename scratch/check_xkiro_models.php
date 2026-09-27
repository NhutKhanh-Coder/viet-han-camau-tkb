<?php
$list = [
    'mistralai/mistral-large-2512',
    'mistralai/mistral-medium-3.5',
    'mistralai/mistral-small-2603',
    'mistralai/codestral-2508',
    'minimax/minimax-m2.5',
    'z-ai/glm-4.7-flash',
    'z-ai/glm-4.5-flash',
    'deepseek/deepseek-v4-flash-0731'
];

foreach ($list as $m) {
    $ch = curl_init('https://api.xkiro.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $m,
            'messages' => [['role' => 'user', 'content' => '1+1=?']],
            'max_tokens' => 5
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48'
        ],
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "$m: $code\n";
}
