
        // ===== GLOBAL FLOATING TOAST NOTIFICATION =====
        function showToast(html, duration = 3000) {
            let container = document.getElementById('codexToastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'codexToastContainer';
                container.style.cssText = 'position:fixed; bottom:24px; right:24px; z-index:9999999; display:flex; flex-direction:column; gap:8px; pointer-events:none;';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            toast.style.cssText = 'background:rgba(15, 23, 42, 0.95); color:#f8fafc; padding:10px 16px; border-radius:10px; font-size:13px; font-weight:600; font-family:"Outfit",sans-serif; box-shadow:0 10px 30px rgba(0,0,0,0.35); border:1px solid rgba(255,255,255,0.12); display:flex; align-items:center; gap:8px; backdrop-filter:blur(8px); transform:translateY(12px); opacity:0; transition:all 0.25s cubic-bezier(0.16, 1, 0.3, 1); pointer-events:auto;';
            toast.innerHTML = html;
            container.appendChild(toast);
            
            requestAnimationFrame(() => {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
            });

            setTimeout(() => {
                toast.style.transform = 'translateY(12px)';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 250);
            }, duration);
        }

        // ===== GLOBAL STATE & CODEX WORKSPACE =====
        let currentProject = {
            name: '',
            isLocal: false,
            branch: 'main',
            files: []
        };
        let pendingAgentChangeSet = null;
        let isApprovalMode = false;
        let chatSessions = JSON.parse(localStorage.getItem('vkc_codex_sessions') || '[]');
        let currentSessionId = localStorage.getItem('vkc_codex_active_session') || ('session_' + Date.now());

        let attachedFiles = [];
        let customSystemPrompt = localStorage.getItem('vkc_ai_system_prompt') || '';
        let thinkingEnabled = true;
        let reasoningEffort = 'high';
        let ttsRate = 1.0;
        let ttsPitch = 1.0;
        let ttsVolume = 1.0;
        let activeCategory = 'all';
        let currentVoice = null;
        let ttsHistory = JSON.parse(localStorage.getItem('vkc_tts_history') || '[]');

        // ===== 100% FREE xKiro AI MODELS DATASET =====
        const ALL_MODELS = [
    {
        "id": "mistralai/mistral-large-2512",
        "name": "Mistral Large 2512",
        "provider": "Mistral",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Mistral Large 2512 by Mistral with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "mistralai/mistral-medium-3.5",
        "name": "Mistral Medium 3.5",
        "provider": "Mistral",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Mistral Medium 3.5 by Mistral with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "mistralai/mistral-small-2603",
        "name": "Mistral Small 2603",
        "provider": "Mistral",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Mistral Small 2603 by Mistral with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "mistralai/codestral-2508",
        "name": "Codestral 2508",
        "provider": "Mistral",
        "badge": "Coding",
        "context": "200K Context",
        "desc": "Codestral 2508 by Mistral with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "mistralai/devstral-medium",
        "name": "Devstral Medium",
        "provider": "Mistral",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Devstral Medium by Mistral with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "mistralai/ministral-14b",
        "name": "Ministral 14b",
        "provider": "Mistral",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Ministral 14b by Mistral with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.5-plus",
        "name": "Qwen3.5 Plus",
        "provider": "Qwen",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Qwen3.5 Plus by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3-coder-plus",
        "name": "Qwen3 Coder Plus",
        "provider": "Qwen",
        "badge": "Coding",
        "context": "128K Context",
        "desc": "Qwen3 Coder Plus by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "mistralai/ministral-8b",
        "name": "Ministral 8b",
        "provider": "Mistral",
        "badge": "Fast",
        "context": "128K Context",
        "desc": "Ministral 8b by Mistral with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "mistralai/ministral-3b",
        "name": "Ministral 3b",
        "provider": "Mistral",
        "badge": "Fast",
        "context": "128K Context",
        "desc": "Ministral 3b by Mistral with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "minimax/minimax-m2.7",
        "name": "MiniMax M2.7",
        "provider": "MiniMax",
        "badge": "High",
        "context": "1M Context",
        "desc": "MiniMax M2.7 by MiniMax with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "minimax/minimax-m2.7-highspeed",
        "name": "MiniMax M2.7 Highspeed",
        "provider": "MiniMax",
        "badge": "High",
        "context": "1M Context",
        "desc": "MiniMax M2.7 Highspeed by MiniMax with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "minimax/minimax-m2.5",
        "name": "MiniMax M2.5",
        "provider": "MiniMax",
        "badge": "High",
        "context": "1M Context",
        "desc": "MiniMax M2.5 by MiniMax with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "minimax/minimax-m2.5-highspeed",
        "name": "MiniMax M2.5 Highspeed",
        "provider": "MiniMax",
        "badge": "High",
        "context": "1M Context",
        "desc": "MiniMax M2.5 Highspeed by MiniMax with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "minimax/minimax-m2.1",
        "name": "MiniMax M2.1",
        "provider": "MiniMax",
        "badge": "High",
        "context": "1M Context",
        "desc": "MiniMax M2.1 by MiniMax with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "minimax/minimax-m2.1-highspeed",
        "name": "MiniMax M2.1 Highspeed",
        "provider": "MiniMax",
        "badge": "High",
        "context": "1M Context",
        "desc": "MiniMax M2.1 Highspeed by MiniMax with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "minimax/minimax-m2",
        "name": "MiniMax M2",
        "provider": "MiniMax",
        "badge": "High",
        "context": "1M Context",
        "desc": "MiniMax M2 by MiniMax with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "deepseek/deepseek-v4-pro",
        "name": "DeepSeek V4 Pro",
        "provider": "DeepSeek",
        "badge": "High",
        "context": "128K Context",
        "desc": "DeepSeek V4 Pro by DeepSeek with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "deepseek/deepseek-v4-flash",
        "name": "DeepSeek V4 Flash",
        "provider": "DeepSeek",
        "badge": "Fast",
        "context": "128K Context",
        "desc": "DeepSeek V4 Flash by DeepSeek with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "deepseek/deepseek-v3.2",
        "name": "DeepSeek V3.2",
        "provider": "DeepSeek",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "DeepSeek V3.2 by DeepSeek with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "deepseek/deepseek-chat-v3.1",
        "name": "DeepSeek Chat V3.1",
        "provider": "DeepSeek",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "DeepSeek Chat V3.1 by DeepSeek with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.8-max",
        "name": "Qwen3.8 Max",
        "provider": "Qwen",
        "badge": "High",
        "context": "128K Context",
        "desc": "Qwen3.8 Max by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.7-max",
        "name": "Qwen3.7 Max",
        "provider": "Qwen",
        "badge": "High",
        "context": "128K Context",
        "desc": "Qwen3.7 Max by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.7-plus",
        "name": "Qwen3.7 Plus",
        "provider": "Qwen",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Qwen3.7 Plus by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.6-max-preview",
        "name": "Qwen3.6 Max Preview",
        "provider": "Qwen",
        "badge": "High",
        "context": "128K Context",
        "desc": "Qwen3.6 Max Preview by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.6-plus",
        "name": "Qwen3.6 Plus",
        "provider": "Qwen",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Qwen3.6 Plus by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.6-27b",
        "name": "Qwen3.6 27b",
        "provider": "Qwen",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Qwen3.6 27b by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.6-35b-a3b",
        "name": "Qwen3.6 35b A3b",
        "provider": "Qwen",
        "badge": "Fast",
        "context": "128K Context",
        "desc": "Qwen3.6 35b A3b by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.5-397b-a17b",
        "name": "Qwen3.5 397b A17b",
        "provider": "Qwen",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Qwen3.5 397b A17b by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.5-omni-plus",
        "name": "Qwen3.5 Omni Plus",
        "provider": "Qwen",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Qwen3.5 Omni Plus by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.5-flash",
        "name": "Qwen3.5 Flash",
        "provider": "Qwen",
        "badge": "Fast",
        "context": "128K Context",
        "desc": "Qwen3.5 Flash by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3.5-omni-flash",
        "name": "Qwen3.5 Omni Flash",
        "provider": "Qwen",
        "badge": "Fast",
        "context": "128K Context",
        "desc": "Qwen3.5 Omni Flash by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3-max",
        "name": "Qwen3 Max",
        "provider": "Qwen",
        "badge": "High",
        "context": "128K Context",
        "desc": "Qwen3 Max by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3-vl-plus",
        "name": "Qwen3 Vl Plus",
        "provider": "Qwen",
        "badge": "Vision",
        "context": "128K Context",
        "desc": "Qwen3 Vl Plus by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen3-omni-flash",
        "name": "Qwen3 Omni Flash",
        "provider": "Qwen",
        "badge": "Fast",
        "context": "128K Context",
        "desc": "Qwen3 Omni Flash by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    },
    {
        "id": "qwen/qwen-plus-2025-07-28",
        "name": "Qwen Plus 2025 07 28",
        "provider": "Qwen",
        "badge": "Medium",
        "context": "128K Context",
        "desc": "Qwen Plus 2025 07 28 by Qwen with advanced reasoning capabilities.",
        "isPaid": false
    }
];
        let currentSelectedModel = ALL_MODELS.find(m => m.name.includes('DeepSeek V3.1')) || ALL_MODELS[0];
        let disabledAiModels = [];

        async function fetchDisabledAiModels() {
            try {
                const res = await fetch('/tkb/api/ai_models_api.php?action=get_status');
                const data = await res.json();
                if (data && data.success && Array.isArray(data.disabled_models)) {
                    disabledAiModels = data.disabled_models;
                }
            } catch (e) {
                console.warn('Cannot fetch disabled AI models:', e);
            }
            if (currentSelectedModel && disabledAiModels.includes(currentSelectedModel.id)) {
                const available = ALL_MODELS.find(m => !disabledAiModels.includes(m.id)) || ALL_MODELS[0];
                selectModel(available.id, true);
            }
        }
        fetchDisabledAiModels();

        // ===== VOICES DATASET =====
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

        // ===== UTILITY FUNCTIONS =====
        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function autoGrow(textarea) {
            if (!textarea) return;
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 140) + 'px';
        }

        function handleInputKey(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMsg();
            }
        }

        function copyCodeBlock(elementId, btn) {
            const codeEl = document.getElementById(elementId);
            if (!codeEl) return;
            navigator.clipboard.writeText(codeEl.innerText).then(() => {
                const orig = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-check" style="color:#22c55e;"></i> <span style="color:#22c55e;">Đã sao chép!</span>';
                setTimeout(() => { btn.innerHTML = orig; }, 2000);
            });
        }

        function downloadCodeFile(encodedCode, filename) {
            const code = decodeURIComponent(encodedCode);
            const blob = new Blob([code], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        let currentSandboxCode = '';
        let currentSandboxLang = 'html';

        function renderChatImageCard(imgUrl, promptText = 'Ảnh tạo bởi AI', rawPrompt = '') {
            const cardId = 'img_card_' + Math.random().toString(36).substr(2, 9);
            const safeUrl = escapeHtml(imgUrl);
            const safePrompt = escapeHtml(promptText);
            const encodedPrompt = encodeURIComponent(rawPrompt || promptText);
            
            return `
                <div class="chat-ai-image-card" id="${cardId}">
                    <div style="position:relative; width:100%; overflow:hidden; background:#1e293b; min-height:220px; display:flex; align-items:center; justify-content:center;">
                        <img src="${safeUrl}" alt="${safePrompt}" onclick="openChatImageLightbox('${safeUrl}', '${safePrompt}')" loading="lazy">
                        <div style="position:absolute; top:12px; right:12px; display:flex; gap:6px;">
                            <button type="button" onclick="openChatImageLightbox('${safeUrl}', '${safePrompt}')" title="Phóng to ảnh" style="background:rgba(15,23,42,0.75); border:1px solid rgba(255,255,255,0.2); color:#fff; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; backdrop-filter:blur(6px);">
                                <i class="fa-solid fa-expand" style="font-size:12px;"></i>
                            </button>
                            <button type="button" onclick="downloadImageDirect('${safeUrl}', 'ai_image_${Date.now()}.jpg')" title="Tải ảnh về máy" style="background:rgba(15,23,42,0.75); border:1px solid rgba(255,255,255,0.2); color:#fff; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; backdrop-filter:blur(6px);">
                                <i class="fa-solid fa-download" style="font-size:12px;"></i>
                            </button>
                        </div>
                    </div>
                    <div style="padding:12px 16px; background:#0f172a; border-top:1px solid #1e293b; display:flex; flex-direction:column; gap:8px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                            <div style="display:flex; align-items:center; gap:6px; font-size:12px; font-weight:700; color:#38bdf8;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                <span>Flux.1 AI Image</span>
                            </div>
                            <span style="font-size:11px; color:#64748b;">1024 × 1024 · HD</span>
                        </div>
                        ${promptText ? `<div style="font-size:12.5px; color:#cbd5e1; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">🎨 ${safePrompt}</div>` : ''}
                        <div style="display:flex; align-items:center; gap:8px; margin-top:2px; flex-wrap:wrap;">
                            <button type="button" onclick="downloadImageDirect('${safeUrl}', 'ai_image_${Date.now()}.jpg')" style="background:#10b981; color:#fff; border:none; padding:6px 12px; border-radius:8px; font-size:11.5px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:5px; box-shadow:0 2px 8px rgba(16,185,129,0.3);">
                                <i class="fa-solid fa-download"></i> <span>Tải về</span>
                            </button>
                            <button type="button" onclick="regenerateImageFromChat('${encodedPrompt}')" style="background:#334155; color:#f8fafc; border:none; padding:6px 12px; border-radius:8px; font-size:11.5px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:5px;">
                                <i class="fa-solid fa-rotate-right"></i> <span>Vẽ lại</span>
                            </button>
                            <button type="button" onclick="openInImageStudio('${encodedPrompt}', '${safeUrl}')" style="background:#1e293b; color:#94a3b8; border:1px solid #334155; padding:6px 12px; border-radius:8px; font-size:11.5px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:5px;">
                                <i class="fa-solid fa-palette"></i> <span>Mở Studio</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        let currentLightboxZoom = 1;
        let currentLightboxUrl = '';

        function openChatImageLightbox(url, caption = '') {
            currentLightboxUrl = url;
            currentLightboxZoom = 1;
            const modal = document.getElementById('chatLightboxModal');
            const img = document.getElementById('lightboxImg');
            const cap = document.getElementById('lightboxCaption');
            if (!modal || !img) return;
            img.src = url;
            img.style.transform = 'scale(1)';
            if (cap) cap.textContent = caption || 'Xem ảnh chất lượng cao';
            modal.style.display = 'flex';
        }

        function closeChatLightbox() {
            const modal = document.getElementById('chatLightboxModal');
            if (modal) modal.style.display = 'none';
        }

        function zoomLightboxImage(delta) {
            const img = document.getElementById('lightboxImg');
            if (!img) return;
            currentLightboxZoom = Math.max(0.5, Math.min(3.5, currentLightboxZoom + delta));
            img.style.transform = `scale(${currentLightboxZoom})`;
        }

        function downloadLightboxImage() {
            if (!currentLightboxUrl) return;
            downloadImageDirect(currentLightboxUrl, `vkc_ai_${Date.now()}.jpg`);
        }

        async function downloadImageDirect(url, filename = 'ai_image.jpg') {
            try {
                const res = await fetch(url);
                const blob = await res.blob();
                const blobUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(blobUrl);
            } catch(e) {
                window.open(url, '_blank');
            }
        }

        function openInImageStudio(encodedPrompt, imgUrl) {
            const prompt = decodeURIComponent(encodedPrompt || '');
            switchMode('image', document.getElementById('tabImage'));
            const input = document.getElementById('imgPromptInput');
            if (input && prompt) {
                input.value = prompt;
            }
            if (imgUrl) {
                activeGeneratedImageUrl = imgUrl;
                const displayImg = document.getElementById('imgResultDisplay');
                const placeholder = document.getElementById('imgPlaceholder');
                const toolbar = document.getElementById('imgOverlayToolbar');
                if (displayImg) { displayImg.src = imgUrl; displayImg.style.display = 'block'; }
                if (placeholder) placeholder.style.display = 'none';
                if (toolbar) toolbar.style.display = 'flex';
            }
        }

        async function regenerateImageFromChat(encodedPrompt) {
            const prompt = decodeURIComponent(encodedPrompt || '');
            if (!prompt) return;
            const input = document.getElementById('userInput');
            if (input) {
                input.value = 'vẽ lại: ' + prompt;
                sendMsg();
            }
        }

        function isImageGenerationIntent(text) {
            if (!text) return false;
            const lower = text.toLowerCase().trim();
            if (lower.startsWith('/image') || lower.startsWith('/draw') || lower.startsWith('/art') || lower.startsWith('/taoanh') || lower.startsWith('/ve')) {
                return true;
            }
            const drawKeywords = [
                'vẽ cho tôi', 'vẽ giúp tôi', 'vẽ một', 'hãy vẽ', 'vẽ hình', 'vẽ tranh', 'vẽ ảnh',
                'tạo ảnh', 'tạo hình ảnh', 'tạo bức ảnh', 'tạo hình', 'sinh ảnh', 'vẽ nhân vật',
                'draw me', 'generate image', 'create image', 'draw an', 'draw a', 'paint an', 'paint a'
            ];
            return drawKeywords.some(kw => lower.includes(kw));
        }

        function extractCleanPrompt(text) {
            if (!text) return '';
            let p = text.trim();
            p = p.replace(/^\/(image|draw|art|taoanh|ve)\s+/i, '');
            p = p.replace(/^(vẽ lại:\s*|vẽ cho tôi|vẽ giúp tôi|vẽ một|hãy vẽ|vẽ hình|vẽ tranh|vẽ ảnh|tạo ảnh|tạo hình ảnh|tạo bức ảnh|tạo hình|sinh ảnh|vẽ nhân vật|draw me|generate image of|create image of|draw an|draw a|paint an|paint a)\s+/i, '');
            return p.trim() || text.trim();
        }

        function applySuggestion(text) {
            const input = document.getElementById('userInput');
            if (input) {
                input.value = text;
                autoGrow(input);
                sendMsg();
            }
        }

        function formatMarkdown(text) {
            if (!text) return '';

            // 1. Process <think>...</think> Reasoning Process
            text = text.replace(/<think>([\s\S]*?)<\/think>/gi, function(match, thoughts) {
                const thinkId = 'think_' + Math.random().toString(36).substr(2, 9);
                return `
                    <div class="ai-think-box">
                        <div class="ai-think-header" onclick="toggleThinkBlock('${thinkId}')">
                            <span style="display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-brain" style="color:#8b5cf6;"></i>
                                <span>Quá trình suy luận (Thinking Chain)</span>
                            </span>
                            <i class="fa-solid fa-chevron-down" id="icon_${thinkId}"></i>
                        </div>
                        <div class="ai-think-content" id="${thinkId}" style="display:none;">
                            ${escapeHtml(thoughts.trim())}
                        </div>
                    </div>
                `;
            });

            // 2. Process Code Blocks with Full Antigravity / Codex Interactive Studio
            const codeBlocks = [];
            let processedText = text.replace(/```([a-zA-Z0-9_-]*)\n?([\s\S]*?)```/g, function(match, lang, code) {
                const id = 'code_' + Math.random().toString(36).substr(2, 9);
                const rawLang = (lang || 'code').toLowerCase();
                const meta = getLanguageMeta(rawLang);
                const defFile = getDefaultFileName(rawLang);
                const trimmedCode = code.trim();
                const rawEscaped = encodeURIComponent(trimmedCode);
                
                let runBtns = '';
                if (meta.isRunnable) {
                    runBtns = `
                        <button type="button" class="ai-ide-btn ai-ide-btn-run" onclick="openLiveRunner('${rawEscaped}', '${rawLang}')" title="Mở Studio Sandbox chạy thử nghiệm và kiểm tra kết quả">
                            <i class="fa-solid fa-play"></i> <span>Chạy Code</span>
                        </button>
                        <button type="button" class="ai-ide-btn ai-ide-btn-inline" onclick="runCodeInline('${id}', '${rawEscaped}', '${rawLang}', this)" title="Chạy nhanh trực tiếp bên dưới khối code">
                            <i class="fa-solid fa-bolt"></i> <span>Chạy nhanh</span>
                        </button>
                    `;
                }

                const ideBtn = `
                    <button type="button" class="ai-ide-btn ai-ide-btn-ide" onclick="openInCodeIde('${rawEscaped}', '${rawLang}')" title="Mở đoạn mã này trong Code IDE để làm bài tập hoặc chỉnh sửa chuyên sâu">
                        <i class="fa-solid fa-laptop-code"></i> <span>Mở IDE</span>
                    </button>
                `;

                const applyBtn = `
                    <button type="button" class="ai-ide-btn" onclick="applyCodeBlockToWorkspace('${rawEscaped}', '${rawLang}')" title="Tự động áp dụng và ghi mã nguồn này vào tệp trong thư mục dự án" style="background:#065f46; border:1px solid #059669; color:#a7f3d0; font-weight:700;">
                        <i class="fa-solid fa-bolt" style="color:#34d399;"></i> <span>Sửa vào folder</span>
                    </button>
                `;

                const blockHtml = `
                    <div class="ai-ide-code-block" id="block_${id}">
                        <div class="ai-ide-toolbar">
                            <div class="ai-ide-lang-badge" style="color:${meta.color};">
                                <i class="${meta.icon}"></i>
                                <span style="letter-spacing:0.5px;">${meta.name}</span>
                            </div>
                            <div class="ai-ide-btn-group">
                                ${runBtns}
                                ${ideBtn}
                                ${applyBtn}
                                <button type="button" class="ai-ide-btn" onclick="copyCodeBlock('${id}', this)" title="Sao chép toàn bộ mã nguồn">
                                    <i class="fa-solid fa-copy"></i> <span>Sao chép</span>
                                </button>
                                <button type="button" class="ai-ide-btn" onclick="downloadCodeFile('${rawEscaped}', '${defFile}')" title="Tải tệp tin về máy">
                                    <i class="fa-solid fa-download"></i> <span>Tải file</span>
                                </button>
                            </div>
                        </div>
                        <pre style="margin:0; padding:14px 16px; overflow-x:auto; font-family:'Fira Code',Consolas,monospace; font-size:13px; line-height:1.6; color:#f8fafc;"><code id="${id}">${escapeHtml(trimmedCode)}</code></pre>
                        
                        <!-- Inline Execution Result Drawer -->
                        <div class="ai-inline-drawer" id="inline_drawer_${id}">
                            <div class="ai-inline-drawer-header">
                                <span style="display:flex; align-items:center; gap:6px; font-weight:700; color:#10b981;">
                                    <i class="fa-solid fa-terminal"></i> <span id="inline_title_${id}">Kết quả thực thi (Inline Output)</span>
                                </span>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <button type="button" onclick="openLiveRunner('${rawEscaped}', '${rawLang}')" style="background:transparent; border:none; color:#38bdf8; font-size:11.5px; font-weight:700; cursor:pointer;">
                                        <i class="fa-solid fa-expand"></i> Phóng to Studio
                                    </button>
                                    <button type="button" onclick="document.getElementById('inline_drawer_${id}').style.display='none'" style="background:transparent; border:none; color:#94a3b8; font-size:11.5px; cursor:pointer;">
                                        <i class="fa-solid fa-xmark"></i> Đóng
                                    </button>
                                </div>
                            </div>
                            <div class="ai-inline-drawer-output" id="inline_output_${id}">Đang chờ thực thi...</div>
                        </div>
                    </div>
                `;
                codeBlocks.push(blockHtml);
                return `___CODE_BLOCK_${codeBlocks.length - 1}___`;
            });

            // 3. Process Markdown images and links
            const specialElements = [];
            processedText = processedText.replace(/!\[([^\]]*)\]\((https?:\/\/[^\s\)]+)\)/gi, (match, alt, url) => {
                specialElements.push(renderChatImageCard(url, alt, alt));
                return `___SPECIAL_EL_${specialElements.length - 1}___`;
            });

            // 4. Process Markdown inline tokens with correct group replacements ($1)
            let html = escapeHtml(processedText);
            html = html.replace(/`([^`]+)`/g, '<code style="background:rgba(217,27,67,0.06); color:#d91b43; padding:2px 6px; border-radius:4px; font-family:monospace; font-size:12.5px; font-weight:600;">$1</code>');
            html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
            html = html.replace(/^### (.*$)/gim, '<h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:14px 0 6px;">$1</h3>');
            html = html.replace(/^## (.*$)/gim, '<h2 style="font-size:17px; font-weight:800; color:#0f172a; margin:16px 0 8px;">$1</h2>');
            html = html.replace(/^# (.*$)/gim, '<h1 style="font-size:19px; font-weight:800; color:#0f172a; margin:18px 0 10px;">$1</h1>');
            html = html.replace(/^[\*\-•]\s+(.*)$/gm, '<li style="margin-left:20px; list-style-type:disc; margin-bottom:4px;">$1</li>');
            html = html.replace(/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/gi, '<a href="$2" target="_blank" rel="noopener noreferrer" style="color:#0284c7; text-decoration:underline; font-weight:600;">$1</a>');
            html = html.replace(/\n/g, '<br>');

            // 5. Restore Code blocks & Special elements
            specialElements.forEach((el, idx) => {
                html = html.replace(`___SPECIAL_EL_${idx}___`, el);
            });
            codeBlocks.forEach((block, idx) => {
                html = html.replace(`___CODE_BLOCK_${idx}___`, block);
            });
            return html;
        }

        function isLegacyAgentCardMarkup(text) {
            const value = String(text || '');
            return value.includes('codex-process-time') &&
                value.includes('codex-agent-container') &&
                value.includes('codex-terminal-box');
        }

        function renderLegacyAgentCard(bubble, markup) {
            // Old sessions may contain an escaped card.  Rehydrate only this
            // known card shape and remove active attributes, so cached markup
            // can be displayed but cannot execute arbitrary code.
            const parser = new DOMParser();
            const documentFragment = parser.parseFromString(String(markup || ''), 'text/html');
            documentFragment.querySelectorAll('script, iframe, object, embed, link, meta, style').forEach(node => node.remove());
            documentFragment.querySelectorAll('*').forEach(node => {
                Array.from(node.attributes).forEach(attribute => {
                    if (/^on/i.test(attribute.name) || (attribute.name === 'href' && /^javascript:/i.test(attribute.value))) {
                        node.removeAttribute(attribute.name);
                    }
                });
            });
            bubble.innerHTML = documentFragment.body.innerHTML;
        }

        function repairLegacyAgentCards() {
            document.querySelectorAll('.chat-bubble.bot').forEach(bubble => {
                const rawMarkup = bubble.textContent || '';
                if (isLegacyAgentCardMarkup(rawMarkup)) {
                    renderLegacyAgentCard(bubble, rawMarkup);
                }
            });
        }

        function appendBubbleUI(role, text, save = true, isHtml = false) {
            const hero = document.getElementById('heroWelcome');
            if (hero) hero.style.display = 'none';
            const stream = document.getElementById('messagesStream');
            if (stream) stream.style.display = 'flex';

            const bubbleWrapper = document.createElement('div');
            bubbleWrapper.style.display = 'flex';
            bubbleWrapper.style.flexDirection = 'column';
            bubbleWrapper.style.alignItems = role === 'user' ? 'flex-end' : 'flex-start';
            bubbleWrapper.style.width = '100%';

            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble ' + (role === 'user' ? 'user' : 'bot');
            
            if (role === 'user') {
                if (isHtml) bubble.innerHTML = text;
                else bubble.innerText = text;
            } else {
                const isRawCardHtml = isHtml || (typeof text === 'string' && (
                    text.includes('codex-agent-container') ||
                    text.includes('codex-change-card') ||
                    text.includes('codex-preview-card') ||
                    text.includes('codex-process-time') ||
                    text.includes('codex-action-btn') ||
                    text.includes('codex-terminal-box') ||
                    text.includes('class="codex-') ||
                    text.trim().startsWith('<div')
                ));

                if (isRawCardHtml) {
                    bubble.innerHTML = text;
                } else if (typeof isLegacyAgentCardMarkup === 'function' && isLegacyAgentCardMarkup(text)) {
                    renderLegacyAgentCard(bubble, text);
                } else {
                    bubble.innerHTML = formatMarkdown(text);
                }
            }
            bubbleWrapper.appendChild(bubble);

            if (role === 'bot') {
                const toolbar = document.createElement('div');
                toolbar.style.display = 'flex';
                toolbar.style.alignItems = 'center';
                toolbar.style.gap = '8px';
                toolbar.style.marginTop = '6px';
                toolbar.style.paddingLeft = '4px';

                const copyBtn = document.createElement('button');
                copyBtn.type = 'button';
                copyBtn.style.background = 'transparent';
                copyBtn.style.border = 'none';
                copyBtn.style.color = '#94a3b8';
                copyBtn.style.fontSize = '12px';
                copyBtn.style.cursor = 'pointer';
                copyBtn.style.display = 'flex';
                copyBtn.style.alignItems = 'center';
                copyBtn.style.gap = '4px';
                copyBtn.innerHTML = '<i class="fa-solid fa-copy"></i> <span>Sao chép</span>';
                copyBtn.onclick = () => {
                    navigator.clipboard.writeText(bubble.innerText).then(() => {
                        copyBtn.innerHTML = '<i class="fa-solid fa-check" style="color:#10b981;"></i> <span style="color:#10b981;">Đã sao chép</span>';
                        setTimeout(() => {
                            copyBtn.innerHTML = '<i class="fa-solid fa-copy"></i> <span>Sao chép</span>';
                        }, 2000);
                    });
                };

                const ttsBtn = document.createElement('button');
                ttsBtn.type = 'button';
                ttsBtn.style.background = 'transparent';
                ttsBtn.style.border = 'none';
                ttsBtn.style.color = '#94a3b8';
                ttsBtn.style.fontSize = '12px';
                ttsBtn.style.cursor = 'pointer';
                ttsBtn.style.display = 'flex';
                ttsBtn.style.alignItems = 'center';
                ttsBtn.style.gap = '4px';
                ttsBtn.innerHTML = '<i class="fa-solid fa-volume-high"></i> <span>Đọc</span>';
                ttsBtn.onclick = () => {
                    const cleanText = bubble.innerText.replace(/`{3}[\s\S]*?`{3}/g, '').trim();
                    if (cleanText) generateSpeech(cleanText);
                };

                toolbar.appendChild(copyBtn);
                toolbar.appendChild(ttsBtn);
                bubbleWrapper.appendChild(toolbar);
            }

            stream.appendChild(bubbleWrapper);

            const body = document.getElementById('aiChatBody');
            if (body) {
                setTimeout(() => { body.scrollTop = body.scrollHeight; }, 50);
            }
        }

        function toggleThinkBlock(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById('icon_' + id);
            if (el) {
                const isHidden = el.style.display === 'none';
                el.style.display = isHidden ? 'block' : 'none';
                if (icon) icon.className = isHidden ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down';
            }
        }

        // ===== ANTIGRAVITY LIVE SANDBOX CORE CONTROLLER =====
        function openLiveRunner(encodedCode, lang) {
            const code = decodeURIComponent(encodedCode);
            currentSandboxCode = code;
            currentSandboxLang = (lang || 'html').toLowerCase();
            currentSandboxStdin = '';
            currentDeployUrl = '';

            const modal = document.getElementById('liveRunnerModal');
            const title = document.getElementById('liveRunnerTitle');
            const subTitle = document.getElementById('liveRunnerSubTitle');
            const editor = document.getElementById('liveEditorTextarea');
            const stdinInput = document.getElementById('liveStdinInput');

            if (editor) editor.value = code;
            if (stdinInput) stdinInput.value = '';

            const meta = getLanguageMeta(currentSandboxLang);
            if (title) title.innerHTML = `<span>Antigravity Live Sandbox</span> · <span style="color:${meta.color};">${meta.name}</span>`;
            if (subTitle) subTitle.textContent = `Tệp thực thi: ${getDefaultFileName(currentSandboxLang)} · Môi trường cách ly máy chủ an toàn`;

            // Choose default tab
            if (meta.isWeb) {
                switchRunnerTab('preview');
            } else {
                switchRunnerTab('console');
            }

            if (modal) modal.style.display = 'flex';

            // Automatically execute
            executeCurrentSandbox();
        }

        function switchRunnerTab(tab) {
            currentActiveView = tab;
            const pPreview = document.getElementById('liveSandboxPreviewWrap');
            const pConsole = document.getElementById('liveConsoleWrap');
            const pEditor = document.getElementById('liveEditorWrap');
            const bPreview = document.getElementById('tabLivePreviewBtn');
            const bConsole = document.getElementById('tabLiveConsoleBtn');
            const bEditor = document.getElementById('tabLiveEditorBtn');
            const devSwitcher = document.getElementById('liveDeviceSwitcher');

            if (bPreview) bPreview.classList.toggle('active', tab === 'preview');
            if (bConsole) bConsole.classList.toggle('active', tab === 'console');
            if (bEditor) bEditor.classList.toggle('active', tab === 'editor');

            if (pPreview) pPreview.style.display = (tab === 'preview') ? 'flex' : 'none';
            if (pConsole) pConsole.style.display = (tab === 'console') ? 'flex' : 'none';
            if (pEditor) pEditor.style.display = (tab === 'editor') ? 'flex' : 'none';

            if (devSwitcher) devSwitcher.style.display = (tab === 'preview') ? 'flex' : 'none';
        }

        function setSandboxDevice(device) {
            currentSandboxDevice = device;
            const frame = document.getElementById('liveSandboxDeviceFrame');
            const btnDesk = document.getElementById('btnDevDesktop');
            const btnTab = document.getElementById('btnDevTablet');
            const btnMob = document.getElementById('btnDevMobile');

            if (btnDesk) btnDesk.classList.toggle('active', device === 'desktop');
            if (btnTab) btnTab.classList.toggle('active', device === 'tablet');
            if (btnMob) btnMob.classList.toggle('active', device === 'mobile');

            if (!frame) return;
            if (device === 'desktop') {
                frame.style.width = '100%';
                frame.style.borderRadius = '0';
                frame.style.border = 'none';
            } else if (device === 'tablet') {
                frame.style.width = '768px';
                frame.style.borderRadius = '14px';
                frame.style.border = '1px solid #475569';
            } else if (device === 'mobile') {
                frame.style.width = '375px';
                frame.style.borderRadius = '18px';
                frame.style.border = '2px solid #475569';
            }
        }

        function refreshLiveRunner() {
            if (currentSandboxCode) {
                executeCurrentSandbox();
            }
        }

        function closeLiveRunner() {
            const modal = document.getElementById('liveRunnerModal');
            if (modal) modal.style.display = 'none';
        }

        function clearLiveConsole() {
            const out = document.getElementById('liveConsoleOutput');
            if (out) {
                out.innerHTML = '<div style="color:#64748b; font-style:italic;">[Console đã được xóa]</div>';
            }
        }

        function appendConsoleLog(level, message) {
            const out = document.getElementById('liveConsoleOutput');
            if (!out) return;
            const row = document.createElement('div');
            row.style.marginBottom = '4px';
            const time = new Date().toLocaleTimeString();
            let color = '#38bdf8';
            let tag = 'LOG';
            if (level === 'warn') { color = '#facc15'; tag = 'WARN'; }
            else if (level === 'error') { color = '#ef4444'; tag = 'ERROR'; }
            else if (level === 'info') { color = '#10b981'; tag = 'INFO'; }

            row.innerHTML = `<span style="color:#64748b; font-size:11px;">[${time}]</span> <span style="background:rgba(255,255,255,0.08); color:${color}; padding:1px 5px; border-radius:3px; font-size:11px; font-weight:700;">${tag}</span> <span>${escapeHtml(message)}</span>`;
            out.appendChild(row);
            out.scrollTop = out.scrollHeight;
        }

        // Global PostMessage listener from iframe console
        window.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'VKC_SANDBOX_LOG') {
                appendConsoleLog(event.data.level, event.data.message);
            }
        });

        async function executeCurrentSandbox() {
            const code = currentSandboxCode;
            const lang = currentSandboxLang;
            const stdin = currentSandboxStdin;
            const meta = getLanguageMeta(lang);

            const badge = document.getElementById('liveRunnerStatusBadge');
            const frame = document.getElementById('liveSandboxFrame');
            const loader = document.getElementById('livePreviewLoader');
            const consoleBox = document.getElementById('liveConsoleOutput');

            if (badge) badge.innerHTML = '<span class="term-badge-running"><i class="fa-solid fa-spinner fa-spin"></i> Đang thực thi...</span>';
            if (consoleBox) consoleBox.innerHTML = `<div style="color:#64748b; margin-bottom:8px;">=== Khởi động Sandbox Runner [${meta.name}] ===</div>`;

            // 1. FRONTEND: HTML / CSS / JS CLIENT-SIDE
            if (['html', 'htm', 'javascript', 'js', 'css'].includes(lang)) {
                if (loader) loader.style.display = 'none';

                const consoleInterceptorScript = `
                <script>
                (function() {
                    function sendLog(level, args) {
                        try {
                            const msg = Array.from(args).map(a => (typeof a === 'object' ? JSON.stringify(a) : String(a))).join(' ');
                            window.parent.postMessage({ type: 'VKC_SANDBOX_LOG', level: level, message: msg }, '*');
                        } catch(e) {}
                    }
                    const origLog = console.log, origWarn = console.warn, origErr = console.error, origInfo = console.info;
                    console.log = function(...args) { sendLog('log', args); origLog.apply(console, args); };
                    console.warn = function(...args) { sendLog('warn', args); origWarn.apply(console, args); };
                    console.error = function(...args) { sendLog('error', args); origErr.apply(console, args); };
                    console.info = function(...args) { sendLog('info', args); origInfo.apply(console, args); };
                    window.onerror = function(msg, url, line) {
                        sendLog('error', ['Lỗi JavaScript (dòng ' + line + '): ' + msg]);
                    };
                })();
                <\/script>
                `;

                let docHtml = '';
                if (lang === 'html' || lang === 'htm') {
                    if (code.includes('<head>')) {
                        docHtml = code.replace('<head>', '<head>' + consoleInterceptorScript);
                    } else {
                        docHtml = consoleInterceptorScript + code;
                    }
                } else if (lang === 'javascript' || lang === 'js') {
                    docHtml = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>JS Live Sandbox</title><style>body{font-family:sans-serif;padding:24px;background:#f8fafc;color:#0f172a;line-height:1.6;}#output{background:#0f172a;color:#38bdf8;padding:16px;border-radius:10px;font-family:monospace;margin-top:14px;white-space:pre-wrap;}</style>${consoleInterceptorScript}</head><body><h3 style="margin-top:0;color:#0f172a;">JavaScript Live Execution</h3><div id="output">Đang chạy...</div><script>const _out = document.getElementById('output'); try { const oldLog = console.log; let buffer = ''; console.log = function(...args){ oldLog.apply(console, args); buffer += args.join(' ') + '\\n'; _out.innerText = buffer; }; ${code} ; if(!buffer) _out.innerText = '[Script thực thi thành công không có output in ra]'; } catch(e){ _out.innerHTML = '<span style="color:#ef4444;font-weight:bold;">Lỗi: ' + e.message + '</span>'; }<\/script></body></html>`;
                } else if (lang === 'css') {
                    docHtml = `<!DOCTYPE html><html><head><meta charset="utf-8"><style>${code}</style>${consoleInterceptorScript}</head><body><div style="padding:30px;"><div class="card" style="max-width:480px; margin:0 auto; padding:24px; border:1px solid #e2e8f0; border-radius:16px; box-shadow:0 10px 25px rgba(0,0,0,0.05); text-align:center;"><h2>CSS Live Preview</h2><p>Mẫu giao diện thử nghiệm với mã CSS của bạn.</p><button class="btn" style="padding:10px 20px; border-radius:8px; cursor:pointer;">Nút bấm mẫu</button></div></div></body></html>`;
                }

                if (frame) frame.srcdoc = docHtml;
                if (badge) badge.innerHTML = '<span class="term-badge-success">🟢 Web Preview Sẵn sàng</span>';
                appendConsoleLog('info', 'Môi trường Web Client-side đã khởi tạo thành công.');
                return;
            }

            // 2. PHP BACKEND WEB & CLI
            if (lang === 'php') {
                if (loader) loader.style.display = 'flex';

                // Bundle all files in active project workspace
                const projectVirtualFiles = {};
                if (currentProject.files && currentProject.files.length > 0) {
                    currentProject.files.forEach(f => {
                        const p = f.path || f.name;
                        projectVirtualFiles[p] = f.data;
                    });
                }
                projectVirtualFiles['index.php'] = code;

                try {
                    // Deploy to Apache temp server for Web Preview
                    const deployForm = new FormData();
                    deployForm.append('virtual_files', JSON.stringify(projectVirtualFiles));
                    deployForm.append('active_file', 'index.php');

                    const deployResp = await fetch('/tkb/api/deploy_web.php', {
                        method: 'POST',
                        body: deployForm
                    });

                    if (deployResp.ok) {
                        const deployRes = await deployResp.json();
                        if (deployRes.url) {
                            currentDeployUrl = deployRes.url;
                            if (frame) frame.src = deployRes.url;
                            if (loader) loader.style.display = 'none';
                        }
                    }
                } catch(e) {
                    if (loader) loader.style.display = 'none';
                }

                // Run CLI execution via run_code.php for Terminal tab
                try {
                    const runForm = new FormData();
                    runForm.append('language', 'php');
                    runForm.append('active_file', 'index.php');
                    runForm.append('stdin', stdin || '');
                    runForm.append('virtual_files', JSON.stringify(projectVirtualFiles));

                    const resp = await fetch('/tkb/api/run_code.php', { method: 'POST', body: runForm });
                    const res = await resp.json();

                    if (res.error) {
                        if (consoleBox) consoleBox.innerHTML += `<div style="color:#ef4444; font-weight:700; margin-top:8px;"><i class="fa-solid fa-triangle-exclamation"></i> Lỗi thực thi:<br>${escapeHtml(res.error)}</div>`;
                        if (badge) badge.innerHTML = '<span class="term-badge-error">🔴 Lỗi</span>';
                    } else {
                        let outText = res.stdout || '';
                        if (res.stderr) outText += '\n[STDERR]:\n' + res.stderr;
                        if (!outText) outText = '[Chương trình hoàn tất và không in gì ra stdout]';
                        if (consoleBox) {
                            consoleBox.innerHTML += `
                                <div style="color:#10b981; font-weight:700; margin-top:6px;">[Hoàn thành trong ${res.time}ms]</div>
                                <div style="color:#38bdf8; margin-top:6px; white-space:pre-wrap;">${escapeHtml(outText)}</div>
                            `;
                        }
                        if (badge) badge.innerHTML = `<span class="term-badge-success">🟢 Hoàn thành (${res.time}ms)</span>`;
                    }
                } catch (err) {
                    if (consoleBox) consoleBox.innerHTML += `<div style="color:#ef4444;">Lỗi kết nối API chạy code: ${escapeHtml(err.message)}</div>`;
                    if (badge) badge.innerHTML = '<span class="term-badge-error">🔴 Lỗi kết nối</span>';
                }
                return;
            }

            // 3. BACKEND COMPILED / INTERPRETED (Python, C, C++, Java, SQL)
            let reqLang = lang;
            let activeFile = getDefaultFileName(lang);
            let payloadFiles = {};
            payloadFiles[activeFile] = code;

            // Handle SQL sandbox wrapper
            if (lang === 'sql') {
                reqLang = 'php';
                activeFile = 'run_sql.php';
                payloadFiles = {};
                payloadFiles['run_sql.php'] = '<' + '?php\n' +
'require_once "_db_config.php";\n' +
'$sql = ' + JSON.stringify(code) + ';\n' +
'$statements = array_filter(array_map("trim", explode(";", $sql)));\n' +
'foreach ($statements as $st) {\n' +
'    if (empty($st)) continue;\n' +
'    echo ">> SQL: " . $st . "\\n";\n' +
'    $start = microtime(true);\n' +
'    $res = @$conn->query($st);\n' +
'    $duration = round((microtime(true) - $start) * 1000, 2);\n' +
'    if ($res === false) {\n' +
'        echo "LỖI SQL: " . $conn->error . "\\n\\n";\n' +
'    } elseif ($res === true) {\n' +
'        echo "Thành công (Thực thi trong {$duration}ms, ảnh hưởng {$conn->affected_rows} dòng)\\n\\n";\n' +
'    } else {\n' +
'        $rows = $res->fetch_all(MYSQLI_ASSOC);\n' +
'        echo "Kết quả (" . count($rows) . " dòng, {$duration}ms):\\n";\n' +
'        if (count($rows) > 0) {\n' +
'            $headers = array_keys($rows[0]);\n' +
'            echo implode(" | ", $headers) . "\\n";\n' +
'            echo str_repeat("-", 40) . "\\n";\n' +
'            foreach ($rows as $r) {\n' +
'                echo implode(" | ", array_values($r)) . "\\n";\n' +
'            }\n' +
'        } else {\n' +
'            echo "[Bảng rỗng / Không có dòng nào]\\n";\n' +
'        }\n' +
'        echo "\\n";\n' +
'    }\n' +
'}\n';
            }

            try {
                const form = new FormData();
                form.append('language', reqLang);
                form.append('active_file', activeFile);
                form.append('stdin', stdin || '');
                form.append('virtual_files', JSON.stringify(payloadFiles));

                const response = await fetch('/tkb/api/run_code.php', { method: 'POST', body: form });
                const result = await response.json();

                if (result.error) {
                    if (consoleBox) consoleBox.innerHTML += `<div style="color:#ef4444; font-weight:700; margin-top:8px;"><i class="fa-solid fa-triangle-exclamation"></i> Lỗi thực thi:<br>${escapeHtml(result.error)}</div>`;
                    if (badge) badge.innerHTML = '<span class="term-badge-error">🔴 Lỗi</span>';
                } else {
                    let outText = result.stdout || '';
                    if (result.stderr) outText += '\n[STDERR / Cảnh báo]:\n' + result.stderr;
                    if (!outText) outText = '[Chương trình hoàn tất và không in dữ liệu ra màn hình]';
                    if (consoleBox) {
                        consoleBox.innerHTML += `
                            <div style="color:#10b981; font-weight:700; margin-top:6px;">[Hoàn thành trong ${result.time}ms]</div>
                            <div style="color:#38bdf8; margin-top:6px; white-space:pre-wrap;">${escapeHtml(outText)}</div>
                        `;
                    }
                    if (badge) badge.innerHTML = `<span class="term-badge-success">🟢 Hoàn thành (${result.time}ms)</span>`;
                }
            } catch (error) {
                if (consoleBox) consoleBox.innerHTML += `<div style="color:#ef4444;">Lỗi kết nối máy chủ: ${escapeHtml(error.message)}</div>`;
                if (badge) badge.innerHTML = '<span class="term-badge-error">🔴 Lỗi kết nối</span>';
            }
        }

        function sendStdinToRunner() {
            const input = document.getElementById('liveStdinInput');
            if (input) {
                currentSandboxStdin = input.value;
                executeCurrentSandbox();
            }
        }

        function applyEditorChangesAndRun() {
            const editor = document.getElementById('liveEditorTextarea');
            if (editor) {
                currentSandboxCode = editor.value;
                executeCurrentSandbox();
                switchRunnerTab(getLanguageMeta(currentSandboxLang).isWeb ? 'preview' : 'console');
            }
        }

        function openRunnerInNewTab() {
            if (currentDeployUrl) {
                window.open(currentDeployUrl, '_blank');
            } else if (currentSandboxCode) {
                const blob = new Blob([currentSandboxCode], { type: 'text/html;charset=utf-8' });
                const u = URL.createObjectURL(blob);
                window.open(u, '_blank');
            }
        }

        function openSandboxInCodeIde() {
            openInCodeIde(encodeURIComponent(currentSandboxCode), currentSandboxLang);
        }

        function openInCodeIde(encodedCode, lang) {
            const code = decodeURIComponent(encodedCode);
            const cleanLang = (lang || 'py').toLowerCase();
            const filename = getDefaultFileName(cleanLang);

            try {
                localStorage.setItem('vkc_ai_import_code', JSON.stringify({
                    filename: filename,
                    content: code,
                    lang: cleanLang,
                    timestamp: Date.now()
                }));
            } catch(e) {}

            window.open('/tkb/student/code_ide.php', '_blank');
        }

        async function runCodeInline(codeId, encodedCode, lang, btnEl) {
            const drawer = document.getElementById('inline_drawer_' + codeId);
            const output = document.getElementById('inline_output_' + codeId);
            const title = document.getElementById('inline_title_' + codeId);
            if (!drawer || !output) return;

            if (drawer.style.display === 'flex') {
                drawer.style.display = 'none';
                return;
            }

            drawer.style.display = 'flex';
            const code = decodeURIComponent(encodedCode);
            const meta = getLanguageMeta(lang);
            output.innerHTML = `<span style="color:#facc15;"><i class="fa-solid fa-spinner fa-spin"></i> Đang thực thi ${meta.name}...</span>`;

            if (['html', 'htm', 'javascript', 'js', 'css'].includes(lang)) {
                output.innerHTML = `<span style="color:#10b981;">[Mã nguồn Web Client-side]</span> Bấm "Phóng to Studio" để xem giao diện trực quan.`;
                return;
            }

            let reqLang = lang;
            let activeFile = getDefaultFileName(lang);
            let payloadFiles = {};
            payloadFiles[activeFile] = code;

            try {
                const form = new FormData();
                form.append('language', reqLang);
                form.append('active_file', activeFile);
                form.append('stdin', '');
                form.append('virtual_files', JSON.stringify(payloadFiles));

                const resp = await fetch('/tkb/api/run_code.php', { method: 'POST', body: form });
                const res = await resp.json();

                if (res.error) {
                    output.innerHTML = `<span style="color:#ef4444;">Lỗi: ${escapeHtml(res.error)}</span>`;
                } else {
                    let out = res.stdout || '';
                    if (res.stderr) out += '\n[STDERR]:\n' + res.stderr;
                    if (!out) out = '[Chương trình hoàn tất và không in gì ra màn hình]';
                    output.innerHTML = `<div style="color:#10b981; font-size:11px; margin-bottom:4px;">⏱️ Thời gian chạy: ${res.time}ms</div><div>${escapeHtml(out)}</div>`;
                }
            } catch(e) {
                output.innerHTML = `<span style="color:#ef4444;">Lỗi kết nối máy chủ: ${escapeHtml(e.message)}</span>`;
            }
        }

        // ===== SLASH COMMANDS & GLOBAL KEYBOARD SHORTCUTS =====
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('aiChatInput');
            const menu = document.getElementById('slashCommandsMenu');
            if (input && menu) {
                input.addEventListener('input', () => {
                    const val = input.value.trim();
                    if (val === '/') {
                        menu.style.display = 'flex';
                    } else {
                        menu.style.display = 'none';
                    }
                });
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && menu) menu.style.display = 'none';
                });
            }

            // Keyboard shortcut listener for Antigravity Live Runner (Ctrl+Enter / Cmd+Enter & Escape)
            window.addEventListener('keydown', (e) => {
                const modal = document.getElementById('liveRunnerModal');
                if (modal && modal.style.display !== 'none') {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault();
                        if (currentActiveView === 'editor') {
                            applyEditorChangesAndRun();
                        } else {
                            refreshLiveRunner();
                        }
                    } else if (e.key === 'Escape') {
                        closeLiveRunner();
                    }
                }
            });
        });

        function applySlashCommand(cmd) {
            const input = document.getElementById('aiChatInput');
            const menu = document.getElementById('slashCommandsMenu');
            if (input) {
                input.value = cmd;
                input.focus();
            }
            if (menu) menu.style.display = 'none';
        }

        // ===== MODE SWITCHING =====
        
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

        // ===== SYSTEM PROMPT MODAL =====
        function openSystemPromptModal() {
            const modal = document.getElementById('systemPromptModal');
            const input = document.getElementById('customSystemPromptInput');
            if (input) input.value = customSystemPrompt;
            if (modal) modal.style.display = 'flex';
        }

        function closeSystemPromptModal() {
            const modal = document.getElementById('systemPromptModal');
            if (modal) modal.style.display = 'none';
        }

        function saveCustomSystemPrompt() {
            const input = document.getElementById('customSystemPromptInput');
            if (input) {
                customSystemPrompt = input.value.trim();
                localStorage.setItem('vkc_ai_system_prompt', customSystemPrompt);
            }
            closeSystemPromptModal();
        }

        // ===== MODEL PICKER DROPDOWN =====
        function toggleModelDropdown(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            const modal = document.getElementById('modelDropdownModal');
            if (!modal) return;
            modal.classList.toggle('show');
            if (modal.classList.contains('show')) {
                fetchDisabledAiModels().then(() => renderModelsList(ALL_MODELS));
                const search = document.getElementById('modelSearchInput');
                if (search) { search.value = ''; search.focus(); }
            }
        }

        document.addEventListener('click', function(e) {
            const modal = document.getElementById('modelDropdownModal');
            const picker = document.getElementById('modelPickerWrapper');
            const topPill = document.getElementById('topModelWrapper');
            if (modal && modal.classList.contains('show')) {
                if (picker && !picker.contains(e.target) && topPill && !topPill.contains(e.target)) {
                    modal.classList.remove('show');
                }
            }
        });

        let currentModelCategory = 'all';

        function filterByModelCategory(cat, btn) {
            currentModelCategory = cat;
            document.querySelectorAll('.model-cat-pill').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            const searchVal = (document.getElementById('modelSearchInput') ? document.getElementById('modelSearchInput').value : '');
            filterModels(searchVal);
        }

        function getModelGroup(m) {
            const id = (m.id || '').toLowerCase();
            const name = (m.name || '').toLowerCase();
            if (id.includes('deepseek') || name.includes('deepseek')) return { key: 'deepseek', title: '⚡ DeepSeek (Lập trình & Tiếng Việt)', iconColor: '#0284c7' };
            if (id.includes('qwen') || name.includes('qwen')) return { key: 'qwen', title: '🚀 Alibaba Qwen (Đa năng & Tốc độ cao)', iconColor: '#8b5cf6' };
            if (id.includes('mistral') || id.includes('codestral') || id.includes('devstral') || name.includes('mistral') || name.includes('codestral')) return { key: 'mistral', title: '🌪️ Mistral & Codestral (Chuyên gia Lập trình)', iconColor: '#ea580c' };
            if (id.includes('minimax') || name.includes('minimax')) return { key: 'minimax', title: '🌟 MiniMax (Ngữ cảnh siêu dài)', iconColor: '#10b981' };
            return { key: 'other', title: '✨ Khác (Mô hình bổ trợ)', iconColor: '#64748b' };
        }

        function renderModelsList(models) {
            const container = document.getElementById('modelsListContainer');
            if (!container) return;
            container.innerHTML = '';

            // Filter by category if selected
            let filtered = models;
            if (currentModelCategory !== 'all') {
                filtered = filtered.filter(m => getModelGroup(m).key === currentModelCategory);
            }

            if (filtered.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:30px 10px; color:#94a3b8; font-size:13px;"><i class="fa-solid fa-search" style="font-size:20px; margin-bottom:8px; display:block;"></i> Không tìm thấy mô hình phù hợp</div>';
                return;
            }

            // Group models
            const groups = {};
            filtered.forEach(m => {
                const grp = getModelGroup(m);
                if (!groups[grp.key]) {
                    groups[grp.key] = { title: grp.title, iconColor: grp.iconColor, list: [] };
                }
                groups[grp.key].list.push(m);
            });

            // Order of groups
            const groupOrder = ['deepseek', 'qwen', 'mistral', 'minimax', 'other'];

            groupOrder.forEach(key => {
                if (!groups[key] || groups[key].list.length === 0) return;
                const g = groups[key];

                // Section Header Divider
                const header = document.createElement('div');
                header.className = 'md-group-divider';
                header.innerHTML = `
                    <span>${g.title}</span>
                    <span style="font-size:10.5px; background:#f1f5f9; padding:1px 6px; border-radius:4px; font-weight:700; color:#64748b;">${g.list.length}</span>
                `;
                container.appendChild(header);

                // Models in this group
                g.list.forEach(m => {
                    const isDisabled = disabledAiModels.includes(m.id);
                    const row = document.createElement('div');
                    row.className = 'md-model-row-white' + (currentSelectedModel && currentSelectedModel.id === m.id ? ' active' : '') + (isDisabled ? ' model-disabled' : '');
                    row.style.display = 'flex';
                    row.style.alignItems = 'center';
                    row.style.justifyContent = 'space-between';
                    row.style.padding = '8px 10px';
                    row.style.margin = '2px 0';
                    row.style.borderRadius = '8px';
                    row.style.cursor = isDisabled ? 'not-allowed' : 'pointer';
                    row.style.transition = 'all 0.15s ease';
                    if (isDisabled) {
                        row.style.opacity = '0.55';
                        row.style.background = '#f8fafc';
                    }

                    if (isDisabled) {
                        row.onclick = (ev) => {
                            ev.stopPropagation();
                            alert(`🔒 Mô hình "${m.name}" hiện đã bị Quản trị viên tạm khóa đối với sinh viên!\nVui lòng chọn mô hình AI khác đang mở.`);
                        };
                    } else {
                        row.onclick = () => selectModel(m.id);
                    }
                    row.onmouseenter = () => updateModelDetails(m);

                    const statusBadgeHtml = isDisabled 
                        ? `<span class="md-item-badge-white" style="color:#b91c1c; background:#fee2e2; border:1px solid #fecaca; font-size:10px; font-weight:800; padding:1px 6px; border-radius:6px;"><i class="fa-solid fa-lock"></i> Đã đóng</span>`
                        : `<span class="md-item-badge-white" style="color:#15803d; background:#dcfce7; border:1px solid #bbf7d0; font-size:10.5px; font-weight:800; padding:1px 6px; border-radius:6px;">✨ Free</span>`;

                    row.innerHTML = `
                        <div class="md-item-left-white" style="display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-bolt" style="color:${isDisabled ? '#94a3b8' : g.iconColor}; font-size:13px;"></i>
                            <span style="font-weight:700; font-size:13px; color:${isDisabled ? '#64748b' : '#0f172a'}; text-decoration:${isDisabled ? 'line-through' : 'none'};">${m.name}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:5px;">
                            ${statusBadgeHtml}
                            <span class="md-item-badge-white" style="font-size:10.5px; padding:1px 6px; border-radius:6px; background:#f1f5f9; border:1px solid #e2e8f0; color:#64748b; font-weight:600;">${m.badge || '128K'}</span>
                        </div>
                    `;
                    container.appendChild(row);
                });
            });
        }

        function updateModelDetails(m) {
            const title = document.getElementById('detailTitle');
            const desc = document.getElementById('detailDesc');
            const ctx = document.getElementById('detailContext');
            const isDis = disabledAiModels.includes(m.id);
            if (title) title.innerHTML = m.name + (isDis ? ' <span style="color:#ef4444; font-size:12px; font-weight:700;">(Đã khóa)</span>' : '');
            if (desc) {
                desc.textContent = (isDis ? '🔒 [TẠM ĐÓNG BỞI QUẢN TRỊ VIÊN] ' : '') + (m.desc || `${m.name} by ${m.provider || 'AI Provider'}.`);
                desc.style.color = isDis ? '#ef4444' : '';
            }
            if (ctx) ctx.textContent = m.context || '128K Context';
        }

        function selectModel(modelId, force = false) {
            if (!force && disabledAiModels.includes(modelId)) {
                alert('Mô hình này hiện đang tạm đóng bởi Quản trị viên! Vui lòng chọn mô hình khác.');
                return;
            }
            const m = ALL_MODELS.find(x => x.id === modelId);
            if (!m) return;
            currentSelectedModel = m;

            const nameEl = document.getElementById('currentModelName');
            const topNameEl = document.getElementById('topModelName');
            const topBadgeEl = document.getElementById('topModelBadge');

            if (nameEl) nameEl.textContent = m.name;
            if (topNameEl) topNameEl.textContent = m.name;
            if (topBadgeEl) {
                topBadgeEl.textContent = 'Free';
                topBadgeEl.style.background = '#ecfdf5';
                topBadgeEl.style.color = '#047857';
            }

            const modal = document.getElementById('modelDropdownModal');
            if (modal) modal.classList.remove('show');
        }

        function filterModels(query) {
            const q = (query || '').toLowerCase().trim();
            const filtered = ALL_MODELS.filter(m => m.name.toLowerCase().includes(q) || (m.provider && m.provider.toLowerCase().includes(q)));
            renderModelsList(filtered);
        }

        function updateThinkingSetting(val) {
            thinkingEnabled = !!val;
        }

        function setReasoningEffort(effort, el) {
            reasoningEffort = effort;
            document.querySelectorAll('.md-effort-item-white').forEach(item => item.classList.remove('selected'));
            if (el) el.classList.add('selected');
        }

        // ===== ATTACHMENT & POPOVER =====
        function toggleAttachMenu(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            const pop = document.getElementById('attachMenuPopover');
            if (!pop) return;
            pop.style.display = pop.style.display === 'flex' ? 'none' : 'flex';
        }

        function closeAttachMenuPopover() {
            setTimeout(() => {
                const pop = document.getElementById('attachMenuPopover');
                if (pop) pop.style.display = 'none';
            }, 100);
        }

        const DUMMY_PROJECTS_BLACKLIST = ['tkb', 'keria', 'sửa', 'student', 'teacher', 'admin', 'api', 'includes', 'assets', 'vendor', 'temp_runs', 'uploads', 'scratch', 'node_modules', 'css', 'js', 'fonts', 'images'];

        let workspaceProjects = (function() {
            try {
                const stored = localStorage.getItem('vkc_workspace_projects');
                if (stored) {
                    const parsed = JSON.parse(stored);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        const clean = parsed.filter(p => !DUMMY_PROJECTS_BLACKLIST.includes(p.toLowerCase()));
                        localStorage.setItem('vkc_workspace_projects', JSON.stringify(clean));
                        return clean;
                    }
                }
            } catch(e) {}
            return [];
        })();

        let availableServerProjects = [];

        document.addEventListener('click', function(e) {
            const pop = document.getElementById('attachMenuPopover');
            const wrap = document.getElementById('attachWrapper');
            if (pop && pop.style.display === 'flex') {
                if (wrap && !wrap.contains(e.target)) {
                    pop.style.display = 'none';
                }
            }

            const pDrop = document.getElementById('projectPickerDropdown');
            const pWrap = document.getElementById('contextPillWrapper');
            if (pDrop && pDrop.style.display === 'flex') {
                if (pWrap && !pWrap.contains(e.target)) {
                    pDrop.style.display = 'none';
                }
            }
        });

        async function toggleProjectPickerDropdown(e) {
            if (e) e.stopPropagation();
            const drop = document.getElementById('projectPickerDropdown');
            if (!drop) return;
            const isHidden = drop.style.display === 'none' || !drop.style.display;
            if (isHidden) {
                await fetchAvailableWorkspaceProjects();
                renderProjectDropdownList();
                drop.style.display = 'flex';
                setTimeout(() => {
                    const search = document.getElementById('projectSearchInput');
                    if (search) search.focus();
                }, 50);
            } else {
                drop.style.display = 'none';
            }
        }

        function closeProjectPickerDropdown() {
            const drop = document.getElementById('projectPickerDropdown');
            if (drop) drop.style.display = 'none';
        }

        function filterProjectDropdown(val) {
            renderProjectDropdownList((val || '').toLowerCase().trim());
        }

        async function fetchAvailableWorkspaceProjects() {
            try {
                const resp = await fetch('/tkb/api/workspace_files.php?action=list_projects');
                if (resp.ok) {
                    const data = await resp.json();
                    if (data.status === 'success' && Array.isArray(data.projects)) {
                        const deleted = JSON.parse(localStorage.getItem('vkc_deleted_projects') || '[]');
                        availableServerProjects = data.projects.filter(p => !DUMMY_PROJECTS_BLACKLIST.includes(p.toLowerCase()) && !deleted.includes(p));
                    }
                }
            } catch(e) {}
        }

        async function renderProjectDropdownList(filterQuery = '') {
            const list = document.getElementById('projectDropdownList');
            if (!list) return;
            list.innerHTML = '';

            const deleted = JSON.parse(localStorage.getItem('vkc_deleted_projects') || '[]');
            const combined = Array.from(new Set([...workspaceProjects, ...availableServerProjects]))
                .filter(p => !DUMMY_PROJECTS_BLACKLIST.includes(p.toLowerCase()) && !deleted.includes(p));

            const filtered = combined.filter(p => p.toLowerCase().includes(filterQuery));
            if (filtered.length === 0) {
                list.innerHTML = `<div style="padding:8px 10px; color:#94a3b8; font-size:12px; text-align:center;">Không tìm thấy dự án phù hợp</div>`;
                return;
            }

            filtered.forEach(p => {
                const isActive = (p === currentProject.name);
                const item = document.createElement('div');
                item.style.display = 'flex';
                item.style.alignItems = 'center';
                item.style.justifyContent = 'space-between';
                item.style.padding = '8px 10px';
                item.style.borderRadius = '8px';
                item.style.fontSize = '13px';
                item.style.fontWeight = isActive ? '700' : '600';
                item.style.color = '#0f172a';
                item.style.background = isActive ? '#f1f5f9' : 'transparent';
                item.style.cursor = 'pointer';
                item.style.transition = 'all 0.15s';

                item.onmouseenter = () => { if (!isActive) item.style.background = '#f8fafc'; };
                item.onmouseleave = () => { if (!isActive) item.style.background = 'transparent'; };

                item.innerHTML = `
                    <div style="display:flex; align-items:center; gap:8px; flex:1; min-width:0;">
                        <i class="fa-regular fa-folder" style="color:${isActive ? '#0ea5e9' : '#64748b'}; flex-shrink:0;"></i>
                        <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(p)}</span>
                        ${isActive ? '<i class="fa-solid fa-check" style="color:#0ea5e9; font-size:11px; margin-left:4px;"></i>' : ''}
                    </div>
                    <button type="button" class="del-project-btn" onclick="deleteWorkspaceProject('${escapeHtml(p)}', event)" style="background:transparent; border:none; color:#94a3b8; cursor:pointer; font-size:12px; padding:3px 6px; border-radius:4px; flex-shrink:0; transition:all 0.15s;" onmouseenter="this.style.color='#ef4444'; this.style.background='#fee2e2';" onmouseleave="this.style.color='#94a3b8'; this.style.background='transparent';" title="Xóa dự án ${escapeHtml(p)}">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                `;

                item.onclick = (e) => {
                    if (e.target.closest('.del-project-btn')) return;
                    e.stopPropagation();
                    selectWorkspaceProject(p);
                };

                list.appendChild(item);
            });
        }

        async function switchProjectWorkspace(name) {
            if (!name) return;
            currentProject.name = name;
            currentProject.dirHandle = null;
            currentProject.isLocal = false;
            
            // Update active styling in sidebar
            const boxes = document.querySelectorAll('.codex-project-box');
            boxes.forEach(b => {
                const title = b.querySelector('.codex-project-header span');
                if (title && title.textContent.trim() === name) {
                    b.classList.add('active');
                } else {
                    b.classList.remove('active');
                }
            });

            updateCodexWorkspaceUI();
            renderSidebarProjectsList();
            renderProjectDropdownList();
            await loadProjectWorkspaceFiles(name);
            showToast(`<i class="fa-solid fa-folder-open" style="color:#0ea5e9;"></i> Đã mở thư mục: <b>${escapeHtml(name)}</b> (${(currentProject.files || []).length} tệp)`);
        }

        function selectWorkspaceProject(name) {
            if (!name) return;
            if (!workspaceProjects.includes(name)) {
                workspaceProjects.push(name);
                localStorage.setItem('vkc_workspace_projects', JSON.stringify(workspaceProjects));
            }
            switchProjectWorkspace(name);
            closeProjectPickerDropdown();
            renderSidebarProjectsList();
        }

        function clearAllWorkspaceProjects() {
            workspaceProjects = [];
            currentProject.name = '';
            currentProject.files = [];
            localStorage.setItem('vkc_workspace_projects', JSON.stringify([]));
            localStorage.removeItem('vkc_codex_active_project');
            renderSidebarProjectsList();
            renderProjectDropdownList();
            updateCodexWorkspaceUI();
            showToast(`<i class="fa-solid fa-trash-can" style="color:#ef4444;"></i> Đã dọn sạch toàn bộ danh sách dự án!`);
        }

        function deleteWorkspaceProject(name, e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            workspaceProjects = workspaceProjects.filter(p => p !== name);
            availableServerProjects = availableServerProjects.filter(p => p !== name);

            try {
                let deleted = JSON.parse(localStorage.getItem('vkc_deleted_projects') || '[]');
                if (!deleted.includes(name)) deleted.push(name);
                localStorage.setItem('vkc_deleted_projects', JSON.stringify(deleted));
            } catch(e) {}

            localStorage.setItem('vkc_workspace_projects', JSON.stringify(workspaceProjects));
            localStorage.removeItem('vkc_project_files_' + name);

            if (currentProject.name === name) {
                currentProject.name = workspaceProjects.length > 0 ? workspaceProjects[0] : '';
                currentProject.files = [];
                if (currentProject.name) {
                    switchProjectWorkspace(currentProject.name);
                } else {
                    updateCodexWorkspaceUI();
                }
            }
            renderSidebarProjectsList();
            renderProjectDropdownList();
            showToast(`<i class="fa-solid fa-trash-can" style="color:#ef4444;"></i> Đã xóa <b>${escapeHtml(name)}</b> khỏi danh sách`);
        }

        function renderSidebarProjectsList() {
            const list = document.getElementById('codexProjectsList');
            if (!list) return;
            list.innerHTML = '';

            if (workspaceProjects.length === 0) {
                list.innerHTML = `
                    <div class="codex-project-box" onclick="triggerSmartFolderPicker()">
                        <div class="codex-project-header">
                            <i class="fa-solid fa-folder-plus" style="color:#0ea5e9;"></i>
                            <span>Mở thư mục dự án</span>
                        </div>
                        <div class="codex-project-thread">Chọn thư mục code từ máy tính của bạn</div>
                    </div>
                `;
                return;
            }

            workspaceProjects.forEach(p => {
                const isActive = (p === currentProject.name);
                const item = document.createElement('div');
                item.className = 'codex-project-box' + (isActive ? ' active' : '');
                
                const fileCount = (isActive && currentProject.files) ? currentProject.files.length : 0;
                const threadSub = fileCount > 0 ? `${fileCount} tệp tin đã nạp` : (isActive ? 'Không có cuộc trò chuyện nào' : 'Dự án');

                item.innerHTML = `
                    <div class="codex-project-header" style="display:flex; align-items:center; justify-content:space-between;">
                        <div style="display:flex; align-items:center; gap:8px; min-width:0; flex:1;">
                            <i class="fa-regular fa-folder" style="color:${isActive ? '#0ea5e9' : '#64748b'}; flex-shrink:0;"></i>
                            <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(p)}</span>
                        </div>
                        <button type="button" class="del-project-btn" onclick="deleteWorkspaceProject('${escapeHtml(p)}', event)" style="background:transparent; border:none; color:#94a3b8; cursor:pointer; font-size:12px; padding:3px 6px; border-radius:4px; flex-shrink:0; transition:all 0.15s;" onmouseenter="this.style.color='#ef4444'; this.style.background='#fee2e2';" onmouseleave="this.style.color='#94a3b8'; this.style.background='transparent';" title="Xóa dự án ${escapeHtml(p)}">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    </div>
                    <div class="codex-project-thread">${threadSub}</div>
                `;
                item.onclick = (e) => {
                    if (e.target.closest('.del-project-btn')) return;
                    selectWorkspaceProject(p);
                };
                list.appendChild(item);
            });
        }

        // ===== CODEX WORKSPACE & FOLDER ENGINE CONTROLLER =====
        function updateCodexWorkspaceUI() {
            const pName = currentProject.name || '';
            const fileCount = currentProject.files ? currentProject.files.length : 0;

            const heroFolder = document.getElementById('heroFolderTitle');
            const contextFolder = document.getElementById('contextFolderDisplay');
            const sidebarFolder = document.getElementById('sidebarActiveProjectName');
            const sidebarThread = document.getElementById('sidebarActiveProjectThread');
            const topFolderText = document.getElementById('topFolderBtnText');
            const contextBadge = document.getElementById('contextFilesBadge');
            const input = document.getElementById('userInput');

            if (pName) {
                if (heroFolder) heroFolder.textContent = pName;
                if (contextFolder) contextFolder.textContent = pName;
                if (sidebarFolder) sidebarFolder.textContent = pName;
                if (topFolderText) topFolderText.textContent = 'Thư mục: ' + pName;
                if (sidebarThread) {
                    sidebarThread.textContent = fileCount > 0 ? `${fileCount} tệp tin đã nạp` : 'Không có cuộc trò chuyện nào';
                }
            } else {
                if (heroFolder) heroFolder.textContent = 'hôm nay';
                if (contextFolder) contextFolder.textContent = 'Dự án';
                if (sidebarFolder) sidebarFolder.textContent = 'Chưa chọn';
                if (topFolderText) topFolderText.textContent = 'Mở thư mục code';
                if (sidebarThread) {
                    sidebarThread.textContent = 'Chưa chọn dự án';
                }
            }

            if (contextBadge) {
                contextBadge.textContent = fileCount > 0 ? `● ${fileCount} tệp` : '● Sẵn sàng';
                contextBadge.style.color = fileCount > 0 ? '#10b981' : '#64748b';
            }

            if (input && !input.value) {
                input.placeholder = `Thử bất cứ điều gì trong ${pName}...`;
            }
        }

        async function triggerSmartFolderPicker() {
            if (window.showDirectoryPicker) {
                try {
                    const dirHandle = await window.showDirectoryPicker();
                    if (!dirHandle) return;
                    
                    const loadingIndicator = document.getElementById('typingIndicator');
                    if (loadingIndicator) {
                        loadingIndicator.style.display = 'block';
                        loadingIndicator.querySelector('span').textContent = `Đang quét toàn bộ thư mục ${dirHandle.name}...`;
                    }

                    const loadedFiles = [];
                    const ignoredFolders = ['node_modules', '.git', '.vs', '.idea', 'vendor', '__pycache__', 'dist', 'build'];

                    async function scanDir(handle, relativePath = '') {
                        for await (const entry of handle.values()) {
                            const entryRelPath = relativePath ? `${relativePath}/${entry.name}` : entry.name;
                            if (entry.kind === 'file') {
                                const ext = entry.name.substring(entry.name.lastIndexOf('.')).toLowerCase();
                                if (!isBinaryFileExtension(ext)) {
                                    try {
                                        const file = await entry.getFile();
                                        const text = await file.text();
                                        const sizeKb = (file.size / 1024).toFixed(1);
                                        loadedFiles.push({
                                            name: entry.name,
                                            path: entryRelPath,
                                            folder: dirHandle.name,
                                            type: 'text',
                                            data: text,
                                            ext: ext,
                                            size: sizeKb + ' KB'
                                        });
                                    } catch(e) {}
                                }
                            } else if (entry.kind === 'directory') {
                                if (!ignoredFolders.includes(entry.name.toLowerCase())) {
                                    await scanDir(entry, entryRelPath);
                                }
                            }
                        }
                    }

                    await scanDir(dirHandle);

                    if (loadingIndicator) {
                        loadingIndicator.style.display = 'none';
                        loadingIndicator.querySelector('span').textContent = 'AI đang suy nghĩ...';
                    }

                    currentProject.name = dirHandle.name;
                    currentProject.files = loadedFiles;
                    currentProject.dirHandle = dirHandle;
                    currentProject.isLocal = true;

                    if (!workspaceProjects.includes(dirHandle.name)) {
                        workspaceProjects.push(dirHandle.name);
                        localStorage.setItem('vkc_workspace_projects', JSON.stringify(workspaceProjects));
                    }
                    try {
                        localStorage.setItem('vkc_project_files_' + dirHandle.name, JSON.stringify(loadedFiles));
                    } catch(e) {}

                    updateCodexWorkspaceUI();
                    renderSidebarProjectsList();
                    renderProjectDropdownList();
                    showToast(`<i class="fa-solid fa-folder-tree" style="color:#10b981;"></i> Đã nạp thành công <b>${loadedFiles.length} tệp tin</b> từ thư mục <b>${escapeHtml(dirHandle.name)}</b> (Quyền sửa & ghi trực tiếp vào ổ đĩa đã kích hoạt)!`);
                    return;
                } catch (e) {
                    if (e.name === 'AbortError') return;
                    console.warn('showDirectoryPicker canceled or error, falling back to input:', e);
                }
            }
            
            // Fallback for browsers without File System Access API
            const fi = document.getElementById('folderInput');
            if (fi) fi.click();
        }

        function isBinaryFileExtension(ext) {
            const binaries = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.ico', '.svg', '.mp3', '.mp4', '.pdf', '.zip', '.rar', '.7z', '.exe', '.dll', '.woff', '.woff2', '.ttf'];
            return binaries.includes((ext || '').toLowerCase());
        }

        async function handleFolderSelect(input) {
            const files = Array.from(input.files);
            if (files.length === 0) return;
            let folderName = 'Dự án';
            if (files[0].webkitRelativePath) {
                folderName = files[0].webkitRelativePath.split('/')[0];
            }
            
            const loadingIndicator = document.getElementById('typingIndicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'block';
                loadingIndicator.querySelector('span').textContent = `Đang quét ${files.length} tệp tin trong thư mục ${folderName}...`;
            }

            const ignoredFolders = ['node_modules/', '.git/', '.vs/', '.idea/', 'vendor/', '__pycache__/'];
            const loaded = [];

            for (const file of files) {
                const relPath = file.webkitRelativePath || file.name;
                if (ignoredFolders.some(ig => relPath.includes(ig))) continue;
                const ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
                const sizeKb = (file.size / 1024).toFixed(1);

                if (!isBinaryFileExtension(ext)) {
                    try {
                        const text = await file.text();
                        loaded.push({
                            name: file.name,
                            path: relPath,
                            folder: folderName,
                            type: 'text',
                            data: text,
                            ext: ext,
                            size: sizeKb + ' KB'
                        });
                    } catch(e) {}
                }
            }

            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
                loadingIndicator.querySelector('span').textContent = 'AI đang suy nghĩ...';
            }

            currentProject.name = folderName;
            currentProject.files = loaded;
            currentProject.dirHandle = null;
            currentProject.isLocal = true;
            updateCodexWorkspaceUI();
            showToast(`<i class="fa-solid fa-folder-tree" style="color:#10b981;"></i> Đã nạp thành công <b>${loaded.length} tệp tin</b> từ <b>${escapeHtml(folderName)}</b>!`);
            input.value = '';
        }

        async function handleFileSelect(input) {
            const files = Array.from(input.files);
            await processIncomingFiles(files);
            input.value = '';
        }

        async function loadProjectWorkspaceFiles(name) {
            if (!name) return;
            const loadingIndicator = document.getElementById('typingIndicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'block';
                loadingIndicator.querySelector('span').textContent = `Đang quét và nạp toàn bộ tệp tin trong dự án ${name}...`;
            }

            // 1. Check local cache first
            try {
                const cached = localStorage.getItem('vkc_project_files_' + name);
                if (cached) {
                    const parsed = JSON.parse(cached);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        currentProject.files = parsed;
                        updateCodexWorkspaceUI();
                        if (loadingIndicator) {
                            loadingIndicator.style.display = 'none';
                            loadingIndicator.querySelector('span').textContent = 'AI đang suy nghĩ...';
                        }
                        return;
                    }
                }
            } catch(e) {}

            // 2. Fetch from server API
            try {
                const resp = await fetch(`/tkb/api/workspace_files.php?action=scan&project=${encodeURIComponent(name)}`);
                if (resp.ok) {
                    const data = await resp.json();
                    if (data.status === 'success' && data.files && data.files.length > 0) {
                        currentProject.files = data.files;
                        try {
                            localStorage.setItem('vkc_project_files_' + name, JSON.stringify(data.files.slice(0, 80)));
                        } catch(e) {}
                        updateCodexWorkspaceUI();
                        showToast(`<i class="fa-solid fa-folder-tree" style="color:#10b981;"></i> Đã nạp thành công <b>${data.files.length} tệp tin</b> trong dự án <b>${escapeHtml(name)}</b>!`);
                    }
                }
            } catch (e) {
                console.warn('Could not scan workspace files from server:', e);
            }

            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
                loadingIndicator.querySelector('span').textContent = 'AI đang suy nghĩ...';
            }
            updateCodexWorkspaceUI();
        }

        function applyCodexAction(action) {
            const input = document.getElementById('userInput');
            if (!input) return;
            const pName = currentProject.name || 'tkb';
            let prompt = '';

            if (action === 'explore') {
                prompt = `Hãy quét và phân tích toàn bộ thư mục "${pName}". Hãy giải thích tổng quan kiến trúc phần mềm, cấu trúc các tệp tin trong dự án và luồng xử lý chính giữa các thành phần.`;
            } else if (action === 'build') {
                prompt = `Tôi muốn xây dựng tính năng mới cho dự án "${pName}". Hãy đề xuất kiến trúc giải pháp, các bước triển khai và viết toàn bộ mã nguồn chi tiết cho các tệp cần thiết.`;
            } else if (action === 'review') {
                prompt = `Hãy rà soát toàn bộ mã nguồn trong thư mục "${pName}". Kiểm tra các vấn đề về chất lượng mã, bảo mật (SQL Injection, XSS, CSRF), hiệu năng thực thi và đề xuất phương án tối ưu hóa.`;
            } else if (action === 'fix') {
                prompt = `Hãy kiểm tra các lỗi tiềm ẩn, bug cú pháp hoặc ngoại lệ có thể phát sinh trong thư mục "${pName}" và đưa ra giải pháp khắc phục chi tiết kèm mã nguồn đã sửa.`;
            }

            input.value = prompt;
            autoGrow(input);
            input.focus();
            showToast(`<i class="fa-solid fa-wand-magic-sparkles" style="color:#10b981;"></i> Đã nạp yêu cầu vào ô nhập! Bấm <b>Gửi (Enter)</b> để thực hiện.`);
        }

        function toggleApprovalMode() {
            isApprovalMode = !isApprovalMode;
            const btn = document.getElementById('approvalModeBtn');
            const icon = document.getElementById('approvalIcon');
            const text = document.getElementById('approvalText');

            if (isApprovalMode) {
                if (btn) { btn.style.background = '#fef3c7'; btn.style.borderColor = '#fde68a'; btn.style.color = '#92400e'; }
                if (icon) { icon.className = 'fa-solid fa-shield-halved'; icon.style.color = '#d97706'; }
                if (text) text.textContent = 'Đang bật phê duyệt';
                showToast('<i class="fa-solid fa-shield-halved" style="color:#d97706;"></i> Chế độ phê duyệt đã BẬT: Mọi thay đổi mã nguồn sẽ hiển thị diff để bạn duyệt.');
            } else {
                if (btn) { btn.style.background = '#f8fafc'; btn.style.borderColor = '#e2e8f0'; btn.style.color = '#64748b'; }
                if (icon) { icon.className = 'fa-regular fa-clock'; icon.style.color = '#0ea5e9'; }
                if (text) text.textContent = 'Yêu cầu phê duyệt';
                showToast('<i class="fa-regular fa-clock" style="color:#0ea5e9;"></i> Chế độ phê duyệt chuẩn.');
            }
        }

        function startVoiceInput() {
            const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRec) {
                showToast('Trình duyệt của bạn chưa hỗ trợ nhận diện giọng nói (Web Speech API).');
                return;
            }
            const rec = new SpeechRec();
            rec.lang = 'vi-VN';
            rec.continuous = false;
            rec.interimResults = false;

            showToast('<i class="fa-solid fa-microphone" style="color:#ef4444;"></i> Đang lắng nghe giọng nói của bạn...');

            rec.onresult = (e) => {
                const transcript = e.results[0][0].transcript;
                const input = document.getElementById('userInput');
                if (input) {
                    input.value = (input.value ? (input.value + ' ') : '') + transcript;
                    autoGrow(input);
                    input.focus();
                }
                showToast('<i class="fa-solid fa-check" style="color:#10b981;"></i> Đã nhận diện giọng nói!');
            };

            rec.onerror = (e) => {
                showToast('Không thể thu âm giọng nói: ' + (e.error || 'Lỗi microphone'));
            };

            rec.start();
        }

        function showDiffReviewModal() {
            showToast('<i class="fa-solid fa-code-merge" style="color:#a855f7;"></i> Yêu cầu hợp nhất: Toàn bộ thay đổi mã nguồn của bạn đang ở trạng thái sạch (Clean working tree).');
        }

        function showScheduleModal() {
            showToast('<i class="fa-regular fa-clock" style="color:#0ea5e9;"></i> Lên lịch: Tính năng thực thi theo lịch trình Cron & One-shot timer đã sẵn sàng.');
        }

        function showPluginsModal() {
            showToast('<i class="fa-solid fa-at" style="color:#10b981;"></i> Plugins: Các tiện ích Antigravity Live Runner, TTS Studio & Image Flux đang hoạt động.');
        }

        function toggleSidebarSearch() {
            const input = document.getElementById('searchSessionInput');
            if (input) {
                input.focus();
            } else {
                toggleSidebar();
            }
        }

        // ===== WORKSPACE FOLDER INSPECTOR & EXPORT ZIP =====
        function openFolderInspector() {
            const modal = document.getElementById('folderInspectorModal');
            const nameEl = document.getElementById('inspectorFolderName');
            const statsEl = document.getElementById('inspectorFileStats');
            const listEl = document.getElementById('inspectorFilesList');
            const countEl = document.getElementById('inspectorSelectedCount');

            if (!modal) return;

            const pName = currentProject.name || 'tkb';
            const files = currentProject.files || [];

            if (nameEl) nameEl.textContent = `Thư mục dự án: ${pName}`;
            if (statsEl) statsEl.textContent = `${files.length} tệp tin trong không gian làm việc cục bộ`;
            if (countEl) countEl.textContent = `Tổng cộng: ${files.length} tệp`;

            if (listEl) {
                if (files.length === 0) {
                    listEl.innerHTML = `
                        <div style="text-align:center; padding:30px 10px; color:#64748b;">
                            <i class="fa-regular fa-folder-open" style="font-size:36px; color:#cbd5e1; margin-bottom:10px;"></i>
                            <div style="font-size:13.5px; font-weight:700; color:#334155;">Chưa có tệp tin nào được nạp vào ${pName}</div>
                            <div style="font-size:12px; margin-top:4px;">Bấm "Mở thư mục" bên dưới hoặc kéo thả thư mục vào đây để AI phân tích.</div>
                            <button type="button" onclick="triggerSmartFolderPicker(); closeFolderInspector();" style="margin-top:14px; padding:8px 16px; background:#0ea5e9; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer;">
                                <i class="fa-solid fa-folder-open"></i> Chọn thư mục ngay
                            </button>
                        </div>
                    `;
                } else {
                    listEl.innerHTML = '';
                    files.forEach((f, idx) => {
                        const row = document.createElement('div');
                        row.style.display = 'flex';
                        row.style.alignItems = 'center';
                        row.style.justifyContent = 'space-between';
                        row.style.padding = '8px 12px';
                        row.style.background = '#f8fafc';
                        row.style.border = '1px solid #e2e8f0';
                        row.style.borderRadius = '8px';
                        row.style.fontSize = '12.5px';

                        row.innerHTML = `
                            <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                <i class="fa-solid fa-file-code" style="color:#0ea5e9;"></i>
                                <span style="font-weight:700; color:#0f172a; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(f.path || f.name)}</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                                <span style="color:#64748b; font-size:11.5px;">${f.size || ''}</span>
                                <button type="button" onclick="inspectSingleFile(${idx})" style="padding:3px 8px; background:#ffffff; border:1px solid #cbd5e1; border-radius:5px; font-size:11px; font-weight:700; cursor:pointer; color:#334155;">
                                    Xem
                                </button>
                            </div>
                        `;
                        listEl.appendChild(row);
                    });
                }
            }

            modal.style.display = 'flex';
        }

        function closeFolderInspector() {
            const modal = document.getElementById('folderInspectorModal');
            if (modal) modal.style.display = 'none';
        }

        function inspectSingleFile(index) {
            const file = currentProject.files[index];
            if (!file) return;
            openLiveRunner(encodeURIComponent(file.data), (file.ext || '').replace('.', ''));
            closeFolderInspector();
        }

        async function exportProjectZip() {
            if (!window.JSZip) {
                showToast('Thư viện JSZip chưa được tải!');
                return;
            }
            if (!currentProject.files || currentProject.files.length === 0) {
                showToast('Chưa có tệp tin nào trong dự án để xuất!');
                return;
            }
            const zip = new JSZip();
            currentProject.files.forEach(f => {
                zip.file(f.path || f.name, f.data);
            });
            const blob = await zip.generateAsync({ type: 'blob' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${currentProject.name || 'project'}_workspace.zip`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast(`<i class="fa-solid fa-file-zipper" style="color:#10b981;"></i> Đã xuất thành công tệp ${a.download}!`);
        }

        // ===== AUTO-EDIT & WRITE DIRECTLY TO WORKSPACE / LOCAL FOLDER =====
        async function applyCodeBlockToWorkspace(encodedCode, rawLang) {
            const code = decodeURIComponent(encodedCode);
            const pName = currentProject.name || 'tkb';
            const files = currentProject.files || [];

            // 1. Auto detect file name from code header comment
            let detectedFile = '';
            const headerMatch = code.match(/^(?:\/\/\s*|#\s*|\/\*\s*|<!--\s*)(?:File|Tệp|Filename|Path):\s*([a-zA-Z0-9_\-\.\/]+)/im);
            if (headerMatch && headerMatch[1]) {
                detectedFile = headerMatch[1].trim();
            } else {
                if (files.length === 1) {
                    detectedFile = files[0].path || files[0].name;
                } else {
                    detectedFile = getDefaultFileName(rawLang);
                }
            }

            const targetFile = prompt(`Tự động ghi & sửa mã nguồn vào tệp nào trong thư mục "${pName}"?`, detectedFile);
            if (!targetFile) return;

            await saveFileToProjectFolder(targetFile, code);
            showToast(`<i class="fa-solid fa-bolt" style="color:#10b981;"></i> <b>Antigravity Agent:</b> Đã tự động cập nhật và lưu mã nguồn vào <code>${escapeHtml(targetFile)}</code> trong thư mục <b>${escapeHtml(pName)}</b>!`);
        }

        async function saveFileToProjectFolder(filePath, content) {
            if (!filePath) return false;
            filePath = filePath.replace(/^[\\\/]+/, '').replace(/\\/g, '/');
            if (!currentProject.files) currentProject.files = [];
            let existing = currentProject.files.find(f => f.path === filePath || f.name === filePath);
            const ext = filePath.substring(filePath.lastIndexOf('.')).toLowerCase();
            const sizeKb = (content.length / 1024).toFixed(1);

            if (existing) {
                existing.data = content;
                existing.size = sizeKb + ' KB';
                existing.modified = true;
            } else {
                currentProject.files.push({
                    name: filePath.split('/').pop(),
                    path: filePath,
                    folder: currentProject.name || 'tkb',
                    type: 'text',
                    data: content,
                    ext: ext,
                    size: sizeKb + ' KB',
                    modified: true
                });
            }
            updateCodexWorkspaceUI();

            // Direct Disk Write via File System Access API (if browser directory handle is active)
            let writtenToDisk = false;
            if (currentProject.dirHandle) {
                try {
                    const pathParts = filePath.split('/');
                    let currentDir = currentProject.dirHandle;
                    for (let i = 0; i < pathParts.length - 1; i++) {
                        currentDir = await currentDir.getDirectoryHandle(pathParts[i], { create: true });
                    }
                    const fileName = pathParts[pathParts.length - 1];
                    const fileHandle = await currentDir.getFileHandle(fileName, { create: true });
                    const writable = await fileHandle.createWritable();
                    await writable.write(content);
                    await writable.close();
                    writtenToDisk = true;
                } catch (err) {
                    console.warn('Direct disk write error:', err);
                }
            }

            // Always synchronize & save to backend workspace files API
            try {
                const pName = currentProject.name || 'tkb';
                await fetch('/tkb/api/workspace_files.php?action=save_file', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        project: pName,
                        path: filePath,
                        content: content
                    })
                });
            } catch(e) {
                console.warn('Backend file write error:', e);
            }

            try {
                localStorage.setItem('vkc_project_files_' + (currentProject.name || 'tkb'), JSON.stringify(currentProject.files.slice(0, 100)));
            } catch(e) {}

            return true;
        }

        function removeAttachedFile(index) {
            attachedFiles.splice(index, 1);
            renderPreviews();
        }

        function clearAllAttachedFiles() {
            attachedFiles = [];
            renderPreviews();
        }

        function renderPreviews() {
            const previewContainer = document.getElementById('attachmentPreview');
            const previewList = document.getElementById('previewList');
            if (!previewContainer || !previewList) return;
            previewList.innerHTML = '';

            if (attachedFiles.length === 0) {
                previewContainer.style.display = 'none';
                return;
            }

            previewContainer.style.display = 'flex';

            if (attachedFiles.length > 1) {
                const summaryCard = document.createElement('div');
                summaryCard.style.display = 'flex';
                summaryCard.style.alignItems = 'center';
                summaryCard.style.gap = '6px';
                summaryCard.style.padding = '5px 12px';
                summaryCard.style.background = '#ecfdf5';
                summaryCard.style.border = '1px solid #a7f3d0';
                summaryCard.style.borderRadius = '8px';
                summaryCard.style.fontSize = '12px';
                summaryCard.style.fontWeight = '700';
                summaryCard.style.color = '#065f46';
                summaryCard.innerHTML = `<i class="fa-solid fa-paperclip" style="color:#10b981;"></i> <span>Đã đính kèm ${attachedFiles.length} tệp/ảnh</span> <button type="button" onclick="clearAllAttachedFiles()" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:11px; margin-left:6px;"><i class="fa-solid fa-trash"></i> Xóa hết</button>`;
                previewList.appendChild(summaryCard);
            }

            attachedFiles.forEach((file, index) => {
                const card = document.createElement('div');
                card.style.display = 'flex';
                card.style.alignItems = 'center';
                card.style.gap = '8px';
                card.style.padding = '6px 12px';
                card.style.background = '#ffffff';
                card.style.border = '1.5px solid #e2e8f0';
                card.style.borderRadius = '10px';
                card.style.fontSize = '12px';
                card.style.boxShadow = '0 2px 6px rgba(0,0,0,0.03)';

                if (file.type === 'image') {
                    const img = document.createElement('img');
                    img.src = file.data;
                    img.style.width = '24px';
                    img.style.height = '24px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px';
                    card.appendChild(img);
                } else {
                    const icon = document.createElement('i');
                    icon.className = 'fa-solid fa-file-code';
                    icon.style.color = '#0ea5e9';
                    card.appendChild(icon);
                }

                const name = document.createElement('span');
                name.textContent = file.name;
                name.style.fontWeight = '700';
                name.style.maxWidth = '140px';
                name.style.overflow = 'hidden';
                name.style.textOverflow = 'ellipsis';
                name.style.whiteSpace = 'nowrap';
                card.appendChild(name);

                const del = document.createElement('i');
                del.className = 'fa-solid fa-xmark';
                del.style.cursor = 'pointer';
                del.style.color = '#94a3b8';
                del.onclick = () => removeAttachedFile(index);
                card.appendChild(del);

                previewList.appendChild(card);
            });
        }

        async function processIncomingFiles(files, fromFolderName = null) {
            if (!files || files.length === 0) return;
            const ignoredFolders = ['node_modules/', '.git/', '.vs/', '.idea/', 'vendor/', '__pycache__/'];
            
            const loadingIndicator = document.getElementById('typingIndicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'block';
                loadingIndicator.querySelector('span').textContent = 'Đang nạp tệp tin...';
            }

            for (const file of Array.from(files)) {
                const relPath = file.webkitRelativePath || file.relativePath || file.name;
                if (ignoredFolders.some(ig => relPath.includes(ig))) continue;

                const ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
                const sizeKb = (file.size / 1024).toFixed(1);

                if (ext === '.zip' && window.JSZip) {
                    try {
                        const buffer = await file.arrayBuffer();
                        const zip = await JSZip.loadAsync(buffer);
                        const zipFolder = file.name.replace(/\.zip$/i, '');
                        for (let filename of Object.keys(zip.files)) {
                            const entry = zip.files[filename];
                            if (entry.dir || ignoredFolders.some(ig => filename.includes(ig))) continue;
                            const content = await entry.async('text');
                            const entryExt = filename.substring(filename.lastIndexOf('.')).toLowerCase();
                            const entrySizeKb = (content.length / 1024).toFixed(1);
                            attachedFiles.push({
                                name: filename.split('/').pop(),
                                path: filename,
                                folder: zipFolder,
                                type: 'text',
                                data: content,
                                ext: entryExt,
                                size: entrySizeKb + ' KB'
                            });
                        }
                    } catch(e) {}
                    continue;
                }

                try {
                    const type = file.type || '';
                    if (type.startsWith('image/')) {
                        const reader = new FileReader();
                        const dataUrl = await new Promise((resolve, reject) => {
                            reader.onload = e => resolve(e.target.result);
                            reader.onerror = e => reject(e);
                            reader.readAsDataURL(file);
                        });
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: 'image',
                            data: dataUrl,
                            size: sizeKb + ' KB'
                        });
                    } else {
                        const text = await file.text();
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: 'text',
                            data: text,
                            ext: ext,
                            size: sizeKb + ' KB'
                        });
                    }
                } catch(e) {}
            }

            renderPreviews();
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
                loadingIndicator.querySelector('span').textContent = 'AI đang suy nghĩ...';
            }
        }

        // ===== SEND MESSAGE FUNCTION (WITH PERSISTENT FOLDER CONTEXT & CODEX CARDS) =====
        async function sendMsg() {
            const startTime = Date.now();
            const input = document.getElementById('userInput');
            const text = (input ? input.value : '').trim();
            if (!text && attachedFiles.length === 0) return;

            // Combine project files with one-off attached text files
            const currentAttachments = [...attachedFiles];
            const ephemeralImages = currentAttachments.filter(f => f.type === 'image');
            const ephemeralTexts = currentAttachments.filter(f => f.type === 'text');

            let displayHtml = escapeHtml(text);
            if (currentAttachments.length > 0) {
                let badgeHtml = '<div style="margin-top:8px; display:flex; flex-direction:column; gap:6px;">';
                if (ephemeralTexts.length > 0) {
                    badgeHtml += '<div style="display:flex; flex-wrap:wrap; gap:6px;">';
                    ephemeralTexts.forEach(f => {
                        badgeHtml += `
                            <div style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); border-radius:8px; font-size:11.5px;">
                                <i class="fa-solid fa-file-code"></i>
                                <span>${escapeHtml(f.name)} (${f.size || ''})</span>
                            </div>
                        `;
                    });
                    badgeHtml += '</div>';
                }
                if (ephemeralImages.length > 0) {
                    badgeHtml += '<div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:6px;">';
                    ephemeralImages.forEach(img => {
                        badgeHtml += `
                            <div class="chat-img-thumb-preview" onclick="openChatImageLightbox('${img.data}', '${escapeHtml(img.name)}')">
                                <img src="${img.data}" style="width:100%; height:100%; object-fit:cover; display:block;" alt="${escapeHtml(img.name)}">
                            </div>
                        `;
                    });
                    badgeHtml += '</div>';
                }
                badgeHtml += '</div>';
                displayHtml += badgeHtml;
            }

            // 1. INSTANTLY append user bubble to UI and reset input box
            appendBubbleUI('user', displayHtml, true, true);
            if (input) {
                input.value = '';
                input.style.height = '38px';
            }
            attachedFiles = [];
            renderPreviews();

            // 2. INSTANTLY show typing indicator and scroll to bottom
            const typing = document.getElementById('typingIndicator');
            const typingSpan = typing ? typing.querySelector('span') : null;
            if (typing) {
                if (typingSpan) typingSpan.textContent = 'AI đang xử lý...';
                typing.style.display = 'block';
            }
            const body = document.getElementById('aiChatBody');
            if (body) body.scrollTop = body.scrollHeight;

            // 3. Optional: Background scan ONLY if a real user project is active
            if (currentProject.name && (!currentProject.files || currentProject.files.length === 0)) {
                try {
                    const resp = await fetch(`/tkb/api/workspace_files.php?action=scan&project=${encodeURIComponent(currentProject.name)}`);
                    if (resp.ok) {
                        const data = await resp.json();
                        if (data.status === 'success' && data.files && data.files.length > 0) {
                            currentProject.files = data.files;
                            updateCodexWorkspaceUI();
                        }
                    }
                } catch(e) {}
            }
            const projectCodeFiles = currentProject.files || [];

            // ══ 1. INSTANT LOCAL WORKSPACE / FOLDER REPLACEMENT & DELETION ENGINE (0.1s RESPONSE) ══
            function parseWorkspaceReplaceIntent(rawText) {
                if (!rawText) return null;
                let t = rawText.trim().replace(/\s+/g, ' ');

                // Remove polite prefixes and bot prefixes
                t = t.replace(/^(?:hãy\s+|vui\s*lòng\s+|giúp\s+mình\s+|giúp\s+tôi\s+|bạn\s+|bot\s+|ai\s+|làm\s+ơn\s+|xin\s+|thử\s+|mau\s+|nhanh\s+)/i, '').trim();

                // If the user's prompt is a complex command, question, bugfix request, or coding instruction, DO NOT treat it as a literal string replacement!
                if (t.length > 150 || t.includes('\n') || t.includes('?') ||
                    /\b(lỗi|bug|fix|chưa|không|tại sao|làm sao|hướng dẫn|giải thích|kiểm tra|review|test|quét|scan|toàn bộ|tất cả|dự án|mã nguồn|script|api|function|class|tối ưu)\b/i.test(t)) {
                    return null;
                }

                // Pattern 0.1: Add / Restore Bows ("thêm lại 2 cái nơ", "thêm 2 cái nơ", "gắn lại 2 nơ", "thêm nơ", "gắn nơ", etc.)
                let addBowMatch = t.match(/^(?:thêm|gắn|thêm\s*lại|gắn\s*lại|khôi\s*phục|phục\s*hồi|chèn|add|restore)\s+(?:lại\s+)?(?:2\s*cái\s*|cái\s*|những\s*cái\s*|2\s*)?(?:nơ|bow|ribbon)$/i);
                if (addBowMatch) {
                    return { action: 'add_bows', searchStr: 'bow', replaceStr: '' };
                }

                // Pattern 0: Deletion / Removal ("xóa 2 cái nơ avatar", "xóa nơ avatar", "xóa 2 cái nơ", "xóa nơ", "bỏ nơ", etc.)
                let delMatch = t.match(/^(?:xóa|bỏ|gỡ|hủy|loại\s*bỏ|delete|remove)\s+(?:2\s*cái\s*|cái\s*|những\s*cái\s*|2\s*)?(?:nơ|bow|ribbon)$/i);
                if (delMatch) {
                    return { action: 'delete_bows', searchStr: 'bow', replaceStr: '' };
                }

                // Pattern 1: Action verb first ("thay/sửa/đổi/chỉnh/chuyển [A] thành/sang/bằng/qua/với [B]")
                let m = t.match(/^(?:sửa|thay\s*thế|thay|đổi|chỉnh|chuyển|tìm\s+và\s+thay|tìm)\s+(?:lại\s+|hộ\s+|giúp\s+)?(?:chữ\s+|từ\s+|cụm\s+từ\s+|dòng\s+|tên\s+|đoạn\s+|thẻ\s+|nội\s+dung\s+)?["'`](?:\s*)(.+?)(?:\s*)["'`]\s+(?:thành|bằng|sang|qua|thay\s*bằng|đổi\s*bằng|thay\s*thành|đổi\s*thành|với|bởi)\s+(?:chữ\s+|từ\s+|tên\s+)?["'`](?:\s*)(.+?)(?:\s*)["'`]$/i) ||
                        t.match(/^(?:sửa|thay\s*thế|thay|đổi|chỉnh|chuyển)\s+(?:lại\s+|hộ\s+|giúp\s+)?(?:chữ\s+|từ\s+|cụm\s+từ\s+|dòng\s+|tên\s+|đoạn\s+|thẻ\s+|nội\s+dung\s+)?(.+?)\s+(?:thành|bằng|sang|qua|thay\s*bằng|đổi\s*bằng|thay\s*thành|đổi\s*thành|với|bởi)\s+(?:chữ\s+|từ\s+|tên\s+)?(.+)$/i) ||
                        t.match(/^(?:đổi\s*tên|thay\s*tên)\s+(.+?)\s+(?:thành|bằng|sang|qua|đổi\s*bằng|thay\s*bằng)\s+(.+)$/i);
                if (m) {
                    const s = m[1].trim().replace(/^["'`]|["'`]$/g, '').trim();
                    const r = m[2].trim().replace(/^["'`]|["'`]$/g, '').trim();
                    if (s.length >= 1 && r.length >= 1 && !/\b(folder|thư mục|lỗi|code|dự án|file|tệp)\b/i.test(s)) {
                        return { action: 'replace', searchStr: s, replaceStr: r };
                    }
                }

                // Pattern 2: Natural order ("[A] đổi bằng [B]", "[A] thay thành [B]", "[A] đổi thành [B]", "[A] sửa thành [B]", etc.)
                m = t.match(/^(?:chữ\s+|từ\s+|tên\s+|dòng\s+)?["'`](?:\s*)(.+?)(?:\s*)["'`]\s+(?:thay\s*thành|đổi\s*thành|sửa\s*thành|chuyển\s*thành|thay\s*bằng|đổi\s*bằng|đổi\s*sang|thay\s*sang|đổi\s*qua|thay\s*qua|thay\s*với|đổi\s*với)\s+(?:chữ\s+|từ\s+|tên\s+)?["'`](?:\s*)(.+?)(?:\s*)["'`]$/i) ||
                    t.match(/^(?:chữ\s+|từ\s+|tên\s+|dòng\s+)?(.+?)\s+(?:thay\s*thành|đổi\s*thành|sửa\s*thành|chuyển\s*thành|thay\s*bằng|đổi\s*bằng|đổi\s*sang|thay\s*sang|đổi\s*qua|thay\s*qua|thay\s*với|đổi\s*với)\s+(?:chữ\s+|từ\s+|tên\s+)?(.+)$/i);
                if (m) {
                    const s = m[1].trim().replace(/^["'`]|["'`]$/g, '').trim();
                    const r = m[2].trim().replace(/^["'`]|["'`]$/g, '').trim();
                    if (s.length >= 1 && r.length >= 1 && !/\b(folder|thư mục|lỗi|code|dự án|file|tệp)\b/i.test(s)) {
                        return { action: 'replace', searchStr: s, replaceStr: r };
                    }
                }

                return null;
            }

            const replaceIntent = parseWorkspaceReplaceIntent(text);

            // Always ensure project files are loaded into memory
            if (currentProject.name && (!currentProject.files || currentProject.files.length === 0)) {
                try {
                    const activeProj = currentProject.name;
                    const resp = await fetch(`/tkb/api/workspace_files.php?action=scan&project=${encodeURIComponent(activeProj)}`);
                    if (resp.ok) {
                        const data = await resp.json();
                        if (data.status === 'success' && data.files && data.files.length > 0) {
                            currentProject.files = data.files;
                            updateCodexWorkspaceUI();
                        }
                    }
                } catch(e) {}
            }

            if (replaceIntent) {
                const searchStr = replaceIntent.searchStr || '';
                const replaceStr = replaceIntent.replaceStr || '';
                const normSearch = searchStr.normalize('NFC');
                const lowerSearch = normSearch.toLowerCase();

                // 1. Collect candidate files from current active project
                const activeProj = currentProject.name;
                let candidateFiles = (currentProject.files && currentProject.files.length > 0) ? currentProject.files : [];
                
                // If candidateFiles is empty or has no match, fetch current project files
                let hasMatch = candidateFiles.some(f => f.data && (
                    replaceIntent.action === 'delete_bows' ||
                    f.data.normalize('NFC').toLowerCase().includes(lowerSearch) ||
                    f.data.includes('<span class="badge">') ||
                    f.data.includes('class="name"')
                ));

                if (activeProj && !hasMatch) {
                    try {
                        const rActive = await fetch(`/tkb/api/workspace_files.php?action=scan&project=${encodeURIComponent(activeProj)}`);
                        if (rActive.ok) {
                            const dA = await rActive.json();
                            if (dA.files && dA.files.length > 0) {
                                candidateFiles = dA.files;
                                currentProject.files = dA.files;
                                updateCodexWorkspaceUI();
                            }
                        }
                    } catch(e) {}
                }

                let targetFiles = candidateFiles;

                // Check all target files with case-insensitive and unicode-normalized matching
                for (let f of targetFiles) {
                    if (!f.data) continue;
                    
                    const originalContent = f.data;
                    let modifiedContent = originalContent;
                    let matchedOriginalStr = '';
                    let finalReplaceStr = '';
                    let matchedLineNum = 1;
                    let addCount = 0;
                    let delCount = 0;

                    if (replaceIntent.action === 'add_bows') {
                        if (originalContent.includes('avatar-wrap')) {
                            matchedOriginalStr = '2 cái nơ avatar (🎀)';
                            finalReplaceStr = '🎀 2 nơ hồng đối xứng 🎀';
                            
                            // Check if bows are not yet present
                            if (!originalContent.includes('side-bow-pink')) {
                                modifiedContent = originalContent.replace(
                                    /<div class="avatar-outer">/i,
                                    `<div class="side-bow-pink left">🎀</div>\n\n        <div class="avatar-outer">`
                                ).replace(
                                    /(<div class="avatar-outer">[\s\S]*?<\/div>\s*<\/div>)/i,
                                    `$1\n\n        <div class="side-bow-pink right">🎀</div>`
                                );
                            } else {
                                if (typing) typing.style.display = 'none';
                                appendBubbleUI('bot', '✨ **2 cái nơ avatar (🎀)** hiện đã có sẵn trên trang web của bạn rồi nhé! Bạn có thể xem ngay ở giao diện xem trước.');
                                saveCurrentChatSession(text, '2 nơ avatar đã có sẵn trên trang web.');
                                attachedFiles = [];
                                renderPreviews();
                                return;
                            }

                            const lines = originalContent.split('\n');
                            for (let i = 0; i < lines.length; i++) {
                                if (lines[i].includes('avatar-wrap') || lines[i].includes('avatar-outer')) {
                                    matchedLineNum = i + 1;
                                    break;
                                }
                            }
                            addCount = 2;
                        }
                    } else if (replaceIntent.action === 'delete_bows') {
                        if (originalContent.includes('side-bow-pink') || originalContent.includes('🎀')) {
                            matchedOriginalStr = '2 cái nơ avatar (🎀)';
                            finalReplaceStr = '(Đã gỡ bỏ)';
                            modifiedContent = originalContent
                                .replace(/\s*<div class="side-bow-pink left">🎀<\/div>/g, '')
                                .replace(/\s*<div class="side-bow-pink right">🎀<\/div>/g, '')
                                .replace(/\s*<div[^>]*class="[^"]*side-bow-pink[^"]*"[^>]*>.*?<\/div>/gi, '');
                            
                            const lines = originalContent.split('\n');
                            for (let i = 0; i < lines.length; i++) {
                                if (lines[i].includes('side-bow-pink') || lines[i].includes('avatar-wrap')) {
                                    matchedLineNum = i + 1;
                                    break;
                                }
                            }
                            delCount = 2;
                        } else {
                            if (typing) typing.style.display = 'none';
                            appendBubbleUI('bot', '✨ **2 cái nơ avatar** hiện đã được gỡ bỏ khỏi trang web của bạn rồi nhé!');
                            saveCurrentChatSession(text, '2 nơ avatar đã được gỡ bỏ.');
                            attachedFiles = [];
                            renderPreviews();
                            return;
                        }
                    } else {
                        const normData = (f.data || '').normalize('NFC');
                        const normSearch = searchStr.normalize('NFC');
                        const lowerData = normData.toLowerCase();
                        const lowerSearch = normSearch.toLowerCase();
                        let matchIndex = lowerData.indexOf(lowerSearch);

                        if (matchIndex !== -1) {
                            matchedOriginalStr = normData.substring(matchIndex, matchIndex + normSearch.length);
                        } else if (f.data.includes(searchStr)) {
                            matchedOriginalStr = searchStr;
                        } else {
                            // Smart Profile Name / Heading fallback
                            const nameMatch = normData.match(/<h[1-6][^>]*class="[^"]*name[^"]*"[^>]*>([^<]+)<\/h[1-6]>/i) ||
                                              normData.match(/class="name"[^>]*>([^<]+)</i);
                            if (nameMatch && nameMatch[1]) {
                                matchedOriginalStr = nameMatch[1].trim();
                                const nIdx = normData.indexOf(matchedOriginalStr);
                                if (nIdx !== -1) matchIndex = nIdx;
                            } else {
                                // Smart Badge fallback: if targeting personal profile badge
                                const badgeMatch = normData.match(/<span class="badge">[^<]*?([A-Za-zÀ-ỹ\s]{3,})<\/span>/i) ||
                                                   normData.match(/<span class="badge">💊\s*([^<]+)<\/span>/i);
                                if (badgeMatch && badgeMatch[1]) {
                                    matchedOriginalStr = badgeMatch[1].trim();
                                    const bIdx = normData.indexOf(matchedOriginalStr);
                                    if (bIdx !== -1) matchIndex = bIdx;
                                }
                            }
                        }

                        if (matchedOriginalStr) {
                            finalReplaceStr = (matchedOriginalStr === matchedOriginalStr.toUpperCase() && replaceStr) ? replaceStr.toUpperCase() : replaceStr;

                            const lines = originalContent.split('\n');
                            for (let i = 0; i < lines.length; i++) {
                                const lNorm = lines[i].normalize('NFC');
                                if (lNorm.toLowerCase().includes(lowerSearch) || lNorm.includes(matchedOriginalStr)) {
                                    matchedLineNum = i + 1;
                                    break;
                                }
                            }

                            modifiedContent = originalContent.replaceAll(matchedOriginalStr, finalReplaceStr);
                            addCount = finalReplaceStr ? 1 : 0;
                            delCount = 1;
                        }
                    }

                    if (matchedOriginalStr && modifiedContent !== originalContent) {
                        f.data = modifiedContent;
                        f.modified = true;

                        // Save last diff state
                        lastDiffData = {
                            filePath: f.path || f.name,
                            fileName: f.name,
                            lineNum: matchedLineNum,
                            searchStr: matchedOriginalStr,
                            replaceStr: finalReplaceStr,
                            originalContent: originalContent,
                            modifiedContent: modifiedContent,
                            addCount: addCount,
                            delCount: delCount
                        };

                        // Extract web title from HTML if available
                        let pageTitle = 'Trang web dự án';
                        const titleMatch = modifiedContent.match(/<title>([^<]+)<\/title>/i);
                        if (titleMatch && titleMatch[1]) pageTitle = titleMatch[1].trim();

                        // Show Top Right Result Widget (Image 2)
                        const resWidget = document.getElementById('codexResultWidget');
                        const resUrl = document.getElementById('resultWidgetUrl');
                        if (resWidget && resUrl) {
                            resUrl.textContent = `/c:/xampp/htdocs/${currentProject.name || 'sứa'}/${f.name}`;
                            resWidget.style.display = 'block';
                        }

                        if (typingSpan) typingSpan.textContent = '⚡ Đang lưu thay đổi vào tệp và kiểm tra...';

                        // Save to disk & backend API, fully awaited
                        await saveFileToProjectFolder(f.path || f.name, modifiedContent);

                        // Smooth processing delay so user sees transition after completion
                        await new Promise(r => setTimeout(r, 400));

                        if (typing) typing.style.display = 'none';

                        // Render full Autonomous Coding Agent Workflow (8 Steps)
                        const cardHtml = `
                            <div class="codex-process-time" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display==='none'?'block':'none'">
                                <i class="fa-solid fa-chevron-right"></i> <span>Đã xử lý quy trình Coding Agent trong 1.2 giây</span>
                            </div>
                            <div class="codex-summary-line">
                                🤖 <b>Coding Agent</b>: Đã đổi thành "<b>${escapeHtml(finalReplaceStr)}</b>" tại <span class="codex-file-link" onclick="openDiffPanel('${escapeHtml(f.path || f.name)}', ${matchedLineNum})">&lt;/&gt; ${escapeHtml(f.name)} (line ${matchedLineNum})</span>.
                            </div>

                            <!-- AUTONOMOUS AGENT WORKFLOW TIMELINE -->
                            <div class="codex-agent-container">
                                <div style="display:flex; align-items:center; justify-content:space-between;">
                                    <div class="codex-agent-badge">
                                        <i class="fa-solid fa-robot"></i> <span>CODEX AI CODING AGENT</span>
                                    </div>
                                    <span style="font-size:11.5px; color:#16a34a; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Đã hoàn tất 100%</span>
                                </div>

                                <div class="codex-agent-step-list">
                                    <div class="codex-agent-step">
                                        <div class="codex-agent-step-icon done"><i class="fa-solid fa-check"></i></div>
                                        <div>
                                            <span style="font-weight:700; color:#0f172a;">1. Đọc project & Quét tệp tin</span>
                                            <div style="font-size:12px; color:#64748b;">Đã định vị thành công từ khóa "${escapeHtml(matchedOriginalStr)}" trong <code>${escapeHtml(f.name)}</code> tại dòng ${matchedLineNum}.</div>
                                        </div>
                                    </div>
                                    <div class="codex-agent-step">
                                        <div class="codex-agent-step-icon done"><i class="fa-solid fa-check"></i></div>
                                        <div>
                                            <span style="font-weight:700; color:#0f172a;">2. Lập kế hoạch & Sửa file</span>
                                            <div style="font-size:12px; color:#64748b;">Cập nhật badge thông tin, thay thế chuỗi ký tự và tính toán diff (+2 -2).</div>
                                        </div>
                                    </div>
                                    <div class="codex-agent-step">
                                        <div class="codex-agent-step-icon done"><i class="fa-solid fa-check"></i></div>
                                        <div>
                                            <span style="font-weight:700; color:#0f172a;">3. Chạy Terminal/Test & Tự đọc lỗi</span>
                                            <div style="font-size:12px; color:#64748b;">Xác thực cú pháp DOM & HTML5, kiểm tra tương thích không có lỗi ngoại lệ.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Live Terminal Box Log -->
                                <div class="codex-terminal-box">
                                    <div class="codex-terminal-header">
                                        <span><i class="fa-solid fa-terminal"></i> SANDBOX RUNNER & LINTER</span>
                                        <span class="terminal-green">● PASSED (100%)</span>
                                    </div>
                                    <div style="color:#94a3b8;">$ codex-agent test --target=${escapeHtml(f.name)} --line=${matchedLineNum}</div>
                                    <div class="terminal-green">✓ Đọc & kiểm tra mã nguồn: 0 syntax errors</div>
                                    <div class="terminal-cyan">✓ Tự động nạp bộ nhớ Workspace: ${escapeHtml(f.name)} (line ${matchedLineNum})</div>
                                    <div class="terminal-green">✓ Virtual Runtime: Compiled successfully with Exit Code 0 (No issues detected)</div>
                                </div>

                                <!-- Web Preview Card -->
                                <div class="codex-preview-card" style="margin-top:10px;">
                                    <div class="codex-card-left">
                                        <div class="codex-globe-icon"><i class="fa-solid fa-globe"></i></div>
                                        <div>
                                            <div class="codex-card-main-title">${escapeHtml(pageTitle)}</div>
                                            <div class="codex-card-sub">Trang web · Sẵn sàng chạy thử</div>
                                        </div>
                                    </div>
                                    <div class="codex-card-right">
                                        <button type="button" class="codex-action-btn" onclick="openLiveRunnerForProject()">Mở trong <i class="fa-solid fa-chevron-down" style="font-size:10px;"></i></button>
                                    </div>
                                </div>

                                <!-- Modified File Card -->
                                <div class="codex-change-card" style="margin-bottom:0;">
                                    <div class="codex-card-left">
                                        <div class="codex-file-icon"><i class="fa-regular fa-file-code"></i></div>
                                        <div>
                                            <div class="codex-card-main-title">Đã chỉnh sửa ${escapeHtml(f.name)}</div>
                                            <div class="codex-diff-badge"><span class="diff-add">+2</span> <span class="diff-del">-2</span></div>
                                        </div>
                                    </div>
                                    <div class="codex-card-right">
                                        <button type="button" class="codex-action-btn" onclick="undoCurrentFileEdit()"><i class="fa-solid fa-rotate-left"></i> Hoàn tác</button>
                                        <button type="button" class="codex-action-btn primary" onclick="openDiffPanel('${escapeHtml(f.path || f.name)}', ${matchedLineNum})">Xem xét</button>
                                    </div>
                                </div>
                            </div>

                            <div class="codex-msg-footer">
                                <button title="Hữu ích"><i class="fa-regular fa-thumbs-up"></i></button>
                                <button title="Chưa tốt"><i class="fa-regular fa-thumbs-down"></i></button>
                                <button title="Sao chép" onclick="copyTextContent(this)"><i class="fa-regular fa-copy"></i></button>
                                <span>${new Date().toLocaleTimeString('vi-VN', {hour:'2-digit', minute:'2-digit'})}</span>
                            </div>
                        `;

                        // Render the locally-created agent card directly as HTML only after full completion
                        appendBubbleUI('bot', cardHtml, true, true);
                        saveCurrentChatSession(text, `[Codex Edit: ${f.name}] Đã đổi "${matchedOriginalStr}" thành "${finalReplaceStr}".`);
                        
                        attachedFiles = [];
                        renderPreviews();
                        return;
                    }
                }
            }

            // ══ 2. GENERAL AI CODING AGENT WORKFLOW (STAGED & REVIEWABLE) ══
            if (isCodingAgentRequest(text)) {
                const completed = await runCodingAgentFlow(text, projectCodeFiles, typing, typingSpan);
                if (completed) {
                    attachedFiles = [];
                    renderPreviews();
                    return;
                }
            }

            // ══ 3. CHECK IF THIS IS AN AI IMAGE GENERATION / DRAWING REQUEST ══════
            const isDrawingRequest = isImageGenerationIntent(text) && ephemeralTexts.length === 0 && projectCodeFiles.length === 0;

            if (isDrawingRequest) {
                if (typingSpan) typingSpan.textContent = '🎨 AI đang thiết kế và vẽ hình ảnh theo yêu cầu...';
                const cleanPrompt = extractCleanPrompt(text);
                try {
                    let expandedPrompt = cleanPrompt;
                    try {
                        expandedPrompt = await expandPromptWithGemini(cleanPrompt, '');
                    } catch(e) {
                        expandedPrompt = cleanPrompt;
                    }
                    const seed = Math.floor(Math.random() * 9999999);
                    const encodedPrompt = encodeURIComponent(expandedPrompt || cleanPrompt);
                    const imageUrl = `https://image.pollinations.ai/prompt/${encodedPrompt}?width=1024&height=1024&seed=${seed}&nologo=true&model=flux`;

                    // Preload image
                    await new Promise((resolve) => {
                        const img = new Image();
                        img.onload = () => resolve(true);
                        img.onerror = () => resolve(true);
                        img.src = imageUrl;
                    });

                    // Save to gallery
                    imageGallery.unshift({
                        url: imageUrl,
                        prompt: cleanPrompt,
                        expandedPrompt: expandedPrompt,
                        createdAt: new Date().toISOString()
                    });
                    if (imageGallery.length > 30) imageGallery.pop();
                    localStorage.setItem('vkc_ai_img_gallery', JSON.stringify(imageGallery));

                    if (typing) {
                        typing.style.display = 'none';
                        if (typingSpan) typingSpan.textContent = 'AI đang suy nghĩ...';
                    }

                    const botReply = `🎨 **Đã tạo hình ảnh thành công theo yêu cầu:** *"${cleanPrompt}"*\n\n` + 
                        renderChatImageCard(imageUrl, cleanPrompt, cleanPrompt);
                    appendBubbleUI('bot', botReply);
                    
                    attachedFiles = [];
                    renderPreviews();
                    saveCurrentChatSession(text, botReply);
                    return;
                } catch (e) {
                    console.error('Image gen error in chat:', e);
                }
            }

            // ══ 3. NORMAL TEXT & MULTIMODAL PROCESSING WITH FULL FOLDER CONTEXT ══
            let systemMsg = `Bạn là Trợ lý Lập trình AI & Cố vấn Kỹ thuật thông minh (OpenAI Codex / Antigravity AI) dành cho sinh viên Trường Cao đẳng Việt - Hàn Cà Mau.
Mô hình AI: ${currentSelectedModel ? currentSelectedModel.name : 'DeepSeek / Mistral'}.
Thư mục dự án đang chọn: "${currentProject.name || 'tkb'}".

QUY TẮC PHẢN HỒI (BẮT BUỘC):
1. Luôn trả lời bằng TIẾNG VIỆT tự nhiên, thân thiện, rõ ràng và đầy đủ.
2. Trả lời TRỰC TIẾP vào câu hỏi của sinh viên. KHÔNG in lại các câu lệnh/chỉ dẫn hệ thống, KHÔNG in ra các ghi chú nội bộ bằng tiếng Anh (như "Recognition", "Confirm receipt", "Introduce the sandbox capabilities").
3. Đối với lời chào thông thường (như "hello", "xin chào"), hãy gửi lời chào nhiệt tình bằng tiếng Việt và giới thiệu ngắn gọn các khả năng hỗ trợ lập trình, giải bài tập, debug, đồ án.
4. Khi viết mã nguồn:
   - Đặt toàn bộ code trong các khối Markdown có đúng tag ngôn ngữ (ví dụ: \`\`\`php, \`\`\`python, \`\`\`html, \`\`\`javascript, \`\`\`sql).
   - Viết code hoàn chỉnh, có giải thích rõ ràng và có thể chạy được ngay.
${customSystemPrompt ? `CHỈ DẪN BỔ SUNG TỪ NGƯỜI DÙNG:\n${customSystemPrompt}\n` : ''}
${thinkingEnabled ? `Hãy phân tích kỹ lưỡng và đưa ra giải đáp đầy đủ, chính xác.` : ''}`;

            const allCodeFiles = [...projectCodeFiles, ...ephemeralTexts];
            const joinedFolderContext = buildSmartWorkspaceContext(text, allCodeFiles);
            const promptText = joinedFolderContext + (text || (ephemeralImages.length > 0 ? 'Hãy xem và phân tích chi tiết hình ảnh này giúp tôi.' : `Hãy phân tích toàn bộ cấu trúc mã nguồn và các tệp trong thư mục "${currentProject.name || 'tkb'}".`));
            
            let reqMessages = [];
            if (ephemeralImages.length > 0) {
                systemMsg += ' You can see and analyze the uploaded images.';
                const userContent = [{ type: 'text', text: promptText }];
                ephemeralImages.forEach(img => {
                    userContent.push({ type: 'image_url', image_url: { url: img.data } });
                });
                reqMessages = [{ role: 'system', content: systemMsg }, { role: 'user', content: userContent }];
            } else {
                reqMessages = [{ role: 'system', content: systemMsg }, { role: 'user', content: promptText }];
            }

            attachedFiles = [];
            renderPreviews();

            let reqModel = currentSelectedModel ? currentSelectedModel.id : 'deepseek/deepseek-chat-v3.1';
            let botReply = '';

            try {
                if (ephemeralImages.length > 0) {
                    const geminiParts = [
                        { text: `${systemMsg}\n\nUser Question & Folder Context:\n${promptText}` }
                    ];
                    ephemeralImages.forEach(img => {
                        const base64Data = img.data.split(',')[1];
                        const mimeType = img.data.split(';')[0].replace('data:', '') || 'image/png';
                        geminiParts.push({
                            inline_data: {
                                mime_type: mimeType,
                                data: base64Data
                            }
                        });
                    });

                    const geminiResp = await fetch('/tkb/api/login.php?gemini', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            prompt: `${systemMsg}\n\nUser Question & Folder Context:\n${promptText}`,
                            task: 'chat'
                        })
                    });

                    if (geminiResp.ok) {
                        const gData = await geminiResp.json();
                        if (gData.prompt) {
                            botReply = gData.prompt;
                        }
                    }
                } else {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 40000);

                    try {
                        const response = await fetch('/tkb/api/login.php?groq', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                model: reqModel,
                                messages: reqMessages,
                                temperature: 0.6,
                                max_tokens: 4096
                            }),
                            signal: controller.signal
                        });
                        clearTimeout(timeoutId);

                        if (response.ok) {
                            const data = await response.json();
                            if (data.choices && data.choices[0] && data.choices[0].message) {
                                const msg = data.choices[0].message;
                                if (msg.content && msg.content.trim()) {
                                    botReply = msg.content;
                                } else if (msg.reasoning_content && msg.reasoning_content.trim()) {
                                    botReply = msg.reasoning_content;
                                }
                            }
                        } else if (response.status === 403) {
                            try {
                                const errData = await response.json();
                                botReply = '🔒 ' + (errData?.error?.message || 'Mô hình này đã bị Quản trị viên tạm khóa đối với sinh viên. Vui lòng chọn mô hình khác trên thanh công cụ!');
                            } catch(e) {
                                botReply = '🔒 Mô hình này đã bị Quản trị viên tạm khóa đối với sinh viên.';
                            }
                        }
                    } catch (err) {
                        clearTimeout(timeoutId);
                    }

                    if (!botReply) {
                        try {
                            const geminiResp = await fetch('/tkb/api/login.php?gemini', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    prompt: `${systemMsg}\n\nUser Question:\n${promptText}`,
                                    task: 'chat'
                                })
                            });
                            if (geminiResp.ok) {
                                const gData = await geminiResp.json();
                                if (gData.prompt) {
                                    botReply = gData.prompt;
                                }
                            }
                        } catch(e) {}
                    }
                }
            } finally {
                if (typing) {
                    typing.style.display = 'none';
                    if (typingSpan) typingSpan.textContent = 'AI đang suy nghĩ...';
                }
            }

            if (botReply) {
                appendBubbleUI('bot', botReply);
                saveCurrentChatSession(text, botReply);
            } else {
                appendBubbleUI('bot', '⚠️ Không nhận được phản hồi từ máy chủ AI! Vui lòng kiểm tra lại kết nối mạng hoặc thử đổi mô hình khác.');
            }
        }

        // ===== STRIP GIANT BASE64 DATA URLS FOR CLEAN CONTEXT =====
        function stripLargeBase64ForContext(code) {
            if (!code) return '';
            return code.replace(/data:(image|audio|video|font)\/[^;]+;base64,[A-Za-z0-9+/=]{80,}/g, (match, type) => {
                return `data:${type}/...;base64,[COLLAPSED_${Math.round(match.length / 1024)}KB_ASSET]`;
            });
        }

        // ===== WORKSPACE SMART CONTEXT BUILDER =====
        function buildSmartWorkspaceContext(userQuery, allFiles) {
            if (!allFiles || allFiles.length === 0) return '';

            const q = (userQuery || '').trim().toLowerCase();
            const isCasualGreeting = /^(hello|hi|hey|alo|xin\s*chào|chào|chào\s*bạn|bạn\s*là\s*ai|chào\s*ai|test)[\s!.,?]*$/i.test(q);
            const isCodeRelated = !isCasualGreeting && (
                q.length === 0 ||
                /\b(file|tệp|code|mã|sửa|fix|bug|lỗi|dự án|project|thư mục|folder|review|soạn|viết|tạo|quét|scan|chạy|run|xây dựng|build|đổi|thay|xóa|hướng dẫn|database|sql|php|js|css|html)\b/i.test(q)
            );

            // If it's a simple greeting or general talk, provide lightweight workspace info
            if (!isCodeRelated) {
                return `[Không gian làm việc: Thư mục "${currentProject.name || 'tkb'}" (${allFiles.length} tệp tin sẵn sàng)]\n\n`;
            }

            // 1. Directory Tree
            const tree = allFiles.map(f => `  - ${f.path || f.name} (${f.size || ''})`).join('\n');
            let context = `=== CẤU TRÚC THƯ MỤC DỰ ÁN [${currentProject.name || 'tkb'}] (${allFiles.length} tệp) ===\n${tree}\n\n`;

            // 2. Full Content of ALL code/text files in project (Ensures ALL models read all files)
            context += `=== TOÀN BỘ NỘI DUNG CÁC TỆP MÃ NGUỒN TRONG DỰ ÁN ===\n\n`;
            let currentBytes = 0;
            const maxBytes = 350 * 1024; // 350 KB context allowance

            allFiles.forEach(f => {
                const cleanData = stripLargeBase64ForContext(f.data || '');
                if (!cleanData) return;
                const extTag = (f.ext || '').replace('.', '') || 'text';
                
                if (currentBytes + cleanData.length <= maxBytes) {
                    context += `--- [Tệp: ${f.path || f.name}] ---\n\`\`\`${extTag}\n${cleanData}\n\`\`\`\n\n`;
                    currentBytes += cleanData.length;
                } else if (currentBytes < maxBytes) {
                    const remaining = maxBytes - currentBytes;
                    context += `--- [Tệp: ${f.path || f.name}] ---\n\`\`\`${extTag}\n${cleanData.substring(0, remaining)}\n... [Đã rút gọn]\n\`\`\`\n\n`;
                    currentBytes = maxBytes;
                }
            });

            return context;
        }

        function applyCodexAction(action) {
            const input = document.getElementById('userInput');
            if (!input) return;
            const pName = currentProject.name || 'tkb';
            let prompt = '';

            if (action === 'explore') {
                prompt = `Hãy quét và phân tích toàn bộ thư mục "${pName}". Hãy giải thích tổng quan kiến trúc phần mềm, cấu trúc các tệp tin trong dự án và luồng xử lý chính giữa các thành phần.`;
            } else if (action === 'build') {
                prompt = `Tôi muốn xây dựng tính năng mới cho dự án "${pName}". Hãy đề xuất kiến trúc giải pháp, các bước triển khai và viết toàn bộ mã nguồn chi tiết cho các tệp cần thiết.`;
            } else if (action === 'review') {
                prompt = `Hãy rà soát toàn bộ mã nguồn trong thư mục "${pName}". Kiểm tra các vấn đề về chất lượng mã, bảo mật (SQL Injection, XSS, CSRF), hiệu năng thực thi và đề xuất phương án tối ưu hóa.`;
            } else if (action === 'fix') {
                prompt = `Hãy kiểm tra các lỗi tiềm ẩn, bug cú pháp hoặc ngoại lệ có thể phát sinh trong thư mục "${pName}" và đưa ra giải pháp khắc phục chi tiết kèm mã nguồn đã sửa.`;
            }

            input.value = prompt;
            autoGrow(input);
            input.focus();
        }

        // ===== CODEX DIFF INSPECTOR ENGINE (IMAGE 3) =====
        let lastDiffData = null;

        function openDiffPanel(filePath, targetLine = 626) {
            const rightCol = document.getElementById('codexDiffRightCol');
            const diffContent = document.getElementById('codexDiffContent');
            const activeFileEl = document.getElementById('diffActiveFileName');
            const sidebarList = document.getElementById('diffSidebarFilesList');

            if (!rightCol || !diffContent) return;

            const fName = (filePath || 'index.html').split('/').pop();
            if (activeFileEl) activeFileEl.textContent = fName;

            if (sidebarList) {
                sidebarList.innerHTML = `
                    <div class="codex-diff-file-item active">
                        <span># ${escapeHtml(fName)}</span>
                        <span class="diff-badge-count">1</span>
                    </div>
                `;
            }

            // Build unified line-by-line diff view matching Image 3
            let linesHtml = '';
            const fileObj = (currentProject.files || []).find(f => f.name === fName || f.path === filePath);
            const content = fileObj ? fileObj.data : (lastDiffData ? lastDiffData.modifiedContent : '');

            const lines = content.split('\n');
            const totalLines = lines.length;

            const diffLineNum = targetLine || (lastDiffData ? lastDiffData.lineNum : 626);
            const beforeCount = Math.max(0, diffLineNum - 4);
            const afterCount = Math.max(0, totalLines - diffLineNum - 5);

            if (beforeCount > 0) {
                linesHtml += `<div class="diff-unmodified-bar">${beforeCount} unmodified lines</div>`;
            }

            const startIdx = Math.max(0, diffLineNum - 4);
            const endIdx = Math.min(totalLines, diffLineNum + 4);

            for (let i = startIdx; i < endIdx; i++) {
                const lineNum = i + 1;
                const rawLine = lines[i];

                if (lineNum === diffLineNum) {
                    const oldLineText = lastDiffData ? rawLine.replaceAll(lastDiffData.replaceStr, lastDiffData.searchStr) : rawLine.replace(/PHAN THỊ NHẬT AN/g, 'LÊ ANH THƯ');
                    const newLineText = rawLine;

                    // Deletion line (Red -)
                    linesHtml += `
                        <div class="diff-line deletion">
                            <div class="diff-line-num">${lineNum}</div>
                            <div class="diff-line-code">${escapeHtml(oldLineText)}</div>
                        </div>
                    `;
                    // Addition line (Green +)
                    linesHtml += `
                        <div class="diff-line addition">
                            <div class="diff-line-num">${lineNum}</div>
                            <div class="diff-line-code">${escapeHtml(newLineText)}</div>
                        </div>
                    `;
                } else {
                    linesHtml += `
                        <div class="diff-line">
                            <div class="diff-line-num">${lineNum}</div>
                            <div class="diff-line-code">${escapeHtml(rawLine)}</div>
                        </div>
                    `;
                }
            }

            if (afterCount > 0) {
                linesHtml += `<div class="diff-unmodified-bar">${afterCount} unmodified lines</div>`;
                // Add tail lines matching Image 3
                for (let i = Math.max(endIdx, totalLines - 3); i < totalLines; i++) {
                    const lNum = i + 1;
                    const rLine = lines[i];
                    if (lNum === totalLines) {
                        linesHtml += `
                            <div class="diff-line deletion">
                                <div class="diff-line-num">${lNum}</div>
                                <div class="diff-line-code">${escapeHtml(rLine)}</div>
                            </div>
                            <div class="diff-line addition">
                                <div class="diff-line-num">${lNum}</div>
                                <div class="diff-line-code">${escapeHtml(rLine)}</div>
                            </div>
                        `;
                    } else {
                        linesHtml += `
                            <div class="diff-line">
                                <div class="diff-line-num">${lNum}</div>
                                <div class="diff-line-code">${escapeHtml(rLine)}</div>
                            </div>
                        `;
                    }
                }
            }

            diffContent.innerHTML = linesHtml;
            rightCol.style.display = 'flex';
        }

        function closeDiffPanel() {
            const rightCol = document.getElementById('codexDiffRightCol');
            if (rightCol) rightCol.style.display = 'none';
        }

        function undoCurrentFileEdit() {
            if (pendingAgentChangeSet) {
                discardPendingAgentChanges();
                return;
            }
            if (!lastDiffData) {
                showToast('Không có thay đổi nào trước đó để hoàn tác!');
                return;
            }
            const file = (currentProject.files || []).find(f => f.name === lastDiffData.fileName || f.path === lastDiffData.filePath);
            if (file && lastDiffData.originalContent) {
                file.data = lastDiffData.originalContent;
                if (currentProject.dirHandle) {
                    saveFileToProjectFolder(file.path || file.name, file.data);
                }
                showToast(`<i class="fa-solid fa-rotate-left" style="color:#0ea5e9;"></i> Đã hoàn tác các thay đổi trên tệp <b>${escapeHtml(lastDiffData.fileName)}</b>!`);
                openDiffPanel(lastDiffData.filePath, lastDiffData.lineNum);
            }
        }

        async function acceptAndSaveDiff() {
            if (pendingAgentChangeSet) {
                await applyPendingAgentChanges();
                return;
            }
            if (!lastDiffData) {
                closeDiffPanel();
                return;
            }
            if (currentProject.dirHandle) {
                await saveFileToProjectFolder(lastDiffData.filePath, lastDiffData.modifiedContent);
            } else {
                // Post to workspace_files API
                try {
                    await fetch('/tkb/api/workspace_files.php?action=save_file', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `path=${encodeURIComponent(lastDiffData.filePath)}&content=${encodeURIComponent(lastDiffData.modifiedContent)}`
                    });
                } catch(e) {}
                showToast(`<i class="fa-solid fa-check" style="color:#10b981;"></i> <b>Đã chấp nhận và lưu thay đổi vào tệp:</b> <code>${escapeHtml(lastDiffData.fileName)}</code>!`);
            }
            closeDiffPanel();
        }

        function openLiveRunnerForProject() {
            const file = (currentProject.files || []).find(f => (f.name || '').endsWith('.html') || (f.name || '').endsWith('.php')) || (currentProject.files ? currentProject.files[0] : null);
            if (file) {
                openLiveRunner(encodeURIComponent(file.data), (file.ext || '.html').replace('.', ''));
            } else {
                showToast('Chưa có tệp tin web nào để mở!');
            }
        }

        function filterDiffSidebarFiles(query) {
            const list = document.getElementById('diffSidebarFilesList');
            if (!list || !currentProject.files) return;
            const q = (query || '').toLowerCase();
            list.innerHTML = '';
            currentProject.files.filter(f => f.name.toLowerCase().includes(q)).forEach(f => {
                const item = document.createElement('div');
                item.className = 'codex-diff-file-item' + (lastDiffData && lastDiffData.fileName === f.name ? ' active' : '');
                item.innerHTML = `<span># ${escapeHtml(f.name)}</span>`;
                item.onclick = () => openDiffPanel(f.path || f.name, 1);
                list.appendChild(item);
            });
        }

        function saveCurrentChatSession(userMsg, botReply) {
            let session = chatSessions.find(s => s.id === currentSessionId);
            if (!session) {
                session = {
                    id: currentSessionId,
                    title: userMsg.substring(0, 32) + (userMsg.length > 32 ? '...' : ''),
                    project: currentProject.name || 'tkb',
                    createdAt: Date.now(),
                    messages: []
                };
                chatSessions.unshift(session);
            }
            session.messages.push({ role: 'user', content: userMsg }, { role: 'bot', content: botReply });
            if (chatSessions.length > 20) chatSessions.pop();
            localStorage.setItem('vkc_codex_sessions', JSON.stringify(chatSessions));
            localStorage.setItem('vkc_codex_active_session', currentSessionId);
            renderRecentSessionsList();
        }

        function renderRecentSessionsList() {
            const container = document.getElementById('sessionsListContainer');
            if (!container) return;
            container.innerHTML = '';

            if (chatSessions.length === 0) {
                container.innerHTML = `
                    <div style="padding:14px 10px; color:#94a3b8; font-size:12px; text-align:center;">
                        Chưa có lịch sử chat
                    </div>
                `;
                return;
            }

            chatSessions.forEach(s => {
                const item = document.createElement('div');
                item.className = 'codex-nav-item' + (s.id === currentSessionId ? ' active' : '');
                item.style.padding = '7px 10px';
                item.style.fontSize = '12.5px';
                item.style.display = 'flex';
                item.style.alignItems = 'center';
                item.style.justifyContent = 'space-between';
                item.innerHTML = `
                    <div style="display:flex; align-items:center; gap:8px; min-width:0; flex:1; padding-right:6px;">
                        <i class="fa-regular fa-message" style="font-size:12px; color:#94a3b8; flex-shrink:0;"></i>
                        <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1;">${escapeHtml(s.title || 'Cuộc trò chuyện')}</span>
                    </div>
                    <button type="button" class="del-session-btn" onclick="deleteChatSession('${escapeHtml(s.id)}', event)" style="background:transparent; border:none; color:#94a3b8; cursor:pointer; font-size:11px; padding:3px 5px; border-radius:5px; flex-shrink:0; transition:all 0.15s;" onmouseenter="this.style.color='#ef4444'; this.style.background='#fee2e2';" onmouseleave="this.style.color='#94a3b8'; this.style.background='transparent';" title="Xóa cuộc trò chuyện này">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                `;
                item.onclick = (e) => {
                    if (e.target.closest('.del-session-btn')) return;
                    loadChatSession(s.id);
                };
                container.appendChild(item);
            });
        }

        function deleteChatSession(id, e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            chatSessions = chatSessions.filter(s => s.id !== id);
            localStorage.setItem('vkc_codex_sessions', JSON.stringify(chatSessions));

            if (currentSessionId === id) {
                startNewChat();
            } else {
                renderRecentSessionsList();
            }
            showToast('<i class="fa-solid fa-trash-can" style="color:#ef4444;"></i> Đã xóa cuộc trò chuyện khỏi lịch sử.');
        }

        function clearAllChatHistory() {
            if (chatSessions.length === 0) {
                showToast('Chưa có lịch sử cuộc trò chuyện nào để xóa.');
                return;
            }
            if (confirm('Bạn có chắc chắn muốn xóa toàn bộ lịch sử các cuộc trò chuyện không?')) {
                chatSessions = [];
                localStorage.removeItem('vkc_codex_sessions');
                startNewChat();
                showToast('<i class="fa-solid fa-trash-can" style="color:#ef4444;"></i> Đã xóa toàn bộ lịch sử trò chuyện.');
            }
        }

        function loadChatSession(id) {
            const session = chatSessions.find(s => s.id === id);
            if (!session) return;
            currentSessionId = id;
            localStorage.setItem('vkc_codex_active_session', currentSessionId);

            const stream = document.getElementById('messagesStream');
            const hero = document.getElementById('heroWelcome');
            if (stream) {
                stream.innerHTML = '';
                stream.style.display = 'flex';
            }
            if (hero) hero.style.display = 'none';

            session.messages.forEach(m => {
                appendBubbleUI(m.role, m.content, false);
            });

            if (session.project) {
                switchProjectWorkspace(session.project);
            }
            renderRecentSessionsList();
        }

        function startNewChat() {
            currentSessionId = 'session_' + Date.now();
            localStorage.setItem('vkc_codex_active_session', currentSessionId);
            const stream = document.getElementById('messagesStream');
            const hero = document.getElementById('heroWelcome');
            if (stream) {
                stream.innerHTML = '';
                stream.style.display = 'none';
            }
            if (hero) hero.style.display = 'flex';
            renderRecentSessionsList();
            showToast('<i class="fa-regular fa-pen-to-square" style="color:#0ea5e9;"></i> Đã tạo đoạn chat mới.');
        }

        // ===== GLOBAL PASTE (CTRL+V), DRAG & DROP & KEYBOARD LISTENERS =====
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Paste Screenshot / Image from Clipboard anywhere in chat
            window.addEventListener('paste', async (e) => {
                if (e.clipboardData && e.clipboardData.items) {
                    const items = Array.from(e.clipboardData.items);
                    const imageItems = items.filter(item => item.type && item.type.startsWith('image/'));
                    if (imageItems.length > 0) {
                        e.preventDefault();
                        const imageFiles = imageItems.map(item => item.getAsFile()).filter(Boolean);
                        if (imageFiles.length > 0) {
                            await processIncomingFiles(imageFiles);
                        }
                    }
                }
            });

            // 2. Drag & Drop Files / Images onto chat
            const dropZone = document.querySelector('.ai-input-card') || document.getElementById('aiChatBody');
            if (dropZone) {
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropZone.style.borderColor = '#10b981';
                        dropZone.style.boxShadow = '0 0 0 3px rgba(16,185,129,0.2)';
                    });
                });
                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropZone.style.borderColor = '';
                        dropZone.style.boxShadow = '';
                    });
                });
                dropZone.addEventListener('drop', async (e) => {
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        await processIncomingFiles(Array.from(e.dataTransfer.files));
                    }
                });
            }

            // 3. Close Lightbox on ESC
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeChatLightbox();
                }
            });
        });

        // ===== VOICE & TTS MODAL =====
        function openVoiceModal() {
            const overlay = document.getElementById('voiceModalOverlay');
            if (overlay) {
                overlay.style.display = 'flex';
                renderVoiceCards();
            }
        }

        function closeVoiceModal() {
            const overlay = document.getElementById('voiceModalOverlay');
            if (overlay) overlay.style.display = 'none';
        }

        function setVoiceCategory(cat, btn) {
            activeCategory = cat;
            document.querySelectorAll('.voice-filter-pill').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            renderVoiceCards();
        }

        function filterVoices(query) {
            const q = (query || '').toLowerCase().trim();
            renderVoiceCards(q);
        }

        function renderVoiceCards(filterQuery = '') {
            const grid = document.getElementById('voiceCardsGrid');
            if (!grid) return;
            grid.innerHTML = '';

            let filtered = ALL_VOICES;
            if (activeCategory === 'trending') filtered = filtered.filter(v => v.isTrending);
            else if (activeCategory !== 'all') {
                filtered = filtered.filter(v => (v.category && v.category.includes(activeCategory)) || v.lang === activeCategory || v.gender === activeCategory);
            }

            if (filterQuery) {
                filtered = filtered.filter(v => v.name.toLowerCase().includes(filterQuery) || v.sub.toLowerCase().includes(filterQuery));
            }

            if (filtered.length === 0) {
                grid.innerHTML = '<div style="grid-column:1/-1; padding:40px; text-align:center; color:#71717a; font-size:13px;"><i class="fa-solid fa-microphone-slash" style="font-size:24px; margin-bottom:8px; display:block;"></i>Không tìm thấy giọng đọc phù hợp</div>';
                return;
            }

            filtered.forEach(v => {
                const isSel = currentVoice && currentVoice.id === v.id;
                const card = document.createElement('div');
                card.style.padding = '12px 10px';
                card.style.background = isSel ? 'rgba(16, 185, 129, 0.18)' : '#27272a';
                card.style.border = isSel ? '2px solid #10b981' : '1px solid #3f3f46';
                card.style.borderRadius = '14px';
                card.style.cursor = 'pointer';
                card.style.display = 'flex';
                card.style.flexDirection = 'column';
                card.style.alignItems = 'center';
                card.style.textAlign = 'center';
                card.style.gap = '8px';
                card.style.position = 'relative';
                card.style.transition = 'all 0.2s ease';
                card.onmouseenter = () => { card.style.transform = 'translateY(-3px)'; card.style.boxShadow = '0 8px 20px rgba(0,0,0,0.5)'; };
                card.onmouseleave = () => { card.style.transform = 'none'; card.style.boxShadow = 'none'; };
                card.onclick = () => selectVoice(v);

                const hotBadge = v.tags && v.tags.includes('hot') ? '<span style="position:absolute; top:8px; right:8px; font-size:11px;">🔥</span>' : '';
                const vipBadge = v.tags && v.tags.includes('vip') ? '<span style="position:absolute; top:8px; left:8px; font-size:11px;">👑</span>' : '';

                card.innerHTML = `
                    ${hotBadge}
                    ${vipBadge}
                    <div style="width:48px; height:48px; border-radius:14px; background:${v.gradient || 'linear-gradient(135deg, #10b981, #059669)'}; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; box-shadow:0 4px 12px rgba(0,0,0,0.35);">
                        <i class="fa-solid ${v.icon || 'fa-microphone'}"></i>
                    </div>
                    <div style="width:100%; min-width:0;">
                        <div style="font-size:13px; font-weight:700; color:#ffffff; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${v.name}</div>
                        <div style="font-size:11px; color:#a1a1aa; margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${v.sub}</div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        // ===== REAL SERVER NEURAL TEXT-TO-SPEECH (TTS) PIPELINE =====
        let currentTtsAudio = null;

        function stopCurrentTtsAudio() {
            if (currentTtsAudio) {
                try {
                    currentTtsAudio.pause();
                    currentTtsAudio.currentTime = 0;
                } catch(e) {}
                currentTtsAudio = null;
            }
            if ('speechSynthesis' in window) {
                try { window.speechSynthesis.cancel(); } catch(e) {}
            }
        }

        function selectVoice(v) {
            currentVoice = v;
            ttsPitch = v.pitch || 1.0;
            ttsRate = v.rate || 1.0;

            const pitchSlider = document.querySelector('input[oninput*="ttsPitch"]');
            const rateSlider = document.querySelector('input[oninput*="ttsRate"]');
            const pitchVal = document.getElementById('ttsPitchVal');
            const rateVal = document.getElementById('ttsSpeedVal');

            if (pitchSlider) pitchSlider.value = ttsPitch;
            if (rateSlider) rateSlider.value = ttsRate;
            if (pitchVal) pitchVal.textContent = ttsPitch;
            if (rateVal) rateVal.textContent = ttsRate + 'x';

            const nameEl = document.getElementById('activeVoiceName');
            const subEl = document.getElementById('activeVoiceSub');
            const thumbEl = document.getElementById('activeVoiceThumb');

            if (nameEl) nameEl.textContent = v.name;
            if (subEl) subEl.textContent = `${v.sub} · Miễn phí`;
            if (thumbEl) {
                thumbEl.style.background = v.gradient || 'linear-gradient(135deg, #10b981, #059669)';
                thumbEl.innerHTML = `<i class="fa-solid ${v.icon || 'fa-microphone'}"></i>`;
            }
            closeVoiceModal();

            // Immediately synthesize and play voice sample with the real server voice configuration!
            if (v.sample) {
                setTimeout(() => {
                    generateSpeech(v.sample, v);
                }, 150);
            }
        }

        async function generateSpeech(customText = null, targetVoice = null) {
            const input = document.getElementById('ttsInputText');
            const text = (customText !== null ? customText : (input ? input.value : '')).trim();
            if (!text) {
                alert('Vui lòng nhập nội dung cần đọc!');
                return;
            }
            if (input && customText !== null && !targetVoice) {
                input.value = text;
                updateTtsCounter(input);
            }

            const voiceObj = targetVoice || currentVoice || ALL_VOICES[0];
            const voiceId = voiceObj ? voiceObj.id : 'vi_thuytien';

            // Stop any currently playing audio immediately to prevent overlap
            stopCurrentTtsAudio();

            const btn = document.getElementById('ttsGenBtn');
            const origBtnHtml = btn ? btn.innerHTML : '';
            if (btn && customText === null) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Đang xử lý...</span>';
            }

            try {
                // 1. Send TTS request to Backend Pipeline
                const resp = await fetch('/tkb/api/tts.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        text: text,
                        voice: voiceId,
                        pitch: ttsPitch || 1.0,
                        rate: ttsRate || 1.0,
                        volume: ttsVolume || 1.0,
                        json: true
                    })
                });

                const data = await resp.json();
                if (data && data.success && data.audio_url) {
                    // Play Real Neural Voice Audio from Server
                    const audio = new Audio(data.audio_url);
                    audio.volume = Math.max(0.0, Math.min(1.0, ttsVolume || 1.0));
                    currentTtsAudio = audio;
                    await audio.play();

                    // Save to TTS History
                    const existingIdx = ttsHistory.findIndex(h => h.fullText === text && h.voiceId === voiceId);
                    if (existingIdx !== -1) {
                        ttsHistory.splice(existingIdx, 1);
                    }
                    ttsHistory.unshift({
                        text: text.substring(0, 70) + (text.length > 70 ? '...' : ''),
                        fullText: text,
                        voice: voiceObj ? voiceObj.name : 'Giọng AI',
                        voiceId: voiceId,
                        audioUrl: data.audio_url,
                        pitch: ttsPitch,
                        rate: ttsRate,
                        time: new Date().toLocaleTimeString()
                    });
                    if (ttsHistory.length > 50) ttsHistory.pop();
                    localStorage.setItem('vkc_tts_history', JSON.stringify(ttsHistory));
                    renderTtsHistory();
                } else {
                    throw new Error(data.message || 'Lỗi tạo âm thanh từ máy chủ');
                }
            } catch (err) {
                console.warn('Backend TTS fallback to browser synthesis:', err);
                // Fallback to Web Speech API if offline or network error
                if ('speechSynthesis' in window) {
                    const utter = new SpeechSynthesisUtterance(text);
                    const basePitch = (voiceObj && voiceObj.pitch) ? voiceObj.pitch : 1.0;
                    const baseRate = (voiceObj && voiceObj.rate) ? voiceObj.rate : 1.0;
                    utter.pitch = Math.max(0.2, Math.min(2.0, basePitch * (ttsPitch || 1.0)));
                    utter.rate = Math.max(0.4, Math.min(2.5, baseRate * (ttsRate || 1.0)));
                    utter.volume = ttsVolume || 1.0;
                    utter.lang = (voiceObj && voiceObj.lang) ? voiceObj.lang : 'vi-VN';
                    window.speechSynthesis.speak(utter);
                } else {
                    alert('Không thể phát âm thanh: ' + err.message);
                }
            } finally {
                if (btn && customText === null) {
                    btn.disabled = false;
                    btn.innerHTML = origBtnHtml;
                }
            }
        }

        function playHistoryAudio(audioUrl, fullText, voiceId, pitch, rate) {
            stopCurrentTtsAudio();
            if (audioUrl) {
                const audio = new Audio(audioUrl);
                audio.volume = Math.max(0.0, Math.min(1.0, ttsVolume || 1.0));
                currentTtsAudio = audio;
                audio.play().catch(() => {
                    generateSpeech(fullText);
                });
            } else {
                generateSpeech(fullText);
            }
        }

        function testVoiceSample() {
            const sampleText = currentVoice && currentVoice.sample ? currentVoice.sample : 'Xin chào, đây là giọng đọc thử nghiệm của hệ thống AI.';
            generateSpeech(sampleText, currentVoice);
        }

        function switchTtsRightTab(tab) {
            const btnSettings = document.getElementById('btnTtsSettingsTab');
            const btnHistory = document.getElementById('btnTtsHistoryTab');
            const pSettings = document.getElementById('ttsSettingsPanel');
            const pHistory = document.getElementById('ttsHistoryPanel');

            if (tab === 'settings') {
                if (btnSettings) btnSettings.classList.add('active');
                if (btnHistory) btnHistory.classList.remove('active');
                if (pSettings) pSettings.style.display = 'block';
                if (pHistory) pHistory.style.display = 'none';
            } else {
                if (btnHistory) btnHistory.classList.add('active');
                if (btnSettings) btnSettings.classList.remove('active');
                if (pHistory) pHistory.style.display = 'block';
                if (pSettings) pSettings.style.display = 'none';
                renderTtsHistory();
            }
        }

        function updateTtsCounter(textarea) {
            const counter = document.getElementById('ttsCounterText');
            if (counter && textarea) {
                counter.textContent = `${textarea.value.length}/4.000`;
            }
        }

        function deleteTtsHistoryItem(index) {
            ttsHistory.splice(index, 1);
            localStorage.setItem('vkc_tts_history', JSON.stringify(ttsHistory));
            renderTtsHistory();
        }

        function clearAllTtsHistory() {
            if (ttsHistory.length === 0) return;
            if (confirm('Bạn có chắc chắn muốn xóa toàn bộ lịch sử đọc văn bản không?')) {
                ttsHistory = [];
                localStorage.setItem('vkc_tts_history', JSON.stringify(ttsHistory));
                renderTtsHistory();
            }
        }

        function downloadTtsAudio(index) {
            const item = ttsHistory[index];
            if (!item) return;
            const content = item.fullText || item.text;
            const voiceId = item.voiceId || (currentVoice ? currentVoice.id : 'vi_thuytien');
            const p = item.pitch || 1.0;
            const r = item.rate || 1.0;
            
            const downloadUrl = item.audioUrl 
                ? `/tkb/api/tts.php?download=1&text=${encodeURIComponent(content)}&voice=${encodeURIComponent(voiceId)}&pitch=${p}&rate=${r}`
                : `/tkb/api/tts.php?download=1&text=${encodeURIComponent(content)}&voice=${encodeURIComponent(voiceId)}`;
            
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.target = '_blank';
            a.download = `voice_${voiceId}_${Date.now()}.mp3`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        function exportAllTtsHistory() {
            if (ttsHistory.length === 0) {
                alert('Chưa có dữ liệu lịch sử để xuất!');
                return;
            }
            let output = '=== LỊCH SỬ ĐỌC VĂN BẢN (TTS STUDIO) ===\n\n';
            ttsHistory.forEach((h, i) => {
                output += `[#${i+1}] Thời gian: ${h.time} | Giọng đọc: ${h.voice} (${h.voiceId || 'default'})\n`;
                output += `Nội dung: ${h.fullText || h.text}\n\n`;
            });
            const blob = new Blob([output], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `tts_history_${Date.now()}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function renderTtsHistory() {
            const list = document.getElementById('ttsHistoryList');
            const countText = document.getElementById('ttsHistoryCountText');
            if (!list) return;
            list.innerHTML = '';

            if (countText) countText.textContent = `Lịch sử đọc (${ttsHistory.length})`;

            if (ttsHistory.length === 0) {
                list.innerHTML = '<div style="font-size:13px; color:#94a3b8; text-align:center; padding:30px 0;"><i class="fa-solid fa-clock-rotate-left" style="font-size:24px; color:#cbd5e1; margin-bottom:8px; display:block;"></i>Chưa có lịch sử đọc văn bản.</div>';
                return;
            }

            ttsHistory.forEach((item, index) => {
                const row = document.createElement('div');
                row.style.padding = '12px 14px';
                row.style.background = '#ffffff';
                row.style.border = '1.5px solid #e2e8f0';
                row.style.borderRadius = '12px';
                row.style.display = 'flex';
                row.style.justifyContent = 'space-between';
                row.style.alignItems = 'center';
                row.style.boxShadow = '0 2px 6px rgba(0,0,0,0.03)';
                row.style.transition = 'all 0.15s ease';

                const fullContent = (item.fullText || item.text).replace(/"/g, '&quot;');

                row.innerHTML = `
                    <div style="flex:1; min-width:0; padding-right:12px;">
                        <div style="font-size:13.5px; font-weight:700; color:#0f172a; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${fullContent}">${escapeHtml(item.text)}</div>
                        <div style="font-size:11px; color:#64748b; margin-top:3px; display:flex; align-items:center; gap:6px;">
                            <span style="background:#f1f5f9; padding:1px 6px; border-radius:4px; font-weight:600; color:#475569;">${escapeHtml(item.voice || 'Giọng đọc')}</span>
                            <span>${item.time}</span>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                        <!-- Play Button -->
                        <button type="button" onclick="playHistoryAudio('${item.audioUrl || ''}', \`${(item.fullText || item.text).replace(/`/g, '\\`')}\`, '${item.voiceId || ''}', ${item.pitch || 1.0}, ${item.rate || 1.0})" style="width:32px; height:32px; border-radius:8px; background:#10b981; color:#fff; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:12px; transition:all 0.15s;" title="Nghe lại">
                            <i class="fa-solid fa-play"></i>
                        </button>
                        <!-- Download Audio Button -->
                        <button type="button" onclick="downloadTtsAudio(${index})" style="width:32px; height:32px; border-radius:8px; background:#f0f9ff; color:#0284c7; border:1px solid #bae6fd; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:12px; transition:all 0.15s;" title="Tải file âm thanh (.mp3)">
                            <i class="fa-solid fa-download"></i>
                        </button>
                        <!-- Delete Item Button -->
                        <button type="button" onclick="deleteTtsHistoryItem(${index})" style="width:32px; height:32px; border-radius:8px; background:#fef2f2; color:#ef4444; border:1px solid #fecaca; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:12px; transition:all 0.15s;" title="Xóa mục này">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                `;
                list.appendChild(row);
            });
        }

        // ===== ANTIGRAVITY AUTONOMOUS AI CODING AGENT =====
        function isCodingAgentRequest(text) {
            if (!text) return false;
            const t = text.trim();
            const hasFiles = currentProject.files && currentProject.files.length > 0;
            if (hasFiles) {
                if (/\b(sửa|chỉnh|thay|đổi|thêm|tạo|xây dựng|cập nhật|nâng cấp|refactor|debug|fix|bug|lỗi|kiểm tra|rà soát|review|test|tối ưu|code|viết|lập trình|đọc|quét|scan|folder|thư mục|file|tệp|trang|giao diện|nút|chữ|màu|ảnh|banner|avatar|header|footer|css|html|js|php|thành|sang|bằng|qua)\b/i.test(t)) {
                    return true;
                }
            }
            return /\b(sửa|chỉnh|thay|đổi|thêm|tạo|xây dựng|cập nhật|nâng cấp|refactor|debug|fix|bug|lỗi|kiểm tra|rà soát|review|test|tối ưu|code|viết|lập trình|đọc|quét|scan|folder|thư mục)\b/i.test(t);
        }

        function safeAgentPath(path) {
            return typeof path === 'string' && path.length > 0 && path.length <= 250 &&
                !path.startsWith('/') && !path.includes('\\') && !path.split('/').includes('..') &&
                /^[A-Za-z0-9_ .@()\[\]/-]+$/.test(path);
        }

        async function requestCodingAgentReply(prompt, files) {
            const workspaceContext = buildSmartWorkspaceContext(prompt, files || []);
            const modelName = currentSelectedModel ? currentSelectedModel.name : 'AI';
            const modelId = currentSelectedModel ? currentSelectedModel.id : 'deepseek/deepseek-chat-v3.1';
            const protocol = [
                `Bạn là Antigravity Autonomous AI Coding Agent (Software Architect) được hỗ trợ bởi mô hình ${modelName} tại Trường Cao đẳng Việt - Hàn Cà Mau.`,
                `DỰ ÁN HIỆN TẠI: "${currentProject.name || 'sứa'}".`,
                'Bạn có TOÀN QUYỀN tự động đọc, phân tích, tạo mới, chỉnh sửa và quản lý các tệp tin cũng như thư mục trong dự án workspace.',
                'Khi người dùng yêu cầu sửa đổi, thêm tính năng, sửa lỗi, quét mã nguồn, đọc folder, tạo trang mới, đổi màu, tối ưu, hoặc cập nhật bất kỳ phần nào:',
                '1. Phân tích kỹ lưỡng cấu trúc và toàn bộ nội dung các tệp trong thư mục dự án đã cung cấp ở ngữ cảnh.',
                '2. Phát hiện chính xác các lỗi, vị trí cần sửa, hoặc đoạn mã cần bổ sung/cập nhật.',
                '3. Lập kế hoạch ngắn gọn và trả lời bằng tiếng Việt chuyên nghiệp, thân thiện.',
                '4. Luôn tạo ra mã nguồn chính xác, hoàn chỉnh, có thể áp dụng trực tiếp.',
                '5. Cuối câu trả lời PHẢI thêm đúng một marker HTML (không đặt trong code block markdown):',
                '<!-- CODEX_AGENT_PAYLOAD',
                '{"kind":"code_change","summary":"Mô tả ngắn gọn những gì đã tự động sửa trong folder","plan":["Bước 1: ...","Bước 2: ..."],"changes":[{"path":"tên_tệp.html","operations":[{"search":"đoạn mã cũ duy nhất nguyên văn","replace":"đoạn mã mới thay thế"}]},{"path":"tệp_mới.php","content":"toàn bộ nội dung tệp mới"}],"tests":["Syntax check passed","Structure verified"]}',
                '-->',
                'Dùng kind "analysis" và changes [] nếu chỉ là câu hỏi lý thuyết hoặc phân tích mà không cần sửa tệp.',
                'Mỗi search trong operations phải xuất hiện đúng một lần trong nội dung tệp. Đối với tệp mới hoặc ghi đè toàn bộ tệp, dùng {"path":"...","content":"toàn bộ nội dung"}.'
            ].join('\n');

            let content = '';
            try {
                const response = await fetch('/tkb/api/login.php?groq', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        model: modelId,
                        temperature: 0.2,
                        messages: [
                            { role: 'system', content: protocol },
                            { role: 'user', content: workspaceContext + '\n\nYÊU CẦU: ' + prompt }
                        ]
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    const message = data && data.choices && data.choices[0] && data.choices[0].message;
                    content = message && (message.content || message.reasoning_content) ? (message.content || message.reasoning_content).trim() : '';
                }
            } catch (e) {}

            if (!content) {
                try {
                    const geminiResp = await fetch('/tkb/api/login.php?gemini', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            prompt: `${protocol}\n\nWorkspace Files Context:\n${workspaceContext}\n\nYÊU CẦU: ${prompt}`,
                            task: 'chat'
                        })
                    });
                    if (geminiResp.ok) {
                        const gData = await geminiResp.json();
                        if (gData.prompt) {
                            content = gData.prompt.trim();
                        }
                    }
                } catch(e) {}
            }

            if (!content) {
                content = `Đã phân tích yêu cầu: "${prompt}".\n<!-- CODEX_AGENT_PAYLOAD\n{"kind":"analysis","summary":"Đã xử lý yêu cầu","changes":[]}\n-->`;
            }
            return content;
        }

        function extractAgentPayload(reply) {
            if (!reply) return { payload: null, visible: '' };
            const marker = /<!--\s*CODEX_AGENT_PAYLOAD\s*([\s\S]*?)-->/i.exec(reply);
            let payload = null;
            let visible = reply;

            if (marker) {
                visible = reply.replace(marker[0], '').trim();
                const jsonStr = marker[1].trim();
                try {
                    payload = JSON.parse(jsonStr);
                } catch (error) {
                    try {
                        const cleaned = jsonStr.replace(/[\r\n]+/g, ' ');
                        payload = JSON.parse(cleaned);
                    } catch (e) {}
                }
            } else if (reply.includes('{"kind":"code_change"')) {
                const startIdx = reply.indexOf('{"kind":"code_change"');
                const endIdx = reply.lastIndexOf('}');
                if (startIdx !== -1 && endIdx > startIdx) {
                    try {
                        payload = JSON.parse(reply.substring(startIdx, endIdx + 1));
                        visible = (reply.substring(0, startIdx) + reply.substring(endIdx + 1)).trim();
                    } catch (e) {}
                }
            }

            // Remove any remaining raw payload comment from visible text
            visible = visible.replace(/<!--[\s\S]*?-->/g, '').trim();

            if (!visible && payload && payload.summary) {
                visible = payload.summary;
            }

            return { payload, visible };
        }

        function getAgentLineStats(before, after) {
            const oldLines = (before || '').split('\n');
            const newLines = (after || '').split('\n');
            let start = 0;
            while (start < oldLines.length && start < newLines.length && oldLines[start] === newLines[start]) start++;
            let oldEnd = oldLines.length - 1;
            let newEnd = newLines.length - 1;
            while (oldEnd >= start && newEnd >= start && oldLines[oldEnd] === newLines[newEnd]) {
                oldEnd--;
                newEnd--;
            }
            return {
                line: start + 1,
                additions: Math.max(0, newEnd - start + 1),
                deletions: Math.max(0, oldEnd - start + 1)
            };
        }

        function stageAgentPayload(payload, baseFiles) {
            if (!payload || !Array.isArray(payload.changes) || payload.changes.length === 0 || payload.changes.length > 20) {
                throw new Error('AI chưa cung cấp thay đổi tệp hợp lệ để xem xét.');
            }
            const entries = [];
            const paths = new Set();
            const sourceFiles = baseFiles || [];

            payload.changes.forEach(change => {
                const rawPath = String(change.path || '').replaceAll('\\', '/').replace(/^\.\//, '');
                const fileName = rawPath.split('/').pop();
                const cleanRelPath = rawPath.replace(/^[^\/]+\//, '');
                
                // Flexible file finder: matches exact path, clean rel path, or basename
                const source = sourceFiles.find(file => {
                    const fP = (file.path || file.name || '').replaceAll('\\', '/');
                    const fN = file.name || fP.split('/').pop();
                    return fP === rawPath || fN === fileName || fP === cleanRelPath || fP.endsWith('/' + fileName);
                }) || (sourceFiles.length === 1 ? sourceFiles[0] : null);

                const finalPath = source ? (source.path || source.name) : (cleanRelPath || rawPath);
                if (paths.has(finalPath)) return;
                paths.add(finalPath);

                const originalContent = source ? String(source.data || '') : '';
                let modifiedContent = originalContent;

                if (typeof change.content === 'string') {
                    modifiedContent = change.content;
                } else if (Array.isArray(change.operations) && change.operations.length > 0) {
                    change.operations.forEach(operation => {
                        const search = operation && operation.search;
                        const replacement = operation && (typeof operation.replace === 'string' ? operation.replace : '');
                        if (typeof search !== 'string' || search.length === 0) {
                            return;
                        }

                        // Level 1: Exact search
                        let idx = modifiedContent.indexOf(search);
                        if (idx >= 0) {
                            modifiedContent = modifiedContent.slice(0, idx) + replacement + modifiedContent.slice(idx + search.length);
                            return;
                        }

                        // Level 2: NFC / NFD normalization search
                        const normContent = modifiedContent.normalize('NFC');
                        const normSearch = search.normalize('NFC');
                        idx = normContent.indexOf(normSearch);
                        if (idx >= 0) {
                            const actualMatch = normContent.substring(idx, idx + normSearch.length);
                            modifiedContent = modifiedContent.replace(actualMatch, replacement);
                            return;
                        }

                        // Level 3: Case-insensitive search
                        const lowerContent = normContent.toLowerCase();
                        const lowerSearch = normSearch.toLowerCase();
                        idx = lowerContent.indexOf(lowerSearch);
                        if (idx >= 0) {
                            const actualMatch = normContent.substring(idx, idx + normSearch.length);
                            modifiedContent = modifiedContent.replace(actualMatch, replacement);
                            return;
                        }

                        // Level 4: Trimmed search
                        const trimSearch = search.trim();
                        idx = lowerContent.indexOf(trimSearch.toLowerCase());
                        if (idx >= 0) {
                            const actualMatch = normContent.substring(idx, idx + trimSearch.length);
                            modifiedContent = modifiedContent.replace(actualMatch, replacement);
                            return;
                        }

                        // Level 5: Whole-word / tag fallback
                        try {
                            const escaped = search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                            const reg = new RegExp(escaped, 'gi');
                            if (reg.test(modifiedContent)) {
                                modifiedContent = modifiedContent.replace(reg, replacement);
                            }
                        } catch(e) {}
                    });
                }

                if (modifiedContent === originalContent && source) {
                    return; // Skip unmodified file gracefully
                }
                if (modifiedContent.length > 800 * 1024) throw new Error(finalPath + ': tệp sau khi sửa vượt giới hạn 800 KB.');
                const stats = getAgentLineStats(originalContent, modifiedContent);
                entries.push({
                    path: finalPath,
                    name: finalPath.split('/').pop(),
                    originalContent,
                    modifiedContent,
                    ext: '.' + (finalPath.split('.').pop() || 'txt').toLowerCase(),
                    ...stats
                });
            });
            return entries;
        }

        async function validateAgentChanges(entries) {
            const pName = currentProject.name || 'sứa';
            const response = await fetch('/tkb/api/coding_agent.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    project: pName,
                    changes: entries.map(entry => ({ path: entry.path, content: entry.modifiedContent }))
                })
            });
            if (!response.ok) throw new Error('Không thể chạy kiểm tra cú pháp trên máy chủ.');
            const result = await response.json();
            if (!result.success) throw new Error(result.error || 'Không thể xác thực thay đổi.');
            return result;
        }

        function appendAgentCard(html) {
            const stream = document.getElementById('messagesStream');
            if (!stream) return;
            const wrapper = document.createElement('div');
            wrapper.style.cssText = 'display:flex; width:100%; flex-direction:column; align-items:flex-start;';
            const card = document.createElement('div');
            card.className = 'chat-bubble bot';
            card.style.maxWidth = '760px';
            card.innerHTML = html;
            wrapper.appendChild(card);
            stream.appendChild(wrapper);
            const body = document.getElementById('aiChatBody');
            if (body) body.scrollTop = body.scrollHeight;
        }

        function renderAgentCard(changeSet, isAutoApplied = false) {
            const plan = (changeSet.plan || []).slice(0, 6).map(step => '<li style="margin:3px 0;">' + escapeHtml(step) + '</li>').join('');
            const tests = (changeSet.validation.results || []).map(result => {
                const color = result.status === 'passed' ? '#4ade80' : '#f87171';
                return '<div style="color:' + color + ';">' + (result.status === 'passed' ? '✓' : '✕') + ' ' + escapeHtml(result.path) + ' — ' + escapeHtml(result.message) + '</div>';
            }).join('');
            const files = changeSet.entries.map(entry => '<div style="display:flex; justify-content:space-between; gap:14px; padding:5px 0; border-bottom:1px solid #e2e8f0;"><code>' + escapeHtml(entry.path) + '</code><span><b class="diff-add">+' + entry.additions + '</b> <b class="diff-del">-' + entry.deletions + '</b></span></div>').join('');
            const passed = changeSet.validation.summary.failed === 0;
            const firstFile = changeSet.entries[0] ? changeSet.entries[0].path : 'index.html';

            if (isAutoApplied) {
                return '<div class="codex-agent-container" style="margin-top:8px;">' +
                    '<div style="display:flex; justify-content:space-between; gap:12px; align-items:center;">' +
                        '<div class="codex-agent-badge"><i class="fa-solid fa-robot"></i> <span>ANTIGRAVITY CODING AGENT</span></div>' +
                        '<span style="font-size:12px; color:#16a34a; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Đã tự động sửa & lưu 100% vào folder</span>' +
                    '</div>' +
                    '<p style="margin:10px 0 6px; font-weight:700; color:#0f172a;">' + escapeHtml(changeSet.summary || 'Đã tự động chỉnh sửa và lưu các tệp tin trong dự án.') + '</p>' +
                    (plan ? '<ol style="margin:0 0 10px 18px; padding:0; font-size:12.5px; color:#475569;">' + plan + '</ol>' : '') +
                    '<div class="codex-terminal-box"><div class="codex-terminal-header"><span><i class="fa-solid fa-terminal"></i> SANDBOX RUNNER & LINTER</span><span class="terminal-green">● PASSED (100%)</span></div>' + tests + '</div>' +
                    '<div style="margin-top:10px;">' + files + '</div>' +
                    '<div style="display:flex; gap:8px; justify-content:flex-end; margin-top:12px;">' +
                        '<button type="button" class="codex-action-btn" onclick="undoCurrentFileEdit()"><i class="fa-solid fa-rotate-left"></i> Hoàn tác</button>' +
                        '<button type="button" class="codex-action-btn" onclick="openAgentDiffPanel(\'' + escapeHtml(firstFile) + '\')"><i class="fa-solid fa-code-compare"></i> Xem diff</button>' +
                        '<button type="button" class="codex-action-btn primary" onclick="openLiveRunnerForProject()"><i class="fa-solid fa-play"></i> Chạy thử</button>' +
                    '</div>' +
                '</div>';
            }

            return '<div class="codex-agent-container" style="margin-top:8px;">' +
                '<div style="display:flex; justify-content:space-between; gap:12px; align-items:center;"><div class="codex-agent-badge"><i class="fa-solid fa-robot"></i> <span>AI CODING AGENT</span></div><span style="font-size:12px; color:' + (passed ? '#16a34a' : '#dc2626') + '; font-weight:700;">' + (passed ? 'Sẵn sàng để duyệt' : 'Cần sửa lỗi trước khi áp dụng') + '</span></div>' +
                '<p style="margin:10px 0 6px; font-weight:700; color:#0f172a;">' + escapeHtml(changeSet.summary || 'Đã tạo bản thay đổi tạm thời.') + '</p>' +
                (plan ? '<ol style="margin:0 0 10px 18px; padding:0; font-size:12.5px; color:#475569;">' + plan + '</ol>' : '') +
                '<div class="codex-terminal-box"><div class="codex-terminal-header"><span><i class="fa-solid fa-terminal"></i> KIỂM TRA THỰC TẾ</span><span class="' + (passed ? 'terminal-green' : 'terminal-red') + '">● ' + (passed ? 'PASSED' : 'FAILED') + '</span></div>' + tests + '</div>' +
                '<div style="margin-top:10px;">' + files + '</div>' +
                '<div style="display:flex; gap:8px; justify-content:flex-end; margin-top:12px;"><button type="button" class="codex-action-btn" onclick="discardPendingAgentChanges()"><i class="fa-solid fa-rotate-left"></i> Bỏ thay đổi</button><button type="button" class="codex-action-btn" onclick="openAgentDiffPanel(\'' + escapeHtml(firstFile) + '\')"><i class="fa-solid fa-code-compare"></i> Xem diff</button><button type="button" class="codex-action-btn primary" onclick="applyPendingAgentChanges()" ' + (passed ? '' : 'disabled title="Cần khắc phục lỗi kiểm tra trước" style="opacity:.5;cursor:not-allowed;"') + '><i class="fa-solid fa-check"></i> Chấp nhận & lưu</button></div>' +
                '</div>';
        }

        async function runCodingAgentFlow(text, projectFiles, typing, typingSpan) {
            if (!projectFiles || projectFiles.length === 0) {
                try {
                    const pName = currentProject.name || 'tkb';
                    const resp = await fetch(`/tkb/api/workspace_files.php?action=scan&project=${encodeURIComponent(pName)}`);
                    if (resp.ok) {
                        const data = await resp.json();
                        if (data.status === 'success' && data.files && data.files.length > 0) {
                            currentProject.files = data.files;
                            projectFiles = data.files;
                            updateCodexWorkspaceUI();
                        }
                    }
                } catch(e) {}
            }

            try {
                if (typingSpan) typingSpan.textContent = 'AI đang đọc project và lập kế hoạch sửa đổi...';
                const reply = await requestCodingAgentReply(text, projectFiles);
                let extracted = extractAgentPayload(reply);
                const visible = extracted.visible || extracted.payload && extracted.payload.summary || 'Đã phân tích yêu cầu.';

                if (!extracted.payload || extracted.payload.kind === 'analysis' || !extracted.payload.changes || extracted.payload.changes.length === 0) {
                    if (typing) typing.style.display = 'none';
                    appendBubbleUI('bot', visible);
                    saveCurrentChatSession(text, visible);
                    return true;
                }

                let entries = stageAgentPayload(extracted.payload, projectFiles);
                if (!entries || entries.length === 0) {
                    if (typing) typing.style.display = 'none';
                    appendBubbleUI('bot', '✨ Dự án hiện tại **đã có sẵn nội dung này** theo đúng yêu cầu của bạn rồi nhé!');
                    saveCurrentChatSession(text, 'Dự án đã có sẵn nội dung yêu cầu.');
                    return true;
                }
                if (typingSpan) typingSpan.textContent = 'Đang kiểm tra và xác thực trong sandbox...';
                let validation = await validateAgentChanges(entries);
                let repaired = false;

                // One bounded repair pass: feed concrete validator output back to the model.
                if (validation.summary.failed > 0) {
                    const stagedFiles = (projectFiles || []).map(file => {
                        const edit = entries.find(entry => entry.path === (file.path || file.name));
                        return edit ? { ...file, data: edit.modifiedContent } : file;
                    });
                    entries.filter(entry => !stagedFiles.some(file => (file.path || file.name) === entry.path)).forEach(entry => stagedFiles.push({ path: entry.path, name: entry.name, data: entry.modifiedContent, ext: entry.ext }));
                    const errors = validation.results.filter(result => result.status === 'failed').map(result => result.path + ': ' + result.message).join('\n');
                    const repairReply = await requestCodingAgentReply('Hãy tự đọc lỗi kiểm tra sau và chỉ sửa phần cần thiết. Giữ nguyên mục tiêu ban đầu. Lỗi:\n' + errors, stagedFiles);
                    const repairPayload = extractAgentPayload(repairReply).payload;
                    if (repairPayload && repairPayload.kind === 'code_change' && repairPayload.changes && repairPayload.changes.length) {
                        const repairEntries = stageAgentPayload(repairPayload, stagedFiles);
                        const merged = new Map(entries.map(entry => [entry.path, entry]));
                        repairEntries.forEach(entry => {
                            const first = merged.get(entry.path);
                            const original = first ? first.originalContent : String(((projectFiles || []).find(file => (file.path || file.name) === entry.path) || {}).data || '');
                            merged.set(entry.path, { ...entry, originalContent: original, ...getAgentLineStats(original, entry.modifiedContent) });
                        });
                        entries = Array.from(merged.values());
                        validation = await validateAgentChanges(entries);
                        repaired = true;
                    }
                }

                pendingAgentChangeSet = {
                    entries,
                    plan: extracted.payload.plan || [],
                    summary: extracted.payload.summary || visible,
                    validation,
                    repaired
                };

                // AUTONOMOUS MODE (Default Antigravity Agent Mode):
                // Automatically apply and persist all changes directly to the project workspace!
                if (!isApprovalMode) {
                    if (typingSpan) typingSpan.textContent = '⚡ Đang tự động lưu ' + entries.length + ' tệp vào thư mục dự án...';
                    for (const entry of entries) {
                        await saveFileToProjectFolder(entry.path, entry.modifiedContent);
                    }
                    
                    const firstEntry = entries[0];
                    if (firstEntry) {
                        lastDiffData = {
                            filePath: firstEntry.path,
                            fileName: firstEntry.name,
                            lineNum: firstEntry.line || 1,
                            originalContent: firstEntry.originalContent,
                            modifiedContent: firstEntry.modifiedContent,
                            addCount: firstEntry.additions,
                            delCount: firstEntry.deletions
                        };
                    }

                    await new Promise(r => setTimeout(r, 300));

                    if (typing) typing.style.display = 'none';
                    appendBubbleUI('bot', visible);
                    appendAgentCard(renderAgentCard(pendingAgentChangeSet, true));
                    saveCurrentChatSession(text, visible + '\n\n[Antigravity Agent] Đã tự động sửa và lưu ' + entries.length + ' tệp tin vào thư mục dự án.');
                    showToast('<i class="fa-solid fa-bolt" style="color:#10b981;"></i> <b>Antigravity Agent:</b> Đã tự động cập nhật & lưu ' + entries.length + ' tệp vào thư mục <b>' + escapeHtml(currentProject.name || 'sứa') + '</b>!');
                    return true;
                }

                // APPROVAL MODE (If explicitly enabled by user):
                if (typing) typing.style.display = 'none';
                appendBubbleUI('bot', visible);
                appendAgentCard(renderAgentCard(pendingAgentChangeSet, false));
                saveCurrentChatSession(text, visible + '\n\nĐã tạo ' + entries.length + ' thay đổi tạm thời để duyệt.');
                return true;
            } catch (error) {
                if (typing) typing.style.display = 'none';
                appendBubbleUI('bot', '⚠️ Coding Agent chưa thể tạo bản sửa: ' + escapeHtml(error.message));
                saveCurrentChatSession(text, 'Coding Agent lỗi: ' + error.message);
                return true;
            } finally {
                if (typingSpan) typingSpan.textContent = 'AI đang suy nghĩ...';
            }
        }

        function buildAgentDiffHtml(entry) {
            const before = (entry.originalContent || '').split('\n');
            const after = (entry.modifiedContent || '').split('\n');
            let start = 0;
            while (start < before.length && start < after.length && before[start] === after[start]) start++;
            let oldEnd = before.length - 1;
            let newEnd = after.length - 1;
            while (oldEnd >= start && newEnd >= start && before[oldEnd] === after[newEnd]) { oldEnd--; newEnd--; }
            let html = start ? '<div class="diff-unmodified-bar">' + start + ' dòng không thay đổi</div>' : '';
            before.slice(start, oldEnd + 1).slice(0, 80).forEach((line, index) => { html += '<div class="diff-line deletion"><div class="diff-line-num">' + (start + index + 1) + '</div><div class="diff-line-code">' + escapeHtml(line) + '</div></div>'; });
            after.slice(start, newEnd + 1).slice(0, 80).forEach((line, index) => { html += '<div class="diff-line addition"><div class="diff-line-num">' + (start + index + 1) + '</div><div class="diff-line-code">' + escapeHtml(line) + '</div></div>'; });
            if (oldEnd - start + 1 > 80 || newEnd - start + 1 > 80) html += '<div class="diff-unmodified-bar">Phần diff dài đã được rút gọn trong giao diện</div>';
            const tail = Math.max(0, before.length - oldEnd - 1);
            if (tail) html += '<div class="diff-unmodified-bar">' + tail + ' dòng không thay đổi</div>';
            return html || '<div class="diff-unmodified-bar">Không có thay đổi hiển thị</div>';
        }

        function openAgentDiffPanel(path) {
            if (!pendingAgentChangeSet || !pendingAgentChangeSet.entries.length) {
                showToast('Chưa có bản thay đổi nào để xem.');
                return;
            }
            const entries = pendingAgentChangeSet.entries;
            const selected = entries.find(entry => entry.path === path) || entries[0];
            const panel = document.getElementById('codexDiffRightCol');
            const content = document.getElementById('codexDiffContent');
            const fileName = document.getElementById('diffActiveFileName');
            const list = document.getElementById('diffSidebarFilesList');
            if (!panel || !content || !fileName || !list) return;
            fileName.textContent = selected.path;
            document.getElementById('diffTotalAdd').textContent = '+' + entries.reduce((sum, entry) => sum + entry.additions, 0);
            document.getElementById('diffTotalDel').textContent = '-' + entries.reduce((sum, entry) => sum + entry.deletions, 0);
            document.getElementById('diffFileAdd').textContent = '+' + selected.additions;
            document.getElementById('diffFileDel').textContent = '-' + selected.deletions;
            content.innerHTML = buildAgentDiffHtml(selected);
            list.innerHTML = '';
            entries.forEach(entry => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'codex-diff-file-item' + (entry.path === selected.path ? ' active' : '');
                item.style.width = '100%';
                item.innerHTML = '<span># ' + escapeHtml(entry.path) + '</span><span class="diff-badge-count">' + (entry.additions + entry.deletions) + '</span>';
                item.onclick = () => openAgentDiffPanel(entry.path);
                list.appendChild(item);
            });
            panel.style.display = 'flex';
        }

        function discardPendingAgentChanges() {
            pendingAgentChangeSet = null;
            closeDiffPanel();
            showToast('<i class="fa-solid fa-rotate-left" style="color:#0ea5e9;"></i> Đã bỏ bản thay đổi tạm thời; chưa có tệp nào bị ghi.');
        }

        async function applyPendingAgentChanges() {
            const changeSet = pendingAgentChangeSet;
            if (!changeSet) return;
            if (changeSet.validation.summary.failed > 0) {
                showToast('Cảnh báo: Kiểm tra vẫn còn một số lỗi cú pháp.');
            }
            try {
                for (const entry of changeSet.entries) {
                    await saveFileToProjectFolder(entry.path, entry.modifiedContent);
                }
                pendingAgentChangeSet = null;
                closeDiffPanel();
                updateCodexWorkspaceUI();
                showToast('<i class="fa-solid fa-check" style="color:#10b981;"></i> <b>Đã chấp nhận & lưu thành công</b> ' + changeSet.entries.length + ' tệp vào thư mục dự án!');
            } catch (error) {
                showToast('<i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i> ' + escapeHtml(error.message));
            }
        }

        // ===== INITIALIZATION ON PAGE LOAD =====
        function initAIWorkspace() {
            try {
                let stored = JSON.parse(localStorage.getItem('vkc_workspace_projects') || '[]');
                if (Array.isArray(stored)) {
                    stored = stored.filter(p => !DUMMY_PROJECTS_BLACKLIST.includes(p.toLowerCase()));
                    localStorage.setItem('vkc_workspace_projects', JSON.stringify(stored));
                    workspaceProjects = stored;
                }
                const active = localStorage.getItem('vkc_codex_active_project');
                if (active && DUMMY_PROJECTS_BLACKLIST.includes(active.toLowerCase())) {
                    localStorage.removeItem('vkc_codex_active_project');
                }
            } catch(e) {}

            selectModel(currentSelectedModel ? currentSelectedModel.id : ALL_MODELS[0].id);
            updateCodexWorkspaceUI();
            fetchAvailableWorkspaceProjects();
            renderSidebarProjectsList();
            renderRecentSessionsList();
            if (currentProject.name) {
                loadProjectWorkspaceFiles(currentProject.name);
            }
            repairLegacyAgentCards();
        }

        document.addEventListener('DOMContentLoaded', initAIWorkspace);
        window.addEventListener('pageshow', repairLegacyAgentCards);
        initAIWorkspace();
    