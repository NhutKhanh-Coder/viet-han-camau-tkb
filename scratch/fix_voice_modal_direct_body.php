<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// Remove existing voiceModalOverlay from anywhere in the file
$aiPhp = preg_replace('/<!-- CHỌN GIỌNG.*?<\/div>\s*<\/div>\s*<\/div>/s', '', $aiPhp);
$aiPhp = preg_replace('/<!-- CHỌN GIỌNG.*?<\/div>\s*<\/div>/s', '', $aiPhp);

// Define modal HTML with bulletproof inline styles
$modalHtml = '
    <!-- CHỌN GIỌNG (VOICE SELECTOR) MODAL (EXACT MATCH XKiro DARK FLOATING MODAL) -->
    <div class="voice-modal-overlay" id="voiceModalOverlay" onclick="if(event.target===this) closeVoiceModal()" style="display:none; position:fixed !important; top:0 !important; left:0 !important; width:100vw !important; height:100vh !important; background:rgba(0,0,0,0.75) !important; z-index:999999999 !important; align-items:center !important; justify-content:center !important; backdrop-filter:blur(6px) !important;">
        <div class="voice-modal-box" style="width:850px; max-width:94vw; height:630px; max-height:90vh; background:#18181b; border:1px solid #27272a; border-radius:18px; box-shadow:0 30px 90px rgba(0,0,0,0.85); display:flex; flex-direction:column; overflow:hidden; font-family:\'Outfit\',sans-serif; color:#f4f4f5; position:relative; z-index:1000000000;">
            <div class="voice-modal-header" style="padding:16px 22px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #27272a; background:#18181b;">
                <h3 class="voice-modal-title" style="margin:0; font-size:17px; font-weight:700; color:#ffffff; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-volume-high" style="color:#10b981;"></i>
                    <span>Chọn giọng</span>
                </h3>
                <button type="button" class="voice-modal-close-btn" onclick="closeVoiceModal()" style="background:transparent; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#a1a1aa; cursor:pointer; font-size:16px;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="voice-modal-search-row" style="padding:14px 22px 8px; background:#18181b;">
                <input type="text" class="voice-search-input" id="voiceSearchInput" placeholder="🔍 Tìm giọng..." oninput="filterVoices(this.value)" style="width:100%; padding:10px 16px; background:#121214; border:1.5px solid #27272a; border-radius:10px; font-size:13.5px; color:#ffffff; outline:none; box-sizing:border-box; font-family:inherit;">
            </div>

            <div class="voice-filter-pills" id="voiceFilterPills" style="padding:6px 22px 14px; display:flex; align-items:center; gap:8px; overflow-x:auto; border-bottom:1px solid #27272a; background:#18181b;">
                <button type="button" class="voice-filter-pill active" onclick="setVoiceCategory(\'all\', this)">Tất cả</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'trending\', this)">🔥 Thịnh hành</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'en\', this)">Tiếng Anh</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'vi\', this)">Tiếng Việt</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'female\', this)">Nữ</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'male\', this)">Nam</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'character\', this)">Nhân vật</button>
                <button type="button" class="voice-filter-pill" onclick="setVoiceCategory(\'meme\', this)">Bài hát Meme</button>
            </div>

            <div class="voice-cards-grid" id="voiceCardsGrid" style="flex:1; padding:18px 22px; overflow-y:auto; display:grid; grid-template-columns:repeat(5, 1fr); gap:14px; background:#18181b;">
                <!-- Dynamically populated voice cards -->
            </div>
        </div>
    </div>';

// Insert directly before </body>
$aiPhp = str_replace('</body>', $modalHtml . "\n</body>", $aiPhp);

// Update openVoiceModal and closeVoiceModal to use setProperty important
$jsOpenClose = '
        window.openVoiceModal = function() {
            const modal = document.getElementById(\'voiceModalOverlay\');
            if (modal) {
                modal.style.setProperty(\'display\', \'flex\', \'important\');
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
                modal.style.setProperty(\'display\', \'none\', \'important\');
                modal.classList.remove(\'show\');
            }
        };';

$aiPhp = preg_replace('/window\.openVoiceModal\s*=\s*function.*?window\.closeVoiceModal\s*=\s*function.*?};/s', $jsOpenClose, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully fixed Voice Modal to be a direct child of <body> with full fixed overlay!\n";
