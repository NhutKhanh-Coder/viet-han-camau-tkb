<?php
$models = json_decode(file_get_contents(__DIR__ . '/formatted_87_models_tagged.json'), true);

$freeModels = array_values(array_filter($models, function($m) {
    return empty($m['isPaid']);
}));

echo "Total Free models: " . count($freeModels) . "\n";
foreach ($freeModels as $fm) {
    echo "• {$fm['name']} ({$fm['id']})\n";
}

file_put_contents(__DIR__ . '/free_models_only.json', json_encode($freeModels, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

// Use restore_full_ai_script.php logic but using free_models_only.json!
$modelsJson = json_encode($freeModels, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$scriptCode = <<< 'EOD'
<script>
        // ===== GLOBAL STATE =====
        let attachedFiles = [];
        let customSystemPrompt = localStorage.getItem('vkc_ai_system_prompt') || '';
        let thinkingEnabled = true;
        let reasoningEffort = 'high';
        let ttsRate = 1.0;
        let ttsPitch = 1.0;
        let ttsVolume = 1.0;
        let activeCategory = 'all';
        let currentVoice = { id: 'thuy_tien', name: 'Thuỳ Tiên (Nữ Bắc)', sub: 'vi · female · Miễn phí', icon: 'fa-microphone', lang: 'vi' };
        let ttsHistory = JSON.parse(localStorage.getItem('vkc_tts_history') || '[]');

        // ===== 100% FREE xKiro AI MODELS DATASET =====
        const ALL_MODELS = %%MODELS_JSON%%;
        let currentSelectedModel = ALL_MODELS.find(m => m.name.includes('DeepSeek V3.1')) || ALL_MODELS[0];

        // ===== VOICES DATASET =====
        const ALL_VOICES = [
            { id: 'thuy_tien', name: 'Thuỳ Tiên', sub: 'Nữ Miền Bắc (Chuẩn)', lang: 'vi', gender: 'female', isTrending: true },
            { id: 'mai_phuong', name: 'Mai Phương', sub: 'Nữ Miền Nam (Ngọt ngào)', lang: 'vi', gender: 'female', isTrending: true },
            { id: 'tuan_hung', name: 'Tuấn Hùng', sub: 'Nam Miền Bắc (Trầm ấm)', lang: 'vi', gender: 'male', isTrending: true },
            { id: 'quang_dung', name: 'Quang Dũng', sub: 'Nam Miền Nam (Truyền cảm)', lang: 'vi', gender: 'male', isTrending: false },
            { id: 'huong_giang', name: 'Hương Giang', sub: 'Nữ Miền Trung (Dịu dàng)', lang: 'vi', gender: 'female', isTrending: false },
            { id: 'en_sarah', name: 'Sarah', sub: 'American English (Warm)', lang: 'en', gender: 'female', isTrending: true },
            { id: 'en_alex', name: 'Alex', sub: 'American English (Professional)', lang: 'en', gender: 'male', isTrending: false },
            { id: 'en_emma', name: 'Emma', sub: 'British English (Refined)', lang: 'en', gender: 'female', isTrending: true },
            { id: 'en_david', name: 'David', sub: 'British English (Deep)', lang: 'en', gender: 'male', isTrending: false },
            { id: 'char_bot', name: 'CyberBot 3000', sub: 'Sci-Fi AI Assistant', lang: 'character', gender: 'male', isTrending: true },
            { id: 'char_fairy', name: 'Tiên Nữ', sub: 'Giọng cổ tích, huyền ảo', lang: 'character', gender: 'female', isTrending: false },
            { id: 'meme_rick', name: 'Rickroll Astley', sub: 'Never Gonna Give You Up', lang: 'meme', gender: 'male', isTrending: true }
        ];

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

        function formatMarkdown(text) {
            if (!text) return '';
            const codeBlocks = [];
            let processedText = text.replace(/```([a-zA-Z0-9_-]*)\n?([\s\S]*?)```/g, function(match, lang, code) {
                const id = 'code_' + Math.random().toString(36).substr(2, 9);
                const cleanLang = (lang || 'code').toUpperCase();
                const ext = lang ? lang.toLowerCase() : 'txt';
                const rawEscaped = encodeURIComponent(code.trim());
                
                const blockHtml = `
                    <div class="ai-ide-code-block" style="margin:14px 0; background:#0f172a; border:1.5px solid #1e293b; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.15);">
                        <div class="ai-ide-toolbar" style="display:flex; align-items:center; justify-content:space-between; padding:8px 14px; background:#1e293b; border-bottom:1px solid rgba(255,255,255,0.08); font-size:12px; color:#94a3b8;">
                            <div style="display:flex; align-items:center; gap:8px; font-weight:700;">
                                <i class="fa-solid fa-code" style="color:#38bdf8;"></i>
                                <span style="color:#f8fafc; letter-spacing:0.5px;">${cleanLang}</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <button type="button" class="ai-ide-btn" onclick="copyCodeBlock('${id}', this)" style="background:rgba(255,255,255,0.08); color:#f8fafc; border:1px solid rgba(255,255,255,0.12); padding:4px 10px; border-radius:6px; font-size:11.5px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:4px;">
                                    <i class="fa-solid fa-copy"></i> <span>Sao chép</span>
                                </button>
                                <button type="button" class="ai-ide-btn" onclick="downloadCodeFile('${rawEscaped}', 'snippet_${Date.now()}.${ext}')" style="background:#10b981; color:#fff; border:none; padding:4px 10px; border-radius:6px; font-size:11.5px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:4px;">
                                    <i class="fa-solid fa-download"></i> <span>Tải file</span>
                                </button>
                            </div>
                        </div>
                        <pre style="margin:0; padding:14px 16px; overflow-x:auto; font-family:'Fira Code',Consolas,monospace; font-size:13px; line-height:1.6; color:#f8fafc;"><code id="${id}">${escapeHtml(code.trim())}</code></pre>
                    </div>
                `;
                codeBlocks.push(blockHtml);
                return `___CODE_BLOCK_${codeBlocks.length - 1}___`;
            });

            let html = escapeHtml(processedText);
            html = html.replace(/`([^`]+)`/g, '<code style="background:rgba(217,27,67,0.06); color:#d91b43; padding:2px 6px; border-radius:4px; font-family:monospace; font-size:12.5px; font-weight:600;">$1</code>');
            html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/^[\*\-•]\s+(.*)$/gm, '<li style="margin-left:20px; list-style-type:disc;">$1</li>');
            html = html.replace(/\n/g, '<br>');

            codeBlocks.forEach((block, idx) => {
                html = html.replace(`___CODE_BLOCK_${idx}___`, block);
            });
            return html;
        }

        function appendBubbleUI(role, text, save = true, isHtml = false) {
            const hero = document.getElementById('heroWelcome');
            if (hero) hero.style.display = 'none';
            const stream = document.getElementById('messagesStream');
            if (stream) stream.style.display = 'flex';

            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble ' + (role === 'user' ? 'user' : 'bot');
            if (role === 'user') {
                if (isHtml) bubble.innerHTML = text;
                else bubble.innerText = text;
            } else {
                bubble.innerHTML = formatMarkdown(text);
            }
            stream.appendChild(bubble);

            const body = document.getElementById('aiChatBody');
            if (body) body.scrollTop = body.scrollHeight;
        }

        // ===== MODE SWITCHING =====
        function switchMode(mode, btn) {
            document.querySelectorAll('.ai-mode-tab').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const chatView = document.getElementById('aiChatView');
            const ttsView = document.getElementById('aiTtsView');

            if (mode === 'chat') {
                if (chatView) chatView.style.display = 'flex';
                if (ttsView) ttsView.style.display = 'none';
            } else if (mode === 'tts') {
                if (chatView) chatView.style.display = 'none';
                if (ttsView) ttsView.style.display = 'flex';
            } else if (mode === 'image') {
                alert('Tính năng Tạo ảnh AI đang được cập nhật!');
                switchMode('chat', document.getElementById('tabChat'));
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

        function renderModelsList(models) {
            const container = document.getElementById('modelsListContainer');
            if (!container) return;
            container.innerHTML = '';

            models.forEach(m => {
                const row = document.createElement('div');
                row.className = 'md-model-row-white' + (currentSelectedModel && currentSelectedModel.id === m.id ? ' active' : '');
                row.onclick = () => selectModel(m.id);
                row.onmouseenter = () => updateModelDetails(m);

                row.innerHTML = `
                    <div class="md-item-left-white" style="display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-bolt" style="color:#0ea5e9;"></i>
                        <span style="font-weight:700; font-size:13px; color:#0f172a;">${m.name}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:5px;">
                        <span class="md-item-badge-white" style="color:#15803d; background:#dcfce7; border-color:#bbf7d0; font-size:11px; font-weight:700; padding:2px 7px; border-radius:6px;">✨ Free</span>
                        <span class="md-item-badge-white" style="font-size:11px; padding:2px 6px; border-radius:6px; background:#f1f5f9; color:#475569;">${m.badge || '128K'}</span>
                    </div>
                `;
                container.appendChild(row);
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
            else if (activeCategory !== 'all') filtered = filtered.filter(v => v.lang === activeCategory || v.gender === activeCategory);

            if (filterQuery) {
                filtered = filtered.filter(v => v.name.toLowerCase().includes(filterQuery) || v.sub.toLowerCase().includes(filterQuery));
            }

            filtered.forEach(v => {
                const isSel = currentVoice && currentVoice.id === v.id;
                const card = document.createElement('div');
                card.style.padding = '14px';
                card.style.background = isSel ? 'rgba(16, 185, 129, 0.15)' : '#27272a';
                card.style.border = isSel ? '1.5px solid #10b981' : '1px solid #3f3f46';
                card.style.borderRadius = '12px';
                card.style.cursor = 'pointer';
                card.style.display = 'flex';
                card.style.flexDirection = 'column';
                card.style.alignItems = 'center';
                card.style.textAlign = 'center';
                card.style.gap = '8px';
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
        }

        function selectVoice(v) {
            currentVoice = v;
            const nameEl = document.getElementById('activeVoiceName');
            const subEl = document.getElementById('activeVoiceSub');
            if (nameEl) nameEl.textContent = v.name;
            if (subEl) subEl.textContent = `${v.lang} · ${v.gender || 'neutral'} · Miễn phí`;
            closeVoiceModal();
        }

        function generateSpeech() {
            const input = document.getElementById('ttsInputText');
            const text = (input ? input.value : '').trim();
            if (!text) {
                alert('Vui lòng nhập nội dung cần đọc!');
                return;
            }
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utter = new SpeechSynthesisUtterance(text);
                utter.rate = ttsRate;
                utter.pitch = ttsPitch;
                utter.volume = ttsVolume;
                if (currentVoice.lang === 'vi') utter.lang = 'vi-VN';
                else if (currentVoice.lang === 'en') utter.lang = 'en-US';
                window.speechSynthesis.speak(utter);

                // Add to history
                ttsHistory.unshift({
                    text: text.substring(0, 80) + (text.length > 80 ? '...' : ''),
                    voice: currentVoice.name,
                    time: new Date().toLocaleTimeString()
                });
                if (ttsHistory.length > 20) ttsHistory.pop();
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

        function renderTtsHistory() {
            const list = document.getElementById('ttsHistoryList');
            if (!list) return;
            list.innerHTML = '';
            if (ttsHistory.length === 0) {
                list.innerHTML = '<div style="font-size:13px; color:#64748b; text-align:center; padding:20px 0;">Chưa có lịch sử đọc văn bản.</div>';
                return;
            }
            ttsHistory.forEach(item => {
                const row = document.createElement('div');
                row.style.padding = '10px 12px';
                row.style.background = '#f8fafc';
                row.style.border = '1px solid #e2e8f0';
                row.style.borderRadius = '10px';
                row.style.display = 'flex';
                row.style.justifyContent = 'space-between';
                row.style.alignItems = 'center';
                row.innerHTML = `
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#0f172a;">${item.text}</div>
                        <div style="font-size:11px; color:#64748b; margin-top:2px;">${item.voice} · ${item.time}</div>
                    </div>
                    <button type="button" onclick="document.getElementById('ttsInputText').value = '${item.text}'; generateSpeech();" style="background:#10b981; color:#fff; border:none; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer;">
                        <i class="fa-solid fa-play"></i>
                    </button>
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
    </script>
EOD;

$scriptCode = str_replace('%%MODELS_JSON%%', $modelsJson, $scriptCode);

$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$pattern = '/<script>.*?<\/script>/s';
$aiPhp = preg_replace($pattern, $scriptCode, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully filtered out all paid models and updated student/ai.php!\n";
