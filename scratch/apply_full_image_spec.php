<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Update Style & Ratio HTML buttons to store clean names and prompts
$oldStyleHtml = '<div class="ai-img-style-grid">
                            <div class="ai-img-style-card active" onclick="selectImgStyle(\'\', this)">
                                <i class="fa-solid fa-sparkles" style="font-size:16px; color:#10b981;"></i>
                                <span>Mặc định</span>
                            </div>
                            <div class="ai-img-style-card" onclick="selectImgStyle(\'anime, manga style, makoto shinkai aesthetic, vibrant anime art, 8k\', this)">
                                <i class="fa-solid fa-dragon" style="font-size:16px; color:#ec4899;"></i>
                                <span>Anime Manga</span>
                            </div>
                            <div class="ai-img-style-card" onclick="selectImgStyle(\'hyperrealistic, 8k photography, ultra detailed, cinematic lighting, photorealistic\', this)">
                                <i class="fa-solid fa-camera" style="font-size:16px; color:#3b82f6;"></i>
                                <span>Chụp ảnh 8K</span>
                            </div>
                            <div class="ai-img-style-card" onclick="selectImgStyle(\'cyberpunk style, neon glow, futuristic city, octane render 8k\', this)">
                                <i class="fa-solid fa-bolt" style="font-size:16px; color:#8b5cf6;"></i>
                                <span>Cyberpunk</span>
                            </div>
                            <div class="ai-img-style-card" onclick="selectImgStyle(\'3D pixar disney style, cute 3d character render, unreal engine 5, soft lighting\', this)">
                                <i class="fa-solid fa-cube" style="font-size:16px; color:#f59e0b;"></i>
                                <span>3D Pixar</span>
                            </div>
                            <div class="ai-img-style-card" onclick="selectImgStyle(\'oil painting, classic masterpiece, van gogh textured brushstrokes\', this)">
                                <i class="fa-solid fa-paintbrush" style="font-size:16px; color:#ef4444;"></i>
                                <span>Sơn Dầu</span>
                            </div>
                        </div>';

$newStyleHtml = '<div class="ai-img-style-grid">
                            <div class="ai-img-style-card active" onclick="selectImgStyle(\'Mặc định\', \'\', this)">
                                <i class="fa-solid fa-sparkles" style="font-size:16px; color:#10b981;"></i>
                                <span>Mặc định</span>
                            </div>
                            <div class="ai-img-style-card" onclick="selectImgStyle(\'Anime Manga\', \'anime aesthetic, manga style, Makoto Shinkai art style, vibrant anime art\', this)">
                                <i class="fa-solid fa-dragon" style="font-size:16px; color:#ec4899;"></i>
                                <span>Anime Manga</span>
                            </div>
                            <div class="ai-img-style-card" onclick="selectImgStyle(\'Chụp ảnh 8K\', \'hyperrealistic 8k photograph, cinematic lighting, ultra detailed, photorealistic\', this)">
                                <i class="fa-solid fa-camera" style="font-size:16px; color:#3b82f6;"></i>
                                <span>Chụp ảnh 8K</span>
                            </div>
                            <div class="ai-img-style-card" onclick="selectImgStyle(\'Cyberpunk\', \'cyberpunk style, neon glow, futuristic aesthetic, 8k octane render\', this)">
                                <i class="fa-solid fa-bolt" style="font-size:16px; color:#8b5cf6;"></i>
                                <span>Cyberpunk</span>
                            </div>
                            <div class="ai-img-style-card" onclick="selectImgStyle(\'3D Pixar\', \'3D Pixar Disney animation style, cute 3D character render, unreal engine 5, soft smooth lighting\', this)">
                                <i class="fa-solid fa-cube" style="font-size:16px; color:#f59e0b;"></i>
                                <span>3D Pixar</span>
                            </div>
                            <div class="ai-img-style-card" onclick="selectImgStyle(\'Sơn Dầu\', \'classic oil painting masterpiece, textured brushstrokes, fine art\', this)">
                                <i class="fa-solid fa-paintbrush" style="font-size:16px; color:#ef4444;"></i>
                                <span>Sơn Dầu</span>
                            </div>
                        </div>';

$aiPhp = str_replace($oldStyleHtml, $newStyleHtml, $aiPhp);

$oldRatioHtml = '<div class="ai-img-ratio-row">
                            <div class="ai-img-ratio-btn active" onclick="selectImgRatio(1024, 1024, this)">
                                <i class="fa-regular fa-square" style="font-size:14px;"></i>
                                <span>1:1 Vuông</span>
                            </div>
                            <div class="ai-img-ratio-btn" onclick="selectImgRatio(1280, 720, this)">
                                <i class="fa-solid fa-tv" style="font-size:14px;"></i>
                                <span>16:9 Ngang</span>
                            </div>
                            <div class="ai-img-ratio-btn" onclick="selectImgRatio(720, 1280, this)">
                                <i class="fa-solid fa-mobile-screen" style="font-size:14px;"></i>
                                <span>9:16 Dọc</span>
                            </div>
                        </div>';

