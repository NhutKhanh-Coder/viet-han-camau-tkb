<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Update CSS for AI Image Studio to be pixel-perfect and eliminate double scrollbars
$oldImgCss = '/* ===== VIEW 3: AI IMAGE GENERATOR STUDIO ===== */
        .ai-image-view {
            display: none;
            flex: 1;
            overflow: hidden;
            padding: 20px 24px;
            gap: 20px;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            box-sizing: border-box;
            height: calc(100vh - 120px);
        }
        @media (max-width: 960px) {
            .ai-image-view {
                flex-direction: column;
                height: auto;
                overflow-y: auto;
                padding: 14px;
            }
        }
        .ai-img-left-panel {
            width: 440px;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background: #ffffff;
            border: 1.5px solid var(--ai-border);
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            overflow-y: auto;
            flex-shrink: 0;
        }
        .ai-img-right-panel {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
        }';

$newImgCss = '/* ===== VIEW 3: AI IMAGE GENERATOR STUDIO ===== */
        .ai-image-view {
            display: none;
            flex: 1;
            overflow: hidden;
            padding: 14px 24px 20px;
            gap: 20px;
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            box-sizing: border-box;
            height: calc(100vh - 80px);
        }
        @media (max-width: 960px) {
            .ai-image-view {
                flex-direction: column;
                height: auto;
                overflow-y: auto;
                padding: 14px;
            }
        }
        .ai-img-left-panel {
            width: 430px;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #ffffff;
            border: 1.5px solid var(--ai-border);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            overflow-y: auto;
            flex-shrink: 0;
        }
        .ai-img-right-panel {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
            overflow: hidden;
        }';

$aiPhp = str_replace($oldImgCss, $newImgCss, $aiPhp);

