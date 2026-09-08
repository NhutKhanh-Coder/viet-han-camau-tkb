<?php
$testVoices = [
    'vi_thuytien' => 'Xin chào quý vị khán giả.',
    'vi_tuanhung' => 'Chào bạn, tôi là Tuấn Hùng.',
    'vi_maiphuong' => 'Dạ em chào anh chị nha.',
    'vi_chihang' => 'Ngày xửa ngày xưa có một cô bé quàng khăn đỏ.',
    'en_sarah' => 'Hello, this is Sarah.',
    'en_david' => 'Welcome to the BBC news broadcast.',
    'char_monster' => 'Grừừừ, ta là quái vật!',
    'meme_anime' => 'Konnichiwa, ogenki desu ka?'
];

$baseUrl = 'http://localhost/tkb/api/tts.php';
$results = [];

foreach ($testVoices as $vid => $txt) {
    $ch = curl_init($baseUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'text' => $txt,
            'voice' => $vid,
            'pitch' => 1.0,
            'rate' => 1.0,
            'json' => true
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($resp, true);
    $results[$vid] = [
        'http_code' => $code,
        'success' => $json['success'] ?? false,
        'voice_id' => $json['voice_id'] ?? null,
        'audio_url' => $json['audio_url'] ?? null,
        'filesize' => $json['filesize'] ?? 0
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
