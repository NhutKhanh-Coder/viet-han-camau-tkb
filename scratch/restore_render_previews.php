<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$functionsToAdd = '
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

            // If more than 3 files, show a summary header
            if (attachedFiles.length > 1) {
                const summaryCard = document.createElement(\'div\');
                summaryCard.style.display = \'flex\';
                summaryCard.style.alignItems = \'center\';
                summaryCard.style.gap = \'6px\';
                summaryCard.style.padding = \'5px 10px\';
                summaryCard.style.background = \'#ecfdf5\';
                summaryCard.style.border = \'1px solid #a7f3d0\';
                summaryCard.style.borderRadius = \'8px\';
                summaryCard.style.fontSize = \'12px\';
                summaryCard.style.fontWeight = \'700\';
                summaryCard.style.color = \'#065f46\';
                summaryCard.innerHTML = `<i class="fa-solid fa-layer-group"></i> <span>Đã chọn ${attachedFiles.length} tệp</span> <button type="button" onclick="clearAllAttachedFiles()" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:11px; margin-left:6px;"><i class="fa-solid fa-trash"></i> Xóa hết</button>`;
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

$aiPhp = str_replace('async function sendMsg()', $functionsToAdd . "\n        async function sendMsg()", $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully restored renderPreviews with multi-file counter and clear all in student/ai.php!\n";
