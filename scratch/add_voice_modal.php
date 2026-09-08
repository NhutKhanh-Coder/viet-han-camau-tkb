<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Add CSS for Voice Modal
$cssInsert = '
        /* ===== CHỌN GIỌNG (VOICE SELECTOR) MODAL (WHITE THEME) ===== */
        .voice-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 99999999;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
            animation: modalFadeIn 0.2s ease;
        }
        .voice-modal-overlay.show { display: flex; }
        .voice-modal-box {
            width: 860px;
            max-width: 95vw;
            height: 620px;
            max-height: 90vh;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 25px 70px rgba(15, 23, 42, 0.25);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            font-family: \'Outfit\', sans-serif;
        }
        .voice-modal-header {
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #f1f5f9;
        }
        .voice-modal-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .voice-modal-close-btn {
            background: #f1f5f9;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
        }
        .voice-modal-close-btn:hover {
            background: #fee2e2;
            color: #ef4444;
        }
        .voice-modal-search-row {
            padding: 14px 24px 8px;
        }
        .voice-search-input {
            width: 100%;
            padding: 10px 16px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            color: #0f172a;
            outline: none;
            box-sizing: border-box;
            font-family: inherit;
            transition: all 0.2s;
        }
        .voice-search-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
            background: #fff;
        }
        .voice-filter-pills {
            padding: 6px 24px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            border-bottom: 1px solid #f1f5f9;
        }
        .voice-filter-pill {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .voice-filter-pill:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .voice-filter-pill.active {
            background: #10b981;
            color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
        }
        .voice-cards-grid {
            flex: 1;
            padding: 20px 24px;
            overflow-y: auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 16px;
        }
        .voice-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .voice-card:hover {
            transform: translateY(-3px);
            border-color: #10b981;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.15);
        }
        .voice-card.selected {
            border-color: #10b981;
            box-shadow: 0 0 0 2.5px #10b981;
        }
        .voice-card-artwork {
            height: 100px;
            position: relative;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 8px;
        }
        .voice-badge-free {
            background: rgba(16, 185, 129, 0.9);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            backdrop-filter: blur(4px);
        }
        .voice-badges-right {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
        }
        .voice-card-play-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.2s;
        }
        .voice-card:hover .voice-card-play-overlay {
            opacity: 1;
        }
        .voice-play-icon-btn {
            width: 34px;
            height: 34px;
            background: #ffffff;
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.15s;
        }
        .voice-play-icon-btn:hover {
            transform: scale(1.1);
        }
        .voice-card-info {
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .voice-card-name {
            font-size: 13.5px;
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .voice-card-sub {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 500;
        }
';

$aiPhp = str_replace('/* ===== 2-COLUMN MODEL DROPDOWN MODAL (WHITE THEME) ===== */', $cssInsert . "\n        /* ===== 2-COLUMN MODEL DROPDOWN MODAL (WHITE THEME) ===== */", $aiPhp);

// 2. Replace TTS Voice Select in HTML with Interactive Voice Selector Card
$oldTtsSelect = '<div class="ai-tts-setting-row">
                            <label class="ai-tts-label">Giọng đọc (Voice)</label>
                            <select id="ttsVoiceSelect" class="ai-tts-select" onchange="onVoiceChange(this.value)">
                                <option value="vi-VN-female">Tiếng Việt (Nữ tự nhiên)</option>
                                <option value="vi-VN-male">Tiếng Việt (Nam truyền cảm)</option>
                                <option value="en-US-female">English (US Female)</option>
                                <option value="en-US-male">English (US Male)</option>
                            </select>
                        </div>';

$newTtsSelect = '<div class="ai-tts-setting-row">
                            <label class="ai-tts-label">
                                <span>Giọng đọc (Voice)</span>
                                <span style="color:#10b981; font-weight:700; font-size:11px; cursor:pointer;" onclick="openVoiceModal()">Xem tất cả ></span>
                            </label>
                            
                            <!-- Interactive Selected Voice Card Trigger -->
                            <div onclick="openVoiceModal()" style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px; cursor:pointer; transition:all 0.2s;" onmouseenter="this.style.borderColor=\'#10b981\'; this.style.background=\'#fff\';" onmouseleave="this.style.borderColor=\'#e2e8f0\'; this.style.background=\'#f8fafc\';">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div id="activeVoiceThumb" style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, #10b981, #059669); display:flex; align-items:center; justify-content:center; color:#fff; font-size:16px; box-shadow:0 3px 8px rgba(16,185,129,0.25);">
                                        <i class="fa-solid fa-microphone"></i>
                                    </div>
                                    <div>
                                        <div id="activeVoiceName" style="font-size:13.5px; font-weight:800; color:#0f172a;">Thuỳ Tiên (Nữ Bắc)</div>
                                        <div id="activeVoiceSub" style="font-size:11.5px; color:#64748b;">vi · female · Miễn phí</div>
                                    </div>
                                </div>
                                <button type="button" style="background:#10b981; color:#fff; border:none; padding:5px 12px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer;">
                                    Đổi giọng
                                </button>
                            </div>
                        </div>';

$aiPhp = str_replace($oldTtsSelect, $newTtsSelect, $aiPhp);

// 3. Add Voice Selection Modal HTML before </body>
$voiceModalHtml = '
    <!-- CHỌN GIỌNG (VOICE SELECTOR) MODAL (WHITE THEME) -->
    <div class="voice-modal-overlay" id="voiceModalOverlay" onclick="if(event.target===this) closeVoiceModal()">
        <div class="voice-modal-box">
            <div class="voice-modal-header">
                <h3 class="voice-modal-title">
                    <i class="fa-solid fa-volume-high" style="color:#10b981;"></i>
                    <span>Chọn giọng</span>
                </h3>
                <button type="button" class="voice-modal-close-btn" onclick="closeVoiceModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="voice-modal-search-row">
                <input type="text" class="voice-search-input" id="voiceSearchInput" placeholder="🔍 Tìm giọng..." oninput="filterVoices(this.value)">
            </div>

            <div class="voice-filter-pills" id="voiceFilterPills">
                <button type="button" class="voice-filter-pill active" onclick="setVoiceCategory(\'all\', this)">Tất cả</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'trending\', this)">🔥 Thịnh hành</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'vi\', this)">Tiếng Việt</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'en\', this)">Tiếng Anh</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'female\', this)">Nữ</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'male\', this)">Nam</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'character\', this)">Nhân vật</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'meme\', this)">Bài hát Meme</button>
            </div>

            <div class="voice-cards-grid" id="voiceCardsGrid">
                <!-- Dynamically populated voice cards -->
            </div>
        </div>
    </div>
';

$aiPhp = str_replace('</body>', $voiceModalHtml . "\n</body>", $aiPhp);

// 4. Add Voice Dataset and Controller Functions to Javascript
$jsVoiceLogic = "
        // ===== FULL VOICE DATASET (MATCHING SCREENSHOT) =====
        const ALL_VOICES = [
            // Tiếng Việt
            { id: 'vi_thuytien', name: 'Thuỳ Tiên (Nữ Bắc)', lang: 'vi', gender: 'female', category: ['all', 'trending', 'vi', 'female'], sub: 'vi · female', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%)', desc: 'Giọng Nữ miền Bắc tự nhiên, truyền cảm, chuẩn phát thanh.' },
            { id: 'vi_minhquan', name: 'Minh Quân (Nam Bắc)', lang: 'vi', gender: 'male', category: ['all', 'trending', 'vi', 'male'], sub: 'vi · male', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 50%, #1e40af 100%)', desc: 'Giọng Nam miền Bắc ấm áp, lịch thiệp, dễ nghe.' },
            { id: 'vi_mytam', name: 'Mỹ Tâm (Nữ Nam)', lang: 'vi', gender: 'female', category: ['all', 'vi', 'female'], sub: 'vi · female', tags: ['Miễn phí', 'hot'], gradient: 'linear-gradient(135deg, #f43f5e 0%, #e11d48 50%, #be123c 100%)', desc: 'Giọng Nữ miền Nam ngọt ngào, gần gũi, dịu dàng.' },
            { id: 'vi_hoanglong', name: 'Hoàng Long (Nam Nam)', lang: 'vi', gender: 'male', category: ['all', 'vi', 'male'], sub: 'vi · male', tags: ['Miễn phí', 'vip'], gradient: 'linear-gradient(135deg, #8b5cf6 0%, #6d28d9 50%, #4c1d95 100%)', desc: 'Giọng Nam miền Nam hào sảng, phong thái trẻ trung.' },
            { id: 'vi_chihang', name: 'Chị Hằng (Kể chuyện)', lang: 'vi', gender: 'female', category: ['all', 'vi', 'female', 'character'], sub: 'vi · female', tags: ['Miễn phí'], gradient: 'linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%)', desc: 'Giọng đọc diễn cảm chuyên kể chuyện và đọc sách.' },
            { id: 'vi_onggiao', name: 'Thầy Giáo (Giảng bài)', lang: 'vi', gender: 'male', category: ['all', 'vi', 'male', 'character'], sub: 'vi · male', tags: ['Miễn phí'], gradient: 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 50%, #0369a1 100%)', desc: 'Giọng đọc chậm rãi, rõ ràng từng từ, chuẩn bài giảng CNTT.' },

            // Tiếng Anh & Quốc tế (Theo ảnh mẫu)
            { id: 'en_princess', name: 'Princess', lang: 'en', gender: 'female', category: ['all', 'trending', 'en', 'female'], sub: 'en · female', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #14b8a6 0%, #0f766e 100%)', desc: 'Sweet, elegant royal tone.' },
            { id: 'en_jeffrey', name: 'Jeffrey', lang: 'en', gender: 'female', category: ['all', 'trending', 'en', 'female'], sub: 'en · female', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #2563eb 0%, #f59e0b 100%)', desc: 'Deep dynamic narrative voice.' },
            { id: 'en_commentary', name: 'Commentary Male', lang: 'en', gender: 'male', category: ['all', 'trending', 'en', 'male'], sub: 'en · male', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #4f46e5 0%, #e11d48 100%)', desc: 'Engaging, energetic broadcast speaker.' },
            { id: 'en_nigel', name: 'Nigel', lang: 'en', gender: 'male', category: ['all', 'en', 'male'], sub: 'en · male', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #f87171 0%, #fb923c 100%)', desc: 'Warm British studio actor voice.' },
            { id: 'en_child', name: 'Child', lang: 'en', gender: 'male', category: ['all', 'en', 'male', 'character'], sub: 'en · male', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #a855f7 0%, #ec4899 100%)', desc: 'Playful and bright young voice.' },
            { id: 'en_betty', name: 'Betty Boop', lang: 'en', gender: 'female', category: ['all', 'en', 'female', 'character'], sub: 'en · female', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #f59e0b 0%, #1e293b 100%)', desc: 'Retro animated playful tone.' },
            { id: 'en_caroline', name: 'Caroline', lang: 'en', gender: 'female', category: ['all', 'en', 'female'], sub: 'en · female', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #10b981 0%, #1e3a8a 100%)', desc: 'Calm, soothing meditation narrator.' },
            { id: 'en_bibble', name: 'Bibble', lang: 'en', gender: 'female', category: ['all', 'en', 'female', 'character'], sub: 'en · female', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #fbbf24 0%, #f97316 100%)', desc: 'Cute fairytale fairy voice.' },
            { id: 'en_daisy', name: 'Daisy', lang: 'en', gender: 'female', category: ['all', 'en', 'female'], sub: 'en · female', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #ef4444 0%, #06b6d4 100%)', desc: 'Friendly conversational AI agent.' },
            { id: 'en_doll', name: 'Doll Effect', lang: 'en', gender: 'female', category: ['all', 'en', 'meme', 'character'], sub: 'en', tags: ['Miễn phí', 'hot', 'vip'], gradient: 'linear-gradient(135deg, #38bdf8 0%, #f472b6 100%)', desc: 'Whimsical high pitch robot effect.' },

            // Meme & Character voices
            { id: 'meme_rickroll', name: 'Never Give You Up', lang: 'en', gender: 'male', category: ['all', 'meme'], sub: 'meme · music', tags: ['Miễn phí', 'hot'], gradient: 'linear-gradient(135deg, #f43f5e 0%, #3b82f6 100%)', desc: 'Funny melodic meme voice style.' },
            { id: 'meme_anime', name: 'Anime Chan', lang: 'ja', gender: 'female', category: ['all', 'meme', 'character'], sub: 'ja · female', tags: ['Miễn phí', 'vip'], gradient: 'linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%)', desc: 'Kawaii anime voice tone.' }
        ];

        let currentSelectedVoice = ALL_VOICES[0];
        let currentVoiceCategory = 'all';

        function openVoiceModal() {
            const modal = document.getElementById('voiceModalOverlay');
            if (modal) modal.classList.add('show');
            renderVoiceCards();
        }

        function closeVoiceModal() {
            const modal = document.getElementById('voiceModalOverlay');
            if (modal) modal.classList.remove('show');
        }

        function setVoiceCategory(cat, el) {
            currentVoiceCategory = cat;
            document.querySelectorAll('.voice-filter-pill').forEach(p => p.classList.remove('active'));
            if (el) el.classList.add('active');
            renderVoiceCards();
        }

        function filterVoices(keyword) {
            renderVoiceCards(keyword);
        }

        function renderVoiceCards(filterKeyword = '') {
            const container = document.getElementById('voiceCardsGrid');
            if (!container) return;
            container.innerHTML = '';

            const kw = (filterKeyword || '').toLowerCase().trim();

            const filtered = ALL_VOICES.filter(v => {
                const matchCat = currentVoiceCategory === 'all' || (v.category && v.category.includes(currentVoiceCategory));
                const matchKw = !kw || v.name.toLowerCase().includes(kw) || v.sub.toLowerCase().includes(kw);
                return matchCat && matchKw;
            });

            if (filtered.length === 0) {
                container.innerHTML = '<div style=\"grid-column:1/-1; padding:40px; text-align:center; color:#64748b;\">Không tìm thấy giọng đọc phù hợp</div>';
                return;
            }

            filtered.forEach(v => {
                const isSelected = v.id === currentSelectedVoice.id;
                const card = document.createElement('div');
                card.className = 'voice-card' + (isSelected ? ' selected' : '');
                
                card.innerHTML = `
                    <div class=\"voice-card-artwork\" style=\"background: \${v.gradient};\">
                        <span class=\"voice-badge-free\">Miễn phí</span>
                        <div class=\"voice-badges-right\">
                            \${v.tags.includes('hot') ? '🔥' : ''}
                            \${v.tags.includes('vip') ? '👑' : ''}
                        </div>
                        <div class=\"voice-card-play-overlay\" onclick=\"previewVoiceSample('\${v.id}', event)\">
                            <div class=\"voice-play-icon-btn\"><i class=\"fa-solid fa-play\"></i></div>
                        </div>
                    </div>
                    <div class=\"voice-card-info\">
                        <div class=\"voice-card-name\" title=\"\${v.name}\">\${v.name}</div>
                        <div class=\"voice-card-sub\">\${v.sub}</div>
                    </div>
                `;

                card.onclick = () => selectVoice(v);
                container.appendChild(card);
            });
        }

        function selectVoice(v) {
            currentSelectedVoice = v;
            localStorage.setItem('vkc_active_voice', JSON.stringify(v));
            updateActiveVoiceUI();
            closeVoiceModal();
        }

        function updateActiveVoiceUI() {
            const v = currentSelectedVoice;
            const nameEl = document.getElementById('activeVoiceName');
            const subEl = document.getElementById('activeVoiceSub');
            const thumbEl = document.getElementById('activeVoiceThumb');
            if (nameEl) nameEl.textContent = v.name;
            if (subEl) subEl.textContent = `\${v.sub} · Miễn phí`;
            if (thumbEl) thumbEl.style.background = v.gradient;
        }

        function previewVoiceSample(voiceId, e) {
            if (e) e.stopPropagation();
            const v = ALL_VOICES.find(item => item.id === voiceId);
            if (!v) return;
            const sample = v.lang === 'vi' ? `Xin chào! Tôi là giọng đọc \${v.name}, rất vui được hỗ trợ bạn đọc văn bản.` : `Hello! I am \${v.name}, ready to read your text.`;
            speakText(sample);
        }
";

$aiPhp = str_replace('function initVoicesList() {', $jsVoiceLogic . "\n        function initVoicesList() {", $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully added Voice Selector Modal to student/ai.php!\n";
