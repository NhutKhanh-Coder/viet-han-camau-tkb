<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// Replace button onclick with clean triggerFolderPickerDirect()
$oldBtn = '<button type="button" class="ai-tool-btn" onclick="(async function(btn){btn.disabled=true;btn.innerHTML=\'<i class=\\\'fa-solid fa-spinner fa-spin\\\'></i>\';try{if(!window.showDirectoryPicker){alert(\'API không khả dụng\');btn.disabled=false;btn.innerHTML=\'<i class=\\\'fa-solid fa-paperclip\\\'></i>\';return;}var d=await window.showDirectoryPicker({mode:\'read\'});var fn=d.name;var fe=await getFilesRecursivelyFromHandle(d,fn);if(fe.length>0){processExtractedFileEntries(fe,fn);}else{alert(\'Thư mục trống\');}}catch(e){if(e.name!==\'AbortError\'){alert(\'Lỗi: \'+e.name+\' - \'+e.message);}}btn.disabled=false;btn.innerHTML=\'<i class=\\\'fa-solid fa-paperclip\\\'></i>\';})(this)" title="Mở thư mục dự án (Open Folder)">
                                    <i class="fa-solid fa-paperclip"></i>
                                </button>';

$newBtn = '<button type="button" class="ai-tool-btn" onclick="triggerFolderPickerDirect()" title="Mở thư mục dự án (Open Folder / Workspace)">
                                    <i class="fa-solid fa-paperclip"></i>
                                </button>';

$aiPhp = str_replace($oldBtn, $newBtn, $aiPhp);

// Define global window functions
$globalHelpers = '
        window.getFilesRecursivelyFromHandle = async function(dirHandle, currentPath = "") {
            const results = [];
            const ignored = ["node_modules", ".git", ".vs", ".idea", "vendor", "__pycache__"];
            
            for await (const entry of dirHandle.values()) {
                if (ignored.includes(entry.name)) continue;
                const entryPath = currentPath ? `${currentPath}/${entry.name}` : entry.name;
                
                if (entry.kind === "file") {
                    try {
                        const f = await entry.getFile();
                        results.push({ file: f, path: entryPath });
                    } catch(e) {}
                } else if (entry.kind === "directory") {
                    try {
                        const subs = await window.getFilesRecursivelyFromHandle(entry, entryPath);
                        results.push(...subs);
                    } catch(e) {}
                }
            }
            return results;
        };

        window.processExtractedFileEntries = function(entries, folderName) {
            let loadedCount = 0;
            const total = entries.length;

            entries.forEach(item => {
                const file = item.file;
                const relPath = item.path;
                const ext = file.name.substring(file.name.lastIndexOf(".")).toLowerCase();
                const sizeKb = (file.size / 1024).toFixed(1);
                const reader = new FileReader();

                if (file.type && file.type.startsWith("image/")) {
                    reader.onload = function(e) {
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: folderName,
                            type: "image",
                            data: e.target.result,
                            size: `${sizeKb} KB`
                        });
                        loadedCount++;
                        renderPreviews();
                    };
                    reader.readAsDataURL(file);
                } else {
                    reader.onload = function(e) {
                        attachedFiles.push({
                            name: file.name,
                            path: relPath,
                            folder: folderName,
                            type: "text",
                            data: e.target.result,
                            ext: ext,
                            size: `${sizeKb} KB`
                        });
                        loadedCount++;
                        renderPreviews();
                    };
                    reader.readAsText(file);
                }
            });
        };

        window.triggerFolderPickerDirect = async function() {
            if (window.showDirectoryPicker) {
                try {
                    const dirHandle = await window.showDirectoryPicker({ mode: "read" });
                    if (!dirHandle) return;
                    const folderName = dirHandle.name;
                    const fileEntries = await window.getFilesRecursivelyFromHandle(dirHandle, folderName);
                    if (fileEntries.length > 0) {
                        window.processExtractedFileEntries(fileEntries, folderName);
                    } else {
                        alert(`Thư mục "${folderName}" không chứa tệp nào.`);
                    }
                    return;
                } catch (err) {
                    if (err.name === "AbortError") return; // User cancelled
                    alert("Lỗi mở thư mục: " + err.name + " - " + err.message);
                }
            } else {
                alert("Trình duyệt của bạn không hỗ trợ showDirectoryPicker. Vui lòng cập nhật Chrome hoặc Edge mới nhất.");
            }
        };
';

$aiPhp = str_replace('<script>', "<script>\n" . $globalHelpers, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully attached folder picker functions to window scope in student/ai.php!\n";
