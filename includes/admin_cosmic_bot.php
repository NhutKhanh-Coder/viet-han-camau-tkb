<?php
/**
 * VŨ TRỤ AI BOT ★ Trợ Lý Trí Tuệ Nhân Tạo Không Giới Hạn
 * Tích hợp giao diện Vũ Trụ AI theo đúng chuẩn Mockup
 * Tích hợp Kira AI Gateway qua /tkb/api/admin_ai_api.php
 */
if (session_status() === PHP_SESSION_NONE) @session_start();
if (isset($cur_file) && in_array($cur_file, ['ai_studio.php', 'botchat.php'])) {
    return;
}
$cur_admin_name = $_SESSION['ho_ten'] ?? 'Quản Trị Viên';
?>
<!-- =====================================================================
     VŨ TRỤ AI - FLOATING BOT WIDGET (CHUẨN MOCKUP)
     ===================================================================== -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
<?php if (isset($cur_file) && in_array($cur_file, ['ai_studio.php', 'botchat.php'])): ?>
.vt-fab-wrap { display: none !important; }
<?php endif; ?>

:root {
  --vt-void: #f8fafc;
  --vt-card: #ffffff;
  --vt-border: #e2e8f0;
  --vt-purple: #6366f1;
  --vt-indigo: #4f46e5;
  --vt-cyan: #0ea5e9;
  --vt-green: #10b981;
  --vt-text: #0f172a;
  --vt-sub: #475569;
  --vt-muted: #64748b;
  --font-main: 'Plus Jakarta Sans', sans-serif;
  --font-heading: 'Outfit', sans-serif;
}

/* Floating FAB */
.vt-fab-wrap {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 999990;
  display: flex;
  align-items: center;
  gap: 12px;
  user-select: none;
}

.vt-fab-pill {
  background: #ffffff;
  border: 1.5px solid #e0e7ff;
  padding: 8px 18px;
  color: #1e1b4b;
  font-size: 13.5px;
  font-family: var(--font-heading);
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  border-radius: 30px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08), 0 2px 10px rgba(99, 102, 241, 0.15);
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.vt-fab-pill:hover {
  background: #f8fafc;
  border-color: #818cf8;
  color: #4f46e5;
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12), 0 4px 15px rgba(99, 102, 241, 0.25);
}
.vt-fab-pill .pulse-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--vt-green);
  box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
  animation: vtPulse 1.4s infinite;
}
@keyframes vtPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.85); }
}

