<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// Replace duplicate function
$pattern = '/async function triggerFolderPickerDirect\(\)\s*\{.*?\}\s*async function openNativeFolderPicker/s';
$aiPhp = preg_replace($pattern, 'async function openNativeFolderPicker', $aiPhp);

// Update button with id="attachBtn" and inline fallback
$oldBtn = '<button type="button" class="ai-tool-btn" onclick="triggerFolderPickerDirect()" title="Mở thư mục dự án (Open Folder / Workspace)">
                                    <i class="fa-solid fa-paperclip"></i>
                                </button>';

$newBtn = '<button type="button" id="attachBtn" class="ai-tool-btn" onclick="window.triggerFolderPickerDirect()" title="Mở thư mục dự án (Open Folder / Workspace)" style="cursor:pointer; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; border:1px solid #e2e8f0; background:#f8fafc;">
                                    <i class="fa-solid fa-paperclip" style="font-size:15px; color:#475569;"></i>
                                </button>';

$aiPhp = str_replace($oldBtn, $newBtn, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Cleaned up student/ai.php!\n";
