<?php
$key = 'sk-xt-0c6462a1c6ee1d8cce9a9eb3b86e6a1d409b85fe7c3ca30a';
$ch = curl_init('https://api.xkiro.com/v1/models');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $key]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true)['data'] ?? [];
$formatted = [];

foreach ($data as $m) {
    $id = $m['id'];
    $parts = explode('/', $id);
    $providerRaw = count($parts) > 1 ? $parts[0] : 'Other';
    $nameRaw = count($parts) > 1 ? $parts[1] : $id;

    // Pretty provider
    $provider = 'Other';
    $p = strtolower($providerRaw);
    if ($p === 'openai') $provider = 'OpenAI';
    elseif ($p === 'anthropic') $provider = 'Anthropic';
    elseif ($p === 'google') $provider = 'Google';
    elseif ($p === 'deepseek') $provider = 'DeepSeek';
    elseif ($p === 'x-ai' || $p === 'xai') $provider = 'xAI (Grok)';
    elseif ($p === 'qwen') $provider = 'Qwen';
    elseif ($p === 'mistralai' || $p === 'mistral') $provider = 'Mistral';
    elseif ($p === 'z-ai' || $p === 'glm') $provider = 'GLM';
    elseif ($p === 'minimax') $provider = 'MiniMax';
    elseif ($p === 'nvidia') $provider = 'NVIDIA';
    elseif ($p === 'moonshotai' || $p === 'kimi') $provider = 'Kimi';
    elseif ($p === 'xiaomi') $provider = 'Xiaomi';
    elseif ($p === 'tencent') $provider = 'Tencent';
    elseif ($p === 'meta') $provider = 'Meta';
    elseif ($p === 'stealth') $provider = 'Stealth';

    // Pretty name
    $name = ucwords(str_replace(['-', '_'], ' ', $nameRaw));
    // Refine names
    $name = preg_replace('/Gpt /i', 'GPT-', $name);
    $name = preg_replace('/Claude /i', '', $name);
    $name = preg_replace('/Gemini /i', 'Gemini ', $name);
    $name = preg_replace('/Deepseek /i', 'DeepSeek ', $name);
    $name = preg_replace('/Qwen /i', 'Qwen ', $name);
    $name = preg_replace('/Grok /i', 'Grok ', $name);
    $name = preg_replace('/Kimi /i', 'Kimi ', $name);
    $name = preg_replace('/Minimax /i', 'MiniMax ', $name);
    $name = preg_replace('/Glm /i', 'GLM-', $name);

    $badge = 'Medium';
    $lowId = strtolower($id);
    if (strpos($lowId, 'flash') !== false || strpos($lowId, 'fast') !== false || strpos($lowId, 'haiku') !== false || strpos($lowId, 'turbo') !== false || strpos($lowId, '3b') !== false || strpos($lowId, '8b') !== false) {
        $badge = 'Fast';
    } elseif (strpos($lowId, 'code') !== false) {
        $badge = 'Coding';
    } elseif (strpos($lowId, 'vision') !== false || strpos($lowId, 'vl') !== false) {
        $badge = 'Vision';
    } elseif (strpos($lowId, 'pro') !== false || strpos($lowId, 'max') !== false || strpos($lowId, 'ultra') !== false) {
        $badge = 'High';
    } elseif (strpos($lowId, 'opus') !== false || strpos($lowId, '5.5') !== false) {
        $badge = 'Extra High';
    }

    $context = '128K Context';
    if (strpos($lowId, 'gemini') !== false) $context = '2M Context';
    elseif (strpos($lowId, 'gpt-5') !== false || strpos($lowId, 'claude') !== false || strpos($lowId, 'minimax') !== false) $context = '1M Context';
    elseif (strpos($lowId, 'kimi') !== false || strpos($lowId, 'codestral') !== false) $context = '200K Context';

    $formatted[] = [
        'id' => $id,
        'name' => trim($name),
        'provider' => $provider,
        'badge' => $badge,
        'context' => $context,
        'desc' => "$name by $provider with advanced reasoning capabilities."
    ];
}

file_put_contents(__DIR__ . '/formatted_87_models.json', json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Formatted " . count($formatted) . " models successfully!\n";
