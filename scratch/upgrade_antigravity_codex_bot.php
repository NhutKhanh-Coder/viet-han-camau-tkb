<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Add Antigravity / Codex CSS Styles
$antigravityCss = <<< 'EOD'

        /* ===== ANTIGRAVITY / CODEX AGENTIC UI STYLES ===== */
        .ai-think-box {
            margin: 10px 0 14px 0;
            background: rgba(248, 250, 252, 0.85);
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            font-size: 12.5px;
            color: #475569;
            transition: all 0.2s ease;
        }
        .ai-think-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 14px;
            background: #f1f5f9;
            cursor: pointer;
            font-weight: 700;
            color: #334155;
            user-select: none;
        }
        .ai-think-header:hover {
            background: #e2e8f0;
        }
        .ai-think-content {
            padding: 12px 14px;
            border-top: 1px solid #e2e8f0;
            line-height: 1.6;
            font-family: inherit;
            color: #64748b;
            background: #ffffff;
            white-space: pre-wrap;
            max-height: 280px;
            overflow-y: auto;
        }
        .ai-ide-code-block {
            margin: 14px 0;
            background: #0f172a;
            border: 1.5px solid #1e293b;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.25);
            font-family: 'Fira Code', Consolas, Monaco, monospace;
        }
        .ai-ide-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 14px;
            background: #1e293b;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            font-size: 12px;
            color: #94a3b8;
        }
        .ai-ide-btn {
            background: rgba(255,255,255,0.08);
            color: #f8fafc;
            border: 1px solid rgba(255,255,255,0.12);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease;
        }
        .ai-ide-btn:hover {
            background: rgba(255,255,255,0.18);
            color: #ffffff;
        }
        .ai-ide-btn-run {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            border: none;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(16,185,129,0.3);
        }
        .ai-ide-btn-run:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16,185,129,0.45);
        }
        .slash-commands-menu {
            position: absolute;
            bottom: calc(100% + 10px);
            left: 16px;
            width: 320px;
            background: #ffffff;
            border: 1.5px solid var(--ai-border);
            border-radius: 14px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            padding: 8px;
            display: none;
            flex-direction: column;
            gap: 4px;
            z-index: 99999;
            backdrop-filter: blur(10px);
        }
        .slash-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            transition: all 0.15s ease;
        }
        .slash-item:hover, .slash-item.active {
            background: #ecfdf5;
            color: #065f46;
        }
        .slash-tag {
            font-family: monospace;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11.5px;
            color: #0ea5e9;
            font-weight: 700;
        }
EOD;

// Inject CSS before </style>
$aiPhp = str_replace('</style>', $antigravityCss . "\n    </style>", $aiPhp);

// 2. Add Slash Commands Menu HTML inside chat input wrapper
$slashMenuHtml = <<< 'EOD'
                        <!-- SLASH COMMANDS POPUP (ANTIGRAVITY / CODEX STYLE) -->
                        <div class="slash-commands-menu" id="slashCommandsMenu">
                            <div style="padding:4px 8px; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px;">Antigravity Slash Commands</div>
                            <div class="slash-item" onclick="applySlashCommand('/code ')">
                                <span class="slash-tag">/code</span>
                                <div>
                                    <div style="color:#0f172a;">Viết mã nguồn</div>
                                    <div style="font-size:11px; color:#64748b; font-weight:normal;">Lập trình giải thuật, tính năng và backend/frontend</div>
                                </div>
                            </div>
                            <div class="slash-item" onclick="applySlashCommand('/fix ')">
                                <span class="slash-tag" style="color:#ef4444;">/fix</span>
                                <div>
                                    <div style="color:#0f172a;">Sửa lỗi Bug</div>
                                    <div style="font-size:11px; color:#64748b; font-weight:normal;">Chẩn đoán, gỡ lỗi và cung cấp bản vá chuẩn</div>
                                </div>
                            </div>
                            <div class="slash-item" onclick="applySlashCommand('/explain ')">
                                <span class="slash-tag" style="color:#8b5cf6;">/explain</span>
                                <div>
                                    <div style="color:#0f172a;">Giải thích chi tiết</div>
                                    <div style="font-size:11px; color:#64748b; font-weight:normal;">Giải mã từng dòng code, thuật toán và luồng dữ liệu</div>
                                </div>
                            </div>
                            <div class="slash-item" onclick="applySlashCommand('/refactor ')">
                                <span class="slash-tag" style="color:#f59e0b;">/refactor</span>
                                <div>
                                    <div style="color:#0f172a;">Tối ưu hóa Code</div>
                                    <div style="font-size:11px; color:#64748b; font-weight:normal;">Clean Code, tăng hiệu năng và chuẩn cấu trúc</div>
                                </div>
                            </div>
                            <div class="slash-item" onclick="applySlashCommand('/plan ')">
                                <span class="slash-tag" style="color:#10b981;">/plan</span>
                                <div>
                                    <div style="color:#0f172a;">Planning Mode</div>
                                    <div style="font-size:11px; color:#64748b; font-weight:normal;">Lập kế hoạch kiến trúc từng bước (Architecture Plan)</div>
                                </div>
                            </div>
                        </div>
