<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$newVoiceJs = <<< 'EOD'
        // ===== RICH & DIVERSE 26+ VOICES WITH REAL SAMPLES & DRAMATIC ACOUSTICS =====
        const ALL_VOICES = [
            // --- VIETNAMESE VOICES ---
            { 
                id: 'vi_thuytien', 
                name: 'Thuỳ Tiên (Nữ Bắc)', 
                lang: 'vi', 
                gender: 'female', 
                category: ['all', 'trending', 'vi', 'female'], 
                sub: 'Nữ Miền Bắc (Chuẩn VTV)', 
                pitch: 1.15, 
                rate: 1.0, 
                sample: 'Xin chào, tôi là Thuỳ Tiên, phát thanh viên miền Bắc chuẩn VTV.',
                isTrending: true, 
                tags: ['hot', 'vip'], 
                gradient: 'linear-gradient(135deg, #10b981, #047857)', 
                icon: 'fa-microphone' 
            },
            { 
                id: 'vi_maiphuong', 
                name: 'Mai Phương (Nữ Nam)', 
                lang: 'vi', 
                gender: 'female', 
                category: ['all', 'trending', 'vi', 'female'], 
                sub: 'Nữ Miền Nam (Ngọt ngào)', 
                pitch: 1.45, 
                rate: 1.08, 
                sample: 'Dạ em chào anh chị, em là Mai Phương giọng miền Nam ngọt ngào đây nè!',
                isTrending: true, 
                tags: ['hot'], 
                gradient: 'linear-gradient(135deg, #f43f5e, #be123c)', 
                icon: 'fa-microphone' 
            },
            { 
                id: 'vi_tuanhung', 
                name: 'Tuấn Hùng (Nam Bắc)', 
                lang: 'vi', 
                gender: 'male', 
                category: ['all', 'trending', 'vi', 'male'], 
                sub: 'Nam Miền Bắc (Trầm ấm)', 
                pitch: 0.65, 
                rate: 0.95, 
                sample: 'Chào bạn, tôi là Tuấn Hùng, chúc bạn học tập và làm việc thật tốt.',
                isTrending: true, 
                tags: ['hot', 'vip'], 
                gradient: 'linear-gradient(135deg, #3b82f6, #1e40af)', 
                icon: 'fa-user-tie' 
            },
            { 
                id: 'vi_quangdung', 
                name: 'Quang Dũng (Nam Nam)', 
                lang: 'vi', 
                gender: 'male', 
                category: ['all', 'vi', 'male'], 
                sub: 'Nam Miền Nam (Truyền cảm)', 
                pitch: 0.55, 
                rate: 0.90, 
                sample: 'Thân chào các bạn sinh viên Việt Hàn Cà Mau, tôi là Quang Dũng.',
                isTrending: false, 
                tags: ['vip'], 
                gradient: 'linear-gradient(135deg, #8b5cf6, #4c1d95)', 
                icon: 'fa-user' 
            },
            { 
                id: 'vi_chihang', 
                name: 'Chị Hằng (Kể chuyện)', 
                lang: 'vi', 
                gender: 'female', 
                category: ['all', 'vi', 'female', 'character'], 
                sub: 'Kể chuyện cổ tích', 
                pitch: 1.35, 
                rate: 0.88, 
                sample: 'Ngày xửa ngày xưa, ở một ngôi làng nọ có một cô bé rất ngoan ngoãn.',
                isTrending: true, 
                tags: ['hot'], 
                gradient: 'linear-gradient(135deg, #f59e0b, #b45309)', 
                icon: 'fa-wand-magic-sparkles' 
            },
            { 
                id: 'vi_thaygiao', 
                name: 'Thầy Giáo (Giảng bài)', 
                lang: 'vi', 
                gender: 'male', 
                category: ['all', 'vi', 'male', 'character'], 
                sub: 'Hàn lâm & Sư phạm', 
                pitch: 0.75, 
                rate: 0.92, 
                sample: 'Các em chú ý, hôm nay chúng ta sẽ tìm hiểu về cấu trúc dữ liệu và giải thuật.',
                isTrending: false, 
                tags: [], 
                gradient: 'linear-gradient(135deg, #0ea5e9, #0369a1)', 
                icon: 'fa-graduation-cap' 
            },
            { 
                id: 'vi_mcthoisu', 
                name: 'MC Thời Sự (Nhanh)', 
                lang: 'vi', 
                gender: 'female', 
                category: ['all', 'vi', 'female'], 
                sub: 'Bản tin & Tin tức nhanh', 
                pitch: 1.1, 
                rate: 1.35, 
                sample: 'Bản tin 24 giờ phát sóng từ trường Cao đẳng Việt Hàn Cà Mau xin kính chào quý vị.',
                isTrending: false, 
                tags: ['vip'], 
                gradient: 'linear-gradient(135deg, #dc2626, #991b1b)', 
                icon: 'fa-bullhorn' 
            },
            { 
                id: 'vi_bacbaphi', 
                name: 'Bác Ba Phi (Hài hước)', 
                lang: 'vi', 
                gender: 'male', 
                category: ['all', 'vi', 'male', 'character'], 
                sub: 'Chuyện vui dân tộc Cà Mau', 
                pitch: 0.6, 
                rate: 1.15, 
                sample: 'Bác Ba Phi tao ở miệt Cà Mau đây, bữa nay kể bay nghe chuyện bắt cá sấu!',
                isTrending: true, 
                tags: ['hot'], 
                gradient: 'linear-gradient(135deg, #d97706, #78350f)', 
                icon: 'fa-face-laugh-beam' 
            },
            { 
                id: 'vi_cotam', 
                name: 'Cô Tấm (Thanh thoát)', 
                lang: 'vi', 
                gender: 'female', 
                category: ['all', 'vi', 'female', 'character'], 
                sub: 'Nhẹ nhàng bay bổng', 
                pitch: 1.6, 
                rate: 0.95, 
                sample: 'Bống bống bang bang, lên ăn cơm vàng cơm bạc nhà ta.',
                isTrending: false, 
                tags: [], 
                gradient: 'linear-gradient(135deg, #06b6d4, #0891b2)', 
                icon: 'fa-feather' 
            },

            // --- ENGLISH VOICES (REAL US / UK VOICES) ---
            { 
                id: 'en_sarah', 
                name: 'Sarah (US Warm)', 
                lang: 'en', 
                gender: 'female', 
                category: ['all', 'trending', 'en', 'female'], 
                sub: 'American English (Warm)', 
                pitch: 1.2, 
                rate: 1.0, 
                sample: 'Hello! I am Sarah, your AI voice assistant from the United States.',
                isTrending: true, 
                tags: ['hot', 'vip'], 
                gradient: 'linear-gradient(135deg, #f87171, #ea580c)', 
                icon: 'fa-microphone' 
            },
            { 
                id: 'en_alex', 
                name: 'Alex (US Professional)', 
                lang: 'en', 
                gender: 'male', 
                category: ['all', 'en', 'male'], 
                sub: 'American English (Male Pro)', 
                pitch: 0.75, 
                rate: 0.98, 
                sample: 'Welcome to Viet Han Ca Mau AI Workspace. How can I help you today?',
                isTrending: false, 
                tags: ['vip'], 
                gradient: 'linear-gradient(135deg, #38bdf8, #0284c7)', 
                icon: 'fa-user-tie' 
            },
            { 
                id: 'en_emma', 
                name: 'Emma (UK Refined)', 
                lang: 'en-GB', 
                gender: 'female', 
                category: ['all', 'trending', 'en', 'female'], 
                sub: 'British English (Refined)', 
                pitch: 1.25, 
                rate: 0.92, 
                sample: 'Good day! I am Emma, speaking with a British accent.',
                isTrending: true, 
                tags: ['hot'], 
                gradient: 'linear-gradient(135deg, #a855f7, #6b21a8)', 
                icon: 'fa-microphone' 
            },
            { 
                id: 'en_david', 
                name: 'David (UK Deep Host)', 
                lang: 'en-GB', 
                gender: 'male', 
                category: ['all', 'en', 'male'], 
                sub: 'British English (Deep Voice)', 
                pitch: 0.55, 
                rate: 0.90, 
                sample: 'This is David, presenting the latest technology broadcast.',
                isTrending: false, 
                tags: [], 
                gradient: 'linear-gradient(135deg, #475569, #1e293b)', 
                icon: 'fa-radio' 
            },
            { 
                id: 'en_narrator', 
                name: 'Christopher (Trailer Narrator)', 
                lang: 'en', 
                gender: 'male', 
                category: ['all', 'en', 'male', 'character'], 
                sub: 'Movie Trailer Deep Narrator', 
                pitch: 0.45, 
                rate: 0.85, 
                sample: 'In a world of artificial intelligence, one system changes everything.',
                isTrending: true, 
                tags: ['vip'], 
                gradient: 'linear-gradient(135deg, #1e1b4b, #0f172a)', 
                icon: 'fa-clapperboard' 
            },

            // --- CHARACTERS & SCI-FI ---
            { 
                id: 'char_bot', 
                name: 'CyberBot 3000', 
                lang: 'en', 
                gender: 'male', 
                category: ['all', 'trending', 'character'], 
                sub: 'Sci-Fi AI Robot Sound', 
                pitch: 0.4, 
                rate: 1.35, 
                sample: 'Beep boop! CyberBot 3000 initialized. Ready for user commands.',
                isTrending: true, 
                tags: ['hot', 'vip'], 
                gradient: 'linear-gradient(135deg, #06b6d4, #3b82f6)', 
                icon: 'fa-robot' 
            },
            { 
                id: 'char_fairy', 
                name: 'Tiên Nữ Huyền Ảo', 
                lang: 'vi', 
                gender: 'female', 
                category: ['all', 'character'], 
                sub: 'Giọng cổ tích thanh cao', 
                pitch: 1.75, 
                rate: 0.85, 
                sample: 'Ta là tiên nữ nơi bồng lai, chúc các bạn luôn vui vẻ và may mắn.',
                isTrending: false, 
                tags: [], 
                gradient: 'linear-gradient(135deg, #ec4899, #a855f7)', 
                icon: 'fa-wand-magic-sparkles' 
            },
            { 
                id: 'char_wizard', 
                name: 'Pháp Sư Tối Thượng', 
                lang: 'vi', 
                gender: 'male', 
                category: ['all', 'character'], 
                sub: 'Trầm hùng & Bí thuật', 
                pitch: 0.5, 
                rate: 0.82, 
                sample: 'Hỡi phàm nhân, sức mạnh ma pháp cổ xưa đã được khai mở.',
                isTrending: false, 
                tags: ['vip'], 
                gradient: 'linear-gradient(135deg, #4338ca, #1e1b4b)', 
                icon: 'fa-hat-wizard' 
            },
            { 
                id: 'char_cartoon', 
                name: 'Chú Cừu Nhí Nhảnh', 
                lang: 'vi', 
                gender: 'female', 
                category: ['all', 'character'], 
                sub: 'Hoạt hình trẻ thơ siêu cao', 
                pitch: 1.85, 
                rate: 1.25, 
                sample: 'Chào các bạn nhỏ, hôm nay trời đẹp quá cùng đi chơi với mình nha!',
                isTrending: false, 
                tags: [], 
                gradient: 'linear-gradient(135deg, #f472b6, #fb7185)', 
                icon: 'fa-face-laugh-squint' 
            },
            { 
                id: 'char_monster', 
                name: 'Quái Vật Hài Hước', 
                lang: 'vi', 
                gender: 'male', 
                category: ['all', 'character'], 
                sub: 'Trầm khàn ồm ồm hài hước', 
                pitch: 0.35, 
                rate: 0.88, 
                sample: 'Grừừừ! Ta là quái vật dễ thương nhất hệ mặt trời đây ha ha ha!',
                isTrending: false, 
                tags: [], 
                gradient: 'linear-gradient(135deg, #15803d, #14532d)', 
                icon: 'fa-dragon' 
            },

            // --- MEME & VIRAL ---
            { 
                id: 'meme_google', 
                name: 'Chị Google (Review Phim)', 
                lang: 'vi', 
                gender: 'female', 
                category: ['all', 'trending', 'meme', 'vi'], 
                sub: 'Tóm tắt phim 3 phút', 
                pitch: 1.05, 
                rate: 1.25, 
                sample: 'Chào mừng các bạn đã quay trở lại với kênh review phim 3 phút của chị Google.',
                isTrending: true, 
                tags: ['hot', 'vip'], 
                gradient: 'linear-gradient(135deg, #4285f4, #34a853)', 
                icon: 'fa-video' 
            },
            { 
                id: 'meme_rick', 
                name: 'Rickroll Astley', 
                lang: 'en', 
                gender: 'male', 
                category: ['all', 'trending', 'meme'], 
                sub: 'Never Gonna Give You Up', 
                pitch: 0.85, 
                rate: 1.15, 
                sample: 'Never gonna give you up, never gonna let you down, never gonna run around and desert you!',
                isTrending: true, 
                tags: ['hot'], 
                gradient: 'linear-gradient(135deg, #f43f5e, #3b82f6)', 
                icon: 'fa-music' 
            },
            { 
                id: 'meme_pepe', 
                name: 'Pepe The Frog', 
                lang: 'en', 
                gender: 'male', 
                category: ['all', 'meme'], 
                sub: 'Feels Good Man Meme', 
                pitch: 0.55, 
                rate: 0.95, 
                sample: 'Feels good man. Pepe is here to cheer you up.',
                isTrending: false, 
                tags: [], 
                gradient: 'linear-gradient(135deg, #22c55e, #15803d)', 
                icon: 'fa-frog' 
            },
            { 
                id: 'meme_anime', 
                name: 'Anime Chan (Kawaii)', 
                lang: 'ja', 
                gender: 'female', 
                category: ['all', 'meme', 'character'], 
                sub: 'Kawaii High Pitch Anime', 
                pitch: 1.9, 
                rate: 1.2, 
                sample: 'Konnichiwa! Ogenki desu ka? Anata ga daisuki desu!',
                isTrending: false, 
                tags: ['vip'], 
                gradient: 'linear-gradient(135deg, #f472b6, #c084fc)', 
                icon: 'fa-heart' 
            }
        ];
        currentVoice = ALL_VOICES[0];