.vt-fab-btn {
  position: relative;
  width: 62px;
  height: 62px;
  border-radius: 50%;
  padding: 2px;
  background: linear-gradient(135deg, #6366f1, #38bdf8);
  box-shadow: 0 8px 25px rgba(99, 102, 241, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  outline: none;
  border: none;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.vt-fab-btn:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 12px 30px rgba(99, 102, 241, 0.5);
}
.vt-fab-btn:active {
  transform: scale(0.96);
}

.vt-fab-inner {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  overflow: hidden;
  background: #ffffff;
}
.vt-fab-inner img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Floating Modal */
.vt-modal {
  position: fixed;
  bottom: 96px;
  right: 24px;
  width: 450px;
  height: 670px;
  max-width: calc(100vw - 20px);
  max-height: calc(100vh - 110px);
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 22px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12), 0 4px 20px rgba(99, 102, 241, 0.08);
  display: none;
  flex-direction: column;
  overflow: hidden;
  z-index: 999995;
  font-family: var(--font-main);
  color: #0f172a;
  transform-origin: bottom right;
  animation: vtOpen 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.vt-modal.open { display: flex; }

.vt-modal.fullscreen {
  width: 95vw !important;
  height: 92vh !important;
  max-width: 1500px !important;
  max-height: 94vh !important;
  bottom: 3vh !important;
  right: 2.5vw !important;
  left: 2.5vw !important;
  margin: 0 auto;
}

@keyframes vtOpen {
  from { opacity: 0; transform: scale(0.92) translateY(20px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Header */
.vtm-header {
  position: relative;
  z-index: 3;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 18px;
  background: #ffffff;
  border-bottom: 1px solid #f1f5f9;
}

.vtm-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}
.vtm-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  border: 1.5px solid #6366f1;
  box-shadow: 0 0 10px rgba(99, 102, 241, 0.25);
  flex-shrink: 0;
}
.vtm-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.vtm-title-wrap h3 {
  margin: 0;
  font-family: var(--font-heading);
  font-size: 14px;
  font-weight: 800;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 6px;
}
.vtm-status {
  font-size: 11px;
  color: #10b981;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 5px;
}
.vtm-status::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 6px rgba(16, 185, 129, 0.6);
}

.vtm-tools {
  display: flex;
  align-items: center;
  gap: 5px;
}
.vtm-btn {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #475569;
  width: 30px;
  height: 30px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  transition: all 0.2s;
}
.vtm-btn:hover {
  background: #eef2ff;
  color: #4f46e5;
  border-color: #c7d2fe;
}

/* Model selector */
.vtm-model-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  gap: 12px;
}
.vtm-model-lbl {
  font-size: 11.5px;
  font-weight: 700;
  color: #4f46e5;
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}
.vtm-model-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 999px;
  background: #eef2ff;
  border: 1px solid #c7d2fe;
  color: #4f46e5;
}
.vtm-model-select {
  flex: 1;
  max-width: 290px;
  font-family: var(--font-main);
  font-size: 12px;
  font-weight: 600;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 9px;
  color: #0f172a;
  padding: 5px 8px;
  outline: none;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  transition: all 0.2s ease;
}
.vtm-model-select:hover, .vtm-model-select:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}
.vtm-model-select optgroup {
  background: #ffffff;
  color: #4f46e5;
  font-weight: 800;
  font-style: normal;
  padding: 6px 0;
}
.vtm-model-select option {
  background: #ffffff;
  color: #1e293b;
  padding: 6px 10px;
  font-weight: 500;
}

/* Stream */
.vtm-stream {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: #f8fafc;
}
.vtm-stream::-webkit-scrollbar { width: 5px; }
.vtm-stream::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.vtm-stream::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.vtm-msg {
  display: flex;
  gap: 10px;
  max-width: 90%;
  animation: vtMsgIn 0.2s forwards;
}
@keyframes vtMsgIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
.vtm-msg.user { align-self: flex-end; flex-direction: row-reverse; }
.vtm-msg.bot { align-self: flex-start; }

