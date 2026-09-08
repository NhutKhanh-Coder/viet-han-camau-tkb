<?php
// 1. Update api/login.php to include Google AI Studio Gemini endpoint
$loginPhp = file_get_contents(__DIR__ . '/../api/login.php');

$geminiProxyCode = <<< 'EOD'
// ══ GOOGLE AI STUDIO (GEMINI 3.6 FLASH) PROXY ══════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['gemini'])) {
    $GEMINI_KEY = 'AQ.Ab8RN6KhYYAj9hQMSKIADsz9qPRHc1THSWrXDBOq6m9UlhvAJQ';
    $body = file_get_contents('php://input');
    $inputData = json_decode($body, true) ?? [];
    $userPrompt = trim($inputData['prompt'] ?? '');
    $userTask = trim($inputData['task'] ?? 'image_prompt');

    if (!$userPrompt) {
        sendJsonResponse(['success' => false, 'message' => 'Prompt không được để trống']);
    }

    $systemInstruction = "You are an elite Google AI Studio Prompt Engineer. Convert user input (Vietnamese or English idea, coding meme, scene, characters) into a highly vivid, descriptive single-sentence English image prompt for Flux.1 / Stable Diffusion XL. Focus on expressive character actions, comedic elements, lighting, style, 8k quality. Output ONLY the English prompt text, no explanations, no quotes.";

    if ($userTask === 'chat') {
        $systemInstruction = "You are Google Gemini, an intelligent, helpful AI assistant for students at Viet Han Ca Mau College.";
    }

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $systemInstruction . "\n\nUser request: " . $userPrompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 500
        ]
    ];

    $models = ['gemini-3.6-flash', 'gemini-flash-latest', 'gemini-3.5-flash'];
    $resultText = null;

    foreach ($models as $m) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key=" . urlencode($GEMINI_KEY);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $GEMINI_KEY
            ],
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $resultText = trim($data['candidates'][0]['content']['parts'][0]['text']);
                $resultText = trim(trim($resultText), '"\'');
                break;
            }
        }
    }

    if (ob_get_length()) @ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    if ($resultText) {
        echo json_encode([
            'success' => true,
            'model' => 'Google AI Studio (Gemini 3.6 Flash)',
            'prompt' => $resultText
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi xử lý Google AI Studio',
            'prompt' => $userPrompt
        ]);
    }
    exit();
}
// ══ END GOOGLE AI STUDIO PROXY ═════════════════════════════════════
EOD;

if (!strpos($loginPhp, 'GOOGLE AI STUDIO (GEMINI 3.6 FLASH) PROXY')) {
    $loginPhp = str_replace('// ══ AI PROXY (xKiro DeepSeek & Models)', $geminiProxyCode . "\n\n// ══ AI PROXY (xKiro DeepSeek & Models)", $loginPhp);
    file_put_contents(__DIR__ . '/../api/login.php', $loginPhp);
    echo "Updated api/login.php with Google AI Studio Gemini proxy!\n";
}

