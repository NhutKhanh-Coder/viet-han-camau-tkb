<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Update appendBubbleUI to support isHtml for user bubble
$oldAppendBubble = 'function appendBubbleUI(role, text, save = true) {
            const hero = document.getElementById(\'heroWelcome\');
            if (hero) hero.style.display = \'none\';
            const stream = document.getElementById(\'messagesStream\');
            if (stream) stream.style.display = \'flex\';

            const bubble = document.createElement(\'div\');
            bubble.className = \'chat-bubble \' + (role === \'user\' ? \'user\' : \'bot\');
            if (role === \'user\') {
                bubble.innerText = text;
            } else {
                bubble.innerHTML = formatMarkdown(text);
            }
            stream.appendChild(bubble);

            const body = document.getElementById(\'aiChatBody\');
            if (body) body.scrollTop = body.scrollHeight;

            if (save) saveMessageToCurrentSession(role, text);
        }';

$newAppendBubble = 'function appendBubbleUI(role, text, save = true, isHtml = false) {
            const hero = document.getElementById(\'heroWelcome\');
            if (hero) hero.style.display = \'none\';
            const stream = document.getElementById(\'messagesStream\');
            if (stream) stream.style.display = \'flex\';

            const bubble = document.createElement(\'div\');
            bubble.className = \'chat-bubble \' + (role === \'user\' ? \'user\' : \'bot\');
            if (role === \'user\') {
                if (isHtml) {
                    bubble.innerHTML = text;
                } else {
                    bubble.innerText = text;
                }
            } else {
                bubble.innerHTML = formatMarkdown(text);
            }
            stream.appendChild(bubble);

            const body = document.getElementById(\'aiChatBody\');
            if (body) body.scrollTop = body.scrollHeight;

            if (save) saveMessageToCurrentSession(role, text);
        }';

$aiPhp = str_replace($oldAppendBubble, $newAppendBubble, $aiPhp);

// 2. Add complete sendMsg function before document.addEventListener('DOMContentLoaded'
$sendMsgJs = '
        async function sendMsg() {
            const input = document.getElementById(\'userInput\');
            const text = (input ? input.value : \'\').trim();
            if (!text && attachedFiles.length === 0) return;

            // Group files if a folder was attached
            const folderMap = {};
            const standaloneFiles = [];
            attachedFiles.forEach(f => {
                const folderName = f.folder || (f.path && f.path.includes(\'/\') ? f.path.split(\'/\')[0] : null);
                if (folderName) {
                    if (!folderMap[folderName]) folderMap[folderName] = [];
                    folderMap[folderName].push(f);
                } else {
                    standaloneFiles.push(f);
                }
            });

            // Build rich user bubble display
            let displayHtml = escapeHtml(text);
            if (attachedFiles.length > 0) {
                let badgeHtml = \'<div style="margin-top:8px; display:flex; flex-direction:column; gap:6px;">\';
                
                // Show folder groups
                for (let fName of Object.keys(folderMap)) {
                    const filesInFolder = folderMap[fName];
                    const totalKb = filesInFolder.reduce((sum, f) => sum + parseFloat(f.size || 0), 0).toFixed(1);
                    badgeHtml += `
                        <div style="display:inline-flex; align-items:center; gap:8px; padding:6px 12px; background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3); border-radius:10px; font-size:12.5px; font-weight:700;">
                            <i class="fa-solid fa-folder-open" style="color:#a7f3d0; font-size:14px;"></i>
                            <span>📂 Thư mục: ${fName} (${filesInFolder.length} tệp · ${totalKb} KB)</span>
                        </div>
                    `;
                }

                // Show standalone files
                if (standaloneFiles.length > 0) {
                    badgeHtml += \'<div style="display:flex; flex-wrap:wrap; gap:6px;">\';
                    standaloneFiles.forEach(f => {
                        badgeHtml += `
                            <div style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); border-radius:8px; font-size:11.5px;">
                                <i class="fa-solid fa-file-code"></i>
                                <span>${f.name} (${f.size || \'\'})</span>
                            </div>
                        `;
                    });
                    badgeHtml += \'</div>\';
                }
                badgeHtml += \'</div>\';
                displayHtml += badgeHtml;
            }

            appendBubbleUI(\'user\', displayHtml, true, true);
            if (input) {
                input.value = \'\';
                input.style.height = \'38px\';
            }

            const typing = document.getElementById(\'typingIndicator\');
            if (typing) typing.style.display = \'block\';
            const body = document.getElementById(\'aiChatBody\');
            if (body) body.scrollTop = body.scrollHeight;

            const studentDbContext = ' . json_encode([
                'student' => [
                    'name' => '<?= $ho_ten ?>',
                    'code' => '<?= $ma_sv ?>',
                    'class' => '<?= $lop ?>',
                    'department' => '<?= $khoa ?>'
                ],
                'schedule' => '<?= json_encode($tkb_list, JSON_UNESCAPED_UNICODE) ?>',
                'assignments' => '<?= json_encode($assignments_list, JSON_UNESCAPED_UNICODE) ?>',
                'quiz_results' => '<?= json_encode($quiz_attempts, JSON_UNESCAPED_UNICODE) ?>',
                'documents' => '<?= json_encode($documents_list, JSON_UNESCAPED_UNICODE) ?>'
            ]) . ';

            let systemMsg = `You are VKC AI Assistant powered by ${currentSelectedModel ? currentSelectedModel.name : \'DeepSeek\'}, an elite AI Software Engineer and Academic Tutor (empowered like Google Antigravity & OpenAI Codex).

${customSystemPrompt ? `CUSTOM INSTRUCTIONS FROM STUDENT:\\n${customSystemPrompt}\\n` : \'\'}
${thinkingEnabled ? `Reasoning Level: ${reasoningEffort.toUpperCase()}. Think through problems step by step before answering.` : \'\'}

INSTRUCTIONS:
- You are an elite AI Coding Assistant and Academic Tutor (empowered like Google Antigravity & OpenAI Codex).
- Answer all questions friendly, professionally, and clearly in Vietnamese. Use markdown formatting.
- When full folders, multi-files, or project codebases are attached:
  1. Inspect the entire project folder structure and individual code files carefully.
  2. Explain your analysis, step-by-step reasoning, and solutions clearly with architectural insights.
  3. ALWAYS output the complete, corrected, or enhanced code inside markdown code blocks with the specific language identifier (e.g., \`\`\`php, \`\`\`python, \`\`\`javascript, \`\`\`html, \`\`\`css, \`\`\`sql, \`\`\`cpp).
  4. Ensure all code is production-ready, clean, well-commented, and includes proper error handling.`;

            const images = attachedFiles.filter(f => f.type === \'image\');
            const texts = attachedFiles.filter(f => f.type === \'text\');

            let joinedTextContent = \'\';
            if (texts.length > 0) {
                const hasFolders = texts.some(f => f.folder || (f.path && f.path.includes(\'/\')));
                if (hasFolders) {
                    const treeList = texts.map(f => `  - ${f.path || f.name} (${f.size || \'\'})`).join(\'\\n\');
                    joinedTextContent = `=== TOÀN BỘ CẤU TRÚC THƯ MỤC DỰ ÁN (${texts.length} TỆP TIN) ===\\n${treeList}\\n\\n=== CHI TIẾT NỘI DUNG CÁC TỆP TRONG DỰ ÁN ===\\n\\n`;
                    joinedTextContent += texts.map(f => `--- [Tệp: ${f.path || f.name}] ---\\n\`\`\`${(f.ext || \'\').replace(\'.\', \'\')}\\n${f.data}\\n\`\`\``).join(\'\\n\\n\') + \'\\n\\n\';
                } else {
                    joinedTextContent = texts.map(f => `[Tệp đính kèm: ${f.path || f.name}]\\n\`\`\`${(f.ext || \'\').replace(\'.\', \'\')}\\n${f.data}\\n\`\`\``).join(\'\\n\\n\') + \'\\n\\n\';
                }
            }

            const promptText = joinedTextContent + (text || \'Hãy phân tích toàn bộ cấu trúc mã nguồn và các tệp trong thư mục dự án trên.\');

            let reqMessages = [];
            if (images.length > 0) {
                systemMsg += \' You can see and analyze the uploaded images.\';
                const userContent = [{ type: \'text\', text: promptText }];
                images.forEach(img => {
                    userContent.push({ type: \'image_url\', image_url: { url: img.data } });
                });

                reqMessages = [
                    { role: \'system\', content: systemMsg },
                    { role: \'user\', content: userContent }
                ];
            } else {
                reqMessages = [
                    { role: \'system\', content: systemMsg },
                    { role: \'user\', content: promptText }
                ];
            }

            // Clear attachments
            attachedFiles = [];
            renderPreviews();

            let reqModel = currentSelectedModel ? currentSelectedModel.id : \'deepseek/deepseek-chat-v3.1\';

            try {
                const response = await fetch(\'/tkb/api/login.php?groq\', {
                    method: \'POST\',
                    headers: { \'Content-Type\': \'application/json\' },
                    body: JSON.stringify({
                        model: reqModel,
                        messages: reqMessages,
                        temperature: 0.6
                    })
                });

                const data = await response.json();
                if (typing) typing.style.display = \'none\';

                if (data.choices && data.choices[0] && data.choices[0].message) {
                    appendBubbleUI(\'bot\', data.choices[0].message.content);
                } else if (data.error && data.error.message) {
                    let errText = data.error.message;
                    appendBubbleUI(\'bot\', `⚠️ **Thông báo:** ${errText}`);
                } else {
                    appendBubbleUI(\'bot\', \'Lỗi phản hồi từ trợ lý AI! Vui lòng thử lại sau.\');
                }
            } catch (error) {
                if (typing) typing.style.display = \'none\';
                appendBubbleUI(\'bot\', \'Lỗi kết nối máy chủ AI! Vui lòng kiểm tra lại đường truyền mạng.\');
            }
        }
';

$aiPhp = str_replace('// Initialize on page load', $sendMsgJs . "\n        // Initialize on page load", $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully restored and empowered sendMsg for Full Folder sending!\n";
