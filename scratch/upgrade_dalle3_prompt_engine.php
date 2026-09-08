<?php
// 1. Update student/ai.php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$newGeminiFunction = <<< 'EOD'
        const GOOGLE_GEMINI_KEY = 'AQ.Ab8RN6KhYYAj9hQMSKIADsz9qPRHc1THSWrXDBOq6m9UlhvAJQ';

        async function expandPromptWithGemini(rawPrompt, stylePreset = '') {
            try {
                const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=${encodeURIComponent(GOOGLE_GEMINI_KEY)}`;
                const systemInst = `You are an elite AI Image Prompt Engineer like ChatGPT DALL-E 3 and Midjourney v6.
Your task is to take the user's idea (in Vietnamese or English) and expand it into an extremely detailed, visually stunning English image prompt for Flux.1 AI.

Guidelines:
1. Accurately translate and depict the core subject and scenario (e.g., "mèo phi hành gia" -> adorable cat wearing an intricate NASA astronaut spacesuit and glass helmet floating weightlessly in outer space with nebulae and planet Earth).
2. For memes/coding jokes (e.g. "meme dân dev"), create a hilarious, expressive situation (e.g. frantic programmer at 3 AM with 500 error screens, coffee cups, funny cartoon style).
3. If style is specified (${stylePreset || 'artistic'}), incorporate that aesthetic seamlessly.
4. Describe materials, lighting, background, camera angle, atmospheric depth, and 8k details.
5. Output ONLY the single-paragraph English prompt string. DO NOT output any introductory text, notes, markdown formatting, or quotes.`;

                const payload = {
                    contents: [
                        {
                            parts: [
                                {
                                    text: `${systemInst}\n\nUser Concept: ${rawPrompt}`
                                }
                            ]
                        }
                    ],
                    generationConfig: {
                        temperature: 0.7,
                        maxOutputTokens: 2048
                    }
                };

                const resp = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (resp.ok) {
                    const data = await resp.json();
                    if (data.candidates && data.candidates[0] && data.candidates[0].content && data.candidates[0].content.parts) {
                        let text = data.candidates[0].content.parts[0].text.trim();
                        text = text.replace(/^["']|["']$/g, '').trim();
                        if (text.length > 20) return text;
                    }
                }
            } catch (err) {
                console.warn('Gemini Direct Client Call error:', err);
            }

            // Fallback via PHP proxy if direct call was blocked
            try {
                const proxyResp = await fetch('/tkb/api/login.php?gemini', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt: rawPrompt, style: stylePreset, task: 'image_prompt' })
                });
                const proxyData = await proxyResp.json();
                if (proxyData.success && proxyData.prompt && proxyData.prompt.length > 20) {
                    return proxyData.prompt;
                }
            } catch(e) {}

            return rawPrompt;
        }

        async function generateAiImage() {
            const promptInput = document.getElementById('imgPromptInput');
            const rawPrompt = (promptInput ? promptInput.value : '').trim();
            if (!rawPrompt) {
                alert('Vui lòng nhập mô tả bức ảnh bạn muốn tạo!');
                if (promptInput) promptInput.focus();
                return;
            }

            const placeholder = document.getElementById('imgPlaceholder');
            const loadingState = document.getElementById('imgLoadingState');
            const displayImg = document.getElementById('imgResultDisplay');
            const toolbar = document.getElementById('imgOverlayToolbar');
            const btnGen = document.getElementById('btnGenImage');

            if (placeholder) placeholder.style.display = 'none';
            if (displayImg) displayImg.style.display = 'none';
            if (toolbar) toolbar.style.display = 'none';
            if (loadingState) {
                loadingState.style.display = 'block';
                loadingState.innerHTML = `
                    <i class="fa-solid fa-wand-magic-sparkles fa-spin" style="font-size:36px; color:#10b981; margin-bottom:14px; display:inline-block;"></i>
                    <div style="font-size:15px; font-weight:700; color:#f8fafc;">Google AI Studio (DALL-E 3 Mode) đang sáng tạo...</div>
                    <div style="font-size:12px; color:#94a3b8; margin-top:4px;" id="geminiStatusSubText">Đang phân tích và mở rộng ý tưởng "${rawPrompt}"</div>
                `;
            }
            if (btnGen) {
                btnGen.disabled = true;
                btnGen.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Google AI đang vẽ...</span>';
            }

            // 1. Process and expand prompt via Google AI Studio Gemini DALL-E 3 Engine
            const masterPrompt = await expandPromptWithGemini(rawPrompt, selectedImgStyle);

            const statusSub = document.getElementById('geminiStatusSubText');
            if (statusSub) {
                statusSub.textContent = `Đang render tác phẩm 8K siêu chi tiết...`;
            }

            // 2. Build final prompt for Flux.1 AI Image Engine
            const fullPrompt = selectedImgStyle ? `${masterPrompt}, ${selectedImgStyle}` : masterPrompt;
            const seed = Math.floor(Math.random() * 1000000);
            const encodedPrompt = encodeURIComponent(fullPrompt);
            const imageUrl = `https://image.pollinations.ai/prompt/${encodedPrompt}?width=${selectedImgWidth}&height=${selectedImgHeight}&seed=${seed}&nologo=true&model=flux`;

            const img = new Image();
            img.onload = function() {
                activeGeneratedImageUrl = imageUrl;
                if (loadingState) loadingState.style.display = 'none';
                if (displayImg) {
                    displayImg.src = imageUrl;
                    displayImg.style.display = 'block';
                }
                if (toolbar) toolbar.style.display = 'flex';
                if (btnGen) {
                    btnGen.disabled = false;
                    btnGen.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> <span>Tạo ảnh AI ngay</span>';
                }

                // Save to history gallery with master prompt
                imageGallery.unshift({
                    url: imageUrl,
                    prompt: rawPrompt,
                    enhancedPrompt: masterPrompt,
                    time: new Date().toLocaleTimeString()
                });
                if (imageGallery.length > 30) imageGallery.pop();
                localStorage.setItem('vkc_ai_img_gallery', JSON.stringify(imageGallery));
                renderImageGallery();
            };

            img.onerror = function() {
                if (loadingState) loadingState.style.display = 'none';
                if (placeholder) placeholder.style.display = 'block';
                if (btnGen) {
                    btnGen.disabled = false;
                    btnGen.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> <span>Tạo ảnh AI ngay</span>';
                }
                alert('Lỗi kết nối máy chủ vẽ tranh! Vui lòng thử lại với mô tả khác.');
            };

            img.src = imageUrl;
        }
EOD;

$pattern = '/const GOOGLE_GEMINI_KEY = .*?img\.src = imageUrl;\s*\}/s';
$aiPhp = preg_replace($pattern, $newGeminiFunction, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Upgraded prompt engineer in ai.php to DALL-E 3 master level!\n";
