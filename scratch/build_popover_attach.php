<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Replace the attach button container in the toolbar
$oldToolbar = '<div style="display:flex; align-items:center; gap:6px;">
                                <!-- Folder Picker Button with INLINE code to bypass cache -->
                                <button type="button" id="attachBtn" class="ai-tool-btn" onclick="window.triggerFolderPickerDirect()" title="Mở thư mục dự án (Open Folder / Workspace)" style="cursor:pointer; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; border:1px solid #e2e8f0; background:#f8fafc;">
                                    <i class="fa-solid fa-paperclip" style="font-size:15px; color:#475569;"></i>
                                </button>
                            </div>';

$newToolbar = '<div style="display:flex; align-items:center; gap:6px; position:relative;" id="attachWrapper">
                                <button type="button" id="attachBtn" class="ai-tool-btn" onclick="toggleAttachMenu(event)" title="Đính kèm Tệp hoặc Thư mục" style="cursor:pointer; display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; border:1.5px solid #cbd5e1; background:#ffffff; box-shadow:0 2px 5px rgba(0,0,0,0.04);">
                                    <i class="fa-solid fa-paperclip" style="font-size:16px; color:#334155;"></i>
                                </button>

                                <!-- Attach Popover Menu -->
                                <div id="attachMenuPopover" style="display:none; position:absolute; bottom:46px; left:0; background:#ffffff; border:1.5px solid #e2e8f0; border-radius:14px; box-shadow:0 12px 35px rgba(0,0,0,0.15); padding:8px; z-index:9999999; min-width:220px; flex-direction:column; gap:6px;">
                                    <button type="button" onclick="openFolderDirectly(event)" style="display:flex; align-items:center; gap:10px; width:100%; padding:10px 14px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:10px; font-size:13px; font-weight:700; color:#065f46; cursor:pointer; text-align:left; transition:all 0.15s;">
                                        <i class="fa-solid fa-folder-open" style="color:#10b981; font-size:16px;"></i>
                                        <span>Chọn Thư Mục (Folder)</span>
                                    </button>
                                    <button type="button" onclick="openFilesDirectly(event)" style="display:flex; align-items:center; gap:10px; width:100%; padding:10px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; font-size:13px; font-weight:700; color:#334155; cursor:pointer; text-align:left; transition:all 0.15s;">
                                        <i class="fa-solid fa-file-code" style="color:#64748b; font-size:16px;"></i>
                                        <span>Chọn Tệp Tin (Files)</span>
                                    </button>
                                </div>
                            </div>';

$aiPhp = str_replace($oldToolbar, $newToolbar, $aiPhp);

// 2. Add toggleAttachMenu, openFolderDirectly, openFilesDirectly functions
$menuJs = '
        function toggleAttachMenu(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            const pop = document.getElementById("attachMenuPopover");
            if (!pop) return;
            pop.style.display = pop.style.display === "flex" ? "none" : "flex";
        }

        document.addEventListener("click", function(e) {
            const pop = document.getElementById("attachMenuPopover");
            const wrap = document.getElementById("attachWrapper");
            if (pop && pop.style.display === "flex") {
                if (wrap && !wrap.contains(e.target)) {
                    pop.style.display = "none";
                }
            }
        });

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

        function openFilesDirectly(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            const pop = document.getElementById("attachMenuPopover");
            if (pop) pop.style.display = "none";

            const fi = document.getElementById("fileInput");
            if (fi) fi.click();
        }
';

$aiPhp = str_replace('window.triggerFolderPickerDirect =', $menuJs . "\n        window.triggerFolderPickerDirect =", $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully integrated attach popup menu in student/ai.php!\n";
