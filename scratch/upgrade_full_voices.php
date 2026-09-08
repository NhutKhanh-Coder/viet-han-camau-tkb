<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$voicesDataset = <<< 'EOD'
        // ===== RICH & DIVERSE 26+ VOICES DATASET =====
        const ALL_VOICES = [
            // --- VIETNAMESE VOICES ---
            { id: 'vi_thuytien', name: 'Thuỳ Tiên (Nữ Bắc)', lang: 'vi', gender: 'female', category: ['all', 'trending', 'vi', 'female'], sub: 'Nữ Miền Bắc (Chuẩn VTV)', pitch: 1.1, rate: 1.0, isTrending: true, tags: ['hot', 'vip'], gradient: 'linear-gradient(135deg, #10b981, #047857)', icon: 'fa-microphone' },
            { id: 'vi_maiphuong', name: 'Mai Phương (Nữ Nam)', lang: 'vi', gender: 'female', category: ['all', 'trending', 'vi', 'female'], sub: 'Nữ Miền Nam (Ngọt ngào)', pitch: 1.25, rate: 1.05, isTrending: true, tags: ['hot'], gradient: 'linear-gradient(135deg, #f43f5e, #be123c)', icon: 'fa-microphone' },
            { id: 'vi_tuanhung', name: 'Tuấn Hùng (Nam Bắc)', lang: 'vi', gender: 'male', category: ['all', 'trending', 'vi', 'male'], sub: 'Nam Miền Bắc (Trầm ấm)', pitch: 0.85, rate: 0.95, isTrending: true, tags: ['hot', 'vip'], gradient: 'linear-gradient(135deg, #3b82f6, #1e40af)', icon: 'fa-user-tie' },
            { id: 'vi_quangdung', name: 'Quang Dũng (Nam Nam)', lang: 'vi', gender: 'male', category: ['all', 'vi', 'male'], sub: 'Nam Miền Nam (Truyền cảm)', pitch: 0.8, rate: 0.92, isTrending: false, tags: ['vip'], gradient: 'linear-gradient(135deg, #8b5cf6, #4c1d95)', icon: 'fa-user' },
            { id: 'vi_huonggiang', name: 'Hương Giang (Nữ Trung)', lang: 'vi', gender: 'female', category: ['all', 'vi', 'female'], sub: 'Nữ Miền Trung (Dịu dàng)', pitch: 1.15, rate: 0.98, isTrending: false, tags: [], gradient: 'linear-gradient(135deg, #ec4899, #9d174d)', icon: 'fa-microphone' },
            { id: 'vi_chihang', name: 'Chị Hằng (Kể chuyện)', lang: 'vi', gender: 'female', category: ['all', 'vi', 'female', 'character'], sub: 'Cổ tích & Truyện thiếu nhi', pitch: 1.2, rate: 0.9, isTrending: true, tags: ['hot'], gradient: 'linear-gradient(135deg, #f59e0b, #b45309)', icon: 'fa-wand-magic-sparkles' },
            { id: 'vi_thaygiao', name: 'Thầy Giáo (Giảng bài)', lang: 'vi', gender: 'male', category: ['all', 'vi', 'male', 'character'], sub: 'Giảng dạy & Hàn lâm', pitch: 0.9, rate: 0.95, isTrending: false, tags: [], gradient: 'linear-gradient(135deg, #0ea5e9, #0369a1)', icon: 'fa-graduation-cap' },
            { id: 'vi_cotam', name: 'Cô Tấm (Thanh thoát)', lang: 'vi', gender: 'female', category: ['all', 'vi', 'female', 'character'], sub: 'Nhẹ nhàng thanh tao', pitch: 1.3, rate: 1.0, isTrending: false, tags: [], gradient: 'linear-gradient(135deg, #06b6d4, #0891b2)', icon: 'fa-feather' },
            { id: 'vi_mcthoisu', name: 'MC Thời Sự (Quyết đoán)', lang: 'vi', gender: 'female', category: ['all', 'vi', 'female'], sub: 'Bản tin & Sự kiện', pitch: 1.05, rate: 1.2, isTrending: false, tags: ['vip'], gradient: 'linear-gradient(135deg, #dc2626, #991b1b)', icon: 'fa-bullhorn' },
            { id: 'vi_bacbaphi', name: 'Bác Ba Phi (Hài hước)', lang: 'vi', gender: 'male', category: ['all', 'vi', 'male', 'character'], sub: 'Chuyện vui dân tộc', pitch: 0.75, rate: 1.05, isTrending: true, tags: ['hot'], gradient: 'linear-gradient(135deg, #d97706, #78350f)', icon: 'fa-face-laugh-beam' },

            // --- ENGLISH VOICES ---
            { id: 'en_sarah', name: 'Sarah (US Warm)', lang: 'en', gender: 'female', category: ['all', 'trending', 'en', 'female'], sub: 'American English (Warm)', pitch: 1.1, rate: 1.0, isTrending: true, tags: ['hot', 'vip'], gradient: 'linear-gradient(135deg, #f87171, #ea580c)', icon: 'fa-microphone' },
            { id: 'en_alex', name: 'Alex (US Pro)', lang: 'en', gender: 'male', category: ['all', 'en', 'male'], sub: 'American English (Professional)', pitch: 0.9, rate: 1.0, isTrending: false, tags: ['vip'], gradient: 'linear-gradient(135deg, #38bdf8, #0284c7)', icon: 'fa-user-tie' },
            { id: 'en_emma', name: 'Emma (UK Refined)', lang: 'en', gender: 'female', category: ['all', 'trending', 'en', 'female'], sub: 'British English (Refined)', pitch: 1.15, rate: 0.95, isTrending: true, tags: ['hot'], gradient: 'linear-gradient(135deg, #a855f7, #6b21a8)', icon: 'fa-microphone' },
            { id: 'en_david', name: 'David (UK Deep)', lang: 'en', gender: 'male', category: ['all', 'en', 'male'], sub: 'British English (Deep Host)', pitch: 0.75, rate: 0.92, isTrending: false, tags: [], gradient: 'linear-gradient(135deg, #475569, #1e293b)', icon: 'fa-radio' },
            { id: 'en_caroline', name: 'Caroline (US Expressive)', lang: 'en', gender: 'female', category: ['all', 'en', 'female'], sub: 'American English (Expressive)', pitch: 1.2, rate: 1.02, isTrending: false, tags: [], gradient: 'linear-gradient(135deg, #059669, #1e3a8a)', icon: 'fa-microphone' },
            { id: 'en_narrator', name: 'Christopher (Narrator)', lang: 'en', gender: 'male', category: ['all', 'en', 'male', 'character'], sub: 'Movie Trailer Narrator', pitch: 0.65, rate: 0.88, isTrending: true, tags: ['vip'], gradient: 'linear-gradient(135deg, #1e1b4b, #0f172a)', icon: 'fa-clapperboard' },
            { id: 'en_daisy', name: 'Daisy (Young British)', lang: 'en', gender: 'female', category: ['all', 'en', 'female'], sub: 'British English (Cheerful)', pitch: 1.35, rate: 1.1, isTrending: false, tags: [], gradient: 'linear-gradient(135deg, #fbbf24, #d97706)', icon: 'fa-face-smile' },

            // --- CHARACTERS & SCI-FI ---
            { id: 'char_bot', name: 'CyberBot 3000', lang: 'en', gender: 'male', category: ['all', 'trending', 'character'], sub: 'Sci-Fi AI Robot Assistant', pitch: 0.6, rate: 1.15, isTrending: true, tags: ['hot', 'vip'], gradient: 'linear-gradient(135deg, #06b6d4, #3b82f6)', icon: 'fa-robot' },
            { id: 'char_fairy', name: 'Tiên Nữ', lang: 'vi', gender: 'female', category: ['all', 'character'], sub: 'Giọng cổ tích, huyền ảo', pitch: 1.4, rate: 0.85, isTrending: false, tags: [], gradient: 'linear-gradient(135deg, #ec4899, #a855f7)', icon: 'fa-wand-magic-sparkles' },
            { id: 'char_wizard', name: 'Pháp Sư Tối Thượng', lang: 'vi', gender: 'male', category: ['all', 'character'], sub: 'Trầm hùng & Quyền năng', pitch: 0.65, rate: 0.85, isTrending: false, tags: ['vip'], gradient: 'linear-gradient(135deg, #4338ca, #1e1b4b)', icon: 'fa-hat-wizard' },
            { id: 'char_cartoon', name: 'Chú Cừu Vui Vẻ', lang: 'vi', gender: 'female', category: ['all', 'character'], sub: 'Nhí nhảnh trẻ thơ hoạt hình', pitch: 1.5, rate: 1.2, isTrending: false, tags: [], gradient: 'linear-gradient(135deg, #f472b6, #fb7185)', icon: 'fa-face-laugh-squint' },
            { id: 'char_monster', name: 'Quái Vật Hài Hước', lang: 'vi', gender: 'male', category: ['all', 'character'], sub: 'Trầm khàn nhộn nhịp', pitch: 0.5, rate: 0.9, isTrending: false, tags: [], gradient: 'linear-gradient(135deg, #15803d, #14532d)', icon: 'fa-dragon' },
            { id: 'char_betty', name: 'Betty Boop', lang: 'en', gender: 'female', category: ['all', 'character'], sub: 'Vintage Cartoon Voice', pitch: 1.45, rate: 1.2, isTrending: false, tags: [], gradient: 'linear-gradient(135deg, #f43f5e, #fda4af)', icon: 'fa-masks-theater' },

            // --- MEME & VIRAL ---
            { id: 'meme_rick', name: 'Rickroll Astley', lang: 'en', gender: 'male', category: ['all', 'trending', 'meme'], sub: 'Never Gonna Give You Up', pitch: 0.9, rate: 1.1, isTrending: true, tags: ['hot'], gradient: 'linear-gradient(135deg, #f43f5e, #3b82f6)', icon: 'fa-music' },
            { id: 'meme_google', name: 'Chị Google (Review Phim)', lang: 'vi', gender: 'female', category: ['all', 'trending', 'meme', 'vi'], sub: 'Reviewer Phim Huyền Thoại', pitch: 1.0, rate: 1.18, isTrending: true, tags: ['hot', 'vip'], gradient: 'linear-gradient(135deg, #4285f4, #34a853)', icon: 'fa-video' },
            { id: 'meme_pepe', name: 'Pepe The Frog', lang: 'en', gender: 'male', category: ['all', 'meme'], sub: 'Feels Good Man (Viral Meme)', pitch: 0.6, rate: 0.95, isTrending: false, tags: [], gradient: 'linear-gradient(135deg, #22c55e, #15803d)', icon: 'fa-frog' },
            { id: 'meme_anime', name: 'Anime Chan', lang: 'ja', gender: 'female', category: ['all', 'meme', 'character'], sub: 'Kawaii High-Pitch Anime', pitch: 1.6, rate: 1.2, isTrending: false, tags: ['vip'], gradient: 'linear-gradient(135deg, #f472b6, #c084fc)', icon: 'fa-heart' }
        ];
