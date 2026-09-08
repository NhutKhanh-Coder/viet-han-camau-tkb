<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Add CSS for AI Image Studio
$imgCss = '
        /* ===== VIEW 3: AI IMAGE GENERATOR STUDIO ===== */
        .ai-image-view {
            display: none;
            flex: 1;
            overflow: hidden;
            padding: 18px 24px 24px;
            gap: 20px;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            box-sizing: border-box;
            height: calc(100vh - 120px);
        }
        @media (max-width: 900px) {
            .ai-image-view {
                flex-direction: column;
                height: auto;
                overflow-y: auto;
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
            gap: 16px;
            overflow-y: auto;
        }
        .ai-img-textarea {
            width: 100%;
            height: 110px;
            padding: 14px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 13.5px;
            font-family: inherit;
            color: #0f172a;
            outline: none;
            resize: none;
            box-sizing: border-box;
            transition: all 0.2s;
            line-height: 1.5;
        }
        .ai-img-textarea:focus {
            background: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }
        .ai-img-style-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .ai-img-style-card {
            padding: 10px 6px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }
        .ai-img-style-card:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }
        .ai-img-style-card.active {
            background: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.2);
        }
        .ai-img-ratio-row {
            display: flex;
            gap: 8px;
        }
        .ai-img-ratio-btn {
            flex: 1;
            padding: 9px 6px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            transition: all 0.15s;
        }
        .ai-img-ratio-btn:hover {
            background: #ffffff;
            border-color: #cbd5e1;
        }
        .ai-img-ratio-btn.active {
            background: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
        }
        .ai-img-generate-btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 14.5px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
            transition: all 0.2s;
        }
        .ai-img-generate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.45);
        }
        .ai-img-preview-box {
            flex: 1;
            min-height: 420px;
            background: #0f172a;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        .ai-img-preview-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 14px;
            transition: opacity 0.3s ease;
        }
        .ai-img-overlay-toolbar {
            position: absolute;
            bottom: 14px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(10px);
            padding: 8px 14px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 25px rgba(0,0,0,0.5);
            z-index: 10;
        }
        .ai-img-action-btn {
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s;
        }
        .ai-img-action-btn:hover {
            background: #10b981;
            border-color: #10b981;
        }
        .ai-img-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 10px;
            padding: 12px;
            background: #ffffff;
            border: 1.5px solid var(--ai-border);
            border-radius: 16px;
            max-height: 160px;
            overflow-y: auto;
        }
        .ai-img-thumb-item {
            aspect-ratio: 1/1;
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            transition: all 0.15s;
        }
        .ai-img-thumb-item:hover {
            transform: scale(1.05);
            border-color: #10b981;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .ai-img-thumb-item.active {
            border-color: #10b981;
            box-shadow: 0 0 0 2px #10b981;
        }
        .ai-img-thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
';

$aiPhp = str_replace('/* ===== VIEW 2: TEXT-TO-SPEECH (ĐỌC VĂN BẢN) STUDIO ===== */', $imgCss . "\n        /* ===== VIEW 2: TEXT-TO-SPEECH (ĐỌC VĂN BẢN) STUDIO ===== */", $aiPhp);

