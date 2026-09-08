<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Update toolbar button
$oldBtn = '<button type="button" class="ai-tool-btn" onclick="openNativeFolderPicker()" title="Mở thư mục dự án (Open Folder / Workspace)">
                                    <i class="fa-solid fa-paperclip"></i>
                                </button>';

$newBtn = '<button type="button" class="ai-tool-btn" onclick="triggerFolderPickerDirect(event)" title="Mở thư mục dự án (Open Folder / Workspace)">
                                    <i class="fa-solid fa-paperclip"></i>
                                </button>';

$aiPhp = str_replace($oldBtn, $newBtn, $aiPhp);

// 2. Add triggerFolderPickerDirect
$newTriggerJs = '
        function triggerFolderPickerDirect(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            
            if (typeof window.showDirectoryPicker === \'function\') {
                window.showDirectoryPicker({ mode: \'read\' })
                    .then(async dirHandle => {
                        if (!dirHandle) return;
                        const folderName = dirHandle.name;
                        const fileEntries = await getFilesRecursivelyFromHandle(dirHandle, folderName);
                        if (fileEntries.length > 0) {
                            processExtractedFileEntries(fileEntries, folderName);
                        }
                    })
                    .catch(err => {
                        if (err.name !== \'AbortError\') {
                            const fi = document.getElementById(\'folderInput\');
                            if (fi) fi.click();
                        }
                    });
            } else {
                const fi = document.getElementById(\'folderInput\');
                if (fi) fi.click();
            }
        }
';

$aiPhp = str_replace('async function openNativeFolderPicker()', $newTriggerJs . "\n        async function openNativeFolderPicker()", $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully applied direct synchronous folder picker trigger in student/ai.php!\n";
