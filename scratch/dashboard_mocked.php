<?php $total_students=2; $total_teachers=4; $total_classes=2; $total_subjects=2; $total_mmo=3; $total_logs=328; $current_admin_name="Lê Nhựt Khánh"; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Điều Khiển Quản Trị - Lofi Chill Dreamy Aesthetic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">

    <style>
        :root {
            --adm-bg: #0c0717;
            --adm-card-bg: #140d27;
            --adm-card-border: rgba(168, 85, 247, 0.18);
            --adm-card-hover-border: rgba(192, 132, 252, 0.4);
            --adm-text-main: #ffffff;
            --adm-text-sub: #a79bb7;
            --adm-purple: #a855f7;
            --adm-cyan: #38bdf8;
            --adm-pink: #f472b6;
            --adm-green: #34d399;
            --adm-orange: #fb923c;
        }

        body.admin-portal {
            background-color: var(--adm-bg) !important;
            color: #f3e8ff;
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
            position: relative;
            overflow-x: hidden;
            display: block !important;
        }

        .adm-content-container {
            padding: 20px 24px 30px;
            max-width: 1680px;
            margin: 0 auto;
            box-sizing: border-box;
            position: relative;
            z-index: 2;
        }

        /* ----------------------------------------------------
           TWO-COLUMN MAIN DASHBOARD GRID
           ---------------------------------------------------- */
        .adm-dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 330px;
            gap: 20px;
            align-items: start;
        }

        @media (max-width: 1360px) {
            .adm-dashboard-grid {
                grid-template-columns: 1fr 310px;
                gap: 16px;
            }
        }

        @media (max-width: 1100px) {
            .adm-dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ----------------------------------------------------
           1. HERO BANNER
           ---------------------------------------------------- */
        .adm-hero-banner {
            position: relative;
            background: #090514 url('/tkb/assets/img/lofi_night_sky.jpg?v=<?= time() ?>') no-repeat center center;
            background-size: cover;
            border-radius: 20px;
            padding: 28px 34px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.5), inset 0 0 0 1px rgba(168, 85, 247, 0.3);
            margin-bottom: 20px;
            min-height: 175px;
        }
        .adm-hero-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(10, 6, 20, 0.90) 0%, rgba(13, 8, 26, 0.60) 45%, rgba(13, 8, 26, 0.10) 100%);
            pointer-events: none;
        }

        .adm-hero-left {
            position: relative;
            z-index: 2;
            max-width: 580px;
        }
        .adm-hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            background: rgba(168, 85, 247, 0.18);
            border: 1px solid rgba(192, 132, 252, 0.35);
            color: #f3e8ff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .adm-hero-title {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 6px;
            line-height: 1.25;
            letter-spacing: -0.2px;
        }
        .adm-hero-desc {
            font-size: 13px;
            color: #c4b5fd;
            line-height: 1.5;
            margin: 0;
            font-weight: 400;
        }

        .adm-hero-clock-card {
            position: relative;
            z-index: 2;
            background: rgba(18, 11, 35, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(168, 85, 247, 0.25);
            border-radius: 16px;
            padding: 16px 20px;
            text-align: center;
            min-width: 170px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
        .adm-clock-time {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 2px;
            text-shadow: 0 0 15px rgba(168, 85, 247, 0.4);
        }
        .adm-clock-day {
            font-size: 12px;
            color: #c4b5fd;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .adm-clock-tag {
            display: inline-block;
            font-size: 10.5px;
            color: #e9d5ff;
            background: rgba(168, 85, 247, 0.2);
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 600;
        }

        /* ----------------------------------------------------
           2. METRICS STAT CARDS (ROW 1 & ROW 2)
           ---------------------------------------------------- */
        .adm-stat-card-row1 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 16px;
        }

        .adm-stat-box {
            background: var(--adm-card-bg);
            border: 1px solid var(--adm-card-border);
            border-radius: 16px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
            min-height: 120px;
        }
        .adm-stat-box:hover {
            transform: translateY(-2px);
            border-color: var(--adm-card-hover-border);
            box-shadow: 0 10px 30px rgba(168, 85, 247, 0.2);
        }
        .adm-stat-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .adm-stat-num {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1;
        }
        .adm-stat-title {
            font-size: 11.5px;
            font-weight: 800;
            color: #f3e8ff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        .adm-stat-subtext {
            font-size: 11px;
            color: var(--adm-text-sub);
            margin-bottom: 8px;
        }
        .adm-stat-icon-sq {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }
        .sq-pink { background: rgba(244, 114, 182, 0.15); color: #f472b6; border: 1px solid rgba(244, 114, 182, 0.25); }
        .sq-purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.25); }
        .sq-green { background: rgba(52, 211, 153, 0.15); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.25); }
        .sq-orange { background: rgba(251, 146, 60, 0.15); color: #fb923c; border: 1px solid rgba(251, 146, 60, 0.25); }
        .sq-yellow { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.25); }

        .adm-stat-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
        }
        .adm-stat-trend-tag {
            font-size: 10.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .adm-stat-sparkline {
            width: 65px;
            height: 22px;
        }

        /* Row 2: 2 Boxes + Traffic Spline Chart */
        .adm-stat-card-row2 {
            display: grid;
            grid-template-columns: 1fr 1fr 2fr;
            gap: 14px;
            margin-bottom: 20px;
        }

        /* ----------------------------------------------------
           ACCESS STATS SPLINE CHART CARD
           ---------------------------------------------------- */
        .adm-chart-box {
            background: var(--adm-card-bg);
            border: 1px solid var(--adm-card-border);
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .adm-chart-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .adm-chart-heading {
            font-size: 12.5px;
            font-weight: 800;
            color: #f3e8ff;
        }
        .adm-chart-pill {
            font-size: 11px;
            font-weight: 600;
            color: #c4b5fd;
            background: rgba(30, 19, 56, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.25);
            padding: 3px 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .adm-chart-inner {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .adm-chart-val h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            line-height: 1;
        }
        .adm-chart-val span {
            font-size: 11px;
            color: var(--adm-text-sub);
            display: block;
            margin: 2px 0 4px;
        }
        .adm-chart-growth-tag {
            font-size: 10.5px;
            font-weight: 700;
            color: #34d399;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .adm-chart-svg {
            flex: 1;
            height: 75px;
        }

        /* ----------------------------------------------------
           3. BOTTOM 3 COLUMNS (Lớp học, Thông báo, Hoạt động)
           ---------------------------------------------------- */
        .adm-bottom-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        .adm-info-card {
            background: var(--adm-card-bg);
            border: 1px solid var(--adm-card-border);
            border-radius: 16px;
            padding: 16px 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .adm-info-header {
            font-size: 13px;
            font-weight: 800;
            color: #f3e8ff;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .adm-info-link {
            text-align: center;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid rgba(168, 85, 247, 0.1);
        }
        .adm-info-link a {
            font-size: 11.5px;
            font-weight: 700;
            color: #c084fc;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: 0.2s;
        }
        .adm-info-link a:hover {
            color: #ffffff;
            transform: translateX(3px);
        }

        /* Progress Bar Item */
        .adm-progress-row {
            margin-bottom: 10px;
        }
        .adm-progress-meta {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            color: #f3e8ff;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .adm-progress-sub {
            font-size: 10.5px;
            color: var(--adm-text-sub);
        }
        .adm-progress-bar-bg {
            height: 6px;
            background: rgba(168, 85, 247, 0.15);
            border-radius: 4px;
            overflow: hidden;
        }
        .adm-progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #7c3aed, #c084fc);
            border-radius: 4px;
        }

        /* Notice List Item */
        .adm-notice-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 7px 0;
            border-bottom: 1px solid rgba(168, 85, 247, 0.08);
            font-size: 11.5px;
        }
        .adm-notice-item:last-child {
            border-bottom: none;
        }
        .adm-notice-title {
            color: #e9d5ff;
            font-weight: 600;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .adm-notice-date {
            color: var(--adm-text-sub);
            font-size: 10px;
            flex-shrink: 0;
        }

        /* Recent Activity Item */
        .adm-act-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 6px 0;
            border-bottom: 1px solid rgba(168, 85, 247, 0.08);
            font-size: 11.5px;
        }
        .adm-act-item:last-child {
            border-bottom: none;
        }
        .adm-act-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-top: 4px;
            flex-shrink: 0;
        }
        .adm-act-text {
            color: #e9d5ff;
            font-weight: 600;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .adm-act-time {
            color: var(--adm-text-sub);
            font-size: 10px;
            flex-shrink: 0;
        }

        /* ----------------------------------------------------
           4. RIGHT SIDEBAR: GÓC KHOE NGƯỜI YÊU 💜
           ---------------------------------------------------- */
        .adm-dash-side-col {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .adm-gf-card {
            background: var(--adm-card-bg);
            border: 1px solid var(--adm-card-border);
            border-radius: 18px;
            padding: 16px 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        .adm-gf-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, #ec4899, #a855f7, #38bdf8);
            opacity: 0.8;
        }

        .adm-gf-header-title {
            font-size: 12.5px;
            font-weight: 800;
            color: #f472b6;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
            letter-spacing: 0.3px;
        }

        .adm-gf-profile-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
        }
        .adm-gf-avatar-wrap {
            position: relative;
            width: 78px;
            height: 78px;
            flex-shrink: 0;
        }
        .adm-gf-avatar-wrap img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2.5px solid #c084fc;
            box-shadow: 0 0 16px rgba(168, 85, 247, 0.5);
        }
        .adm-gf-meta h3 {
            font-size: 14.5px;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 3px;
        }
        .adm-gf-meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 700;
            color: #f472b6;
            background: rgba(244, 114, 182, 0.12);
            padding: 1px 6px;
            border-radius: 4px;
            margin-bottom: 4px;
        }
        .adm-gf-message {
            font-size: 10.5px;
            color: #c4b5fd;
            line-height: 1.4;
            font-style: italic;
        }

        .adm-gf-date-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10.5px;
            color: #e9d5ff;
            background: rgba(168, 85, 247, 0.12);
            border: 1px solid rgba(168, 85, 247, 0.2);
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: 600;
        }

        /* Gallery Grid */
        .adm-gf-gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-top: 8px;
        }
        .adm-gf-gallery-thumb {
            aspect-ratio: 1;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(168, 85, 247, 0.2);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .adm-gf-gallery-thumb:hover {
            transform: scale(1.06);
            border-color: #c084fc;
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.4);
        }
        .adm-gf-gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Video Memory Card */
        .adm-video-player-box {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(168, 85, 247, 0.25);
            background: #090414;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            margin-top: 8px;
        }
        .adm-video-story-bars {
            position: absolute;
            top: 8px;
            left: 8px;
            right: 8px;
            display: flex;
            gap: 4px;
            z-index: 5;
        }
        .adm-video-story-bar {
            height: 3px;
            flex: 1;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 2px;
            overflow: hidden;
            cursor: pointer;
            transition: background 0.2s;
        }
        .adm-video-story-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #ec4899, #c084fc);
            border-radius: 2px;
            transition: width 0.1s linear;
        }
        .adm-video-element-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            min-height: 175px;
            max-height: 220px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .adm-video-element-wrap video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .adm-video-element-wrap img.adm-video-thumb-static {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .adm-video-nav-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(20, 13, 39, 0.7);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(168, 85, 247, 0.35);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 11px;
            z-index: 6;
            transition: all 0.2s;
            opacity: 0;
        }
        .adm-video-player-box:hover .adm-video-nav-arrow {
            opacity: 1;
        }
        .adm-video-nav-arrow:hover {
            background: rgba(168, 85, 247, 0.85);
            transform: translateY(-50%) scale(1.1);
        }
        .adm-video-nav-prev { left: 8px; }
        .adm-video-nav-next { right: 8px; }
        .adm-video-del-btn {
            position: absolute;
            top: 18px;
            right: 8px;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: rgba(239, 68, 68, 0.85);
            color: #fff;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 10px;
            z-index: 7;
            opacity: 0;
            transition: all 0.2s;
        }
        .adm-video-player-box:hover .adm-video-del-btn {
            opacity: 1;
        }
        .adm-video-del-btn:hover {
            background: #ef4444;
            transform: scale(1.1);
        }
        .adm-video-center-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(147, 51, 234, 0.85);
            border: 2px solid rgba(255, 255, 255, 0.6);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.8);
            transition: 0.2s;
            z-index: 5;
            pointer-events: none;
        }
        .adm-video-bottom-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px 10px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.4) 60%, transparent 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10.5px;
            color: #fff;
            z-index: 6;
        }

        /* Love Quote Bottom Card */
        .adm-quote-card {
            background: linear-gradient(135deg, rgba(147, 51, 234, 0.15), rgba(244, 114, 182, 0.1));
            border: 1px solid rgba(244, 114, 182, 0.25);
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .adm-quote-text {
            font-size: 11px;
            font-style: italic;
            color: #fce7f3;
            line-height: 1.4;
        }
        .adm-quote-heart {
            font-size: 16px;
            color: #f472b6;
            animation: pulseHeart 1.5s infinite ease-in-out;
            flex-shrink: 0;
        }

        @keyframes pulseHeart {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 2px #f472b6); }
            50% { transform: scale(1.2); filter: drop-shadow(0 0 8px #f472b6); }
        }

        /* Floating Sakura Petals Background */
        .sakura-petal {
            position: fixed;
            background: radial-gradient(circle, #fbcfe8 0%, #f472b6 60%, transparent 100%);
            border-radius: 100% 0 100% 0;
            pointer-events: none;
            opacity: 0.45;
            z-index: 1;
            animation: sakuraFall linear infinite;
        }
        @keyframes sakuraFall {
            0% { transform: translate(0, -10px) rotate(0deg); opacity: 0; }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% { transform: translate(120px, 100vh) rotate(360deg); opacity: 0; }
        }

        /* Footer */
        .adm-footer {
            margin-top: 30px;
            padding-top: 14px;
            border-top: 1px solid rgba(168, 85, 247, 0.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            color: #a79bb7;
        }

        @media (max-width: 900px) {
            .adm-stat-card-row1 { grid-template-columns: repeat(2, 1fr); }
            .adm-stat-card-row2 { grid-template-columns: 1fr; }
            .adm-bottom-row { grid-template-columns: 1fr; }
            .adm-hero-banner { flex-direction: column; align-items: flex-start; gap: 16px; }
            .adm-hero-clock-card { width: 100%; box-sizing: border-box; }
        }

        /* ============ COUPLE EDIT BUTTON ============ */
        .adm-gf-edit-btn {
            position: absolute; top: 10px; right: 10px;
            width: 28px; height: 28px; border-radius: 8px;
            background: rgba(168, 85, 247, 0.25); border: 1px solid rgba(168, 85, 247, 0.4);
            color: #c084fc; display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 11px; transition: all 0.2s; z-index: 3;
        }
        .adm-gf-edit-btn:hover { background: rgba(168, 85, 247, 0.5); color: #fff; transform: scale(1.1); }

        .adm-gf-add-btn {
            display: flex; align-items: center; justify-content: center; gap: 5px;
            width: 100%; padding: 7px 0; margin-top: 8px; border-radius: 10px;
            background: rgba(168, 85, 247, 0.12); border: 1.5px dashed rgba(168, 85, 247, 0.35);
            color: #c084fc; font-size: 11px; font-weight: 700; cursor: pointer; transition: all 0.2s;
        }
        .adm-gf-add-btn:hover { background: rgba(168, 85, 247, 0.25); border-color: #c084fc; }

        .adm-gf-gallery-thumb .adm-gf-del-photo {
            position: absolute; top: 3px; right: 3px; width: 18px; height: 18px;
            border-radius: 50%; background: rgba(239, 68, 68, 0.85); color: #fff;
            display: none; align-items: center; justify-content: center;
            font-size: 9px; cursor: pointer; z-index: 5; border: none;
        }
        .adm-gf-gallery-thumb:hover .adm-gf-del-photo { display: flex; }
        .adm-gf-gallery-thumb { position: relative; }

        .adm-video-item { position: relative; margin-bottom: 10px; }
        .adm-video-item .adm-video-del {
            position: absolute; top: 6px; right: 6px; width: 22px; height: 22px;
            border-radius: 6px; background: rgba(239, 68, 68, 0.85); color: #fff;
            display: none; align-items: center; justify-content: center;
            font-size: 10px; cursor: pointer; z-index: 5; border: none;
        }
        .adm-video-item:hover .adm-video-del { display: flex; }

        /* ============ COUPLE EDIT MODAL ============ */
        .couple-modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(6px);
            z-index: 9999; display: none; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s;
        }
        .couple-modal-overlay.active { display: flex; opacity: 1; }
        .couple-modal {
            background: #1a0f2e; border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 20px; width: 480px; max-width: 95vw; max-height: 85vh;
            overflow-y: auto; padding: 24px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .couple-modal::-webkit-scrollbar { width: 4px; }
        .couple-modal::-webkit-scrollbar-thumb { background: #a855f7; border-radius: 4px; }
        .couple-modal-title {
            font-size: 16px; font-weight: 800; color: #f472b6; margin-bottom: 16px;
            display: flex; align-items: center; gap: 8px;
        }
        .couple-modal .cm-group { margin-bottom: 14px; }
        .couple-modal .cm-label {
            font-size: 11px; font-weight: 700; color: #c4b5fd; margin-bottom: 4px;
            display: flex; align-items: center; gap: 4px;
        }
        .couple-modal .cm-input {
            width: 100%; padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(168, 85, 247, 0.25);
            background: rgba(168, 85, 247, 0.08); color: #f3e8ff; font-size: 12.5px;
            font-family: inherit; outline: none; transition: border-color 0.2s;
        }
        .couple-modal .cm-input:focus { border-color: #a855f7; }
        .couple-modal .cm-textarea {
            width: 100%; padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(168, 85, 247, 0.25);
            background: rgba(168, 85, 247, 0.08); color: #f3e8ff; font-size: 12.5px;
            font-family: inherit; outline: none; resize: vertical; min-height: 60px; transition: border-color 0.2s;
        }
        .couple-modal .cm-textarea:focus { border-color: #a855f7; }
        .couple-modal .cm-avatar-preview {
            width: 70px; height: 70px; border-radius: 50%; object-fit: cover;
            border: 2px solid #c084fc; margin-right: 10px;
        }
        .couple-modal .cm-avatar-row { display: flex; align-items: center; gap: 10px; }
        .couple-modal .cm-upload-btn {
            padding: 6px 14px; border-radius: 8px; background: rgba(168, 85, 247, 0.2);
            border: 1px solid rgba(168, 85, 247, 0.4); color: #c084fc; font-size: 11px;
            font-weight: 700; cursor: pointer; transition: all 0.2s;
        }
        .couple-modal .cm-upload-btn:hover { background: rgba(168, 85, 247, 0.4); color: #fff; }
        .couple-modal .cm-actions {
            display: flex; gap: 10px; margin-top: 18px; justify-content: flex-end;
        }
        .couple-modal .cm-btn-cancel {
            padding: 8px 20px; border-radius: 10px; background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12); color: #a79bb7; font-size: 12px;
            font-weight: 700; cursor: pointer; transition: all 0.2s;
        }
        .couple-modal .cm-btn-cancel:hover { background: rgba(255, 255, 255, 0.12); }
        .couple-modal .cm-btn-save {
            padding: 8px 24px; border-radius: 10px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            border: none; color: #fff; font-size: 12px; font-weight: 700;
            cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 15px rgba(168, 85, 247, 0.4);
        }
        .couple-modal .cm-btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(168, 85, 247, 0.6); }
        .couple-modal .cm-btn-save:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .cm-toast {
            position: fixed; bottom: 30px; right: 30px; padding: 12px 20px;
            border-radius: 12px; background: linear-gradient(135deg, #a855f7, #ec4899);
            color: #fff; font-size: 12px; font-weight: 700; z-index: 99999;
            box-shadow: 0 8px 25px rgba(168, 85, 247, 0.5);
            animation: toastIn 0.3s ease, toastOut 0.3s ease 2.5s forwards;
        }
        @keyframes toastIn { from { transform: translateY(20px); opacity: 0; } }
        @keyframes toastOut { to { transform: translateY(20px); opacity: 0; } }
    </style>
</head>
<body class="admin-portal">

    <!-- Subtle Sakura Blossom Elements -->
    <div class="sakura-petal" style="width:10px; height:10px; left:5%; animation-duration:9s; animation-delay:0s;"></div>
    <div class="sakura-petal" style="width:14px; height:14px; left:18%; animation-duration:12s; animation-delay:2s;"></div>
    <div class="sakura-petal" style="width:8px; height:8px; left:35%; animation-duration:8s; animation-delay:4s;"></div>
    <div class="sakura-petal" style="width:12px; height:12px; left:60%; animation-duration:11s; animation-delay:1s;"></div>
    <div class="sakura-petal" style="width:10px; height:10px; left:82%; animation-duration:10s; animation-delay:3s;"></div>

    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="main-content">
        <div class="adm-content-container">
            
            <div class="adm-dashboard-grid">
                
                <!-- ==============================================
                     LEFT COLUMN: MAIN METRICS & CARDS
                     ============================================== -->
                <div class="adm-dash-main-col">
                    
                    <!-- 1. HERO BANNER -->
                    <div class="adm-hero-banner">
                        <div class="adm-hero-left">
                            <div class="adm-hero-pill">
                                <i class="fa-solid fa-heart" style="color:#f472b6;"></i>
                                <span>QUẢN TRỊ VIÊN HỆ THỐNG</span>
                            </div>
                            <h1 class="adm-hero-title">Xin chào, <span style="color:#c084fc;"><?= htmlspecialchars($current_admin_name) ?></span>!</h1>
                            <p class="adm-hero-desc">Chào mừng bạn đến với Cổng Quản Trị Hệ Thống Trường Cao Đẳng Cà Mau. Giám sát toàn diện người dùng, tài nguyên, phân quyền và dữ liệu vận hành.</p>
                        </div>

                        <div class="adm-hero-clock-card">
                            <div class="adm-clock-time">
                                <i class="fa-regular fa-clock" style="color:#c084fc; font-size:18px;"></i>
                                <span id="liveClockHour">20:06</span>
                            </div>
                            <div class="adm-clock-day" id="liveClockDate">Thứ Tư, 02/09/2026</div>
                            <div class="adm-clock-tag" id="liveClockVibe">Buổi tối lofi chill 🌙</div>
                        </div>
                    </div>

                    <!-- 2. ROW 1: 4 STAT CARDS -->
                    <div class="adm-stat-card-row1">
                        
                        <!-- Sinh viên -->
                        <a href="/tkb/admin/students.php" class="adm-stat-box">
                            <div>
                                <div class="adm-stat-top">
                                    <div class="adm-stat-num"><?= number_format($total_students) ?></div>
                                    <div class="adm-stat-icon-sq sq-pink"><i class="fa-solid fa-graduation-cap"></i></div>
                                </div>
                                <div class="adm-stat-title">SINH VIÊN</div>
                                <div class="adm-stat-subtext">Tổng số sinh viên</div>
                            </div>
                            <div class="adm-stat-bottom">
                                <span class="adm-stat-trend-tag" style="color:#f472b6;">↗ 12% so với tháng trước</span>
                                <svg class="adm-stat-sparkline" viewBox="0 0 65 22">
                                    <path d="M0,18 Q 15,20 30,12 T 65,4" fill="none" stroke="#f472b6" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </a>

                        <!-- Giảng viên -->
                        <a href="/tkb/admin/giangvien.php" class="adm-stat-box">
                            <div>
                                <div class="adm-stat-top">
                                    <div class="adm-stat-num"><?= number_format($total_teachers) ?></div>
                                    <div class="adm-stat-icon-sq sq-purple"><i class="fa-solid fa-chalkboard-user"></i></div>
                                </div>
                                <div class="adm-stat-title">GIẢNG VIÊN</div>
                                <div class="adm-stat-subtext">Tổng số giảng viên</div>
                            </div>
                            <div class="adm-stat-bottom">
                                <span class="adm-stat-trend-tag" style="color:#38bdf8;">↗ 8% so với tháng trước</span>
                                <svg class="adm-stat-sparkline" viewBox="0 0 65 22">
                                    <path d="M0,19 Q 20,10 40,16 T 65,5" fill="none" stroke="#818cf8" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </a>

                        <!-- Lớp học -->
                        <a href="/tkb/admin/lop.php" class="adm-stat-box">
                            <div>
                                <div class="adm-stat-top">
                                    <div class="adm-stat-num"><?= number_format($total_classes) ?></div>
                                    <div class="adm-stat-icon-sq sq-green"><i class="fa-solid fa-book-open"></i></div>
                                </div>
                                <div class="adm-stat-title">LỚP HỌC</div>
                                <div class="adm-stat-subtext">Tổng số lớp học</div>
                            </div>
                            <div class="adm-stat-bottom">
                                <span class="adm-stat-trend-tag" style="color:#34d399;">↗ 5% so với tháng trước</span>
                                <svg class="adm-stat-sparkline" viewBox="0 0 65 22">
                                    <path d="M0,16 Q 20,18 40,11 T 65,6" fill="none" stroke="#34d399" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </a>

                        <!-- Môn học -->
                        <a href="/tkb/admin/monhoc.php" class="adm-stat-box">
                            <div>
                                <div class="adm-stat-top">
                                    <div class="adm-stat-num"><?= number_format($total_subjects) ?></div>
                                    <div class="adm-stat-icon-sq sq-orange"><i class="fa-solid fa-box-archive"></i></div>
                                </div>
                                <div class="adm-stat-title">MÔN HỌC</div>
                                <div class="adm-stat-subtext">Tổng số môn học</div>
                            </div>
                            <div class="adm-stat-bottom">
                                <span class="adm-stat-trend-tag" style="color:#fb923c;">↗ 7% so với tháng trước</span>
                                <svg class="adm-stat-sparkline" viewBox="0 0 65 22">
                                    <path d="M0,18 Q 20,12 40,17 T 65,8" fill="none" stroke="#fb923c" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </a>

                    </div>

                    <!-- 3. ROW 2: 2 CARDS + ACCESS CHART -->
                    <div class="adm-stat-card-row2">
                        
                        <!-- Chợ MMO & AI -->
                        <a href="/tkb/admin/quanly_ai_accounts.php" class="adm-stat-box">
                            <div>
                                <div class="adm-stat-top">
                                    <div class="adm-stat-num"><?= number_format($total_mmo) ?></div>
                                    <div class="adm-stat-icon-sq sq-yellow"><i class="fa-solid fa-cart-shopping"></i></div>
                                </div>
                                <div class="adm-stat-title">CHỢ MMO & AI</div>
                                <div class="adm-stat-subtext">Tổng số sản phẩm</div>
                            </div>
                            <div class="adm-stat-bottom">
                                <span class="adm-stat-trend-tag" style="color:#34d399;">↗ 15% so với tháng trước</span>
                                <svg class="adm-stat-sparkline" viewBox="0 0 65 22">
                                    <path d="M0,17 Q 20,19 40,12 T 65,5" fill="none" stroke="#fbbf24" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </a>

                        <!-- Nhật ký ghi nhận -->
                        <a href="/tkb/admin/nhatky.php" class="adm-stat-box">
                            <div>
                                <div class="adm-stat-top">
                                    <div class="adm-stat-num"><?= number_format($total_logs) ?></div>
                                    <div class="adm-stat-icon-sq sq-purple"><i class="fa-solid fa-clipboard-list"></i></div>
                                </div>
                                <div class="adm-stat-title">NHẬT KÝ GHI NHẬN</div>
                                <div class="adm-stat-subtext">Tổng số nhật ký</div>
                            </div>
                            <div class="adm-stat-bottom">
                                <span class="adm-stat-trend-tag" style="color:#34d399;">↗ 10% so với tháng trước</span>
                                <svg class="adm-stat-sparkline" viewBox="0 0 65 22">
                                    <path d="M0,18 Q 20,11 40,16 T 65,6" fill="none" stroke="#c084fc" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </a>

                        <!-- ACCESS STATS SPLINE CHART -->
                        <div class="adm-chart-box">
                            <div class="adm-chart-top">
                                <span class="adm-chart-heading">Thống kê truy cập (7 ngày qua)</span>
                                <div class="adm-chart-pill">
                                    <span>7 ngày qua</span>
                                    <i class="fa-solid fa-chevron-down" style="font-size:9px;"></i>
                                </div>
                            </div>
                            <div class="adm-chart-inner">
                                <div class="adm-chart-val">
                                    <h4>1.248</h4>
                                    <span>Lượt truy cập</span>
                                    <div class="adm-chart-growth-tag">
                                        <i class="fa-solid fa-arrow-up"></i> 18.6%
                                    </div>
                                </div>
                                <div class="adm-chart-svg">
                                    <svg viewBox="0 0 380 75" fill="none" preserveAspectRatio="none" style="width:100%; height:100%;">
                                        <defs>
                                            <linearGradient id="purpleLofiGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" stop-color="#a855f7" stop-opacity="0.35"/>
                                                <stop offset="100%" stop-color="#a855f7" stop-opacity="0.0"/>
                                            </linearGradient>
                                        </defs>
                                        <line x1="0" y1="60" x2="380" y2="60" stroke="rgba(168, 85, 247, 0.15)" stroke-dasharray="2 2" />
                                        <path d="M0,60 Q 60,58 120,48 T 240,36 T 340,18 L 380,12 L 380,60 L 0,60 Z" fill="url(#purpleLofiGrad)" />
                                        <path d="M0,60 Q 60,58 120,48 T 240,36 T 340,18 L 380,12" stroke="#a855f7" stroke-width="2.5" stroke-linecap="round" fill="none" />
                                        <circle cx="380" cy="12" r="3.5" fill="#ffffff" stroke="#a855f7" stroke-width="2" />
                                        
                                        <text x="0" y="72" fill="#9d8ba7" font-size="9" font-weight="600">13/08</text>
                                        <text x="60" y="72" fill="#9d8ba7" font-size="9" font-weight="600">14/08</text>
                                        <text x="125" y="72" fill="#9d8ba7" font-size="9" font-weight="600">15/08</text>
                                        <text x="190" y="72" fill="#9d8ba7" font-size="9" font-weight="600">17/08</text>
                                        <text x="255" y="72" fill="#9d8ba7" font-size="9" font-weight="600">18/08</text>
                                        <text x="345" y="72" fill="#c084fc" font-size="9" font-weight="700">19/08</text>
                                    </svg>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- 4. ROW 3: 3 BOTTOM CARDS -->
                    <div class="adm-bottom-row">
                        
                        <!-- Lớp học hoạt động -->
                        <div class="adm-info-card">
                            <div>
                                <div class="adm-info-header">
                                    <span>Lớp học hoạt động</span>
                                </div>
                                
                                <div class="adm-progress-row">
                                    <div class="adm-progress-meta">
                                        <span>CNTT K15</span>
                                        <span style="color:#c084fc;">80%</span>
                                    </div>
                                    <div class="adm-progress-sub" style="margin-bottom:4px;">28 sinh viên</div>
                                    <div class="adm-progress-bar-bg">
                                        <div class="adm-progress-bar-fill" style="width:80%;"></div>
                                    </div>
                                </div>

                                <div class="adm-progress-row">
                                    <div class="adm-progress-meta">
                                        <span>QTKD K16</span>
                                        <span style="color:#c084fc;">65%</span>
                                    </div>
                                    <div class="adm-progress-sub" style="margin-bottom:4px;">32 sinh viên</div>
                                    <div class="adm-progress-bar-bg">
                                        <div class="adm-progress-bar-fill" style="width:65%;"></div>
                                    </div>
                                </div>

                                <div class="adm-progress-row">
                                    <div class="adm-progress-meta">
                                        <span>TCNH K15</span>
                                        <span style="color:#c084fc;">75%</span>
                                    </div>
                                    <div class="adm-progress-sub" style="margin-bottom:4px;">25 sinh viên</div>
                                    <div class="adm-progress-bar-bg">
                                        <div class="adm-progress-bar-fill" style="width:75%;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="adm-info-link">
                                <a href="/tkb/admin/lop.php">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>

                        <!-- Thông báo mới -->
                        <div class="adm-info-card">
                            <div>
                                <div class="adm-info-header">
                                    <span>Thông báo mới</span>
                                </div>

                                <div class="adm-notice-item">
                                    <i class="fa-regular fa-file-lines" style="color:#f472b6; margin-top:2px;"></i>
                                    <span class="adm-notice-title">Thông báo lịch thi học kỳ 1</span>
                                    <span class="adm-notice-date">02/09/2026</span>
                                </div>

                                <div class="adm-notice-item">
                                    <i class="fa-regular fa-bookmark" style="color:#fbbf24; margin-top:2px;"></i>
                                    <span class="adm-notice-title">Cập nhật bài giảng mới - PHP nâng cao</span>
                                    <span class="adm-notice-date">01/09/2026</span>
                                </div>

                                <div class="adm-notice-item">
                                    <i class="fa-solid fa-shield-halved" style="color:#38bdf8; margin-top:2px;"></i>
                                    <span class="adm-notice-title">Bảo trì hệ thống ngày 05/09/2026</span>
                                    <span class="adm-notice-date">31/08/2026</span>
                                </div>
                            </div>

                            <div class="adm-info-link">
                                <a href="/tkb/admin/nhatky.php">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>

                        <!-- Hoạt động gần đây -->
                        <div class="adm-info-card">
                            <div>
                                <div class="adm-info-header">
                                    <span>Hoạt động gần đây</span>
                                </div>

                                <div class="adm-act-item">
                                    <div class="adm-act-dot" style="background:#c084fc; box-shadow:0 0 6px #c084fc;"></div>
                                    <span class="adm-act-text">Lê Nhựt Khánh đăng nhập hệ thống</span>
                                    <span class="adm-act-time">2 phút trước</span>
                                </div>

                                <div class="adm-act-item">
                                    <div class="adm-act-dot" style="background:#38bdf8; box-shadow:0 0 6px #38bdf8;"></div>
                                    <span class="adm-act-text">Nguyễn Văn An nộp bài tập PHP</span>
                                    <span class="adm-act-time">15 phút trước</span>
                                </div>

                                <div class="adm-act-item">
                                    <div class="adm-act-dot" style="background:#34d399; box-shadow:0 0 6px #34d399;"></div>
                                    <span class="adm-act-text">Trần Thị Bảo cập nhật điểm môn CSDL</span>
                                    <span class="adm-act-time">30 phút trước</span>
                                </div>

                                <div class="adm-act-item">
                                    <div class="adm-act-dot" style="background:#fbbf24; box-shadow:0 0 6px #fbbf24;"></div>
                                    <span class="adm-act-text">Phạm Minh Hoàng đăng bài giảng mới</span>
                                    <span class="adm-act-time">1 giờ trước</span>
                                </div>
                            </div>

                            <div class="adm-info-link">
                                <a href="/tkb/admin/nhatky.php">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- ==============================================
                     RIGHT COLUMN: GÓC KHOE NGƯỜI YÊU 💜
                     ============================================== -->
                <div class="adm-dash-side-col" id="coupleColumn">
                    <!-- Content will be dynamically rendered by JS -->
                    <div style="text-align:center; padding:40px 0; color:#a79bb7;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size:24px; color:#a855f7;"></i>
                        <div style="margin-top:10px; font-size:12px;">Đang tải Góc Người Yêu...</div>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="adm-footer">
                <div>© 2026 Trường Cao Đẳng Cà Mau. All rights reserved.</div>
                <div>Made with <i class="fa-solid fa-heart" style="color:#f472b6;"></i> and lofi vibes</div>
            </div>

        </div>
    </div>

    <!-- Couple Edit Modal -->
    <div class="couple-modal-overlay" id="coupleModalOverlay">
        <div class="couple-modal" id="coupleModal">
            <div class="couple-modal-title">
                <i class="fa-solid fa-heart-pulse"></i> Chỉnh sửa Góc Người Yêu 💜
            </div>

            <div class="cm-group">
                <div class="cm-label"><i class="fa-solid fa-camera"></i> Ảnh đại diện</div>
                <div class="cm-avatar-row">
                    <img id="cmAvatarPreview" class="cm-avatar-preview" src="/tkb/assets/img/phuong_anh_avatar.png" alt="Avatar">
                    <button class="cm-upload-btn" onclick="document.getElementById('cmAvatarFile').click()"><i class="fa-solid fa-upload"></i> Đổi ảnh</button>
                    <input type="file" id="cmAvatarFile" accept="image/*" style="display:none" onchange="uploadCoupleAvatar(this)">
                </div>
            </div>

            <div class="cm-group">
                <div class="cm-label"><i class="fa-solid fa-user"></i> Tên người yêu</div>
                <input class="cm-input" id="cmName" placeholder="Nguyễn Phương Anh">
            </div>

            <div class="cm-group">
                <div class="cm-label"><i class="fa-solid fa-heart"></i> Badge (tag yêu)</div>
                <input class="cm-input" id="cmBadge" placeholder="My Everything">
            </div>

            <div class="cm-group">
                <div class="cm-label"><i class="fa-solid fa-comment-dots"></i> Lời nhắn yêu thương</div>
                <textarea class="cm-textarea" id="cmMessage" placeholder="Cảm ơn em vì đã luôn ở đây..."></textarea>
            </div>

            <div class="cm-group">
                <div class="cm-label"><i class="fa-solid fa-calendar-heart"></i> Ngày kỷ niệm</div>
                <input class="cm-input" id="cmDate" placeholder="17/02/2026">
            </div>

            <div class="cm-group">
                <div class="cm-label"><i class="fa-solid fa-tag"></i> Ghi chú ngày</div>
                <input class="cm-input" id="cmDateLabel" placeholder="Ngày chúng ta bắt đầu ❤️">
            </div>

            <div class="cm-group">
                <div class="cm-label"><i class="fa-solid fa-quote-left"></i> Câu nói yêu thương</div>
                <input class="cm-input" id="cmQuote" placeholder="Mỗi ngày bên em là một ngày tuyệt vời nhất.">
            </div>

            <div class="cm-actions">
                <button class="cm-btn-cancel" onclick="closeCoupleModal()">Hủy</button>
                <button class="cm-btn-save" id="cmSaveBtn" onclick="saveCoupleSettings()"><i class="fa-solid fa-check"></i> Lưu thay đổi</button>
            </div>
        </div>
    </div>

    <!-- Live Digital Clock + Couple Dynamic Render Script -->
    <script>
    // ============ CLOCK ============
    function updateClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
        const dayName = days[now.getDay()];
        const dateStr = String(now.getDate()).padStart(2, '0') + '/' + String(now.getMonth() + 1).padStart(2, '0') + '/' + now.getFullYear();
        const hourNum = now.getHours();
        let vibe = 'Buổi tối lofi chill 🌙';
        if (hourNum >= 5 && hourNum < 12) vibe = 'Buổi sáng lofi chill ☕';
        else if (hourNum >= 12 && hourNum < 18) vibe = 'Buổi chiều lofi chill 🌸';
        const hEl = document.getElementById('liveClockHour');
        const dEl = document.getElementById('liveClockDate');
        const vEl = document.getElementById('liveClockVibe');
        if (hEl) hEl.textContent = `${h}:${m}`;
        if (dEl) dEl.textContent = `${dayName}, ${dateStr}`;
        if (vEl) vEl.textContent = vibe;
    }
    updateClock();
    setInterval(updateClock, 1000);

    // ============ COUPLE DATA & CONTINUOUS VIDEO PLAYLIST ============
    let coupleData = null;
    let currentVideoIdx = 0;
    let isVideoMuted = true;
    let isPlaying = false;

    async function loadCoupleData() {
        try {
            const res = await fetch('/tkb/api/couple_api.php?action=get');
            const json = await res.json();
            if (json.success && json.data) {
                coupleData = json.data;
                renderCoupleColumn();
                startContinuousVideo();
                return;
            }
        } catch (e) {
            console.error('Error fetching couple data:', e);
        }

        if (!coupleData) {
            coupleData = {
                name: 'Nguyễn Phương Anh', badge: 'My Everything',
                message: 'Cảm ơn em vì đã luôn ở đây, là động lực và ánh sáng trong cuộc sống của anh. 🌙',
                date: '17/02/2026', date_label: 'Ngày chúng ta bắt đầu ❤️',
                quote: 'Mỗi ngày bên em là một ngày tuyệt vời nhất.',
                avatar: '/tkb/assets/img/phuong_anh_avatar.png',
                photos: ['/tkb/assets/img/phuong_anh_1.png', '/tkb/assets/img/phuong_anh_2.png', '/tkb/assets/img/phuong_anh_3.png', '/tkb/assets/img/phuong_anh_4.png'],
                videos: [{thumb: '/tkb/assets/img/anime_video_thumb.png', url: '', title: 'Video Kỷ Niệm'}]
            };
            renderCoupleColumn();
            startContinuousVideo();
        }
    }

    function renderCoupleColumn() {
        const col = document.getElementById('coupleColumn');
        if (!col || !coupleData) return;
        const d = coupleData;

        // Build photos HTML
        let photosHtml = '';
        (d.photos || []).forEach((src, i) => {
            photosHtml += `
                <div class="adm-gf-gallery-thumb">
                    <img src="${esc(src)}" alt="Ảnh ${i+1}">
                    <button class="adm-gf-del-photo" onclick="deletePhoto(${i})" title="Xóa ảnh"><i class="fa-solid fa-xmark"></i></button>
                </div>`;
        });

        // Build single unified continuous Video Player HTML
        const videoPlayerHtml = renderVideoPlayerBox();

        col.innerHTML = `
            <!-- 1. Profile Girlfriend Card -->
            <div class="adm-gf-card">
                <button class="adm-gf-edit-btn" onclick="openCoupleModal()" title="Chỉnh sửa thông tin"><i class="fa-solid fa-pen"></i></button>
                <div class="adm-gf-header-title">
                    <i class="fa-solid fa-star" style="color:#c084fc;"></i> GÓC KHOE NGƯỜI YÊU <i class="fa-solid fa-heart" style="color:#f472b6;"></i>
                </div>
                <div class="adm-gf-profile-row">
                    <div class="adm-gf-avatar-wrap">
                        <img src="${esc(d.avatar)}" onerror="this.onerror=null; this.src='/tkb/assets/img/avatar_khanh.png';" alt="${esc(d.name)}">
                    </div>
                    <div class="adm-gf-meta">
                        <h3>${esc(d.name)}</h3>
                        <div class="adm-gf-meta-badge"><i class="fa-solid fa-heart"></i> ${esc(d.badge)}</div>
                        <div class="adm-gf-message">${esc(d.message)}</div>
                    </div>
                </div>
                <div class="adm-gf-date-pill">
                    <i class="fa-regular fa-clock" style="color:#f472b6;"></i>
                    <span>${esc(d.date)} • ${esc(d.date_label)}</span>
                </div>
            </div>

            <!-- 2. Photo Gallery Card -->
            <div class="adm-gf-card">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                    <span style="font-size:12px; font-weight:800; color:#f3e8ff;">BỘ SƯU TẬP ẢNH 📷</span>
                    <span style="font-size:10px; color:#a79bb7;">${(d.photos||[]).length} ảnh</span>
                </div>
                <div class="adm-gf-gallery-grid">${photosHtml}</div>
                <div class="adm-gf-add-btn" onclick="document.getElementById('addPhotoInput').click()">
                    <i class="fa-solid fa-plus"></i> Thêm ảnh
                </div>
                <input type="file" id="addPhotoInput" accept="image/*" multiple style="display:none" onchange="uploadPhotos(this)">
            </div>

            <!-- 3. Video Memory Continuous Player -->
            <div class="adm-gf-card">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                    <span style="font-size:12px; font-weight:800; color:#f3e8ff;">VIDEO KỶ NIỆM 🎬</span>
                    <span style="font-size:10px; color:#a79bb7;">${(d.videos||[]).length} video</span>
                </div>
                ${videoPlayerHtml}
                <div class="adm-gf-add-btn" onclick="document.getElementById('addVideoInput').click()">
                    <i class="fa-solid fa-video"></i> Thêm video
                </div>
                <input type="file" id="addVideoInput" accept="video/*" style="display:none" onchange="uploadVideoFile(this)">
            </div>

            <!-- 4. Love Quote Bottom Card -->
            <div class="adm-quote-card">
                <div style="display:flex; align-items:flex-start; gap:6px;">
                    <span style="font-size:18px; line-height:1; color:#f472b6;">❝</span>
                    <div class="adm-quote-text">"${esc(d.quote)}"</div>
                </div>
                <i class="fa-solid fa-heart adm-quote-heart"></i>
            </div>
        `;
    }

    function renderVideoPlayerBox() {
        const d = coupleData;
        const vids = (d && d.videos) || [];
        if (vids.length === 0) {
            return `
            <div style="text-align:center; padding:30px 10px; color:#a79bb7; font-size:11px;">
                <i class="fa-solid fa-film" style="font-size:24px; color:#a855f7; margin-bottom:8px; display:block;"></i>
                Chưa có video nào. Nhấn "+ Thêm video" bên dưới để tải lên!
            </div>`;
        }

        if (currentVideoIdx >= vids.length) currentVideoIdx = 0;
        const cur = vids[currentVideoIdx];
        const total = vids.length;

        // Build story bars
        let storyBarsHtml = '';
        if (total > 1) {
            storyBarsHtml = '<div class="adm-video-story-bars">';
            for (let i = 0; i < total; i++) {
                const fillWidth = i < currentVideoIdx ? '100%' : (i === currentVideoIdx ? '0%' : '0%');
                storyBarsHtml += `
                    <div class="adm-video-story-bar" onclick="event.stopPropagation(); switchVideoTo(${i})" title="Video ${i+1}">
                        <div class="adm-video-story-fill" id="storyFill_${i}" style="width:${fillWidth};"></div>
                    </div>`;
            }
            storyBarsHtml += '</div>';
        }

        // Video tag or Static preview
        let mediaHtml = '';
        if (cur.url) {
            mediaHtml = `
                <video id="admMainVideo" src="${esc(cur.url)}" playsinline ${isVideoMuted ? 'muted' : ''} preload="auto"
                       onended="handleVideoEnded()"
                       ontimeupdate="handleVideoTimeUpdate(this)"
                       onplay="handleVideoPlayState(true)"
                       onpause="handleVideoPlayState(false)">
                </video>`;
        } else {
            mediaHtml = `
                <img src="${esc(cur.thumb || '/tkb/assets/img/anime_video_thumb.png')}" class="adm-video-thumb-static" alt="Video">
            `;
        }

        return `
        <div class="adm-video-player-box" id="admVideoBox">
            ${storyBarsHtml}
            
            <!-- Delete Button for current active video -->
            <button class="adm-video-del-btn" onclick="event.stopPropagation(); deleteVideo(${currentVideoIdx})" title="Xóa video này">
                <i class="fa-solid fa-trash"></i>
            </button>

            <!-- Nav Arrows (if > 1 video) -->
            ${total > 1 ? `
                <button class="adm-video-nav-arrow adm-video-nav-prev" onclick="event.stopPropagation(); prevCoupleVideo()" title="Video trước">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button class="adm-video-nav-arrow adm-video-nav-next" onclick="event.stopPropagation(); nextCoupleVideo(true)" title="Video tiếp theo">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            ` : ''}

            <!-- Video Element Area -->
            <div class="adm-video-element-wrap" onclick="togglePlayPause()">
                ${mediaHtml}
                <div class="adm-video-center-btn" id="admCenterPlayBtn" style="display:${isPlaying ? 'none' : 'flex'};">
                    <i class="fa-solid fa-play" style="margin-left:3px;"></i>
                </div>
            </div>

            <!-- Bottom Controls -->
            <div class="adm-video-bottom-bar">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span onclick="event.stopPropagation(); togglePlayPause()" style="cursor:pointer; display:inline-flex; width:16px; justify-content:center;">
                        <i class="fa-solid ${isPlaying ? 'fa-pause' : 'fa-play'}" id="admBottomPlayIcon"></i>
                    </span>
                    <span id="admVideoTimeText" style="font-size:10px; font-weight:600; color:#e9d5ff;">0:00 / 0:00</span>
                </div>
                
                <div style="display:flex; align-items:center; gap:8px;">
                    ${total > 1 ? `<span style="font-size:9.5px; color:#c084fc; font-weight:700; background:rgba(168,85,247,0.2); padding:1px 6px; border-radius:4px;"><i class="fa-solid fa-rotate" style="font-size:8px;"></i> Tự chuyển (${currentVideoIdx+1}/${total})</span>` : `<span style="font-size:9.5px; color:#c084fc; font-weight:700;"><i class="fa-solid fa-repeat"></i> Lặp liên tục</span>`}
                    <span onclick="event.stopPropagation(); toggleVideoMute()" style="cursor:pointer; font-size:11px;" title="Bật/Tắt âm thanh">
                        <i class="fa-solid ${isVideoMuted ? 'fa-volume-xmark' : 'fa-volume-high'}" id="admMuteIcon"></i>
                    </span>
                    <span onclick="event.stopPropagation(); toggleVideoFullscreen()" style="cursor:pointer; font-size:11px;" title="Toàn màn hình">
                        <i class="fa-solid fa-expand"></i>
                    </span>
                </div>
            </div>
        </div>`;
    }

    function esc(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ============ CONTINUOUS VIDEO LOGIC ============
    function startContinuousVideo() {
        setTimeout(() => {
            const vid = document.getElementById('admMainVideo');
            if (vid) {
                vid.muted = isVideoMuted;
                vid.play().then(() => {
                    isPlaying = true;
                    handleVideoPlayState(true);
                }).catch(e => {
                    console.log('Autoplay waiting for user interaction:', e);
                });
            }
        }, 300);
    }

    function handleVideoEnded() {
        nextCoupleVideo(true);
    }

    function nextCoupleVideo(autoStart = true) {
        const vids = (coupleData && coupleData.videos) || [];
        if (vids.length === 0) return;
        
        currentVideoIdx = (currentVideoIdx + 1) % vids.length;
        renderCoupleColumn();
        
        if (autoStart) {
            setTimeout(() => {
                const vid = document.getElementById('admMainVideo');
                if (vid) {
                    vid.muted = isVideoMuted;
                    vid.play().then(() => {
                        isPlaying = true;
                        handleVideoPlayState(true);
                    }).catch(e => {});
                }
            }, 100);
        }
    }

    function prevCoupleVideo() {
        const vids = (coupleData && coupleData.videos) || [];
        if (vids.length === 0) return;
        currentVideoIdx = (currentVideoIdx - 1 + vids.length) % vids.length;
        renderCoupleColumn();
        setTimeout(() => {
            const vid = document.getElementById('admMainVideo');
            if (vid) {
                vid.muted = isVideoMuted;
                vid.play().then(() => {
                    isPlaying = true;
                    handleVideoPlayState(true);
                }).catch(e => {});
            }
        }, 100);
    }

    function switchVideoTo(idx) {
        const vids = (coupleData && coupleData.videos) || [];
        if (idx >= 0 && idx < vids.length) {
            currentVideoIdx = idx;
            renderCoupleColumn();
            setTimeout(() => {
                const vid = document.getElementById('admMainVideo');
                if (vid) {
                    vid.muted = isVideoMuted;
                    vid.play().then(() => {
                        isPlaying = true;
                        handleVideoPlayState(true);
                    }).catch(e => {});
                }
            }, 100);
        }
    }

    function handleVideoTimeUpdate(video) {
        if (!video || !video.duration) return;
        const cur = video.currentTime;
        const dur = video.duration;
        const percent = (cur / dur) * 100;
        
        const fillEl = document.getElementById(`storyFill_${currentVideoIdx}`);
        if (fillEl) fillEl.style.width = `${percent}%`;
        
        const textEl = document.getElementById('admVideoTimeText');
        if (textEl) {
            textEl.textContent = `${formatTime(cur)} / ${formatTime(dur)}`;
        }
    }

    function formatTime(secs) {
        if (isNaN(secs) || secs < 0) return '0:00';
        const m = Math.floor(secs / 60);
        const s = Math.floor(secs % 60);
        return `${m}:${String(s).padStart(2, '0')}`;
    }

    function togglePlayPause() {
        const vid = document.getElementById('admMainVideo');
        if (!vid) return;
        if (vid.paused) {
            vid.play().then(() => {
                isPlaying = true;
                handleVideoPlayState(true);
            }).catch(e => {});
        } else {
            vid.pause();
            isPlaying = false;
            handleVideoPlayState(false);
        }
    }

    function toggleVideoMute() {
        const vid = document.getElementById('admMainVideo');
        isVideoMuted = !isVideoMuted;
        if (vid) vid.muted = isVideoMuted;
        const icon = document.getElementById('admMuteIcon');
        if (icon) {
            icon.className = isVideoMuted ? 'fa-solid fa-volume-xmark' : 'fa-solid fa-volume-high';
        }
        showCoupleToast(isVideoMuted ? '🔇 Đã tắt âm thanh' : '🔊 Đã bật âm thanh');
    }

    function toggleVideoFullscreen() {
        const box = document.getElementById('admVideoBox');
        if (!box) return;
        if (!document.fullscreenElement) {
            if (box.requestFullscreen) box.requestFullscreen().catch(err => {});
        } else {
            document.exitFullscreen();
        }
    }

    function handleVideoPlayState(playing) {
        isPlaying = playing;
        const centerBtn = document.getElementById('admCenterPlayBtn');
        const bottomIcon = document.getElementById('admBottomPlayIcon');
        if (centerBtn) centerBtn.style.display = playing ? 'none' : 'flex';
        if (bottomIcon) bottomIcon.className = playing ? 'fa-solid fa-pause' : 'fa-solid fa-play';
    }

    // ============ MODAL OPEN / CLOSE ============
    function openCoupleModal() {
        if (!coupleData) return;
        document.getElementById('cmName').value = coupleData.name || '';
        document.getElementById('cmBadge').value = coupleData.badge || '';
        document.getElementById('cmMessage').value = coupleData.message || '';
        document.getElementById('cmDate').value = coupleData.date || '';
        document.getElementById('cmDateLabel').value = coupleData.date_label || '';
        document.getElementById('cmQuote').value = coupleData.quote || '';
        document.getElementById('cmAvatarPreview').src = coupleData.avatar || '/tkb/assets/img/phuong_anh_avatar.png';
        document.getElementById('coupleModalOverlay').classList.add('active');
    }

    function closeCoupleModal() {
        document.getElementById('coupleModalOverlay').classList.remove('active');
    }

    document.getElementById('coupleModalOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeCoupleModal();
    });

    // ============ SAVE SETTINGS ============
    async function saveCoupleSettings() {
        const btn = document.getElementById('cmSaveBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang lưu...';

        coupleData.name = document.getElementById('cmName').value;
        coupleData.badge = document.getElementById('cmBadge').value;
        coupleData.message = document.getElementById('cmMessage').value;
        coupleData.date = document.getElementById('cmDate').value;
        coupleData.date_label = document.getElementById('cmDateLabel').value;
        coupleData.quote = document.getElementById('cmQuote').value;

        try {
            const res = await fetch('/tkb/api/couple_api.php?action=save', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(coupleData)
            });
            const json = await res.json();
            if (json.success && json.data) {
                coupleData = json.data;
                showCoupleToast('💜 Đã lưu thành công!');
                renderCoupleColumn();
                startContinuousVideo();
                closeCoupleModal();
            } else {
                showCoupleToast('❌ ' + (json.error || 'Lỗi khi lưu'));
            }
        } catch (e) {
            showCoupleToast('❌ Lỗi kết nối máy chủ');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Lưu thay đổi';
    }

    // ============ UPLOAD AVATAR ============
    async function uploadCoupleAvatar(input) {
        if (!input.files[0]) return;
        const file = input.files[0];
        const fd = new FormData();
        fd.append('file', file);
        
        try {
            const res = await fetch('/tkb/api/couple_api.php?action=upload_avatar', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success && json.data) {
                coupleData = json.data;
                document.getElementById('cmAvatarPreview').src = json.url;
                renderCoupleColumn();
                showCoupleToast('📷 Đã đổi ảnh đại diện!');
            } else {
                showCoupleToast('❌ ' + (json.error || 'Lỗi upload'));
            }
        } catch (e) {
            showCoupleToast('❌ Lỗi kết nối');
        }
        input.value = '';
    }

    // ============ UPLOAD PHOTOS ============
    async function uploadPhotos(input) {
        if (!input.files.length) return;
        showCoupleToast('⏳ Đang tải ảnh lên...');

        for (const file of input.files) {
            const fd = new FormData();
            fd.append('file', file);
            try {
                const res = await fetch('/tkb/api/couple_api.php?action=upload_photo', { method: 'POST', body: fd });
                const json = await res.json();
                if (json.success && json.data) {
                    coupleData = json.data;
                }
            } catch (e) {}
        }

        renderCoupleColumn();
        startContinuousVideo();
        showCoupleToast(`📷 Đã thêm ${input.files.length} ảnh!`);
        input.value = '';
    }

    // ============ DELETE PHOTO ============
    async function deletePhoto(index) {
        if (!confirm('Xóa ảnh này khỏi bộ sưu tập?')) return;
        try {
            const res = await fetch(`/tkb/api/couple_api.php?action=delete_photo&index=${index}`);
            const json = await res.json();
            if (json.success && json.data) {
                coupleData = json.data;
            } else {
                coupleData.photos.splice(index, 1);
            }
        } catch (e) {
            coupleData.photos.splice(index, 1);
        }
        renderCoupleColumn();
        startContinuousVideo();
        showCoupleToast('🗑️ Đã xóa ảnh');
    }

    // ============ UPLOAD VIDEO ============
    async function uploadVideoFile(input) {
        if (!input.files[0]) return;
        const file = input.files[0];
        showCoupleToast('⏳ Đang tải video lên máy chủ...');
        const fd = new FormData();
        fd.append('file', file);
        
        try {
            const res = await fetch('/tkb/api/couple_api.php?action=upload_video', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success && json.data) {
                coupleData = json.data;
                currentVideoIdx = coupleData.videos.length - 1; // point to new video
                renderCoupleColumn();
                setTimeout(() => {
                    const vid = document.getElementById('admMainVideo');
                    if (vid) {
                        vid.muted = isVideoMuted;
                        vid.play().then(() => {
                            isPlaying = true;
                            handleVideoPlayState(true);
                        }).catch(e => {});
                    }
                }, 200);
                showCoupleToast('🎬 Đã thêm video & đang phát!');
            } else {
                showCoupleToast('❌ ' + (json.error || 'Lỗi tải video lên'));
            }
        } catch (e) {
            showCoupleToast('❌ Lỗi kết nối khi tải video');
        }
        input.value = '';
    }

    // ============ DELETE VIDEO ============
    async function deleteVideo(index) {
        if (!confirm('Xóa video này khỏi danh sách kỷ niệm?')) return;
        try {
            const res = await fetch(`/tkb/api/couple_api.php?action=delete_video&index=${index}`);
            const json = await res.json();
            if (json.success && json.data) {
                coupleData = json.data;
            } else {
                coupleData.videos.splice(index, 1);
            }
        } catch (e) {
            coupleData.videos.splice(index, 1);
        }

        if (currentVideoIdx >= (coupleData.videos || []).length) {
            currentVideoIdx = Math.max(0, (coupleData.videos || []).length - 1);
        }
        renderCoupleColumn();
        startContinuousVideo();
        showCoupleToast('🗑️ Đã xóa video');
    }

    // ============ TOAST ============
    function showCoupleToast(msg) {
        const existing = document.querySelector('.cm-toast');
        if (existing) existing.remove();
        const el = document.createElement('div');
        el.className = 'cm-toast';
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3000);
    }

    // ============ INIT ============
    loadCoupleData();
    </script>
</body>
</html>