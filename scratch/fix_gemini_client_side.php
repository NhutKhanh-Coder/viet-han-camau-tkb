<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$newGenImg = <<< 'EOD'
        const GOOGLE_GEMINI_KEY = 'AQ.Ab8RN6KhYYAj9hQMSKIADsz9qPRHc1THSWrXDBOq6m9UlhvAJQ';

        async function expandPromptWithGemini(rawPrompt) {
            try {
                const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=${encodeURIComponent(GOOGLE_GEMINI_KEY)}`;
                const payload = {
                    contents: [
                        {
                            parts: [
                                {
                                    text: `You are an elite Google AI Studio Image Prompt Engineer. Convert this user idea (Vietnamese/English) into an extremely vivid, creative, highly descriptive single-sentence English image prompt for Flux.1 / Stable Diffusion image generation. Focus on character emotion, comedic elements, specific objects, lighting, and 8k detail. Output ONLY the English prompt string, without any other text, prefix, or quotes. User idea: ${rawPrompt}`
                                }
                            ]
                        }
                    ],
                    generationConfig: {
                        temperature: 0.7,
                        maxOutputTokens: 300
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
                        if (text.length > 10) return text;
                    }
                }
            } catch (err) {
                console.warn('Gemini Client Direct Call error:', err);
            }

            // Fallback via PHP proxy if direct call was blocked
            try {
                const proxyResp = await fetch('/tkb/api/login.php?gemini', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt: rawPrompt, task: 'image_prompt' })
                });
                const proxyData = await proxyResp.json();
                if (proxyData.success && proxyData.prompt) return proxyData.prompt;
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
                    <div style="font-size:15px; font-weight:700; color:#f8fafc;">Google AI Studio đang sáng tạo & vẽ tranh...</div>
                    <div style="font-size:12px; color:#94a3b8; margin-top:4px;" id="geminiStatusSubText">Gemini 3.6 Flash Engine · Đang phân tích ý tưởng "${rawPrompt}"</div>
                `;
            }
            if (btnGen) {
                btnGen.disabled = true;
                btnGen.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Google AI Studio đang vẽ...</span>';
            }

            // 1. Process and expand prompt via Google AI Studio Gemini 3.6 Flash
            const englishPrompt = await expandPromptWithGemini(rawPrompt);

            const statusSub = document.getElementById('geminiStatusSubText');
            if (statusSub) {
                statusSub.textContent = `Đang render ảnh độ phân giải cao 8K...`;
            }

            // 2. Build full prompt with artistic style preset
            const fullPrompt = selectedImgStyle ? `${englishPrompt}, ${selectedImgStyle}` : englishPrompt;
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

                // Save to history gallery
                imageGallery.unshift({
                    url: imageUrl,
                    prompt: rawPrompt,
                    enhancedPrompt: englishPrompt,
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

// Replace generateAiImage implementation
$pattern = '/async function generateAiImage\(\) \{.*?img\.src = imageUrl;\s*\}/s';
$aiPhp = preg_replace($pattern, $newGenImg, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully updated student/ai.php with direct Google AI Studio client integration!\n";
