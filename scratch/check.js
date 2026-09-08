
        // ===== GLOBAL STATE =====
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
        function escapeHtml(str) {
            return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
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

        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function formatMarkdown(text) {
            if (!text) return '';

            // 1. Process <think>...</think> Reasoning Process (Antigravity Chain-of-Thought)
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
                const cleanLang = rawLang.toUpperCase();
                const ext = rawLang || 'txt';
                const trimmedCode = code.trim();
                const rawEscaped = encodeURIComponent(trimmedCode);
                
                const isRunnable = ['html', 'htm', 'javascript', 'js', 'css', 'python', 'py', 'php'].includes(rawLang);
                const runBtn = isRunnable ? `
                    <button type="button" class="ai-ide-btn ai-ide-btn-run" onclick="openLiveRunner('${rawEscaped}', '${rawLang}')">
                        <i class="fa-solid fa-play"></i> <span>Chạy thử</span>
                    </button>
                ` : '';

                const blockHtml = `
                    <div class="ai-ide-code-block">
                        <div class="ai-ide-toolbar">
                            <div style="display:flex; align-items:center; gap:8px; font-weight:700;">
                                <i class="fa-solid fa-code" style="color:#38bdf8;"></i>
                                <span style="color:#f8fafc; letter-spacing:0.5px;">${cleanLang}</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                ${runBtn}
                                <button type="button" class="ai-ide-btn" onclick="copyCodeBlock('${id}', this)">
                                    <i class="fa-solid fa-copy"></i> <span>Sao chép</span>
                                </button>
                                <button type="button" class="ai-ide-btn" onclick="downloadCodeFile('${rawEscaped}', 'solution_${Date.now()}.${ext}')">
                                    <i class="fa-solid fa-download"></i> <span>Tải file</span>
                                </button>
                            </div>
                        </div>
                        <pre style="margin:0; padding:14px 16px; overflow-x:auto; font-family:'Fira Code',Consolas,monospace; font-size:13px; line-height:1.6; color:#f8fafc;"><code id="${id}">${escapeHtml(trimmedCode)}</code></pre>
                    </div>
                `;
                codeBlocks.push(blockHtml);
                return `___CODE_BLOCK_${codeBlocks.length - 1}___`;
            });

            // 3. Process Markdown inline tokens with correct group replacements
            let html = escapeHtml(processedText);
            html = html.replace(/`([^`]+)`/g, '<code style="background:rgba(217,27,67,0.06); color:#d91b43; padding:2px 6px; border-radius:4px; font-family:monospace; font-size:12.5px; font-weight:600;"></code>');
            html = html.replace(/\*\*([^*]+)\*\*/g, '<strong></strong>');
            html = html.replace(/\*([^*]+)\*/g, '<em></em>');
            html = html.replace(/^### (.*$)/gim, '<h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:14px 0 6px;"></h3>');
            html = html.replace(/^## (.*$)/gim, '<h2 style="font-size:17px; font-weight:800; color:#0f172a; margin:16px 0 8px;"></h2>');
            html = html.replace(/^# (.*$)/gim, '<h1 style="font-size:19px; font-weight:800; color:#0f172a; margin:18px 0 10px;"></h1>');
            html = html.replace(/^[\*\-•]\s+(.*)$/gm, '<li style="margin-left:20px; list-style-type:disc; margin-bottom:4px;"></li>');
            html = html.replace(/\n/g, '<br>');

            // 4. Restore Code blocks
            codeBlocks.forEach((block, idx) => {
                html = html.replace(`___CODE_BLOCK_${idx}___`, block);
            });
            return html;
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

        function openLiveRunner(encodedCode, lang) {
            const code = decodeURIComponent(encodedCode);
            currentSandboxCode = code;
            currentSandboxLang = lang;

            const modal = document.getElementById('liveRunnerModal');
            const frame = document.getElementById('liveSandboxFrame');
            const consoleBox = document.getElementById('liveConsoleOutput');
            const title = document.getElementById('liveRunnerTitle');

            if (title) title.textContent = `Live Sandbox Runner (${lang.toUpperCase()})`;

            if (['html', 'htm', 'javascript', 'js', 'css'].includes(lang)) {
                if (frame) {
                    frame.style.display = 'block';
                    let docContent = code;
                    if (lang === 'javascript' || lang === 'js') {
                        docContent = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>JS Sandbox</title><style>body{font-family:sans-serif;padding:20px;color:#0f172a;}</style></head><body><div id="output"></div><script>try{ ${code} }catch(e){ document.body.innerHTML += '<div style="color:red;font-weight:bold;">Error: '+e.message+'</div>'; }<\/script></body></html>`;
                    } else if (lang === 'css') {
                        docContent = `<!DOCTYPE html><html><head><meta charset="utf-8"><style>${code}</style></head><body><div class="box"><h1>CSS Live Preview</h1><p>Demo style applied successfully!</p></div></body></html>`;
                    }
                    frame.srcdoc = docContent;
                }
                if (consoleBox) consoleBox.style.display = 'none';
            } else {
                if (frame) frame.style.display = 'none';
                if (consoleBox) {
                    consoleBox.style.display = 'block';
                    consoleBox.textContent = `=== [${lang.toUpperCase()} EMULATOR OUTPUT] ===\n\n>> Compiling and executing source code...\n\n` + code;
                }
            }

            if (modal) modal.style.display = 'flex';
        }

        function refreshLiveRunner() {
            if (currentSandboxCode && currentSandboxLang) {
                openLiveRunner(encodeURIComponent(currentSandboxCode), currentSandboxLang);
            }
        }

        function closeLiveRunner() {
            const modal = document.getElementById('liveRunnerModal');
            if (modal) modal.style.display = 'none';
        }

        // ===== SLASH COMMANDS LISTENER =====
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
                renderModelsList(ALL_MODELS);
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
                    const row = document.createElement('div');
                    row.className = 'md-model-row-white' + (currentSelectedModel && currentSelectedModel.id === m.id ? ' active' : '');
                    row.style.display = 'flex';
                    row.style.alignItems = 'center';
                    row.style.justifyContent = 'space-between';
                    row.style.padding = '8px 10px';
                    row.style.margin = '2px 0';
                    row.style.borderRadius = '8px';
                    row.style.cursor = 'pointer';
                    row.style.transition = 'all 0.15s ease';

                    row.onclick = () => selectModel(m.id);
                    row.onmouseenter = () => updateModelDetails(m);

                    row.innerHTML = `
                        <div class="md-item-left-white" style="display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-bolt" style="color:${g.iconColor}; font-size:13px;"></i>
                            <span style="font-weight:700; font-size:13px; color:#0f172a;">${m.name}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <span class="md-item-badge-white" style="color:#15803d; background:#dcfce7; border:1px solid #bbf7d0; font-size:10.5px; font-weight:800; padding:1px 6px; border-radius:6px;">✨ Free</span>
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
            if (title) title.textContent = m.name;
            if (desc) desc.textContent = m.desc || `${m.name} by ${m.provider || 'AI Provider'}.`;
            if (ctx) ctx.textContent = m.context || '128K Context';
        }

        function selectModel(modelId) {
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

        document.addEventListener('click', function(e) {
            const pop = document.getElementById('attachMenuPopover');
            const wrap = document.getElementById('attachWrapper');
            if (pop && pop.style.display === 'flex') {
                if (wrap && !wrap.contains(e.target)) {
                    pop.style.display = 'none';
                }
            }
        });

        // ===== FILE & FOLDER UPLOAD HANDLING =====
        async function handleFolderSelect(input) {
            const files = Array.from(input.files);
            if (files.length === 0) return;
            let folderName = 'Thư mục dự án';
            if (files[0].webkitRelativePath) {
                folderName = files[0].webkitRelativePath.split('/')[0];
            }
            await processIncomingFiles(files, folderName);
            input.value = '';
        }

        async function handleFileSelect(input) {
            const files = Array.from(input.files);
            await processIncomingFiles(files);
            input.value = '';
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
                summaryCard.innerHTML = `<i class="fa-solid fa-folder-tree" style="color:#10b981;"></i> <span>Đã chọn ${attachedFiles.length} tệp</span> <button type="button" onclick="clearAllAttachedFiles()" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:11px; margin-left:6px;"><i class="fa-solid fa-trash"></i> Xóa hết</button>`;
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

        // ===== SEND MESSAGE FUNCTION =====
        async function sendMsg() {
            const input = document.getElementById('userInput');
            const text = (input ? input.value : '').trim();
            if (!text && attachedFiles.length === 0) return;

            const folderMap = {};
            const standaloneFiles = [];
            attachedFiles.forEach(f => {
                const folderName = f.folder || (f.path && f.path.includes('/') ? f.path.split('/')[0] : null);
                if (folderName) {
                    if (!folderMap[folderName]) folderMap[folderName] = [];
                    folderMap[folderName].push(f);
                } else {
                    standaloneFiles.push(f);
                }
            });

            let displayHtml = escapeHtml(text);
            if (attachedFiles.length > 0) {
                let badgeHtml = '<div style="margin-top:8px; display:flex; flex-direction:column; gap:6px;">';
                for (let fName of Object.keys(folderMap)) {
                    const filesInFolder = folderMap[fName];
                    const totalKb = filesInFolder.reduce((sum, f) => sum + parseFloat(f.size || 0), 0).toFixed(1);
                    badgeHtml += `
                        <div style="display:inline-flex; align-items:center; gap:8px; padding:6px 12px; background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3); border-radius:10px; font-size:12.5px; font-weight:700;">
                            <i class="fa-solid fa-folder-open" style="color:#a7f3d0; font-size:14px;"></i>
                            <span>📂 Thư mục: ${fName} (${filesInFolder.length} tệp · ${totalKb} KB)</span>
                        </div>
                    `;
                }
                if (standaloneFiles.length > 0) {
                    badgeHtml += '<div style="display:flex; flex-wrap:wrap; gap:6px;">';
                    standaloneFiles.forEach(f => {
                        badgeHtml += `
                            <div style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); border-radius:8px; font-size:11.5px;">
                                <i class="fa-solid fa-file-code"></i>
                                <span>${f.name} (${f.size || ''})</span>
                            </div>
                        `;
                    });
                    badgeHtml += '</div>';
                }
                badgeHtml += '</div>';
                displayHtml += badgeHtml;
            }

            appendBubbleUI('user', displayHtml, true, true);
            if (input) {
                input.value = '';
                input.style.height = '38px';
            }

            const typing = document.getElementById('typingIndicator');
            if (typing) typing.style.display = 'block';
            const body = document.getElementById('aiChatBody');
            if (body) body.scrollTop = body.scrollHeight;

            let systemMsg = `You are VKC AI Assistant powered by ${currentSelectedModel ? currentSelectedModel.name : 'DeepSeek'}, an elite AI Software Engineer and Academic Tutor.
${customSystemPrompt ? `CUSTOM INSTRUCTIONS:\n${customSystemPrompt}\n` : ''}
${thinkingEnabled ? `Reasoning Level: ${reasoningEffort.toUpperCase()}. Think through problems step by step before answering.` : ''}
Answer all questions friendly, professionally, and clearly in Vietnamese with Markdown formatting and code blocks.`;

            const images = attachedFiles.filter(f => f.type === 'image');
            const texts = attachedFiles.filter(f => f.type === 'text');

            let joinedTextContent = '';
            if (texts.length > 0) {
                const hasFolders = texts.some(f => f.folder || (f.path && f.path.includes('/')));
                if (hasFolders) {
                    const treeList = texts.map(f => `  - ${f.path || f.name} (${f.size || ''})`).join('\n');
                    joinedTextContent = `=== TOÀN BỘ CẤU TRÚC THƯ MỤC DỰ ÁN (${texts.length} TỆP TIN) ===\n${treeList}\n\n=== CHI TIẾT NỘI DUNG CÁC TỆP TRONG DỰ ÁN ===\n\n`;
                    joinedTextContent += texts.map(f => `--- [Tệp: ${f.path || f.name}] ---\n\`\`\`${(f.ext || '').replace('.', '')}\n${f.data}\n\`\`\``).join('\n\n') + '\n\n';
                } else {
                    joinedTextContent = texts.map(f => `[Tệp đính kèm: ${f.path || f.name}]\n\`\`\`${(f.ext || '').replace('.', '')}\n${f.data}\n\`\`\``).join('\n\n') + '\n\n';
                }
            }

            const promptText = joinedTextContent + (text || 'Hãy phân tích toàn bộ cấu trúc mã nguồn và các tệp trong thư mục dự án trên.');
            let reqMessages = [];
            if (images.length > 0) {
                systemMsg += ' You can see and analyze the uploaded images.';
                const userContent = [{ type: 'text', text: promptText }];
                images.forEach(img => {
                    userContent.push({ type: 'image_url', image_url: { url: img.data } });
                });
                reqMessages = [{ role: 'system', content: systemMsg }, { role: 'user', content: userContent }];
            } else {
                reqMessages = [{ role: 'system', content: systemMsg }, { role: 'user', content: promptText }];
            }

            attachedFiles = [];
            renderPreviews();

            let reqModel = currentSelectedModel ? currentSelectedModel.id : 'deepseek/deepseek-chat-v3.1';

            try {
                const response = await fetch('/tkb/api/login.php?groq', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        model: reqModel,
                        messages: reqMessages,
                        temperature: 0.6
                    })
                });

                const data = await response.json();
                if (typing) typing.style.display = 'none';

                if (data.choices && data.choices[0] && data.choices[0].message) {
                    appendBubbleUI('bot', data.choices[0].message.content);
                } else if (data.error && data.error.message) {
                    let errText = data.error.message;
                    appendBubbleUI('bot', errText);
                } else {
                    appendBubbleUI('bot', 'Lỗi phản hồi từ trợ lý AI! Vui lòng thử lại sau.');
                }
            } catch (error) {
                if (typing) typing.style.display = 'none';
                appendBubbleUI('bot', 'Lỗi kết nối máy chủ AI! Vui lòng kiểm tra lại đường truyền mạng.');
            }
        }

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

            // Speak greeting sample in selected voice so user immediately hears the difference!
            if (v.sample) {
                setTimeout(() => {
                    generateSpeech(v.sample);
                }, 200);
            }
        }

        function generateSpeech(customText = null) {
            const input = document.getElementById('ttsInputText');
            const text = (customText !== null ? customText : (input ? input.value : '')).trim();
            if (!text) {
                alert('Vui lòng nhập nội dung cần đọc!');
                return;
            }
            if (input && customText !== null) {
                input.value = text;
                updateTtsCounter(input);
            }

            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utter = new SpeechSynthesisUtterance(text);
                
                // Calculate dynamic pitch & speed by combining voice profile and slider
                const basePitch = (currentVoice && currentVoice.pitch) ? currentVoice.pitch : 1.0;
                const baseRate = (currentVoice && currentVoice.rate) ? currentVoice.rate : 1.0;

                utter.pitch = Math.max(0.2, Math.min(2.0, basePitch * (ttsPitch || 1.0)));
                utter.rate = Math.max(0.4, Math.min(2.5, baseRate * (ttsRate || 1.0)));
                utter.volume = ttsVolume || 1.0;

                const targetLang = (currentVoice && currentVoice.lang) ? currentVoice.lang : 'vi';
                if (targetLang === 'vi') utter.lang = 'vi-VN';
                else if (targetLang === 'en-GB') utter.lang = 'en-GB';
                else if (targetLang.startsWith('en')) utter.lang = 'en-US';
                else if (targetLang === 'ja') utter.lang = 'ja-JP';
                else utter.lang = targetLang;

                // Match best system voice from browser synthesizer
                const voices = window.speechSynthesis.getVoices();
                if (voices && voices.length > 0) {
                    let matched = null;
                    if (targetLang.startsWith('vi')) {
                        matched = voices.find(v => v.lang.includes('vi') || v.name.includes('Vietnamese') || v.name.includes('HoaiMy') || v.name.includes('NamMinh'));
                    } else if (targetLang.startsWith('en')) {
                        if (currentVoice && currentVoice.gender === 'female') {
                            matched = voices.find(v => v.lang.startsWith('en') && (v.name.includes('Zira') || v.name.includes('Samantha') || v.name.includes('Jenny') || v.name.includes('Female') || v.name.includes('Google US English')));
                        } else {
                            matched = voices.find(v => v.lang.startsWith('en') && (v.name.includes('David') || v.name.includes('Guy') || v.name.includes('Male') || v.name.includes('George')));
                        }
                        if (!matched) matched = voices.find(v => v.lang.startsWith('en'));
                    } else if (targetLang === 'ja') {
                        matched = voices.find(v => v.lang.includes('ja') || v.name.includes('Japanese') || v.name.includes('Haruka') || v.name.includes('Ichiro'));
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
                    text: text.substring(0, 70) + (text.length > 70 ? '...' : ''),
                    fullText: text,
                    voice: currentVoice ? currentVoice.name : 'Giọng AI',
                    lang: targetLang,
                    time: new Date().toLocaleTimeString()
                });
                if (ttsHistory.length > 50) ttsHistory.pop();
                localStorage.setItem('vkc_tts_history', JSON.stringify(ttsHistory));
                renderTtsHistory();
            } else {
                alert('Trình duyệt của bạn không hỗ trợ Web Speech API.');
            }
        }

        function testVoiceSample() {
            generateSpeech();
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
            const lang = (item.lang === 'en' || (item.voice && item.voice.toLowerCase().includes('en'))) ? 'en' : 'vi';
            const audioUrl = `https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&tl=${lang}&q=${encodeURIComponent(content)}`;
            
            // Create temporary link to download audio
            const a = document.createElement('a');
            a.href = audioUrl;
            a.target = '_blank';
            a.download = `voice_${Date.now()}.mp3`;
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
                output += `[#${i+1}] Thời gian: ${h.time} | Giọng đọc: ${h.voice}\n`;
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
                        <button type="button" onclick="generateSpeech(\`${(item.fullText || item.text).replace(/`/g, '\`')}\`)" style="width:32px; height:32px; border-radius:8px; background:#10b981; color:#fff; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:12px; transition:all 0.15s;" title="Nghe lại">
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

        // ===== INITIALIZATION ON PAGE LOAD =====
        function initAIWorkspace() {
            selectModel(currentSelectedModel ? currentSelectedModel.id : ALL_MODELS[0].id);
        }

        document.addEventListener('DOMContentLoaded', initAIWorkspace);
        initAIWorkspace();
    