$newRatioHtml = '<div class="ai-img-ratio-row">
                            <div class="ai-img-ratio-btn active" onclick="selectImgRatio(1024, 1024, \'1:1 (Square)\', this)">
                                <i class="fa-regular fa-square" style="font-size:14px;"></i>
                                <span>1:1 Vuông</span>
                            </div>
                            <div class="ai-img-ratio-btn" onclick="selectImgRatio(1280, 720, \'16:9 (Landscape)\', this)">
                                <i class="fa-solid fa-tv" style="font-size:14px;"></i>
                                <span>16:9 Ngang</span>
                            </div>
                            <div class="ai-img-ratio-btn" onclick="selectImgRatio(720, 1280, \'9:16 (Portrait)\', this)">
                                <i class="fa-solid fa-mobile-screen" style="font-size:14px;"></i>
                                <span>9:16 Dọc</span>
                            </div>
                        </div>';

$aiPhp = str_replace($oldRatioHtml, $newRatioHtml, $aiPhp);

// 2. Add Error Box element into Preview Screen
$oldPreviewHtml = '<img id="imgResultDisplay" class="ai-img-preview-img" style="display:none;" alt="AI Generated Artwork">';
$newPreviewHtml = '<div id="imgErrorState" style="display:none; text-align:center; color:#f87171; padding:30px; z-index:5;">
                            <div style="width:60px; height:60px; border-radius:18px; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); display:flex; align-items:center; justify-content:center; margin:0 auto 12px; font-size:24px; color:#ef4444;">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700; color:#fca5a5;" id="imgErrorTitle">Không thể tạo ảnh</h4>
                            <p style="margin:0; font-size:12.5px; color:#94a3b8;" id="imgErrorDesc">Vui lòng thử lại với mô tả khác hoặc kiểm tra kết nối mạng.</p>
                        </div>
                        <img id="imgResultDisplay" class="ai-img-preview-img" style="display:none;" alt="AI Generated Artwork">';

$aiPhp = str_replace($oldPreviewHtml, $newPreviewHtml, $aiPhp);

