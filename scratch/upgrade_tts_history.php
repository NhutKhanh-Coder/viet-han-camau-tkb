<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Update ttsHistoryPanel HTML with header controls
$oldHistoryPanel = '                    <!-- History Panel -->
                    <div class="ai-tts-panel-content" id="ttsHistoryPanel" style="display:none;">
                        <div id="ttsHistoryList" style="display:flex; flex-direction:column; gap:10px;">
                            <!-- Populated dynamically -->
                        </div>
                    </div>';

$newHistoryPanel = '                    <!-- History Panel -->
                    <div class="ai-tts-panel-content" id="ttsHistoryPanel" style="display:none;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid #e2e8f0;">
                            <span style="font-size:12.5px; font-weight:800; color:#475569;" id="ttsHistoryCountText">Lịch sử đọc</span>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <button type="button" onclick="exportAllTtsHistory()" style="padding:4px 8px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:6px; font-size:11px; font-weight:700; color:#334155; cursor:pointer; display:flex; align-items:center; gap:4px;" title="Tải toàn bộ lịch sử thành file .txt">
                                    <i class="fa-solid fa-file-arrow-down" style="color:#0ea5e9;"></i> <span>Xuất file</span>
                                </button>
                                <button type="button" onclick="clearAllTtsHistory()" style="padding:4px 8px; background:#fee2e2; border:1px solid #fecaca; border-radius:6px; font-size:11px; font-weight:700; color:#dc2626; cursor:pointer; display:flex; align-items:center; gap:4px;" title="Xóa tất cả lịch sử">
                                    <i class="fa-solid fa-trash"></i> <span>Xóa hết</span>
                                </button>
                            </div>
                        </div>
                        <div id="ttsHistoryList" style="display:flex; flex-direction:column; gap:10px;">
                            <!-- Populated dynamically -->
                        </div>
                    </div>';

$aiPhp = str_replace($oldHistoryPanel, $newHistoryPanel, $aiPhp);

