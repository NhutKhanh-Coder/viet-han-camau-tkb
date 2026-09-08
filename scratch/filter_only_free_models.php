<?php
$models = json_decode(file_get_contents(__DIR__ . '/formatted_87_models_tagged.json'), true);

$freeModels = array_values(array_filter($models, function($m) {
    return empty($m['isPaid']);
}));

echo "Total Free models: " . count($freeModels) . "\n";
foreach ($freeModels as $fm) {
    echo "• {$fm['name']} ({$fm['id']})\n";
}

// Update restore_full_ai_script.php or directly update student/ai.php
file_put_contents(__DIR__ . '/free_models_only.json', json_encode($freeModels, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$freeModelsJs = json_encode($freeModels, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// Replace ALL_MODELS definition in ai.php
$pattern = '/const ALL_MODELS = \[.*?\];/s';
$replacement = "const ALL_MODELS = " . $freeModelsJs . ";";

$aiPhp = preg_replace($pattern, $replacement, $aiPhp);

// In renderModelsList, simplify row display to only show Free badge
$oldRowHtml = "                const paidTag = m.isPaid ? '<span class=\"md-item-badge-white\" style=\"color:#b45309; background:#fef3c7; border-color:#fde68a;\">💎 Paid</span>' : '<span class=\"md-item-badge-white\" style=\"color:#15803d; background:#dcfce7; border-color:#bbf7d0;\">✨ Free</span>';

                row.innerHTML = `
                    <div class=\"md-item-left-white\" style=\"display:flex; align-items:center; gap:8px;\">
                        <i class=\"fa-solid fa-bolt\" style=\"color:\${m.isPaid ? '#f59e0b' : '#0ea5e9'};\x60></i>
                        <span style=\"font-weight:700; font-size:13px; color:#0f172a;\">\${m.name}</span>
                    </div>
                    <div style=\"display:flex; align-items:center; gap:5px;\">
                        \${paidTag}
                        <span class=\"md-item-badge-white\" style=\"font-size:11px; padding:2px 6px; border-radius:6px; background:#f1f5f9; color:#475569;\">\${m.badge || '128K'}</span>
                    </div>
                `;";

$newRowHtml = '                row.innerHTML = `
                    <div class="md-item-left-white" style="display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-bolt" style="color:#0ea5e9;"></i>
                        <span style="font-weight:700; font-size:13px; color:#0f172a;">${m.name}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:5px;">
                        <span class="md-item-badge-white" style="color:#15803d; background:#dcfce7; border-color:#bbf7d0; font-size:11px; font-weight:700; padding:2px 7px; border-radius:6px;">✨ Free</span>
                        <span class="md-item-badge-white" style="font-size:11px; padding:2px 6px; border-radius:6px; background:#f1f5f9; color:#475569;">${m.badge || '128K'}</span>
                    </div>
                `;';

$patternRow = '/const paidTag = m\.isPaid.*?container\.appendChild\(row\);/s';
$replacementRow = $newRowHtml . "\n                container.appendChild(row);";

$aiPhp = preg_replace($patternRow, $replacementRow, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully updated ai.php with only free models!\n";