EOD;

// Update the ALL_VOICES array in ai.php
$pattern = '/const ALL_VOICES = \[.*?\];/s';
$aiPhp = preg_replace($pattern, $voicesDataset, $aiPhp);

// Update renderVoiceCards to render gorgeous cards with tags, gradients and custom icons
$oldRenderVoice = '        function renderVoiceCards(filterQuery = \'\') {
            const grid = document.getElementById(\'voiceCardsGrid\');
            if (!grid) return;
            grid.innerHTML = \'\';

            let filtered = ALL_VOICES;
            if (activeCategory === \'trending\') filtered = filtered.filter(v => v.isTrending);
            else if (activeCategory !== \'all\') filtered = filtered.filter(v => v.lang === activeCategory || v.gender === activeCategory);

            if (filterQuery) {
                filtered = filtered.filter(v => v.name.toLowerCase().includes(filterQuery) || v.sub.toLowerCase().includes(filterQuery));
            }

            filtered.forEach(v => {
                const isSel = currentVoice && currentVoice.id === v.id;
                const card = document.createElement(\'div\');
                card.style.padding = \'14px\';
                card.style.background = isSel ? \'rgba(16, 185, 129, 0.15)\' : \'#27272a\';
                card.style.border = isSel ? \'1.5px solid #10b981\' : \'1px solid #3f3f46\';
                card.style.borderRadius = \'12px\';
                card.style.cursor = \'pointer\';
                card.style.display = \'flex\';
                card.style.flexDirection = \'column\';
                card.style.alignItems = \'center\';
                card.style.textAlign = \'center\';
                card.style.gap = \'8px\';
                card.onclick = () => selectVoice(v);

                card.innerHTML = `
                    <div style="width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg, #10b981, #059669); display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px;">
                        <i class="fa-solid fa-microphone"></i>
                    </div>
                    <div>
                        <div style="font-size:13.5px; font-weight:700; color:#ffffff;">${v.name}</div>
                        <div style="font-size:11px; color:#a1a1aa; margin-top:2px;">${v.sub}</div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }';

$newRenderVoice = '        function renderVoiceCards(filterQuery = \'\') {
            const grid = document.getElementById(\'voiceCardsGrid\');
            if (!grid) return;
            grid.innerHTML = \'\';

            let filtered = ALL_VOICES;
            if (activeCategory === \'trending\') filtered = filtered.filter(v => v.isTrending);
            else if (activeCategory !== \'all\') {
                filtered = filtered.filter(v => (v.category && v.category.includes(activeCategory)) || v.lang === activeCategory || v.gender === activeCategory);
            }

            if (filterQuery) {
                filtered = filtered.filter(v => v.name.toLowerCase().includes(filterQuery) || v.sub.toLowerCase().includes(filterQuery));
            }

            if (filtered.length === 0) {
                grid.innerHTML = \'<div style="grid-column:1/-1; padding:40px; text-align:center; color:#71717a; font-size:13px;"><i class="fa-solid fa-microphone-slash" style="font-size:24px; margin-bottom:8px; display:block;"></i>Không tìm thấy giọng đọc phù hợp</div>\';
                return;
            }

            filtered.forEach(v => {
                const isSel = currentVoice && currentVoice.id === v.id;
                const card = document.createElement(\'div\');
                card.style.padding = \'12px 10px\';
                card.style.background = isSel ? \'rgba(16, 185, 129, 0.18)\' : \'#27272a\';
                card.style.border = isSel ? \'2px solid #10b981\' : \'1px solid #3f3f46\';
                card.style.borderRadius = \'14px\';
                card.style.cursor = \'pointer\';
                card.style.display = \'flex\';
                card.style.flexDirection = \'column\';
                card.style.alignItems = \'center\';
                card.style.textAlign = \'center\';
                card.style.gap = \'8px\';
                card.style.position = \'relative\';
                card.style.transition = \'all 0.2s ease\';
                card.onmouseenter = () => { card.style.transform = \'translateY(-3px)\'; card.style.boxShadow = \'0 8px 20px rgba(0,0,0,0.5)\'; };
                card.onmouseleave = () => { card.style.transform = \'none\'; card.style.boxShadow = \'none\'; };
                card.onclick = () => selectVoice(v);

                const hotBadge = v.tags && v.tags.includes(\'hot\') ? \'<span style="position:absolute; top:8px; right:8px; font-size:11px;">🔥</span>\' : \'\';
                const vipBadge = v.tags && v.tags.includes(\'vip\') ? \'<span style="position:absolute; top:8px; left:8px; font-size:11px;">👑</span>\' : \'\';

                card.innerHTML = `
                    ${hotBadge}
                    ${vipBadge}
                    <div style="width:48px; height:48px; border-radius:14px; background:${v.gradient || \'linear-gradient(135deg, #10b981, #059669)\'}; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; box-shadow:0 4px 12px rgba(0,0,0,0.35);">
                        <i class="fa-solid ${v.icon || \'fa-microphone\'}"></i>
                    </div>
                    <div style="width:100%; min-width:0;">
                        <div style="font-size:13px; font-weight:700; color:#ffffff; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${v.name}</div>
                        <div style="font-size:11px; color:#a1a1aa; margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${v.sub}</div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }';

$aiPhp = str_replace($oldRenderVoice, $newRenderVoice, $aiPhp);

// Update selectVoice to apply the custom pitch and rate of the selected voice!
$oldSelectVoice = '        function selectVoice(v) {
            currentVoice = v;
            const nameEl = document.getElementById(\'activeVoiceName\');
            const subEl = document.getElementById(\'activeVoiceSub\');
            if (nameEl) nameEl.textContent = v.name;
            if (subEl) subEl.textContent = `${v.lang} · ${v.gender || \'neutral\'} · Miễn phí`;
            closeVoiceModal();
        }';

$newSelectVoice = '        function selectVoice(v) {
            currentVoice = v;
            if (v.pitch) ttsPitch = v.pitch;
            if (v.rate) ttsRate = v.rate;

            const pitchSlider = document.querySelector(\'input[oninput*="ttsPitch"]\');
            const rateSlider = document.querySelector(\'input[oninput*="ttsRate"]\');
            const pitchVal = document.getElementById(\'ttsPitchVal\');
            const rateVal = document.getElementById(\'ttsSpeedVal\');

            if (pitchSlider && v.pitch) pitchSlider.value = v.pitch;
            if (rateSlider && v.rate) rateSlider.value = v.rate;
            if (pitchVal && v.pitch) pitchVal.textContent = v.pitch;
            if (rateVal && v.rate) rateVal.textContent = v.rate + \'x\';

            const nameEl = document.getElementById(\'activeVoiceName\');
            const subEl = document.getElementById(\'activeVoiceSub\');
            const thumbEl = document.getElementById(\'activeVoiceThumb\');

            if (nameEl) nameEl.textContent = v.name;
            if (subEl) subEl.textContent = `${v.sub} · Miễn phí`;
            if (thumbEl) {
                thumbEl.style.background = v.gradient || \'linear-gradient(135deg, #10b981, #059669)\';
                thumbEl.innerHTML = `<i class="fa-solid ${v.icon || \'fa-microphone\'}"></i>`;
            }
            closeVoiceModal();
        }';

$aiPhp = str_replace($oldSelectVoice, $newSelectVoice, $aiPhp);

// Update initial voice selection
$aiPhp = str_replace("let currentVoice = { id: 'thuy_tien', name: 'Thuỳ Tiên (Nữ Bắc)', sub: 'vi · female · Miễn phí', icon: 'fa-microphone', lang: 'vi' };", "let currentVoice = ALL_VOICES[0];", $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully upgraded 26+ full voices dataset with realistic pitch/rate modulation and icons!\n";
