<?php
$apiKey = 'AQ.Ab8RN6KhYYAj9hQMSKIADsz9qPRHc1THSWrXDBOq6m9UlhvAJQ';

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . urlencode($apiKey);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
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
$data = json_decode($response, true);
if (isset($data['models'])) {
    echo "Total models: " . count($data['models']) . "\n";
    foreach ($data['models'] as $m) {
        echo "- " . $m['name'] . " (" . implode(', ', $m['supportedGenerationMethods'] ?? []) . ")\n";
    }
} else {
    echo "Response: $response\n";
}
