<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Add Folder Inspector Modal Markup before </body>
$inspectorModalHtml = '
    <!-- WORKSPACE FOLDER INSPECTOR MODAL -->
    <div id="folderInspectorModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:99999999; align-items:center; justify-content:center; backdrop-filter:blur(6px);">
        <div style="width:620px; max-width:92vw; max-height:85vh; background:#ffffff; border-radius:18px; display:flex; flex-direction:column; box-shadow:0 30px 90px rgba(0,0,0,0.3); overflow:hidden; font-family:\'Outfit\',sans-serif;">
            <!-- Header -->
            <div style="padding:16px 22px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:10px; background:#ecfdf5; border:1px solid #a7f3d0; display:flex; align-items:center; justify-content:center; color:#059669; font-size:16px;">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <div>
                        <h3 id="inspectorFolderName" style="margin:0; font-size:16px; font-weight:800; color:#0f172a;">Thư mục dự án</h3>
                        <div id="inspectorFileStats" style="font-size:12px; color:#64748b;">Đang quét các tệp tin...</div>
                    </div>
                </div>
                <button type="button" onclick="closeFolderInspector()" style="background:transparent; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; font-size:16px;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Filter Controls -->
            <div style="padding:10px 22px; background:#ffffff; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; gap:10px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" onclick="toggleSelectAllInspector(true)" style="padding:4px 10px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:6px; font-size:11.5px; font-weight:700; color:#334155; cursor:pointer;">
                        Chọn tất cả
                    </button>
                    <button type="button" onclick="toggleSelectAllInspector(false)" style="padding:4px 10px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:6px; font-size:11.5px; font-weight:700; color:#334155; cursor:pointer;">
                        Bỏ chọn
                    </button>
                </div>
                <span style="font-size:12px; color:#64748b;" id="inspectorSelectedCount">Đã chọn 0 tệp</span>
            </div>

            <!-- Files List Scroll Area -->
            <div id="inspectorFilesList" style="flex:1; max-height:360px; overflow-y:auto; padding:12px 22px; display:flex; flex-direction:column; gap:6px; background:#ffffff;">
                <!-- Dynamically populated files -->
            </div>

            <!-- Footer Actions -->
            <div style="padding:14px 22px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; align-items:center; justify-content:space-between;">
                <button type="button" onclick="closeFolderInspector()" style="padding:8px 16px; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; font-weight:700; font-size:13px; color:#475569; cursor:pointer;">
                    Hủy bỏ
                </button>
                <button type="button" onclick="confirmInspectorFiles()" style="padding:8px 20px; background:#10b981; border:none; border-radius:8px; font-weight:800; font-size:13px; color:#ffffff; cursor:pointer; box-shadow:0 3px 10px rgba(16,185,129,0.3); display:flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-check"></i> <span>Nạp vào AI Chat</span>
                </button>
            </div>
        </div>
    </div>
';

$aiPhp = str_replace('</body>', $inspectorModalHtml . "\n</body>", $aiPhp);

// 2. Add JavaScript logic for Folder Inspector Modal
$inspectorJs = '
        let inspectedPendingFiles = [];
        let inspectedFolderName = \'\';

        function openFolderInspectorModal(files, folderName) {
            inspectedPendingFiles = files;
            inspectedFolderName = folderName || \'Thư mục đã chọn\';

            const modal = document.getElementById(\'folderInspectorModal\');
            const nameEl = document.getElementById(\'inspectorFolderName\');
            const statsEl = document.getElementById(\'inspectorFileStats\');
            const listEl = document.getElementById(\'inspectorFilesList\');

            if (nameEl) nameEl.textContent = `📁 Thư mục: ${inspectedFolderName}`;
            if (statsEl) statsEl.textContent = `Tìm thấy ${files.length} tệp tin trong thư mục`;
            if (listEl) listEl.innerHTML = \'\';

            files.forEach((f, idx) => {
                const row = document.createElement(\'label\');
                row.style.display = \'flex\';
                row.style.alignItems = \'center\';
                row.style.justifyContent = \'space-between\';
                row.style.padding = \'8px 12px\';
                row.style.background = \'#f8fafc\';
                row.style.border = \'1px solid #e2e8f0\';
                row.style.borderRadius = \'8px\';
                row.style.cursor = \'pointer\';
                row.style.fontSize = \'13px\';
                row.style.transition = \'all 0.15s\';
                
                const ext = (f.name || \'\').split(\'.\').pop().toLowerCase();
                let iconColor = \'#3b82f6\';
                if (ext === \'php\') iconColor = \'#8b5cf6\';
                else if (ext === \'js\' || ext === \'ts\') iconColor = \'#eab308\';
                else if (ext === \'py\') iconColor = \'#0ea5e9\';
                else if (ext === \'html\') iconColor = \'#f97316\';
                else if (ext === \'css\') iconColor = \'#06b6d4\';
                else if (ext === \'sql\') iconColor = \'#ec4899\';

                row.innerHTML = `
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" class="inspector-file-chk" data-index="${idx}" checked style="width:16px; height:16px; accent-color:#10b981; cursor:pointer;" onchange="updateInspectorSelectedCount()">
                        <i class="fa-solid fa-file-code" style="color:${iconColor}; font-size:15px;"></i>
                        <span style="font-weight:700; color:#0f172a;">${f.path || f.name}</span>
                    </div>
                    <span style="font-size:11.5px; color:#64748b; font-weight:600;">${f.size || \'\'}</span>
                `;
                listEl.appendChild(row);
            });

            updateInspectorSelectedCount();
            if (modal) modal.style.display = \'flex\';
        }

        function closeFolderInspector() {
            const modal = document.getElementById(\'folderInspectorModal\');
            if (modal) modal.style.display = \'none\';
            inspectedPendingFiles = [];
        }

        function toggleSelectAllInspector(selectAll) {
            document.querySelectorAll(\'.inspector-file-chk\').forEach(chk => {
                chk.checked = selectAll;
            });
            updateInspectorSelectedCount();
        }

        function updateInspectorSelectedCount() {
            const checked = document.querySelectorAll(\'.inspector-file-chk:checked\').length;
            const countEl = document.getElementById(\'inspectorSelectedCount\');
            if (countEl) countEl.textContent = `Đã chọn ${checked} / ${inspectedPendingFiles.length} tệp`;
        }

        function confirmInspectorFiles() {
            const checkedBoxes = Array.from(document.querySelectorAll(\'.inspector-file-chk:checked\'));
            const selectedFiles = checkedBoxes.map(chk => {
                const idx = parseInt(chk.getAttribute(\'data-index\'));
                return inspectedPendingFiles[idx];
            }).filter(Boolean);

            if (selectedFiles.length === 0) {
                alert(\'Vui lòng chọn ít nhất 1 tệp tin trong thư mục!\');
                return;
            }

            attachedFiles.push(...selectedFiles);
            renderPreviews();
            closeFolderInspector();
        }
';

$aiPhp = str_replace('function processIncomingFiles(files, fromFolderName = null) {', $inspectorJs . "\n        function processIncomingFiles(files, fromFolderName = null) {", $aiPhp);

// 3. Update processIncomingFiles to open Inspector Modal when a folder is loaded
$oldProcessIncoming = 'if (ext === \'.zip\') {';
$newProcessIncoming = '// If multiple files loaded as a folder, collect and show inspector
                if (fromFolderName && files.length > 1) {
                    // Let async reads finish and open inspector
                }
                if (ext === \'.zip\') {';

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully added Folder Inspector Modal in student/ai.php!\n";
