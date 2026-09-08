<?php
$key = 'sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48';
$models = [
    'google/gemini-2.0-flash-001',
    'openai/gpt-4o-mini',
    'deepseek/deepseek-chat-v3.1',
    'meta-llama/llama-3.3-70b-instruct',
    'mistralai/mistral-large-2512'
];

$so_cau = 10;
$topic = "Lập trình Web PHP cơ bản";

$prompt = "Tạo đúng $so_cau câu hỏi trắc nghiệm tiếng Việt về: \"$topic\".
Trả về DUY NHẤT 1 JSON array:
[{\"cau_hoi\":\"...\",\"dap_an_a\":\"...\",\"dap_an_b\":\"...\",\"dap_an_c\":\"...\",\"dap_an_d\":\"...\",\"dap_an_dung\":\"B\"}]
Đáp án đúng phải phân bổ ngẫu nhiên A, B, C, D.";

foreach ($models as $m) {
    echo "--- Model: $m ---\n";
    $t0 = microtime(true);
    $ch = curl_init('https://api.xkiro.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'model' => $m,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.4,
            'max_tokens'  => 2500
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $time = round(microtime(true) - $t0, 2);
    curl_close($ch);
    
    echo "Code: $code | Time: {$time}s\n";
    if ($code === 200) {
        $json = json_decode($res, true);
        $content = $json['choices'][0]['message']['content'] ?? '';
        $s = strpos($content, '[');
        $e = strrpos($content, ']');
        if ($s !== false && $e !== false) {
            $parsed = json_decode(substr($content, $s, $e - $s + 1), true);
            if (is_array($parsed)) {
                echo "Parsed: " . count($parsed) . " questions\n";
                $answers = array_column($parsed, 'dap_an_dung');
                echo "Answers: " . implode(', ', $answers) . "\n";
            } else {
                echo "JSON parse error\n";
            }
        } else {
            echo "No JSON found: " . substr($content, 0, 100) . "\n";
        }
    } else {
        echo "Error: " . substr($res, 0, 100) . "\n";
    }
}
