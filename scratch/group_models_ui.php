<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Update the HTML of the left column to include category pills
$oldSearchWrap = '<div class="md-search-wrap-white">
                                            <input type="text" id="modelSearchInput" class="md-search-box-white" placeholder="Tìm mô hình..." oninput="filterModels(this.value)">
                                        </div>';

$newSearchWrap = '<div class="md-search-wrap-white">
                                            <input type="text" id="modelSearchInput" class="md-search-box-white" placeholder="🔍 Tìm mô hình (DeepSeek, Qwen, Mistral...)" oninput="filterModels(this.value)">
                                            <div style="display:flex; align-items:center; gap:6px; margin-top:8px; overflow-x:auto; padding-bottom:2px;" id="modelCategoryTabs">
                                                <button type="button" class="model-cat-pill active" onclick="filterByModelCategory(\'all\', this)">Tất cả (36)</button>
                                                <button type="button" class="model-cat-pill" onclick="filterByModelCategory(\'deepseek\', this)">⚡ DeepSeek (4)</button>
                                                <button type="button" class="model-cat-pill" onclick="filterByModelCategory(\'qwen\', this)">🚀 Qwen (17)</button>
                                                <button type="button" class="model-cat-pill" onclick="filterByModelCategory(\'mistral\', this)">🌪️ Mistral (8)</button>
                                                <button type="button" class="model-cat-pill" onclick="filterByModelCategory(\'minimax\', this)">🌟 MiniMax (7)</button>
                                            </div>
                                        </div>';

$aiPhp = str_replace($oldSearchWrap, $newSearchWrap, $aiPhp);

// 2. Add CSS for category pills
$pillCss = '
        .model-cat-pill {
            padding: 4px 10px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s;
        }
        .model-cat-pill:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .model-cat-pill.active {
            background: #10b981;
            border-color: #059669;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
        }
        .md-group-divider {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px 4px;
            margin-top: 6px;
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px dashed #e2e8f0;
        }
';

$aiPhp = str_replace('.md-models-list-white {', $pillCss . "\n        .md-models-list-white {", $aiPhp);

