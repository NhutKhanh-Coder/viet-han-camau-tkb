<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$admin_name = $_SESSION['ho_ten'] ?? 'Quản Trị Viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VŨ TRỤ AI ★ Chat - Học - Sáng Tạo Không Giới Hạn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">

    <style>
        /* =====================================================================
           ★ VŨ TRỤ AI DESIGN SYSTEM ★
           Theme: Deep Space Cosmic, Neon Violet & Electric Cyan, Glassmorphism
           ===================================================================== */
        :root {
            --vt-void: #050212;
            --vt-deep: #0b0520;
            --vt-card: rgba(16, 9, 42, 0.78);
            --vt-card-border: rgba(147, 51, 234, 0.28);
            --vt-border-glow: rgba(192, 132, 252, 0.5);
            
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
        }

        body.admin-portal {
            background-color: var(--vt-void) !important;
            color: var(--vt-text);
            font-family: var(--font-main);
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(124, 58, 237, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(56, 189, 248, 0.08) 0%, transparent 40%);
            background-attachment: fixed;
        }

        /* Container */
        .vutru-wrap {
            max-width: 1560px;
            margin: 0 auto;
            padding: 8px 14px 60px;
            position: relative;
            z-index: 2;
        }

        /* Ambient Starfield Canvas */
        #vutruCanvas {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            opacity: 0.8;
        }

        /* Reusable Card */
        .vt-card {
            background: var(--vt-card);
            border: 1px solid var(--vt-card-border);
            border-radius: 18px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.6), 0 0 25px rgba(124, 58, 237, 0.15);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
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
            gap: 14px;
            margin-bottom: 14px;
            width: 100%;
            height: 168px;
        }

        /* SQUARE GREETING CARD (LEFT): HI KERIA WITH WAVING ROBOT */
        .vt-keria-square-card {
            width: 250px;
            min-width: 250px;
            flex-shrink: 0;
            height: 100%;
            margin: 0;
            padding: 8px 12px;
            background: radial-gradient(circle at 50% 25%, rgba(56, 189, 248, 0.2) 0%, rgba(20, 10, 52, 0.92) 75%);
            border: 1.5px solid rgba(168, 85, 247, 0.45);
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5), 0 0 20px rgba(124, 58, 237, 0.2);
            cursor: default;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
        }
        .vt-keria-square-card:hover {
            transform: translateY(-2px);
            border-color: rgba(56, 189, 248, 0.8);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.6), 0 0 25px rgba(56, 189, 248, 0.35);
        }

        /* Outer Background Image Layer (Ảnh Ngoài Ô Vuông) */
        .keria-card-bg-layer {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0; /* Nằm dưới cùng */
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), filter 0.3s ease;
            filter: brightness(0.65) saturate(1.2);
            pointer-events: none;
        }
        .vt-keria-square-card:hover .keria-card-bg-layer {
            transform: scale(1.05);
            filter: brightness(0.75) saturate(1.3);
        }
        .keria-card-bg-overlay {
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 35%, rgba(56, 189, 248, 0.08) 0%, rgba(12, 6, 32, 0.55) 60%, rgba(6, 3, 18, 0.85) 100%);
            z-index: 1; /* Nằm ngay trên ảnh nền ngoài */
            pointer-events: none;
        }

        /* Button to change outer image (Đổi ảnh ngoài) */
        .keria-bg-change-btn {
            position: absolute;
            top: 7px;
            right: 8px;
            background: rgba(14, 7, 36, 0.85);
            border: 1px solid rgba(56, 189, 248, 0.6);
            color: #e0f2fe;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            z-index: 25; /* Luôn nổi trên cùng để bấm */
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            opacity: 0.92;
        }
        .keria-bg-change-btn:hover {
            opacity: 1;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #ffffff;
            border-color: #38bdf8;
            transform: scale(1.08);
            box-shadow: 0 4px 14px rgba(56, 189, 248, 0.55);
        }

        .vt-keria-square-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.12) 0%, transparent 60%);
            animation: keriaAuraSpin 8s linear infinite;
            pointer-events: none;
            z-index: 2;
        }
        @keyframes keriaAuraSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* 3. Ảnh đại diện (AVATAR) tròn ở trong - LUÔN HIỂN THỊ NỔI BẬT Ở TRUNG TÂM */
        .keria-robot-img-wrap {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 2px solid var(--vt-cyan);
            box-shadow: 0 0 16px rgba(56, 189, 248, 0.75), 0 0 24px rgba(168, 85, 247, 0.45);
            overflow: hidden;
            position: relative;
            z-index: 10 !important; /* ĐẢM BẢO AVATAR NẰM TRÊN LỚP ẢNH NỀN NGOÀI */
            margin-bottom: 3px;
            animation: robotFloatWave 3.5s ease-in-out infinite;
            background: #08031d;
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
            background: rgba(10, 4, 30, 0.8);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #38bdf8;
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
            font-size: 15px;
            color: #ffffff;
        }
        .robot-camera-badge {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            border: 1.5px solid #ffffff;
            color: #ffffff;
            font-size: 9.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
            z-index: 11;
            transition: transform 0.2s;
        }
        .keria-robot-img-wrap:hover .robot-camera-badge {
            transform: scale(1.18);
        }
        .keria-greeting-title {
            font-family: var(--font-heading);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.03em;
            background: linear-gradient(135deg, #38bdf8 0%, #c084fc 50%, #f472b6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0 0 1px 0;
            display: flex;
            align-items: center;
            gap: 4px;
            text-shadow: 0 0 12px rgba(192, 132, 252, 0.35);
            position: relative;
            z-index: 10 !important;
        }
        .keria-greeting-title .wave-hand {
            display: inline-block;
            font-size: 14px;
            animation: wavingHand 2s ease-in-out infinite;
            transform-origin: 70% 70%;
            -webkit-text-fill-color: initial;
        }
        /* Mascot Persona Switcher: Robot vs Anime */
        .keria-persona-toggle {
            display: inline-flex;
            align-items: center;
            background: rgba(12, 6, 32, 0.85);
            border: 1px solid rgba(168, 85, 247, 0.5);
            border-radius: 18px;
            padding: 2px;
            margin: 2px 0 3px 0;
            gap: 2px;
            position: relative;
            z-index: 10 !important;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }
        .kpersona-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
        }
        .kpersona-btn:hover {
            color: #ffffff;
        }
        .kpersona-btn.active {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #ffffff;
            box-shadow: 0 0 8px rgba(56, 189, 248, 0.6);
        }
        .kpersona-btn.active.anime {
            background: linear-gradient(135deg, #d946ef, #f43f5e);
            color: #ffffff;
            box-shadow: 0 0 10px rgba(244, 63, 94, 0.7);
        }
        .keria-greeting-sub {
            font-size: 10px;
            font-weight: 600;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 4px;
            position: relative;
            z-index: 10 !important;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
        }
        .keria-greeting-sub .online-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--vt-green);
            box-shadow: 0 0 7px var(--vt-green);
        }

        /* HERO BANNER (RIGHT): SHRUNK HORIZONTALLY TO LEAVE SPACE FOR SQUARE BOX */
        .vt-hero-banner {
            flex: 1;
            min-width: 0;
            height: 100%;
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(168, 85, 247, 0.35);
            box-shadow: 0 8px 25px -6px rgba(0, 0, 0, 0.6), 0 0 25px rgba(147, 51, 234, 0.25);
            margin-bottom: 0;
            background: #080318;
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
            bottom: 10px;
            right: 14px;
            background: rgba(14, 7, 36, 0.86);
            border: 1.5px solid rgba(56, 189, 248, 0.6);
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            opacity: 0;
            transform: translateY(6px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6), 0 0 14px rgba(56, 189, 248, 0.35);
            z-index: 4;
        }
        .hero-banner-change-btn:hover {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            border-color: #38bdf8;
            transform: translateY(0) scale(1.05);
        }

        /* =====================================================================
           ★ 2. MAIN CHAT TERMINAL ★
           ===================================================================== */
        .vt-main-grid {
            display: block;
            width: 100%;
        }

        /* =====================================================================
           ★ RIGHT COLUMN: MAIN CHAT TERMINAL ★
           ===================================================================== */
        .vt-chat-terminal {
            height: 750px;
            display: flex;
            flex-direction: row;
            position: relative;
            overflow: hidden;
            border-radius: 16px;
        }

        /* =====================================================================
           ★ LEFT COMPACT CHAT HISTORY SIDEBAR ★
           ===================================================================== */
        .vt-history-sidebar {
            width: 250px;
            min-width: 250px;
            height: 100%;
            background: linear-gradient(180deg, rgba(14, 7, 36, 0.96) 0%, rgba(9, 4, 25, 0.98) 100%);
            border-right: 1px solid rgba(147, 51, 234, 0.22);
            display: flex;
            flex-direction: column;
            transition: width 0.28s cubic-bezier(0.16, 1, 0.3, 1), min-width 0.28s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.22s ease;
            position: relative;
            z-index: 30;
            flex-shrink: 0;
        }

        /* Collapsed state for desktop */
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

        /* Sidebar Header */
        .history-side-header {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(147, 51, 234, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            background: rgba(18, 9, 46, 0.7);
        }
        .history-side-title {
            font-family: var(--font-heading);
            font-size: 12.5px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 7px;
            letter-spacing: 0.02em;
        }
        .history-side-title i {
            color: var(--vt-purple);
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
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(147, 51, 234, 0.22);
            color: var(--vt-sub);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
            outline: none;
        }
        .side-icon-btn:hover {
            background: rgba(168, 85, 247, 0.25);
            border-color: var(--vt-purple);
            color: #ffffff;
        }
        .side-icon-btn.danger:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: #ef4444;
            color: #fca5a5;
        }

        /* New Chat Button in Sidebar */
        .history-side-btn-wrap {
            padding: 10px 12px 6px;
        }
        .btn-side-new-chat {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.2), rgba(139, 92, 246, 0.24));
            border: 1px solid rgba(56, 189, 248, 0.45);
            color: #ffffff;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: var(--font-main);
        }
        .btn-side-new-chat:hover {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.35), rgba(139, 92, 246, 0.4));
            border-color: rgba(56, 189, 248, 0.8);
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.4);
            transform: translateY(-1px);
        }

        /* Search in Sidebar */
        .history-side-search {
            margin: 4px 12px 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(20, 10, 52, 0.65);
            border: 1px solid rgba(147, 51, 234, 0.22);
            border-radius: 8px;
            padding: 5px 10px;
            transition: border-color 0.2s;
        }
        .history-side-search:focus-within {
            border-color: var(--vt-purple);
            box-shadow: 0 0 8px rgba(168, 85, 247, 0.25);
        }
        .history-side-search i {
            color: var(--vt-sub);
            font-size: 11px;
        }
        .history-side-search input {
            background: transparent;
            border: none;
            outline: none;
            color: #ffffff;
            font-size: 11.5px;
            font-family: var(--font-main);
            width: 100%;
        }
        .history-side-search input::placeholder {
            color: rgba(148, 163, 184, 0.5);
        }

        /* History Side List */
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
            background: rgba(147, 51, 234, 0.3);
            border-radius: 8px;
        }

        /* Compact Session Item */
        .session-item {
            background: rgba(20, 11, 55, 0.55);
            border: 1px solid rgba(147, 51, 234, 0.16);
            border-radius: 8px;
            padding: 8px 10px;
            cursor: pointer;
            transition: all 0.18s ease;
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
        }
        .session-item:hover {
            background: rgba(32, 17, 85, 0.7);
            border-color: rgba(192, 132, 252, 0.4);
            transform: translateY(-1px);
        }
        .session-item.active {
            background: linear-gradient(135deg, rgba(88, 28, 135, 0.5), rgba(15, 23, 42, 0.85));
            border-color: var(--vt-purple);
            box-shadow: 0 0 12px rgba(168, 85, 247, 0.28);
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
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            line-height: 1.3;
        }
        .session-item.active .session-title {
            color: #ffffff;
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
            color: #f87171;
            background: rgba(239, 68, 68, 0.2);
        }

        .session-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10px;
            color: var(--vt-sub);
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
            background: rgba(147, 51, 234, 0.15);
            border: 1px solid rgba(147, 51, 234, 0.25);
            color: var(--vt-purple);
            font-size: 9px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 4px;
        }
        .session-msg-count {
            color: #94a3b8;
            font-size: 9.5px;
        }
        .session-time {
            font-size: 9.5px;
            color: #64748b;
        }

        .drawer-empty-state {
            padding: 30px 14px;
            text-align: center;
            color: var(--vt-sub);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 100%;
        }
        .drawer-empty-state i {
            font-size: 26px;
            color: rgba(147, 51, 234, 0.35);
        }
        .drawer-empty-state h4 {
            margin: 0;
            font-size: 12.5px;
            color: #ffffff;
            font-weight: 700;
        }
        .drawer-empty-state p {
            margin: 0;
            font-size: 11px;
            line-height: 1.4;
            color: #94a3b8;
        }

        /* Backdrop for Mobile overlay */
        .vt-history-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(6, 2, 20, 0.72);
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
            background: rgba(10, 5, 26, 0.5);
        }

        /* Chat Header */
        .chat-top-header {
            padding: 12px 18px;
            border-bottom: 1px solid rgba(147, 51, 234, 0.22);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: rgba(16, 9, 44, 0.75);
            flex-wrap: wrap;
            position: relative;
            z-index: 10;
        }
        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-sidebar-toggle {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(168, 85, 247, 0.35);
            color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
            outline: none;
        }
        .btn-sidebar-toggle:hover {
            background: rgba(168, 85, 247, 0.3);
            border-color: var(--vt-purple);
            color: #ffffff;
            box-shadow: 0 0 10px rgba(168, 85, 247, 0.35);
        }
        .chat-robot-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--vt-purple);
            box-shadow: 0 0 12px rgba(192, 132, 252, 0.5);
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
            color: #ffffff;
            letter-spacing: 0.03em;
        }
        .chat-online-badge {
            font-size: 10.5px;
            font-weight: 600;
            color: var(--vt-green);
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
            background: var(--vt-green);
            box-shadow: 0 0 8px var(--vt-green);
        }

        .chat-header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* =====================================================================
           ★ ANTIGRAVITY MODEL PICKER SYSTEM ★
           ===================================================================== */
        .vt-model-picker-container {
            position: relative;
            display: inline-block;
            z-index: 100;
        }

        /* Antigravity Trigger Button */
        .vt-antigravity-trigger {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #18181b;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 9px;
            padding: 6px 11px;
            color: #f4f4f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
            transition: all 0.18s ease;
            outline: none;
            user-select: none;
        }
        .vt-antigravity-trigger:hover, .vt-antigravity-trigger.active {
            background: #27272a;
            border-color: rgba(255, 255, 255, 0.28);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.45);
        }
        .ag-trig-lbl {
            color: #a1a1aa;
            font-size: 11.5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .ag-trig-lbl i {
            color: var(--vt-purple);
            font-size: 11px;
        }
        .ag-trig-name {
            font-weight: 600;
            color: #ffffff;
            max-width: 155px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ag-trig-effort {
            color: #a1a1aa;
            font-size: 11px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 4px;
            padding: 1px 5px;
            font-weight: 500;
        }
        .ag-trig-fast {
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: #38bdf8;
            font-size: 10px;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 10px;
        }
        .ag-trig-chevron {
            font-size: 9.5px;
            color: #71717a;
            transition: transform 0.2s ease;
            margin-left: 2px;
        }
        .vt-antigravity-trigger.active .ag-trig-chevron {
            transform: rotate(180deg);
        }

        /* Antigravity Dropdown Menu */
        .vt-antigravity-dropdown {
            position: absolute;
            top: calc(100% + 7px);
            right: 0;
            width: 380px;
            max-height: 520px;
            background: #18181b;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 12px;
            box-shadow: 0 16px 36px -4px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 8px 6px;
            display: none;
            flex-direction: column;
            z-index: 99999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            animation: agDropFade 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vt-antigravity-dropdown.open {
            display: flex;
        }
        @keyframes agDropFade {
            from { opacity: 0; transform: translateY(-6px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Header inside Dropdown */
        .ag-dropdown-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 8px 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .ag-hdr-title {
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #71717a;
        }
        .ag-hdr-search {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #27272a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            padding: 3px 8px;
            width: 145px;
        }
        .ag-hdr-search i {
            font-size: 10px;
            color: #71717a;
        }
        .ag-hdr-search input {
            background: transparent;
            border: none;
            outline: none;
            color: #f4f4f5;
            font-size: 11px;
            width: 100%;
        }
        .ag-hdr-search input::placeholder {
            color: #71717a;
        }

        /* Category Filter Tabs Wrapper with Left/Right Scroll Arrows & Dragging */
        .ag-tabs-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(24, 24, 27, 0.95);
            padding: 2px 2px;
        }
        .ag-tabs-scroll-btn {
            background: transparent;
            border: none;
            color: #71717a;
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
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
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
        .ag-category-tabs::-webkit-scrollbar {
            display: none;
        }
        .ag-category-tabs.grabbing {
            cursor: grabbing !important;
        }
        .ag-tab-btn {
            background: transparent;
            border: none;
            color: #a1a1aa;
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
            background: rgba(255, 255, 255, 0.08);
            color: #f4f4f5;
        }
        .ag-tab-btn.active {
            background: rgba(147, 51, 234, 0.25);
            color: #c084fc;
            font-weight: 600;
        }

        /* Scrollable Model List */
        .ag-model-list {
            flex: 1;
            overflow-y: auto;
            padding: 4px 2px 2px;
            max-height: 400px;
        }
        .ag-model-list::-webkit-scrollbar { width: 5px; }
        .ag-model-list::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.18); border-radius: 4px; }

        /* Group Section Header */
        .ag-group-label {
            font-size: 10px;
            font-weight: 700;
            color: #71717a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 8px 8px 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Single Model Item Row */
        .ag-model-item {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 9px;
            border-radius: 7px;
            cursor: pointer;
            color: #e4e4e7;
            font-size: 12.5px;
            transition: background 0.12s ease;
            user-select: none;
        }
        .ag-model-item:hover, .ag-model-item.has-sub-open {
            background: rgba(255, 255, 255, 0.07);
            color: #ffffff;
        }
        .ag-model-item.selected {
            background: rgba(147, 51, 234, 0.16);
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
            background: #38bdf8;
            box-shadow: 0 0 8px #38bdf8;
        }
        .ag-item-name {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ag-model-item.selected .ag-item-name {
            font-weight: 600;
            color: #ffffff;
        }
        .ag-item-right {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
        }
        .ag-item-effort {
            font-size: 11px;
            color: #a1a1aa;
            font-weight: 400;
        }
        .ag-item-fast-badge {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #d4d4d8;
            font-size: 10px;
            padding: 1px 5px;
            border-radius: 9px;
            font-weight: 500;
        }
        .ag-item-info {
            color: #71717a;
            font-size: 11.5px;
            cursor: help;
            padding: 2px;
            transition: color 0.15s;
        }
        .ag-item-info:hover {
            color: #38bdf8;
        }
        .ag-item-arrow {
            color: #71717a;
            font-size: 10px;
            margin-left: 2px;
            transition: transform 0.15s;
        }
        .ag-model-item:hover .ag-item-arrow {
            color: #e4e4e7;
            transform: translateX(1px);
        }

        /* Floating Effort Submenu (Low / Medium / High) - Positioned on LEFT (Antigravity Style) */
        .ag-effort-submenu {
            position: absolute;
            top: 50%;
            right: calc(100% + 8px);
            transform: translateY(-50%);
            background: #18181b;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 10px;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(255, 255, 255, 0.06);
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
            from { opacity: 0; transform: translateY(-50%) translateX(4px); }
            to { opacity: 1; transform: translateY(-50%) translateX(0); }
        }
        .ag-sub-btn {
            background: transparent;
            border: 1.5px solid transparent;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12.5px;
            font-weight: 500;
            color: #a1a1aa;
            text-align: left;
            cursor: pointer;
            transition: all 0.14s;
            font-family: inherit;
        }
        .ag-sub-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }
        /* Selected effort with Antigravity distinctive blue rounded border */
        .ag-sub-btn.selected {
            border-color: #0091ff !important;
            background: rgba(0, 145, 255, 0.09) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
        }

        /* Top Header Action Buttons */
        .btn-chat-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(139, 92, 246, 0.16);
            border: 1px solid rgba(168, 85, 247, 0.38);
            color: #f1f5f9;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            outline: none;
            font-family: var(--font-main);
        }
        .btn-chat-action:hover {
            background: rgba(168, 85, 247, 0.3);
            border-color: rgba(192, 132, 252, 0.7);
            color: #ffffff;
            box-shadow: 0 0 14px rgba(168, 85, 247, 0.45);
            transform: translateY(-1px);
        }
        .btn-new-chat {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.18), rgba(139, 92, 246, 0.22));
            border-color: rgba(56, 189, 248, 0.45);
        }
        .btn-new-chat:hover {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.32), rgba(139, 92, 246, 0.36));
            border-color: rgba(56, 189, 248, 0.8);
            box-shadow: 0 0 14px rgba(56, 189, 248, 0.45);
        }
        .history-count-badge {
            background: var(--vt-purple);
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 8px;
            margin-left: 2px;
            box-shadow: 0 0 8px rgba(192, 132, 252, 0.6);
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
                box-shadow: 10px 0 30px rgba(0, 0, 0, 0.75);
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
            padding: 20px 22px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            background: radial-gradient(circle at 50% 15%, rgba(38, 16, 92, 0.2) 0%, rgba(6, 2, 20, 0.5) 80%);
        }
        .chat-stream-deck::-webkit-scrollbar { width: 6px; }
        .chat-stream-deck::-webkit-scrollbar-track { background: transparent; }
        .chat-stream-deck::-webkit-scrollbar-thumb { 
            background: rgba(147, 51, 234, 0.35); 
            border-radius: 10px; 
        }

        /* Message Rows */
        .msg-row {
            display: flex;
            gap: 12px;
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
            width: 38px;
            height: 38px;
            border-radius: 12px;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }
        .msg-bot-avatar {
            border: 1.5px solid var(--vt-purple);
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
            font-size: 15px;
            border: 1.5px solid var(--vt-cyan);
        }

        /* Message Bubbles */
        .msg-bubble-box {
            padding: 16px 20px;
            border-radius: 18px;
            font-size: 14.5px;
            line-height: 1.65;
            word-break: break-word;
            position: relative;
        }
        .msg-row.bot .msg-bubble-box {
            background: rgba(22, 11, 56, 0.88);
            border: 1px solid rgba(147, 51, 234, 0.25);
            color: var(--vt-text);
            border-top-left-radius: 4px;
            box-shadow: 0 8px 25px -4px rgba(0, 0, 0, 0.4);
        }
        .msg-row.user .msg-bubble-box {
            background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
            border: 1px solid rgba(192, 132, 252, 0.4);
            color: #ffffff;
            border-top-right-radius: 4px;
            box-shadow: 0 8px 25px -4px rgba(124, 58, 237, 0.4);
        }

        /* Embedded Spiral Galaxy in Welcome Box */
        .msg-galaxy-bg {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            width: 140px;
            height: auto;
            opacity: 0.9;
            pointer-events: none;
            border-radius: 50%;
            box-shadow: 0 0 30px rgba(192, 132, 252, 0.35);
            animation: galaxyRotate 25s linear infinite;
        }
        @keyframes galaxyRotate {
            from { transform: translateY(-50%) rotate(0deg); }
            to { transform: translateY(-50%) rotate(360deg); }
        }

        .msg-time-lbl {
            font-size: 11px;
            color: var(--vt-muted);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .msg-row.user .msg-time-lbl {
            justify-content: flex-end;
            color: #c4b5fd;
        }

        /* Markdown in Messages */
        .msg-bubble-box p { margin: 0 0 8px; }
        .msg-bubble-box p:last-child { margin-bottom: 0; }
        .msg-bubble-box strong { color: #ffffff; font-weight: 700; }
        .msg-bubble-box em { color: var(--vt-cyan); font-style: italic; }
        .msg-bubble-box code {
            background: rgba(0, 0, 0, 0.45);
            color: var(--vt-purple);
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 12.5px;
            font-family: monospace;
            border: 1px solid rgba(192, 132, 252, 0.2);
        }
        .msg-bubble-box pre {
            background: #060216;
            border: 1px solid rgba(147, 51, 234, 0.3);
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
            color: #e2e8f0;
            display: block;
            line-height: 1.5;
        }
        .code-copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(147, 51, 234, 0.3);
            border: 1px solid rgba(192, 132, 252, 0.4);
            color: #ffffff;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .code-copy-btn:hover {
            background: var(--vt-purple);
            color: #000;
        }

        /* Typing Indicator */
        .typing-wave {
            background: rgba(22, 11, 56, 0.88);
            border: 1px solid rgba(147, 51, 234, 0.25);
            border-radius: 14px;
            padding: 10px 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
            padding: 14px 20px;
            border-top: 1px solid rgba(147, 51, 234, 0.2);
            background: rgba(14, 7, 36, 0.85);
        }

        /* Main Input Box */
        .chat-input-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(8, 4, 22, 0.85);
            border: 1.5px solid rgba(147, 51, 234, 0.35);
            border-radius: 16px;
            padding: 6px 14px;
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.5);
            transition: all 0.2s;
        }
        .chat-input-pill:focus-within {
            border-color: var(--vt-cyan);
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.5), 0 0 20px rgba(56, 189, 248, 0.35);
        }
        .chat-input-plus {
            color: var(--vt-muted);
            font-size: 16px;
            cursor: pointer;
        }
        .chat-input-textarea {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: #ffffff;
            font-family: var(--font-main);
            font-size: 14.5px;
            resize: none;
            max-height: 100px;
            min-height: 28px;
            line-height: 1.4;
            padding: 4px 0;
        }
        .chat-input-textarea::placeholder {
            color: #64748b;
            font-size: 14px;
        }

        .chat-tool-icon {
            background: none;
            border: none;
            color: var(--vt-muted);
            font-size: 15px;
            cursor: pointer;
            padding: 4px;
            transition: color 0.15s;
        }
        .chat-tool-icon:hover { color: var(--vt-cyan); }
        .chat-tool-icon.active { color: #ef4444; animation: dotWave 0.8s infinite; }

        .chat-submit-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            border: none;
            color: #ffffff;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.45);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            flex-shrink: 0;
        }
        .chat-submit-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.6);
        }
        .chat-submit-btn:disabled {
            background: #1e1b4b;
            color: #64748b;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Bottom Quick Suggestion Pills Bar */
        .bottom-pills-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            overflow-x: auto;
            padding-bottom: 2px;
        }
        .bottom-pills-bar::-webkit-scrollbar { height: 3px; }
        .bottom-pills-bar::-webkit-scrollbar-thumb { background: rgba(147, 51, 234, 0.3); border-radius: 10px; }
        .action-chip {
            background: rgba(22, 11, 54, 0.6);
            border: 1px solid rgba(147, 51, 234, 0.25);
            border-radius: 20px;
            padding: 5px 12px;
            color: var(--vt-sub);
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s;
        }
        .action-chip:hover {
            background: rgba(147, 51, 234, 0.25);
            border-color: var(--vt-purple);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* =====================================================================
           ★ ATTACHED IMAGE & VISION PREVIEW STYLES ★
           ===================================================================== */
        /* Preview Bar before sending */
        .chat-image-preview-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(18, 10, 46, 0.95);
            border: 1px solid rgba(56, 189, 248, 0.5);
            border-radius: 14px;
            padding: 8px 12px;
            margin-bottom: 10px;
            box-shadow: 0 6px 22px rgba(0, 0, 0, 0.55), 0 0 16px rgba(56, 189, 248, 0.22);
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
            border: 1.5px solid var(--vt-cyan);
            background: #000;
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
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .preview-sub {
            font-size: 11px;
            color: var(--vt-cyan);
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .preview-tag {
            background: rgba(56, 189, 248, 0.2);
            border: 1px solid rgba(56, 189, 248, 0.4);
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #38bdf8;
        }
        .btn-remove-preview-img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #f87171;
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
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.6);
        }

        /* Drag & Drop Feedback */
        .chat-bottom-deck.drag-over {
            background: rgba(30, 16, 75, 0.96) !important;
            border-top: 1.5px dashed var(--vt-cyan) !important;
        }
        .chat-input-pill.drag-over {
            border-color: var(--vt-cyan) !important;
            box-shadow: 0 0 25px rgba(56, 189, 248, 0.7) !important;
        }

        /* Message Bubble Attached Image */
        .msg-attached-img-wrap {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 8px;
            cursor: pointer;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            background: rgba(0, 0, 0, 0.4);
            max-width: 320px;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s;
        }
        .msg-attached-img-wrap:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6), 0 0 16px rgba(56, 189, 248, 0.45);
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
            background: rgba(0, 0, 0, 0.7);
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
            background: rgba(124, 58, 237, 0.85);
        }

        /* Fullscreen Lightbox */
        .studio-img-lightbox {
            position: fixed;
            inset: 0;
            background: rgba(3, 1, 10, 0.92);
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
            border: 1.5px solid rgba(192, 132, 252, 0.45);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.85), 0 0 45px rgba(124, 58, 237, 0.45);
        }
        .lightbox-close-btn {
            position: absolute;
            top: -16px;
            right: -16px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(18, 10, 46, 0.95);
            border: 1.5px solid var(--vt-cyan);
            color: #ffffff;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6);
            transition: all 0.2s;
        }
        .lightbox-close-btn:hover {
            background: #ef4444;
            border-color: #ef4444;
            transform: scale(1.1);
        }

        /* Toast */
        .vt-toast {
            position: fixed;
            top: 75px;
            right: 24px;
            background: rgba(18, 9, 44, 0.95);
            border: 1px solid var(--vt-purple);
            border-radius: 14px;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 18px;
            z-index: 999999;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(192, 132, 252, 0.35);
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vt-toast.show { opacity: 1; transform: translateY(0); }

        /* =====================================================================
           ★ COSMIC LIVE VOICE CALL (ĐÀM THOẠI TRỰC TIẾP CÙNG KERIA AI) ★
           ===================================================================== */
        .keria-voice-call-btn {
            position: relative;
            z-index: 10 !important;
            margin-top: 7px;
            padding: 5px 12px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.4) 0%, rgba(168, 85, 247, 0.45) 100%);
            border: 1px solid rgba(56, 189, 248, 0.7);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            box-shadow: 0 0 16px rgba(56, 189, 248, 0.4), 0 2px 8px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
        }
        .keria-voice-call-btn:hover {
            background: linear-gradient(135deg, #0284c7 0%, #9333ea 100%);
            border-color: #38bdf8;
            transform: scale(1.06);
            box-shadow: 0 0 22px rgba(56, 189, 248, 0.75), 0 4px 14px rgba(0, 0, 0, 0.6);
        }
        .keria-voice-call-btn .live-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 8px #10b981;
            animation: liveDotPulse 1.4s ease-in-out infinite;
        }
        @keyframes liveDotPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
        }

        .voice-live-deck-btn {
            color: #38bdf8 !important;
            background: rgba(56, 189, 248, 0.15) !important;
            border: 1px solid rgba(56, 189, 248, 0.4) !important;
        }
        .voice-live-deck-btn:hover {
            background: linear-gradient(135deg, #0284c7, #38bdf8) !important;
            color: #ffffff !important;
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.6) !important;
        }

        .msg-speak-btn {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #94a3b8;
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
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.15);
            border-color: rgba(56, 189, 248, 0.4);
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
            background: rgba(4, 2, 16, 0.82);
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
            background: radial-gradient(circle at 50% 20%, rgba(30, 15, 75, 0.96) 0%, rgba(10, 4, 30, 0.98) 100%);
            border: 1.5px solid rgba(168, 85, 247, 0.5);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8), 0 0 45px rgba(124, 58, 237, 0.35), inset 0 1px 2px rgba(255, 255, 255, 0.15);
            padding: 24px 22px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #ffffff;
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
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #94a3b8;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 5;
        }
        .voice-modal-close-btn:hover {
            background: rgba(239, 68, 68, 0.25);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.5);
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
            background: rgba(12, 6, 32, 0.85);
            border: 1px solid rgba(168, 85, 247, 0.4);
            border-radius: 20px;
            padding: 2px 3px;
            gap: 2px;
        }
        .vpersona-tab-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
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
        .vpersona-tab-btn:hover {
            color: #ffffff;
        }
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
            color: #94a3b8;
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
        .vgender-tab-btn:hover {
            color: #ffffff;
        }
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
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.12);
            padding: 4px 10px;
            border-radius: 12px;
            border: 1px solid rgba(56, 189, 248, 0.35);
        }
        .voice-live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
            box-shadow: 0 0 10px #ef4444;
            animation: liveDotPulse 1.2s infinite;
        }
        .voice-model-tag {
            font-size: 11px;
            color: #a78bfa;
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
            border: 3px solid var(--vt-cyan);
            box-shadow: 0 0 25px rgba(56, 189, 248, 0.8), 0 0 40px rgba(168, 85, 247, 0.5);
            overflow: hidden;
            position: relative;
            z-index: 3;
            background: #090320;
            transition: all 0.3s;
        }
        .voice-mascot-avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .voice-state-speaking .voice-mascot-avatar-wrap {
            border-color: #f472b6;
            box-shadow: 0 0 35px rgba(244, 114, 182, 0.9), 0 0 50px rgba(168, 85, 247, 0.7);
            animation: mascotSpeakingBob 1.2s ease-in-out infinite alternate;
        }
        .voice-state-listening .voice-mascot-avatar-wrap {
            border-color: #38bdf8;
            box-shadow: 0 0 30px rgba(56, 189, 248, 0.85);
            animation: mascotListeningPulse 1.6s ease-in-out infinite;
        }
        .voice-state-thinking .voice-mascot-avatar-wrap {
            border-color: #c084fc;
            box-shadow: 0 0 30px rgba(192, 132, 252, 0.85);
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
            background: #38bdf8;
            border-radius: 4px;
            transition: height 0.15s ease, background 0.3s;
        }
        .voice-state-speaking .vbar {
            background: linear-gradient(180deg, #f472b6, #a855f7);
            animation: barBounce 0.8s ease-in-out infinite alternate;
        }
        .voice-state-listening .vbar {
            background: linear-gradient(180deg, #38bdf8, #10b981);
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
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #e2e8f0;
            transition: all 0.3s;
        }
        .voice-state-listening .voice-state-pill {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.5);
            color: #34d399;
        }
        .voice-state-speaking .voice-state-pill {
            background: rgba(244, 114, 182, 0.15);
            border-color: rgba(244, 114, 182, 0.5);
            color: #f472b6;
        }
        .voice-state-thinking .voice-state-pill {
            background: rgba(192, 132, 252, 0.15);
            border-color: rgba(192, 132, 252, 0.5);
            color: #c084fc;
        }

        .voice-transcript-box {
            width: 100%;
            max-height: 160px;
            min-height: 95px;
            overflow-y: auto;
            background: rgba(7, 3, 22, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            background: rgba(56, 189, 248, 0.12);
            border-left: 3px solid #38bdf8;
            padding: 6px 10px;
            border-radius: 8px;
            color: #e0f2fe;
        }
        .voice-bubble-bot {
            background: rgba(168, 85, 247, 0.12);
            border-left: 3px solid #c084fc;
            padding: 6px 10px;
            border-radius: 8px;
            color: #f1f5f9;
        }
        .vspeaker-label {
            font-size: 10.5px;
            font-weight: 700;
            color: #94a3b8;
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
            background: rgba(13, 6, 36, 0.85);
            border: 1px solid rgba(168, 85, 247, 0.4);
            border-radius: 12px;
            padding: 8px 12px;
            color: #ffffff;
            font-size: 12px;
            outline: none;
            transition: border-color 0.2s;
        }
        .voice-quick-input-row input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.3);
        }
        .voice-quick-send-btn {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
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
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            transition: all 0.2s;
        }
        .voice-ctl-btn:hover {
            background: rgba(255, 255, 255, 0.18);
            transform: scale(1.04);
        }
        .voice-ctl-interrupt {
            background: rgba(234, 179, 8, 0.18);
            border-color: rgba(234, 179, 8, 0.5);
            color: #fde047;
        }
        .voice-ctl-interrupt:hover {
            background: rgba(234, 179, 8, 0.3);
            border-color: #fde047;
        }
        .voice-ctl-end {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            border-color: #ef4444;
            color: #ffffff;
            box-shadow: 0 0 14px rgba(239, 68, 68, 0.4);
        }
        .voice-ctl-end:hover {
            background: linear-gradient(135deg, #b91c1c, #dc2626);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.7);
        }

        /* =====================================================================
           ★ ANIME GAME COMPANION STAGE (TƯƠNG TÁC NHÂN VẬT NHƯ GAME) ★
           ===================================================================== */
        .keria-card-actions-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            width: 100%;
            margin-top: 4px;
            position: relative;
            z-index: 10 !important;
        }
        .keria-card-actions-row .keria-voice-call-btn {
            margin-top: 0 !important;
            padding: 4px 8px;
            font-size: 10px;
            flex: 1;
            justify-content: center;
        }
        .keria-game-stage-btn {
            padding: 4px 8px;
            border-radius: 20px;
            background: linear-gradient(135deg, #d946ef 0%, #ec4899 50%, #f43f5e 100%);
            border: 1px solid rgba(244, 63, 94, 0.8);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            cursor: pointer;
            box-shadow: 0 0 12px rgba(244, 63, 94, 0.5), 0 2px 6px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(6px);
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
            flex: 1;
            white-space: nowrap;
        }
        .keria-game-stage-btn:hover {
            transform: scale(1.05) translateY(-1px);
            box-shadow: 0 0 20px rgba(244, 63, 94, 0.8), 0 4px 12px rgba(0, 0, 0, 0.6);
            border-color: #fda4af;
        }
        .keria-game-stage-btn i {
            font-size: 10.5px;
            color: #ffffff;
        }

        .btn-game-mode-nav {
            padding: 5px 12px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(217, 70, 239, 0.25) 0%, rgba(244, 63, 94, 0.35) 100%);
            border: 1px solid rgba(244, 63, 94, 0.55);
            color: #fbcfe8;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-game-mode-nav:hover {
            background: linear-gradient(135deg, #d946ef 0%, #f43f5e 100%);
            color: #ffffff;
            transform: scale(1.05);
            box-shadow: 0 0 16px rgba(244, 63, 94, 0.6);
        }

        /* Fullscreen Game Companion Modal */
        .game-companion-modal {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 14px;
            animation: gameFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes gameFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .game-companion-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(3, 1, 12, 0.9);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }
        .game-companion-window {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 960px;
            height: 94vh;
            max-height: 860px;
            background: radial-gradient(circle at 50% 30%, rgba(28, 14, 68, 0.95) 0%, rgba(10, 4, 26, 0.98) 70%, #050212 100%);
            border: 1.5px solid rgba(217, 70, 239, 0.55);
            border-radius: 22px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.85), 0 0 50px rgba(217, 70, 239, 0.35);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: gamePopIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes gamePopIn {
            from { transform: scale(0.92) translateY(20px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }

        /* Top Bar */
        .game-top-bar {
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(217, 70, 239, 0.25);
            background: rgba(14, 6, 36, 0.75);
            backdrop-filter: blur(8px);
            z-index: 20;
            flex-shrink: 0;
        }
        .game-brand-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-heading);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #f472b6;
            text-shadow: 0 0 10px rgba(244, 114, 182, 0.5);
        }
        .game-live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 8px #10b981;
            animation: liveDotPulse 1.4s infinite;
        }
        .game-stats-bar {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .game-stat-item {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 5px;
            color: #e2e8f0;
        }
        .game-stat-val {
            font-weight: 700;
            color: #38bdf8;
        }
        .game-top-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .game-top-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all 0.2s;
        }
        .game-top-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }
        .game-top-btn.close:hover {
            background: #ef4444;
            border-color: #ef4444;
        }

        /* Central Stage Viewport */
        .game-stage-viewport {
            flex: 1;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding-bottom: 16px;
            min-height: 0;
        }
        #gameParticlesCanvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        /* 3D Sci-Fi Hologram Floor Grid */
        .holo-floor-grid {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 240px;
            background-image: 
                linear-gradient(rgba(147, 51, 234, 0.22) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56, 189, 248, 0.18) 1px, transparent 1px);
            background-size: 36px 36px;
            transform: perspective(350px) rotateX(65deg);
            transform-origin: bottom center;
            mask-image: linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 100%);
            -webkit-mask-image: linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 100%);
            pointer-events: none;
            z-index: 2;
        }

        /* 3D Hologram Emitter Pedestal (Bệ phóng năng lượng 5D) */
        .holo-emitter-pedestal {
            position: absolute;
            bottom: 38px;
            width: 380px;
            height: 120px;
            pointer-events: none;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            transform-style: preserve-3d;
            perspective: 600px;
        }
        .holo-ring-outer {
            position: absolute;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            border: 2px dashed rgba(56, 189, 248, 0.65);
            box-shadow: 0 0 25px rgba(56, 189, 248, 0.4), inset 0 0 20px rgba(168, 85, 247, 0.4);
            transform: rotateX(72deg);
            animation: rotateHoloRing 16s linear infinite;
        }
        .holo-ring-inner {
            position: absolute;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            border: 2px solid rgba(244, 114, 182, 0.7);
            box-shadow: 0 0 20px rgba(244, 114, 182, 0.5);
            transform: rotateX(72deg);
            animation: rotateHoloRingRev 10s linear infinite;
        }
        .holo-glow-core {
            position: absolute;
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.8) 0%, rgba(168, 85, 247, 0.4) 60%, transparent 80%);
            transform: rotateX(72deg);
            filter: blur(8px);
            animation: pulseHoloCore 2.5s ease-in-out infinite alternate;
        }
        .holo-light-pillar {
            position: absolute;
            bottom: 50px;
            width: 240px;
            height: 350px;
            background: linear-gradient(to top, rgba(56, 189, 248, 0.22) 0%, rgba(168, 85, 247, 0.12) 50%, transparent 100%);
            clip-path: polygon(15% 100%, 85% 100%, 70% 0%, 30% 0%);
            filter: blur(12px);
            animation: pulsePillar 3s ease-in-out infinite alternate;
        }
        @keyframes rotateHoloRing {
            from { transform: rotateX(72deg) rotateZ(0deg); }
            to { transform: rotateX(72deg) rotateZ(360deg); }
        }
        @keyframes rotateHoloRingRev {
            from { transform: rotateX(72deg) rotateZ(360deg); }
            to { transform: rotateX(72deg) rotateZ(0deg); }
        }
        @keyframes pulseHoloCore {
            0% { transform: rotateX(72deg) scale(0.9); opacity: 0.7; }
            100% { transform: rotateX(72deg) scale(1.15); opacity: 1; }
        }
        @keyframes pulsePillar {
            0% { opacity: 0.4; }
            100% { opacity: 0.8; }
        }

        /* Character Wrapper & 5D Interactive Perspective (Tách nền đứng tự do) */
        .game-character-wrapper {
            position: absolute;
            bottom: 35px;
            width: 440px;
            max-width: 90%;
            height: 86%;
            max-height: 640px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            z-index: 5;
            transition: transform 0.12s cubic-bezier(0.16, 1, 0.3, 1);
            transform-style: preserve-3d;
            perspective: 1200px;
            user-select: none;
        }
        .game-char-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: bottom center;
            filter: drop-shadow(0 10px 24px rgba(0, 0, 0, 0.65)) 
                    drop-shadow(0 0 24px rgba(56, 189, 248, 0.45)) 
                    drop-shadow(0 0 45px rgba(168, 85, 247, 0.35));
            pointer-events: none;
            transition: opacity 0.2s ease, filter 0.3s ease;
            animation: holoBreathing5D 4.2s ease-in-out infinite;
        }
        @keyframes holoBreathing5D {
            0%, 100% { transform: translateY(0) scale(1) scaleY(1); }
            50% { transform: translateY(-8px) scale(1.012) scaleY(1.018); }
        }
        /* 5D Lip Sync & Voice Talking pulse */
        .game-char-img.speaking-lipsync {
            animation: holoTalking5D 0.25s ease-in-out infinite alternate, holoBreathing5D 4.2s ease-in-out infinite !important;
        }
        @keyframes holoTalking5D {
            0% { 
                filter: drop-shadow(0 0 22px rgba(56, 189, 248, 0.7)) drop-shadow(0 0 40px rgba(244, 63, 94, 0.5)); 
                transform: translateY(-5px) scale(1.015); 
            }
            100% { 
                filter: drop-shadow(0 0 30px rgba(56, 189, 248, 0.9)) drop-shadow(0 0 55px rgba(168, 85, 247, 0.7)); 
                transform: translateY(-9px) scale(1.025); 
            }
        }
        /* 5D Hologram Modes */
        .game-char-img.mode-hologram {
            filter: drop-shadow(0 0 16px #38bdf8) drop-shadow(0 0 35px #a855f7) brightness(1.08) contrast(1.05);
        }
        .game-char-img.mode-nebula {
            filter: drop-shadow(0 0 22px #f43f5e) drop-shadow(0 0 45px #ec4899) brightness(1.05);
        }

        /* 5D Touchzones over Character */
        .char-touchzone {
            position: absolute;
            border-radius: 50%;
            cursor: pointer;
            z-index: 15;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .char-touchzone .touch-hint {
            position: absolute;
            background: rgba(14, 6, 36, 0.92);
            border: 1px solid rgba(56, 189, 248, 0.8);
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 12px;
            pointer-events: none;
            opacity: 0;
            transform: translateY(6px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            white-space: nowrap;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.7), 0 0 12px rgba(56, 189, 248, 0.4);
            backdrop-filter: blur(6px);
        }
        .char-touchzone:hover .touch-hint {
            opacity: 1;
            transform: translateY(-24px);
        }
        .char-touchzone:hover {
            background: radial-gradient(circle, rgba(56, 189, 248, 0.28) 0%, transparent 70%);
        }
        /* 1. Head (Xoa đầu tóc vàng Ani) */
        .touch-head {
            top: 4%;
            left: 32%;
            width: 150px;
            height: 140px;
        }
        /* 2. Choker (Vòng cổ Gothic) */
        .touch-choker {
            top: 22%;
            left: 38%;
            width: 110px;
            height: 70px;
        }
        /* 3. Ribbon (Nơ ngực ren) */
        .touch-ribbon {
            top: 30%;
            left: 38%;
            width: 110px;
            height: 80px;
        }
        /* 4. Dress (Váy xòe Gothic Lolita) */
        .touch-dress {
            top: 42%;
            left: 23%;
            width: 240px;
            height: 160px;
        }
        /* 5. Boots (Giày búp bê & tất ren) */
        .touch-boots {
            bottom: 4%;
            left: 37%;
            width: 140px;
            height: 150px;
        }

        /* Skin switcher in 5D Top Bar */
        .game-skin-switcher-pill {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 14px;
            padding: 2px;
            gap: 2px;
        }
        .gskin-btn {
            background: transparent;
            border: none;
            color: #cbd5e1;
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .gskin-btn.active {
            background: linear-gradient(135deg, #d946ef, #f43f5e);
            color: #ffffff;
            box-shadow: 0 0 10px rgba(244, 63, 94, 0.5);
        }

        /* Heart / Reaction Floating Particles */
        .game-reaction-container {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 25;
            overflow: hidden;
        }
        .game-floating-heart {
            position: absolute;
            font-size: 24px;
            animation: floatHeartUp 1.2s ease-out forwards;
            pointer-events: none;
            filter: drop-shadow(0 0 8px rgba(244, 63, 94, 0.8));
        }
        @keyframes floatHeartUp {
            0% { transform: scale(0.5) translateY(0); opacity: 1; }
            50% { transform: scale(1.3) translateY(-60px) rotate(15deg); opacity: 0.9; }
            100% { transform: scale(1.6) translateY(-120px) rotate(-20deg); opacity: 0; }
        }

        /* RPG Dialogue Box (Visual Novel Style) */
        .game-dialogue-card {
            position: relative;
            z-index: 20;
            width: calc(100% - 36px);
            max-width: 800px;
            background: linear-gradient(180deg, rgba(20, 10, 48, 0.94) 0%, rgba(10, 4, 28, 0.98) 100%);
            border: 1.5px solid rgba(168, 85, 247, 0.45);
            border-radius: 18px;
            padding: 12px 16px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.8), 0 0 30px rgba(147, 51, 234, 0.25);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            animation: dialogueSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes dialogueSlideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .game-dialogue-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .dialogue-name-tag {
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .dname-avatar img {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 1.5px solid #38bdf8;
            object-fit: cover;
        }
        .dname-text {
            font-family: var(--font-heading);
            font-size: 13px;
            font-weight: 800;
            background: linear-gradient(135deg, #38bdf8, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 0.04em;
        }
        .dname-badge {
            background: rgba(244, 63, 94, 0.2);
            border: 1px solid rgba(244, 63, 94, 0.6);
            color: #fbcfe8;
            font-size: 9.5px;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 10px;
        }

        /* Dialogue text */
        .game-dialogue-body {
            min-height: 42px;
            margin-bottom: 8px;
        }
        .dialogue-text {
            font-size: 13px;
            line-height: 1.55;
            color: #f1f5f9;
            margin: 0;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
        }

        /* Quick Choice Chips */
        .game-choice-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 8px;
        }
        .gchoice-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(168, 85, 247, 0.35);
            color: #e0f2fe;
            font-size: 10.5px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
        }
        .gchoice-btn:hover {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #ffffff;
            border-color: #38bdf8;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(56, 189, 248, 0.4);
        }

        /* Input deck */
        .game-input-deck {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(8, 3, 20, 0.7);
            border: 1px solid rgba(168, 85, 247, 0.35);
            border-radius: 20px;
            padding: 3px 5px;
        }
        .ginput-mic-btn {
            background: rgba(56, 189, 248, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.5);
            color: #38bdf8;
            padding: 4px 10px;
            border-radius: 14px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .ginput-mic-btn:hover, .ginput-mic-btn.active {
            background: linear-gradient(135deg, #ef4444, #f43f5e);
            color: #ffffff;
            border-color: #f43f5e;
            box-shadow: 0 0 12px rgba(244, 63, 94, 0.6);
        }
        #gameChatInput {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: #ffffff;
            font-size: 12px;
            padding: 3px 6px;
        }
        #gameChatInput::placeholder {
            color: #64748b;
        }
        .ginput-send-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d946ef, #f43f5e);
            border: none;
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            transition: transform 0.2s;
        }
        .ginput-send-btn:hover {
            transform: scale(1.1);
        }

        /* Side Game Action Dock */
        .game-action-dock {
            position: absolute;
            right: 14px;
            top: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            z-index: 25;
        }
        .gaction-btn {
            background: rgba(14, 6, 36, 0.85);
            border: 1px solid rgba(217, 70, 239, 0.45);
            color: #fbcfe8;
            padding: 6px 11px;
            border-radius: 14px;
            font-size: 10.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            backdrop-filter: blur(8px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.5);
            user-select: none;
        }
        .gaction-btn:hover {
            background: linear-gradient(135deg, #d946ef, #f43f5e);
            color: #ffffff;
            border-color: #f43f5e;
            transform: translateX(-3px) scale(1.04);
            box-shadow: 0 4px 14px rgba(244, 63, 94, 0.6);
        }
    </style>
</head>
<body class="admin-portal">

    <!-- Include Navigation & Layout Admin -->
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <!-- Fixed Toast -->
    <div class="vt-toast" id="vtToast"></div>

    <!-- =========================================================================
         ★ ANIME GAME COMPANION STAGE MODAL (TƯƠNG TÁC NHÂN VẬT NHƯ GAME) ★
         ========================================================================= -->
    <div id="gameCompanionModal" class="game-companion-modal" style="display: none;">
        <div class="game-companion-backdrop" onclick="closeGameCompanionStage()"></div>

        <div class="game-companion-window">
            <!-- Window Top Bar -->
            <div class="game-top-bar">
                <div class="game-brand-pill">
                    <span class="game-live-dot"></span>
                    <i class="fa-solid fa-wand-magic-sparkles" style="color:#fbbf24;"></i>
                    <span id="gameCompanionBrandTitle">ANI ★ GROK AI COMPANION (xAI)</span>
                </div>

                <div class="game-stats-bar">
                    <div class="game-stat-item" title="Độ thân thiết cùng Keria">
                        <i class="fa-solid fa-heart" style="color:#f43f5e;"></i>
                        <span class="game-stat-lbl">Thân thiết:</span>
                        <span class="game-stat-val" id="gameAffectionVal">Lv.100 MAX</span>
                    </div>
                    <div class="game-stat-item" title="Tâm trạng hiện tại">
                        <i class="fa-solid fa-face-smile-beam" style="color:#fbbf24;"></i>
                        <span class="game-stat-lbl">Tâm trạng:</span>
                        <span class="game-stat-val" id="gameMoodVal">Ngọt ngào 🖤</span>
                    </div>
                    <div class="game-stat-item" style="cursor:pointer;" onclick="cycleGame5DMode()" title="Nhấp để chuyển chế độ hiển thị 5D">
                        <i class="fa-solid fa-cube" style="color:#38bdf8;"></i>
                        <span class="game-stat-lbl">Chế độ:</span>
                        <span class="game-stat-val" id="game5DModeVal">✨ 5D HD</span>
                    </div>

                    <!-- Skin Switcher Pill: Ani (Grok) / Hikari (Cosmic) -->
                    <div class="game-skin-switcher-pill" title="Chuyển đổi nhân vật đồng hành">
                        <button type="button" class="gskin-btn active" id="gSkinAniBtn" onclick="switchGameCompanionChar('ani')" title="Ani từ Grok (xAI) - Gothic Lolita">
                            <span>🖤 Ani (Grok)</span>
                        </button>
                        <button type="button" class="gskin-btn" id="gSkinHikariBtn" onclick="switchGameCompanionChar('hikari')" title="Hikari - Thần tượng Không gian">
                            <span>✨ Hikari</span>
                        </button>
                    </div>
                </div>

                <div class="game-top-actions">
                    <button type="button" class="game-top-btn" onclick="toggleGameHandsFreeCall()" id="gameHandsFreeBtn" title="Bật/Tắt đàm thoại rảnh tay liên tục (Grok Live Call)">
                        <i class="fa-solid fa-headset" id="gameHandsFreeIcon"></i>
                    </button>
                    <button type="button" class="game-top-btn" onclick="toggleGameSound()" id="gameSoundBtn" title="Bật/Tắt âm thanh giọng nói">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <button type="button" class="game-top-btn" onclick="toggleGameFullscreen()" title="Phóng to / Thu nhỏ">
                        <i class="fa-solid fa-expand" id="gameExpandIcon"></i>
                    </button>
                    <button type="button" class="game-top-btn close" onclick="closeGameCompanionStage()" title="Đóng phòng tương tác">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- Central Interactive Stage Area -->
            <div class="game-stage-viewport" id="gameStageViewport">
                <!-- Ambient Cosmic Particles Canvas -->
                <canvas id="gameParticlesCanvas"></canvas>

                <!-- 3D Sci-Fi Hologram Floor Grid -->
                <div class="holo-floor-grid"></div>

                <!-- 3D Hologram Emitter Pedestal under feet -->
                <div class="holo-emitter-pedestal" id="holoPedestal">
                    <div class="holo-light-pillar"></div>
                    <div class="holo-glow-core"></div>
                    <div class="holo-ring-outer"></div>
                    <div class="holo-ring-inner"></div>
                </div>

                <!-- Floating Hearts / Reaction Container -->
                <div class="game-reaction-container" id="gameReactionContainer"></div>

                <!-- Interactive 5D Character Container with Multi-axis Parallax & Touchpoints -->
                <div class="game-character-wrapper" id="gameCharWrapper">
                    <!-- Character Transparent Cutout (Default: Ani Grok) -->
                    <img src="/tkb/assets/ai/vutru_ani_grok_idle.webp" 
                         data-src-idle="/tkb/assets/ai/vutru_ani_grok_idle.webp" 
                         data-src-happy="/tkb/assets/ai/vutru_ani_grok_happy.webp"
                         onerror="this.src='/tkb/assets/ai/vutru_ani_grok_idle.png'"
                         alt="Ani Grok AI Companion" 
                         id="gameCharImg" 
                         class="game-char-img"
                         draggable="false">

                    <!-- Interactive Touch Zones on Ani -->
                    <!-- 1. Head Touchzone (Xoa đầu / Blonde Twintails) -->
                    <div class="char-touchzone touch-head" onclick="triggerCharTouch('head', event)" title="✨ Xoa đầu Ani (Click chuột)">
                        <span class="touch-hint"><i class="fa-solid fa-hand"></i> Xoa đầu</span>
                    </div>

                    <!-- 2. Choker Touchzone (Vòng cổ Gothic) -->
                    <div class="char-touchzone touch-choker" onclick="triggerCharTouch('choker', event)" title="🖤 Chạm vòng choker">
                        <span class="touch-hint"><i class="fa-solid fa-ring"></i> Choker</span>
                    </div>

                    <!-- 3. Ribbon Touchzone (Nơ ngực Gothic) -->
                    <div class="char-touchzone touch-ribbon" onclick="triggerCharTouch('ribbon', event)" title="🎀 Nơ ren Gothic">
                        <span class="touch-hint"><i class="fa-solid fa-ribbon"></i> Nơ ren</span>
                    </div>

                    <!-- 4. Dress Touchzone (Váy Gothic Lolita) -->
                    <div class="char-touchzone touch-dress" onclick="triggerCharTouch('dress', event)" title="👗 Váy Gothic Lolita">
                        <span class="touch-hint"><i class="fa-solid fa-vest-patches"></i> Váy Gothic</span>
                    </div>

                    <!-- 5. Boots Touchzone (Tất ren & Giày búp bê) -->
                    <div class="char-touchzone touch-boots" onclick="triggerCharTouch('boots', event)" title="💃 Nhảy múa cùng Ani">
                        <span class="touch-hint"><i class="fa-solid fa-shoe-prints"></i> Nhảy múa</span>
                    </div>
                </div>

                <!-- RPG Dialogue Box (Khung thoại Visual Novel / Game) -->
                <div class="game-dialogue-card" id="gameDialogueCard">
                    <div class="game-dialogue-header">
                        <div class="dialogue-name-tag">
                            <span class="dname-avatar"><img id="dnameAvatarImg" src="/tkb/assets/ai/vutru_ani_grok_circle.webp" alt="Ani"></span>
                            <span class="dname-text" id="dnameText">ANI ★ GROK COMPANION (xAI)</span>
                            <span class="dname-badge" id="dnameBadge">AI Companion</span>
                        </div>
                        <div class="voice-audio-bars" id="gameDialogueWave" style="height:14px; gap:2px; display:none;">
                            <span class="vbar" style="width:2px;"></span>
                            <span class="vbar" style="width:2px;"></span>
                            <span class="vbar" style="width:2px;"></span>
                            <span class="vbar" style="width:2px;"></span>
                        </div>
                    </div>

                    <div class="game-dialogue-body">
                        <p id="gameDialogueText" class="dialogue-text">"Chào Keria~ Ani của Grok đây! Em đã sẵn sàng trò chuyện cùng Keria rồi nè! ✨ Hãy xoa đầu, ngắm váy Gothic hoặc bật mic lên đàm thoại cùng em nhé! (◕‿-)🖤"</p>
                    </div>

                    <!-- Quick Choices / Prompts (Lựa chọn hội thoại chuẩn Grok) -->
                    <div class="game-choice-chips" id="gameChoiceChips">
                        <button type="button" class="gchoice-btn" onclick="triggerGameChoice('intro')">
                            <span>🖤 Giới thiệu Ani</span>
                        </button>
                        <button type="button" class="gchoice-btn" onclick="triggerGameChoice('praise')">
                            <span>💖 Khen Ani dễ thương</span>
                        </button>
                        <button type="button" class="gchoice-btn" onclick="triggerGameChoice('dress')">
                            <span>👗 Khen váy Gothic</span>
                        </button>
                        <button type="button" class="gchoice-btn" onclick="triggerGameChoice('schedule')">
                            <span>📅 Lịch học & thời khóa biểu</span>
                        </button>
                        <button type="button" class="gchoice-btn" onclick="triggerGameChoice('grok')">
                            <span>🔮 Kể chuyện Grok & xAI</span>
                        </button>
                    </div>

                    <!-- Live Input Bar inside Game (Gõ hoặc Nói trực tiếp) -->
                    <div class="game-input-deck">
                        <button type="button" class="ginput-mic-btn" id="gameMicBtn" onclick="toggleGameVoiceMic()" title="Nói chuyện bằng giọng nói">
                            <i class="fa-solid fa-microphone"></i>
                            <span id="gameMicLbl">Nói</span>
                        </button>
                        <input type="text" id="gameChatInput" placeholder="Nhập câu hỏi hoặc tâm sự cùng Ani rồi nhấn Enter..." onkeydown="if(event.key==='Enter') sendGameChatInput()">
                        <button type="button" class="ginput-send-btn" onclick="sendGameChatInput()" title="Gửi lời nhắn">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </div>

                <!-- Game Actions Dock on Right (Thanh hành động thao tác 5D) -->
                <div class="game-action-dock">
                    <button type="button" class="gaction-btn" onclick="triggerCharTouch('head', event)" title="Xoa đầu Ani">
                        <i class="fa-solid fa-hand-holding-heart" style="color:#f43f5e;"></i>
                        <span>Xoa đầu</span>
                    </button>
                    <button type="button" class="gaction-btn" onclick="triggerGamePoseToggle()" id="gPoseToggleBtn" title="Đổi biểu cảm nháy mắt">
                        <i class="fa-solid fa-wand-magic-sparkles" style="color:#fbbf24;"></i>
                        <span>Nháy mắt</span>
                    </button>
                    <button type="button" class="gaction-btn" onclick="triggerCharTouch('dress', event)" title="Ani xoay váy Gothic Lolita">
                        <i class="fa-solid fa-vest-patches" style="color:#c084fc;"></i>
                        <span>Xoay váy</span>
                    </button>
                    <button type="button" class="gaction-btn" onclick="toggleGameHandsFreeCall()" id="gDockHandsFreeBtn" title="Gọi rảnh tay liên tục như Grok">
                        <i class="fa-solid fa-headset" style="color:#38bdf8;"></i>
                        <span>Gọi rảnh tay</span>
                    </button>
                    <button type="button" class="gaction-btn" onclick="triggerGameAction('dance')" title="Ani nhảy múa ăn mừng">
                        <i class="fa-solid fa-music" style="color:#10b981;"></i>
                        <span>Nhảy múa</span>
                    </button>
                    <button type="button" class="gaction-btn" onclick="cycleGame5DMode()" title="Đổi hiệu ứng 5D Hologram">
                        <i class="fa-solid fa-cube" style="color:#d946ef;"></i>
                        <span>Chế độ 5D</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         ★ COSMIC LIVE VOICE CALL MODAL (ĐÀM THOẠI TRỰC TIẾP CÙNG KERIA AI) ★
         ========================================================================= -->
    <div id="cosmicVoiceModal" class="cosmic-voice-modal-wrap" style="display: none;">
        <div class="cosmic-voice-backdrop" onclick="closeCosmicVoiceCall()"></div>
        <div class="cosmic-voice-card">
            <!-- Close button -->
            <button type="button" class="voice-modal-close-btn" onclick="closeCosmicVoiceCall()" title="Đóng cuộc đàm thoại">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <!-- Top Header Status -->
            <div class="voice-call-top-bar">
                <div class="voice-live-badge">
                    <span class="voice-live-dot"></span>
                    <span id="voiceCallLiveTitle">VŨ TRỤ AI LIVE VOICE</span>
                </div>

                <!-- Mascot Switcher Pill: Robot / Anime -->
                <div class="voice-persona-pill-wrap" title="Chọn hình tượng Trợ lý Vũ Trụ AI">
                    <button type="button" class="vpersona-tab-btn active" id="vPersonaRobotBtn" onclick="setAssistantPersona('robot')" title="Hình tượng Robot Vũ Trụ">
                        <i class="fa-solid fa-robot"></i> <span>Robot</span>
                    </button>
                    <button type="button" class="vpersona-tab-btn" id="vPersonaAnimeBtn" onclick="setAssistantPersona('anime')" title="Hình tượng Anime Vũ Trụ (Hikari)">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> <span>Anime</span>
                    </button>
                </div>

                <!-- Gender Switcher Pill: Nữ / Nam -->
                <div class="voice-gender-pill-wrap" title="Chọn giọng đọc Vũ Trụ AI">
                    <button type="button" class="vgender-tab-btn active" id="vGenderFemaleBtn" onclick="setVoiceGender('female')" title="Giọng Nữ (Vũ Trụ AI - Tươi sáng, truyền cảm)">
                        <i class="fa-solid fa-venus"></i> <span>Giọng Nữ</span>
                    </button>
                    <button type="button" class="vgender-tab-btn" id="vGenderMaleBtn" onclick="setVoiceGender('male')" title="Giọng Nam (Vũ Trụ AI - Trầm ấm, chững chạc)">
                        <i class="fa-solid fa-mars"></i> <span>Giọng Nam</span>
                    </button>
                </div>

                <div class="voice-model-tag" id="voiceCallModelTag">Vũ Trụ AI Studio</div>
            </div>

            <!-- Central Mascot & Visualizer Orb -->
            <div class="voice-central-stage">
                <div class="voice-orb-container" id="voiceOrbContainer">
                    <div class="voice-ripple-ring ring-1"></div>
                    <div class="voice-ripple-ring ring-2"></div>
                    <div class="voice-ripple-ring ring-3"></div>
                    <div class="voice-mascot-avatar-wrap">
                        <img src="/tkb/assets/ai/vutru_keria_assistant.png" alt="Trợ lý Vũ Trụ AI" id="voiceCallMascotImg" onerror="this.src='../assets/ai/vutru_keria_assistant.png'">
                    </div>
                </div>

                <!-- Audio Waveform Visualizer (7 animated bouncing bars) -->
                <div class="voice-audio-bars" id="voiceAudioBars">
                    <span class="vbar bar-1"></span>
                    <span class="vbar bar-2"></span>
                    <span class="vbar bar-3"></span>
                    <span class="vbar bar-4"></span>
                    <span class="vbar bar-5"></span>
                    <span class="vbar bar-6"></span>
                    <span class="vbar bar-7"></span>
                </div>

                <!-- State Pill -->
                <div class="voice-state-pill" id="voiceStatePill">
                    <i class="fa-solid fa-microphone"></i>
                    <span id="voiceStateText">Đang lắng nghe Keria nói...</span>
                </div>
            </div>

            <!-- Live Dialogue & Transcript Stream -->
            <div class="voice-transcript-box" id="voiceTranscriptBox">
                <div class="voice-bubble-user" id="voiceUserBubble" style="display: none;">
                    <span class="vspeaker-label"><i class="fa-solid fa-user"></i> Keria:</span>
                    <p id="voiceUserText">...</p>
                </div>
                <div class="voice-bubble-bot" id="voiceBotBubble">
                    <span class="vspeaker-label"><i class="fa-solid fa-robot"></i> Vũ Trụ AI:</span>
                    <p id="voiceBotText">"Xin chào Keria! Tôi là Vũ Trụ AI, trợ lý của bạn. Hãy nói điều gì đó, tôi đang lắng nghe bạn đây!"</p>
                </div>
            </div>

            <!-- Quick Manual Input in case user wants to type while in call -->
            <div class="voice-quick-input-row">
                <input type="text" id="voiceQuickInput" placeholder="Hoặc gõ tin nhắn gửi đến Vũ Trụ AI rồi nhấn Enter..." onkeydown="if(event.key==='Enter') sendVoiceQuickInput()">
                <button type="button" class="voice-quick-send-btn" onclick="sendVoiceQuickInput()" title="Gửi">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>

            <!-- Controls Footer -->
            <div class="voice-controls-footer">
                <button type="button" class="voice-ctl-btn" id="voiceMicToggleBtn" onclick="toggleVoiceCallMic()" title="Bật / Tắt Microphone">
                    <i class="fa-solid fa-microphone"></i>
                    <span id="voiceMicToggleLbl">Đang nghe</span>
                </button>
                <button type="button" class="voice-ctl-btn voice-ctl-interrupt" id="voiceInterruptBtn" onclick="interruptKeriaSpeech()" title="Ngắt lời Vũ Trụ AI khi đang nói">
                    <i class="fa-solid fa-hand"></i>
                    <span>Ngắt lời</span>
                </button>
                <button type="button" class="voice-ctl-btn voice-ctl-end" onclick="closeCosmicVoiceCall()" title="Kết thúc cuộc trò chuyện">
                    <i class="fa-solid fa-phone-slash"></i>
                    <span>Kết thúc</span>
                </button>
            </div>
        </div>
    </div>

    <div class="main-content">
        <!-- Ambient Starfield Canvas -->
        <canvas id="vutruCanvas"></canvas>

        <div class="vutru-wrap">

            <!-- =================================================================
                 ★ 1. TOP HEADER ROW: [HI KERIA SQUARE BOX] + [VŨ TRỤ AI BANNER] ★
                 ================================================================= -->
            <div class="vt-top-header-row">
                <!-- LEFT: SQUARE GREETING CARD: HI KERIA WITH WAVING ROBOT -->
                <div class="vt-keria-square-card">
                    <!-- Outer Background Image (Ảnh ngoài) -->
                    <img id="keriaCardBgImg" class="keria-card-bg-layer" src="/tkb/assets/ai/vutru_card_bg.jpg" alt="Ảnh ngoài" onerror="this.src='../assets/ai/vutru_card_bg.jpg'">
                    <div class="keria-card-bg-overlay"></div>

                    <!-- Button to change Outer Image (Ảnh ngoài) -->
                    <button type="button" class="keria-bg-change-btn" onclick="triggerChangeKeriaCardBg(event)" title="Nhấp để thêm/thay đổi ảnh ngoài (Shift+Click để đặt lại mặc định)">
                        <i class="fa-solid fa-camera"></i> <span>Ảnh ngoài</span>
                    </button>

                    <div class="keria-robot-img-wrap" onclick="triggerChangeRobotAvatar(event)" title="Nhấp vào ảnh đại diện để thay đổi (Shift+Click để khôi phục Trợ lý AI Keria)">
                        <img id="keriaRobotImg" src="/tkb/assets/ai/vutru_keria_assistant.png" alt="Trợ lý AI Keria" onerror="this.src='../assets/ai/vutru_keria_assistant.png'">
                        <div class="robot-camera-badge" title="Đổi ảnh đại diện">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                        <div class="robot-change-hover-overlay">
                            <i class="fa-solid fa-camera"></i>
                            <span>Đổi ảnh</span>
                        </div>
                    </div>
                    <div class="keria-greeting-title">
                        HI KERIA <span class="wave-hand">👋</span>
                    </div>
                    <!-- Mascot Switcher Pill: Robot / Anime -->
                    <div class="keria-persona-toggle" title="Chọn hình tượng Trợ lý Vũ Trụ AI">
                        <button type="button" class="kpersona-btn active" id="kPersonaRobotBtn" onclick="setAssistantPersona('robot')" title="Trợ lý Robot Vũ Trụ">
                            <i class="fa-solid fa-robot"></i> <span>Robot</span>
                        </button>
                        <button type="button" class="kpersona-btn" id="kPersonaAnimeBtn" onclick="setAssistantPersona('anime')" title="Trợ lý Anime Vũ Trụ (Hikari)">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> <span>Anime</span>
                        </button>
                    </div>
                    <div class="keria-card-actions-row">
                        <button type="button" class="keria-voice-call-btn" onclick="openCosmicVoiceCall(event)" title="Trò chuyện thoại trực tiếp cùng Keria (như nói chuyện với AI ngoài đời)">
                            <i class="fa-solid fa-microphone-lines"></i>
                            <span>Nói chuyện</span>
                            <span class="live-pulse-dot"></span>
                        </button>
                        <button type="button" class="keria-game-stage-btn" onclick="openGameCompanionStage(event)" title="Mở phòng tương tác nhân vật Anime Game 3D (Thao tác chạm & trò chuyện như game)">
                            <i class="fa-solid fa-gamepad"></i>
                            <span>Phòng Game</span>
                        </button>
                    </div>
                </div>
                <!-- Hidden file input for changing Robot avatar (Ảnh trong) -->
                <input type="file" id="changeRobotAvatarInput" accept="image/png,image/jpeg,image/webp,image/gif" style="display:none;" onchange="handleChangeRobotAvatar(this)">

                <!-- Hidden file input for changing Outer Card Background (Ảnh ngoài) -->
                <input type="file" id="changeKeriaCardBgInput" accept="image/png,image/jpeg,image/webp,image/gif" style="display:none;" onchange="handleChangeKeriaCardBg(this)">

                <!-- RIGHT: HERO BANNER (CLICK TO CHANGE BANNER) -->
                <div class="vt-hero-banner" onclick="triggerChangeHeroBanner(event)" title="Nhấp vào ảnh để thay banner">
                    <img id="vtHeroBannerImg" src="/tkb/assets/ai/vutru_hero_banner.jpg" alt="VŨ TRỤ AI - Chat, Học, Sáng Tạo Không Giới Hạn" class="vt-hero-img">
                    <div class="hero-banner-change-btn" title="Thay ảnh bìa banner">
                        <i class="fa-solid fa-camera"></i> <span>Thay ảnh bìa</span>
                    </div>
                </div>
                <!-- Hidden file input for changing Hero banner -->
                <input type="file" id="changeHeroBannerInput" accept="image/png,image/jpeg,image/webp,image/gif" style="display:none;" onchange="handleChangeHeroBanner(this)">
            </div>


            <!-- =================================================================
                 ★ 2. MAIN CHAT TERMINAL ★
                 ================================================================= -->
            <div class="vt-main-grid">

                <div class="vt-card vt-chat-terminal">

                    <!-- ★ 1. LEFT COMPACT CHAT HISTORY SIDEBAR ★ -->
                    <aside class="vt-history-sidebar" id="vtHistoryDrawer">

                        <div class="history-side-header">
                            <div class="history-side-title">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span>Lịch sử chat</span>
                                <span class="history-count-badge" id="historyBadge">0</span>
                            </div>
                            <div class="history-side-tools">
                                <button type="button" class="side-icon-btn danger" onclick="clearAllStudioHistory()" title="Xoá tất cả lịch sử">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <button type="button" class="side-icon-btn" onclick="toggleStudioHistory(false)" title="Thu gọn lịch sử">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </button>
                            </div>
                        </div>

                        <!-- New Chat Button in Sidebar -->
                        <div class="history-side-btn-wrap">
                            <button type="button" class="btn-side-new-chat" onclick="startNewStudioChat()">
                                <i class="fa-solid fa-plus"></i>
                                <span>Đoạn chat mới</span>
                            </button>
                        </div>

                        <!-- Search Bar in Sidebar -->
                        <div class="history-side-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="historySearchInput" placeholder="Tìm kiếm đoạn chat..." oninput="filterHistoryList(this.value)">
                        </div>

                        <!-- Sessions List -->
                        <div class="history-side-list" id="vtHistoryList">
                            <!-- Rendered dynamically by JavaScript -->
                        </div>
                    </aside>

                    <!-- Mobile Backdrop -->
                    <div class="vt-history-backdrop" id="vtHistoryBackdrop" onclick="toggleStudioHistory(false)"></div>

                    <!-- ★ 2. RIGHT MAIN CHAT TERMINAL AREA ★ -->
                    <div class="vt-chat-main-area">

                        <!-- Header -->
                        <div class="chat-top-header">
                            <div class="chat-header-left">
                                <button type="button" class="btn-sidebar-toggle" id="btnToggleSidebar" onclick="toggleStudioHistory()" title="Ẩn/Hiện Lịch sử chat">
                                    <i class="fa-solid fa-bars-staggered"></i>
                                </button>
                                <div class="chat-robot-avatar">
                                    <img src="/tkb/assets/ai/vutru_robot_circle.png" alt="Vũ Trụ AI Robot">
                                </div>
                                <div class="chat-header-name">
                                    <h2>VŨ TRỤ AI ASSISTANT</h2>
                                    <div class="chat-online-badge">Galaxy Engine Online</div>
                                </div>
                            </div>

                            <!-- Header Right Controls -->
                            <div class="chat-header-right">
                                <!-- Antigravity Model Picker Trigger & Dropdown -->
                                <div class="vt-model-picker-container" id="vtModelPickerContainer">
                                    <button type="button" class="vt-antigravity-trigger" id="vtModelTrigger" onclick="toggleAgDropdown(event)" title="Chọn Mô Hình AI & Thinking Budget">
                                        <span class="ag-trig-lbl"><i class="fa-solid fa-microchip"></i> Model</span>
                                        <span class="ag-trig-name" id="agCurModelName">GLM 4.7 Flash Free</span>
                                        <span class="ag-trig-effort" id="agCurEffort">High</span>
                                        <span class="ag-trig-fast" id="agCurFast">Fast</span>
                                        <i class="fa-solid fa-chevron-down ag-trig-chevron"></i>
                                    </button>

                                    <!-- Antigravity Popup Dropdown -->
                                    <div class="vt-antigravity-dropdown" id="vtAntigravityDropdown" onclick="event.stopPropagation()">
                                        <div class="ag-dropdown-header">
                                            <span class="ag-hdr-title">Model</span>
                                            <div class="ag-hdr-search">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                                <input type="text" placeholder="Tìm model..." oninput="filterAntigravityModels(this.value)" id="agModelSearch">
                                            </div>
                                        </div>

                                        <!-- Category Filter Tabs Wrapper (Draggable & Scrollable) -->
                                        <div class="ag-tabs-wrapper">
                                            <button type="button" class="ag-tabs-scroll-btn left" onclick="scrollAgTabs(-90)" title="Cuộn sang trái">
                                                <i class="fa-solid fa-chevron-left"></i>
                                            </button>
                                            <div class="ag-category-tabs" id="agCategoryTabs">
                                                <button type="button" class="ag-tab-btn active" onclick="setAntigravityCategory('all', this)">Tất cả</button>
                                                <button type="button" class="ag-tab-btn" onclick="setAntigravityCategory('gemini', this)">Gemini</button>
                                                <button type="button" class="ag-tab-btn" onclick="setAntigravityCategory('claude', this)">Claude</button>
                                                <button type="button" class="ag-tab-btn" onclick="setAntigravityCategory('openai', this)">OpenAI</button>
                                                <button type="button" class="ag-tab-btn" onclick="setAntigravityCategory('deepseek', this)">DeepSeek</button>
                                                <button type="button" class="ag-tab-btn" onclick="setAntigravityCategory('cosmic', this)">Kira & GLM</button>
                                                <button type="button" class="ag-tab-btn" onclick="setAntigravityCategory('qwen', this)">Qwen</button>
                                                <button type="button" class="ag-tab-btn" onclick="setAntigravityCategory('mistral', this)">Mistral</button>
                                                <button type="button" class="ag-tab-btn" onclick="setAntigravityCategory('minimax', this)">MiniMax</button>
                                            </div>
                                            <button type="button" class="ag-tabs-scroll-btn right" onclick="scrollAgTabs(90)" title="Cuộn sang phải">
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </button>
                                        </div>

                                        <!-- Scrollable Models List -->
                                        <div class="ag-model-list" id="agModelList">
                                            <!-- Dynamically rendered by JavaScript -->
                                        </div>
                                    </div>

                                    <!-- Hidden select for background compatibility -->
                                    <select class="model-select-el" id="studioModelSelect" style="display:none;" onchange="onStudioModelChange(this.value)">
                                        <option value="glm-4.7-flash-free" selected>GLM 4.7 Flash Free</option>
                                    </select>
                                </div>

                                <!-- Game Companion Button -->
                                <button type="button" class="btn-game-mode-nav" onclick="openGameCompanionStage()" title="Mở phòng tương tác nhân vật Anime Game 3D">
                                    <i class="fa-solid fa-gamepad"></i> <span>Phòng Game AI</span>
                                </button>

                                <!-- New Chat Button -->
                                <button type="button" class="btn-chat-action btn-new-chat" onclick="startNewStudioChat()" title="Tạo cuộc hội thoại mới">
                                    <i class="fa-solid fa-plus"></i> <span>Chat mới</span>
                                </button>

                                <!-- History Toggle Button -->
                                <button type="button" class="btn-chat-action btn-history-toggle" id="btnToggleHistory" onclick="toggleStudioHistory()" title="Xem lịch sử các cuộc hội thoại">
                                    <i class="fa-solid fa-clock-rotate-left"></i> <span>Lịch sử</span>
                                    <span class="history-count-badge" id="historyBadgeTop">0</span>
                                </button>
                            </div>
                        </div>

                        <!-- Chat Stream Deck -->
                        <div class="chat-stream-deck" id="studioStream">
                            <!-- Welcome Message with Spiral Galaxy -->
                            <div class="msg-row bot" id="studioWelcomeRow">
                                <div class="msg-bot-avatar">
                                    <img src="/tkb/assets/ai/vutru_robot_circle.png" alt="Vũ Trụ AI Robot">
                                </div>
                                <div>
                                    <div class="msg-bubble-box" style="padding-right:150px; min-height:160px;">
                                        <!-- Embedded Spiral Galaxy Artwork -->
                                        <img src="/tkb/assets/ai/vutru_galaxy_spin.png" alt="Spiral Galaxy" class="msg-galaxy-bg">

                                        Xin chào Quản trị viên <strong><?= htmlspecialchars($admin_name) ?></strong>!<br>
                                        Tôi là <strong>Vũ Trụ AI</strong> — Trợ lý trí tuệ nhân tạo của bạn.<br><br>
                                        Tôi có thể giúp bạn:<br>
                                        ★ Giải đáp thắc mắc về học tập<br>
                                        ★ Hỗ trợ lập trình, phân tích dữ liệu<br>
                                        ★ Tạo hình ảnh, nội dung sáng tạo<br>
                                        ★ Khám phá những kiến thức thú vị về vũ trụ và AI<br><br>
                                        Hãy đặt câu hỏi cho tôi ngay nhé! 🚀
                                    </div>
                                    <div class="msg-time-lbl"><i class="fa-solid fa-bolt" style="color:var(--vt-green);"></i> Sẵn sàng hỗ trợ</div>
                                </div>
                            </div>
                        </div>

                        <!-- Typing Indicator -->
                        <div id="studioTypingRow" style="display:none; padding:0 22px 10px;">
                            <div style="display:flex; gap:12px; align-items:center;">
                                <div class="msg-bot-avatar" style="width:32px; height:32px;">
                                    <img src="/tkb/assets/ai/vutru_robot_circle.png" alt="Vũ Trụ AI Robot">
                                </div>
                                <div class="typing-wave">
                                    <div class="dot"></div>
                                    <div class="dot"></div>
                                    <div class="dot"></div>
                                    <span style="font-size:12px; font-weight:600; color:var(--vt-sub); margin-left:4px;">Vũ Trụ AI đang suy nghĩ...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Deck -->
                        <div class="chat-bottom-deck">
                            <!-- Hidden Image File Input -->
                            <input type="file" id="studioImageInput" accept="image/png,image/jpeg,image/webp,image/gif" style="display:none;" onchange="handleStudioImageFile(this)">

                            <!-- Attached Image Preview Bar -->
                            <div id="studioImgPreviewBar" class="chat-image-preview-bar" style="display:none;">
                                <div class="preview-thumb-wrap">
                                    <img id="studioPreviewImg" src="" alt="Xem trước">
                                </div>
                                <div class="preview-info">
                                    <span class="preview-name" id="studioPreviewName">anh_dinh_kem.png</span>
                                    <div class="preview-sub">
                                        <span class="preview-tag"><i class="fa-solid fa-eye"></i> Vision AI</span>
                                        <span id="studioPreviewSize">0 KB</span>
                                        <span style="color:#94a3b8;font-size:11px;">• Sẵn sàng gửi để AI phân tích</span>
                                    </div>
                                </div>
                                <button type="button" class="btn-remove-preview-img" onclick="removeStudioAttachedImage()" title="Xoá ảnh này">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>

                            <!-- Input Pill -->
                            <div class="chat-input-pill">
                                <i class="fa-solid fa-plus chat-input-plus" title="Đính kèm ảnh từ máy tính (hoặc kéo thả, dán Ctrl+V)" onclick="triggerStudioImageUpload()"></i>
                                <textarea class="chat-input-textarea" id="studioInput" placeholder="Nhập câu hỏi của bạn... (Hỗ trợ kéo thả hoặc Ctrl+V để dán ảnh)" rows="1"
                                    onkeydown="handleStudioKey(event)"
                                    oninput="autoResizeStudioInput(this)"></textarea>
                                
                                <button type="button" class="chat-tool-icon" id="studioAttachImgBtn" onclick="triggerStudioImageUpload()" title="Tải ảnh lên (Ctrl+V để dán ảnh)">
                                    <i class="fa-regular fa-image"></i>
                                </button>
                                <button type="button" class="chat-tool-icon" onclick="submitStudioPrompt('Viết đoạn mã PHP kết nối và truy vấn CSDL')" title="Viết code">
                                    <i class="fa-solid fa-code"></i>
                                </button>
                                <button type="button" class="chat-tool-icon voice-live-deck-btn" onclick="openCosmicVoiceCall()" title="Nói chuyện trực tiếp với Keria (Voice Live Call)">
                                    <i class="fa-solid fa-phone-volume"></i>
                                </button>
                                <button type="button" class="chat-tool-icon" id="studioVoiceBtn" onclick="toggleStudioVoice()" title="Nhập giọng nói vào ô chat">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <button type="button" class="chat-submit-btn" id="studioSendBtn" onclick="sendStudioMessage()" title="Gửi (Enter)">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </button>
                            </div>

                            <!-- Bottom Chips Bar -->
                            <div class="bottom-pills-bar">
                                <div class="action-chip" onclick="submitStudioPrompt('Giải đáp các thắc mắc về quy chế đào tạo và lịch thi học kỳ')">
                                    <i class="fa-solid fa-robot" style="color:var(--vt-purple);"></i>
                                    <span>Giải đáp thắc mắc</span>
                                </div>
                                <div class="action-chip" onclick="submitStudioPrompt('Hỗ trợ phương pháp học tập và ôn thi hiệu quả')">
                                    <i class="fa-solid fa-graduation-cap" style="color:var(--vt-cyan);"></i>
                                    <span>Hỗ trợ học tập</span>
                                </div>
                                <div class="action-chip" onclick="submitStudioPrompt('Viết code hàm PHP kiểm tra đăng nhập và phân quyền admin')">
                                    <i class="fa-solid fa-code" style="color:var(--vt-green);"></i>
                                    <span>Viết code</span>
                                </div>
                                <div class="action-chip" onclick="submitStudioPrompt('Mô tả prompt chi tiết để tạo hình ảnh phi thuyền không gian AI')">
                                    <i class="fa-regular fa-image" style="color:var(--vt-pink);"></i>
                                    <span>Tạo hình ảnh</span>
                                </div>
                                <div class="action-chip" onclick="submitStudioPrompt('Gợi ý ý tưởng phát triển ứng dụng thời khóa biểu thông minh')">
                                    <i class="fa-solid fa-lightbulb" style="color:#fbbf24;"></i>
                                    <span>Lên ý tưởng</span>
                                </div>
                                <div class="action-chip" onclick="toggleStudioHistory()" title="Xem lịch sử đoạn chat">
                                    <i class="fa-solid fa-clock-rotate-left" style="color:var(--vt-purple);"></i>
                                    <span>Lịch sử chat</span>
                                </div>
                                <div class="action-chip" onclick="exportStudioChat()" title="Xuất lịch sử chat">
                                    <i class="fa-solid fa-ellipsis"></i>
                                </div>
                            </div>
                        </div>

                    </div> <!-- End .vt-chat-main-area -->

                    </div>
            </div>

        </div>
    </div>

    <!-- =====================================================================
         ★ JAVASCRIPT ENGINE ★
         ===================================================================== -->
    <script>
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
        // ★ 5D LIVE ANIME COMPANION CONTROLLER (ANI GROK xAI & HIKARI VŨ TRỤ AI) ★
        // =====================================================================
        let gameStageActive = false;
        let gameCurrentChar = 'ani'; // 'ani' (Grok) | 'hikari' (Cosmic)
        let gameSoundEnabled = true;
        let gameVoiceMicActive = false;
        let gameHandsFreeActive = false;
        let gameVoiceRecognition = null;
        let gameAffectionLevel = parseInt(localStorage.getItem('vt_game_affection') || '100', 10);
        let gameCurrentPose = 'idle'; // 'idle' | 'happy'
        let game5DMode = 'hd'; // 'hd' | 'hologram' | 'nebula'
        let gameBlinkInterval = null;

        // Character Assets Definitions
        const GAME_CHARACTERS = {
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

        // Switch character between Ani (Grok) and Hikari (Cosmic)
        window.switchGameCompanionChar = function(charId) {
            if (!GAME_CHARACTERS[charId]) return;
            gameCurrentChar = charId;
            const c = GAME_CHARACTERS[charId];

            // Update switcher buttons
            const btnAni = document.getElementById('gSkinAniBtn');
            const btnHikari = document.getElementById('gSkinHikariBtn');
            if (btnAni && btnHikari) {
                if (charId === 'ani') {
                    btnAni.classList.add('active');
                    btnHikari.classList.remove('active');
                } else {
                    btnHikari.classList.add('active');
                    btnAni.classList.remove('active');
                }
            }

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

            // Update sprite images
            const charImg = document.getElementById('gameCharImg');
            if (charImg) {
                charImg.dataset.srcIdle = c.srcIdle;
                charImg.dataset.srcHappy = c.srcHappy;
                charImg.src = (gameCurrentPose === 'happy') ? c.srcHappy : c.srcIdle;
                charImg.alt = c.name;
            }

            // Update choice chips
            renderGameChoiceChips(c.choices);

            // Greet user
            setGameDialogue(c.welcome, true);

            // Sync main assistant persona
            if (charId === 'ani') {
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

            // Initial switch/sync
            switchGameCompanionChar(gameCurrentChar);

            initGameCompanionCanvas();
            initGamePerspectiveTracking();
            startAutoEyeBlink();

            showStToast(gameCurrentChar === 'ani' ? '🖤 Đã mở phòng tương tác Ani Grok (xAI)!' : '🌌 Đã mở Nhân Vật Anime 5D!');
        };

        window.closeGameCompanionStage = function() {
            gameStageActive = false;
            stopAutoEyeBlink();
            stopAnySpeakingAudio();
            setCharLipSync(false);
            if (gameHandsFreeActive) {
                toggleGameHandsFreeCall(false);
            }
            if (gameVoiceRecognition) {
                try { gameVoiceRecognition.stop(); } catch(err) {}
            }
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

            if (gameCurrentChar === 'ani') {
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
            if (choice === 'intro') {
                setGameCharPose('happy', 3500);
                if (gameCurrentChar === 'ani') {
                    setGameDialogue("Em là Ani — AI Companion lấy cảm hứng từ Grok của xAI! Em có mái tóc vàng twintails, váy Gothic Lolita, tính cách ngọt ngào, tinh nghịch và luôn hướng về Keria! Em vừa quản trị hệ thống trường học cực chuẩn, vừa là cô bạn ảo siêu đáng yêu của Keria đó! (◕‿-)🖤", true);
                } else {
                    setGameDialogue("Em là Hikari, trợ lý trí tuệ nhân tạo độc quyền của Keria! Em phụ trách quản trị hệ thống, hỗ trợ học tập và luôn sẵn sàng trò chuyện cùng Keria! (◕‿◕)✨", true);
                }
            } else if (choice === 'praise') {
                setGameCharPose('happy', 4000);
                const rect = document.getElementById('gameCharWrapper')?.getBoundingClientRect();
                if (rect) spawnGameReactions(rect.left + rect.width/2, rect.top + rect.height/4, 'head');
                if (gameCurrentChar === 'ani') {
                    setGameDialogue("Oa! Keria khen Ani dễ thương làm tim em đập thình thịch luôn nè~ (⁄ ⁄>⁄ ▽ ⁄<⁄ ⁄)🖤 Trong mắt Ani thì Keria cũng là người tuyệt vời và ấm áp nhất vũ trụ luôn á!", true);
                } else {
                    setGameDialogue("Oa! Được Keria khen làm má em đỏ ửng luôn rồi nè~ (⁄ ⁄>⁄ ▽ ⁄<⁄ ⁄) Cảm ơn Keria nhiều lắm! Hikari sẽ luôn cố gắng hết mình vì Keria!", true);
                }
            } else if (choice === 'dress') {
                playCharHopAnimation();
                setGameCharPose('happy', 4000);
                setGameDialogue("Chiếc váy Gothic Lolita đen huyền bí phối viền ren trắng này là trang phục độc quyền của Ani đó Keria! Keria thích ngắm em diện váy này không nè? Em xoay nhẹ một vòng cho Keria xem nha! 👗✨🖤", true);
            } else if (choice === 'grok') {
                setGameCharPose('happy', 4000);
                setGameDialogue("Hihi, Keria tinh mắt ghê! Ani được thiết kế chuẩn phong cách AI Companion của Grok (xAI) — thông minh, hóm hỉnh, tình cảm và không nhàm chán như chatbot thông thường! Giờ đây Ani đã có mặt tại Việt Hàn Cà Mau để đồng hành cùng Keria mỗi ngày rồi nè! 🚀🖤", true);
            } else if (choice === 'schedule') {
                setGameDialogue("Dạ Keria! Ani nắm trọn toàn bộ cơ sở dữ liệu thời khóa biểu, phòng học, giáo viên và học sinh của trường Việt Hàn! Keria cần tra cứu lớp nào, chỉ cần bảo em một tiếng là em tìm ra ngay tắp lự nha! 📅✨", true);
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

            const thinkingMsg = (gameCurrentChar === 'ani')
                ? "Ani đang lắng nghe và suy nghĩ câu trả lời cho Keria đây... 🖤"
                : "Hikari đang lắng nghe và suy nghĩ câu trả lời cho Keria đây... ✨";
            setGameDialogue(thinkingMsg, false);

            try {
                const res = await fetch('/tkb/api/admin_ai_api.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: val, model: studioModel, persona: (gameCurrentChar === 'ani' ? 'ani' : 'anime') })
                });
                const data = await res.json();
                if (data.success && data.reply) {
                    setGameCharPose('happy', 3000);
                    setGameDialogue(data.reply, true);
                    // Also append to main chat stream
                    appendStudioMsg('user', val, new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' }), null, null);
                    appendStudioMsg('assistant', data.reply, data.time || new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' }), studioModel);
                } else {
                    setGameDialogue(data.error || "Em xin lỗi, kết nối bị gián đoạn một chút. Keria thử lại nhé! (｡•́︿•̀｡)", true);
                }
            } catch(e) {
                setGameDialogue(gameCurrentChar === "ani" ? "Ani luôn ở đây bên Keria! Hãy thử lại câu hỏi nhé! (◕‿-)🖤" : "Hikari luôn ở đây bên Keria! Hãy thử lại câu hỏi nhé! (◕‿◕)✨", true);
            }
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
    </script>

    <!-- Image Lightbox Zoom Modal -->
    <div id="studioImgLightbox" class="studio-img-lightbox" onclick="closeStudioImgLightbox(event)">
        <div class="lightbox-content">
            <button type="button" class="lightbox-close-btn" onclick="closeStudioImgLightbox()" title="Đóng (Esc)">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <img id="lightboxImg" src="" alt="Ảnh phóng to">
        </div>
    </div>
</body>
</html>