// 2. Add HTML for AI Image Studio right after aiTtsView
$imgHtml = '
            <!-- ===== VIEW 3: AI IMAGE GENERATOR STUDIO ===== -->
            <div class="ai-image-view" id="aiImageView">
                <!-- Left: Controls & Prompts -->
                <div class="ai-img-left-panel">
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <h3 style="margin:0; font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-wand-magic-sparkles" style="color:#10b981;"></i>
                            <span>Mô tả bức ảnh (Prompt)</span>
                        </h3>
                        <button type="button" onclick="insertRandomPrompt()" style="background:#f1f5f9; border:1px solid #e2e8f0; padding:4px 8px; border-radius:6px; font-size:11.5px; font-weight:700; color:#475569; cursor:pointer; display:flex; align-items:center; gap:4px;">
                            <i class="fa-solid fa-dice" style="color:#10b981;"></i> <span>Gợi ý mẫu</span>
                        </button>
                    </div>

                    <textarea id="imgPromptInput" class="ai-img-textarea" placeholder="Mô tả chi tiết bức ảnh bạn muốn vẽ (hỗ trợ cả Tiếng Việt và Tiếng Anh)... Ví dụ: Một chú mèo phi hành gia khám phá dải ngân hà neon rực rỡ, phong cách cyberpunk 8k render..."></textarea>

                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:800; color:#475569; margin-bottom:8px;">
                            <i class="fa-solid fa-palette" style="color:#0ea5e9; margin-right:4px;"></i> Phong cách nghệ thuật
                        </label>
                        <div class="ai-img-style-grid">
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
                        </div>
                    </div>

                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:800; color:#475569; margin-bottom:8px;">
                            <i class="fa-solid fa-crop-simple" style="color:#8b5cf6; margin-right:4px;"></i> Tỉ lệ khung hình
                        </label>
                        <div class="ai-img-ratio-row">
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
                        </div>
                    </div>

                    <button type="button" class="ai-img-generate-btn" id="btnGenImage" onclick="generateAiImage()">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <span>Tạo ảnh AI ngay</span>
                    </button>
                </div>

                <!-- Right: Preview Screen & Gallery -->
                <div class="ai-img-right-panel">
                    <div class="ai-img-preview-box" id="imgPreviewBox">
                        <div id="imgPlaceholder" style="text-align:center; color:#94a3b8; padding:30px;">
                            <div style="width:70px; height:70px; border-radius:20px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; font-size:28px; color:#10b981;">
                                <i class="fa-solid fa-image"></i>
                            </div>
                            <h3 style="margin:0 0 6px 0; font-size:16px; font-weight:700; color:#f8fafc;">Chưa có tác phẩm nào được tạo</h3>
                            <p style="margin:0; font-size:13px; color:#64748b;">Nhập mô tả ở cột bên trái và bấm <strong>Tạo ảnh AI ngay</strong> để bắt đầu!</p>
                        </div>

                        <div id="imgLoadingState" style="display:none; text-align:center; color:#f8fafc; z-index:5;">
                            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:36px; color:#10b981; margin-bottom:14px; display:inline-block;"></i>
                            <div style="font-size:15px; font-weight:700;">AI đang vẽ bức tranh của bạn...</div>
                            <div style="font-size:12px; color:#94a3b8; margin-top:4px;">Thời gian xử lý dự kiến ~3 đến 5 giây</div>
                        </div>

                        <img id="imgResultDisplay" class="ai-img-preview-img" style="display:none;" alt="AI Generated Artwork">

                        <div class="ai-img-overlay-toolbar" id="imgOverlayToolbar" style="display:none;">
                            <button type="button" class="ai-img-action-btn" onclick="downloadActiveImage()">
                                <i class="fa-solid fa-download"></i> <span>Tải ảnh HD</span>
                            </button>
                            <button type="button" class="ai-img-action-btn" onclick="openImageFullscreen()">
                                <i class="fa-solid fa-expand"></i> <span>Phóng to</span>
                            </button>
                            <button type="button" class="ai-img-action-btn" onclick="generateAiImage()">
                                <i class="fa-solid fa-arrows-rotate"></i> <span>Vẽ lại</span>
                            </button>
                        </div>
                    </div>

                    <!-- History Gallery -->
                    <div>
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                            <span style="font-size:13px; font-weight:800; color:#475569;" id="galleryCountText">Thư viện ảnh đã tạo (0)</span>
                            <button type="button" onclick="clearImageGallery()" style="background:none; border:none; color:#ef4444; font-size:11.5px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:4px;">
                                <i class="fa-solid fa-trash"></i> <span>Xóa thư viện</span>
                            </button>
                        </div>
                        <div class="ai-img-gallery-grid" id="imgGalleryGrid">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>
';

$oldViewEnd = '            <!-- ===== VIEW 2: TEXT-TO-SPEECH (ĐỌC VĂN BẢN) STUDIO ===== -->';
$aiPhp = str_replace('<!-- ===== VIEW 2: TEXT-TO-SPEECH (ĐỌC VĂN BẢN) STUDIO ===== -->', $imgHtml . "\n            <!-- ===== VIEW 2: TEXT-TO-SPEECH (ĐỌC VĂN BẢN) STUDIO ===== -->", $aiPhp);

