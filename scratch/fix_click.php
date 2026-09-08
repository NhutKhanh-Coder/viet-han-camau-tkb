<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// Fix openFolderDirectly in the menu script
$oldMenuScript = '
        function openFolderDirectly(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            const pop = document.getElementById("attachMenuPopover");
            if (pop) pop.style.display = "none";

            if (window.showDirectoryPicker) {
                window.showDirectoryPicker({ mode: "read" })
                    .then(async dirHandle => {
                        if (!dirHandle) return;
                        const folderName = dirHandle.name;
                        const fileEntries = await window.getFilesRecursivelyFromHandle(dirHandle, folderName);
                        if (fileEntries.length > 0) {
                            window.processExtractedFileEntries(fileEntries, folderName);
                        } else {
                            alert(`Thư mục "${folderName}" không chứa tệp nào.`);
                        }
                    })
                    .catch(err => {
                        if (err.name !== "AbortError") {
                            const fi = document.getElementById("folderInput");
                            if (fi) fi.click();
                        }
                    });
            } else {
                const fi = document.getElementById("folderInput");
                if (fi) fi.click();
            }
        }
';

$newMenuScript = '
        function openFolderDirectly(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            const pop = document.getElementById("attachMenuPopover");
            if (pop) pop.style.display = "none";

            // Luôn dùng input webkitdirectory vì showDirectoryPicker bị lỗi trên hosting free
            const fi = document.getElementById("folderInput");
            if (fi) {
                // Fix lỗi cache thuộc tính của trình duyệt
                fi.webkitdirectory = true;
                fi.setAttribute("webkitdirectory", "");
                fi.setAttribute("directory", "");
                fi.click();
            }
        }
';

$aiPhp = str_replace(trim($oldMenuScript), trim($newMenuScript), $aiPhp);


// Fix openNativeFolderPicker (the top bar green button)
$oldTopBarScript = "
        async function openNativeFolderPicker() {
            // First try standard showDirectoryPicker if available and secure
            if (window.showDirectoryPicker) {
                try {
                    const dirHandle = await window.showDirectoryPicker({ mode: 'read' });
                    if (!dirHandle) return;
                    const folderName = dirHandle.name;
                    const fileEntries = await getFilesRecursivelyFromHandle(dirHandle, folderName);
                    if (fileEntries.length > 0) {
                        processExtractedFileEntries(fileEntries, folderName);
                    } else {
                        alert(`Thư mục \"\${folderName}\" không có tệp nào.`);
                    }
                    return;
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.warn('showDirectoryPicker failed, falling back to input:', err);
                        const fi = document.getElementById('folderInput');
                        if (fi) fi.click();
                    }
                    return;
                }
            }
            const fi = document.getElementById('folderInput');
            if (fi) fi.click();
        }
";

$newTopBarScript = '
        function openNativeFolderPicker() {
            // Luôn dùng input webkitdirectory vì showDirectoryPicker bị lỗi trên hosting free
            const fi = document.getElementById("folderInput");
            if (fi) {
                // Fix lỗi cache thuộc tính của trình duyệt
                fi.webkitdirectory = true;
                fi.setAttribute("webkitdirectory", "");
                fi.setAttribute("directory", "");
                fi.click();
            }
        }
';

// The old function might be missing the exact spacing, so we use regex
$pattern = '/async function openNativeFolderPicker\(\)\s*\{.*?\n        \}/s';
$aiPhp = preg_replace($pattern, trim($newTopBarScript), $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully fixed folder picker buttons!\n";
