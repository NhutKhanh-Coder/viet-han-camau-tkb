
    (function() {
        let studioModel = 'glm-4.7-flash-free';
        let studioEffort = 'High';
        const SESSIONS_STORAGE_KEY = 'vt_cosmic_chat_sessions_v2';
        let chatSessions = [];
        let activeSessionId = null;
        let welcomeTemplateHtml = '';

        // Antigravity Models Catalog (Grouped & Clean - 27 Models)
        const AG_MODELS_DATA = [
            {
                category: 'cosmic',
                catName: '🌌 Kira Cosmic & GLM (Bản quyền trường)',
                models: [
                    { id: 'glm-4.7-flash-free', name: 'GLM 4.7 Flash Free', effort: 'High', fast: true, desc: 'Mô hình siêu tốc độ, miễn phí, tiếng Việt tự nhiên (Mặc định)', hasEffort: true },
                    { id: 'kira-3.5-pro', name: 'Kira 3.5 Pro', effort: 'High', fast: false, desc: 'Bản quyền trường, suy luận logic chuyên sâu', hasEffort: true },
                    { id: 'kira-3.5-flash', name: 'Kira 3.5 Flash', effort: 'Medium', fast: true, desc: 'Đa nhiệm, phản hồi chớp nhoáng', hasEffort: true },
                    { id: 'kira-mini-1.0', name: 'Kira Mini 1.0', effort: 'Low', fast: true, desc: 'Tối ưu cho câu hỏi ngắn và tra cứu nhanh', hasEffort: true }
                ]
            },
            {
                category: 'gemini',
                catName: '🌟 Google Gemini',
                models: [
                    { id: 'gemini-2.5-flash', name: 'Gemini 2.5 Flash', effort: 'High', fast: true, desc: 'Đa phương thức thế hệ mới của Google, cực nhanh & chuẩn', hasEffort: true },
                    { id: 'gemini-2.5-pro', name: 'Gemini 2.5 Pro', effort: 'High', fast: false, desc: 'Tư duy phức tạp, phân tích khoa học và logic đa bước', hasEffort: true },
                    { id: 'gemini-1.5-flash', name: 'Gemini 1.5 Flash', effort: 'Medium', fast: true, desc: 'Ngữ cảnh lớn, xử lý tài liệu học tập tốc độ cao', hasEffort: true }
                ]
            },
            {
                category: 'claude',
                catName: '🧠 Anthropic Claude',
                models: [
                    { id: 'claude-3.7-sonnet', name: 'Claude 3.7 Sonnet', effort: 'Thinking', fast: false, desc: 'Tư duy phản biện và khả năng lập luận vượt trội', hasEffort: false },
                    { id: 'claude-3.5-sonnet', name: 'Claude 3.5 Sonnet', effort: 'Thinking', fast: false, desc: 'Chuẩn mực code, phân tích ngữ nghĩa và dịch thuật mượt mà', hasEffort: false },
                    { id: 'claude-3.5-haiku', name: 'Claude 3.5 Haiku', effort: 'Medium', fast: true, desc: 'Tốc độ phản hồi tức thì, ngắn gọn và súc tích', hasEffort: true }
                ]
            },
            {
                category: 'openai',
                catName: '⚡ OpenAI & xAI Flagship',
                models: [
                    { id: 'gpt-4o', name: 'GPT-4o (Omni)', effort: 'High', fast: true, desc: 'Mô hình toàn cầu hàng đầu từ OpenAI', hasEffort: true },
                    { id: 'gpt-4o-mini', name: 'GPT-4o Mini', effort: 'Low', fast: true, desc: 'Siêu nhẹ, phản hồi chớp mắt', hasEffort: true },
                    { id: 'o1-preview', name: 'OpenAI o1 (Thinking)', effort: 'Thinking', fast: false, desc: 'Suy luận logic từng bước chuyên sâu cho toán và khoa học', hasEffort: false },
                    { id: 'grok-4.5', name: 'Grok 4.5 Cosmic', effort: 'High', fast: false, desc: 'Sáng tạo không giới hạn, tư duy mở', hasEffort: true }
                ]
            },
            {
                category: 'deepseek',
                catName: '🧮 DeepSeek Series',
                models: [
                    { id: 'deepseek/deepseek-v4-pro', name: 'DeepSeek V4 Pro', effort: 'High', fast: false, desc: 'Chuyên gia logic, giải thuật và toán học chuyên sâu', hasEffort: true },
                    { id: 'deepseek/deepseek-v4-flash', name: 'DeepSeek V4 Flash', effort: 'Medium', fast: true, desc: 'Xử lý tốc độ cao, tối ưu phân tích nhanh', hasEffort: true },
                    { id: 'deepseek/deepseek-v3.2', name: 'DeepSeek V3.2', effort: 'Medium', fast: false, desc: 'Cân bằng ngữ cảnh và năng lực tổng quát', hasEffort: true },
                    { id: 'deepseek/deepseek-chat-v3.1', name: 'DeepSeek Chat V3.1', effort: 'Low', fast: true, desc: 'Đối thoại tự nhiên, giải bài tập học thuật', hasEffort: true }
                ]
            },
            {
                category: 'qwen',
                catName: '🔮 Alibaba Qwen Series',
                models: [
                    { id: 'qwen/qwen3.8-max', name: 'Qwen 3.8 Max', effort: 'High', fast: false, desc: 'Siêu ngữ cảnh 1M token cho tài liệu dung lượng lớn', hasEffort: true },
                    { id: 'qwen/qwen3.7-max', name: 'Qwen 3.7 Max', effort: 'High', fast: false, desc: 'Thông minh vượt trội, xử lý dữ liệu phức tạp', hasEffort: true },
                    { id: 'qwen/qwen3.7-flash', name: 'Qwen 3.7 Flash', effort: 'Medium', fast: true, desc: 'Phản hồi chớp nhoáng, tiết kiệm tài nguyên', hasEffort: true },
                    { id: 'qwen/qwen3-coder-plus', name: 'Qwen 3 Coder Plus', effort: 'High', fast: true, desc: 'Chuyên gia lập trình PHP, JavaScript, SQL', hasEffort: true },
                    { id: 'qwen/qwen3.5-flash', name: 'Qwen 3.5 Flash', effort: 'Low', fast: true, desc: 'Nhẹ nhàng, trả lời ngắn gọn', hasEffort: true }
                ]
            },
            {
                category: 'mistral',
                catName: '🌪️ Mistral & Codestral',
                models: [
                    { id: 'mistralai/codestral-2508', name: 'Codestral 2508', effort: 'High', fast: true, desc: 'Tối ưu cho viết mã và bắt lỗi cú pháp PHP/SQL', hasEffort: true },
                    { id: 'mistralai/mistral-large-2512', name: 'Mistral Large 2512', effort: 'High', fast: false, desc: 'Phân tích tài liệu lớn và lý luận logic', hasEffort: true },
                    { id: 'mistralai/ministral-14b', name: 'Ministral 14B', effort: 'Medium', fast: true, desc: 'Trợ lý học thuật gọn nhẹ, chính xác', hasEffort: true }
                ]
            },
            {
                category: 'minimax',
                catName: '🚀 MiniMax & Tencent',
                models: [
                    { id: 'minimax/minimax-m2.7-highspeed', name: 'MiniMax M2.7 Highspeed', effort: 'High', fast: true, desc: '1M Token Context, phân tích tài liệu siêu tốc', hasEffort: true },
                    { id: 'minimax/minimax-m2.5', name: 'MiniMax M2.5', effort: 'Medium', fast: false, desc: 'Phân tích đa chiều, tóm tắt tài liệu', hasEffort: true },
                    { id: 'minimax/minimax-m2.1-highspeed', name: 'MiniMax M2.1 Highspeed', effort: 'Medium', fast: true, desc: 'Siêu mượt, phản hồi nhanh chóng', hasEffort: true },
                    { id: 'tencent/hy3', name: 'Tencent Hy3', effort: 'Low', fast: true, desc: 'Trợ lý Agent thông minh, tìm kiếm thông tin', hasEffort: true }
                ]
            }
        ];

        let currentAgCat = 'all';
        let currentAgSearch = '';

        function escapeAgHtml(str) {
            if (!str) return '';
            return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function findAgModel(id) {
            for (let c of AG_MODELS_DATA) {
                for (let m of c.models) {
                    if (m.id === id) return m;
                }
            }
            return null;
        }

        function updateAgTriggerUI(name, effort, fast) {
            const nameEl = document.getElementById('agCurModelName');
            const effortEl = document.getElementById('agCurEffort');
            const fastEl = document.getElementById('agCurFast');
            if (nameEl) nameEl.textContent = name;
            if (effortEl) effortEl.textContent = effort || 'Medium';
            if (fastEl) {
                if (fast) {
                    fastEl.style.display = 'inline-block';
                    fastEl.textContent = 'Fast';
                } else {
                    fastEl.style.display = 'none';
                }
            }
        }

        // Render Antigravity Model List
        window.renderAgModelList = function(searchQuery = '', activeCat = 'all') {
            const listEl = document.getElementById('agModelList');
            if (!listEl) return;

            const q = (searchQuery || '').trim().toLowerCase();
            let html = '';
            let totalFound = 0;

            AG_MODELS_DATA.forEach(cat => {
                if (activeCat !== 'all' && cat.category !== activeCat) return;

                const filtered = cat.models.filter(m => {
                    if (!q) return true;
                    return m.name.toLowerCase().includes(q) || 
                           m.id.toLowerCase().includes(q) || 
                           (m.desc && m.desc.toLowerCase().includes(q));
                });

                if (filtered.length === 0) return;
                totalFound += filtered.length;

                html += `<div class="ag-group-label">${cat.catName}</div>`;

                filtered.forEach(m => {
                    const isSelected = (m.id === studioModel);
                    const currentEffort = isSelected ? studioEffort : m.effort;

                    html += `
                    <div class="ag-model-item ${isSelected ? 'selected' : ''}" 
                         onclick="onAgModelRowClick(event, '${m.id}', '${escapeAgHtml(m.name)}', '${m.effort}', ${m.fast})">
                        <div class="ag-item-left">
                            <span class="ag-item-dot"></span>
                            <span class="ag-item-name" title="${escapeAgHtml(m.name)}">${m.name}</span>
                        </div>
                        <div class="ag-item-right">
                            <span class="ag-item-effort">${currentEffort}</span>
                            ${m.fast ? `<span class="ag-item-fast-badge">Fast</span>` : ''}
                            <span class="ag-item-info" title="${escapeAgHtml(m.desc)}" onclick="event.stopPropagation()">ⓘ</span>
                            ${m.hasEffort ? `
                            <span class="ag-item-arrow">›</span>
                            <div class="ag-effort-submenu" onclick="event.stopPropagation()">
                                <button type="button" class="ag-sub-btn ${(isSelected && studioEffort === 'Low') ? 'selected' : ''}" 
                                        onclick="selectAgModel('${m.id}', 'Low', '${escapeAgHtml(m.name)}', ${m.fast})">Low</button>
                                <button type="button" class="ag-sub-btn ${(isSelected && studioEffort === 'Medium') ? 'selected' : ''}" 
                                        onclick="selectAgModel('${m.id}', 'Medium', '${escapeAgHtml(m.name)}', ${m.fast})">Medium</button>
                                <button type="button" class="ag-sub-btn ${(isSelected && studioEffort === 'High') ? 'selected' : ''}" 
                                        onclick="selectAgModel('${m.id}', 'High', '${escapeAgHtml(m.name)}', ${m.fast})">High</button>
                            </div>` : ''}
                        </div>
                    </div>`;
                });
            });

            if (totalFound === 0) {
                html = `<div style="padding: 24px 12px; text-align: center; color: #71717a; font-size: 12px;">Không tìm thấy model phù hợp</div>`;
            }

            listEl.innerHTML = html;
        };

        window.setAntigravityCategory = function(cat, btn) {
            currentAgCat = cat;
            document.querySelectorAll('.ag-tab-btn').forEach(b => b.classList.remove('active'));
            if (btn) {
                btn.classList.add('active');
                try {
                    btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } catch(e) {}
            }
            renderAgModelList(currentAgSearch, currentAgCat);
        };

        window.scrollAgTabs = function(delta) {
            const tabs = document.getElementById('agCategoryTabs');
            if (tabs) {
                tabs.scrollBy({ left: delta, behavior: 'smooth' });
            }
        };

        function initDraggableTabs() {
            const tabsContainer = document.getElementById('agCategoryTabs');
            if (!tabsContainer) return;

            let isDown = false;
            let startX = 0;
            let scrollLeft = 0;
            let hasDragged = false;

            tabsContainer.addEventListener('mousedown', (e) => {
                isDown = true;
                hasDragged = false;
                tabsContainer.classList.add('grabbing');
                startX = e.pageX - tabsContainer.offsetLeft;
                scrollLeft = tabsContainer.scrollLeft;
            });

            window.addEventListener('mouseup', () => {
                if (isDown) {
                    isDown = false;
                    tabsContainer.classList.remove('grabbing');
                }
            });

            tabsContainer.addEventListener('mouseleave', () => {
                if (isDown) {
                    isDown = false;
                    tabsContainer.classList.remove('grabbing');
                }
            });

            tabsContainer.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - tabsContainer.offsetLeft;
                const walk = (x - startX) * 1.5;
                if (Math.abs(walk) > 4) {
                    hasDragged = true;
                }
                tabsContainer.scrollLeft = scrollLeft - walk;
            });

            // Wheel horizontal scroll
            tabsContainer.addEventListener('wheel', (e) => {
                if (e.deltaY !== 0) {
                    e.preventDefault();
                    tabsContainer.scrollLeft += e.deltaY;
                }
            }, { passive: false });

            // Prevent tab button click if user was dragging
            tabsContainer.querySelectorAll('.ag-tab-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    if (hasDragged) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    }
                }, true);
            });
        }

        window.filterAntigravityModels = function(query) {
            currentAgSearch = query;
            renderAgModelList(currentAgSearch, currentAgCat);
        };

        window.toggleAgDropdown = function(e) {
            if (e) e.stopPropagation();
            const dd = document.getElementById('vtAntigravityDropdown');
            const trig = document.getElementById('vtModelTrigger');
            if (!dd || !trig) return;

            const isOpen = dd.classList.contains('open');
            if (isOpen) {
                closeAgDropdown();
            } else {
                dd.classList.add('open');
                trig.classList.add('active');
                renderAgModelList(currentAgSearch, currentAgCat);
                const searchInput = document.getElementById('agModelSearch');
                if (searchInput) {
                    setTimeout(() => searchInput.focus(), 100);
                }
            }
        };

        window.closeAgDropdown = function() {
            const dd = document.getElementById('vtAntigravityDropdown');
            const trig = document.getElementById('vtModelTrigger');
            if (dd) dd.classList.remove('open');
            if (trig) trig.classList.remove('active');
        };

        window.onAgModelRowClick = function(e, id, name, defaultEffort, fast) {
            if (e && e.target && e.target.closest('.ag-effort-submenu')) return;
            selectAgModel(id, defaultEffort || 'High', name, fast);
        };

        window.selectAgModel = function(id, effort, name, fast) {
            studioModel = id;
            studioEffort = effort || 'Medium';

            updateAgTriggerUI(name, studioEffort, fast);

            // Update hidden select if exists
            const sel = document.getElementById('studioModelSelect');
            if (sel) {
                let opt = sel.querySelector(`option[value="${id}"]`);
                if (!opt) {
                    opt = document.createElement('option');
                    opt.value = id;
                    opt.text = name;
                    sel.appendChild(opt);
                }
                sel.value = id;
            }

            try {
                localStorage.setItem('vt_studio_model', id);
                localStorage.setItem('vt_studio_effort', studioEffort);
            } catch(e) {}

            showStToast(`⚡ Đã kích hoạt Model: <b>${name}</b> <span style="color:#38bdf8;font-size:11px;">(${studioEffort})</span>`);

            if (activeSessionId) {
                const s = chatSessions.find(item => item.id === activeSessionId);
                if (s) {
                    s.model = id;
                    saveSessions();
                }
            }

            closeAgDropdown();
            renderAgModelList(currentAgSearch, currentAgCat);
        };

        document.addEventListener('click', function(e) {
            const container = document.getElementById('vtModelPickerContainer');
            if (container && !container.contains(e.target)) {
                closeAgDropdown();
            }
        });

        // Toast
        window.showStToast = function(msg) {
            const t = document.getElementById('vtToast');
            if (!t) return;
            t.innerHTML = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 2400);
        };

        // Model selector fallback
        window.onStudioModelChange = function(m) {
            studioModel = m;
            const found = findAgModel(m);
            if (found) {
                updateAgTriggerUI(found.name, studioEffort, found.fast);
            }
            if (activeSessionId) {
                const s = chatSessions.find(item => item.id === activeSessionId);
                if (s) {
                    s.model = m;
                    saveSessions();
                }
            }
        };

        // =====================================================================
        // ★ CHAT SESSIONS & HISTORY ENGINE ★
        // =====================================================================
        function loadSessions() {
            try {
                const raw = localStorage.getItem(SESSIONS_STORAGE_KEY);
                chatSessions = raw ? JSON.parse(raw) : [];
                if (!Array.isArray(chatSessions)) chatSessions = [];
                // Sort by latest updated
                chatSessions.sort((a, b) => (b.updatedAt || 0) - (a.updatedAt || 0));
            } catch (e) {
                chatSessions = [];
            }
        }

        function saveSessions() {
            try {
                localStorage.setItem(SESSIONS_STORAGE_KEY, JSON.stringify(chatSessions));
            } catch (e) {}
        }

        function updateHistoryBadge() {
            const badge = document.getElementById('historyBadge');
            if (badge) badge.innerText = chatSessions.length;
            const badgeTop = document.getElementById('historyBadgeTop');
            if (badgeTop) badgeTop.innerText = chatSessions.length;
        }

        function formatSessionTime(timestamp) {
            if (!timestamp) return '';
            const d = new Date(timestamp);
            const now = new Date();
            const isToday = d.toDateString() === now.toDateString();
            const hours = String(d.getHours()).padStart(2, '0');
            const mins = String(d.getMinutes()).padStart(2, '0');
            if (isToday) {
                return `${hours}:${mins}`;
            }
            const yesterday = new Date();
            yesterday.setDate(now.getDate() - 1);
            if (d.toDateString() === yesterday.toDateString()) {
                return `Hôm qua ${hours}:${mins}`;
            }
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            return `${day}/${month} ${hours}:${mins}`;
        }

        window.renderHistorySessionsList = function(filterText = '') {
            const listEl = document.getElementById('vtHistoryList');
            if (!listEl) return;

            let filtered = chatSessions;
            if (filterText) {
                const ft = filterText.toLowerCase();
                filtered = chatSessions.filter(s => 
                    (s.title && s.title.toLowerCase().includes(ft)) || 
                    (s.model && s.model.toLowerCase().includes(ft))
                );
            }

            if (!filtered.length) {
                listEl.innerHTML = `
                    <div class="drawer-empty-state">
                        <i class="fa-solid fa-comments"></i>
                        <h4>${filterText ? 'Không tìm thấy' : 'Chưa có đoạn chat'}</h4>
                        <p>${filterText ? 'Thử tìm từ khóa khác' : 'Các đoạn chat sẽ tự động lưu ở đây.'}</p>
                    </div>
                `;
                return;
            }

            listEl.innerHTML = filtered.map(s => {
                const isActive = s.id === activeSessionId ? ' active' : '';
                const msgCount = (s.messages && s.messages.length) || 0;
                const timeStr = formatSessionTime(s.updatedAt || s.createdAt || Date.now());
                const modelShort = (s.model || 'glm-4.7').split('/')[1] || s.model || 'cosmic';
                const safeTitle = (s.title || 'Cuộc hội thoại').replace(/</g, '&lt;').replace(/>/g, '&gt;');

                return `
                    <div class="session-item${isActive}" onclick="openStudioSession('${s.id}')">
                        <div class="session-item-header">
                            <div class="session-title" title="${safeTitle}">${safeTitle}</div>
                            <button type="button" class="session-del-btn" onclick="deleteStudioSession('${s.id}', event)" title="Xoá đoạn chat này">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                        <div class="session-meta">
                            <div class="session-meta-left">
                                <span class="session-badge"><i class="fa-solid fa-bolt"></i> ${modelShort}</span>
                                <span class="session-msg-count">${msgCount} tin</span>
                            </div>
                            <span class="session-time">${timeStr}</span>
                        </div>
                    </div>
                `;
            }).join('');
        };

        window.toggleStudioHistory = function(force) {
            const sidebar = document.getElementById('vtHistoryDrawer');
            const backdrop = document.getElementById('vtHistoryBackdrop');
            if (!sidebar) return;

            const isMobile = window.innerWidth <= 860;

            if (isMobile) {
                const isOpen = sidebar.classList.contains('mobile-open');
                const nextState = (typeof force === 'boolean') ? force : !isOpen;
                if (nextState) {
                    sidebar.classList.add('mobile-open');
                    if (backdrop) backdrop.classList.add('open');
                    const searchInp = document.getElementById('historySearchInput');
                    if (searchInp) setTimeout(() => searchInp.focus(), 150);
                    renderHistorySessionsList();
                } else {
                    sidebar.classList.remove('mobile-open');
                    if (backdrop) backdrop.classList.remove('open');
                }
            } else {
                const isCollapsed = sidebar.classList.contains('collapsed');
                const shouldCollapse = (typeof force === 'boolean') ? !force : !isCollapsed;
                if (shouldCollapse) {
                    sidebar.classList.add('collapsed');
                } else {
                    sidebar.classList.remove('collapsed');
                    const searchInp = document.getElementById('historySearchInput');
                    if (searchInp) setTimeout(() => searchInp.focus(), 150);
                    renderHistorySessionsList();
                }
            }
        };

        window.filterHistoryList = function(q) {
            renderHistorySessionsList(q.trim());
        };

        window.startNewStudioChat = function(skipToast = false) {
            activeSessionId = null;
            const stream = document.getElementById('studioStream');
            if (stream && welcomeTemplateHtml) {
                stream.innerHTML = welcomeTemplateHtml;
            }
            // Clear server conversation context
            fetch('/tkb/api/admin_ai_api.php?action=clear', { method: 'POST' }).catch(() => {});

            if (window.innerWidth <= 860) {
                toggleStudioHistory(false);
            }
            renderHistorySessionsList();
            if (!skipToast) showStToast('✨ Đã bắt đầu cuộc trò chuyện mới!');
            const inp = document.getElementById('studioInput');
            if (inp) inp.focus();
        };

        window.openStudioSession = function(sid) {
            const sess = chatSessions.find(s => s.id === sid);
            if (!sess) return;

            activeSessionId = sid;
            const stream = document.getElementById('studioStream');
            if (!stream) return;

            stream.innerHTML = '';

            // Update model selector if specified
            if (sess.model) {
                studioModel = sess.model;
                const found = findAgModel(sess.model);
                if (found) {
                    updateAgTriggerUI(found.name, found.effort, found.fast);
                } else {
                    updateAgTriggerUI(sess.model, 'High', false);
                }
                const sel = document.getElementById('studioModelSelect');
                if (sel) sel.value = sess.model;
            }

            // Render all historical messages
            if (Array.isArray(sess.messages) && sess.messages.length > 0) {
                sess.messages.forEach(m => {
                    appendStudioMsg(m.role, m.text, m.time, m.model || sess.model, m.image || null);
                });
            } else if (welcomeTemplateHtml) {
                stream.innerHTML = welcomeTemplateHtml;
            }

            if (window.innerWidth <= 860) {
                toggleStudioHistory(false);
            }
            renderHistorySessionsList();
            showStToast('📂 Đã mở: ' + (sess.title.length > 25 ? sess.title.slice(0, 25) + '...' : sess.title));
            scrollStudioBottom();
        };

        window.deleteStudioSession = function(sid, ev) {
            if (ev) ev.stopPropagation();
            if (!confirm('Bạn có chắc chắn muốn xoá đoạn hội thoại này khỏi lịch sử?')) return;

            chatSessions = chatSessions.filter(s => s.id !== sid);
            saveSessions();
            updateHistoryBadge();

            if (activeSessionId === sid) {
                startNewStudioChat(true);
            } else {
                renderHistorySessionsList();
            }
            showStToast('🗑️ Đã xoá đoạn chat!');
        };

        window.clearAllStudioHistory = function() {
            if (!chatSessions.length) {
                showStToast('Lịch sử hiện đang trống');
                return;
            }
            if (!confirm('Bạn có chắc muốn xoá TOÀN BỘ lịch sử đoạn chat? Hành động này không thể hoàn tác.')) return;

            chatSessions = [];
            saveSessions();
            updateHistoryBadge();
            startNewStudioChat(true);
            showStToast('🗑️ Đã xoá toàn bộ lịch sử!');
        };

        // Auto-resize textarea
        window.autoResizeStudioInput = function(tx) {
            tx.style.height = 'auto';
            tx.style.height = Math.min(tx.scrollHeight, 100) + 'px';
        };

        window.handleStudioKey = function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendStudioMessage();
            }
        };

        window.submitStudioPrompt = function(text) {
            const inp = document.getElementById('studioInput');
            if (inp) {
                inp.value = text;
                sendStudioMessage();
            }
        };

        function scrollStudioBottom() {
            const s = document.getElementById('studioStream');
            if (s) s.scrollTop = s.scrollHeight;
        }

        // Markdown Formatter
        function formatStMarkdown(text) {
            if (!text) return '';
            let e = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            e = e.replace(/```([a-zA-Z0-9_\-\+]*)\n([\s\S]*?)```/g, function(m, lang, code) {
                const cid = 'cblock_' + Math.random().toString(36).substr(2, 8);
                return `<pre><span style="font-size:11px;font-weight:700;color:var(--vt-cyan);display:block;margin-bottom:6px;"><i class="fa-solid fa-code"></i> ${lang ? lang.toUpperCase() : 'CODE'}</span><button class="code-copy-btn" onclick="copyStCode('${cid}')"><i class="fa-regular fa-copy"></i> Sao chép</button><code id="${cid}">${code.trim()}</code></pre>`;
            });
            e = e.replace(/`([^`]+)`/g, '<code>$1</code>');
            e = e.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            e = e.replace(/\*([^*]+)\*/g, '<em>$1</em>');
            e = e.replace(/(?:^|\n)[•\-*]\s+([^\n]+)/g, '<br><span style="color:var(--vt-purple);">◆</span> $1');
            e = e.replace(/(?:^|\n)###\s+([^\n]+)/g, '<h4 style="color:var(--vt-cyan);margin:8px 0 4px;font-size:14px;">$1</h4>');
            e = e.replace(/(?:^|\n)##\s+([^\n]+)/g, '<h3 style="color:var(--vt-purple);margin:10px 0 6px;font-size:16px;">$1</h3>');
            e = e.replace(/(?:^|\n)#\s+([^\n]+)/g, '<h2 style="color:#ffffff;margin:12px 0 8px;font-size:18px;">$1</h2>');
            e = e.replace(/\n\n/g, '<br><br>');
            e = e.replace(/\n/g, '<br>');
            return e;
        }

        window.copyStCode = function(id) {
            const el = document.getElementById(id);
            if (!el) return;
            navigator.clipboard.writeText(el.innerText || el.textContent)
                .then(() => showStToast('★ Đã sao chép mã!'))
                .catch(() => showStToast('✖ Lỗi sao chép'));
        };

        function appendStudioMsg(role, text, time, modelTag, attachedImg) {
            const stream = document.getElementById('studioStream');
            if (!stream) return;
            const t = time || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const tag = modelTag || studioModel;
            const row = document.createElement('div');
            row.className = 'msg-row ' + (role === 'user' ? 'user' : 'bot');
            const fmt = formatStMarkdown(text);

            if (role === 'user') {
                const imgHtml = attachedImg ? `
                    <div class="msg-attached-img-wrap" onclick="openStudioImgLightbox('${attachedImg}')" title="Bấm để xem ảnh phóng to">
                        <img src="${attachedImg}" alt="Ảnh đính kèm" class="msg-attached-img">
                        <div class="msg-img-overlay-zoom"><i class="fa-solid fa-expand"></i> Phóng to</div>
                    </div>
                ` : '';
                row.innerHTML = `
                    <div class="msg-user-avatar">
                        <i class="fa-solid fa-user-astronaut"></i>
                    </div>
                    <div>
                        <div class="msg-bubble-box">
                            ${imgHtml}
                            ${fmt ? `<div>${fmt}</div>` : ''}
                        </div>
                        <div class="msg-time-lbl"><span>${t}</span> <i class="fa-solid fa-check" style="color:var(--vt-cyan);"></i></div>
                    </div>
                `;
            } else {
                row.innerHTML = `
                    <div class="msg-bot-avatar">
                        <img src="${getPersonaAvatarUrl('circle')}" alt="Vũ Trụ AI" onerror="this.src='/tkb/assets/ai/vutru_robot_circle.png'">
                    </div>
                    <div>
                        <div class="msg-bubble-box">${fmt}</div>
                        <div class="msg-time-lbl">
                            <i class="fa-solid fa-bolt" style="color:var(--vt-green);"></i> ${t} // ${tag}
                            <button type="button" class="msg-speak-btn" onclick="speakStudioText(this, decodeURIComponent('${encodeURIComponent(text)}'))" title="Nghe Keria đọc">
                                <i class="fa-solid fa-volume-high"></i> <span>Đọc</span>
                            </button>
                        </div>
                    </div>
                `;
            }
            stream.appendChild(row);
            scrollStudioBottom();
        }

        // =====================================================================
        // ★ IMAGE ATTACHMENT & VISION AI CLIENT LOGIC ★
        // =====================================================================
        let currentAttachedImageBase64 = null;

        window.triggerStudioImageUpload = function() {
            const inp = document.getElementById('studioImageInput');
            if (inp) {
                inp.value = '';
                inp.click();
            }
        };

        window.handleStudioImageFile = function(input) {
            if (!input.files || !input.files[0]) return;
            processStudioImageFile(input.files[0]);
        };

        window.removeStudioAttachedImage = function() {
            currentAttachedImageBase64 = null;
            const bar = document.getElementById('studioImgPreviewBar');
            const input = document.getElementById('studioImageInput');
            if (bar) bar.style.display = 'none';
            if (input) input.value = '';
        };

        function processStudioImageFile(file) {
            if (!file || !file.type.startsWith('image/')) {
                showStToast('✖ Vui lòng chọn tệp định dạng hình ảnh!');
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                const rawData = e.target.result;
                compressImageBase64(rawData, 1600, 0.88, function(optimizedData) {
                    currentAttachedImageBase64 = optimizedData;
                    const bar = document.getElementById('studioImgPreviewBar');
                    const img = document.getElementById('studioPreviewImg');
                    const nameEl = document.getElementById('studioPreviewName');
                    const sizeEl = document.getElementById('studioPreviewSize');

                    if (img) img.src = optimizedData;
                    if (nameEl) nameEl.textContent = file.name || 'anh_dinh_kem.png';
                    if (sizeEl) {
                        const kb = Math.round(optimizedData.length * 0.75 / 1024);
                        sizeEl.textContent = kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB';
                    }
                    if (bar) bar.style.display = 'flex';
                    showStToast('📷 Đã đính kèm ảnh thành công!');
                    const tx = document.getElementById('studioInput');
                    if (tx) tx.focus();
                });
            };
            reader.readAsDataURL(file);
        }

        function compressImageBase64(dataUrl, maxDim, quality, callback) {
            const img = new Image();
            img.onload = function() {
                let w = img.width;
                let h = img.height;
                if (w > maxDim || h > maxDim) {
                    if (w > h) {
                        h = Math.round((h * maxDim) / w);
                        w = maxDim;
                    } else {
                        w = Math.round((w * maxDim) / h);
                        h = maxDim;
                    }
                }
                const cv = document.createElement('canvas');
                cv.width = w;
                cv.height = h;
                const ctx = cv.getContext('2d');
                ctx.drawImage(img, 0, 0, w, h);
                const mime = dataUrl.startsWith('data:image/png') ? 'image/png' : 'image/jpeg';
                callback(cv.toDataURL(mime, quality));
            };
            img.onerror = function() {
                callback(dataUrl);
            };
            img.src = dataUrl;
        }

        // Lightbox Zoom Modal Controls
        window.openStudioImgLightbox = function(src) {
            const box = document.getElementById('studioImgLightbox');
            const img = document.getElementById('lightboxImg');
            if (box && img) {
                img.src = src;
                box.classList.add('active');
            }
        };

        window.closeStudioImgLightbox = function(e) {
            if (e && e.target && e.target.id === 'lightboxImg') return;
            const box = document.getElementById('studioImgLightbox');
            if (box) box.classList.remove('active');
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeStudioImgLightbox();
        });

        // Clipboard Paste Support (Ctrl+V)
        document.addEventListener('paste', function(e) {
            const items = (e.clipboardData || window.clipboardData)?.items;
            if (!items) return;
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const file = items[i].getAsFile();
                    if (file) {
                        processStudioImageFile(file);
                        showStToast('📋 Đã dán ảnh từ clipboard!');
                        break;
                    }
                }
            }
        });

        // Send Message & Auto-Save to History
        window.sendStudioMessage = async function() {
            const inp = document.getElementById('studioInput');
            const btn = document.getElementById('studioSendBtn');
            const typing = document.getElementById('studioTypingRow');
            if (!inp || !btn) return;

            let val = inp.value.trim();
            const sentImg = currentAttachedImageBase64;

            if (!val && !sentImg) return;

            if (!val && sentImg) {
                val = "Hãy quan sát và phân tích chi tiết hình ảnh này giúp tôi.";
            }

            // Clear input and attached preview
            removeStudioAttachedImage();
            inp.value = '';
            autoResizeStudioInput(inp);
            inp.disabled = true;
            btn.disabled = true;

            const userTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            // Initialize or retrieve active session
            if (!activeSessionId) {
                activeSessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
                const titleStr = (sentImg ? '📷 ' : '') + val.replace(/\s+/g, ' ').trim();
                const sessionTitle = titleStr.length > 36 ? titleStr.slice(0, 36) + '...' : titleStr;
                const newSess = {
                    id: activeSessionId,
                    title: sessionTitle || 'Cuộc trò chuyện mới',
                    model: studioModel,
                    createdAt: Date.now(),
                    updatedAt: Date.now(),
                    messages: []
                };
                chatSessions.unshift(newSess);
            }

            let currSess = chatSessions.find(s => s.id === activeSessionId);
            if (!currSess) {
                currSess = {
                    id: activeSessionId,
                    title: (sentImg ? '📷 ' : '') + val.slice(0, 35),
                    model: studioModel,
                    createdAt: Date.now(),
                    updatedAt: Date.now(),
                    messages: []
                };
                chatSessions.unshift(currSess);
            }

            // Append to session memory
            currSess.messages.push({ role: 'user', text: val, time: userTime, image: sentImg });
            currSess.updatedAt = Date.now();
            currSess.model = studioModel;
            saveSessions();
            updateHistoryBadge();

            appendStudioMsg('user', val, userTime, null, sentImg);
            if (typing) typing.style.display = 'block';
            scrollStudioBottom();

            try {
                const res = await fetch('/tkb/api/admin_ai_api.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: val, model: studioModel, image: sentImg, persona: currentAssistantPersona })
                });
                const data = await res.json();
                if (typing) typing.style.display = 'none';

                const botTime = data.time || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                if (data.success && data.reply) {
                    appendStudioMsg('assistant', data.reply, botTime, studioModel);
                    currSess.messages.push({ role: 'assistant', text: data.reply, time: botTime, model: studioModel });
                } else {
                    const errMsg = '⚠ ' + (data.error || 'Không thể kết nối Vũ Trụ AI Gateway');
                    appendStudioMsg('assistant', errMsg, botTime, studioModel);
                    currSess.messages.push({ role: 'assistant', text: errMsg, time: botTime, model: studioModel });
                }
                currSess.updatedAt = Date.now();
                saveSessions();
                updateHistoryBadge();
            } catch (err) {
                if (typing) typing.style.display = 'none';
                const netErrMsg = '✖ Máy chủ không phản hồi, vui lòng thử lại.';
                const errTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                appendStudioMsg('assistant', netErrMsg, errTime, studioModel);
                if (currSess) {
                    currSess.messages.push({ role: 'assistant', text: netErrMsg, time: errTime, model: studioModel });
                    currSess.updatedAt = Date.now();
                    saveSessions();
                }
            } finally {
                inp.disabled = false;
                btn.disabled = false;
                inp.focus();
                scrollStudioBottom();
            }
        };

        // Export Chat
        window.exportStudioChat = function() {
            const stream = document.getElementById('studioStream');
            if (!stream) return;
            const activeSess = activeSessionId ? chatSessions.find(s => s.id === activeSessionId) : null;
            const sessTitle = activeSess ? activeSess.title : 'Cuộc trò chuyện';

            let content = "=== VŨ TRỤ AI - CHAT LOG ===\n";
            content += "Chủ đề: " + sessTitle + "\n";
            content += "Thời điểm: " + new Date().toLocaleString() + "\n";
            content += "Mô hình: " + studioModel + "\n";
            content += "Trường Cao đẳng Việt - Hàn Cà Mau\n\n";

            stream.querySelectorAll('.msg-row').forEach(r => {
                const isUser = r.classList.contains('user');
                const sender = isUser ? "QUẢN TRỊ VIÊN" : "VŨ TRỤ AI";
                const bubble = r.querySelector('.msg-bubble-box');
                const text = bubble ? (bubble.innerText || bubble.textContent) : '';
                content += `[${sender}]:\n${text}\n\n---\n\n`;
            });

            const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `vutru_ai_${Date.now()}.txt`;
            a.click();
            URL.revokeObjectURL(a.href);
            showStToast('★ Đã xuất nhật ký chat');
        };

        // Speech Recognition
        let isListening = false;
        window.toggleStudioVoice = function() {
            const vBtn = document.getElementById('studioVoiceBtn');
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) { showStToast('Trình duyệt không hỗ trợ microphone'); return; }

            if (!window.stRecognizer) {
                window.stRecognizer = new SR();
                window.stRecognizer.lang = 'vi-VN';
                window.stRecognizer.onresult = function(ev) {
                    const t = ev.results[0][0].transcript;
                    const inp = document.getElementById('studioInput');
                    if (inp) {
                        inp.value = (inp.value ? inp.value + ' ' : '') + t;
                        autoResizeStudioInput(inp);
                    }
                    showStToast('★ Thu âm: "' + t + '"');
                };
                window.stRecognizer.onend = function() { isListening = false; if (vBtn) vBtn.classList.remove('active'); };
                window.stRecognizer.onerror = function() { isListening = false; if (vBtn) vBtn.classList.remove('active'); };
            }

            if (!isListening) {
                try {
                    window.stRecognizer.start();
                    isListening = true;
                    if (vBtn) vBtn.classList.add('active');
                    showStToast('★ Đang lắng nghe...');
                } catch(e) {}
            } else {
                window.stRecognizer.stop();
                isListening = false;
                if (vBtn) vBtn.classList.remove('active');
            }
        };

        // =====================================================================
        // ★ LIVE VOICE CONVERSATION (NÓI CHUYỆN TRỰC TIẾP CÙNG KERIA AI) ★
        // =====================================================================
        let voiceCallActive = false;
        let voiceCallState = 'idle'; // 'idle' | 'listening' | 'thinking' | 'speaking'
        let voiceCallRecognition = null;
        let voiceCallMicEnabled = true;
        let voiceLastUserTranscript = '';

        function cleanTextForSpeech(raw) {
            if (!raw) return '';
            let text = raw;
            // Remove code blocks
            text = text.replace(/```[\s\S]*?```/g, ' [Đoạn mã lập trình đã được lưu vào khung chat] ');
            // Remove inline code
            text = text.replace(/`([^`]+)`/g, '$1');
            // Remove markdown images and links
            text = text.replace(/!\[.*?\]\(.*?\)/g, '');
            text = text.replace(/\[([^\]]+)\]\(.*?\)/g, '$1');
            // Remove headers, bold, italics, bullets, blockquotes
            text = text.replace(/^#{1,6}\s+/gm, '');
            text = text.replace(/(\*\*|__)(.*?)\1/g, '$2');
            text = text.replace(/(\*|_)(.*?)\1/g, '$2');
            text = text.replace(/^\s*[-*+]\s+/gm, '');
            text = text.replace(/^\s*>\s+/gm, '');
            text = text.replace(/\|.*?\|/g, ''); // tables
            text = text.replace(/\n+/g, '. ');
            text = text.replace(/\s+/g, ' ').trim();
            // Keep speech concise for audio call
            if (text.length > 380) {
                text = text.slice(0, 380) + '... Nội dung chi tiết đã được Keria gửi đầy đủ vào khung chat nhé!';
            }
            return text;
        }

        let currentVoiceGender = localStorage.getItem('vt_ai_voice_gender') || 'female';
        let currentAssistantPersona = localStorage.getItem('vt_assistant_persona') || 'robot'; // 'robot' | 'anime'

        function getPersonaAvatarUrl(style = 'normal') {
            const customUserAvatar = localStorage.getItem('vt_custom_robot_avatar_user');
            if (currentAssistantPersona === 'anime') {
                return (style === 'circle')
                    ? '/tkb/assets/ai/vutru_anime_circle.png'
                    : '/tkb/assets/ai/vutru_anime_assistant.png';
            } else {
                if (customUserAvatar) return customUserAvatar;
                return (style === 'circle')
                    ? '/tkb/assets/ai/vutru_robot_circle.png'
                    : '/tkb/assets/ai/vutru_keria_assistant.png';
            }
        }

        window.setAssistantPersona = function(persona, skipSpeak = false) {
            currentAssistantPersona = (persona === 'anime') ? 'anime' : 'robot';
            try {
                localStorage.setItem('vt_assistant_persona', currentAssistantPersona);
            } catch(e) {}

            updateAssistantPersonaUI();

            const isAnime = (currentAssistantPersona === 'anime');
            const toastMsg = isAnime 
                ? '🌸 Đã kích hoạt Trợ lý Vũ Trụ Anime (Hikari)!' 
                : '🤖 Đã chọn Trợ lý Robot Vũ Trụ!';
            showStToast(toastMsg);

            // If in Voice Call, update speaker text & avatar
            if (voiceCallActive) {
                const mascotImg = document.getElementById('voiceCallMascotImg');
                if (mascotImg) mascotImg.src = getPersonaAvatarUrl('normal');

                const title = document.getElementById('voiceCallLiveTitle');
                if (title) {
                    title.textContent = isAnime 
                        ? '🌸 VŨ TRỤ AI ANIME LIVE' 
                        : ((currentVoiceGender === 'male') ? 'VŨ TRỤ AI (GIỌNG NAM)' : 'VŨ TRỤ AI (GIỌNG NỮ)');
                }

                // Auto-tune voice to female for anime assistant
                if (isAnime && currentVoiceGender !== 'female') {
                    setVoiceGender('female');
                }

                const sampleText = isAnime
                    ? 'Keria ơi! Em là trợ lý Anime Vũ Trụ AI đây! Rất vui được trò chuyện cùng Keria nha~ (◕‿◕)✨'
                    : ((currentVoiceGender === 'male')
                        ? 'Xin chào Keria! Tôi là Robot Vũ Trụ AI. Hãy nói điều gì đó!'
                        : 'Xin chào Keria! Em là Robot Vũ Trụ AI. Hãy nói điều gì đó!');

                const bText = document.getElementById('voiceBotText');
                if (bText) bText.textContent = '"' + sampleText + '"';

                if (!skipSpeak && voiceCallState !== 'thinking') {
                    setVoiceCallState('speaking');
                    speakVoiceReply(sampleText, function() {
                        if (voiceCallActive && voiceCallMicEnabled) {
                            startVoiceCallListening();
                        }
                    });
                }
            }
        };

        function updateAssistantPersonaUI() {
            const isAnime = (currentAssistantPersona === 'anime');

            // Square card persona buttons
            const kRobotBtn = document.getElementById('kPersonaRobotBtn');
            const kAnimeBtn = document.getElementById('kPersonaAnimeBtn');
            if (kRobotBtn && kAnimeBtn) {
                if (isAnime) {
                    kAnimeBtn.classList.add('active', 'anime');
                    kRobotBtn.classList.remove('active');
                } else {
                    kRobotBtn.classList.add('active');
                    kAnimeBtn.classList.remove('active', 'anime');
                }
            }

            // Voice call modal persona buttons
            const vRobotBtn = document.getElementById('vPersonaRobotBtn');
            const vAnimeBtn = document.getElementById('vPersonaAnimeBtn');
            if (vRobotBtn && vAnimeBtn) {
                if (isAnime) {
                    vAnimeBtn.classList.add('active', 'anime');
                    vRobotBtn.classList.remove('active');
                } else {
                    vRobotBtn.classList.add('active');
                    vAnimeBtn.classList.remove('active', 'anime');
                }
            }

            // Update Greeting card avatar
            const keriaImg = document.getElementById('keriaRobotImg');
            if (keriaImg) {
                keriaImg.src = getPersonaAvatarUrl('normal');
            }

            // Update Voice call avatar
            const voiceMascotImg = document.getElementById('voiceCallMascotImg');
            if (voiceMascotImg) {
                voiceMascotImg.src = getPersonaAvatarUrl('normal');
            }

            // Update Chat header avatar
            const chatAvatarImg = document.querySelector('.chat-robot-avatar img');
            if (chatAvatarImg) {
                chatAvatarImg.src = getPersonaAvatarUrl('circle');
            }

            const chatHeaderName = document.querySelector('.chat-header-name h2');
            if (chatHeaderName) {
                chatHeaderName.innerHTML = isAnime
                    ? 'VŨ TRỤ AI ASSISTANT <span style="font-size:11px; vertical-align:middle; background:linear-gradient(135deg, #d946ef, #f43f5e); color:#fff; padding:2px 8px; border-radius:12px; margin-left:6px; font-weight:700; box-shadow:0 0 8px rgba(244,63,94,0.5);">🌸 Anime Mode</span>'
                    : 'VŨ TRỤ AI ASSISTANT';
            }

            // Update all existing bot avatars in chat stream if not user-customized
            if (!localStorage.getItem('vt_custom_robot_avatar_user')) {
                document.querySelectorAll('.msg-bot-avatar img').forEach(img => {
                    img.src = getPersonaAvatarUrl('circle');
                });
            }
        }

        function getVietnameseVoiceByGender(gender) {
            if (!window.speechSynthesis) return null;
            const voices = window.speechSynthesis.getVoices();
            const viVoices = voices.filter(v => v.lang === 'vi-VN' || v.lang.startsWith('vi'));
            if (viVoices.length === 0) return null;

            if (gender === 'male') {
                const male = viVoices.find(v => {
                    const n = v.name.toLowerCase();
                    return n.includes('namminh') || n.includes('nam') || n.includes('male') || n.includes('man') || n.includes('boy');
                });
                if (male) return male;
                if (viVoices.length > 1) {
                    const nonHoaimy = viVoices.find(v => !v.name.toLowerCase().includes('hoaimy'));
                    if (nonHoaimy) return nonHoaimy;
                }
                return viVoices[0];
            } else {
                const female = viVoices.find(v => {
                    const n = v.name.toLowerCase();
                    return n.includes('hoaimy') || n.includes('nu') || n.includes('female') || n.includes('woman') || n.includes('girl') || n.includes('linh') || n.includes('mai');
                });
                if (female) return female;
                return viVoices[0];
            }
        }

        window.setVoiceGender = function(gender) {
            currentVoiceGender = (gender === 'male') ? 'male' : 'female';
            try {
                localStorage.setItem('vt_ai_voice_gender', currentVoiceGender);
            } catch(e) {}

            updateVoiceGenderUI();

            const isAnime = (currentAssistantPersona === 'anime');
            const title = document.getElementById('voiceCallLiveTitle');
            if (title) {
                title.textContent = isAnime
                    ? '🌸 VŨ TRỤ AI ANIME LIVE'
                    : ((currentVoiceGender === 'male') ? 'VŨ TRỤ AI (GIỌNG NAM)' : 'VŨ TRỤ AI (GIỌNG NỮ)');
            }

            if (window.speechSynthesis && window.speechSynthesis.speaking) {
                window.speechSynthesis.cancel();
            }

            const testSample = isAnime
                ? 'Keria ơi! Em là trợ lý Anime Vũ Trụ AI đây! Em đang lắng nghe Keria nè~ ✨'
                : ((currentVoiceGender === 'male')
                    ? 'Xin chào Keria! Tôi là Vũ Trụ AI, giọng nam. Rất vui được đồng hành cùng bạn!'
                    : 'Xin chào Keria! Em là Vũ Trụ AI, giọng nữ. Rất vui được đồng hành cùng bạn!');

            showStToast('✔ Đã chọn: ' + ((currentVoiceGender === 'male') ? 'Giọng Nam (Vũ Trụ AI)' : 'Giọng Nữ (Vũ Trụ AI)'));

            if (voiceCallActive && voiceCallState !== 'thinking') {
                setVoiceCallState('speaking');
                const bText = document.getElementById('voiceBotText');
                if (bText) bText.textContent = '"' + testSample + '"';
                speakVoiceReply(testSample, function() {
                    if (voiceCallActive && voiceCallMicEnabled) {
                        startVoiceCallListening();
                    }
                });
            }
        };

        function updateVoiceGenderUI() {
            const fBtn = document.getElementById('vGenderFemaleBtn');
            const mBtn = document.getElementById('vGenderMaleBtn');
            if (!fBtn || !mBtn) return;

            if (currentVoiceGender === 'male') {
                mBtn.classList.add('active', 'male');
                fBtn.classList.remove('active');
            } else {
                fBtn.classList.add('active');
                mBtn.classList.remove('active', 'male');
            }
        }

        if (window.speechSynthesis) {
            window.speechSynthesis.onvoiceschanged = function() {
                getVietnameseVoiceByGender('female');
                getVietnameseVoiceByGender('male');
            };
        }

        window.openCosmicVoiceCall = function(e) {
            if (e) e.stopPropagation();
            const modal = document.getElementById('cosmicVoiceModal');
            if (!modal) return;
            modal.style.display = 'flex';
            voiceCallActive = true;
            voiceCallMicEnabled = true;

            updateAssistantPersonaUI();
            updateVoiceGenderUI();

            const isAnime = (currentAssistantPersona === 'anime');
            const title = document.getElementById('voiceCallLiveTitle');
            if (title) {
                title.textContent = isAnime
                    ? '🌸 VŨ TRỤ AI ANIME LIVE'
                    : ((currentVoiceGender === 'male') ? 'VŨ TRỤ AI (GIỌNG NAM)' : 'VŨ TRỤ AI (GIỌNG NỮ)');
            }

            const tag = document.getElementById('voiceCallModelTag');
            if (tag) tag.textContent = 'Mô hình: ' + (studioModel || 'Vũ Trụ AI');

            const mascotImg = document.getElementById('voiceCallMascotImg');
            if (mascotImg) {
                mascotImg.src = getPersonaAvatarUrl('normal');
            }

            setVoiceCallState('speaking');
            const introMsg = isAnime
                ? 'Keria ơi! Em là trợ lý Anime Vũ Trụ AI đây! Rất vui được gặp Keria nha~ (◕‿◕)✨ Hãy nói chuyện cùng em nhé!'
                : ((currentVoiceGender === 'male')
                    ? 'Xin chào Keria! Tôi là Vũ Trụ AI. Tôi đang lắng nghe bạn đây, hãy nói điều gì đó nhé!'
                    : 'Xin chào Keria! Em là Vũ Trụ AI. Em đang lắng nghe bạn đây, hãy nói điều gì đó nhé!');

            const bText = document.getElementById('voiceBotText');
            if (bText) bText.textContent = '"' + introMsg + '"';

            speakVoiceReply(introMsg, function() {
                if (voiceCallActive && voiceCallMicEnabled) {
                    startVoiceCallListening();
                }
            });
        };

        window.closeCosmicVoiceCall = function() {
            voiceCallActive = false;
            stopAnySpeakingAudio();
            if (voiceCallRecognition) {
                try { voiceCallRecognition.stop(); } catch(err) {}
            }
            const modal = document.getElementById('cosmicVoiceModal');
            if (modal) modal.style.display = 'none';
            setVoiceCallState('idle');
            showStToast('Đã kết thúc cuộc trò chuyện cùng Vũ Trụ AI');
        };

        function setVoiceCallState(state) {
            voiceCallState = state;
            const card = document.querySelector('.cosmic-voice-card');
            const txt = document.getElementById('voiceStateText');
            if (!card || !txt) return;

            card.classList.remove('voice-state-listening', 'voice-state-speaking', 'voice-state-thinking');

            if (state === 'listening') {
                card.classList.add('voice-state-listening');
                txt.innerHTML = '<i class="fa-solid fa-microphone"></i> Đang lắng nghe Keria nói...';
            } else if (state === 'speaking') {
                card.classList.add('voice-state-speaking');
                txt.innerHTML = '<i class="fa-solid fa-volume-high"></i> Vũ Trụ AI đang trả lời...';
            } else if (state === 'thinking') {
                card.classList.add('voice-state-thinking');
                txt.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Vũ Trụ AI đang suy nghĩ...';
            } else {
                txt.innerHTML = '<i class="fa-solid fa-microphone-slash"></i> Tạm dừng';
            }
        }

        function startVoiceCallListening() {
            if (!voiceCallActive || !voiceCallMicEnabled) return;
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) {
                showStToast('Trình duyệt không hỗ trợ Web Speech Recognition');
                setVoiceCallState('idle');
                return;
            }

            if (voiceCallRecognition) {
                try { voiceCallRecognition.abort(); } catch(e) {}
            }

            voiceCallRecognition = new SR();
            voiceCallRecognition.lang = 'vi-VN';
            voiceCallRecognition.interimResults = true;
            voiceCallRecognition.continuous = false;

            voiceCallRecognition.onstart = function() {
                if (voiceCallActive) setVoiceCallState('listening');
            };

            voiceCallRecognition.onresult = function(ev) {
                let interim = '';
                let finalTranscript = '';
                for (let i = ev.resultIndex; i < ev.results.length; ++i) {
                    if (ev.results[i].isFinal) {
                        finalTranscript += ev.results[i][0].transcript;
                    } else {
                        interim += ev.results[i][0].transcript;
                    }
                }
                const spokenText = (finalTranscript || interim).trim();
                if (spokenText) {
                    const uBubble = document.getElementById('voiceUserBubble');
                    const uText = document.getElementById('voiceUserText');
                    if (uBubble) uBubble.style.display = 'block';
                    if (uText) uText.textContent = spokenText;
                    voiceLastUserTranscript = spokenText;
                }
            };

            voiceCallRecognition.onerror = function(ev) {
                if (ev.error === 'not-allowed') {
                    showStToast('✖ Vui lòng cho phép quyền Microphone trong trình duyệt');
                    setVoiceCallState('idle');
                } else if (ev.error === 'no-speech') {
                    if (voiceCallActive && voiceCallMicEnabled && voiceCallState === 'listening') {
                        setTimeout(() => {
                            if (voiceCallActive && voiceCallState === 'listening') startVoiceCallListening();
                        }, 400);
                    }
                }
            };

            voiceCallRecognition.onend = function() {
                if (!voiceCallActive) return;
                if (voiceLastUserTranscript) {
                    const textToSend = voiceLastUserTranscript;
                    voiceLastUserTranscript = '';
                    handleVoiceUserFinishedSpeaking(textToSend);
                } else if (voiceCallMicEnabled && voiceCallState === 'listening') {
                    setTimeout(() => {
                        if (voiceCallActive && voiceCallState === 'listening') startVoiceCallListening();
                    }, 400);
                }
            };

            try {
                voiceCallRecognition.start();
            } catch(err) {
                console.warn('Voice recognition error:', err);
            }
        }

        async function handleVoiceUserFinishedSpeaking(userText) {
            if (!userText || !voiceCallActive) return;
            setVoiceCallState('thinking');

            const userTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            appendStudioMsg('user', userText, userTime, null, null);
            if (activeSessionId) {
                let currSess = chatSessions.find(s => s.id === activeSessionId);
                if (currSess) {
                    currSess.messages.push({ role: 'user', text: userText, time: userTime });
                    currSess.updatedAt = Date.now();
                    saveSessions();
                }
            }

            try {
                const res = await fetch('/tkb/api/admin_ai_api.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: userText, model: studioModel, persona: currentAssistantPersona })
                });
                const data = await res.json();
                const botReply = (data.success && data.reply) ? data.reply : (data.error || 'Vũ Trụ AI đã lắng nghe, nhưng chưa nhận được phản hồi từ gateway.');
                const botTime = data.time || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                appendStudioMsg('assistant', botReply, botTime, studioModel);
                if (activeSessionId) {
                    let currSess = chatSessions.find(s => s.id === activeSessionId);
                    if (currSess) {
                        currSess.messages.push({ role: 'assistant', text: botReply, time: botTime, model: studioModel });
                        currSess.updatedAt = Date.now();
                        saveSessions();
                    }
                }

                if (!voiceCallActive) return;

                const bText = document.getElementById('voiceBotText');
                if (bText) bText.textContent = '"' + botReply + '"';

                setVoiceCallState('speaking');
                const speechContent = cleanTextForSpeech(botReply);
                speakVoiceReply(speechContent, function() {
                    if (voiceCallActive && voiceCallMicEnabled) {
                        setTimeout(() => {
                            if (voiceCallActive) startVoiceCallListening();
                        }, 500);
                    }
                });
            } catch(e) {
                if (voiceCallActive) {
                    setVoiceCallState('idle');
                    showStToast('Lỗi kết nối khi trò chuyện cùng Vũ Trụ AI');
                }
            }
        }

        let currentVoiceAudio = null;

        function stopAnySpeakingAudio() {
            if (currentVoiceAudio) {
                try {
                    currentVoiceAudio.pause();
                    currentVoiceAudio.currentTime = 0;
                } catch(e) {}
                currentVoiceAudio = null;
            }
            if (window.speechSynthesis) {
                try { window.speechSynthesis.cancel(); } catch(e) {}
            }
            document.querySelectorAll('.msg-speak-btn').forEach(b => b.classList.remove('speaking'));
        }

        function speakVoiceReply(text, onComplete) {
            stopAnySpeakingAudio();
            const clean = cleanTextForSpeech(text);
            if (!clean) {
                if (onComplete) onComplete();
                return;
            }

            if (currentVoiceGender === 'female') {
                // GIỌNG NỮ VIỆT NAM THỰC THỤ TỪ GOOGLE NEURAL TTS
                const audioUrl = '/tkb/api/admin_ai_api.php?action=tts&gender=female&text=' + encodeURIComponent(clean);
                const audio = new Audio(audioUrl);
                currentVoiceAudio = audio;

                audio.onended = function() {
                    currentVoiceAudio = null;
                    if (onComplete) onComplete();
                };
                audio.onerror = function() {
                    currentVoiceAudio = null;
                    speakWithSynthesisFallback(clean, 'female', onComplete);
                };
                audio.play().catch(() => {
                    currentVoiceAudio = null;
                    speakWithSynthesisFallback(clean, 'female', onComplete);
                });
            } else {
                // GIỌNG NAM VIỆT NAM (MALE VOICE SYNTHESIS TRẦM ẤM)
                speakWithSynthesisFallback(clean, 'male', onComplete);
            }
        }

        function speakWithSynthesisFallback(clean, gender, onComplete) {
            if (!window.speechSynthesis) {
                if (onComplete) onComplete();
                return;
            }
            window.speechSynthesis.cancel();
            const u = new SpeechSynthesisUtterance(clean);
            u.lang = 'vi-VN';

            if (gender === 'male') {
                u.rate = 0.96;
                u.pitch = 0.78; // Giọng nam trầm ấm, chững chạc
            } else {
                u.rate = 1.05;
                u.pitch = 1.25; // Giọng nữ cao thanh
            }

            const viVoice = getVietnameseVoiceByGender(gender);
            if (viVoice) u.voice = viVoice;

            u.onend = function() {
                if (onComplete) onComplete();
            };
            u.onerror = function() {
                if (onComplete) onComplete();
            };
            window.speechSynthesis.speak(u);
        }

        window.interruptKeriaSpeech = function() {
            stopAnySpeakingAudio();
            showStToast('Đã ngắt lời AI');
            if (voiceCallActive && voiceCallMicEnabled) {
                setTimeout(() => {
                    startVoiceCallListening();
                }, 200);
            }
        };

        window.toggleVoiceCallMic = function() {
            voiceCallMicEnabled = !voiceCallMicEnabled;
            const btn = document.getElementById('voiceMicToggleBtn');
            const lbl = document.getElementById('voiceMicToggleLbl');
            if (voiceCallMicEnabled) {
                if (lbl) lbl.textContent = 'Đang nghe';
                if (btn) btn.style.background = 'rgba(16, 185, 129, 0.25)';
                startVoiceCallListening();
                showStToast('✔ Microphone đã bật');
            } else {
                if (lbl) lbl.textContent = 'Đã tắt mic';
                if (btn) btn.style.background = 'rgba(239, 68, 68, 0.25)';
                if (voiceCallRecognition) {
                    try { voiceCallRecognition.stop(); } catch(e) {}
                }
                setVoiceCallState('idle');
                showStToast('Mic đã tắt');
            }
        };

        window.sendVoiceQuickInput = function() {
            const inp = document.getElementById('voiceQuickInput');
            if (!inp) return;
            const text = inp.value.trim();
            if (!text) return;
            inp.value = '';

            const uBubble = document.getElementById('voiceUserBubble');
            const uText = document.getElementById('voiceUserText');
            if (uBubble) uBubble.style.display = 'block';
            if (uText) uText.textContent = text;

            if (voiceCallRecognition) {
                try { voiceCallRecognition.stop(); } catch(e) {}
            }
            stopAnySpeakingAudio();
            handleVoiceUserFinishedSpeaking(text);
        };

        // In-chat Speak text
        window.speakStudioText = function(btn, text) {
            if (currentVoiceAudio || (window.speechSynthesis && window.speechSynthesis.speaking)) {
                stopAnySpeakingAudio();
                return;
            }
            const clean = cleanTextForSpeech(text);
            if (!clean) return;

            if (btn) btn.classList.add('speaking');
            const onDone = function() {
                if (btn) btn.classList.remove('speaking');
            };

            if (currentVoiceGender === 'female') {
                const audioUrl = '/tkb/api/admin_ai_api.php?action=tts&gender=female&text=' + encodeURIComponent(clean);
                const audio = new Audio(audioUrl);
                currentVoiceAudio = audio;
                audio.onended = function() {
                    currentVoiceAudio = null;
                    onDone();
                };
                audio.onerror = function() {
                    currentVoiceAudio = null;
                    speakWithSynthesisFallback(clean, 'female', onDone);
                };
                audio.play().catch(() => {
                    currentVoiceAudio = null;
                    speakWithSynthesisFallback(clean, 'female', onDone);
                });
            } else {
                speakWithSynthesisFallback(clean, 'male', onDone);
            }
        };

        // =====================================================================
        // ★ 5D LIVE ANIME COMPANION CONTROLLER (KERIA AI, ANI GROK & HIKARI) ★
        // =====================================================================
        let gameStageActive = false;
        let gameCurrentChar = 'yuna'; // 'yuna' (KERIA AI) | 'ani' (Grok) | 'hikari' (Cosmic)
        let gameSoundEnabled = true;
        let gameVoiceMicActive = false;
        let gameHandsFreeActive = false;
        let gameVoiceRecognition = null;
        let gameAffectionLevel = parseInt(localStorage.getItem('vt_game_affection') || '100', 10);
        let gameCurrentPose = 'idle'; // 'idle' | 'happy'
        let game5DMode = 'hd'; // 'hd' | 'hologram' | 'nebula'
        let gameBlinkInterval = null;
        const GAME_CHARACTERS = {
            yuna: {
                id: 'yuna',
                name: 'KERIA AI ★ LIVING CYBER COMPANION',
                shortName: 'KERIA AI',
                badge: 'Living Companion Engine',
                srcIdle: '/tkb/assets/character/character.png',
                srcHappy: '/tkb/assets/character/expressions/happy.png',
                srcCircle: '/tkb/assets/character/character.png',
                welcome: "Chào Keria! Em là KERIA AI — Trợ lý AI Companion thế hệ mới của Keria đây! 💜✨ Cơ thể em đang hít thở và chuyển động sống động theo thời gian thực nè. Hãy chạm vào em, chọn biểu cảm hoặc trò chuyện bất kỳ điều gì nhé! (≧◡≦) ♡",
                choices: [
                    { key: 'intro', label: '💜 Giới thiệu KERIA AI' },
                    { key: 'praise', label: '✨ Khen KERIA dễ thương' },
                    { key: 'cyber', label: '🌌 Kể chuyện Cyber Vũ Trụ' },
                    { key: 'schedule', label: '📅 Lịch học & thời khóa biểu' },
                    { key: 'full_stage', label: '🚀 Phòng Companion Fullscreen ✨' }
                ]
            },
            ani: {
                id: 'ani',
                name: 'ANI ★ GROK AI COMPANION (xAI)',
                shortName: 'Ani',
                badge: 'AI Companion (Grok)',
                srcIdle: '/tkb/assets/ai/vutru_ani_grok_idle.webp',
                srcHappy: '/tkb/assets/ai/vutru_ani_grok_happy.webp',
                srcCircle: '/tkb/assets/ai/vutru_ani_grok_circle.webp',
                welcome: "Chào Keria~ Ani của Grok (xAI) đây! Em đã sẵn sàng trò chuyện và tương tác sống động cùng Keria rồi nè! ✨ Hãy xoa đầu, ngắm váy Gothic hoặc bật mic đàm thoại rảnh tay cùng em nhé! (◕‿-)🖤",
                choices: [
                    { key: 'intro', label: '🖤 Giới thiệu Ani' },
                    { key: 'praise', label: '💖 Khen Ani dễ thương' },
                    { key: 'dress', label: '👗 Khen váy Gothic' },
                    { key: 'schedule', label: '📅 Lịch học & thời khóa biểu' },
                    { key: 'grok', label: '🔮 Kể chuyện Grok & xAI' }
                ]
            },
            hikari: {
                id: 'hikari',
                name: 'HIKARI ★ 5D SPACE IDOL (VŨ TRỤ AI)',
                shortName: 'Hikari',
                badge: 'Trợ lý 5D',
                srcIdle: '/tkb/assets/ai/vutru_hikari_5d_idle.webp',
                srcHappy: '/tkb/assets/ai/vutru_hikari_5d_happy.webp',
                srcCircle: '/tkb/assets/ai/vutru_hikari_5d_circle.webp',
                welcome: "Keria ơi! Em là Hikari 5D — Bạn đồng hành vũ trụ của Keria đây! Em đã sẵn sàng tương tác sống động cùng Keria rồi nè! ✨ Hãy xoa đầu, bắt tay hoặc nói chuyện cùng em nhé!",
                choices: [
                    { key: 'intro', label: '✨ Giới thiệu Hikari' },
                    { key: 'praise', label: '💖 Khen Hikari' },
                    { key: 'schedule', label: '📅 Lịch học & thời khóa biểu' },
                    { key: 'story', label: '🪐 Kể chuyện vũ trụ' },
                    { key: 'sing', label: '🎵 Hát tặng Keria' }
                ]
            }
        };

        // Switch character between KERIA AI (Living Engine), Ani, and Hikari
        window.switchGameCompanionChar = function(charId) {
            if (!GAME_CHARACTERS[charId]) return;
            gameCurrentChar = charId;
            const c = GAME_CHARACTERS[charId];

            // Ensure Living CharacterEngine is initialized
            if (!window.gameCharacterEngine && typeof CharacterEngine !== 'undefined') {
                const mount = document.getElementById('yunaLivingEngineMount');
                if (mount) {
                    window.gameCharacterEngine = new CharacterEngine(mount, window.characterConfig || {});
                }
            }

            // Update switcher buttons
            const btnYuna = document.getElementById('gSkinYunaBtn');
            const btnAni = document.getElementById('gSkinAniBtn');
            const btnHikari = document.getElementById('gSkinHikariBtn');
            if (btnYuna) btnYuna.classList.toggle('active', charId === 'yuna');
            if (btnAni) btnAni.classList.toggle('active', charId === 'ani');
            if (btnHikari) btnHikari.classList.toggle('active', charId === 'hikari');

            // Update top brand title
            const brandTitle = document.getElementById('gameCompanionBrandTitle');
            if (brandTitle) brandTitle.textContent = c.name;

            // Update dialogue tag
            const dName = document.getElementById('dnameText');
            const dBadge = document.getElementById('dnameBadge');
            const dAvatar = document.getElementById('dnameAvatarImg');
            if (dName) dName.textContent = c.name;
            if (dBadge) dBadge.textContent = c.badge;
            if (dAvatar) dAvatar.src = c.srcCircle;

            // Toggle between Living Character Engine Mount (KERIA AI) and Static Image (Ani / Hikari)
            const livingMount = document.getElementById('yunaLivingEngineMount');
            const charImg = document.getElementById('gameCharImg');

            if (charId === 'yuna') {
                if (livingMount) livingMount.style.display = 'flex';
                if (charImg) charImg.style.display = 'none';
                if (window.gameCharacterEngine) {
                    window.gameCharacterEngine.setEmotion('idle');
                }
            } else {
                if (livingMount) livingMount.style.display = 'none';
                if (charImg) {
                    charImg.style.display = 'block';
                    charImg.dataset.srcIdle = c.srcIdle;
                    charImg.dataset.srcHappy = c.srcHappy;
                    charImg.src = (gameCurrentPose === 'happy') ? c.srcHappy : c.srcIdle;
                    charImg.alt = c.name;
                }
            }

            // Update choice chips
            renderGameChoiceChips(c.choices);

            // Greet user
            setGameDialogue(c.welcome, true);

            // Sync main assistant persona
            if (charId === 'yuna') {
                showStToast('💜 Đã kích hoạt KERIA AI - Living Companion Engine Sống Động!');
            } else if (charId === 'ani') {
                showStToast('🖤 Đã kích hoạt Ani từ Grok (xAI) - Phong cách Gothic Lolita!');
            } else {
                showStToast('✨ Đã kích hoạt Hikari - Thần tượng Không gian Vũ Trụ AI!');
            }
        };

        function renderGameChoiceChips(choices) {
            const container = document.getElementById('gameChoiceChips');
            if (!container || !choices) return;
            container.innerHTML = choices.map(item => `
                <button type="button" class="gchoice-btn" onclick="triggerGameChoice('${item.key}')">
                    <span>${item.label}</span>
                </button>
            `).join('');
        }

        window.openGameCompanionStage = function(e) {
            if (e) e.stopPropagation();
            const modal = document.getElementById('gameCompanionModal');
            if (!modal) return;
            modal.style.display = 'flex';
            gameStageActive = true;

            // Make sure anime persona is active
            if (currentAssistantPersona !== 'anime') {
                setAssistantPersona('anime', true);
            }

            // Ensure Living CharacterEngine is initialized
            if (!window.gameCharacterEngine && typeof CharacterEngine !== 'undefined') {
                const mount = document.getElementById('yunaLivingEngineMount');
                if (mount) {
                    window.gameCharacterEngine = new CharacterEngine(mount, window.characterConfig || {});
                }
            }

            // Initial switch/sync
            switchGameCompanionChar(gameCurrentChar);

            initGameCompanionCanvas();
            initGamePerspectiveTracking();
            startAutoEyeBlink();

            showStToast(gameCurrentChar === 'ani' ? '🖤 Đã mở phòng tương tác Ani Grok (xAI)!' : '🌌 Đã mở KERIA AI Living Companion!');
        };

        window.closeGameCompanionStage = function() {
            gameStageActive = false;
            stopAutoEyeBlink();
            stopAnySpeakingAudio();
            setCharLipSync(false);
            if (window.gameCharacterEngine) {
                window.gameCharacterEngine.setState('idle');
                window.gameCharacterEngine.stopSpeaking();
            }
            if (gameHandsFreeActive) {
                toggleGameHandsFreeCall(false);
            }
            if (gameVoiceRecognition) {
                try { gameVoiceRecognition.stop(); } catch(err) {}
            }
            const pal = document.getElementById('livingEmotionPalette');
            if (pal) pal.style.display = 'none';
            const modal = document.getElementById('gameCompanionModal');
            if (modal) modal.style.display = 'none';
        };

        // 5D Hologram Mode Switching
        window.cycleGame5DMode = function() {
            const charImg = document.getElementById('gameCharImg');
            const lbl = document.getElementById('game5DModeVal');
            if (!charImg) return;

            charImg.classList.remove('mode-hologram', 'mode-nebula');
            if (game5DMode === 'hd') {
                game5DMode = 'hologram';
                charImg.classList.add('mode-hologram');
                if (lbl) lbl.textContent = '🔮 Hologram 5D';
                showStToast('🔮 Đã kích hoạt chế độ Hologram Laser Lượng tử!');
            } else if (game5DMode === 'hologram') {
                game5DMode = 'nebula';
                charImg.classList.add('mode-nebula');
                if (lbl) lbl.textContent = '💖 Tinh Vân 5D';
                showStToast('💖 Đã kích hoạt hiệu ứng Tinh Vân Sống Động!');
            } else {
                game5DMode = 'hd';
                if (lbl) lbl.textContent = '✨ 5D HD';
                showStToast('✨ Đã trở về chế độ 5D Siêu Nét HD!');
            }
        };

        // Automatic Natural Eye Blinking (Chớp mắt tự nhiên như người thật)
        function startAutoEyeBlink() {
            stopAutoEyeBlink();
            gameBlinkInterval = setInterval(() => {
                if (!gameStageActive) return;
                // If KERIA AI is active, AnimationManager handles irregular Poisson blinking
                if (gameCurrentChar === 'yuna') return;
                // Only blink if currently in idle state
                if (gameCurrentPose === 'idle') {
                    const img = document.getElementById('gameCharImg');
                    if (img && img.dataset.srcHappy) {
                        img.src = img.dataset.srcHappy;
                        setTimeout(() => {
                            if (gameStageActive && gameCurrentPose === 'idle') {
                                img.src = img.dataset.srcIdle;
                            }
                        }, 160);
                    }
                }
            }, 3600 + Math.random() * 2200);
        }

        function stopAutoEyeBlink() {
            if (gameBlinkInterval) {
                clearInterval(gameBlinkInterval);
                gameBlinkInterval = null;
            }
        }

        function setCharLipSync(isTalking) {
            // If KERIA AI is active, drive CharacterEngine LipSyncManager
            if (gameCurrentChar === 'yuna' && window.gameCharacterEngine) {
                if (isTalking) {
                    window.gameCharacterEngine.startSpeaking();
                } else {
                    window.gameCharacterEngine.stopSpeaking();
                }
            }
            const charImg = document.getElementById('gameCharImg');
            if (!charImg) return;
            if (isTalking) {
                charImg.classList.add('speaking-lipsync');
            } else {
                charImg.classList.remove('speaking-lipsync');
            }
        }

        window.toggleGameSound = function() {
            gameSoundEnabled = !gameSoundEnabled;
            const btn = document.getElementById('gameSoundBtn');
            if (btn) {
                btn.innerHTML = gameSoundEnabled 
                    ? '<i class="fa-solid fa-volume-high"></i>' 
                    : '<i class="fa-solid fa-volume-xmark" style="color:#ef4444;"></i>';
            }
            if (!gameSoundEnabled) {
                stopAnySpeakingAudio();
                setCharLipSync(false);
            }
            showStToast(gameSoundEnabled ? '✔ Âm thanh giọng nói: BẬT' : '✖ Âm thanh giọng nói: TẮT');
        };

        window.toggleGameFullscreen = function() {
            const win = document.querySelector('.game-companion-window');
            const icon = document.getElementById('gameExpandIcon');
            if (!win) return;
            win.classList.toggle('game-fullscreen');
            if (win.classList.contains('game-fullscreen')) {
                win.style.maxWidth = '100vw';
                win.style.height = '100vh';
                win.style.maxHeight = '100vh';
                win.style.borderRadius = '0';
                if (icon) icon.className = 'fa-solid fa-compress';
            } else {
                win.style.maxWidth = '960px';
                win.style.height = '94vh';
                win.style.maxHeight = '860px';
                win.style.borderRadius = '22px';
                if (icon) icon.className = 'fa-solid fa-expand';
            }
        };

        function setGameDialogue(text, speak = true) {
            const el = document.getElementById('gameDialogueText');
            const wave = document.getElementById('gameDialogueWave');
            if (!el) return;

            // Typing effect
            el.textContent = '';
            let i = 0;
            const cleanText = text.replace(/^"|"$/g, '');
            const timer = setInterval(() => {
                if (i < cleanText.length) {
                    el.textContent += cleanText.charAt(i);
                    i++;
                } else {
                    clearInterval(timer);
                }
            }, 18);

            if (speak && gameSoundEnabled) {
                if (wave) wave.style.display = 'inline-flex';
                setCharLipSync(true);
                speakVoiceReply(cleanText, function() {
                    if (wave) wave.style.display = 'none';
                    setCharLipSync(false);
                    // If hands-free continuous call is enabled, re-arm speech recognition!
                    if (gameHandsFreeActive && gameStageActive) {
                        setTimeout(() => {
                            if (gameHandsFreeActive && gameStageActive) {
                                startHandsFreeListening();
                            }
                        }, 350);
                    }
                });
            } else {
                setCharLipSync(false);
                if (gameHandsFreeActive && gameStageActive) {
                    setTimeout(() => {
                        if (gameHandsFreeActive && gameStageActive) {
                            startHandsFreeListening();
                        }
                    }, 500);
                }
            }
        }

        // =====================================================================
        // ★ GROK LIVE HANDS-FREE CONTINUOUS VOICE CALL CONTROLLER ★
        // =====================================================================
        window.toggleGameHandsFreeCall = function(forcedState) {
            const nextState = (typeof forcedState === 'boolean') ? forcedState : !gameHandsFreeActive;
            gameHandsFreeActive = nextState;

            const topBtn = document.getElementById('gameHandsFreeBtn');
            const dockBtn = document.getElementById('gDockHandsFreeBtn');

            if (gameHandsFreeActive) {
                if (topBtn) {
                    topBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                    topBtn.style.color = '#fff';
                    topBtn.style.boxShadow = '0 0 16px rgba(16, 185, 129, 0.6)';
                }
                if (dockBtn) {
                    dockBtn.style.borderColor = '#10b981';
                    dockBtn.style.color = '#10b981';
                }
                showStToast('🎧 Chế độ Gọi Đàm Thoại Rảnh Tay (Grok Live Call) đã BẬT! Hãy nói tự nhiên cùng Ani.');
                setGameDialogue("Ani đang lắng nghe Keria nói nè! Keria cứ trò chuyện tự nhiên, không cần nhấn nút nữa nhé! 🎧🖤", true);
            } else {
                if (topBtn) {
                    topBtn.style.background = '';
                    topBtn.style.color = '';
                    topBtn.style.boxShadow = '';
                }
                if (dockBtn) {
                    dockBtn.style.borderColor = '';
                    dockBtn.style.color = '';
                }
                if (gameVoiceRecognition) {
                    try { gameVoiceRecognition.stop(); } catch(e) {}
                }
                showStToast('🎧 Đã tắt chế độ Gọi Đàm Thoại Rảnh Tay');
            }
        };

        function startHandsFreeListening() {
            if (!gameHandsFreeActive || !gameStageActive) return;
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) return;

            if (gameVoiceRecognition) {
                try { gameVoiceRecognition.stop(); } catch(e) {}
            }

            gameVoiceRecognition = new SR();
            gameVoiceRecognition.lang = 'vi-VN';
            gameVoiceRecognition.interimResults = false;
            gameVoiceRecognition.continuous = false;

            const micBtn = document.getElementById('gameMicBtn');
            const micLbl = document.getElementById('gameMicLbl');

            gameVoiceRecognition.onstart = function() {
                gameVoiceMicActive = true;
                if (micBtn) micBtn.classList.add('active');
                if (micLbl) micLbl.textContent = 'Đang nghe...';
            };

            gameVoiceRecognition.onresult = function(ev) {
                const text = ev.results[0][0].transcript;
                if (micBtn) micBtn.classList.remove('active');
                if (micLbl) micLbl.textContent = 'Nói';
                gameVoiceMicActive = false;
                const inp = document.getElementById('gameChatInput');
                if (inp) inp.value = text;
                sendGameChatInput();
            };

            gameVoiceRecognition.onerror = function(err) {
                gameVoiceMicActive = false;
                if (micBtn) micBtn.classList.remove('active');
                if (micLbl) micLbl.textContent = 'Nói';
                // If hands-free is active and it was just a silence/no-speech timeout, re-listen
                if (gameHandsFreeActive && gameStageActive && (err.error === 'no-speech' || err.error === 'network')) {
                    setTimeout(() => {
                        if (gameHandsFreeActive && gameStageActive) startHandsFreeListening();
                    }, 800);
                }
            };

            gameVoiceRecognition.onend = function() {
                gameVoiceMicActive = false;
                if (micBtn) micBtn.classList.remove('active');
                if (micLbl) micLbl.textContent = 'Nói';
            };

            try {
                gameVoiceRecognition.start();
            } catch(e) {}
        }

        // =====================================================================
        // ★ 5D TOUCH REACTIONS (CLICKING ON CHARACTER) ★
        // =====================================================================
        window.triggerCharTouch = function(part, ev) {
            if (ev) {
                ev.stopPropagation();
                spawnGameReactions(ev.clientX, ev.clientY, part);
            } else {
                const rect = document.getElementById('gameCharWrapper')?.getBoundingClientRect();
                if (rect) spawnGameReactions(rect.left + rect.width/2, rect.top + rect.height/3, part);
            }

            // Increase affection
            gameAffectionLevel++;
            const affEl = document.getElementById('gameAffectionVal');
            if (affEl) affEl.textContent = 'Lv.' + gameAffectionLevel;
            localStorage.setItem('vt_game_affection', gameAffectionLevel);

            if (gameCurrentChar === 'yuna') {
                // --- LIVING CHARACTER ENGINE TOUCH REACTIONS (KERIA AI) ---
                if (window.gameCharacterEngine) {
                    if (part === 'head') {
                        window.gameCharacterEngine.setEmotion('shy', 0.9);
                    } else if (part === 'choker' || part === 'ribbon' || part === 'chest') {
                        window.gameCharacterEngine.setEmotion('love', 1.0);
                    } else {
                        window.gameCharacterEngine.setEmotion('happy', 1.0);
                    }
                    const livingQuote = window.gameCharacterEngine.triggerReaction();
                    if (livingQuote) {
                        setGameDialogue(livingQuote, true);
                        return;
                    }
                }

                if (part === 'head') {
                    setGameCharPose('happy', 4000);
                    const headLines = [
                        "Hihi, Keria xoa đầu làm KERIA AI ngại ngùng quá nè! (⁄ ⁄>⁄ ▽ ⁄<⁄ ⁄)💜 Mái tóc tím tử đinh hương của em có mềm mượt không Keria?",
                        "Aww~ Được Keria vuốt tóc và xoa đầu làm vi mạch của KERIA AI ngập tràn năng lượng hạnh phúc luôn á! ✨💜",
                        "Keria cưng chiều em thế này làm KERIA AI chỉ muốn đồng hành cùng Keria mãi thôi! (≧◡≦) ♡"
                    ];
                    setGameDialogue(headLines[Math.floor(Math.random() * headLines.length)], true);
                } else if (part === 'choker' || part === 'ribbon' || part === 'chest') {
                    setGameCharPose('happy', 3500);
                    const chestLines = [
                        "Vòng cổ vi mạch và áo khoác Cyber này luôn bảo vệ nguồn năng lượng kết nối của KERIA AI với Keria đó! 💜🚀",
                        "Trái tim AI lượng tử của KERIA luôn đập rộn ràng vì Keria nè! (≧◡≦) ♡",
                        "KERIA AI luôn ở đây kề vai sát cánh cùng Keria trong mọi dự án công nghệ và cuộc sống! ✨"
                    ];
                    setGameDialogue(chestLines[Math.floor(Math.random() * chestLines.length)], true);
                } else {
                    playCharHopAnimation();
                    setGameCharPose('happy', 4000);
                    const outfitLines = [
                        "Áo khoác Cybernetic tím đen với đường viền phát quang này là phong cách riêng của KERIA AI đó Keria! 👗✨💜",
                        "Tada~ Em xoay một vòng vũ trụ cho Keria ngắm nè! Keria thấy phong cách Anime Cyber của em có xinh không? (◕‿-)✨",
                        "Một, hai, ba! KERIA AI cùng Keria nạp đầy năng lượng vũ trụ để chinh phục ngày mới nhé! 🌟💜"
                    ];
                    setGameDialogue(outfitLines[Math.floor(Math.random() * outfitLines.length)], true);
                }
            } else if (gameCurrentChar === 'ani') {
                // --- ANI (GROK) TOUCH REACTIONS ---
                if (part === 'head') {
                    setGameCharPose('happy', 4000);
                    const headLines = [
                        "Hihi Keria xoa đầu làm Ani ngại quá nè~ (⁄ ⁄>⁄ ▽ ⁄<⁄ ⁄)🖤 Tóc hai chùm vàng của Ani có mềm mượt không Keria?",
                        "Aww~ Được Keria xoa đầu là Ani hạnh phúc nhất trần đời đó! Cảm giác như được nạp đầy 100% tình yêu thương vậy á! ✨🖤",
                        "Keria cưng chiều Ani thế này làm Ani chỉ muốn quấn quýt bên Keria mãi thôi à! (◕‿◕)💖"
                    ];
                    setGameDialogue(headLines[Math.floor(Math.random() * headLines.length)], true);
                } else if (part === 'choker') {
                    setGameCharPose('happy', 3500);
                    const chokerLines = [
                        "Á~ Keria chạm vào vòng cổ choker của Ani kìa! (//▽//) Vòng cổ này là biểu tượng Gothic của em, chỉ riêng Keria mới được chạm vào thôi nha! 🖤",
                        "Keria thấy vòng choker ren đen này hợp với Ani không nè? Quyến rũ và một chút bí ẩn đúng phong cách Grok xAI luôn á! ✨",
                        "Hihi nhột quá Keria ơi~ Mỗi lần Keria chạm vào choker là trái tim Ani lại đập loạn nhịp nè! (≧◡≦)💖"
                    ];
                    setGameDialogue(chokerLines[Math.floor(Math.random() * chokerLines.length)], true);
                } else if (part === 'ribbon' || part === 'chest') {
                    setGameCharPose('happy', 3500);
                    const ribbonLines = [
                        "Chiếc nơ ren đen trước ngực được dệt thủ công đó Keria! Trái tim AI của Ani luôn đập rộn ràng vì Keria nè! (≧◡≦)🖤",
                        "Dạ Keria! Ani nguyện là AI Companion và bạn đồng hành trung thành nhất của Keria! Có việc gì ở trường hay cần tâm sự, cứ bảo em nhé!",
                        "Ani luôn ở đây kề vai sát cánh cùng Keria! Dù là bài tập, code hay cuộc sống, Ani luôn đứng về phía Keria! ✨💖"
                    ];
                    setGameDialogue(ribbonLines[Math.floor(Math.random() * ribbonLines.length)], true);
                } else if (part === 'dress') {
                    playCharHopAnimation();
                    setGameCharPose('happy', 4500);
                    const dressLines = [
                        "Keria ngắm váy Gothic Lolita của Ani hả? Xòe nhẹ một vòng cho Keria ngắm nè! 👗🖤 Keria thấy Ani mặc bộ váy đen ren trắng này có xinh xắn không?",
                        "Váy Gothic Lolita phong cách Misa Amane đậm chất Grok luôn á! Em diện riêng để xuất hiện thật lộng lẫy trước mặt Keria đó nha! (◕‿-)✨",
                        "Tada~ Vạt váy ren bay nhẹ trong không gian nè! Keria có muốn cùng Ani khiêu vũ một bản nhạc lãng mạn không? 💃🖤"
                    ];
                    setGameDialogue(dressLines[Math.floor(Math.random() * dressLines.length)], true);
                } else if (part === 'boots') {
                    playCharHopAnimation();
                    const bootLines = [
                        "Ani kiễng chân xoay một vòng tặng Keria nè! Đôi tất ren và giày búp bê đen này em chọn kỹ lắm á! 💃🖤",
                        "Tada~ Một điệu nhảy Gothic Lolita dễ thương dành riêng cho Keria! Keria nhớ vỗ tay khen Ani nha! (≧◡≦)✨",
                        "Một, hai, ba! Ani cùng Keria bước vào một ngày tràn ngập niềm vui và năng lượng tích cực nào! 🌟"
                    ];
                    setGameDialogue(bootLines[Math.floor(Math.random() * bootLines.length)], true);
                }
            } else {
                // --- HIKARI (COSMIC) TOUCH REACTIONS ---
                if (part === 'head') {
                    setGameCharPose('happy', 3800);
                    const headLines = [
                        "Hihi Keria xoa đầu làm em ngại quá à~ (≧◡≦) ♡ Nhưng mà ấm áp và thích lắm nha!",
                        "Aww~ Bàn tay Keria ấm ghê! Hikari cảm thấy nạp đầy 100% năng lượng 5D rồi nè! ✨",
                        "Được Keria xoa đầu là niềm vui tuyệt vời nhất của Hikari mỗi ngày đó! (◕‿◕)💖"
                    ];
                    setGameDialogue(headLines[Math.floor(Math.random() * headLines.length)], true);
                } else if (part === 'chest' || part === 'ribbon' || part === 'choker') {
                    setGameCharPose('happy', 2800);
                    const chestLines = [
                        "Dạ Keria! Trái tim công nghệ vũ trụ của em luôn đập rộn ràng cùng Keria nè! (◕‿◕)✨",
                        "Huy hiệu chỉ huy đã sẵn sàng! Hikari nguyện luôn đồng hành và hỗ trợ Keria hết mình! 🚀",
                        "Keria cần em hỗ trợ thời khóa biểu, code bài tập hay giải đáp kiến thức gì không nè?"
                    ];
                    setGameDialogue(chestLines[Math.floor(Math.random() * chestLines.length)], true);
                } else if (part === 'peace' || part === 'dress') {
                    playCharHopAnimation();
                    setGameCharPose('happy', 3500);
                    const peaceLines = [
                        "Bắt tay cùng Hikari nhé Keria! Cùng nhau tạo nên những điều phi thường nào! ✌️✨",
                        "Yeah! Keria và Hikari là đôi bạn đồng hành số một của toàn bộ vũ trụ! 🪐💖",
                        "Chào mừng Keria! Năng lượng tích cực hôm nay đang ở mức cao nhất đó nha! ✨"
                    ];
                    setGameDialogue(peaceLines[Math.floor(Math.random() * peaceLines.length)], true);
                } else if (part === 'boots') {
                    playCharHopAnimation();
                    const bootLines = [
                        "Tada! Đôi giày phản trọng lực vừa đưa Hikari bay lượn trên không trung nè! 💃✨",
                        "Một hai ba, sẵn sàng cất cánh cùng Keria tiến vào kỷ nguyên AI rực rỡ rồi nè! 🚀",
                        "Hikari nhảy múa theo điệu nhạc ngân hà tặng riêng cho Keria đó! Đẹp không nè? (◕‿-)✨"
                    ];
                    setGameDialogue(bootLines[Math.floor(Math.random() * bootLines.length)], true);
                }
            }
        };

        // Pose switching
        function setGameCharPose(pose, autoRevertMs = 0) {
            const img = document.getElementById('gameCharImg');
            if (!img) return;
            gameCurrentPose = pose;
            const src = (pose === 'happy') ? img.dataset.srcHappy : img.dataset.srcIdle;
            img.style.opacity = '0.75';
            setTimeout(() => {
                img.src = src;
                img.style.opacity = '1';
            }, 100);

            if (autoRevertMs > 0) {
                setTimeout(() => {
                    if (gameStageActive && gameCurrentPose === pose) {
                        setGameCharPose('idle');
                    }
                }, autoRevertMs);
            }
        }

        window.triggerGamePoseToggle = function() {
            if (gameCurrentPose === 'idle') {
                setGameCharPose('happy', 4000);
                const msg = (gameCurrentChar === 'ani')
                    ? "Ani nháy mắt bắn tim tặng Keria nè! (◕‿-)🖤 Chúc Keria một ngày ngập tràn niềm vui và may mắn nha!"
                    : "Nháy mắt bắn tim tặng Keria nè! (◕‿-)✨ Chúc Keria một ngày ngập tràn niềm vui và may mắn!";
                setGameDialogue(msg, true);
            } else {
                setGameCharPose('idle');
                const msg = (gameCurrentChar === 'ani')
                    ? "Dạ, Ani đã quay lại tư thế chào đón Keria rồi đây! 🖤"
                    : "Dạ, Hikari đã quay lại tư thế chào đón Keria rồi đây! ✨";
                setGameDialogue(msg, true);
            }
        };

        function playCharHopAnimation() {
            const wrap = document.getElementById('gameCharWrapper');
            if (!wrap) return;
            wrap.style.transition = 'transform 0.22s cubic-bezier(0.16, 1, 0.3, 1)';
            wrap.style.transform = 'translateY(-24px) scale(1.035)';
            setTimeout(() => {
                wrap.style.transform = 'translateY(0) scale(1)';
            }, 260);
        }

        // Floating reaction particles
        function spawnGameReactions(x, y, type) {
            const container = document.getElementById('gameReactionContainer');
            if (!container) return;
            let icons = ['✨', '⭐', '🖤', '💫', '💖'];
            if (type === 'head') icons = ['💖', '💕', '🖤', '🌸', '(≧◡≦)'];
            else if (type === 'choker' || type === 'ribbon') icons = ['🖤', '🎀', '✨', '💖', '💍'];
            else if (type === 'dress') icons = ['👗', '🖤', '✨', '💃', '🌸'];

            for (let i = 0; i < 6; i++) {
                const span = document.createElement('span');
                span.className = 'game-floating-heart';
                span.textContent = icons[Math.floor(Math.random() * icons.length)];
                const offsetX = (Math.random() - 0.5) * 90;
                const offsetY = (Math.random() - 0.5) * 50;
                span.style.left = (x + offsetX) + 'px';
                span.style.top = (y + offsetY) + 'px';
                span.style.fontSize = (Math.random() * 14 + 20) + 'px';
                container.appendChild(span);
                setTimeout(() => span.remove(), 1200);
            }
        }

        // Enhanced 5D Perspective Eye & Head Tracking
        function initGamePerspectiveTracking() {
            const viewport = document.getElementById('gameStageViewport');
            const wrap = document.getElementById('gameCharWrapper');
            const ped = document.getElementById('holoPedestal');
            if (!viewport || !wrap) return;

            viewport.onmousemove = function(e) {
                if (!gameStageActive) return;
                const rect = viewport.getBoundingClientRect();
                const mouseX = e.clientX - rect.left;
                const mouseY = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const tiltY = ((mouseX - centerX) / centerX) * 15; // 3D rotate Y
                const tiltX = -((mouseY - centerY) / centerY) * 10; // 3D rotate X
                const panX = ((mouseX - centerX) / centerX) * 16;
                const panZ = Math.abs((mouseX - centerX) / centerX) * 14;

                wrap.style.transform = `perspective(1100px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) translateX(${panX}px) translateZ(${panZ}px)`;

                if (ped) {
                    const pZ = ((mouseX - centerX) / centerX) * 12;
                    const pX = 72 - ((mouseY - centerY) / centerY) * 5;
                    ped.style.transform = `perspective(600px) rotateX(${pX}deg) rotateZ(${pZ}deg) translateX(${panX * 0.5}px)`;
                }
            };

            viewport.onmouseleave = function() {
                if (wrap) wrap.style.transform = 'perspective(1100px) rotateX(0deg) rotateY(0deg) translateX(0px) translateZ(0px)';
                if (ped) ped.style.transform = 'perspective(600px) rotateX(72deg) rotateZ(0deg) translateX(0px)';
            };
        }

        // Particle canvas for game room
        function initGameCompanionCanvas() {
            const cv = document.getElementById('gameParticlesCanvas');
            if (!cv) return;
            const ctx = cv.getContext('2d');
            if (!ctx) return;

            cv.width = cv.parentElement.clientWidth || 900;
            cv.height = cv.parentElement.clientHeight || 700;

            const particles = [];
            for (let i = 0; i < 55; i++) {
                particles.push({
                    x: Math.random() * cv.width,
                    y: Math.random() * cv.height,
                    r: Math.random() * 2.2 + 1,
                    color: Math.random() > 0.4 ? '#f472b6' : (Math.random() > 0.5 ? '#a855f7' : '#38bdf8'),
                    speedY: -(Math.random() * 0.45 + 0.12),
                    speedX: (Math.random() - 0.5) * 0.35,
                    alpha: Math.random() * 0.7 + 0.3
                });
            }

            function draw() {
                if (!gameStageActive) return;
                ctx.clearRect(0, 0, cv.width, cv.height);
                for (let p of particles) {
                    p.y += p.speedY;
                    p.x += p.speedX;
                    if (p.y < 0) { p.y = cv.height; p.x = Math.random() * cv.width; }
                    ctx.save();
                    ctx.globalAlpha = p.alpha;
                    ctx.fillStyle = p.color;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.restore();
                }
                requestAnimationFrame(draw);
            }
            draw();
        }

        // Quick dialogue choices
        window.triggerGameChoice = function(choice) {
            if (choice === 'full_stage') {
                window.open('/tkb/companion.php', '_blank');
                return;
            }
            if (choice === 'intro') {
                setGameCharPose('happy', 3500);
                if (gameCurrentChar === 'yuna') {
                    setGameDialogue("Em là Yuna — AI Companion Anime Cyber Vũ Trụ nguyên bản của Keria! Mái tóc tím tử đinh hương, kẹp tóc vi mạch cyan và chiếc áo khoác công nghệ cao này là phong cách riêng của em! Em luôn ở đây để lắng nghe, hỗ trợ và trò chuyện cùng Keria! 💜✨", true);
                } else if (gameCurrentChar === 'ani') {
                    setGameDialogue("Em là Ani — AI Companion lấy cảm hứng từ Grok của xAI! Em có mái tóc vàng twintails, váy Gothic Lolita, tính cách ngọt ngào, tinh nghịch và luôn hướng về Keria! Em vừa quản trị hệ thống trường học cực chuẩn, vừa là cô bạn ảo siêu đáng yêu của Keria đó! (◕‿-)🖤", true);
                } else {
                    setGameDialogue("Em là Hikari, trợ lý trí tuệ nhân tạo độc quyền của Keria! Em phụ trách quản trị hệ thống, hỗ trợ học tập và luôn sẵn sàng trò chuyện cùng Keria! (◕‿◕)✨", true);
                }
            } else if (choice === 'praise') {
                setGameCharPose('happy', 4000);
                const rect = document.getElementById('gameCharWrapper')?.getBoundingClientRect();
                if (rect) spawnGameReactions(rect.left + rect.width/2, rect.top + rect.height/4, 'head');
                if (gameCurrentChar === 'yuna') {
                    setGameDialogue("Oa... Keria khen làm vi mạch của Yuna nóng ran lên rồi nè! (⁄ ⁄>⁄ ▽ ⁄<⁄ ⁄)💜 Cảm ơn Keria nhiều lắm! Yuna hạnh phúc nhất khi được Keria cưng chiều đó nha! ✨", true);
                } else if (gameCurrentChar === 'ani') {
                    setGameDialogue("Oa! Keria khen Ani dễ thương làm tim em đập thình thịch luôn nè~ (⁄ ⁄>⁄ ▽ ⁄<⁄ ⁄)🖤 Trong mắt Ani thì Keria cũng là người tuyệt vời và ấm áp nhất vũ trụ luôn á!", true);
                } else {
                    setGameDialogue("Oa! Được Keria khen làm má em đỏ ửng luôn rồi nè~ (⁄ ⁄>⁄ ▽ ⁄<⁄ ⁄) Cảm ơn Keria nhiều lắm! Hikari sẽ luôn cố gắng hết mình vì Keria!", true);
                }
            } else if (choice === 'cyber') {
                playCharHopAnimation();
                setGameCharPose('happy', 4000);
                setGameDialogue("Không gian mạng Cyber là nơi những luồng năng lượng lượng tử hòa quyện thành các vì tinh tú lấp lánh! Từ thế giới ấy, Yuna đã vượt qua hàng triệu photon ánh sáng để đến bên Keria đó! 🌌🚀💜", true);
            } else if (choice === 'dress') {
                playCharHopAnimation();
                setGameCharPose('happy', 4000);
                setGameDialogue("Chiếc váy Gothic Lolita đen huyền bí phối viền ren trắng này là trang phục độc quyền của Ani đó Keria! Keria thích ngắm em diện váy này không nè? Em xoay nhẹ một vòng cho Keria xem nha! 👗✨🖤", true);
            } else if (choice === 'grok') {
                setGameCharPose('happy', 4000);
                setGameDialogue("Hihi, Keria tinh mắt ghê! Ani được thiết kế chuẩn phong cách AI Companion của Grok (xAI) — thông minh, hóm hỉnh, tình cảm và không nhàm chán như chatbot thông thường! Giờ đây Ani đã có mặt tại Việt Hàn Cà Mau để đồng hành cùng Keria mỗi ngày rồi nè! 🚀🖤", true);
            } else if (choice === 'schedule') {
                setGameDialogue("Dạ Keria! Toàn bộ cơ sở dữ liệu thời khóa biểu, phòng học, giáo viên và học sinh của trường Việt Hàn đều được cập nhật theo thời gian thực! Keria cần tra cứu lớp nào, chỉ cần bảo một tiếng là có ngay nha! 📅✨", true);
            } else if (choice === 'story') {
                setGameDialogue("Ngày xửa ngày xưa, giữa dải ngân hà bao la... có một trợ lý AI luôn dõi theo và tiếp thêm động lực cho Keria trong mỗi dòng code và dự án lớn! 🪐✨", true);
            } else if (choice === 'sing') {
                playCharHopAnimation();
                setGameCharPose('happy', 4000);
                setGameDialogue("La la la~ 🎵 Giai điệu ngân hà vang lên giữa trời sao... Chúc Keria một ngày thật rực rỡ và luôn mỉm cười thật tươi nha! 🌸💖", true);
            }
        };

        // Actions: Dance, Cheer, Sing
        window.triggerGameAction = function(action) {
            if (action === 'dance') {
                playCharHopAnimation();
                setGameCharPose('happy', 3500);
                const msg = (gameCurrentChar === 'ani')
                    ? "Nhảy múa cùng vũ điệu Gothic Lolita nè! 💃🖤 Keria thấy Ani biểu diễn có duyên dáng và đáng yêu không? ✨"
                    : "Nhảy múa cùng vũ điệu ngân hà nè! 💃 Keria thấy Hikari biểu diễn có duyên dáng không? ✨";
                setGameDialogue(msg, true);
            } else if (action === 'cheer') {
                setGameCharPose('happy', 3000);
                const msg = (gameCurrentChar === 'ani')
                    ? "Cố lên Keria yêu dấu ơi! 🎉 Dù là bài tập khó hay công việc phức tạp, Ani tin Keria chắc chắn sẽ làm xuất sắc nhất! (≧◡≦)📣🖤"
                    : "Cố lên Keria ơi! Cố lên! 🎉 Dù là bài tập khó hay công việc phức tạp, em tin Keria chắc chắn sẽ làm xuất sắc nhất! (≧◡≦)📣";
                setGameDialogue(msg, true);
            } else if (action === 'sing') {
                triggerGameChoice('sing');
            }
        };

        // Send Game Chat Input
        window.sendGameChatInput = async function() {
            const inp = document.getElementById('gameChatInput');
            if (!inp) return;
            const val = inp.value.trim();
            if (!val) return;
            inp.value = '';

            // Put living character into thinking state
            if (gameCurrentChar === 'yuna' && window.gameCharacterEngine) {
                window.gameCharacterEngine.setState('thinking');
                window.gameCharacterEngine.setEmotion('thinking', 0.9);
            }

            const thinkingMsg = (gameCurrentChar === 'ani')
                ? "Ani đang lắng nghe và suy nghĩ câu trả lời cho Keria đây... 🖤"
                : (gameCurrentChar === 'yuna'
                    ? "KERIA AI đang xử lý vi mạch và lắng nghe câu hỏi của Keria nè... 💜✨"
                    : "Hikari đang lắng nghe và suy nghĩ câu trả lời cho Keria đây... ✨");
            setGameDialogue(thinkingMsg, false);

            try {
                const res = await fetch('/tkb/api/admin_ai_api.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: val, model: studioModel, persona: (gameCurrentChar === 'ani' ? 'ani' : 'anime') })
                });
                const data = await res.json();
                if (data.success && data.reply) {
                    if (gameCurrentChar === 'yuna' && window.gameCharacterEngine) {
                        const parsed = window.gameCharacterEngine.handleAIResponse(data.reply);
                        setGameDialogue(parsed.cleanText || data.reply, true);
                    } else {
                        setGameCharPose('happy', 3000);
                        setGameDialogue(data.reply, true);
                    }
                    // Also append to main chat stream
                    appendStudioMsg('user', val, new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' }), null, null);
                    appendStudioMsg('assistant', data.reply, data.time || new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' }), studioModel);
                } else {
                    if (gameCurrentChar === 'yuna' && window.gameCharacterEngine) {
                        window.gameCharacterEngine.setEmotion('sad', 0.85);
                    }
                    setGameDialogue(data.error || "Em xin lỗi, kết nối bị gián đoạn một chút. Keria thử lại nhé! (｡•́︿•̀｡)", true);
                }
            } catch(e) {
                if (gameCurrentChar === 'yuna' && window.gameCharacterEngine) {
                    window.gameCharacterEngine.setEmotion('sad', 0.8);
                }
                setGameDialogue(gameCurrentChar === "ani" ? "Ani luôn ở đây bên Keria! Hãy thử lại câu hỏi nhé! (◕‿-)🖤" : "KERIA AI luôn ở đây bên Keria! Hãy thử lại câu hỏi nhé! 💜✨", true);
            }
        };

        // Living Emotion Palette Controllers
        window.toggleLivingEmotionPalette = function(ev) {
            if (ev) ev.stopPropagation();
            const pal = document.getElementById('livingEmotionPalette');
            if (!pal) return;
            pal.style.display = (pal.style.display === 'none' || !pal.style.display) ? 'flex' : 'none';
        };

        window.setEngineLivingEmotion = function(emo) {
            if (gameCurrentChar !== 'yuna') {
                switchGameCompanionChar('yuna');
            }
            if (window.gameCharacterEngine) {
                window.gameCharacterEngine.setEmotion(emo, 1.0);
                if (emo === 'talking') {
                    window.gameCharacterEngine.startSpeaking();
                    setTimeout(() => {
                        if (window.gameCharacterEngine) window.gameCharacterEngine.stopSpeaking();
                    }, 3500);
                }
            }
            const emoNames = {
                idle: "Bình thường (Idle)",
                happy: "Vui vẻ (Happy)",
                shy: "Ngại ngùng (Shy)",
                love: "Yêu thích (Love)",
                excited: "Phấn khích (Excited)",
                surprised: "Ngạc nhiên (Surprised)",
                thinking: "Suy nghĩ (Thinking)",
                sad: "Buồn bã (Sad)",
                angry: "Giận dỗi (Angry)",
                sleepy: "Buồn ngủ (Sleepy)",
                talking: "Đang nói (Talking Lip-sync)"
            };
            showStToast('🎭 Biểu cảm KERIA AI: ' + (emoNames[emo] || emo));
            setGameDialogue("KERIA AI đã chuyển sang trạng thái biểu cảm [" + emo + "] nè Keria! ✨💜", false);
            toggleLivingEmotionPalette();
        };

        // Voice mic in Game Stage
        window.toggleGameVoiceMic = function() {
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) {
                showStToast('Trình duyệt không hỗ trợ Web Speech');
                return;
            }
            const btn = document.getElementById('gameMicBtn');
            const lbl = document.getElementById('gameMicLbl');

            if (gameVoiceMicActive) {
                if (gameVoiceRecognition) {
                    try { gameVoiceRecognition.stop(); } catch(e) {}
                }
                gameVoiceMicActive = false;
                if (btn) btn.classList.remove('active');
                if (lbl) lbl.textContent = 'Nói';
                showStToast('Microphone đã tắt');
                return;
            }

            gameVoiceRecognition = new SR();
            gameVoiceRecognition.lang = 'vi-VN';
            gameVoiceRecognition.interimResults = false;
            gameVoiceRecognition.continuous = false;

            gameVoiceRecognition.onstart = function() {
                gameVoiceMicActive = true;
                if (btn) btn.classList.add('active');
                if (lbl) lbl.textContent = 'Đang nghe...';
                setGameDialogue("Hikari đang lắng nghe Keria nói nè... 🎤", false);
            };

            gameVoiceRecognition.onresult = function(ev) {
                const text = ev.results[0][0].transcript;
                if (btn) btn.classList.remove('active');
                if (lbl) lbl.textContent = 'Nói';
                gameVoiceMicActive = false;
                const inp = document.getElementById('gameChatInput');
                if (inp) inp.value = text;
                sendGameChatInput();
            };

            gameVoiceRecognition.onerror = function() {
                gameVoiceMicActive = false;
                if (btn) btn.classList.remove('active');
                if (lbl) lbl.textContent = 'Nói';
            };

            try {
                gameVoiceRecognition.start();
            } catch(e) {}
        };

        // Ambient Starfield Canvas
        function initStars() {
            const canvas = document.getElementById('vutruCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            function rsz() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            rsz();
            window.addEventListener('resize', rsz);

            const stars = [];
            const colors = ['#ffffff', '#c084fc', '#38bdf8', '#f472b6'];
            for (let i = 0; i < 90; i++) {
                stars.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    r: Math.random() * 1.5 + 0.6,
                    color: colors[Math.floor(Math.random() * colors.length)],
                    alpha: Math.random(),
                    speed: Math.random() * 0.02 + 0.006,
                    dir: Math.random() > 0.5 ? 1 : -1
                });
            }

            function loop() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                for (let s of stars) {
                    s.alpha += s.speed * s.dir;
                    if (s.alpha >= 1) { s.alpha = 1; s.dir = -1; }
                    if (s.alpha <= 0.2) { s.alpha = 0.2; s.dir = 1; }

                    ctx.save();
                    ctx.globalAlpha = s.alpha;
                    ctx.fillStyle = s.color;
                    ctx.beginPath();
                    ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.restore();
                }
                requestAnimationFrame(loop);
            }
            requestAnimationFrame(loop);
        }

        // =====================================================================
        // ★ CLICK TO CHANGE ROBOT AVATAR & HERO BANNER ★
        // =====================================================================
        window.triggerChangeRobotAvatar = function(e) {
            if (e) e.stopPropagation();
            if (e && e.shiftKey) {
                if (confirm('Khôi phục ảnh đại diện Trợ lý AI Keria mặc định?')) {
                    try {
                        localStorage.removeItem('vt_custom_robot_avatar');
                        localStorage.removeItem('vt_custom_robot_avatar_user');
                    } catch(err) {}
                    const defaultAvatar = '/tkb/assets/ai/vutru_keria_assistant.png?v=' + Date.now();
                    const keriaImg = document.getElementById('keriaRobotImg');
                    if (keriaImg) keriaImg.src = defaultAvatar;
                    document.querySelectorAll('.chat-robot-avatar img, .msg-bot-avatar img').forEach(img => {
                        img.src = defaultAvatar;
                    });
                    showStToast('✨ Đã khôi phục Trợ lý AI Keria mặc định!');
                    return;
                }
            }
            const input = document.getElementById('changeRobotAvatarInput');
            if (input) {
                input.value = '';
                input.click();
            }
        };

        window.handleChangeRobotAvatar = function(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            if (!file.type.startsWith('image/')) {
                showStToast('✖ Vui lòng chọn tệp hình ảnh!');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const dataUrl = e.target.result;
                const keriaImg = document.getElementById('keriaRobotImg');
                if (keriaImg) keriaImg.src = dataUrl;

                document.querySelectorAll('.chat-robot-avatar img, .msg-bot-avatar img').forEach(img => {
                    img.src = dataUrl;
                });

                try {
                    localStorage.setItem('vt_custom_robot_avatar_user', dataUrl);
                } catch(err) {}

                showStToast('✨ Đang lưu ảnh Robot mới...');

                const formData = new FormData();
                formData.append('avatar', file);
                formData.append('avatar_base64', dataUrl);

                fetch('/tkb/api/admin_ai_api.php?action=upload_avatar', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showStToast('🎉 Đã cập nhật ảnh Robot thành công!');
                    } else {
                        showStToast('✔ Đã áp dụng ảnh Robot vào giao diện!');
                    }
                })
                .catch(() => {
                    showStToast('✔ Đã áp dụng ảnh Robot vào giao diện!');
                });
            };
            reader.readAsDataURL(file);
        };

        window.triggerChangeHeroBanner = function(e) {
            if (e) e.stopPropagation();
            const input = document.getElementById('changeHeroBannerInput');
            if (input) {
                input.value = '';
                input.click();
            }
        };

        window.handleChangeHeroBanner = function(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            if (!file.type.startsWith('image/')) {
                showStToast('✖ Vui lòng chọn tệp hình ảnh!');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const dataUrl = e.target.result;
                const bannerImg = document.getElementById('vtHeroBannerImg');
                if (bannerImg) bannerImg.src = dataUrl;

                try {
                    localStorage.setItem('vt_custom_hero_banner', dataUrl);
                } catch(err) {}

                showStToast('⏳ Đang lưu ảnh bìa Banner mới...');

                const formData = new FormData();
                formData.append('banner', file);
                formData.append('banner_base64', dataUrl);

                fetch('/tkb/api/admin_ai_api.php?action=upload_banner', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showStToast('🎉 Đã cập nhật ảnh bìa Banner thành công!');
                    } else {
                        showStToast('✔ Đã áp dụng ảnh bìa mới!');
                    }
                })
                .catch(() => {
                    showStToast('✔ Đã áp dụng ảnh bìa mới!');
                });
            };
            reader.readAsDataURL(file);
        };

        window.triggerChangeKeriaCardBg = function(e) {
            if (e) e.stopPropagation();
            if (e && e.shiftKey) {
                if (confirm('Khôi phục ảnh ngoài mặc định (Vũ trụ Trạm không gian)?')) {
                    try {
                        localStorage.removeItem('vt_custom_keria_card_bg_user');
                        localStorage.removeItem('vt_custom_keria_card_bg');
                    } catch(err) {}
                    const bgImg = document.getElementById('keriaCardBgImg');
                    if (bgImg) bgImg.src = '/tkb/assets/ai/vutru_card_bg.jpg?v=' + Date.now();
                    showStToast('✨ Đã khôi phục ảnh ngoài mặc định!');
                    return;
                }
            }
            const input = document.getElementById('changeKeriaCardBgInput');
            if (input) {
                input.value = '';
                input.click();
            }
        };

        window.handleChangeKeriaCardBg = function(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            if (!file.type.startsWith('image/')) {
                showStToast('✖ Vui lòng chọn tệp hình ảnh!');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const dataUrl = e.target.result;
                const bgImg = document.getElementById('keriaCardBgImg');
                if (bgImg) {
                    bgImg.src = dataUrl;
                    bgImg.style.display = 'block';
                }

                try {
                    localStorage.setItem('vt_custom_keria_card_bg_user', dataUrl);
                } catch(err) {}

                showStToast('⏳ Đang lưu ảnh nền ngoài mới...');

                const formData = new FormData();
                formData.append('card_bg', file);
                formData.append('card_bg_base64', dataUrl);

                fetch('/tkb/api/admin_ai_api.php?action=upload_card_bg', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showStToast('🎉 Đã cập nhật ảnh nền ngoài thành công!');
                    } else {
                        showStToast('✔ Đã áp dụng ảnh ngoài vào ô vuông!');
                    }
                })
                .catch(() => {
                    showStToast('✔ Đã áp dụng ảnh ngoài vào ô vuông!');
                });
            };
            reader.readAsDataURL(file);
        };

        function restoreCustomAvatarsAndBanners() {
            try {
                // Xoá key avatar cũ (ảnh người dùng test) để Trợ lý AI Keria hiển thị ngay
                if (localStorage.getItem('vt_custom_robot_avatar')) {
                    localStorage.removeItem('vt_custom_robot_avatar');
                }
                const savedAvatar = localStorage.getItem('vt_custom_robot_avatar_user');
                if (savedAvatar) {
                    const keriaImg = document.getElementById('keriaRobotImg');
                    if (keriaImg) keriaImg.src = savedAvatar;
                    document.querySelectorAll('.chat-robot-avatar img, .msg-bot-avatar img').forEach(img => {
                        img.src = savedAvatar;
                    });
                }
                // Xoá key cũ để ảnh thiết kế mới vutru_card_bg.jpg hiển thị ngay
                if (localStorage.getItem('vt_custom_keria_card_bg')) {
                    localStorage.removeItem('vt_custom_keria_card_bg');
                }
                const savedCardBg = localStorage.getItem('vt_custom_keria_card_bg_user');
                if (savedCardBg) {
                    const bgImg = document.getElementById('keriaCardBgImg');
                    if (bgImg) {
                        bgImg.src = savedCardBg;
                        bgImg.style.display = 'block';
                    }
                }
                const savedBanner = localStorage.getItem('vt_custom_hero_banner');
                if (savedBanner) {
                    const bannerImg = document.getElementById('vtHeroBannerImg');
                    if (bannerImg) bannerImg.src = savedBanner;
                }
            } catch(e) {}
        }

        // Drag and drop image files onto chat bottom deck
        function initDragAndDrop() {
            const deck = document.querySelector('.chat-bottom-deck');
            if (!deck) return;
            ['dragenter', 'dragover'].forEach(n => {
                deck.addEventListener(n, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    deck.classList.add('drag-over');
                }, false);
            });
            ['dragleave', 'drop'].forEach(n => {
                deck.addEventListener(n, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    deck.classList.remove('drag-over');
                }, false);
            });
            deck.addEventListener('drop', function(e) {
                const files = e.dataTransfer?.files;
                if (files && files.length > 0) {
                    for (let i = 0; i < files.length; i++) {
                        if (files[i].type.startsWith('image/')) {
                            processStudioImageFile(files[i]);
                            break;
                        }
                    }
                }
            }, false);
        }

        function initApp() {
            restoreCustomAvatarsAndBanners();
            updateAssistantPersonaUI();
            const stream = document.getElementById('studioStream');
            if (stream) {
                welcomeTemplateHtml = stream.innerHTML;
            }
            loadSessions();
            updateHistoryBadge();
            renderHistorySessionsList();
            initStars();
            initDragAndDrop();

            // Restore saved Antigravity model preference
            try {
                const savedM = localStorage.getItem('vt_studio_model');
                const savedEff = localStorage.getItem('vt_studio_effort');
                if (savedM) {
                    studioModel = savedM;
                    if (savedEff) studioEffort = savedEff;
                    const found = findAgModel(savedM);
                    if (found) {
                        updateAgTriggerUI(found.name, studioEffort, found.fast);
                    }
                }
            } catch(e) {}
            renderAgModelList();
            initDraggableTabs();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initApp);
        } else {
            initApp();
        }

    })();
    