EOD;

// Replace ALL_VOICES definition
$pattern = '/\/\/ ===== RICH & DIVERSE 26\+ VOICES DATASET =====.*?currentVoice = ALL_VOICES\[0\];/s';
$aiPhp = preg_replace($pattern, $newVoiceJs, $aiPhp);

// Update generateSpeech to match real system voices & apply acoustic profiles
$oldGenSpeech = '        function generateSpeech(customText = null) {
            const input = document.getElementById(\'ttsInputText\');
            const text = (customText !== null ? customText : (input ? input.value : \'\')).trim();
            if (!text) {
                alert(\'Vui lòng nhập nội dung cần đọc!\');
                return;
            }
            if (input && customText !== null) {
                input.value = text;
                updateTtsCounter(input);
            }

            if (\'speechSynthesis\' in window) {
                window.speechSynthesis.cancel();
                const utter = new SpeechSynthesisUtterance(text);
                utter.rate = ttsRate;
                utter.pitch = ttsPitch;
                utter.volume = ttsVolume;
                if (currentVoice.lang === \'vi\') utter.lang = \'vi-VN\';
                else if (currentVoice.lang === \'en\') utter.lang = \'en-US\';
                window.speechSynthesis.speak(utter);

                // Add to history if new
                const existingIdx = ttsHistory.findIndex(h => h.fullText === text);
                if (existingIdx !== -1) {
                    ttsHistory.splice(existingIdx, 1);
                }
                ttsHistory.unshift({
                    text: text.substring(0, 70) + (text.length > 70 ? \'...\' : \'\'),
                    fullText: text,
                    voice: currentVoice.name,
                    lang: currentVoice.lang || \'vi\',
                    time: new Date().toLocaleTimeString()
                });
                if (ttsHistory.length > 50) ttsHistory.pop();
                localStorage.setItem(\'vkc_tts_history\', JSON.stringify(ttsHistory));
                renderTtsHistory();
            } else {
                alert(\'Trình duyệt của bạn không hỗ trợ Web Speech API.\');
            }
        }';

$newGenSpeech = '        function generateSpeech(customText = null) {
            const input = document.getElementById(\'ttsInputText\');
            const text = (customText !== null ? customText : (input ? input.value : \'\')).trim();
            if (!text) {
                alert(\'Vui lòng nhập nội dung cần đọc!\');
                return;
            }
            if (input && customText !== null) {
                input.value = text;
                updateTtsCounter(input);
            }

            if (\'speechSynthesis\' in window) {
                window.speechSynthesis.cancel();
                const utter = new SpeechSynthesisUtterance(text);
                
                // Calculate dynamic pitch & speed by combining voice profile and slider
                const basePitch = (currentVoice && currentVoice.pitch) ? currentVoice.pitch : 1.0;
                const baseRate = (currentVoice && currentVoice.rate) ? currentVoice.rate : 1.0;

                utter.pitch = Math.max(0.2, Math.min(2.0, basePitch * (ttsPitch || 1.0)));
                utter.rate = Math.max(0.4, Math.min(2.5, baseRate * (ttsRate || 1.0)));
                utter.volume = ttsVolume || 1.0;

                const targetLang = (currentVoice && currentVoice.lang) ? currentVoice.lang : \'vi\';
                if (targetLang === \'vi\') utter.lang = \'vi-VN\';
                else if (targetLang === \'en-GB\') utter.lang = \'en-GB\';
                else if (targetLang.startsWith(\'en\')) utter.lang = \'en-US\';
                else if (targetLang === \'ja\') utter.lang = \'ja-JP\';
                else utter.lang = targetLang;

                // Match best system voice from browser synthesizer
                const voices = window.speechSynthesis.getVoices();
                if (voices && voices.length > 0) {
                    let matched = null;
                    if (targetLang.startsWith(\'vi\')) {
                        matched = voices.find(v => v.lang.includes(\'vi\') || v.name.includes(\'Vietnamese\') || v.name.includes(\'HoaiMy\') || v.name.includes(\'NamMinh\'));
                    } else if (targetLang.startsWith(\'en\')) {
                        if (currentVoice && currentVoice.gender === \'female\') {
                            matched = voices.find(v => v.lang.startsWith(\'en\') && (v.name.includes(\'Zira\') || v.name.includes(\'Samantha\') || v.name.includes(\'Jenny\') || v.name.includes(\'Female\') || v.name.includes(\'Google US English\')));
                        } else {
                            matched = voices.find(v => v.lang.startsWith(\'en\') && (v.name.includes(\'David\') || v.name.includes(\'Guy\') || v.name.includes(\'Male\') || v.name.includes(\'George\')));
                        }
                        if (!matched) matched = voices.find(v => v.lang.startsWith(\'en\'));
                    } else if (targetLang === \'ja\') {
                        matched = voices.find(v => v.lang.includes(\'ja\') || v.name.includes(\'Japanese\') || v.name.includes(\'Haruka\') || v.name.includes(\'Ichiro\'));
                    }
                    if (matched) utter.voice = matched;
                }

                window.speechSynthesis.speak(utter);

                // Add to history
                const existingIdx = ttsHistory.findIndex(h => h.fullText === text);
                if (existingIdx !== -1) {
                    ttsHistory.splice(existingIdx, 1);
                }
                ttsHistory.unshift({
                    text: text.substring(0, 70) + (text.length > 70 ? \'...\' : \'\'),
                    fullText: text,
                    voice: currentVoice ? currentVoice.name : \'Giọng AI\',
                    lang: targetLang,
                    time: new Date().toLocaleTimeString()
                });
                if (ttsHistory.length > 50) ttsHistory.pop();
                localStorage.setItem(\'vkc_tts_history\', JSON.stringify(ttsHistory));
                renderTtsHistory();
            } else {
                alert(\'Trình duyệt của bạn không hỗ trợ Web Speech API.\');
            }
        }';

$aiPhp = str_replace($oldGenSpeech, $newGenSpeech, $aiPhp);

// Update selectVoice to automatically speak the sample of the voice when clicked!
$oldSelectVoice = '        function selectVoice(v) {
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

$newSelectVoice = '        function selectVoice(v) {
            currentVoice = v;
            ttsPitch = v.pitch || 1.0;
            ttsRate = v.rate || 1.0;

            const pitchSlider = document.querySelector(\'input[oninput*="ttsPitch"]\');
            const rateSlider = document.querySelector(\'input[oninput*="ttsRate"]\');
            const pitchVal = document.getElementById(\'ttsPitchVal\');
            const rateVal = document.getElementById(\'ttsSpeedVal\');

            if (pitchSlider) pitchSlider.value = ttsPitch;
            if (rateSlider) rateSlider.value = ttsRate;
            if (pitchVal) pitchVal.textContent = ttsPitch;
            if (rateVal) rateVal.textContent = ttsRate + \'x\';

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

            // Speak greeting sample in selected voice so user immediately hears the difference!
            if (v.sample) {
                setTimeout(() => {
                    generateSpeech(v.sample);
                }, 200);
            }
        }';

$aiPhp = str_replace($oldSelectVoice, $newSelectVoice, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully supercharged voices with distinct acoustic modulation and language matching!\n";
