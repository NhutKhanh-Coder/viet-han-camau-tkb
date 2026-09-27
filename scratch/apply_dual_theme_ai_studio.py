# -*- coding: utf-8 -*-
import re

css_theme_vars = """/* =====================================================================
           ★ VŨ TRỤ AI DESIGN SYSTEM - DUAL THEME (LOFI DARK & CLEAN WHITE) ★
           Seamless automatic switching between Dark Cosmic & Pure White
           ===================================================================== */
        :root {
            /* === DARK MODE DEFAULT: Deep Cosmic Neon Violet === */
            --vt-void: #060214;
            --vt-deep: #0e0722;
            --vt-card: rgba(18, 9, 44, 0.92);
            --vt-card-border: rgba(147, 51, 234, 0.32);
            --vt-border-glow: rgba(192, 132, 252, 0.35);
            
            --vt-purple: #c084fc;
            --vt-purple-grad: linear-gradient(135deg, #7c3aed, #a855f7);
            --vt-blue-grad: linear-gradient(135deg, #0284c7, #38bdf8);
            --vt-cyan: #38bdf8;
            --vt-pink: #f472b6;
            --vt-green: #34d399;
            
            --vt-text: #f8fafc;
            --vt-sub: #cbd5e1;
            --vt-muted: #8b7bb3;
            
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --font-heading: 'Outfit', sans-serif;

            /* Portal background */
            --vt-portal-bg: #090514;
            --vt-portal-bg-img: radial-gradient(circle at 10% 20%, rgba(124, 58, 237, 0.15) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(56, 189, 248, 0.1) 0%, transparent 40%);
            --vt-canvas-display: block;

            /* Top Row: Hi Keria */
            --vt-keria-bg: radial-gradient(circle at 50% 25%, rgba(56, 189, 248, 0.2) 0%, rgba(20, 10, 52, 0.92) 75%);
            --vt-keria-border: rgba(168, 85, 247, 0.45);
            --vt-keria-shadow: 0 8px 24px rgba(0, 0, 0, 0.5), 0 0 20px rgba(124, 58, 237, 0.2);
            --vt-keria-overlay: radial-gradient(circle at 50% 35%, rgba(20, 10, 52, 0.35) 0%, rgba(14, 7, 34, 0.88) 60%, rgba(10, 5, 24, 0.98) 100%);
            --vt-keria-btn-bg: rgba(25, 12, 55, 0.85);
            --vt-keria-btn-border: rgba(168, 85, 247, 0.35);
            --vt-keria-btn-color: #f1f5f9;
            --vt-keria-persona-bg: rgba(10, 5, 24, 0.7);
            --vt-keria-persona-border: rgba(147, 51, 234, 0.25);
            --vt-keria-persona-text: #a79bb7;

            /* Top Row: Hero Banner */
            --vt-hero-banner-border: rgba(168, 85, 247, 0.35);
            --vt-hero-banner-shadow: 0 8px 25px -6px rgba(0, 0, 0, 0.6), 0 0 25px rgba(147, 51, 234, 0.25);
            --vt-hero-banner-bg: #080318;
            --vt-hero-btn-bg: rgba(25, 12, 55, 0.85);
            --vt-hero-btn-border: rgba(168, 85, 247, 0.4);
            --vt-hero-btn-color: #f8fafc;

            /* Chat Terminal */
            --vt-terminal-bg: rgba(16, 8, 38, 0.94);
            --vt-terminal-border: rgba(147, 51, 234, 0.32);
            --vt-terminal-shadow: 0 12px 40px rgba(0, 0, 0, 0.6), 0 0 30px rgba(124, 58, 237, 0.15);
            --vt-terminal-backdrop: blur(16px);

            /* Left Sidebar */
            --vt-sidebar-bg: linear-gradient(180deg, rgba(14, 7, 36, 0.96) 0%, rgba(9, 4, 25, 0.98) 100%);
            --vt-sidebar-border: rgba(147, 51, 234, 0.22);
            --vt-side-header-bg: rgba(18, 9, 44, 0.98);
            --vt-side-header-border: rgba(147, 51, 234, 0.22);
            --vt-side-title-color: #f8fafc;
            --vt-side-icon-btn-bg: rgba(25, 12, 55, 0.8);
            --vt-side-icon-btn-border: rgba(168, 85, 247, 0.3);
            --vt-side-icon-btn-color: #a79bb7;

            --vt-btn-new-chat-bg: rgba(124, 58, 237, 0.15);
            --vt-btn-new-chat-border: #a855f7;
            --vt-btn-new-chat-color: #d8b4fe;

            --vt-search-box-bg: rgba(10, 5, 26, 0.7);
            --vt-search-box-border: rgba(147, 51, 234, 0.3);
            --vt-search-box-text: #f8fafc;
            --vt-search-box-placeholder: #8b7bb3;

            --vt-session-bg: rgba(22, 11, 50, 0.65);
            --vt-session-border: rgba(147, 51, 234, 0.22);
            --vt-session-title: #e2e8f0;
            --vt-session-time: #8b7bb3;
            --vt-session-meta: #a79bb7;
            --vt-session-badge-bg: rgba(124, 58, 237, 0.25);
            --vt-session-badge-border: rgba(168, 85, 247, 0.35);
            --vt-session-badge-color: #d8b4fe;
            --vt-session-hover-bg: rgba(35, 18, 75, 0.85);
            --vt-session-hover-border: rgba(168, 85, 247, 0.45);
            --vt-session-active-bg: rgba(124, 58, 237, 0.28);
            --vt-session-active-border: #c084fc;
            --vt-session-active-title: #f3e8ff;

            /* Right Chat Area */
            --vt-chat-main-bg: rgba(12, 6, 28, 0.88);
            --vt-chat-header-bg: rgba(18, 9, 44, 0.96);
            --vt-chat-header-border: rgba(147, 51, 234, 0.25);
            --vt-chat-header-name: #f8fafc;

            /* Model Trigger & Dropdown */
            --vt-trigger-bg: #18181b;
            --vt-trigger-border: rgba(255, 255, 255, 0.14);
            --vt-trigger-text: #f4f4f5;
            --vt-trigger-lbl: #a79bb7;
            --vt-trigger-effort-bg: rgba(124, 58, 237, 0.2);
            --vt-trigger-effort-border: rgba(168, 85, 247, 0.3);
            --vt-trigger-effort-color: #d8b4fe;

            --vt-dropdown-bg: rgba(18, 9, 44, 0.98);
            --vt-dropdown-border: rgba(168, 85, 247, 0.35);
            --vt-dropdown-shadow: 0 16px 40px rgba(0, 0, 0, 0.7), 0 0 30px rgba(124, 58, 237, 0.2);
            --vt-dropdown-header-border: rgba(147, 51, 234, 0.25);
            --vt-dropdown-title: #a79bb7;
            --vt-dropdown-search-bg: rgba(10, 5, 26, 0.8);
            --vt-dropdown-search-border: rgba(147, 51, 234, 0.35);
            --vt-dropdown-search-text: #f8fafc;
            --vt-dropdown-tabs-border: rgba(147, 51, 234, 0.25);
            --vt-dropdown-tab-text: #a79bb7;
            --vt-dropdown-model-border: rgba(147, 51, 234, 0.15);
            --vt-dropdown-model-name: #f8fafc;
            --vt-dropdown-model-sub: #8b7bb3;
            --vt-dropdown-model-hover: rgba(124, 58, 237, 0.2);
            --vt-dropdown-model-active: rgba(124, 58, 237, 0.35);

            /* Message Bubbles */
            --vt-bot-bubble-bg: rgba(22, 11, 56, 0.88);
            --vt-bot-bubble-border: rgba(147, 51, 234, 0.25);
            --vt-bot-bubble-text: #f8fafc;
            --vt-bot-bubble-shadow: 0 8px 25px -4px rgba(0, 0, 0, 0.4);

            /* Bottom Deck & Input */
            --vt-input-deck-bg: rgba(12, 6, 28, 0.95);
            --vt-input-deck-border: rgba(147, 51, 234, 0.22);
            --vt-input-pill-bg: rgba(8, 4, 22, 0.85);
            --vt-input-pill-border: rgba(147, 51, 234, 0.35);
            --vt-input-pill-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.5);
            --vt-input-text: #f8fafc;
            --vt-input-placeholder: #8b7bb3;
            --vt-action-chip-bg: rgba(22, 11, 52, 0.7);
            --vt-action-chip-border: rgba(147, 51, 234, 0.25);
            --vt-action-chip-color: #cbd5e1;

            /* Empty State */
            --vt-empty-state-h4: #cbd5e1;
            --vt-empty-state-p: #8b7bb3;
            --vt-empty-state-icon: rgba(168, 85, 247, 0.4);

            /* Voice Modal */
            --vt-voice-card-bg: rgba(18, 9, 44, 0.98);
            --vt-voice-card-border: rgba(168, 85, 247, 0.4);
            --vt-voice-card-shadow: 0 25px 60px rgba(0, 0, 0, 0.7), 0 0 40px rgba(124, 58, 237, 0.3);
            --vt-voice-transcript-bg: rgba(10, 5, 26, 0.7);
            --vt-voice-transcript-border: rgba(147, 51, 234, 0.25);
            --vt-voice-quick-bg: rgba(10, 5, 26, 0.8);
            --vt-voice-quick-border: rgba(147, 51, 234, 0.3);
        }

        /* === LIGHT MODE: Crisp Modern White & Slate === */
        body.adm-light-mode {
            --vt-void: #f8fafc;
            --vt-deep: #ffffff;
            --vt-card: #ffffff;
            --vt-card-border: #e2e8f0;
            --vt-border-glow: rgba(124, 58, 237, 0.12);

            --vt-purple: #7c3aed;
            --vt-purple-grad: linear-gradient(135deg, #7c3aed, #9333ea);
            --vt-blue-grad: linear-gradient(135deg, #0284c7, #38bdf8);
            --vt-cyan: #0284c7;
            --vt-pink: #ec4899;
            --vt-green: #10b981;

            --vt-text: #0f172a;
            --vt-sub: #475569;
            --vt-muted: #64748b;

            --vt-portal-bg: #f8fafc !important;
            --vt-portal-bg-img: none !important;
            --vt-canvas-display: none !important;

            --vt-keria-bg: linear-gradient(135deg, #ffffff 0%, #f5f3ff 60%, #eff6ff 100%);
            --vt-keria-border: #e2e8f0;
            --vt-keria-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            --vt-keria-overlay: radial-gradient(circle at 50% 35%, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.82) 60%, rgba(255, 255, 255, 0.96) 100%);
            --vt-keria-btn-bg: rgba(255, 255, 255, 0.95);
            --vt-keria-btn-border: #cbd5e1;
            --vt-keria-btn-color: #334155;
            --vt-keria-persona-bg: #f1f5f9;
            --vt-keria-persona-border: #e2e8f0;
            --vt-keria-persona-text: #64748b;

            --vt-hero-banner-border: #e2e8f0;
            --vt-hero-banner-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            --vt-hero-banner-bg: #ffffff;
            --vt-hero-btn-bg: rgba(255, 255, 255, 0.95);
            --vt-hero-btn-border: #cbd5e1;
            --vt-hero-btn-color: #0f172a;

            --vt-terminal-bg: #ffffff;
            --vt-terminal-border: #e2e8f0;
            --vt-terminal-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.06);
            --vt-terminal-backdrop: none;

            --vt-sidebar-bg: #f8fafc;
            --vt-sidebar-border: #e2e8f0;
            --vt-side-header-bg: #ffffff;
            --vt-side-header-border: #e2e8f0;
            --vt-side-title-color: #0f172a;
            --vt-side-icon-btn-bg: #ffffff;
            --vt-side-icon-btn-border: #e2e8f0;
            --vt-side-icon-btn-color: #64748b;

            --vt-btn-new-chat-bg: #ffffff;
            --vt-btn-new-chat-border: #7c3aed;
            --vt-btn-new-chat-color: #7c3aed;

            --vt-search-box-bg: #ffffff;
            --vt-search-box-border: #e2e8f0;
            --vt-search-box-text: #0f172a;
            --vt-search-box-placeholder: #94a3b8;

            --vt-session-bg: #ffffff;
            --vt-session-border: #e2e8f0;
            --vt-session-title: #1e293b;
            --vt-session-time: #94a3b8;
            --vt-session-meta: #64748b;
            --vt-session-badge-bg: #ede9fe;
            --vt-session-badge-border: #ddd6fe;
            --vt-session-badge-color: #7c3aed;
            --vt-session-hover-bg: #f8fafc;
            --vt-session-hover-border: #cbd5e1;
            --vt-session-active-bg: #f5f3ff;
            --vt-session-active-border: #c084fc;
            --vt-session-active-title: #6d28d9;

            --vt-chat-main-bg: #f8fafc;
            --vt-chat-header-bg: #ffffff;
            --vt-chat-header-border: #e2e8f0;
            --vt-chat-header-name: #0f172a;

            --vt-trigger-bg: #ffffff;
            --vt-trigger-border: #cbd5e1;
            --vt-trigger-text: #0f172a;
            --vt-trigger-lbl: #64748b;
            --vt-trigger-effort-bg: #f1f5f9;
            --vt-trigger-effort-border: #e2e8f0;
            --vt-trigger-effort-color: #475569;

            --vt-dropdown-bg: #ffffff;
            --vt-dropdown-border: #e2e8f0;
            --vt-dropdown-shadow: 0 16px 40px -4px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.03);
            --vt-dropdown-header-border: #e2e8f0;
            --vt-dropdown-title: #64748b;
            --vt-dropdown-search-bg: #f8fafc;
            --vt-dropdown-search-border: #cbd5e1;
            --vt-dropdown-search-text: #0f172a;
            --vt-dropdown-tabs-border: #e2e8f0;
            --vt-dropdown-tab-text: #64748b;
            --vt-dropdown-model-border: #f1f5f9;
            --vt-dropdown-model-name: #0f172a;
            --vt-dropdown-model-sub: #64748b;
            --vt-dropdown-model-hover: #f8fafc;
            --vt-dropdown-model-active: #f5f3ff;

            --vt-bot-bubble-bg: #ffffff;
            --vt-bot-bubble-border: #e2e8f0;
            --vt-bot-bubble-text: #0f172a;
            --vt-bot-bubble-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);

            --vt-input-deck-bg: #ffffff;
            --vt-input-deck-border: #e2e8f0;
            --vt-input-pill-bg: #f8fafc;
            --vt-input-pill-border: #cbd5e1;
            --vt-input-pill-shadow: none;
            --vt-input-text: #0f172a;
            --vt-input-placeholder: #94a3b8;
            --vt-action-chip-bg: #f1f5f9;
            --vt-action-chip-border: #e2e8f0;
            --vt-action-chip-color: #475569;

            --vt-empty-state-h4: #0f172a;
            --vt-empty-state-p: #64748b;
            --vt-empty-state-icon: #cbd5e1;

            --vt-voice-card-bg: #ffffff;
            --vt-voice-card-border: #e2e8f0;
            --vt-voice-card-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            --vt-voice-transcript-bg: #f8fafc;
            --vt-voice-transcript-border: #e2e8f0;
            --vt-voice-quick-bg: #f8fafc;
            --vt-voice-quick-border: #cbd5e1;
        }"""

filepath = r"c:\xampp\htdocs\tkb\admin\ai_studio.php"
with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Match the comment and the :root block
old_header_pattern = r'/\* =+\s*★ VŨ TRỤ AI DESIGN SYSTEM[^\n]*\n[^\*]+\*/\s*:root\s*\{[^}]+\}'
if re.search(old_header_pattern, content):
    content = re.sub(old_header_pattern, css_theme_vars, content, count=1)
    print("Replaced with old_header_pattern")
else:
    # Just replace :root { ... }
    content = re.sub(r':root\s*\{[^}]+\}', css_theme_vars, content, count=1)
    print("Replaced with :root fallback")

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)

print("SUCCESS: Dual-Theme Variables Written!")
