<?php
$apiKey = 'AQ.Ab8RN6KhYYAj9hQMSKIADsz9qPRHc1THSWrXDBOq6m9UlhvAJQ';
$rawPrompt = 'mèo phi hành gia';

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=" . urlencode($apiKey);
$payload = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => "You are an elite Google AI Studio Image Prompt Engineer. Convert this user idea (Vietnamese/English) into an extremely vivid, creative, highly descriptive single-sentence English image prompt for Flux.1 / Stable Diffusion image generation. Focus on character emotion, comedic elements, specific objects, lighting, and 8k detail. Output ONLY the English prompt string, without any other text, prefix, or quotes. User idea: " . $rawPrompt
                ]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 300
    ]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey
    ],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n$response\n";
