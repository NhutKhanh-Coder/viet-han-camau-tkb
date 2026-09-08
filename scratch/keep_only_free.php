<?php
$models = json_decode(file_get_contents(__DIR__ . '/formatted_87_models_tagged.json'), true);

$freeModels = array_values(array_filter($models, function($m) {
    return empty($m['isPaid']);
}));

echo "Total Free models to keep: " . count($freeModels) . "\n";

$jsArray = "        // ===== 100% FREE & ACTIVE xKiro MODELS DATASET =====\n        const ALL_MODELS = " . json_encode($freeModels, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ";\n";

$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$pattern = '/\/\/ ===== ALL 87 xKiro MODELS DATASET.*?\/\/ ===== AUTHENTIC BRAND SVG LOGOS/s';
$replacement = $jsArray . "\n        // ===== AUTHENTIC BRAND SVG LOGOS";

$newAiPhp = preg_replace($pattern, $replacement, $aiPhp);

// Simplify renderModelsList (no need for paid tag since all are 100% free)
$oldRender = '                    const paidTag = m.isPaid ? \'<span class="md-item-badge-white" style="color:#b45309; background:#fef3c7; border-color:#fde68a;">💎 Paid</span>\' : \'<span class="md-item-badge-white" style="color:#15803d; background:#dcfce7; border-color:#bbf7d0;">✨ Free</span>\';
                    row.innerHTML = `
                        <div class="md-item-left-white">
                            <span class="md-item-icon-white">${iconSvg}</span>
                            <span>${m.name}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:5px;">
                            ${paidTag}
                            <span class="md-item-badge-white">${m.badge}</span>
                        </div>
                    `;';

$newRender = '                    row.innerHTML = `
                        <div class="md-item-left-white">
                            <span class="md-item-icon-white">${iconSvg}</span>
                            <span>${m.name}</span>
                        </div>
                        <span class="md-item-badge-white">${m.badge}</span>
                    `;';

$newAiPhp = str_replace($oldRender, $newRender, $newAiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $newAiPhp);
echo "Successfully updated student/ai.php with only free models!\n";
