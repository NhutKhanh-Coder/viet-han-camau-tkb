<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// Remove existing voiceModalOverlay from bottom if any
$aiPhp = preg_replace('/<!-- CHỌN GIỌNG.*?<\/div>\s*<\/div>/s', '', $aiPhp);

// Define modal HTML
$modalHtml = '
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
    </div>';

// Insert before `<script>`
$aiPhp = str_replace('<script>', $modalHtml . "\n\n    <script>", $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Cleaned and repositioned Voice Modal HTML successfully!\n";
