# -*- coding: utf-8 -*-

filepath = r"c:\xampp\htdocs\tkb\admin\ai_studio.php"
with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Update .chat-stream-deck
content = content.replace(
    """.chat-stream-deck {
            flex: 1;
            overflow-y: auto;
            padding: 12px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #f8fafc;
        }""",
    """.chat-stream-deck {
            flex: 1;
            overflow-y: auto;
            padding: 12px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: var(--vt-chat-main-bg);
        }"""
)

# 2. Update .typing-wave
content = content.replace(
    """.typing-wave {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }""",
    """.typing-wave {
            background: var(--vt-bot-bubble-bg);
            border: 1px solid var(--vt-bot-bubble-border);
            border-radius: 14px;
            padding: 10px 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: var(--vt-bot-bubble-shadow);
        }"""
)

# 3. Update .chat-input-pill:focus-within
content = content.replace(
    """.chat-input-pill:focus-within {
            background: #ffffff;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
        }""",
    """.chat-input-pill:focus-within {
            background: var(--vt-input-pill-focus-bg, var(--vt-input-pill-bg));
            border-color: var(--vt-purple);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.18);
        }"""
)

# 4. Update .vt-antigravity-trigger:hover
content = content.replace(
    """.vt-antigravity-trigger:hover, .vt-antigravity-trigger.active {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }""",
    """.vt-antigravity-trigger:hover, .vt-antigravity-trigger.active {
            background: var(--vt-dropdown-model-hover);
            border-color: var(--vt-purple);
            color: var(--vt-trigger-text);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }"""
)

# 5. Update .btn-chat-action:hover
content = content.replace(
    """.btn-chat-action:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
        }""",
    """.btn-chat-action:hover {
            background: var(--vt-dropdown-model-hover);
            border-color: var(--vt-purple);
            color: var(--vt-trigger-text);
        }"""
)

# 6. Update .chat-image-preview-bar
content = content.replace(
    """.chat-image-preview-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            border: 1.5px solid #bae6fd;
            border-radius: 14px;
            padding: 8px 12px;
            margin-bottom: 10px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            animation: fadeInSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }""",
    """.chat-image-preview-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--vt-bot-bubble-bg);
            border: 1.5px solid var(--vt-bot-bubble-border);
            border-radius: 14px;
            padding: 8px 12px;
            margin-bottom: 10px;
            box-shadow: var(--vt-bot-bubble-shadow);
            animation: fadeInSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }"""
)

# 7. Add --vt-input-pill-focus-bg to :root and body.adm-light-mode
content = content.replace(
    "--vt-action-chip-color: #cbd5e1;",
    "--vt-action-chip-color: #cbd5e1;\n            --vt-input-pill-focus-bg: rgba(14, 7, 34, 0.95);"
)
content = content.replace(
    "--vt-action-chip-color: #475569;",
    "--vt-action-chip-color: #475569;\n            --vt-input-pill-focus-bg: #ffffff;"
)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)

print("SUCCESS: Refined chat stream deck and interactive elements!")