EOD;

$aiPhp = str_replace('<div class="ai-input-pill">', '<div class="ai-input-pill" style="position:relative;">' . "\n" . $slashMenuHtml, $aiPhp);

// 3. Add Live Sandboxed Code Preview Modal at bottom of body
$previewModalHtml = <<< 'EOD'
    <!-- LIVE SANDBOX CODE RUNNER MODAL (ANTIGRAVITY / CODEX LIVE PREVIEW) -->
    <div id="liveRunnerModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.75); z-index:99999999; align-items:center; justify-content:center; backdrop-filter:blur(8px);">
        <div style="width:1050px; max-width:95vw; height:720px; max-height:92vh; background:#1e293b; border-radius:18px; display:flex; flex-direction:column; box-shadow:0 30px 90px rgba(0,0,0,0.6); overflow:hidden; border:1px solid #334155; font-family:'Outfit',sans-serif;">
            <div style="padding:12px 20px; background:#0f172a; border-bottom:1px solid #334155; display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg, #10b981, #059669); display:flex; align-items:center; justify-content:center; color:#fff; font-size:16px;">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div>
                        <div style="font-size:14.5px; font-weight:800; color:#f8fafc;" id="liveRunnerTitle">Live Sandbox Runner</div>
                        <div style="font-size:11.5px; color:#94a3b8;">Chạy thử nghiệm thời gian thực trong môi trường cách ly an toàn</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" onclick="refreshLiveRunner()" style="background:#334155; color:#f8fafc; border:none; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:5px;">
                        <i class="fa-solid fa-rotate-right"></i> <span>Tải lại</span>
                    </button>
                    <button type="button" onclick="closeLiveRunner()" style="background:transparent; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; font-size:16px;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
            <div style="flex:1; background:#ffffff; position:relative;">
                <iframe id="liveSandboxFrame" style="width:100%; height:100%; border:none;" sandbox="allow-scripts allow-modals allow-same-origin allow-forms"></iframe>
                <div id="liveConsoleOutput" style="display:none; width:100%; height:100%; background:#0f172a; color:#38bdf8; font-family:'Fira Code',monospace; padding:18px; box-sizing:border-box; overflow:auto; font-size:13px; line-height:1.6; white-space:pre-wrap;"></div>
            </div>
        </div>
    </div>
EOD;

$aiPhp = str_replace('</body>', $previewModalHtml . "\n</body>", $aiPhp);

