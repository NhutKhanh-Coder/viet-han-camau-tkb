# -*- coding: utf-8 -*-
import re

css_white_theme = """    <style>
        /* =====================================================================
           ★ VŨ TRỤ AI DESIGN SYSTEM - PURE WHITE / LIGHT THEME ★
           Modern, Minimalist, High-Tech Studio Interface
           ===================================================================== */
        :root {
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
            
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        body.admin-portal {
            background-color: #f8fafc !important;
            color: #0f172a !important;
            font-family: var(--font-main);
            overflow-x: hidden;
            background-image: none !important;
        }

        /* Container */
        .vutru-wrap {
            max-width: 1560px;
            margin: 0 auto;
            padding: 6px 14px 8px;
            position: relative;
            z-index: 2;
        }

        /* Ambient Starfield Canvas - Hidden in clean white mode */
        #vutruCanvas {
            display: none !important;
        }

        /* Reusable Card */
        .vt-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* =====================================================================
           ★ 1. TOP HEADER ROW: [HI KERIA SQUARE BOX] + [VŨ TRỤ AI BANNER] ★
           ===================================================================== */
        .vt-top-header-row {
            display: flex;
            align-items: stretch;
            gap: 12px;
            margin-bottom: 8px;
            width: 100%;
            height: 148px;
            overflow: hidden;
        }

        /* SQUARE GREETING CARD (LEFT): HI KERIA WITH WAVING ROBOT */
        .vt-keria-square-card {
            width: 265px;
            min-width: 265px;
            flex-shrink: 0;
            height: 100%;
            margin: 0;
            padding: 8px 12px;
            background: linear-gradient(135deg, #ffffff 0%, #f5f3ff 60%, #eff6ff 100%);
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            cursor: default;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
        }
        .vt-keria-square-card:hover {
            transform: translateY(-2px);
            border-color: #c084fc;
            box-shadow: 0 8px 24px rgba(124, 58, 237, 0.12);
        }

        /* Outer Background Image Layer */
        .keria-card-bg-layer {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            opacity: 0.15;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), filter 0.3s ease;
            filter: saturate(1.2);
            pointer-events: none;
        }
        .vt-keria-square-card:hover .keria-card-bg-layer {
            transform: scale(1.05);
            opacity: 0.22;
        }
        .keria-card-bg-overlay {
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 35%, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.82) 60%, rgba(255, 255, 255, 0.96) 100%);
            z-index: 1;
            pointer-events: none;
        }

        /* Button to change outer image */
        .keria-bg-change-btn {
            position: absolute;
            top: 7px;
            right: 8px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 11px;
            font-weight: 700;
            padding: 3.5px 9px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            z-index: 25;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
            opacity: 0.92;
        }
        .keria-bg-change-btn:hover {
            opacity: 1;
            background: #ffffff;
            color: #7c3aed;
            border-color: #7c3aed;
            transform: scale(1.06);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.18);
        }

        .vt-keria-square-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.08) 0%, transparent 60%);
            animation: keriaAuraSpin 8s linear infinite;
            pointer-events: none;
            z-index: 2;
        }
        @keyframes keriaAuraSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Avatar circle */
        .keria-robot-img-wrap {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            border: 2.5px solid #7c3aed;
            box-shadow: 0 0 16px rgba(124, 58, 237, 0.22);
            overflow: hidden;
            position: relative;
            z-index: 10 !important;
            margin-bottom: 3px;
            animation: robotFloatWave 3.5s ease-in-out infinite;
            background: #ffffff;
            flex-shrink: 0;
            cursor: pointer;
        }
        @keyframes robotFloatWave {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-3px) scale(1.03); }
        }
        .keria-robot-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.25s ease;
        }
        .keria-robot-img-wrap:hover img {
            transform: scale(1.08);
        }
        .keria-robot-img-wrap:hover .robot-change-hover-overlay {
            opacity: 1;
            transform: translateY(0);
        }
        .robot-change-hover-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #7c3aed;
            font-size: 10.5px;
            font-weight: 700;
            opacity: 0;
            transform: translateY(4px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            border-radius: 50%;
            z-index: 12;
            gap: 2px;
            text-align: center;
            pointer-events: none;
        }
        .robot-change-hover-overlay i {
            font-size: 14px;
            color: #7c3aed;
        }
        .robot-camera-badge {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 19px;
            height: 19px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #9333ea);
            border: 1.5px solid #ffffff;
            color: #ffffff;
            font-size: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            z-index: 11;
            transition: transform 0.2s;
        }
        .keria-robot-img-wrap:hover .robot-camera-badge {
            transform: scale(1.18);
        }
        .keria-greeting-title {
            font-family: var(--font-heading);
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.02em;
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 50%, #db2777 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0 0 3px 0;
            display: flex;
            align-items: center;
            gap: 5px;
            position: relative;
            z-index: 10 !important;
            line-height: 1.15;
        }
        .keria-greeting-title .wave-hand {
            display: inline-block;
            font-size: 15px;
            animation: wavingHand 2s ease-in-out infinite;
            transform-origin: 70% 70%;
            -webkit-text-fill-color: initial;
        }
        @keyframes wavingHand {
            0%, 100% { transform: rotate(0deg); }
            20% { transform: rotate(14deg); }
            40% { transform: rotate(-10deg); }
            60% { transform: rotate(14deg); }
            80% { transform: rotate(-4deg); }
        }

        /* Mascot Persona Switcher */
        .keria-persona-toggle {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 2.5px;
            margin: 2px 0 3px;
            gap: 3px;
            position: relative;
            z-index: 10 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .kpersona-btn {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            padding: 2.5px 9px;
            border-radius: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
        }
        .kpersona-btn:hover {
            color: #0f172a;
        }
        .kpersona-btn.active {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.35);
        }
        .kpersona-btn.active.anime {
            background: linear-gradient(135deg, #d946ef, #f43f5e);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(244, 63, 94, 0.35);
        }
        .keria-greeting-sub {
            font-size: 10px;
            font-weight: 600;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 4px;
            position: relative;
            z-index: 10 !important;
        }
        .keria-greeting-sub .online-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--vt-green);
            box-shadow: 0 0 7px var(--vt-green);
        }

        /* HERO BANNER (RIGHT) */
        .vt-hero-banner {
            flex: 1;
            min-width: 0;
            height: 100%;
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            border: 1.5px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 0;
            background: #ffffff;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .vt-hero-img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .vt-hero-banner:hover .vt-hero-img {
            transform: scale(1.02);
        }
        .vt-hero-banner:hover .hero-banner-change-btn {
            opacity: 1;
            transform: translateY(0);
        }
        .hero-banner-change-btn {
            position: absolute;
            bottom: 12px;
            right: 16px;
            background: rgba(255, 255, 255, 0.95);
            border: 1.5px solid #cbd5e1;
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(8px);
            opacity: 0;
            transform: translateY(6px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 4;
        }
        .hero-banner-change-btn:hover {
            background: #ffffff;
            color: #7c3aed;
            border-color: #7c3aed;
            transform: translateY(0) scale(1.04);
        }

        /* =====================================================================
           ★ 2. MAIN CHAT TERMINAL ★
           ===================================================================== */
        .vt-main-grid {
            display: block;
            width: 100%;
        }

        .vt-chat-terminal {
            height: calc(100vh - 225px);
            min-height: 440px;
            max-height: 520px;
            display: flex;
            flex-direction: row;
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.06);
        }

        /* =====================================================================
           ★ LEFT COMPACT CHAT HISTORY SIDEBAR ★
           ===================================================================== */
        .vt-history-sidebar {
            width: 250px;
            min-width: 250px;
            height: 100%;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            transition: width 0.28s cubic-bezier(0.16, 1, 0.3, 1), min-width 0.28s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.22s ease;
            position: relative;
            z-index: 30;
            flex-shrink: 0;
        }

        .vt-history-sidebar.collapsed {
            width: 0 !important;
            min-width: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            border-right: none !important;
            opacity: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .history-side-header {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            background: #ffffff;
        }
        .history-side-title {
            font-family: var(--font-heading);
            font-size: 12.5px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 7px;
            letter-spacing: 0.02em;
        }
        .history-side-title i {
            color: #7c3aed;
            font-size: 13px;
        }
        .history-side-tools {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .side-icon-btn {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
            outline: none;
        }
        .side-icon-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .side-icon-btn.danger:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #ef4444;
        }

        .history-side-btn-wrap {
            padding: 10px 12px 6px;
        }
        .btn-side-new-chat {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            background: #ffffff;
            border: 1.5px solid #7c3aed;
            color: #7c3aed;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: var(--font-main);
            box-shadow: 0 1px 3px rgba(124, 58, 237, 0.08);
        }
        .btn-side-new-chat:hover {
            background: #7c3aed;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
            transform: translateY(-1px);
        }

        .history-side-search {
            margin: 4px 12px 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 5px 10px;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        }
        .history-side-search:focus-within {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        .history-side-search i {
            color: #94a3b8;
            font-size: 11px;
        }
        .history-side-search input {
            background: transparent;
            border: none;
            outline: none;
            color: #0f172a;
            font-size: 11.5px;
            font-family: var(--font-main);
            width: 100%;
        }
        .history-side-search input::placeholder {
            color: #94a3b8;
        }

        .history-side-list {
            flex: 1;
            overflow-y: auto;
            padding: 4px 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .history-side-list::-webkit-scrollbar { width: 4px; }
        .history-side-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 8px;
        }

        .session-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            cursor: pointer;
            transition: all 0.18s ease;
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        }
        .session-item:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        .session-item.active {
            background: #f5f3ff;
            border-color: #c084fc;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.12);
        }
        .session-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }
        .session-title {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            line-height: 1.3;
        }
        .session-item.active .session-title {
            color: #6d28d9;
            font-weight: 700;
        }
        .session-del-btn {
            opacity: 0;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 11px;
            padding: 2px 4px;
            border-radius: 4px;
            transition: all 0.18s ease;
        }
        .session-item:hover .session-del-btn {
            opacity: 1;
        }
        .session-del-btn:hover {
            color: #ef4444;
            background: #fee2e2;
        }

        .session-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10px;
            color: #64748b;
            gap: 4px;
        }
        .session-meta-left {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .session-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #ede9fe;
            border: 1px solid #ddd6fe;
            color: #7c3aed;
            font-size: 9px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 4px;
        }
        .session-msg-count {
            color: #64748b;
            font-size: 9.5px;
        }
        .session-time {
            font-size: 9.5px;
            color: #94a3b8;
        }

        .drawer-empty-state {
            padding: 30px 14px;
            text-align: center;
            color: #64748b;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 100%;
        }
        .drawer-empty-state i {
            font-size: 26px;
            color: #cbd5e1;
        }
        .drawer-empty-state h4 {
            margin: 0;
            font-size: 12.5px;
            color: #0f172a;
            font-weight: 700;
        }
        .drawer-empty-state p {
            margin: 0;
            font-size: 11px;
            line-height: 1.4;
            color: #64748b;
        }

        .vt-history-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 50;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .vt-history-backdrop.open {
            opacity: 1;
            pointer-events: auto;
        }

        /* =====================================================================
           ★ RIGHT MAIN CHAT TERMINAL AREA ★
           ===================================================================== */
        .vt-chat-main-area {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
            background: #f8fafc;
            z-index: 40;
        }

        .chat-top-header {
            padding: 10px 16px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: #ffffff;
            flex-wrap: nowrap;
            position: relative;
            z-index: 100;
        }
        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            flex-shrink: 1;
        }
        .btn-sidebar-toggle {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
            outline: none;
        }
        .btn-sidebar-toggle:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .chat-robot-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #7c3aed;
            box-shadow: 0 0 12px rgba(124, 58, 237, 0.25);
            flex-shrink: 0;
            animation: orbFloat 4s ease-in-out infinite;
        }
        .chat-robot-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        @keyframes orbFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        .chat-header-name h2 {
            margin: 0;
            font-family: var(--font-heading);
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.03em;
        }
        .chat-online-badge {
            font-size: 10.5px;
            font-weight: 600;
            color: #16a34a;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 1px;
        }
        .chat-online-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #16a34a;
            box-shadow: 0 0 8px #16a34a;
        }

        .chat-header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
            flex-shrink: 0;
        }

        /* =====================================================================
           ★ ANTIGRAVITY MODEL PICKER SYSTEM ★
           ===================================================================== */
        .vt-model-picker-container {
            position: relative;
            display: inline-block;
            z-index: 100;
        }

        .vt-antigravity-trigger {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: 6px 11px;
            color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.18s ease;
            outline: none;
            user-select: none;
        }
        .vt-antigravity-trigger:hover, .vt-antigravity-trigger.active {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .ag-trig-lbl {
            color: #64748b;
            font-size: 11.5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .ag-trig-lbl i {
            color: #7c3aed;
            font-size: 11px;
        }
        .ag-trig-name {
            font-weight: 600;
            color: #0f172a;
            max-width: 155px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ag-trig-effort {
            color: #475569;
            font-size: 11px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 1px 5px;
            font-weight: 600;
        }
        .ag-trig-fast {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #0284c7;
            font-size: 10px;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 10px;
        }
        .ag-trig-chevron {
            font-size: 9.5px;
            color: #94a3b8;
            transition: transform 0.2s ease;
            margin-left: 2px;
        }
        .vt-antigravity-trigger.active .ag-trig-chevron {
            transform: rotate(180deg);
        }

        .vt-antigravity-dropdown {
            position: absolute;
            top: calc(100% + 7px);
            left: 0 !important;
            right: auto;
            width: 380px;
            max-width: min(380px, calc(100vw - 32px));
            max-height: 520px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 16px 40px -4px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 8px 6px;
            display: none;
            flex-direction: column;
            z-index: 99999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            animation: agDropFade 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            box-sizing: border-box;
        }
        .vt-antigravity-dropdown.open {
            display: flex;
        }
        @keyframes agDropFade {
            from { opacity: 0; transform: translateY(-6px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .ag-dropdown-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 8px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .ag-hdr-title {
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
        }
        .ag-hdr-search {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 3px 8px;
            width: 145px;
        }
        .ag-hdr-search i {
            font-size: 10px;
            color: #94a3b8;
        }
        .ag-hdr-search input {
            background: transparent;
            border: none;
            outline: none;
            color: #0f172a;
            font-size: 11px;
            width: 100%;
        }
        .ag-hdr-search input::placeholder {
            color: #94a3b8;
        }

        .ag-tabs-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 2px 2px;
        }
        .ag-tabs-scroll-btn {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 10px;
            width: 20px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: all 0.15s;
            z-index: 5;
            padding: 0;
            border-radius: 4px;
        }
        .ag-tabs-scroll-btn:hover {
            color: #0f172a;
            background: #f1f5f9;
        }
        .ag-category-tabs {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 5px 2px;
            overflow-x: auto;
            border-bottom: none;
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            scrollbar-width: none;
        }
        .ag-category-tabs::-webkit-scrollbar { display: none; }
        .ag-category-tabs.grabbing { cursor: grabbing !important; }
        .ag-tab-btn {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 11px;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 5px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s;
            flex-shrink: 0;
        }
        .ag-tab-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .ag-tab-btn.active {
            background: #ede9fe;
            color: #7c3aed;
            font-weight: 600;
        }

        .ag-model-list {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden !important;
            padding: 4px 2px 2px;
            max-height: 400px;
            width: 100%;
            box-sizing: border-box;
        }
        .ag-model-list::-webkit-scrollbar { width: 5px; }
        .ag-model-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        .ag-group-label {
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 8px 8px 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .ag-model-item {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 9px;
            border-radius: 7px;
            cursor: pointer;
            color: #1e293b;
            font-size: 12.5px;
            transition: background 0.12s ease;
            user-select: none;
        }
        .ag-model-item:hover, .ag-model-item.has-sub-open {
            background: #f1f5f9;
            color: #0f172a;
        }
        .ag-model-item.selected {
            background: #f5f3ff;
        }
        .ag-item-left {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
            flex: 1;
        }
        .ag-item-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: transparent;
            flex-shrink: 0;
        }
        .ag-model-item.selected .ag-item-dot {
            background: #7c3aed;
            box-shadow: 0 0 6px rgba(124, 58, 237, 0.4);
        }
        .ag-item-name {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ag-model-item.selected .ag-item-name {
            font-weight: 600;
            color: #6d28d9;
        }
        .ag-item-right {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
        }
        .ag-item-effort {
            font-size: 11px;
            color: #64748b;
            font-weight: 400;
        }
        .ag-item-fast-badge {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 10px;
            padding: 1px 5px;
            border-radius: 9px;
            font-weight: 500;
        }
        .ag-item-info {
            color: #94a3b8;
            font-size: 11.5px;
            cursor: help;
            padding: 2px;
            transition: color 0.15s;
        }
        .ag-item-info:hover {
            color: #7c3aed;
        }
        .ag-item-arrow {
            color: #94a3b8;
            font-size: 10px;
            margin-left: 2px;
            transition: transform 0.15s;
        }
        .ag-model-item:hover .ag-item-arrow {
            color: #0f172a;
            transform: translateX(1px);
        }

        .ag-effort-submenu {
            position: absolute;
            top: 50%;
            left: calc(100% + 8px);
            right: auto;
            transform: translateY(-50%);
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.04);
            padding: 5px;
            min-width: 105px;
            display: none;
            flex-direction: column;
            gap: 3px;
            z-index: 100000;
            animation: agSubFade 0.15s ease;
        }
        .ag-model-item:hover .ag-effort-submenu,
        .ag-effort-submenu.show {
            display: flex;
        }
        @keyframes agSubFade {
            from { opacity: 0; transform: translateY(-50%) translateX(-4px); }
            to { opacity: 1; transform: translateY(-50%) translateX(0); }
        }
        .ag-sub-btn {
            background: transparent;
            border: 1.5px solid transparent;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12.5px;
            font-weight: 500;
            color: #475569;
            text-align: left;
            cursor: pointer;
            transition: all 0.14s;
            font-family: inherit;
        }
        .ag-sub-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .ag-sub-btn.selected {
            border-color: #7c3aed !important;
            background: #f5f3ff !important;
            color: #6d28d9 !important;
            font-weight: 600 !important;
        }

        .btn-chat-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #334155;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            outline: none;
            font-family: var(--font-main);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .btn-chat-action:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }
        .btn-new-chat {
            background: linear-gradient(135deg, #7c3aed, #9333ea);
            border-color: transparent;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.25);
        }
        .btn-new-chat:hover {
            background: linear-gradient(135deg, #6d28d9, #7e22ce);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
        }
        .history-count-badge {
            background: #7c3aed;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 8px;
            margin-left: 2px;
        }

        /* Responsive on smaller screens */
        @media (max-width: 860px) {
            .vt-top-header-row {
                flex-direction: column;
                height: auto;
                gap: 10px;
            }
            .vt-keria-square-card {
                width: 100%;
                min-width: 100%;
                height: 130px;
            }
            .vt-hero-banner {
                width: 100%;
                height: 110px;
            }
            .vt-history-sidebar {
                position: absolute;
                top: 0;
                left: 0;
                height: 100%;
                width: 260px;
                z-index: 60;
                transform: translateX(-100%);
                box-shadow: 10px 0 30px rgba(0, 0, 0, 0.15);
            }
            .vt-history-sidebar.mobile-open {
                transform: translateX(0);
            }
            .vt-history-sidebar.collapsed {
                width: 260px !important;
                min-width: 260px !important;
                transform: translateX(-100%) !important;
                opacity: 1 !important;
            }
        }

        /* Chat Stream */
        .chat-stream-deck {
            flex: 1;
            overflow-y: auto;
            padding: 12px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #f8fafc;
        }
        .chat-stream-deck::-webkit-scrollbar { width: 6px; }
        .chat-stream-deck::-webkit-scrollbar-track { background: transparent; }
        .chat-stream-deck::-webkit-scrollbar-thumb { 
            background: #cbd5e1; 
            border-radius: 10px; 
        }

        /* Message Rows */
        .msg-row {
            display: flex;
            gap: 10px;
            max-width: 90%;
            animation: msgFade 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes msgFade {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .msg-row.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        .msg-row.bot {
            align-self: flex-start;
        }

        .msg-user-avatar, .msg-bot-avatar {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .msg-bot-avatar {
            border: 1.5px solid #7c3aed;
        }
        .msg-bot-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .msg-user-avatar {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            border: 1.5px solid #38bdf8;
        }

        /* Message Bubbles */
        .msg-bubble-box {
            padding: 11px 16px;
            border-radius: 14px;
            font-size: 14px;
            line-height: 1.55;
            word-break: break-word;
            position: relative;
        }
        .msg-row.bot .msg-bubble-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #0f172a;
            border-top-left-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .msg-row.user .msg-bubble-box {
            background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
            border: none;
            color: #ffffff;
            border-top-right-radius: 4px;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.25);
        }

        /* Embedded Spiral Galaxy in Welcome Box */
        .msg-galaxy-bg {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 72px;
            height: auto;
            opacity: 0.18;
            pointer-events: none;
            border-radius: 50%;
            animation: galaxyRotate 25s linear infinite;
        }
        @keyframes galaxyRotate {
            from { transform: translateY(-50%) rotate(0deg); }
            to { transform: translateY(-50%) rotate(360deg); }
        }

        .msg-time-lbl {
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .msg-row.user .msg-time-lbl {
            justify-content: flex-end;
            color: #7c3aed;
        }

        /* Markdown in Messages */
        .msg-bubble-box p { margin: 0 0 8px; }
        .msg-bubble-box p:last-child { margin-bottom: 0; }
        .msg-bubble-box strong { color: #0f172a; font-weight: 700; }
        .msg-bubble-box em { color: #0284c7; font-style: italic; }
        .msg-bubble-box code {
            background: #f1f5f9;
            color: #7c3aed;
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 12.5px;
            font-family: monospace;
            border: 1px solid #e2e8f0;
        }
        .msg-bubble-box pre {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 12px 16px;
            overflow-x: auto;
            margin: 8px 0;
            position: relative;
        }
        .msg-bubble-box pre code {
            background: transparent;
            border: none;
            padding: 0;
            color: #f8fafc;
            display: block;
            line-height: 1.5;
        }
        .code-copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .code-copy-btn:hover {
            background: #7c3aed;
            color: #ffffff;
        }

        /* Typing Indicator */
        .typing-wave {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .typing-wave .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--vt-purple);
            animation: dotWave 1.4s infinite;
        }
        .typing-wave .dot:nth-child(2) { animation-delay: 0.2s; background: var(--vt-cyan); }
        .typing-wave .dot:nth-child(3) { animation-delay: 0.4s; background: var(--vt-pink); }
        @keyframes dotWave {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.3; }
            30% { transform: translateY(-5px); opacity: 1; }
        }

        /* Chat Input Footer */
        .chat-bottom-deck {
            padding: 8px 16px 8px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }

        /* Main Input Box */
        .chat-input-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 14px;
            padding: 4px 12px;
            box-shadow: none;
            transition: all 0.2s;
        }
        .chat-input-pill:focus-within {
            background: #ffffff;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
        }
        .chat-input-plus {
            color: #64748b;
            font-size: 15px;
            cursor: pointer;
            transition: color 0.15s;
        }
        .chat-input-plus:hover {
            color: #7c3aed;
        }
        .chat-input-textarea {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: #0f172a;
            font-family: var(--font-main);
            font-size: 14px;
            resize: none;
            max-height: 80px;
            min-height: 26px;
            line-height: 1.35;
            padding: 3px 0;
        }
        .chat-input-textarea::placeholder {
            color: #94a3b8;
            font-size: 13.5px;
        }

        .chat-tool-icon {
            background: none;
            border: none;
            color: #64748b;
            font-size: 14px;
            cursor: pointer;
            padding: 3px;
            transition: color 0.15s;
        }
        .chat-tool-icon:hover { color: #7c3aed; }
        .chat-tool-icon.active { color: #ef4444; animation: dotWave 0.8s infinite; }

        .chat-submit-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #9333ea);
            border: none;
            color: #ffffff;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(124, 58, 237, 0.35);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            flex-shrink: 0;
        }
        .chat-submit-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.5);
        }
        .chat-submit-btn:disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Bottom Quick Suggestion Pills Bar */
        .bottom-pills-bar {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 6px;
            overflow-x: auto;
            padding-bottom: 2px;
        }
        .bottom-pills-bar::-webkit-scrollbar { height: 3px; }
        .bottom-pills-bar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .action-chip {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 3px 10px;
            color: #475569;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }
        .action-chip:hover {
            background: #ede9fe;
            border-color: #c084fc;
            color: #7c3aed;
            transform: translateY(-1px);
        }

        /* Attached image preview */
        .chat-image-preview-bar {
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
        }
        @keyframes fadeInSlideUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .preview-thumb-wrap {
            position: relative;
            width: 48px;
            height: 48px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
            border: 1.5px solid #0284c7;
            background: #f8fafc;
        }
        .preview-thumb-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .preview-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .preview-name {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .preview-sub {
            font-size: 11px;
            color: #0284c7;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .preview-tag {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #0284c7;
        }
        .btn-remove-preview-img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .btn-remove-preview-img:hover {
            background: #ef4444;
            color: #ffffff;
            transform: scale(1.1);
        }

        /* Drag & Drop Feedback */
        .chat-bottom-deck.drag-over {
            background: #f5f3ff !important;
            border-top: 1.5px dashed #7c3aed !important;
        }
        .chat-input-pill.drag-over {
            border-color: #7c3aed !important;
            box-shadow: 0 0 15px rgba(124, 58, 237, 0.25) !important;
        }

        /* Message Bubble Attached Image */
        .msg-attached-img-wrap {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 8px;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            max-width: 320px;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s;
        }
        .msg-attached-img-wrap:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        .msg-attached-img {
            display: block;
            width: 100%;
            max-height: 240px;
            object-fit: cover;
        }
        .msg-img-overlay-zoom {
            position: absolute;
            bottom: 6px;
            right: 6px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 4px;
            opacity: 0.85;
            transition: all 0.2s;
        }
        .msg-attached-img-wrap:hover .msg-img-overlay-zoom {
            opacity: 1;
            background: #7c3aed;
        }

        /* Lightbox */
        .studio-img-lightbox {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            z-index: 999999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.2s ease-out;
        }
        .studio-img-lightbox.active {
            display: flex;
        }
        .lightbox-content {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lightbox-content img {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 14px;
            border: 2px solid #ffffff;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }
        .lightbox-close-btn {
            position: absolute;
            top: -16px;
            right: -16px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            transition: all 0.2s;
        }
        .lightbox-close-btn:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #ef4444;
            transform: scale(1.1);
        }

        /* Toast */
        .vt-toast {
            position: fixed;
            top: 75px;
            right: 24px;
            background: #ffffff;
            border: 1.5px solid #7c3aed;
            border-radius: 14px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 18px;
            z-index: 999999;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12), 0 0 15px rgba(124, 58, 237, 0.15);
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vt-toast.show { opacity: 1; transform: translateY(0); }

        /* Voice Call Button */
        .keria-voice-call-btn {
            position: relative;
            z-index: 10 !important;
            margin-top: 7px;
            padding: 5px 12px;
            border-radius: 20px;
            background: linear-gradient(135deg, #0284c7 0%, #7c3aed 100%);
            border: none;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(124, 58, 237, 0.25);
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
        }
        .keria-voice-call-btn:hover {
            background: linear-gradient(135deg, #0369a1 0%, #6d28d9 100%);
            transform: scale(1.05);
            box-shadow: 0 5px 14px rgba(124, 58, 237, 0.35);
        }
        .keria-voice-call-btn .live-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 6px #10b981;
            animation: liveDotPulse 1.4s ease-in-out infinite;
        }
        @keyframes liveDotPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
        }

        .voice-live-deck-btn {
            color: #0284c7 !important;
            background: #e0f2fe !important;
            border: 1px solid #bae6fd !important;
            border-radius: 6px;
        }
        .voice-live-deck-btn:hover {
            background: #0284c7 !important;
            color: #ffffff !important;
            box-shadow: 0 0 10px rgba(2, 132, 199, 0.4) !important;
        }

        .msg-speak-btn {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            margin-left: 8px;
            transition: all 0.2s;
        }
        .msg-speak-btn:hover {
            color: #7c3aed;
            background: #ede9fe;
            border-color: #ddd6fe;
        }
        .msg-speak-btn.speaking {
            color: #10b981;
            border-color: #10b981;
            animation: pulseSpeaking 1s infinite alternate;
        }
        @keyframes pulseSpeaking {
            from { opacity: 0.7; }
            to { opacity: 1; }
        }

        /* Voice Call Modal */
        .cosmic-voice-modal-wrap {
            position: fixed;
            inset: 0;
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .cosmic-voice-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            animation: fadeInVoice 0.3s ease;
        }
        @keyframes fadeInVoice {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .cosmic-voice-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 24px 22px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #0f172a;
            overflow: hidden;
            animation: popUpVoice 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes popUpVoice {
            from { transform: scale(0.92) translateY(18px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }

        .voice-modal-close-btn {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 5;
        }
        .voice-modal-close-btn:hover {
            background: #fee2e2;
            color: #ef4444;
            border-color: #fca5a5;
            transform: rotate(90deg);
        }

        .voice-call-top-bar {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding: 0 4px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .voice-gender-pill-wrap,
        .voice-persona-pill-wrap {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 2px 3px;
            gap: 2px;
        }
        .vpersona-tab-btn {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
            user-select: none;
        }
        .vpersona-tab-btn:hover { color: #0f172a; }
        .vpersona-tab-btn.active {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #ffffff;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.5);
        }
        .vpersona-tab-btn.active.anime {
            background: linear-gradient(135deg, #d946ef, #f43f5e);
            color: #ffffff;
            box-shadow: 0 0 12px rgba(244, 63, 94, 0.6);
        }
        .vgender-tab-btn {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .vgender-tab-btn:hover { color: #0f172a; }
        .vgender-tab-btn.active {
            background: linear-gradient(135deg, #ec4899, #f472b6);
            color: #ffffff;
            box-shadow: 0 0 10px rgba(244, 114, 182, 0.5);
        }
        .vgender-tab-btn.active.male {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.5);
        }
        .voice-live-badge {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.06em;
            color: #0284c7;
            background: #e0f2fe;
            padding: 4px 10px;
            border-radius: 12px;
            border: 1px solid #bae6fd;
        }
        .voice-live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
            box-shadow: 0 0 8px #ef4444;
            animation: liveDotPulse 1.2s infinite;
        }
        .voice-model-tag {
            font-size: 11px;
            color: #7c3aed;
            font-weight: 600;
        }

        .voice-central-stage {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 8px 0 16px;
            position: relative;
            width: 100%;
        }
        .voice-orb-container {
            width: 130px;
            height: 130px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .voice-ripple-ring {
            position: absolute;
            inset: -15px;
            border-radius: 50%;
            border: 2px solid rgba(56, 189, 248, 0.4);
            pointer-events: none;
            opacity: 0;
        }
        .voice-ripple-ring.ring-1 {
            animation: rippleVoice 2.4s ease-out infinite;
        }
        .voice-ripple-ring.ring-2 {
            animation: rippleVoice 2.4s ease-out 0.8s infinite;
            border-color: rgba(168, 85, 247, 0.4);
        }
        .voice-ripple-ring.ring-3 {
            animation: rippleVoice 2.4s ease-out 1.6s infinite;
            border-color: rgba(244, 114, 182, 0.4);
        }
        @keyframes rippleVoice {
            0% { transform: scale(0.7); opacity: 0.8; }
            100% { transform: scale(1.6); opacity: 0; }
        }
        .voice-mascot-avatar-wrap {
            width: 105px;
            height: 105px;
            border-radius: 50%;
            border: 3px solid #7c3aed;
            box-shadow: 0 0 25px rgba(124, 58, 237, 0.25);
            overflow: hidden;
            position: relative;
            z-index: 3;
            background: #ffffff;
            transition: all 0.3s;
        }
        .voice-mascot-avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .voice-state-speaking .voice-mascot-avatar-wrap {
            border-color: #ec4899;
            box-shadow: 0 0 30px rgba(236, 72, 153, 0.4);
            animation: mascotSpeakingBob 1.2s ease-in-out infinite alternate;
        }
        .voice-state-listening .voice-mascot-avatar-wrap {
            border-color: #0284c7;
            box-shadow: 0 0 25px rgba(2, 132, 199, 0.35);
            animation: mascotListeningPulse 1.6s ease-in-out infinite;
        }
        .voice-state-thinking .voice-mascot-avatar-wrap {
            border-color: #7c3aed;
            box-shadow: 0 0 25px rgba(124, 58, 237, 0.35);
            animation: mascotThinkingSpin 3s linear infinite;
        }
        @keyframes mascotSpeakingBob {
            from { transform: scale(1); }
            to { transform: scale(1.08); }
        }
        @keyframes mascotListeningPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.04); }
        }
        @keyframes mascotThinkingSpin {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(360deg); }
        }

        .voice-audio-bars {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            height: 28px;
            margin-top: 14px;
        }
        .vbar {
            width: 4px;
            height: 6px;
            background: #0284c7;
            border-radius: 4px;
            transition: height 0.15s ease, background 0.3s;
        }
        .voice-state-speaking .vbar {
            background: linear-gradient(180deg, #ec4899, #7c3aed);
            animation: barBounce 0.8s ease-in-out infinite alternate;
        }
        .voice-state-listening .vbar {
            background: linear-gradient(180deg, #0284c7, #10b981);
            animation: barListen 1.2s ease-in-out infinite alternate;
        }
        .bar-1 { animation-delay: 0.1s !important; }
        .bar-2 { animation-delay: 0.25s !important; }
        .bar-3 { animation-delay: 0.4s !important; }
        .bar-4 { animation-delay: 0.15s !important; }
        .bar-5 { animation-delay: 0.35s !important; }
        .bar-6 { animation-delay: 0.2s !important; }
        .bar-7 { animation-delay: 0.45s !important; }
        @keyframes barBounce {
            0% { height: 6px; }
            100% { height: 26px; }
        }
        @keyframes barListen {
            0% { height: 4px; }
            100% { height: 16px; }
        }

        .voice-state-pill {
            margin-top: 12px;
            padding: 5px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #334155;
            transition: all 0.3s;
        }
        .voice-state-listening .voice-state-pill {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #059669;
        }
        .voice-state-speaking .voice-state-pill {
            background: #fdf2f8;
            border-color: #fbcfe8;
            color: #db2777;
        }
        .voice-state-thinking .voice-state-pill {
            background: #f5f3ff;
            border-color: #ddd6fe;
            color: #7c3aed;
        }

        .voice-transcript-box {
            width: 100%;
            max-height: 160px;
            min-height: 95px;
            overflow-y: auto;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 12px 14px;
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 13px;
            line-height: 1.5;
        }
        .voice-bubble-user {
            background: #e0f2fe;
            border-left: 3px solid #0284c7;
            padding: 6px 10px;
            border-radius: 8px;
            color: #0369a1;
        }
        .voice-bubble-bot {
            background: #f5f3ff;
            border-left: 3px solid #7c3aed;
            padding: 6px 10px;
            border-radius: 8px;
            color: #5b21b6;
        }
        .vspeaker-label {
            font-size: 10.5px;
            font-weight: 700;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }

        .voice-quick-input-row {
            width: 100%;
            display: flex;
            gap: 8px;
            margin-bottom: 14px;
        }
        .voice-quick-input-row input {
            flex: 1;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 8px 12px;
            color: #0f172a;
            font-size: 12px;
            outline: none;
            transition: border-color 0.2s;
        }
        .voice-quick-input-row input:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
        }
        .voice-quick-send-btn {
            background: linear-gradient(135deg, #7c3aed, #9333ea);
            border: none;
            color: #ffffff;
            border-radius: 12px;
            padding: 0 14px;
            cursor: pointer;
            font-size: 13px;
            transition: transform 0.2s;
        }
        .voice-quick-send-btn:hover {
            transform: scale(1.06);
        }

        .voice-controls-footer {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .voice-ctl-btn {
            padding: 8px 16px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
            color: #0f172a;
            transition: all 0.2s;
        }
        .voice-ctl-btn:hover {
            background: #e2e8f0;
            transform: scale(1.04);
        }
        .voice-ctl-interrupt {
            background: #fef9c3;
            border-color: #fde047;
            color: #854d0e;
        }
        .voice-ctl-interrupt:hover {
            background: #fef08a;
        }
        .voice-ctl-end {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            border-color: #ef4444;
            color: #ffffff;
            box-shadow: 0 2px 10px rgba(239, 68, 68, 0.3);
        }
        .voice-ctl-end:hover {
            background: linear-gradient(135deg, #b91c1c, #dc2626);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.5);
        }

        .keria-card-actions-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            margin-top: 3px;
            position: relative;
            z-index: 10 !important;
        }
        .keria-card-actions-row .keria-voice-call-btn {
            margin-top: 0 !important;
            padding: 4px 10px;
            font-size: 11px;
            flex: 1;
            justify-content: center;
            border-radius: 18px;
        }
    </style>"""

filepath = r"c:\xampp\htdocs\tkb\admin\ai_studio.php"
with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Replace <style>...</style> block
pattern = r"<style>.*?</style>"
match = re.search(pattern, content, re.DOTALL)
if not match:
    print("ERROR: <style> tag not found")
    exit(1)

content = content[:match.start()] + css_white_theme.strip() + content[match.end():]

# Also in formatStMarkdown, ensure markdown headings use dark text #0f172a
content = content.replace(
    "e.replace(/(?:^|\\n)#\\s+([^\\n]+)/g, '<h2 style=\"color:#ffffff;",
    "e.replace(/(?:^|\\n)#\\s+([^\\n]+)/g, '<h2 style=\"color:#0f172a;"
)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)

print("SUCCESS: Updated admin/ai_studio.php with White Theme!")