// 2. Update JavaScript for generateSpeech and renderTtsHistory
$oldTtsFunctions = '        function generateSpeech() {
            const input = document.getElementById(\'ttsInputText\');
            const text = (input ? input.value : \'\').trim();
            if (!text) {
                alert(\'Vui lòng nhập nội dung cần đọc!\');
                return;
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

                // Add to history
                ttsHistory.unshift({
                    text: text.substring(0, 80) + (text.length > 80 ? \'...\' : \'\'),
                    voice: currentVoice.name,
                    time: new Date().toLocaleTimeString()
                });
                if (ttsHistory.length > 20) ttsHistory.pop();
                localStorage.setItem(\'vkc_tts_history\', JSON.stringify(ttsHistory));
                renderTtsHistory();
            } else {
                alert(\'Trình duyệt của bạn không hỗ trợ Web Speech API.\');
            }
        }

        function testVoiceSample() {
            generateSpeech();
        }

        function switchTtsRightTab(tab) {
            const btnSettings = document.getElementById(\'btnTtsSettingsTab\');
            const btnHistory = document.getElementById(\'btnTtsHistoryTab\');
            const pSettings = document.getElementById(\'ttsSettingsPanel\');
            const pHistory = document.getElementById(\'ttsHistoryPanel\');

            if (tab === \'settings\') {
                if (btnSettings) btnSettings.classList.add(\'active\');
                if (btnHistory) btnHistory.classList.remove(\'active\');
                if (pSettings) pSettings.style.display = \'block\';
                if (pHistory) pHistory.style.display = \'none\';
            } else {
                if (btnHistory) btnHistory.classList.add(\'active\');
                if (btnSettings) btnSettings.classList.remove(\'active\');
                if (pHistory) pHistory.style.display = \'block\';
                if (pSettings) pSettings.style.display = \'none\';
                renderTtsHistory();
            }
        }

        function updateTtsCounter(textarea) {
            const counter = document.getElementById(\'ttsCounterText\');
            if (counter && textarea) {
                counter.textContent = `${textarea.value.length}/4.000`;
            }
        }

        function renderTtsHistory() {
            const list = document.getElementById(\'ttsHistoryList\');
            if (!list) return;
            list.innerHTML = \'\';
            if (ttsHistory.length === 0) {
                list.innerHTML = \'<div style="font-size:13px; color:#64748b; text-align:center; padding:20px 0;">Chưa có lịch sử đọc văn bản.</div>\';
                return;
            }
            ttsHistory.forEach(item => {
                const row = document.createElement(\'div\');
                row.style.padding = \'10px 12px\';
                row.style.background = \'#f8fafc\';
                row.style.border = \'1px solid #e2e8f0\';
                row.style.borderRadius = \'10px\';
                row.style.display = \'flex\';
                row.style.justifyContent = \'space-between\';
                row.style.alignItems = \'center\';
                row.innerHTML = `
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#0f172a;">${item.text}</div>
                        <div style="font-size:11px; color:#64748b; margin-top:2px;">${item.voice} · ${item.time}</div>
                    </div>
                    <button type="button" onclick="document.getElementById(\'ttsInputText\').value = \'${item.text}\'; generateSpeech();" style="background:#10b981; color:#fff; border:none; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer;">
                        <i class="fa-solid fa-play"></i>
                    </button>
                `;
                list.appendChild(row);
            });
        }';

$newTtsFunctions = '        function generateSpeech(customText = null) {
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
        }

        function testVoiceSample() {
            generateSpeech();
        }

        function switchTtsRightTab(tab) {
            const btnSettings = document.getElementById(\'btnTtsSettingsTab\');
            const btnHistory = document.getElementById(\'btnTtsHistoryTab\');
            const pSettings = document.getElementById(\'ttsSettingsPanel\');
            const pHistory = document.getElementById(\'ttsHistoryPanel\');

            if (tab === \'settings\') {
                if (btnSettings) btnSettings.classList.add(\'active\');
                if (btnHistory) btnHistory.classList.remove(\'active\');
                if (pSettings) pSettings.style.display = \'block\';
                if (pHistory) pHistory.style.display = \'none\';
            } else {
                if (btnHistory) btnHistory.classList.add(\'active\');
                if (btnSettings) btnSettings.classList.remove(\'active\');
                if (pHistory) pHistory.style.display = \'block\';
                if (pSettings) pSettings.style.display = \'none\';
                renderTtsHistory();
            }
        }

        function updateTtsCounter(textarea) {
            const counter = document.getElementById(\'ttsCounterText\');
            if (counter && textarea) {
                counter.textContent = `${textarea.value.length}/4.000`;
            }
        }

        function deleteTtsHistoryItem(index) {
            ttsHistory.splice(index, 1);
            localStorage.setItem(\'vkc_tts_history\', JSON.stringify(ttsHistory));
            renderTtsHistory();
        }

        function clearAllTtsHistory() {
            if (ttsHistory.length === 0) return;
            if (confirm(\'Bạn có chắc chắn muốn xóa toàn bộ lịch sử đọc văn bản không?\')) {
                ttsHistory = [];
                localStorage.setItem(\'vkc_tts_history\', JSON.stringify(ttsHistory));
                renderTtsHistory();
            }
        }

        function downloadTtsAudio(index) {
            const item = ttsHistory[index];
            if (!item) return;
            const content = item.fullText || item.text;
            const lang = (item.lang === \'en\' || (item.voice && item.voice.toLowerCase().includes(\'en\'))) ? \'en\' : \'vi\';
            const audioUrl = `https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&tl=${lang}&q=${encodeURIComponent(content)}`;
            
            // Create temporary link to download audio
            const a = document.createElement(\'a\');
            a.href = audioUrl;
            a.target = \'_blank\';
            a.download = `voice_${Date.now()}.mp3`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        function exportAllTtsHistory() {
            if (ttsHistory.length === 0) {
                alert(\'Chưa có dữ liệu lịch sử để xuất!\');
                return;
            }
            let output = \'=== LỊCH SỬ ĐỌC VĂN BẢN (TTS STUDIO) ===\\n\\n\';
            ttsHistory.forEach((h, i) => {
                output += `[#${i+1}] Thời gian: ${h.time} | Giọng đọc: ${h.voice}\\n`;
                output += `Nội dung: ${h.fullText || h.text}\\n\\n`;
            });
            const blob = new Blob([output], { type: \'text/plain;charset=utf-8\' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement(\'a\');
            a.href = url;
            a.download = `tts_history_${Date.now()}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function renderTtsHistory() {
            const list = document.getElementById(\'ttsHistoryList\');
            const countText = document.getElementById(\'ttsHistoryCountText\');
            if (!list) return;
            list.innerHTML = \'\';

            if (countText) countText.textContent = `Lịch sử đọc (${ttsHistory.length})`;

            if (ttsHistory.length === 0) {
                list.innerHTML = \'<div style="font-size:13px; color:#94a3b8; text-align:center; padding:30px 0;"><i class="fa-solid fa-clock-rotate-left" style="font-size:24px; color:#cbd5e1; margin-bottom:8px; display:block;"></i>Chưa có lịch sử đọc văn bản.</div>\';
                return;
            }

            ttsHistory.forEach((item, index) => {
                const row = document.createElement(\'div\');
                row.style.padding = \'12px 14px\';
                row.style.background = \'#ffffff\';
                row.style.border = \'1.5px solid #e2e8f0\';
                row.style.borderRadius = \'12px\';
                row.style.display = \'flex\';
                row.style.justifyContent = \'space-between\';
                row.style.alignItems = \'center\';
                row.style.boxShadow = \'0 2px 6px rgba(0,0,0,0.03)\';
                row.style.transition = \'all 0.15s ease\';

                const fullContent = (item.fullText || item.text).replace(/"/g, \'&quot;\');

                row.innerHTML = `
                    <div style="flex:1; min-width:0; padding-right:12px;">
                        <div style="font-size:13.5px; font-weight:700; color:#0f172a; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${fullContent}">${escapeHtml(item.text)}</div>
                        <div style="font-size:11px; color:#64748b; margin-top:3px; display:flex; align-items:center; gap:6px;">
                            <span style="background:#f1f5f9; padding:1px 6px; border-radius:4px; font-weight:600; color:#475569;">${escapeHtml(item.voice || \'Giọng đọc\')}</span>
                            <span>${item.time}</span>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                        <!-- Play Button -->
                        <button type="button" onclick="generateSpeech(\`${(item.fullText || item.text).replace(/`/g, \'\\`\')}\`)" style="width:32px; height:32px; border-radius:8px; background:#10b981; color:#fff; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:12px; transition:all 0.15s;" title="Nghe lại">
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
        }';

$aiPhp = str_replace($oldTtsFunctions, $newTtsFunctions, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully upgraded TTS History with delete, download audio, and clear-all features!\n";