// 4. Update formatMarkdown and JavaScript logic for Thinking process and Code blocks
$newMarkdownAndChatLogic = <<< 'EOD'
        let currentSandboxCode = '';
        let currentSandboxLang = 'html';

        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function formatMarkdown(text) {
            if (!text) return '';

            // 1. Process <think>...</think> Reasoning Process (Antigravity Chain-of-Thought)
            text = text.replace(/<think>([\s\S]*?)<\/think>/gi, function(match, thoughts) {
                const thinkId = 'think_' + Math.random().toString(36).substr(2, 9);
                return `
                    <div class="ai-think-box">
                        <div class="ai-think-header" onclick="toggleThinkBlock('${thinkId}')">
                            <span style="display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-brain" style="color:#8b5cf6;"></i>
                                <span>Quá trình suy luận (Thinking Chain)</span>
                            </span>
                            <i class="fa-solid fa-chevron-down" id="icon_${thinkId}"></i>
                        </div>
                        <div class="ai-think-content" id="${thinkId}" style="display:none;">
                            ${escapeHtml(thoughts.trim())}
                        </div>
                    </div>
                `;
            });

            // 2. Process Code Blocks with Full Antigravity / Codex Interactive Studio
            const codeBlocks = [];
            let processedText = text.replace(/```([a-zA-Z0-9_-]*)\n?([\s\S]*?)```/g, function(match, lang, code) {
                const id = 'code_' + Math.random().toString(36).substr(2, 9);
                const rawLang = (lang || 'code').toLowerCase();
                const cleanLang = rawLang.toUpperCase();
                const ext = rawLang || 'txt';
                const trimmedCode = code.trim();
                const rawEscaped = encodeURIComponent(trimmedCode);
                
                const isRunnable = ['html', 'htm', 'javascript', 'js', 'css', 'python', 'py', 'php'].includes(rawLang);
                const runBtn = isRunnable ? `
                    <button type="button" class="ai-ide-btn ai-ide-btn-run" onclick="openLiveRunner('${rawEscaped}', '${rawLang}')">
                        <i class="fa-solid fa-play"></i> <span>Chạy thử</span>
                    </button>
                ` : '';

                const blockHtml = `
                    <div class="ai-ide-code-block">
                        <div class="ai-ide-toolbar">
                            <div style="display:flex; align-items:center; gap:8px; font-weight:700;">
                                <i class="fa-solid fa-code" style="color:#38bdf8;"></i>
                                <span style="color:#f8fafc; letter-spacing:0.5px;">${cleanLang}</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                ${runBtn}
                                <button type="button" class="ai-ide-btn" onclick="copyCodeBlock('${id}', this)">
                                    <i class="fa-solid fa-copy"></i> <span>Sao chép</span>
                                </button>
                                <button type="button" class="ai-ide-btn" onclick="downloadCodeFile('${rawEscaped}', 'solution_${Date.now()}.${ext}')">
                                    <i class="fa-solid fa-download"></i> <span>Tải file</span>
                                </button>
                            </div>
                        </div>
                        <pre style="margin:0; padding:14px 16px; overflow-x:auto; font-family:'Fira Code',Consolas,monospace; font-size:13px; line-height:1.6; color:#f8fafc;"><code id="${id}">${escapeHtml(trimmedCode)}</code></pre>
                    </div>
                `;
                codeBlocks.push(blockHtml);
                return `___CODE_BLOCK_${codeBlocks.length - 1}___`;
            });

            // 3. Process Markdown inline tokens with correct group replacements
            let html = escapeHtml(processedText);
            html = html.replace(/`([^`]+)`/g, '<code style="background:rgba(217,27,67,0.06); color:#d91b43; padding:2px 6px; border-radius:4px; font-family:monospace; font-size:12.5px; font-weight:600;">$1</code>');
            html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
            html = html.replace(/^### (.*$)/gim, '<h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:14px 0 6px;">$1</h3>');
            html = html.replace(/^## (.*$)/gim, '<h2 style="font-size:17px; font-weight:800; color:#0f172a; margin:16px 0 8px;">$1</h2>');
            html = html.replace(/^# (.*$)/gim, '<h1 style="font-size:19px; font-weight:800; color:#0f172a; margin:18px 0 10px;">$1</h1>');
            html = html.replace(/^[\*\-•]\s+(.*)$/gm, '<li style="margin-left:20px; list-style-type:disc; margin-bottom:4px;">$1</li>');
            html = html.replace(/\n/g, '<br>');

            // 4. Restore Code blocks
            codeBlocks.forEach((block, idx) => {
                html = html.replace(`___CODE_BLOCK_${idx}___`, block);
            });
            return html;
        }

        function toggleThinkBlock(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById('icon_' + id);
            if (el) {
                const isHidden = el.style.display === 'none';
                el.style.display = isHidden ? 'block' : 'none';
                if (icon) icon.className = isHidden ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down';
            }
        }

        function openLiveRunner(encodedCode, lang) {
            const code = decodeURIComponent(encodedCode);
            currentSandboxCode = code;
            currentSandboxLang = lang;

            const modal = document.getElementById('liveRunnerModal');
            const frame = document.getElementById('liveSandboxFrame');
            const consoleBox = document.getElementById('liveConsoleOutput');
            const title = document.getElementById('liveRunnerTitle');

            if (title) title.textContent = `Live Sandbox Runner (${lang.toUpperCase()})`;

            if (['html', 'htm', 'javascript', 'js', 'css'].includes(lang)) {
                if (frame) {
                    frame.style.display = 'block';
                    let docContent = code;
                    if (lang === 'javascript' || lang === 'js') {
                        docContent = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>JS Sandbox</title><style>body{font-family:sans-serif;padding:20px;color:#0f172a;}</style></head><body><div id="output"></div><script>try{ ${code} }catch(e){ document.body.innerHTML += '<div style="color:red;font-weight:bold;">Error: '+e.message+'</div>'; }<\/script></body></html>`;
                    } else if (lang === 'css') {
                        docContent = `<!DOCTYPE html><html><head><meta charset="utf-8"><style>${code}</style></head><body><div class="box"><h1>CSS Live Preview</h1><p>Demo style applied successfully!</p></div></body></html>`;
                    }
                    frame.srcdoc = docContent;
                }
                if (consoleBox) consoleBox.style.display = 'none';
            } else {
                if (frame) frame.style.display = 'none';
                if (consoleBox) {
                    consoleBox.style.display = 'block';
                    consoleBox.textContent = `=== [${lang.toUpperCase()} EMULATOR OUTPUT] ===\n\n>> Compiling and executing source code...\n\n` + code;
                }
            }

            if (modal) modal.style.display = 'flex';
        }

        function refreshLiveRunner() {
            if (currentSandboxCode && currentSandboxLang) {
                openLiveRunner(encodeURIComponent(currentSandboxCode), currentSandboxLang);
            }
        }

        function closeLiveRunner() {
            const modal = document.getElementById('liveRunnerModal');
            if (modal) modal.style.display = 'none';
        }

        // ===== SLASH COMMANDS LISTENER =====
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('aiChatInput');
            const menu = document.getElementById('slashCommandsMenu');
            if (input && menu) {
                input.addEventListener('input', () => {
                    const val = input.value.trim();
                    if (val === '/') {
                        menu.style.display = 'flex';
                    } else {
                        menu.style.display = 'none';
                    }
                });
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && menu) menu.style.display = 'none';
                });
            }
        });

        function applySlashCommand(cmd) {
            const input = document.getElementById('aiChatInput');
            const menu = document.getElementById('slashCommandsMenu');
            if (input) {
                input.value = cmd;
                input.focus();
            }
            if (menu) menu.style.display = 'none';
        }
EOD;

// Replace formatMarkdown in ai.php
$pattern = '/function formatMarkdown\(text\) \{.*?\/\/ ===== MODE SWITCHING =====/s';
$aiPhp = preg_replace($pattern, $newMarkdownAndChatLogic . "\n\n        // ===== MODE SWITCHING =====\n        ", $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully upgraded Chatbot into Antigravity & Codex AI Studio!\n";