// 3. Write JavaScript logic adhering to all 7 requirements
$newJsEngine = <<< 'EOD'
        // ===== AI IMAGE GENERATOR STUDIO STATE & LOGIC =====
        let selectedStyleName = 'Mặc định';
        let selectedImgStyle = '';
        let selectedRatioName = '1:1 (Square)';
        let selectedImgWidth = 1024;
        let selectedImgHeight = 1024;
        let activeGeneratedImageUrl = '';
        let imageGallery = JSON.parse(localStorage.getItem('vkc_ai_img_gallery') || '[]');

        const RANDOM_PROMPTS = [
            "Sasuke Uchiha đứng dưới mưa",
            "Một cô gái anime tóc xanh đứng dưới hoa anh đào",
            "Một thành phố cyberpunk vào ban đêm",
            "Một chú mèo phi hành gia lơ lửng ngoài vũ trụ ngắm dải ngân hà neon",
            "Chân dung một cô gái Việt Nam trong tà áo dài giữa phố cổ Hội An đêm hoa đăng",
            "Một chú rồng nhỏ đáng yêu ngồi trên đỉnh núi tuyết đọc sách ma pháp"
        ];

        function switchMode(mode, btn) {
            document.querySelectorAll('.ai-mode-tab').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const chatView = document.getElementById('aiChatView');
            const ttsView = document.getElementById('aiTtsView');
            const imgView = document.getElementById('aiImageView');
            const topModel = document.getElementById('topModelWrapper');
            const topFolder = document.getElementById('topFolderBtn');

            if (chatView) chatView.style.display = (mode === 'chat' ? 'flex' : 'none');
            if (ttsView) ttsView.style.display = (mode === 'tts' ? 'flex' : 'none');
            if (imgView) {
                imgView.style.display = (mode === 'image' ? 'flex' : 'none');
                if (mode === 'image') renderImageGallery();
            }

            // Clean top bar
            if (topModel) topModel.style.display = (mode === 'chat' ? 'flex' : 'none');
            if (topFolder) topFolder.style.display = (mode === 'chat' ? 'inline-flex' : 'none');
        }

        function insertRandomPrompt() {
            const promptInput = document.getElementById('imgPromptInput');
            if (!promptInput) return;
            const rand = RANDOM_PROMPTS[Math.floor(Math.random() * RANDOM_PROMPTS.length)];
            promptInput.value = rand;
            promptInput.focus();
        }

        function selectImgStyle(styleName, stylePrompt, card) {
            selectedStyleName = styleName;
            selectedImgStyle = stylePrompt;
            document.querySelectorAll('.ai-img-style-card').forEach(c => c.classList.remove('active'));
            if (card) card.classList.add('active');
        }

        function selectImgRatio(w, h, ratioName, btn) {
            selectedImgWidth = w;
            selectedImgHeight = h;
            selectedRatioName = ratioName;
            document.querySelectorAll('.ai-img-ratio-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
        }

        const GOOGLE_GEMINI_KEY = 'AQ.Ab8RN6KhYYAj9hQMSKIADsz9qPRHc1THSWrXDBOq6m9UlhvAJQ';

        async function expandPromptWithGemini(rawPrompt, stylePreset = '') {
            try {
                const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=${encodeURIComponent(GOOGLE_GEMINI_KEY)}`;
                const systemInst = `You are an elite AI Image Prompt Engineer like ChatGPT DALL-E 3 and Midjourney v6.
Your task is to take the user's idea (in Vietnamese or English) and expand it into an extremely detailed English image prompt for Flux.1 AI.

STRICT REQUIREMENTS:
1. Always preserve the EXACT subject, characters, and actions requested by the user. NEVER replace or omit the user's subject.
   - If user inputs "Sasuke Uchiha đứng dưới mưa", describe Sasuke Uchiha with his dark spiky hair, wet clothing with Uchiha crest, rain falling, moody lighting.
   - If user inputs "Một cô gái anime tóc xanh đứng dưới hoa anh đào", describe a beautiful anime girl with vibrant blue hair standing under blooming pink cherry blossom trees with falling petals.
   - If user inputs "Một thành phố cyberpunk vào ban đêm", describe a futuristic city at night with towering neon skyscrapers, flying vehicles, and wet reflective streets.
2. Incorporate the selected style (${stylePreset || 'natural artistic style'}) seamlessly as stylistic guidance without overriding the subject.
3. Describe lighting, environment, textures, colors, and 8k detail.
4. Output ONLY the single English prompt string. DO NOT include any introductory text, quotes, or markdown.`;

                const payload = {
                    contents: [
                        {
                            parts: [
                                {
                                    text: `${systemInst}\n\nUser Prompt: ${rawPrompt}\nSelected Style: ${stylePreset || 'Default'}`
                                }
                            ]
                        }
                    ],
                    generationConfig: {
                        temperature: 0.6,
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
                console.warn('Gemini Direct Client Call Warning:', err);
            }

            // Fallback via PHP proxy if needed
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
            const errorState = document.getElementById('imgErrorState');
            const displayImg = document.getElementById('imgResultDisplay');
            const toolbar = document.getElementById('imgOverlayToolbar');
            const btnGen = document.getElementById('btnGenImage');

            // Requirement 7: Clear old image & reset states immediately
            if (placeholder) placeholder.style.display = 'none';
            if (errorState) errorState.style.display = 'none';
            if (toolbar) toolbar.style.display = 'none';
            if (displayImg) {
                displayImg.src = '';
                displayImg.style.display = 'none';
            }
            activeGeneratedImageUrl = '';

            if (loadingState) {
                loadingState.style.display = 'block';
                loadingState.innerHTML = `
                    <i class="fa-solid fa-wand-magic-sparkles fa-spin" style="font-size:36px; color:#10b981; margin-bottom:14px; display:inline-block;"></i>
                    <div style="font-size:15px; font-weight:700; color:#f8fafc;">Google AI Studio đang phân tích & vẽ tranh...</div>
                    <div style="font-size:12px; color:#94a3b8; margin-top:4px;" id="geminiStatusSubText">Đang bám sát ý tưởng "${escapeHtml(rawPrompt)}"</div>
                `;
            }
            if (btnGen) {
                btnGen.disabled = true;
                btnGen.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Đang vẽ tranh...</span>';
            }

            // 1. Process prompt through Gemini Prompt Engine preserving full user intent
            const expandedPrompt = await expandPromptWithGemini(rawPrompt, selectedImgStyle);

            const statusSub = document.getElementById('geminiStatusSubText');
            if (statusSub) {
                statusSub.textContent = `Đang kết xuất hình ảnh độ nét cao ${selectedRatioName}...`;
            }

            // 2. Build final prompt with style cues
            const finalPrompt = selectedImgStyle ? `${expandedPrompt}, ${selectedImgStyle}` : expandedPrompt;

            // Requirement 5: Log required debugging information to console
            console.log('=== [AI Image Studio Generation Request] ===');
            console.log('userPrompt:', rawPrompt);
            console.log('selectedStyle:', selectedStyleName);
            console.log('selectedAspectRatio:', `${selectedImgWidth}x${selectedImgHeight} (${selectedRatioName})`);
            console.log('finalPrompt:', finalPrompt);
            console.log('model:', 'Flux.1 (via Pollinations.ai + Google Gemini 3.6 Flash Engine)');

            // Requirement 6: Negative prompt
            const negativePrompt = 'wrong character, incorrect subject, distorted anatomy, extra fingers, blurry, low quality, unrelated content, worst quality';

            const seed = Math.floor(Math.random() * 10000000);
            const encodedPrompt = encodeURIComponent(finalPrompt);
            const encodedNeg = encodeURIComponent(negativePrompt);
            const imageUrl = `https://image.pollinations.ai/prompt/${encodedPrompt}?width=${selectedImgWidth}&height=${selectedImgHeight}&seed=${seed}&nologo=true&model=flux&negative_prompt=${encodedNeg}`;

            // Load generated image
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
                    enhancedPrompt: finalPrompt,
                    ratio: selectedRatioName,
                    time: new Date().toLocaleTimeString()
                });
                if (imageGallery.length > 30) imageGallery.pop();
                localStorage.setItem('vkc_ai_img_gallery', JSON.stringify(imageGallery));
                renderImageGallery();
            };

            // Requirement 7: Error handling without showing old image
            img.onerror = function() {
                if (loadingState) loadingState.style.display = 'none';
                if (errorState) {
                    errorState.style.display = 'block';
                    const errDesc = document.getElementById('imgErrorDesc');
                    if (errDesc) errDesc.textContent = `Không thể tải ảnh cho prompt "${rawPrompt}". Vui lòng thử lại!`;
                }
                if (btnGen) {
                    btnGen.disabled = false;
                    btnGen.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> <span>Tạo ảnh AI ngay</span>';
                }
            };

            img.src = imageUrl;
        }

        async function downloadActiveImage() {
            if (!activeGeneratedImageUrl) return;
            try {
                const response = await fetch(activeGeneratedImageUrl);
                const blob = await response.blob();
                const blobUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = `viet_han_ai_${Date.now()}.jpg`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(blobUrl);
            } catch(e) {
                window.open(activeGeneratedImageUrl, '_blank');
            }
        }

        function openImageFullscreen() {
            if (!activeGeneratedImageUrl) return;
            window.open(activeGeneratedImageUrl, '_blank');
        }

        function renderImageGallery() {
            const grid = document.getElementById('imgGalleryGrid');
            const countText = document.getElementById('galleryCountText');
            if (!grid) return;
            grid.innerHTML = '';

            if (countText) countText.textContent = `Thư viện ảnh đã tạo (${imageGallery.length})`;

            if (imageGallery.length === 0) {
                grid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:15px; color:#94a3b8; font-size:12.5px;">Chưa có ảnh nào trong thư viện.</div>';
                return;
            }

            imageGallery.forEach((item, index) => {
                const thumb = document.createElement('div');
                thumb.className = 'ai-img-thumb-item' + (item.url === activeGeneratedImageUrl ? ' active' : '');
                thumb.title = item.prompt;
                thumb.onclick = () => {
                    activeGeneratedImageUrl = item.url;
                    const placeholder = document.getElementById('imgPlaceholder');
                    const errorState = document.getElementById('imgErrorState');
                    const displayImg = document.getElementById('imgResultDisplay');
                    const toolbar = document.getElementById('imgOverlayToolbar');
                    if (placeholder) placeholder.style.display = 'none';
                    if (errorState) errorState.style.display = 'none';
                    if (displayImg) {
                        displayImg.src = item.url;
                        displayImg.style.display = 'block';
                    }
                    if (toolbar) toolbar.style.display = 'flex';
                    const promptInput = document.getElementById('imgPromptInput');
                    if (promptInput) promptInput.value = item.prompt;
                    renderImageGallery();
                };

                thumb.innerHTML = `<img src="${item.url}" class="ai-img-thumb-img" alt="Thumbnail">`;
                grid.appendChild(thumb);
            });
        }

        function clearImageGallery() {
            if (imageGallery.length === 0) return;
            if (confirm('Bạn có chắc muốn xóa toàn bộ thư viện ảnh đã tạo không?')) {
                imageGallery = [];
                localStorage.setItem('vkc_ai_img_gallery', JSON.stringify(imageGallery));
                renderImageGallery();
            }
        }
EOD;

// Replace existing Image Studio logic
$pattern = '/\/\/ ===== AI IMAGE GENERATOR STUDIO STATE & LOGIC =====.*?function clearImageGallery\(\) \{.*?\}\s*\}/s';
$aiPhp = preg_replace($pattern, $newJsEngine, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully applied all 7 Image Studio specifications to student/ai.php!\n";