// 3. Update the JavaScript for renderModelsList and filterByModelCategory
$oldRenderModels = '        function renderModelsList(models) {
            const container = document.getElementById(\'modelsListContainer\');
            if (!container) return;
            container.innerHTML = \'\';

            models.forEach(m => {
                const row = document.createElement(\'div\');
                row.className = \'md-model-row-white\' + (currentSelectedModel && currentSelectedModel.id === m.id ? \' active\' : \'\');
                row.onclick = () => selectModel(m.id);
                row.onmouseenter = () => updateModelDetails(m);

                row.innerHTML = `
                    <div class="md-item-left-white" style="display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-bolt" style="color:#0ea5e9;"></i>
                        <span style="font-weight:700; font-size:13px; color:#0f172a;">${m.name}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:5px;">
                        <span class="md-item-badge-white" style="color:#15803d; background:#dcfce7; border-color:#bbf7d0; font-size:11px; font-weight:700; padding:2px 7px; border-radius:6px;">✨ Free</span>
                        <span class="md-item-badge-white" style="font-size:11px; padding:2px 6px; border-radius:6px; background:#f1f5f9; color:#475569;">${m.badge || \'128K\'}</span>
                    </div>
                `;
                container.appendChild(row);
            });
        }';

$newRenderModels = '        let currentModelCategory = \'all\';

        function filterByModelCategory(cat, btn) {
            currentModelCategory = cat;
            document.querySelectorAll(\'.model-cat-pill\').forEach(b => b.classList.remove(\'active\'));
            if (btn) btn.classList.add(\'active\');
            const searchVal = (document.getElementById(\'modelSearchInput\') ? document.getElementById(\'modelSearchInput\').value : \'\');
            filterModels(searchVal);
        }

        function getModelGroup(m) {
            const id = (m.id || \'\').toLowerCase();
            const name = (m.name || \'\').toLowerCase();
            if (id.includes(\'deepseek\') || name.includes(\'deepseek\')) return { key: \'deepseek\', title: \'⚡ DeepSeek (Lập trình & Tiếng Việt)\', iconColor: \'#0284c7\' };
            if (id.includes(\'qwen\') || name.includes(\'qwen\')) return { key: \'qwen\', title: \'🚀 Alibaba Qwen (Đa năng & Tốc độ cao)\', iconColor: \'#8b5cf6\' };
            if (id.includes(\'mistral\') || id.includes(\'codestral\') || id.includes(\'devstral\') || name.includes(\'mistral\') || name.includes(\'codestral\')) return { key: \'mistral\', title: \'🌪️ Mistral & Codestral (Chuyên gia Lập trình)\', iconColor: \'#ea580c\' };
            if (id.includes(\'minimax\') || name.includes(\'minimax\')) return { key: \'minimax\', title: \'🌟 MiniMax (Ngữ cảnh siêu dài)\', iconColor: \'#10b981\' };
            return { key: \'other\', title: \'✨ Khác (Mô hình bổ trợ)\', iconColor: \'#64748b\' };
        }

        function renderModelsList(models) {
            const container = document.getElementById(\'modelsListContainer\');
            if (!container) return;
            container.innerHTML = \'\';

            // Filter by category if selected
            let filtered = models;
            if (currentModelCategory !== \'all\') {
                filtered = filtered.filter(m => getModelGroup(m).key === currentModelCategory);
            }

            if (filtered.length === 0) {
                container.innerHTML = \'<div style="text-align:center; padding:30px 10px; color:#94a3b8; font-size:13px;"><i class="fa-solid fa-search" style="font-size:20px; margin-bottom:8px; display:block;"></i> Không tìm thấy mô hình phù hợp</div>\';
                return;
            }

            // Group models
            const groups = {};
            filtered.forEach(m => {
                const grp = getModelGroup(m);
                if (!groups[grp.key]) {
                    groups[grp.key] = { title: grp.title, iconColor: grp.iconColor, list: [] };
                }
                groups[grp.key].list.push(m);
            });

            // Order of groups
            const groupOrder = [\'deepseek\', \'qwen\', \'mistral\', \'minimax\', \'other\'];

            groupOrder.forEach(key => {
                if (!groups[key] || groups[key].list.length === 0) return;
                const g = groups[key];

                // Section Header Divider
                const header = document.createElement(\'div\');
                header.className = \'md-group-divider\';
                header.innerHTML = `
                    <span>${g.title}</span>
                    <span style="font-size:10.5px; background:#f1f5f9; padding:1px 6px; border-radius:4px; font-weight:700; color:#64748b;">${g.list.length}</span>
                `;
                container.appendChild(header);

                // Models in this group
                g.list.forEach(m => {
                    const row = document.createElement(\'div\');
                    row.className = \'md-model-row-white\' + (currentSelectedModel && currentSelectedModel.id === m.id ? \' active\' : \'\');
                    row.style.display = \'flex\';
                    row.style.alignItems = \'center\';
                    row.style.justifyContent = \'space-between\';
                    row.style.padding = \'8px 10px\';
                    row.style.margin = \'2px 0\';
                    row.style.borderRadius = \'8px\';
                    row.style.cursor = \'pointer\';
                    row.style.transition = \'all 0.15s ease\';

                    row.onclick = () => selectModel(m.id);
                    row.onmouseenter = () => updateModelDetails(m);

                    row.innerHTML = `
                        <div class="md-item-left-white" style="display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-bolt" style="color:${g.iconColor}; font-size:13px;"></i>
                            <span style="font-weight:700; font-size:13px; color:#0f172a;">${m.name}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <span class="md-item-badge-white" style="color:#15803d; background:#dcfce7; border:1px solid #bbf7d0; font-size:10.5px; font-weight:800; padding:1px 6px; border-radius:6px;">✨ Free</span>
                            <span class="md-item-badge-white" style="font-size:10.5px; padding:1px 6px; border-radius:6px; background:#f1f5f9; border:1px solid #e2e8f0; color:#64748b; font-weight:600;">${m.badge || \'128K\'}</span>
                        </div>
                    `;
                    container.appendChild(row);
                });
            });
        }';

$aiPhp = str_replace($oldRenderModels, $newRenderModels, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully categorized and grouped all models with filter tabs!\n";