// 2. Update switchMode to show/hide top bar chat controls cleanly
$oldSwitchMode = '        function switchMode(mode, btn) {
            document.querySelectorAll(\'.ai-mode-tab\').forEach(b => b.classList.remove(\'active\'));
            if (btn) btn.classList.add(\'active\');

            const chatView = document.getElementById(\'aiChatView\');
            const ttsView = document.getElementById(\'aiTtsView\');
            const imgView = document.getElementById(\'aiImageView\');

            if (chatView) chatView.style.display = (mode === \'chat\' ? \'flex\' : \'none\');
            if (ttsView) ttsView.style.display = (mode === \'tts\' ? \'flex\' : \'none\');
            if (imgView) {
                imgView.style.display = (mode === \'image\' ? \'flex\' : \'none\');
                if (mode === \'image\') renderImageGallery();
            }
        }';

$newSwitchMode = '        function switchMode(mode, btn) {
            document.querySelectorAll(\'.ai-mode-tab\').forEach(b => b.classList.remove(\'active\'));
            if (btn) btn.classList.add(\'active\');

            const chatView = document.getElementById(\'aiChatView\');
            const ttsView = document.getElementById(\'aiTtsView\');
            const imgView = document.getElementById(\'aiImageView\');
            const topModel = document.getElementById(\'topModelWrapper\');
            const topFolder = document.getElementById(\'topFolderBtn\');

            if (chatView) chatView.style.display = (mode === \'chat\' ? \'flex\' : \'none\');
            if (ttsView) ttsView.style.display = (mode === \'tts\' ? \'flex\' : \'none\');
            if (imgView) {
                imgView.style.display = (mode === \'image\' ? \'flex\' : \'none\');
                if (mode === \'image\') renderImageGallery();
            }

            // Clean top bar
            if (topModel) topModel.style.display = (mode === \'chat\' ? \'flex\' : \'none\');
            if (topFolder) topFolder.style.display = (mode === \'chat\' ? \'inline-flex\' : \'none\');
        }';

$aiPhp = str_replace($oldSwitchMode, $newSwitchMode, $aiPhp);

// 3. Update generateAiImage to automatically translate & enhance prompts via AI for stunning, accurate results!
$oldGenImg = '        async function generateAiImage() {
            const promptInput = document.getElementById(\'imgPromptInput\');
            const rawPrompt = (promptInput ? promptInput.value : \'\').trim();
            if (!rawPrompt) {
                alert(\'Vui lòng nhập mô tả bức ảnh bạn muốn tạo!\');
                if (promptInput) promptInput.focus();
                return;
            }

            const fullPrompt = selectedImgStyle ? `${rawPrompt}, ${selectedImgStyle}` : rawPrompt;
            const seed = Math.floor(Math.random() * 1000000);
            const encodedPrompt = encodeURIComponent(fullPrompt);
            const imageUrl = `https://image.pollinations.ai/prompt/${encodedPrompt}?width=${selectedImgWidth}&height=${selectedImgHeight}&seed=${seed}&nologo=true&model=flux`;

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
                btnGen.innerHTML = \'<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Đang vẽ tranh...</span>\';
            }

            const img = new Image();
            img.onload = function() {
                activeGeneratedImageUrl = imageUrl;
                if (loadingState) loadingState.style.display = \'none\';
                if (displayImg) {
                    displayImg.src = imageUrl;
                    displayImg.style.display = \'block\';
                }
                if (toolbar) toolbar.style.display = \'flex\';
                if (btnGen) {
                    btnGen.disabled = false;
                    btnGen.innerHTML = \'<i class="fa-solid fa-wand-magic-sparkles"></i> <span>Tạo ảnh AI ngay</span>\';
                }

                // Save to history gallery
                imageGallery.unshift({
                    url: imageUrl,
                    prompt: rawPrompt,
                    time: new Date().toLocaleTimeString()
                });
                if (imageGallery.length > 30) imageGallery.pop();
                localStorage.setItem(\'vkc_ai_img_gallery\', JSON.stringify(imageGallery));
                renderImageGallery();
            };

            img.onerror = function() {
                if (loadingState) loadingState.style.display = \'none\';
                if (placeholder) placeholder.style.display = \'block\';
                if (btnGen) {
                    btnGen.disabled = false;
                    btnGen.innerHTML = \'<i class="fa-solid fa-wand-magic-sparkles"></i> <span>Tạo ảnh AI ngay</span>\';
                }
                alert(\'Lỗi kết nối máy chủ tạo ảnh AI! Vui lòng thử lại với prompt khác.\');
            };

            img.src = imageUrl;
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
            }

            const fullPrompt = selectedImgStyle ? `${englishPrompt}, ${selectedImgStyle}` : englishPrompt;
            const seed = Math.floor(Math.random() * 1000000);
            const encodedPrompt = encodeURIComponent(fullPrompt);
            const imageUrl = `https://image.pollinations.ai/prompt/${encodedPrompt}?width=${selectedImgWidth}&height=${selectedImgHeight}&seed=${seed}&nologo=true&model=flux`;

            const img = new Image();
            img.onload = function() {
                activeGeneratedImageUrl = imageUrl;
                if (loadingState) loadingState.style.display = \'none\';
                if (displayImg) {
                    displayImg.src = imageUrl;
                    displayImg.style.display = \'block\';
                }
                if (toolbar) toolbar.style.display = \'flex\';
                if (btnGen) {
                    btnGen.disabled = false;
                    btnGen.innerHTML = \'<i class="fa-solid fa-wand-magic-sparkles"></i> <span>Tạo ảnh AI ngay</span>\';
                }

                // Save to history gallery
                imageGallery.unshift({
                    url: imageUrl,
                    prompt: rawPrompt,
                    enhancedPrompt: englishPrompt,
                    time: new Date().toLocaleTimeString()
                });
                if (imageGallery.length > 30) imageGallery.pop();
                localStorage.setItem(\'vkc_ai_img_gallery\', JSON.stringify(imageGallery));
                renderImageGallery();
            };

            img.onerror = function() {
                if (loadingState) loadingState.style.display = \'none\';
                if (placeholder) placeholder.style.display = \'block\';
                if (btnGen) {
                    btnGen.disabled = false;
                    btnGen.innerHTML = \'<i class="fa-solid fa-wand-magic-sparkles"></i> <span>Tạo ảnh AI ngay</span>\';
                }
                alert(\'Lỗi máy chủ vẽ tranh! Vui lòng thử lại với mô tả khác.\');
            };

            img.src = imageUrl;
        }

        async function downloadActiveImage() {
            if (!activeGeneratedImageUrl) return;
            try {
                const response = await fetch(activeGeneratedImageUrl);
                const blob = await response.blob();
                const blobUrl = URL.createObjectURL(blob);
                const a = document.createElement(\'a\');
                a.href = blobUrl;
                a.download = `viet_han_ai_${Date.now()}.jpg`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(blobUrl);
            } catch(e) {
                window.open(activeGeneratedImageUrl, \'_blank\');
            }
        }';

$aiPhp = str_replace($oldGenImg, $newGenImg, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully upgraded AI Image Studio with DeepSeek prompt expansion and perfect layout!\n";