// 2. Update student/ai.php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$oldGenImg = '        async function generateAiImage() {
            const promptInput = document.getElementById(\'imgPromptInput\');
            const rawPrompt = (promptInput ? promptInput.value : \'\').trim();
            if (!rawPrompt) {
                alert(\'Vui lòng nhập mô tả bức ảnh bạn muốn tạo!\');
                if (promptInput) promptInput.focus();
                return;
            }

            const placeholder = document.getElementById(\'imgPlaceholder\');
            const loadingState = document.getElementById(\'imgLoadingState\');
            const displayImg = document.getElementById(\'imgResultDisplay\');
            const toolbar = document.getElementById(\'imgOverlayToolbar\');
            const btnGen = document.getElementById(\'btnGenImage\');

            if (placeholder) placeholder.style.display = \'none\';
            if (displayImg) displayImg.style.display = \'none\';
            if (toolbar) toolbar.style.display = \'none\';
            if (loadingState) loadingState.style.display = \'block\';
            if (btnGen) {
                btnGen.disabled = true;
                btnGen.innerHTML = \'<i class="fa-solid fa-circle-notch fa-spin"></i> <span>AI đang tối ưu & vẽ tranh...</span>\';
            }

            let englishPrompt = rawPrompt;

            // Check if prompt has Vietnamese characters or needs translation/enhancement
            const isVietnamese = /[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/i.test(rawPrompt) || rawPrompt.length < 30;

            if (isVietnamese) {
                try {
                    const transResp = await fetch(\'/tkb/api/login.php?groq\', {
                        method: \'POST\',
                        headers: { \'Content-Type\': \'application/json\' },
                        body: JSON.stringify({
                            model: \'deepseek/deepseek-chat-v3.1\',
                            messages: [
                                { role: \'system\', content: \'You are an AI image prompt translator and expander. Convert user input (especially Vietnamese concepts, memes, coding jokes, characters) into a highly descriptive English image prompt for Flux/SDXL. Output ONLY the English prompt string, without quotes or explanations.\' },
                                { role: \'user\', content: rawPrompt }
                            ],
                            temperature: 0.5
                        })
                    });
                    const transData = await transResp.json();
                    if (transData.choices && transData.choices[0] && transData.choices[0].message) {
                        englishPrompt = transData.choices[0].message.content.trim();
                    }
                } catch(e) {
                    // Fallback to raw prompt if translation API fails
                    englishPrompt = rawPrompt;
                }
            }';

$newGenImg = '        async function generateAiImage() {
            const promptInput = document.getElementById(\'imgPromptInput\');
            const rawPrompt = (promptInput ? promptInput.value : \'\').trim();
            if (!rawPrompt) {
                alert(\'Vui lòng nhập mô tả bức ảnh bạn muốn tạo!\');
                if (promptInput) promptInput.focus();
                return;
            }

            const placeholder = document.getElementById(\'imgPlaceholder\');
            const loadingState = document.getElementById(\'imgLoadingState\');
            const displayImg = document.getElementById(\'imgResultDisplay\');
            const toolbar = document.getElementById(\'imgOverlayToolbar\');
            const btnGen = document.getElementById(\'btnGenImage\');

            if (placeholder) placeholder.style.display = \'none\';
            if (displayImg) displayImg.style.display = \'none\';
            if (toolbar) toolbar.style.display = \'none\';
            if (loadingState) {
                loadingState.style.display = \'block\';
                loadingState.innerHTML = `
                    <i class="fa-solid fa-wand-magic-sparkles fa-spin" style="font-size:36px; color:#10b981; margin-bottom:14px; display:inline-block;"></i>
                    <div style="font-size:15px; font-weight:700; color:#f8fafc;">Google AI Studio đang phân tích & vẽ tranh...</div>
                    <div style="font-size:12px; color:#94a3b8; margin-top:4px;">Gemini 3.6 Flash Engine · Tối ưu hoá prompt chuẩn điện ảnh</div>
                `;
            }
            if (btnGen) {
                btnGen.disabled = true;
                btnGen.innerHTML = \'<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Google AI Studio đang xử lý...</span>\';
            }

            let englishPrompt = rawPrompt;

            // Process prompt via Google AI Studio (Gemini 3.6 Flash)
            try {
                const geminiResp = await fetch(\'/tkb/api/login.php?gemini\', {
                    method: \'POST\',
                    headers: { \'Content-Type\': \'application/json\' },
                    body: JSON.stringify({
                        prompt: rawPrompt,
                        task: \'image_prompt\'
                    })
                });
                const geminiData = await geminiResp.json();
                if (geminiData.success && geminiData.prompt) {
                    englishPrompt = geminiData.prompt;
                }
            } catch(e) {
                console.warn(\'Gemini Proxy Warning:\', e);
                englishPrompt = rawPrompt;
            }';

$aiPhp = str_replace($oldGenImg, $newGenImg, $aiPhp);

// Add Google AI Studio badge in Prompt box
$aiPhp = str_replace(
    '<span>Mô tả bức ảnh (Prompt)</span>',
    '<span>Mô tả bức ảnh</span> <span style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.3); font-size:10px; font-weight:800; padding:2px 6px; border-radius:6px; margin-left:6px;"><i class="fa-brands fa-google"></i> AI Studio</span>',
    $aiPhp
);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Updated student/ai.php with Google AI Studio Gemini integration!\n";
