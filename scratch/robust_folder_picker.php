<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$robustTrigger = '
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
                    console.warn("showDirectoryPicker failed, fallback to folderInput:", err);
                    const fi = document.getElementById("folderInput");
                    if (fi) fi.click();
                    return;
                }
            }
            const fi = document.getElementById("folderInput");
            if (fi) fi.click();
        };
';

// Replace window.triggerFolderPickerDirect
$pattern = '/window\.triggerFolderPickerDirect\s*=\s*async\s*function\s*\(\)\s*\{.*?\};/s';
$aiPhp = preg_replace($pattern, trim($robustTrigger), $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Updated triggerFolderPickerDirect with robust fallback in student/ai.php!\n";
