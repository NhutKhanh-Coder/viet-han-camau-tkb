<?php
$models = json_decode(file_get_contents(__DIR__ . '/formatted_87_models.json'), true);

$workingModels = [
    'deepseek/deepseek-chat-v3.1',
    'deepseek/deepseek-v4-flash',
    'deepseek/deepseek-v3.2',
    'deepseek/deepseek-v4-pro',
    'mistralai/mistral-large-2512',
    'mistralai/mistral-medium-3.5',
    'mistralai/mistral-small-2603',
    'mistralai/codestral-2508',
    'mistralai/devstral-medium',
    'mistralai/ministral-14b',
    'mistralai/ministral-8b',
    'mistralai/ministral-3b',
    'qwen/qwen3.8-max',
    'qwen/qwen3.7-max',
    'qwen/qwen3.7-plus',
    'qwen/qwen3.6-max-preview',
    'qwen/qwen3.6-plus',
    'qwen/qwen3.6-27b',
    'qwen/qwen3.6-35b-a3b',
    'qwen/qwen3.5-397b-a17b',
    'qwen/qwen3.5-omni-plus',
    'qwen/qwen3.5-flash',
    'qwen/qwen3.5-omni-flash',
    'qwen/qwen3-coder-plus',
    'qwen/qwen3-max',
    'qwen/qwen3-vl-plus',
    'qwen/qwen3-omni-flash',
    'qwen/qwen-plus-2025-07-28',
    'qwen/qwen3.5-plus',
    'minimax/minimax-m2.7',
    'minimax/minimax-m2.7-highspeed',
    'minimax/minimax-m2.5',
    'minimax/minimax-m2.5-highspeed',
    'minimax/minimax-m2.1',
    'minimax/minimax-m2.1-highspeed',
    'minimax/minimax-m2'
];

foreach ($models as &$m) {
    if (in_array($m['id'], $workingModels)) {
        $m['isPaid'] = false;
    } else {
        $m['isPaid'] = true;
    }
}

file_put_contents(__DIR__ . '/formatted_87_models_tagged.json', json_encode($models, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "Tagged " . count($models) . " models successfully!\n";
