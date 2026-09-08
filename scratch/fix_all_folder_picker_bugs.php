<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// Define all helper functions for file/folder handling
$folderFunctions = '
        async function openNativeFolderPicker() {
            // First try standard showDirectoryPicker if available and secure
            if (window.showDirectoryPicker) {
                try {
                    const dirHandle = await window.showDirectoryPicker({ mode: \'read\' });
                    if (!dirHandle) return;
                    const folderName = dirHandle.name;
                    const fileEntries = await getFilesRecursivelyFromHandle(dirHandle, folderName);
                    if (fileEntries.length > 0) {
                        processExtractedFileEntries(fileEntries, folderName);
                    } else {
                        alert(`Thư mục "${folderName}" không có tệp nào.`);
                    }
                    return;
                } catch (err) {
                    if (err.name !== \'AbortError\') {
                        console.warn(\'showDirectoryPicker failed, falling back to input:\', err);
                        const fi = document.getElementById(\'folderInput\');
                        if (fi) fi.click();
                    }
                    return;
                }
            }
            const fi = document.getElementById(\'folderInput\');
            if (fi) fi.click();
        }

        async function getFilesRecursivelyFromHandle(dirHandle, currentPath = \'\') {
            const results = [];
            const ignored = [\'node_modules\', \'.git\', \'.vs\', \'.idea\', \'vendor\', \'__pycache__\'];
            
            for await (const entry of dirHandle.values()) {
                if (ignored.includes(entry.name)) continue;
                const entryPath = currentPath ? `${currentPath}/${entry.name}` : entry.name;
                
                if (entry.kind === \'file\') {
                    try {
                        const f = await entry.getFile();
                        results.push({ file: f, path: entryPath });
                    } catch(e) {}
                } else if (entry.kind === \'directory\') {
                    try {
                        const subs = await getFilesRecursivelyFromHandle(entry, entryPath);
                        results.push(...subs);
                    } catch(e) {}
                }
            }
            return results;
        }

        function processExtractedFileEntries(entries, folderName) {
            entries.forEach(item => {
                const file = item.file;
                const relPath = item.path;
                const ext = file.name.substring(file.name.lastIndexOf(\'.\')).toLowerCase();
                const sizeKb = (file.size / 1024).toFixed(1);
                const reader = new FileReader();

                if (file.type.startsWith(\'image/\')) {
                    reader.onload = function(e) {
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: folderName,
                            type: \'image\',
                            data: e.target.result,
                            size: `${sizeKb} KB`
                        });
                        renderPreviews();
                    };
                    reader.readAsDataURL(file);
                } else {
                    reader.onload = function(e) {
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: folderName,
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

        function handleFolderSelect(input) {
            const files = Array.from(input.files);
            if (files.length === 0) return;
            
            let folderName = \'Thư mục dự án\';
            if (files[0].webkitRelativePath) {
                folderName = files[0].webkitRelativePath.split(\'/\')[0];
            }
            
            processIncomingFiles(files, folderName);
            input.value = \'\';
        }

        function handleFileSelect(input) {
            const files = Array.from(input.files);
            processIncomingFiles(files);
            input.value = \'\';
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
            const previewContainer = document.getElementById(\'attachmentPreview\');
            const previewList = document.getElementById(\'previewList\');
            if (!previewContainer || !previewList) return;
            previewList.innerHTML = \'\';

            if (attachedFiles.length === 0) {
                previewContainer.style.display = \'none\';
                return;
            }

            previewContainer.style.display = \'flex\';

            // If more than 1 file, show summary chip
            if (attachedFiles.length > 1) {
                const summaryCard = document.createElement(\'div\');
                summaryCard.style.display = \'flex\';
                summaryCard.style.alignItems = \'center\';
                summaryCard.style.gap = \'6px\';
                summaryCard.style.padding = \'5px 12px\';
                summaryCard.style.background = \'#ecfdf5\';
                summaryCard.style.border = \'1px solid #a7f3d0\';
                summaryCard.style.borderRadius = \'8px\';
                summaryCard.style.fontSize = \'12px\';
                summaryCard.style.fontWeight = \'700\';
                summaryCard.style.color = \'#065f46\';
                summaryCard.innerHTML = `<i class="fa-solid fa-folder-tree" style="color:#10b981;"></i> <span>Đã chọn ${attachedFiles.length} tệp</span> <button type="button" onclick="clearAllAttachedFiles()" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:11px; margin-left:6px;"><i class="fa-solid fa-trash"></i> Xóa hết</button>`;
                previewList.appendChild(summaryCard);
            }

            attachedFiles.forEach((file, index) => {
                const card = document.createElement(\'div\');
                card.style.position = \'relative\';
                card.style.display = \'flex\';
                card.style.alignItems = \'center\';
                card.style.gap = \'8px\';
                card.style.padding = \'6px 12px\';
                card.style.background = \'#ffffff\';
                card.style.border = \'1.5px solid #e2e8f0\';
                card.style.borderRadius = \'10px\';
                card.style.fontSize = \'12px\';
                card.style.boxShadow = \'0 2px 6px rgba(0,0,0,0.03)\';

                if (file.type === \'image\') {
                    const img = document.createElement(\'img\');
                    img.src = file.data;
                    img.style.width = \'24px\';
                    img.style.height = \'24px\';
                    img.style.objectFit = \'cover\';
                    img.style.borderRadius = \'4px\';
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
                    icon.style.fontSize = \'15px\';
                    card.appendChild(icon);
                }

                const txt = document.createElement(\'div\');
                txt.style.display = \'flex\';
                txt.style.flexDirection = \'column\';
                const displayName = file.path || file.name;
                txt.innerHTML = `<span style="font-weight:700; color:#0f172a; max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${displayName}">${displayName}</span><span style="font-size:10px; color:#94a3b8;">${file.size || \'Tệp tin\'}</span>`;
                card.appendChild(txt);

                const delBtn = document.createElement(\'button\');
                delBtn.innerHTML = \'<i class="fa-solid fa-xmark"></i>\';
                delBtn.style.position = \'absolute\';
                delBtn.style.top = \'-5px\';
                delBtn.style.right = \'-5px\';
                delBtn.style.background = \'#ef4444\';
                delBtn.style.color = \'#fff\';
                delBtn.style.border = \'none\';
                delBtn.style.width = \'16px\';
                delBtn.style.height = \'16px\';
                delBtn.style.borderRadius = \'50%\';
                delBtn.style.cursor = \'pointer\';
                delBtn.style.display = \'flex\';
                delBtn.style.alignItems = \'center\';
                delBtn.style.justifyContent = \'center\';
                delBtn.style.fontSize = \'9px\';
                delBtn.onclick = () => removeAttachedFile(index);

                card.appendChild(delBtn);
                previewList.appendChild(card);
            });
        }
';

// Replace functions from openNativeFolderPicker to processIncomingFiles
$pattern = '/async function openNativeFolderPicker\(\).*?function processIncomingFiles\(files, fromFolderName = null\) \{/s';

$replacement = $folderFunctions . "\n        function processIncomingFiles(files, fromFolderName = null) {";

$aiPhp = preg_replace($pattern, $replacement, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully fixed renderPreviews and all folder picker handlers!\n";