// 3. Update switchMode and add AI Image Studio JS functions
$oldSwitchMode = '        function switchMode(mode, btn) {
            document.querySelectorAll(\'.ai-mode-tab\').forEach(b => b.classList.remove(\'active\'));
            if (btn) btn.classList.add(\'active\');

            const chatView = document.getElementById(\'aiChatView\');
            const ttsView = document.getElementById(\'aiTtsView\');

            if (mode === \'chat\') {
                if (chatView) chatView.style.display = \'flex\';
                if (ttsView) ttsView.style.display = \'none\';
            } else if (mode === \'tts\') {
                if (chatView) chatView.style.display = \'none\';
                if (ttsView) ttsView.style.display = \'flex\';
            } else if (mode === \'image\') {
                alert(\'Tính năng Tạo ảnh AI đang được cập nhật!\');
                switchMode(\'chat\', document.getElementById(\'tabChat\'));
            }
        }';

$newImageStudioJs = '        // ===== AI IMAGE GENERATOR STUDIO STATE & LOGIC =====
        let selectedImgStyle = \'\';
        let selectedImgWidth = 1024;
        let selectedImgHeight = 1024;
        let activeGeneratedImageUrl = \'\';
        let imageGallery = JSON.parse(localStorage.getItem(\'vkc_ai_img_gallery\') || \'[]\');

        const RANDOM_PROMPTS = [
            "Một chú mèo phi hành gia lơ lửng ngoài vũ trụ ngắm nhìn dải ngân hà lấp lánh neon, phong cách cyberpunk 8k render",
            "Ngôi trường đại học tương lai với tháp công nghệ cao và xe bay trên bầu trời hoàng hôn, digital art 8k",
            "Một khu vườn thần tiên đầy hoa phát sáng ban đêm với dòng suối pha lê và đom đóm rực rỡ, unreal engine 5 render",
            "Chân dung một cô gái Việt Nam trong tà áo dài truyền thống giữa phố cổ Hội An đêm rằm hoa đăng, 8k photography",
            "Một chú rồng nhỏ đáng yêu ngồi trên đỉnh núi tuyết đọc cuốn sách ma pháp cổ xưa, 3d pixar style",
            "Thành phố cổ tích trên mây với những lâu đài bay lơ lửng và cầu vồng ánh sáng, anime studio ghibli aesthetic"
        ];

        function switchMode(mode, btn) {
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
        }

        function insertRandomPrompt() {
            const promptInput = document.getElementById(\'imgPromptInput\');
            if (!promptInput) return;
            const rand = RANDOM_PROMPTS[Math.floor(Math.random() * RANDOM_PROMPTS.length)];
            promptInput.value = rand;
            promptInput.focus();
        }

        function selectImgStyle(stylePrompt, card) {
            selectedImgStyle = stylePrompt;
            document.querySelectorAll(\'.ai-img-style-card\').forEach(c => c.classList.remove(\'active\'));
            if (card) card.classList.add(\'active\');
        }

        function selectImgRatio(w, h, btn) {
            selectedImgWidth = w;
            selectedImgHeight = h;
            document.querySelectorAll(\'.ai-img-ratio-btn\').forEach(b => b.classList.remove(\'active\'));
            if (btn) btn.classList.add(\'active\');
        }

        async function generateAiImage() {
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
        }

        function downloadActiveImage() {
            if (!activeGeneratedImageUrl) return;
            const a = document.createElement(\'a\');
            a.href = activeGeneratedImageUrl;
            a.target = \'_blank\';
            a.download = `ai_artwork_${Date.now()}.jpg`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        function openImageFullscreen() {
            if (!activeGeneratedImageUrl) return;
            window.open(activeGeneratedImageUrl, \'_blank\');
        }

        function renderImageGallery() {
            const grid = document.getElementById(\'imgGalleryGrid\');
            const countText = document.getElementById(\'galleryCountText\');
            if (!grid) return;
            grid.innerHTML = \'\';

            if (countText) countText.textContent = `Thư viện ảnh đã tạo (${imageGallery.length})`;

            if (imageGallery.length === 0) {
                grid.innerHTML = \'<div style="grid-column:1/-1; text-align:center; padding:15px; color:#94a3b8; font-size:12.5px;">Chưa có ảnh nào trong thư viện.</div>\';
                return;
            }

            imageGallery.forEach((item, index) => {
                const thumb = document.createElement(\'div\');
                thumb.className = \'ai-img-thumb-item\' + (item.url === activeGeneratedImageUrl ? \' active\' : \'\');
                thumb.title = item.prompt;
                thumb.onclick = () => {
                    activeGeneratedImageUrl = item.url;
                    const placeholder = document.getElementById(\'imgPlaceholder\');
                    const displayImg = document.getElementById(\'imgResultDisplay\');
                    const toolbar = document.getElementById(\'imgOverlayToolbar\');
                    if (placeholder) placeholder.style.display = \'none\';
                    if (displayImg) {
                        displayImg.src = item.url;
                        displayImg.style.display = \'block\';
                    }
                    if (toolbar) toolbar.style.display = \'flex\';
                    const promptInput = document.getElementById(\'imgPromptInput\');
                    if (promptInput) promptInput.value = item.prompt;
                    renderImageGallery();
                };

                thumb.innerHTML = `<img src="${item.url}" class="ai-img-thumb-img" alt="Thumbnail">`;
                grid.appendChild(thumb);
            });
        }

        function clearImageGallery() {
            if (imageGallery.length === 0) return;
            if (confirm(\'Bạn có chắc muốn xóa toàn bộ thư viện ảnh đã tạo không?\')) {
                imageGallery = [];
                localStorage.setItem(\'vkc_ai_img_gallery\', JSON.stringify(imageGallery));
                renderImageGallery();
            }
        }';

$aiPhp = str_replace($oldSwitchMode, $newImageStudioJs, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully integrated full AI Image Generator Studio with Pollinations Flux engine!\n";
