<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Update CSS for Voice Modal to match image 1 (Dark sleek glassmorphic modal with 5-column square cover cards)
$cssOldPattern = '/\/\* ===== CHỌN GIỌNG \(VOICE SELECTOR\) MODAL.*?\.voice-card-sub \{.*?\}/s';

$cssNew = '/* ===== CHỌN GIỌNG (VOICE SELECTOR) MODAL (EXACT MATCH TO XKiro DARK MODAL) ===== */
        .voice-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            z-index: 99999999;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
            animation: modalFadeIn 0.2s ease;
        }
        .voice-modal-overlay.show { display: flex !important; }
        .voice-modal-box {
            width: 860px;
            max-width: 95vw;
            height: 640px;
            max-height: 92vh;
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 18px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            font-family: \'Outfit\', sans-serif;
            color: #f4f4f5;
        }
        .voice-modal-header {
            padding: 16px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #27272a;
        }
        .voice-modal-title {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .voice-modal-close-btn {
            background: transparent;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #71717a;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.15s;
        }
        .voice-modal-close-btn:hover {
            background: #27272a;
            color: #ffffff;
        }
        .voice-modal-search-row {
            padding: 14px 22px 8px;
        }
        .voice-search-input {
            width: 100%;
            padding: 10px 16px;
            background: #121214;
            border: 1.5px solid #27272a;
            border-radius: 10px;
            font-size: 13.5px;
            color: #ffffff;
            outline: none;
            box-sizing: border-box;
            font-family: inherit;
            transition: all 0.2s;
        }
        .voice-search-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }
        .voice-search-input::placeholder {
            color: #71717a;
        }
        .voice-filter-pills {
            padding: 6px 22px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            border-bottom: 1px solid #27272a;
        }
        .voice-filter-pills::-webkit-scrollbar { height: 4px; }
        .voice-filter-pills::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
        .voice-filter-pill {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
            color: #a1a1aa;
            background: #27272a;
            border: 1px solid transparent;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s;
        }
        .voice-filter-pill:hover {
            background: #3f3f46;
            color: #ffffff;
        }
        .voice-filter-pill.active {
            background: #10b981;
            color: #ffffff;
            font-weight: 700;
        }
        .voice-cards-grid {
            flex: 1;
            padding: 18px 22px;
            overflow-y: auto;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
        }
        @media (max-width: 768px) {
            .voice-cards-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 500px) {
            .voice-cards-grid { grid-template-columns: repeat(2, 1fr); }
        }
        .voice-cards-grid::-webkit-scrollbar { width: 6px; }
        .voice-cards-grid::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
        .voice-card {
            background: #202024;
            border: 1px solid #2e2e33;
            border-radius: 14px;
            overflow: hidden;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            padding: 8px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        .voice-card:hover {
            transform: translateY(-3px);
            border-color: #10b981;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            background: #27272b;
        }
        .voice-card.selected {
            border-color: #10b981;
            box-shadow: 0 0 0 2px #10b981;
        }
        .voice-card-artwork {
            width: 100%;
            height: 110px;
            border-radius: 10px;
            position: relative;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 6px 8px;
            overflow: hidden;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.2);
        }
        .voice-badge-free {
            background: rgba(16, 185, 129, 0.95);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            letter-spacing: 0.2px;
        }
        .voice-badges-right {
            display: flex;
            align-items: center;
            gap: 3px;
            font-size: 11px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }
        .voice-card-play-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.4);
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
            width: 32px;
            height: 32px;
            background: #ffffff;
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
            transition: all 0.15s;
        }
        .voice-play-icon-btn:hover {
            transform: scale(1.15);
            background: #10b981;
            color: #fff;
        }
        .voice-card-info {
            padding: 8px 4px 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 2px;
        }
        .voice-card-name {
            font-size: 13px;
            font-weight: 700;
            color: #f4f4f5;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }
        .voice-card-sub {
            font-size: 11px;
            color: #a1a1aa;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }';

$aiPhp = preg_replace($cssOldPattern, $cssNew, $aiPhp);

// 2. Update Voice dataset with artwork images matching image 1
$jsVoicePattern = '/\/\/ ===== FULL VOICE DATASET.*?function previewVoiceSample/s';

$jsVoiceNew = '// ===== FULL VOICE DATASET (EXACT MATCH TO XKiro) =====
        const ALL_VOICES = [
            // Tiếng Anh & Quốc tế (Theo đúng thứ tự ảnh mẫu)
            { id: \'en_princess\', name: \'Princess\', lang: \'en\', gender: \'female\', category: [\'all\', \'trending\', \'en\', \'female\'], sub: \'en · female\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=300&q=80\', gradient: \'linear-gradient(135deg, #10b981, #064e3b)\' },
            { id: \'en_jeffrey\', name: \'Jeffrey\', lang: \'en\', gender: \'female\', category: [\'all\', \'trending\', \'en\', \'female\'], sub: \'en · female\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?w=300&q=80\', gradient: \'linear-gradient(135deg, #2563eb, #d97706)\' },
            { id: \'en_commentary\', name: \'Commentary Male\', lang: \'en\', gender: \'male\', category: [\'all\', \'trending\', \'en\', \'male\'], sub: \'en · male\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1550684848-fac1c5b4e853?w=300&q=80\', gradient: \'linear-gradient(135deg, #4f46e5, #be123c)\' },
            { id: \'en_nigel\', name: \'Nigel\', lang: \'en\', gender: \'male\', category: [\'all\', \'en\', \'male\'], sub: \'en · male\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1541701494587-cb58502866ab?w=300&q=80\', gradient: \'linear-gradient(135deg, #f87171, #ea580c)\' },
            { id: \'en_child\', name: \'Child\', lang: \'en\', gender: \'male\', category: [\'all\', \'en\', \'male\', \'character\'], sub: \'en · male\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=300&q=80\', gradient: \'linear-gradient(135deg, #a855f7, #db2777)\' },
            
            { id: \'en_betty\', name: \'Betty Boop\', lang: \'en\', gender: \'female\', category: [\'all\', \'en\', \'female\', \'character\'], sub: \'en · female\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=300&q=80\', gradient: \'linear-gradient(135deg, #f59e0b, #0f172a)\' },
            { id: \'en_caroline\', name: \'Caroline\', lang: \'en\', gender: \'female\', category: [\'all\', \'en\', \'female\'], sub: \'en · female\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=300&q=80\', gradient: \'linear-gradient(135deg, #059669, #1e3a8a)\' },
            { id: \'en_bibble\', name: \'Bibble\', lang: \'en\', gender: \'female\', category: [\'all\', \'en\', \'female\', \'character\'], sub: \'en · female\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?w=300&q=80\', gradient: \'linear-gradient(135deg, #fbbf24, #ea580c)\' },
            { id: \'en_daisy\', name: \'Daisy\', lang: \'en\', gender: \'female\', category: [\'all\', \'en\', \'female\'], sub: \'en · female\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1550684848-fac1c5b4e853?w=300&q=80\', gradient: \'linear-gradient(135deg, #ef4444, #0891b2)\' },
            { id: \'en_doll\', name: \'Doll Effect\', lang: \'en\', gender: \'female\', category: [\'all\', \'en\', \'meme\', \'character\'], sub: \'en\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1541701494587-cb58502866ab?w=300&q=80\', gradient: \'linear-gradient(135deg, #38bdf8, #ec4899)\' },

            // Tiếng Việt
            { id: \'vi_thuytien\', name: \'Thuỳ Tiên (Nữ Bắc)\', lang: \'vi\', gender: \'female\', category: [\'all\', \'trending\', \'vi\', \'female\'], sub: \'vi · female\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=300&q=80\', gradient: \'linear-gradient(135deg, #10b981, #047857)\' },
            { id: \'vi_minhquan\', name: \'Minh Quân (Nam Bắc)\', lang: \'vi\', gender: \'male\', category: [\'all\', \'trending\', \'vi\', \'male\'], sub: \'vi · male\', tags: [\'Miễn phí\', \'hot\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?w=300&q=80\', gradient: \'linear-gradient(135deg, #3b82f6, #1e40af)\' },
            { id: \'vi_mytam\', name: \'Mỹ Tâm (Nữ Nam)\', lang: \'vi\', gender: \'female\', category: [\'all\', \'vi\', \'female\'], sub: \'vi · female\', tags: [\'Miễn phí\', \'hot\'], bgImg: \'https://images.unsplash.com/photo-1550684848-fac1c5b4e853?w=300&q=80\', gradient: \'linear-gradient(135deg, #f43f5e, #be123c)\' },
            { id: \'vi_hoanglong\', name: \'Hoàng Long (Nam Nam)\', lang: \'vi\', gender: \'male\', category: [\'all\', \'vi\', \'male\'], sub: \'vi · male\', tags: [\'Miễn phí\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1541701494587-cb58502866ab?w=300&q=80\', gradient: \'linear-gradient(135deg, #8b5cf6, #4c1d95)\' },
            { id: \'vi_chihang\', name: \'Chị Hằng (Kể chuyện)\', lang: \'vi\', gender: \'female\', category: [\'all\', \'vi\', \'female\', \'character\'], sub: \'vi · female\', tags: [\'Miễn phí\'], bgImg: \'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=300&q=80\', gradient: \'linear-gradient(135deg, #f59e0b, #b45309)\' },
            { id: \'vi_onggiao\', name: \'Thầy Giáo (Giảng bài)\', lang: \'vi\', gender: \'male\', category: [\'all\', \'vi\', \'male\', \'character\'], sub: \'vi · male\', tags: [\'Miễn phí\'], bgImg: \'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=300&q=80\', gradient: \'linear-gradient(135deg, #0ea5e9, #0369a1)\' },

            // Meme & Character voices
            { id: \'meme_rickroll\', name: \'Never Give You Up\', lang: \'en\', gender: \'male\', category: [\'all\', \'meme\'], sub: \'meme · music\', tags: [\'Miễn phí\', \'hot\'], bgImg: \'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=300&q=80\', gradient: \'linear-gradient(135deg, #f43f5e, #3b82f6)\' },
            { id: \'meme_anime\', name: \'Anime Chan\', lang: \'ja\', gender: \'female\', category: [\'all\', \'meme\', \'character\'], sub: \'ja · female\', tags: [\'Miễn phí\', \'vip\'], bgImg: \'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=300&q=80\', gradient: \'linear-gradient(135deg, #ec4899, #8b5cf6)\' }
        ];

        let currentSelectedVoice = ALL_VOICES[0];
        let currentVoiceCategory = \'all\';

        window.openVoiceModal = function() {
            const modal = document.getElementById(\'voiceModalOverlay\');
            if (modal) {
                modal.style.display = \'flex\';
                modal.classList.add(\'show\');
            }
            renderVoiceCards();
            setTimeout(() => {
                const s = document.getElementById(\'voiceSearchInput\');
                if (s) { s.value = \'\'; s.focus(); }
            }, 50);
        };

        window.closeVoiceModal = function() {
            const modal = document.getElementById(\'voiceModalOverlay\');
            if (modal) {
                modal.style.display = \'none\';
                modal.classList.remove(\'show\');
            }
        };

        function setVoiceCategory(cat, el) {
            currentVoiceCategory = cat;
            document.querySelectorAll(\'.voice-filter-pill\').forEach(p => p.classList.remove(\'active\'));
            if (el) el.classList.add(\'active\');
            renderVoiceCards();
        }

        function filterVoices(keyword) {
            renderVoiceCards(keyword);
        }

        function renderVoiceCards(filterKeyword = \'\') {
            const container = document.getElementById(\'voiceCardsGrid\');
            if (!container) return;
            container.innerHTML = \'\';

            const kw = (filterKeyword || \'\').toLowerCase().trim();

            const filtered = ALL_VOICES.filter(v => {
                const matchCat = currentVoiceCategory === \'all\' || (v.category && v.category.includes(currentVoiceCategory));
                const matchKw = !kw || v.name.toLowerCase().includes(kw) || v.sub.toLowerCase().includes(kw);
                return matchCat && matchKw;
            });

            if (filtered.length === 0) {
                container.innerHTML = \'<div style="grid-column:1/-1; padding:40px; text-align:center; color:#71717a;">Không tìm thấy giọng đọc phù hợp</div>\';
                return;
            }

            filtered.forEach(v => {
                const isSelected = v.id === currentSelectedVoice.id;
                const card = document.createElement(\'div\');
                card.className = \'voice-card\' + (isSelected ? \' selected\' : \'\');
                
                const bgStyle = v.bgImg ? `background-image: url(\'${v.bgImg}\'); background-size: cover;` : `background: ${v.gradient};`;

                card.innerHTML = `
                    <div class="voice-card-artwork" style="${bgStyle}">
                        <span class="voice-badge-free">Miễn phí</span>
                        <div class="voice-badges-right">
                            ${v.tags.includes(\'hot\') ? \'🔥\' : \'\'}
                            ${v.tags.includes(\'vip\') ? \'👑\' : \'\'}
                        </div>
                        <div class="voice-card-play-overlay" onclick="previewVoiceSample(\'${v.id}\', event)">
                            <div class="voice-play-icon-btn"><i class="fa-solid fa-play"></i></div>
                        </div>
                    </div>
                    <div class="voice-card-info">
                        <div class="voice-card-name" title="${v.name}">${v.name}</div>
                        <div class="voice-card-sub">${v.sub}</div>
                    </div>
                `;

                card.onclick = () => selectVoice(v);
                container.appendChild(card);
            });
        }

        function selectVoice(v) {
            currentSelectedVoice = v;
            localStorage.setItem(\'vkc_active_voice\', JSON.stringify(v));
            updateActiveVoiceUI();
            closeVoiceModal();
        }

        function updateActiveVoiceUI() {
            const v = currentSelectedVoice || ALL_VOICES[0];
            const nameEl = document.getElementById(\'activeVoiceName\');
            const subEl = document.getElementById(\'activeVoiceSub\');
            const thumbEl = document.getElementById(\'activeVoiceThumb\');
            if (nameEl) nameEl.textContent = v.name;
            if (subEl) subEl.textContent = `${v.sub} · Miễn phí`;
            if (thumbEl) {
                if (v.bgImg) {
                    thumbEl.style.backgroundImage = `url(\'${v.bgImg}\')`;
                    thumbEl.style.backgroundSize = \'cover\';
                } else {
                    thumbEl.style.background = v.gradient;
                }
            }
        }

        function previewVoiceSample';

$aiPhp = preg_replace($jsVoicePattern, $jsVoiceNew, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully updated voice modal to exact match!\n";
