<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Update HTML toolbar to include both File and Folder buttons
$oldToolbar = '<input type="file" id="fileInput" style="display:none;" onchange="handleFileSelect(this)" multiple>
                        
                        <textarea id="userInput" class="ai-textarea" rows="1" placeholder="Nhắn tin (Shift+Enter xuống dòng)..." oninput="autoGrow(this)" onkeydown="handleInputKey(event)"></textarea>

                        <div class="ai-card-toolbar">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <button type="button" class="ai-tool-btn" onclick="document.getElementById(\'fileInput\').click()" title="Đính kèm ảnh hoặc file mã nguồn code">
                                    <i class="fa-solid fa-paperclip"></i>
                                </button>
                            </div>';

$newToolbar = '<input type="file" id="fileInput" style="display:none;" onchange="handleFileSelect(this)" multiple accept="*/*">
                        <input type="file" id="folderInput" style="display:none;" onchange="handleFolderSelect(this)" webkitdirectory directory multiple>
                        
                        <textarea id="userInput" class="ai-textarea" rows="1" placeholder="Nhắn tin hoặc yêu cầu xử lý trên tệp/thư mục (Shift+Enter xuống dòng)..." oninput="autoGrow(this)" onkeydown="handleInputKey(event)"></textarea>

                        <div class="ai-card-toolbar">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <button type="button" class="ai-tool-btn" onclick="document.getElementById(\'fileInput\').click()" title="Đính kèm bất kỳ tệp tin (All files: Code, Ảnh, Văn bản, Data...)">
                                    <i class="fa-solid fa-paperclip"></i>
                                    <span style="font-size:12px; font-weight:600;">Tệp</span>
                                </button>
                                <button type="button" class="ai-tool-btn" onclick="document.getElementById(\'folderInput\').click()" title="Mở toàn bộ thư mục dự án (Open Folder / Workspace)" style="background: rgba(16, 185, 129, 0.08); color: #059669; font-weight: 700; border: 1px solid rgba(16, 185, 129, 0.2);">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <span style="font-size:12px;">Mở thư mục</span>
                                </button>
                            </div>';

$aiPhp = str_replace($oldToolbar, $newToolbar, $aiPhp);

// 2. Add handleFolderSelect and folder drop support to Javascript
$oldJsPattern = '/function handleFileSelect\(input\) \{.*?\/\/ Setup Drag & Drop on Chat Body/s';