.vtm-msg-avatar {
  width: 34px;
  height: 34px;
  border-radius: 12px;
  overflow: hidden;
  flex-shrink: 0;
  border: 1.5px solid;
}
.vtm-msg.bot .vtm-msg-avatar {
  border-color: #c7d2fe;
}
.vtm-msg.bot .vtm-msg-avatar img { width: 100%; height: 100%; object-fit: cover; }
.vtm-msg.user .vtm-msg-avatar {
  background: linear-gradient(135deg, #0284c7, #38bdf8);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  border-color: #7dd3fc;
}

.vtm-bubble {
  padding: 12px 16px;
  border-radius: 16px;
  font-size: 14px;
  line-height: 1.6;
  word-break: break-word;
  position: relative;
}
.vtm-msg.bot .vtm-bubble {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  color: #1e293b;
  border-top-left-radius: 4px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.vtm-msg.bot .vtm-bubble strong {
  color: #0f172a;
  font-weight: 700;
}
.vtm-msg.user .vtm-bubble {
  background: linear-gradient(135deg, #4f46e5, #6366f1);
  border: none;
  color: #ffffff;
  border-top-right-radius: 4px;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
}

.vtm-time {
  font-size: 10.5px;
  color: #64748b;
  margin-top: 5px;
  display: flex;
  align-items: center;
  gap: 4px;
  font-weight: 500;
}
.vtm-msg.user .vtm-time { justify-content: flex-end; color: #818cf8; }

/* Quick Pills */
.vtm-quick-row {
  padding: 8px 14px;
  display: flex;
  gap: 6px;
  overflow-x: auto;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
}
.vtm-quick-row::-webkit-scrollbar { height: 3px; }
.vtm-quick-row::-webkit-scrollbar-thumb { background: #e2e8f0; }
.vtm-quick-pill {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 5px 11px;
  color: #334155;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}
.vtm-quick-pill:hover {
  background: #eef2ff;
  color: #4f46e5;
  border-color: #c7d2fe;
  transform: translateY(-1px);
}

/* Input Area */
.vtm-input-area {
  padding: 10px 14px;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
}
.vtm-input-box {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  background: #f8fafc;
  border: 1.5px solid #cbd5e1;
  border-radius: 14px;
  padding: 6px 12px;
  transition: all 0.2s ease;
}
.vtm-input-box:focus-within {
  background: #ffffff;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.vtm-textarea {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #0f172a;
  font-family: var(--font-main);
  font-size: 14px;
  resize: none;
  max-height: 90px;
  min-height: 26px;
  line-height: 1.4;
  padding: 2px 0;
}
.vtm-textarea::placeholder { color: #94a3b8; font-size: 13px; }

.vtm-tool-btn {
  background: none;
  border: none;
  color: #64748b;
  width: 28px;
  height: 28px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  border-radius: 50%;
  transition: all 0.15s;
  flex-shrink: 0;
}
.vtm-tool-btn:hover { color: #4f46e5; background: #eef2ff; }
.vtm-tool-btn.active { color: #ef4444; }

.vtm-send-btn {
  background: linear-gradient(135deg, #4f46e5, #6366f1);
  border: none;
  border-radius: 10px;
  color: #fff;
  width: 32px;
  height: 32px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  box-shadow: 0 3px 10px rgba(79, 70, 229, 0.35);
  transition: all 0.2s;
  flex-shrink: 0;
}
.vtm-send-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(79, 70, 229, 0.45);
}
.vtm-send-btn:disabled {
  background: #e2e8f0;
  color: #94a3b8;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
</style>

<!-- Floating FAB Launcher -->
<div class="vt-fab-wrap" id="vtFabWrap">
  <div class="vt-fab-pill" onclick="toggleVtModal()" title="Mở Vũ Trụ AI">
    <span class="pulse-dot"></span>
    <span>✦ VŨ TRỤ AI</span>
  </div>
  <button type="button" class="vt-fab-btn" onclick="toggleVtModal()" title="Trợ lý Vũ Trụ AI">
    <div class="vt-fab-inner">
      <img src="/tkb/assets/ai/vutru_robot_mascot.jpg" alt="Vũ Trụ AI Robot Mascot">
    </div>
  </button>
</div>

<!-- Floating Modal -->
<div class="vt-modal" id="vtModal">
  <!-- Header -->
  <div class="vtm-header">
    <div class="vtm-brand">
      <div class="vtm-avatar">
        <img src="/tkb/assets/ai/vutru_robot_mascot.jpg" alt="Vũ Trụ AI">
      </div>
      <div class="vtm-title-wrap">
        <h3>VŨ TRỤ AI ASSISTANT</h3>
        <div class="vtm-status">Galaxy Engine Online</div>
      </div>
    </div>

    <div class="vtm-tools">
      <button type="button" class="vtm-btn" onclick="openVtStudio()" title="Mở toàn màn hình (Studio)">
        <i class="fa-solid fa-up-right-from-square"></i>
      </button>
      <button type="button" class="vtm-btn" onclick="toggleVtFullscreen()" title="Phóng to / Thu nhỏ">
        <i class="fa-solid fa-expand" id="vtmExpandIcon"></i>
      </button>
      <button type="button" class="vtm-btn" onclick="toggleVtModal()" title="Đóng">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  </div>

  <!-- Model selector -->
  <div class="vtm-model-bar">
    <div class="vtm-model-lbl">
      <i class="fa-solid fa-microchip"></i> Model:
      <span class="vtm-model-badge">36 Models</span>
    </div>
    <select class="vtm-model-select" id="pxModelSelect" onchange="onVtModelChange(this.value)">
      <optgroup label="🌌 Kira Cosmic & GLM (Bản quyền)">
        <option value="glm-4.7-flash-free" selected>GLM 4.7 Flash Free ★ Siêu tốc (Mặc định)</option>
        <option value="kira-3.5-flash">Kira 3.5 Flash ★ Suy luận sâu & Nhanh</option>
        <option value="kira-3.5-pro">Kira 3.5 Pro ★ Trí tuệ tối cao</option>
        <option value="kira-mini-1.0">Kira Mini 1.0 ★ Tối ưu tác vụ nhanh</option>
        <option value="kira-2.5-pro">Kira 2.5 Pro ★ Ổn định & Logic</option>
        <option value="kira-2.5-flash">Kira 2.5 Flash ★ Phản hồi gọn nhẹ</option>
      </optgroup>

      <optgroup label="🌟 Google Gemini">
        <option value="gemini-3.8-flash">Google Gemini 3.8 Flash ★ Siêu thế hệ mới</option>
        <option value="gemini-2.5-flash">Google Gemini 2.5 Flash ★ Đa phương thức</option>
        <option value="gemini-2.5-pro">Google Gemini 2.5 Pro ★ Tư duy phức tạp & Đa bước</option>
        <option value="gemini-1.5-flash">Google Gemini 1.5 Flash ★ Ngữ cảnh siêu dài</option>
      </optgroup>

      <optgroup label="🧠 Anthropic Claude">
        <option value="claude-3.7-sonnet">Claude 3.7 Sonnet ★ Phản biện & Văn phong</option>
        <option value="claude-3.5-sonnet">Claude 3.5 Sonnet ★ Chuẩn mực code & Ngữ nghĩa</option>
        <option value="claude-3.5-haiku">Claude 3.5 Haiku ★ Tốc độ chớp mắt & Súc tích</option>
      </optgroup>

      <optgroup label="⚡ OpenAI & xAI Flagship">
        <option value="gpt-4o">OpenAI ChatGPT-4o ★ Đỉnh cao tri thức</option>
        <option value="gpt-4o-mini">OpenAI GPT-4o Mini ★ Gọn nhẹ & Chính xác</option>
        <option value="o1-preview">OpenAI o1 (Thinking) ★ Suy luận toán & Khoa học</option>
        <option value="grok-4.5">Grok 4.5 Cosmic ★ Sáng tạo không giới hạn</option>
        <option value="grok-4.7">Grok 4.7 Cosmic ★ Trực giác & Sắc bén</option>
      </optgroup>

      <optgroup label="🧮 DeepSeek Series">
        <option value="deepseek/deepseek-v4-pro">DeepSeek V4 Pro ★ Logic, Toán & Giải thuật</option>
        <option value="deepseek/deepseek-v4-flash">DeepSeek V4 Flash ★ Xử lý siêu tốc</option>
        <option value="deepseek/deepseek-v3.2">DeepSeek V3.2 ★ Cân bằng tối ưu</option>
        <option value="deepseek/deepseek-chat-v3.1">DeepSeek Chat V3.1 ★ Phân tích chuyên sâu</option>
        <option value="deepseek-v4-flash">DeepSeek V4 Flash ★ Logic</option>
      </optgroup>

      <optgroup label="🔮 Alibaba Qwen Series">
        <option value="qwen/qwen3.8-max">Qwen 3.8 Max ★ Siêu ngữ cảnh 1M token</option>
        <option value="qwen/qwen3.7-max">Qwen 3.7 Max ★ Thông minh vượt trội</option>
        <option value="qwen/qwen3.7-flash">Qwen 3.7 Flash ★ Phản hồi chớp nhoáng</option>
        <option value="qwen/qwen3-coder-plus">Qwen 3 Coder Plus ★ Chuyên gia PHP/SQL</option>
        <option value="qwen/qwen3.5-flash">Qwen 3.5 Flash ★ Tiết kiệm tài nguyên</option>
        <option value="qwen3.8-flash">Qwen 3.8 Flash ★ Đa nhiệm mượt mà</option>
      </optgroup>

      <optgroup label="🌪️ Mistral & Codestral">
        <option value="mistralai/codestral-2508">Codestral 2508 ★ Chuyên gia Code PHP / SQL</option>
        <option value="mistralai/mistral-large-2512">Mistral Large 2512 ★ Phân tích tài liệu lớn</option>
        <option value="mistralai/ministral-14b">Ministral 14B ★ Trợ lý học thuật gọn nhẹ</option>
      </optgroup>

      <optgroup label="🚀 MiniMax & Tencent">
        <option value="minimax/minimax-m2.7-highspeed">MiniMax M2.7 Highspeed ★ 1M Token Context</option>
        <option value="minimax/minimax-m2.5">MiniMax M2.5 ★ Phân tích đa chiều</option>
        <option value="minimax/minimax-m2.1-highspeed">MiniMax M2.1 Highspeed ★ Siêu mượt</option>
        <option value="tencent/hy3">Tencent Hy3 ★ Agent thông minh</option>
      </optgroup>
    </select>
  </div>

  <!-- Stream -->
  <div class="vtm-stream" id="vtmStream">
    <div class="vtm-msg bot">
      <div class="vtm-msg-avatar">
        <img src="/tkb/assets/ai/vutru_robot_mascot.jpg" alt="Vũ Trụ AI">
      </div>
      <div>
        <div class="vtm-bubble">
          Xin chào Quản trị viên <strong><?= htmlspecialchars($cur_admin_name) ?></strong>!<br>
          Tôi là <strong>Vũ Trụ AI</strong> — Trợ lý trí tuệ nhân tạo của bạn.<br><br>
          Tôi có thể giúp bạn giải đáp học tập, phân tích dữ liệu, viết code và soạn thảo văn bản.<br>
          Hãy gửi câu hỏi cho tôi ngay nhé! 🚀
        </div>
        <div class="vtm-time"><i class="fa-solid fa-bolt" style="color:var(--vt-green);"></i> Sẵn sàng hỗ trợ</div>
      </div>
    </div>
  </div>

  <!-- Typing Row -->
  <div id="vtmTypingRow" style="display:none; padding:0 16px 8px;">
    <div style="display:flex; gap:8px; align-items:center;">
      <div class="vtm-msg-avatar" style="width:28px; height:28px;"><img src="/tkb/assets/ai/vutru_robot_mascot.jpg" alt="Robot"></div>
      <span style="font-size:11px; color:var(--vt-sub);">Vũ Trụ AI đang xử lý...</span>
    </div>
  </div>

  <!-- Quick pills -->
  <div class="vtm-quick-row">
    <button type="button" class="vtm-quick-pill" onclick="submitVtPrompt('Tổng quan số lượng sinh viên, giảng viên và lớp học')">
      📊 Thống kê CSDL
    </button>
    <button type="button" class="vtm-quick-pill" onclick="submitVtPrompt('Soạn thông báo lịch thi và nghỉ học chuẩn')">
      📝 Soạn thông báo
    </button>
    <button type="button" class="vtm-quick-pill" onclick="submitVtPrompt('Tạo 5 câu trắc nghiệm CNTT có đáp án')">
      ⚡ Đề Quiz trắc nghiệm
    </button>
    <button type="button" class="vtm-quick-pill" onclick="clearVtChat()">
      🗑️ Xóa lịch sử
    </button>
  </div>

  <!-- Input -->
  <div class="vtm-input-area">
    <div class="vtm-input-box">
      <textarea class="vtm-textarea" id="vtmInput" placeholder="Nhập câu hỏi của bạn... (Enter để gửi)" rows="1"
        onkeydown="handleVtKey(event)"
        oninput="autoResizeVtInput(this)"></textarea>
      <button type="button" class="vtm-tool-btn" id="vtmVoiceBtn" onclick="toggleVtVoice()" title="Nhập giọng nói">
        <i class="fa-solid fa-microphone"></i>
      </button>
      <button type="button" class="vtm-send-btn" id="vtmSendBtn" onclick="sendVtMessage()" title="Gửi (Enter)">
        <i class="fa-solid fa-paper-plane"></i>
      </button>
    </div>
  </div>
</div>

<script>
(function() {
  let vtModel = 'glm-4.7-flash-free';
  let vtIsOpen = false;
  let vtIsFullscreen = false;

  // Restore saved model from localStorage
  try {
    const saved = localStorage.getItem('vt_admin_cosmic_model');
    if (saved) {
      const sel = document.getElementById('pxModelSelect');
      if (sel && sel.querySelector(`option[value="${saved}"]`)) {
        sel.value = saved;
        vtModel = saved;
      }
    }
  } catch(e) {}

  window.toggleVtModal = function() {
    const m = document.getElementById('vtModal');
    if (!m) return;
    vtIsOpen = !vtIsOpen;
    m.classList.toggle('open', vtIsOpen);

    // Sync topbar Cosmic Station button active state
    const topBtn = document.getElementById('admCosmicStationBtn');
    if (topBtn) {
      topBtn.classList.toggle('active', vtIsOpen);
    }

    if (vtIsOpen) {
      const inp = document.getElementById('vtmInput');
      if (inp) setTimeout(() => inp.focus(), 150);
    }
  };

  window.toggleCosmicStation = function(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    window.toggleVtModal();
  };

  window.toggleVtFullscreen = function() {
    const m = document.getElementById('vtModal');
    const ic = document.getElementById('vtmExpandIcon');
    if (!m) return;
    vtIsFullscreen = !vtIsFullscreen;
    m.classList.toggle('fullscreen', vtIsFullscreen);
    if (ic) ic.className = vtIsFullscreen ? 'fa-solid fa-compress' : 'fa-solid fa-expand';
  };

  window.openVtStudio = function() {
    window.location.href = '/tkb/admin/ai_studio.php';
  };

  window.onVtModelChange = function(m) {
    vtModel = m;
    try {
      localStorage.setItem('vt_admin_cosmic_model', m);
    } catch(e) {}
  };

  window.autoResizeVtInput = function(tx) {
    tx.style.height = 'auto';
    tx.style.height = Math.min(tx.scrollHeight, 90) + 'px';
  };

  window.handleVtKey = function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendVtMessage();
    }
  };

  window.submitVtPrompt = function(text) {
    const inp = document.getElementById('vtmInput');
    if (inp) {
      inp.value = text;
      sendVtMessage();
    }
  };

  function formatVtMarkdown(str) {
    if (!str) return '';
    let esc = str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
    
    // Code blocks ```code```
    esc = esc.replace(/```([a-z0-9_-]*)\n([\s\S]*?)```/gi, function(match, lang, code) {
      return `<pre style="background:#0f172a; padding:10px 14px; border-radius:10px; border:1px solid #334155; overflow-x:auto; font-family:monospace; font-size:12px; margin:8px 0; color:#f8fafc; line-height:1.5;"><code>${code.trim()}</code></pre>`;
    });

    // Inline code `code`
    esc = esc.replace(/`([^`]+)`/g, '<code style="background:#f1f5f9; color:#6366f1; padding:2px 6px; border-radius:4px; font-family:monospace; font-size:12px; border:1px solid #e2e8f0; font-weight:600;">$1</code>');

    // Bold **text**
    esc = esc.replace(/\*\*(.*?)\*\*/g, '<strong style="color:inherit; font-weight:700;">$1</strong>');

    // Italic *text*
    esc = esc.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Bullet points
    esc = esc.replace(/^[•\-\*]\s+(.+)$/gm, '<li style="margin-left:14px; list-style-type:disc;">$1</li>');

    // Newlines to <br>
    esc = esc.replace(/\n/g, '<br>');

    return esc;
  }

  function appendVtMsg(role, text, modelTitle) {
    const s = document.getElementById('vtmStream');
    if (!s) return;
    const t = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const row = document.createElement('div');
    row.className = 'vtm-msg ' + (role === 'user' ? 'user' : 'bot');
    if (role === 'user') {
      const clean = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
      row.innerHTML = `
        <div class="vtm-msg-avatar"><i class="fa-solid fa-user-astronaut"></i></div>
        <div>
          <div class="vtm-bubble">${clean}</div>
          <div class="vtm-time">${t}</div>
        </div>
      `;
    } else {
      const formatted = formatVtMarkdown(text);
      const sel = document.getElementById('pxModelSelect');
      const curModelName = modelTitle || (sel?.selectedOptions[0]?.text?.split('★')[0]?.trim()) || 'Vũ Trụ AI';
      row.innerHTML = `
        <div class="vtm-msg-avatar"><img src="/tkb/assets/ai/vutru_robot_mascot.jpg" alt="Vũ Trụ AI"></div>
        <div>
          <div class="vtm-bubble">${formatted}</div>
          <div class="vtm-time"><i class="fa-solid fa-bolt" style="color:var(--vt-green);"></i> <span style="color:#4f46e5; font-weight:600;">${curModelName}</span> • ${t}</div>
        </div>
      `;
    }
    s.appendChild(row);
    s.scrollTop = s.scrollHeight;
  }

  window.sendVtMessage = async function() {
    const inp = document.getElementById('vtmInput');
    const btn = document.getElementById('vtmSendBtn');
    const typing = document.getElementById('vtmTypingRow');
    if (!inp || !btn) return;
    const val = inp.value.trim();
    if (!val) return;

    inp.value = '';
    autoResizeVtInput(inp);
    inp.disabled = true;
    btn.disabled = true;
    appendVtMsg('user', val);
    if (typing) typing.style.display = 'block';

    const sel = document.getElementById('pxModelSelect');
    const activeModelTitle = sel?.selectedOptions[0]?.text?.split('★')[0]?.trim() || vtModel;

    try {
      const res = await fetch('/tkb/api/admin_ai_api.php?action=send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: val, model: vtModel })
      });
      const data = await res.json();
      if (typing) typing.style.display = 'none';

      if (data.success && data.reply) {
        appendVtMsg('assistant', data.reply, activeModelTitle);
      } else {
        appendVtMsg('assistant', '⚠ ' + (data.error || 'Lỗi kết nối'));
      }
    } catch(e) {
      if (typing) typing.style.display = 'none';
      appendVtMsg('assistant', '✖ Máy chủ không phản hồi.');
    } finally {
      inp.disabled = false;
      btn.disabled = false;
      inp.focus();
    }
  };

  window.clearVtChat = async function() {
    if (!confirm('Xóa lịch sử chat?')) return;
    try {
      await fetch('/tkb/api/admin_ai_api.php?action=clear');
      const s = document.getElementById('vtmStream');
      if (s) {
        s.innerHTML = `
          <div class="vtm-msg bot">
            <div class="vtm-msg-avatar"><img src="/tkb/assets/ai/vutru_robot_mascot.jpg" alt="Vũ Trụ AI"></div>
            <div>
              <div class="vtm-bubble">Lịch sử hội thoại đã được làm mới!</div>
              <div class="vtm-time"><i class="fa-solid fa-bolt" style="color:var(--vt-green);"></i> Sẵn sàng</div>
            </div>
          </div>
        `;
      }
    } catch(e) {}
  };

  // Voice
  let isListening = false;
  window.toggleVtVoice = function() {
    const vBtn = document.getElementById('vtmVoiceBtn');
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) return;
    if (!window.vtRecognizer) {
      window.vtRecognizer = new SR();
      window.vtRecognizer.lang = 'vi-VN';
      window.vtRecognizer.onresult = function(ev) {
        const t = ev.results[0][0].transcript;
        const inp = document.getElementById('vtmInput');
        if (inp) {
          inp.value = (inp.value ? inp.value + ' ' : '') + t;
          autoResizeVtInput(inp);
        }
      };
      window.vtRecognizer.onend = function() { isListening = false; if (vBtn) vBtn.classList.remove('active'); };
      window.vtRecognizer.onerror = function() { isListening = false; if (vBtn) vBtn.classList.remove('active'); };
    }
    if (!isListening) {
      try {
        window.vtRecognizer.start();
        isListening = true;
        if (vBtn) vBtn.classList.add('active');
      } catch(e) {}
    } else {
      window.vtRecognizer.stop();
      isListening = false;
      if (vBtn) vBtn.classList.remove('active');
    }
  };

  // Sync persona if selected
  try {
    if (localStorage.getItem('vt_assistant_persona') === 'anime') {
      document.querySelectorAll('#vtFabWrap img, #vtModal .vtm-avatar img, .vtm-msg-avatar img').forEach(img => {
        img.src = '/tkb/assets/ai/vutru_anime_circle.png';
      });
      const title = document.querySelector('.vtm-title-wrap h3');
      if (title) title.innerHTML = 'VŨ TRỤ AI (HIKARI)';
    }
  } catch(e) {}
})();
</script>
