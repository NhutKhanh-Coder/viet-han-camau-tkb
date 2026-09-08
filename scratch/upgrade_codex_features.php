<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Upgrade formatMarkdown to render Antigravity / Codex style code blocks with Copy & Download buttons
$oldFormatMarkdown = 'function formatMarkdown(text) {
            if (!text) return \'\';
            let html = text.replace(/&/g, \'&amp;\').replace(/</g, \'&lt;\').replace(/>/g, \'&gt;\');
            // Code block
            html = html.replace(/```([\\s\\S]*?)```/g, \'<pre style="background:#0f172a; color:#f8fafc; padding:14px; border-radius:10px; overflow-x:auto; margin:10px 0; font-family:monospace; font-size:13px; border:1px solid #1e293b;"><code>$1</code></pre>\');
            // Inline code
            html = html.replace(/`([^`]+)`/g, \'<code style="background:rgba(217,27,67,0.06); color:#d91b43; padding:2px 6px; border-radius:4px; font-family:monospace; font-size:12.5px;">$1</code>\');
            // Bold
            html = html.replace(/\\*\\*([^*]+)\\*\\*/g, \'<strong>$1</strong>\');
            // Lists
            html = html.replace(/^[\\*\\-•]\\s+(.*)$/gm, \'<li style="margin-left:20px; list-style-type:disc;">$1</li>\');
            // Line breaks
            html = html.replace(/\\n/g, \'<br>\');
            return html;
        }';

$newFormatMarkdown = 'function formatMarkdown(text) {
            if (!text) return \'\';
            
            // Replace code blocks with Antigravity / Codex IDE blocks
            const codeBlocks = [];
            let processedText = text.replace(/```([a-zA-Z0-9_-]*)\n?([\\s\\S]*?)```/g, function(match, lang, code) {
                const id = \'code_\' + Math.random().toString(36).substr(2, 9);
                const cleanLang = (lang || \'code\').toUpperCase();
                const ext = lang ? (lang.toLowerCase() === \'javascript\' || lang.toLowerCase() === \'js\' ? \'js\' : (lang.toLowerCase() === \'python\' || lang.toLowerCase() === \'py\' ? \'py\' : (lang.toLowerCase() === \'php\' ? \'php\' : (lang.toLowerCase() === \'html\' ? \'html\' : (lang.toLowerCase() === \'css\' ? \'css\' : (lang.toLowerCase() === \'sql\' ? \'sql\' : (lang.toLowerCase() === \'cpp\' || lang.toLowerCase() === \'c++\' ? \'cpp\' : (lang.toLowerCase() === \'json\' ? \'json\' : \'txt\')))))))) : \'txt\';
                
                const rawEscaped = encodeURIComponent(code.trim());
                
                const blockHtml = `
                    <div class="ai-ide-code-block" style="margin: 14px 0; background: #0f172a; border: 1.5px solid #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                        <div class="ai-ide-toolbar" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 14px; background: #1e293b; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 12px; color: #94a3b8;">
                            <div style="display: flex; align-items: center; gap: 8px; font-weight: 700;">
                                <i class="fa-solid fa-code" style="color: #38bdf8;"></i>
                                <span style="color: #f8fafc; letter-spacing: 0.5px;">${cleanLang}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <button type="button" class="ai-ide-btn" onclick="copyCodeBlock(\'${id}\', this)" style="background: rgba(255,255,255,0.08); color: #f8fafc; border: 1px solid rgba(255,255,255,0.12); padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: all 0.2s;">
                                    <i class="fa-solid fa-copy"></i> <span>Sao chép</span>
                                </button>
                                <button type="button" class="ai-ide-btn" onclick="downloadCodeFile(\'${rawEscaped}\', \'file_${Date.now()}.${ext}\')" style="background: #10b981; color: #fff; border: none; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: all 0.2s;">
                                    <i class="fa-solid fa-download"></i> <span>Tải file</span>
                                </button>
                            </div>
                        </div>
                        <pre style="margin: 0; padding: 14px 16px; overflow-x: auto; font-family: \'Fira Code\', Consolas, Monaco, monospace; font-size: 13px; line-height: 1.6; color: #f8fafc;"><code id="${id}">${escapeHtml(code.trim())}</code></pre>
                    </div>
                `;
                
                codeBlocks.push(blockHtml);
                return `___CODE_BLOCK_${codeBlocks.length - 1}___`;
            });

            // Escape normal HTML
            let html = escapeHtml(processedText);

            // Inline code
            html = html.replace(/`([^`]+)`/g, \'<code style="background:rgba(217,27,67,0.06); color:#d91b43; padding:2px 6px; border-radius:4px; font-family:monospace; font-size:12.5px; font-weight:600;">$1</code>\');
            
            // Bold
            html = html.replace(/\\*\\*([^*]+)\\*\\*/g, \'<strong>$1</strong>\');
            
            // Lists
            html = html.replace(/^[\\*\\-•]\\s+(.*)$/gm, \'<li style="margin-left:20px; list-style-type:disc;">$1</li>\');
            
            // Line breaks
            html = html.replace(/\\n/g, \'<br>\');

            // Re-insert code blocks
            codeBlocks.forEach((block, index) => {
                html = html.replace(`___CODE_BLOCK_${index}___`, block);
            });

            return html;
        }

        function escapeHtml(str) {
            return (str || \'\').replace(/&/g, \'&amp;\').replace(/</g, \'&lt;\').replace(/>/g, \'&gt;\');
        }

        function copyCodeBlock(elementId, btn) {
            const codeEl = document.getElementById(elementId);
            if (!codeEl) return;
            const text = codeEl.innerText;
            navigator.clipboard.writeText(text).then(() => {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = \'<i class="fa-solid fa-check" style="color:#22c55e;"></i> <span style="color:#22c55e;">Đã sao chép!</span>\';
                setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
            });
        }

        function downloadCodeFile(encodedCode, filename) {
            const code = decodeURIComponent(encodedCode);
            const blob = new Blob([code], { type: \'text/plain;charset=utf-8\' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement(\'a\');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            setTimeout(() => {
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }, 300);
        }';

$aiPhp = str_replace($oldFormatMarkdown, $newFormatMarkdown, $aiPhp);

// 2. Enhance File Select Handler to support extensive extensions, file sizes, and drag-and-drop
$oldHandleFileSelect = 'function handleFileSelect(input) {
            const files = Array.from(input.files);
            if (files.length === 0) return;

            const textExtensions = [\'.txt\', \'.php\', \'.js\', \'.py\', \'.sql\', \'.java\', \'.cpp\', \'.c\', \'.h\', \'.html\', \'.css\', \'.json\', \'.md\', \'.xml\', \'.yaml\', \'.yml\', \'.csv\', \'.ts\'];

            files.forEach(file => {
                const ext = file.name.substring(file.name.lastIndexOf(\'.\')).toLowerCase();
                const reader = new FileReader();

                if (file.type.startsWith(\'image/\')) {
                    reader.onload = function(e) {
                        attachedFiles.push({ name: file.name, type: \'image\', data: e.target.result });
                        renderPreviews();
                    };
                    reader.readAsDataURL(file);
                } else if (textExtensions.includes(ext) || file.type.startsWith(\'text/\')) {
                    reader.onload = function(e) {
                        attachedFiles.push({ name: file.name, type: \'text\', data: e.target.result });
                        renderPreviews();
                    };
                    reader.readAsText(file);
                } else {
                    alert(`Không hỗ trợ file nhị phân: ${file.name}. Hệ thống nhận ảnh hoặc file text/code.`);
                }
            });
            input.value = \'\';
        }';

$newHandleFileSelect = 'function handleFileSelect(input) {
            const files = Array.from(input.files);
            processIncomingFiles(files);
            input.value = \'\';
        }

        function processIncomingFiles(files) {
            if (!files || files.length === 0) return;

            const codeExtensions = [\'.txt\', \'.php\', \'.js\', \'.py\', \'.sql\', \'.java\', \'.cpp\', \'.c\', \'.h\', \'.cs\', \'.html\', \'.css\', \'.json\', \'.md\', \'.xml\', \'.yaml\', \'.yml\', \'.csv\', \'.ts\', \'.vue\', \'.jsx\', \'.tsx\', \'.sh\', \'.bat\', \'.env\', \'.gitignore\', \'.htaccess\'];

            files.forEach(file => {
                const ext = file.name.substring(file.name.lastIndexOf(\'.\')).toLowerCase();
                const sizeKb = (file.size / 1024).toFixed(1);
                const reader = new FileReader();

                if (file.type.startsWith(\'image/\')) {
                    reader.onload = function(e) {
                        attachedFiles.push({ name: file.name, type: \'image\', data: e.target.result, size: `${sizeKb} KB` });
                        renderPreviews();
                    };
                    reader.readAsDataURL(file);
                } else if (codeExtensions.includes(ext) || file.type.startsWith(\'text/\') || file.type.includes(\'javascript\') || file.type.includes(\'json\')) {
                    reader.onload = function(e) {
                        attachedFiles.push({ name: file.name, type: \'text\', data: e.target.result, ext: ext, size: `${sizeKb} KB` });
                        renderPreviews();
                    };
                    reader.readAsText(file);
                } else {
                    // Try reading as text anyway for unknown code files
                    reader.onload = function(e) {
                        attachedFiles.push({ name: file.name, type: \'text\', data: e.target.result, ext: ext, size: `${sizeKb} KB` });
                        renderPreviews();
                    };
                    reader.readAsText(file);
                }
            });
        }

        // Setup Drag & Drop on Chat Body & Input Card
        document.addEventListener(\'DOMContentLoaded\', function() {
            const dropZones = [document.getElementById(\'aiChatBody\'), document.getElementById(\'aiInputWrapper\'), document.querySelector(\'.ai-input-card\')];
            
            dropZones.forEach(zone => {
                if (!zone) return;
                zone.addEventListener(\'dragover\', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.style.outline = \'2px dashed #10b981\';
                });
                zone.addEventListener(\'dragleave\', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.style.outline = \'none\';
                });
                zone.addEventListener(\'drop\', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.style.outline = \'none\';
                    if (e.dataTransfer && e.dataTransfer.files) {
                        processIncomingFiles(Array.from(e.dataTransfer.files));
                    }
                });
            });
        });';

$aiPhp = str_replace($oldHandleFileSelect, $newHandleFileSelect, $aiPhp);

// 3. Upgrade renderPreviews to display rich chip with file icons and file sizes
$oldRenderPreviews = 'function renderPreviews() {
            const previewContainer = document.getElementById(\'attachmentPreview\');
            const previewList = document.getElementById(\'previewList\');
            previewList.innerHTML = \'\';

            if (attachedFiles.length === 0) {
                previewContainer.style.display = \'none\';
                return;
            }

            previewContainer.style.display = \'flex\';

            attachedFiles.forEach((file, index) => {
                const card = document.createElement(\'div\');
                card.style.position = \'relative\';
                card.style.display = \'flex\';
                card.style.alignItems = \'center\';
                card.style.gap = \'8px\';
                card.style.padding = \'6px 10px\';
                card.style.background = \'#ffffff\';
                card.style.border = \'1px solid #e2e8f0\';
                card.style.borderRadius = \'8px\';
                card.style.fontSize = \'12px\';

                if (file.type === \'image\') {
                    const img = document.createElement(\'img\');
                    img.src = file.data;
                    img.style.width = \'24px\';
                    img.style.height = \'24px\';
                    img.style.objectFit = \'cover\';
                    img.style.borderRadius = \'4px\';
                    card.appendChild(img);
                } else {
                    const icon = document.createElement(\'i\');
                    icon.className = \'fa-solid fa-file-code\';
                    icon.style.color = \'var(--accent)\';
                    card.appendChild(icon);
                }

                const txt = document.createElement(\'span\');
                txt.innerText = file.name;
                txt.style.maxWidth = \'120px\';
                txt.style.overflow = \'hidden\';
                txt.style.textOverflow = \'ellipsis\';
                txt.style.whiteSpace = \'nowrap\';
                card.appendChild(txt);

                const delBtn = document.createElement(\'button\');
                delBtn.innerHTML = \'<i class="fa-solid fa-xmark"></i>\';
                delBtn.style.position = \'absolute\';
                delBtn.style.top = \'-6px\';
                delBtn.style.right = \'-6px\';
                delBtn.style.background = \'#ef4444\';
                delBtn.style.color = \'#fff\';
                delBtn.style.border = \'none\';
                delBtn.style.width = \'18px\';
                delBtn.style.height = \'18px\';
                delBtn.style.borderRadius = \'50%\';
                delBtn.style.cursor = \'pointer\';
                delBtn.onclick = () => removeAttachedFile(index);

                card.appendChild(delBtn);
                previewList.appendChild(card);
            });
        }';

$newRenderPreviews = 'function renderPreviews() {
            const previewContainer = document.getElementById(\'attachmentPreview\');
            const previewList = document.getElementById(\'previewList\');
            if (!previewContainer || !previewList) return;
            previewList.innerHTML = \'\';

            if (attachedFiles.length === 0) {
                previewContainer.style.display = \'none\';
                return;
            }

            previewContainer.style.display = \'flex\';

            attachedFiles.forEach((file, index) => {
                const card = document.createElement(\'div\');
                card.style.position = \'relative\';
                card.style.display = \'flex\';
                card.style.alignItems = \'center\';
                card.style.gap = \'8px\';
                card.style.padding = \'7px 12px\';
                card.style.background = \'#ffffff\';
                card.style.border = \'1.5px solid #e2e8f0\';
                card.style.borderRadius = \'10px\';
                card.style.fontSize = \'12.5px\';
                card.style.boxShadow = \'0 2px 8px rgba(0,0,0,0.04)\';

                if (file.type === \'image\') {
                    const img = document.createElement(\'img\');
                    img.src = file.data;
                    img.style.width = \'26px\';
                    img.style.height = \'26px\';
                    img.style.objectFit = \'cover\';
                    img.style.borderRadius = \'6px\';
                    card.appendChild(img);
                } else {
                    const ext = (file.name || \'\').split(\'.\').pop().toLowerCase();
                    let iconColor = \'#3b82f6\';
                    if (ext === \'php\') iconColor = \'#8b5cf6\';
                    else if (ext === \'js\' || ext === \'ts\') iconColor = \'#eab308\';
                    else if (ext === \'py\') iconColor = \'#0ea5e9\';
                    else if (ext === \'html\') iconColor = \'#f97316\';
                    else if (ext === \'css\') iconColor = \'#06b6d4\';
                    else if (ext === \'sql\') iconColor = \'#ec4899\';

                    const icon = document.createElement(\'i\');
                    icon.className = \'fa-solid fa-file-code\';
                    icon.style.color = iconColor;
                    icon.style.fontSize = \'16px\';
                    card.appendChild(icon);
                }

                const txt = document.createElement(\'div\');
                txt.style.display = \'flex\';
                txt.style.flexDirection = \'column\';
                txt.innerHTML = `<span style="font-weight:700; color:#0f172a; max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${file.name}</span><span style="font-size:10.5px; color:#94a3b8;">${file.size || \'Tệp đính kèm\'}</span>`;
                card.appendChild(txt);

                const delBtn = document.createElement(\'button\');
                delBtn.innerHTML = \'<i class="fa-solid fa-xmark"></i>\';
                delBtn.style.position = \'absolute\';
                delBtn.style.top = \'-6px\';
                delBtn.style.right = \'-6px\';
                delBtn.style.background = \'#ef4444\';
                delBtn.style.color = \'#fff\';
                delBtn.style.border = \'none\';
                delBtn.style.width = \'18px\';
                delBtn.style.height = \'18px\';
                delBtn.style.borderRadius = \'50%\';
                delBtn.style.cursor = \'pointer\';
                delBtn.style.display = \'flex\';
                delBtn.style.alignItems = \'center\';
                delBtn.style.justifyContent = \'center\';
                delBtn.style.fontSize = \'10px\';
                delBtn.onclick = () => removeAttachedFile(index);

                card.appendChild(delBtn);
                previewList.appendChild(card);
            });
        }';

$aiPhp = str_replace($oldRenderPreviews, $newRenderPreviews, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully upgraded Antigravity/Codex file processing & IDE blocks in student/ai.php!\n";
