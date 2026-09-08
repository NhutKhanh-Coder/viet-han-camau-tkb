<?php
$models = json_decode(file_get_contents(__DIR__ . '/formatted_87_models_tagged.json'), true);

$jsArray = "        // ===== ALL 87 xKiro MODELS DATASET (WITH PAID/FREE TAGS) =====\n        const ALL_MODELS = " . json_encode($models, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ";\n";

$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$pattern = '/\/\/ ===== (?:WORKING|ALL 87) xKiro MODELS DATASET.*?\/\/ ===== AUTHENTIC BRAND SVG LOGOS/s';
$replacement = $jsArray . "\n        // ===== AUTHENTIC BRAND SVG LOGOS";

$newAiPhp = preg_replace($pattern, $replacement, $aiPhp);

// Update renderModelsList
$oldRender = '                    row.innerHTML = `
                        <div class="md-item-left-white">
                            <span class="md-item-icon-white">${iconSvg}</span>
                            <span>${m.name}</span>
                        </div>
                        <span class="md-item-badge-white">${m.badge}</span>
                    `;';

$newRender = '                    const paidTag = m.isPaid ? \'<span class="md-item-badge-white" style="color:#b45309; background:#fef3c7; border-color:#fde68a;">💎 Paid</span>\' : \'<span class="md-item-badge-white" style="color:#15803d; background:#dcfce7; border-color:#bbf7d0;">✨ Free</span>\';
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

$newAiPhp = str_replace($oldRender, $newRender, $newAiPhp);

// Update error handling in sendMsg
$oldErr = '                    let errText = data.error.message;
                    if (errText.includes(\'requires real deposited balance\') || errText.includes(\'premium model\')) {
                        errText = `⚠️ **Mô hình ${currentSelectedModel.name} là dòng Premium (cần nạp số dư ví trên xKiro).**\n\n💡 **Gợi ý:** Bạn có thể chuyển sang chọn các mô hình **Miễn phí & Tốc độ cao** như:\n- ⚡ **DeepSeek V3.1 / DeepSeek V4 Flash**\n- 🚀 **Qwen 3.5 Flash / Qwen 3 Coder Plus**\n- 🌪️ **Ministral 3 8B / Codestral**\n- 🌟 **MiniMax M2.5 / GLM-5 Turbo**\nđể trò chuyện mượt mà không tốn phí nhé!`;
                    }
                    appendMsg(\'bot\', errText);';

$newErr = '                    let errText = data.error.message || "";
                    if (errText.includes(\'paid model\') || errText.includes(\'deposited balance\') || errText.includes(\'premium model\') || errText.includes(\'top up your wallet\') || errText.includes(\'Free plan only allows\')) {
                        errText = `⚠️ **Mô hình ${currentSelectedModel.name} thuộc dòng Trả phí / Premium trên xKiro.**\n\n💡 **Gợi ý:** Bạn có thể chuyển sang các mô hình **Miễn phí 100% & Tốc độ cao** dưới đây để chat ngay:\n• ⚡ **DeepSeek V3.1** (Trí tuệ lập trình & tiếng Việt xuất sắc)\n• 🚀 **Qwen 3.5 Flash** (Phản hồi tức thì)\n• 🌪️ **Codestral 2508** (Chuyên gia lập trình)\n• 🌟 **MiniMax M2.5** (Tự nhiên, ngữ cảnh lớn)\n\n*(Bấm nút chọn model bên dưới thanh chat để đổi sang mô hình Miễn phí nhé!)*`;
                    }
                    appendMsg(\'bot\', errText);';

$newAiPhp = str_replace($oldErr, $newErr, $newAiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $newAiPhp);
echo "Successfully updated student/ai.php with tagged models and enhanced error handling!\n";
