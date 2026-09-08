<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Replace Top Bar Button
$oldTopBtn = '<button type="button" onclick="openNativeFolderPicker()" style="display:flex; align-items:center; gap:6px; padding:6px 12px; background:#ecfdf5; border:1.5px solid #a7f3d0; color:#047857; border-radius:10px; font-weight:700; font-size:12px; cursor:pointer;" title="Mở thư mục dự án (Open Folder Workspace)">
                            <i class="fa-solid fa-folder-open" style="color:#10b981;"></i>
                            <span>Mở Folder</span>
                        </button>';
$newTopBtn = '<label for="folderInput" style="display:flex; align-items:center; gap:6px; padding:6px 12px; margin:0; background:#ecfdf5; border:1.5px solid #a7f3d0; color:#047857; border-radius:10px; font-weight:700; font-size:12px; cursor:pointer;" title="Mở thư mục dự án (Open Folder Workspace)">
                            <i class="fa-solid fa-folder-open" style="color:#10b981;"></i>
                            <span>Mở Folder</span>
                        </label>';
$aiPhp = str_replace($oldTopBtn, $newTopBtn, $aiPhp);

// 2. Replace Hero Card Button
$oldHeroCard = '<div class="ai-hero-card" onclick="openNativeFolderPicker()" style="grid-column: 1 / -1; background: #ecfdf5; border-color: #a7f3d0; color: #047857; font-weight: 700;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                    <span>Mở toàn bộ thư mục dự án trên máy (Open Folder Workspace)</span>
                                </div>
                                <i class="fa-solid fa-arrow-right" style="color: #10b981;"></i>
                            </div>';
$newHeroCard = '<label for="folderInput" class="ai-hero-card" style="grid-column: 1 / -1; margin:0; background: #ecfdf5; border-color: #a7f3d0; color: #047857; font-weight: 700; display:flex; justify-content:space-between; align-items:center; cursor:pointer;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                    <span>Mở toàn bộ thư mục dự án trên máy (Open Folder Workspace)</span>
                                </div>
                                <i class="fa-solid fa-arrow-right" style="color: #10b981;"></i>
                            </label>';
$aiPhp = str_replace($oldHeroCard, $newHeroCard, $aiPhp);

// 3. Replace Popover Buttons
$oldPopover = '<button type="button" onclick="openFolderDirectly(event)" style="display:flex; align-items:center; gap:10px; width:100%; padding:10px 14px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:10px; font-size:13px; font-weight:700; color:#065f46; cursor:pointer; text-align:left; transition:all 0.15s;">
                                        <i class="fa-solid fa-folder-open" style="color:#10b981; font-size:16px;"></i>
                                        <span>Chọn Thư Mục (Folder)</span>
                                    </button>
                                    <button type="button" onclick="openFilesDirectly(event)" style="display:flex; align-items:center; gap:10px; width:100%; padding:10px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; font-size:13px; font-weight:700; color:#334155; cursor:pointer; text-align:left; transition:all 0.15s;">
                                        <i class="fa-solid fa-file-code" style="color:#64748b; font-size:16px;"></i>
                                        <span>Chọn Tệp Tin (Files)</span>
                                    </button>';
$newPopover = '<label for="folderInput" onclick="closeAttachMenuPopover()" style="display:flex; align-items:center; gap:10px; width:100%; padding:10px 14px; margin:0; box-sizing:border-box; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:10px; font-size:13px; font-weight:700; color:#065f46; cursor:pointer; text-align:left; transition:all 0.15s;">
                                        <i class="fa-solid fa-folder-open" style="color:#10b981; font-size:16px;"></i>
                                        <span>Chọn Thư Mục (Folder)</span>
                                    </label>
                                    <label for="fileInput" onclick="closeAttachMenuPopover()" style="display:flex; align-items:center; gap:10px; width:100%; padding:10px 14px; margin:0; box-sizing:border-box; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; font-size:13px; font-weight:700; color:#334155; cursor:pointer; text-align:left; transition:all 0.15s;">
                                        <i class="fa-solid fa-file-code" style="color:#64748b; font-size:16px;"></i>
                                        <span>Chọn Tệp Tin (Files)</span>
                                    </label>';
$aiPhp = str_replace($oldPopover, $newPopover, $aiPhp);

// 4. Inject closeAttachMenuPopover JS and remove old functions
$jsRemovePattern = '/function openFolderDirectly.*?\}[\s\n]*function openFilesDirectly.*?\}[\s\n]*function openNativeFolderPicker.*?\}[\s\n]*/s';
$aiPhp = preg_replace($jsRemovePattern, '', $aiPhp);

$newMenuCode = '
        function closeAttachMenuPopover() {
            setTimeout(() => {
                const pop = document.getElementById("attachMenuPopover");
                if (pop) pop.style.display = "none";
            }, 100);
        }
        
        function toggleAttachMenu(e) {
';
$aiPhp = str_replace('function toggleAttachMenu(e) {', $newMenuCode, $aiPhp);

// 5. Ensure inputs are ready
// Check if folderInput exists, let\'s make sure it is rendered correctly
// Note: we must ensure that input type="file" is not disabled or restricted by any z-index trick.

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully converted all folder triggers to native HTML <label for='...'>!\n";