$newJs = 'function handleFileSelect(input) {
            const files = Array.from(input.files);
            processIncomingFiles(files);
            input.value = \'\';
        }

        function handleFolderSelect(input) {
            const files = Array.from(input.files);
            if (files.length === 0) return;
            
            // Extract top-level folder name from the first file\'s relative path
            let folderName = \'Thư mục dự án\';
            if (files[0].webkitRelativePath) {
                folderName = files[0].webkitRelativePath.split(\'/\')[0];
            }
            
            processIncomingFiles(files, folderName);
            input.value = \'\';
        }

        function processIncomingFiles(files, fromFolderName = null) {
            if (!files || files.length === 0) return;

            // Exclude huge binary / cache folders like node_modules, .git, vendor if too large
            const ignoredFolders = [\'node_modules/\', \'.git/\', \'.vs/\', \'.idea/\'];

            files.forEach(file => {
                const relPath = file.webkitRelativePath || file.relativePath || file.name;
                
                // Skip ignored directories
                if (ignoredFolders.some(ig => relPath.includes(ig))) return;

                const ext = file.name.substring(file.name.lastIndexOf(\'.\')).toLowerCase();
                const sizeKb = (file.size / 1024).toFixed(1);
                const reader = new FileReader();

                if (file.type.startsWith(\'image/\')) {
                    reader.onload = function(e) {
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: \'image\',
                            data: e.target.result,
                            size: `${sizeKb} KB`
                        });
                        renderPreviews();
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Read all files as text (Code, markdown, config, json, sql, txt, etc.)
                    reader.onload = function(e) {
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: fromFolderName,
                            type: \'text\',
                            data: e.target.result,
                            ext: ext,
                            size: `${sizeKb} KB`
                        });
                        renderPreviews();
                    };
                    reader.readAsText(file);
                }
            });
        }

        // Setup Drag & Drop on Chat Body & Input Card with Folder recursion support';

$aiPhp = preg_replace($oldJsPattern, $newJs, $aiPhp);

// 3. Update Drag & Drop handler to recursively scan dropped folders via webkitGetAsEntry
$oldDropPattern = '/\/\/ Setup Drag & Drop on Chat Body.*?\/\/ Initialize on page load/s';

$newDrop = '// Setup Drag & Drop on Chat Body & Input Card with Folder recursion support
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
                zone.addEventListener(\'drop\', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.style.outline = \'none\';

                    if (e.dataTransfer && e.dataTransfer.items) {
                        const items = Array.from(e.dataTransfer.items);
                        const allFiles = [];
                        for (let item of items) {
                            if (item.webkitGetAsEntry) {
                                const entry = item.webkitGetAsEntry();
                                if (entry) {
                                    const scanned = await readEntryRecursively(entry, \'\');
                                    allFiles.push(...scanned);
                                }
                            } else if (item.kind === \'file\') {
                                const file = item.getAsFile();
                                if (file) allFiles.push(file);
                            }
                        }
                        processIncomingFiles(allFiles);
                    } else if (e.dataTransfer && e.dataTransfer.files) {
                        processIncomingFiles(Array.from(e.dataTransfer.files));
                    }
                });
            });
        });

        async function readEntryRecursively(entry, path = \'\') {
            if (entry.isFile) {
                return new Promise((resolve) => {
                    entry.file(file => {
                        file.relativePath = path + file.name;
                        resolve([file]);
                    }, () => resolve([]));
                });
            } else if (entry.isDirectory) {
                const reader = entry.createReader();
                const entries = await new Promise(resolve => {
                    reader.readEntries(res => resolve(res), () => resolve([]));
                });
                const filesPromises = entries.map(e => readEntryRecursively(e, path + entry.name + \'/\'));
                const nestedFiles = await Promise.all(filesPromises);
                return nestedFiles.flat();
            }
            return [];
        }

        // Initialize on page load';

$aiPhp = preg_replace($oldDropPattern, $newDrop, $aiPhp);

// 4. Update sendMsg to package folder projects with clear file tree & relative paths
$oldJoinedText = 'let joinedTextContent = \'\';
            if (texts.length > 0) {
                joinedTextContent = texts.map(f => `[Nội dung tệp đính kèm: ${f.name}]\\n\`\`\`\\n${f.data}\\n\`\`\``).join(\'\\n\\n\') + \'\\n\\n\';
            }';

$newJoinedText = 'let joinedTextContent = \'\';
            if (texts.length > 0) {
                // Group by folder if folder is uploaded
                const hasFolders = texts.some(f => f.folder || f.path.includes(\'/\'));
                if (hasFolders) {
                    const treeList = texts.map(f => `  - ${f.path} (${f.size})`).join(\'\\n\');
                    joinedTextContent = `=== TOÀN BỘ CẤU TRÚC THƯ MỤC DỰ ÁN (${texts.length} TỆP TIN) ===\\n${treeList}\\n\\n=== CHI TIẾT NỘI DUNG CÁC TỆP TRONG DỰ ÁN ===\\n\\n`;
                    joinedTextContent += texts.map(f => `--- [Tệp: ${f.path}] ---\\n\`\`\`${(f.ext || \'\').replace(\'.\', \'\')}\\n${f.data}\\n\`\`\``).join(\'\\n\\n\') + \'\\n\\n\';
                } else {
                    joinedTextContent = texts.map(f => `[Tệp đính kèm: ${f.path || f.name}]\\n\`\`\`${(f.ext || \'\').replace(\'.\', \'\')}\\n${f.data}\\n\`\`\``).join(\'\\n\\n\') + \'\\n\\n\';
                }
            }';

$aiPhp = str_replace($oldJoinedText, $newJoinedText, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully added All Files & Open Folder Project capability to student/ai.php!\n";
