<?php require_once 'includes/public_header.php'; ?>

<style>
/* =========================================================
   INDEX PAGE – Indigo/Violet/Teal 2.0 — Full Redesign
   ========================================================= */

/* --- HERO ------------------------------------------------- */
.hero-v2 {
    min-height: 100vh;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
    background: #ffffff;
    padding: 120px 0 80px;
}

.hero-v2 .particles-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
    z-index: 0;
}

.hero-v2 .particle {
    position: absolute;
    border-radius: 50%;
    animation: floatParticle linear infinite;
    opacity: 0;
}

@keyframes floatParticle {
    0%   { transform: translateY(100vh) scale(0); opacity: 0; }
    10%  { opacity: 0.6; }
    90%  { opacity: 0.4; }
    100% { transform: translateY(-20vh) scale(1); opacity: 0; }
}

.hero-v2 .mesh-overlay {
    position: absolute;
    inset: 0;
    background: none;
    z-index: 0;
}

.hero-v2-inner {
    position: relative;
    z-index: 2;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 40px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

/* Left col */
.hero-badge-v2 {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 16px;
    background: rgba(217, 27, 67, 0.08);
    border: 1px solid rgba(217, 27, 67, 0.25);
    border-radius: 50px;
    color: var(--red-main);
    font-size: 12.5px;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin-bottom: 28px;
    backdrop-filter: blur(10px);
}

.hero-badge-v2 .badge-dot {
    width: 7px; height: 7px;
    background: var(--red-main);
    border-radius: 50%;
    animation: blink 2s ease infinite;
}

@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

.hero-title-v2 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(42px, 5vw, 72px);
    font-weight: 900;
    line-height: 1.12;
    color: var(--ink);
    margin-bottom: 24px;
    letter-spacing: -1px;
}

.hero-title-v2 .gradient-text {
    color: var(--red-main);
    display: block;
}

.hero-subtitle-v2 {
    font-size: 16.5px;
    color: var(--ink-muted);
    line-height: 1.75;
    margin-bottom: 40px;
    max-width: 520px;
}

.hero-cta-row {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 50px;
}

.btn-hero-primary {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 32px;
    background: #d91b43;
    color: #fff;
    border-radius: 50px;
    font-weight: 700;
    font-size: 15px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(217,27,67,0.4);
    position: relative;
    overflow: hidden;
}

.btn-hero-primary::before {
    content: '';
    position: absolute;
    inset: 0;
    background: #f43f6d;
    opacity: 0;
    transition: opacity 0.3s;
}
.btn-hero-primary:hover::before { opacity: 1; }
.btn-hero-primary:hover { transform: translateY(-3px); box-shadow: 0 14px 35px rgba(217,27,67,0.55); }
.btn-hero-primary span, .btn-hero-primary i { position: relative; z-index: 1; }

.btn-hero-secondary {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 32px;
    background: var(--red-pale);
    border: 1.5px solid rgba(217, 27, 67, 0.25);
    color: var(--red-main);
    border-radius: 50px;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}
.btn-hero-secondary:hover {
    background: var(--red-main);
    border-color: var(--red-main);
    color: #ffffff;
    transform: translateY(-2px);
}

/* Stats row */
.hero-stats-row {
    display: flex;
    gap: 28px;
    flex-wrap: wrap;
}

.hero-stat {
    display: flex;
    flex-direction: column;
}

.hero-stat-num {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    font-weight: 900;
    color: var(--red-main);
    line-height: 1;
}

.hero-stat-label {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

/* Right col – visual card */
.hero-visual-col {
    position: relative;
}

.hero-main-card {
    background: #ffffff;
    border: 1.5px solid rgba(217, 27, 67, 0.12);
    border-radius: 28px;
    overflow: visible;
    box-shadow: 0 20px 50px rgba(217, 27, 67, 0.08);
    aspect-ratio: 4/3;
    position: relative;
}

.hero-main-card img {
    width: 100%; height: 100%;
    object-fit: cover;
    opacity: 0.95;
    border-radius: 28px;
}

.hero-main-card::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 28px;
    background: none;
}

/* Floating info cards */
.float-card {
    position: absolute;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(217, 27, 67, 0.15);
    border-radius: 18px;
    padding: 14px 18px;
    box-shadow: 0 10px 30px rgba(217, 27, 67, 0.08);
    z-index: 10;
    animation: cardFloat 4s ease-in-out infinite;
}

.float-card-a { top: -20px; right: 30px; animation-delay: 0s; }
.float-card-b { bottom: 40px; left: -20px; animation-delay: 1.5s; }
.float-card-c { top: 40%; right: -30px; animation-delay: 0.8s; }

@keyframes cardFloat {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-8px); }
}

.float-card-inner { display: flex; align-items: center; gap: 12px; }
.float-card-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.fc-teal { background: rgba(217,27,67,0.08); color: #d91b43; border: 1px solid rgba(217,27,67,0.2); }
.fc-violet { background: rgba(217,27,67,0.08); color: #d91b43; border: 1px solid rgba(217,27,67,0.2); }
.fc-indigo { background: rgba(217,27,67,0.08); color: #d91b43; border: 1px solid rgba(217,27,67,0.2); }
.float-card-text strong { display: block; font-size: 16px; font-weight: 800; color: var(--ink); }
.float-card-text span { font-size: 11px; color: #64748b; font-weight: 600; }

/* ---- BENTO GRID SECTION ---------------------------------- */
.bento-v2-section {
    padding: 100px 0;
    background: #f0f4ff;
    position: relative;
    overflow: hidden;
}

.bento-v2-section::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: rgba(217,27,67,0.2);
}

.bento-v2-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-template-rows: auto;
    gap: 20px;
    margin-top: 60px;
}

/* TKB Widget – Wide + Tall */
.bv2-tkb  { grid-column: span 5; grid-row: span 2; }
/* AI Widget */
.bv2-ai   { grid-column: span 4; grid-row: span 2; }
/* Stats */
.bv2-stat { grid-column: span 3; }
/* Admissions Steps */
.bv2-adm  { grid-column: span 3; }
/* Quick links strip */
.bv2-ql   { grid-column: span 4; }
/* News feature */
.bv2-news { grid-column: span 5; }

.bv2-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 28px;
    border: 1px solid rgba(217,27,67,0.08);
    box-shadow: 0 4px 20px rgba(217,27,67,0.06);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
    position: relative;
}

.bv2-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(217,27,67,0.12);
}

.bv2-card-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 5px 12px;
    border-radius: 50px;
    margin-bottom: 16px;
}

.label-indigo { background: rgba(217,27,67,0.1); color: #d91b43; }
.label-violet { background: rgba(217,27,67,0.1); color: #d91b43; }
.label-teal   { background: rgba(217,27,67,0.1); color: #a8122e; }
.label-green  { background: rgba(52,211,153,0.1); color: #059669; }

.bv2-card-title {
    font-size: 18px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 6px;
}

/* TKB Widget inner */
.tkb-v2-select {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 13.5px;
    font-family: 'Outfit', sans-serif;
    color: #1e293b;
    background: #f8fafc;
    margin-bottom: 16px;
    outline: none;
    cursor: pointer;
    transition: border-color 0.2s;
}
.tkb-v2-select:focus { border-color: #d91b43; background: #fff; }

.tkb-v2-days {
    display: flex;
    gap: 6px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.tkb-v2-day-btn {
    padding: 6px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}
.tkb-v2-day-btn.active,
.tkb-v2-day-btn:hover {
    background: #d91b43;
    color: #fff;
    border-color: #d91b43;
}

.tkb-v2-list { display: flex; flex-direction: column; gap: 10px; max-height: 280px; overflow-y: auto; }
.tkb-v2-list::-webkit-scrollbar { width: 4px; }
.tkb-v2-list::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 4px; }

.tkb-v2-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 12px 14px;
    background: #f8fafc;
    border-radius: 14px;
    border: 1px solid rgba(217,27,67,0.1);
    transition: 0.2s;
}
.tkb-v2-item:hover { border-color: rgba(217,27,67,0.3); }

.tkb-v2-time {
    font-size: 11px;
    font-weight: 800;
    color: #d91b43;
    white-space: nowrap;
    background: rgba(217,27,67,0.1);
    padding: 4px 8px;
    border-radius: 6px;
    min-width: 60px;
    text-align: center;
}

.tkb-v2-info .tkb-v2-subject { font-size: 13px; font-weight: 700; color: #1e293b; }
.tkb-v2-info .tkb-v2-meta { font-size: 11px; color: #64748b; margin-top: 3px; }

.tkb-v2-empty {
    text-align: center;
    padding: 30px;
    color: #94a3b8;
    font-size: 13px;
}

/* AI Card */
.ai-v2-box {
    background: #ffffff;
    border-radius: 20px;
    padding: 24px;
    height: 100%;
    border: 1px solid rgba(217, 27, 67, 0.15);
    box-shadow: 0 4px 24px rgba(217, 27, 67, 0.05);
    color: var(--ink);
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}

.ai-v2-box::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 150px; height: 150px;
    background: radial-gradient(circle, rgba(217,27,67,0.4) 0%, transparent 60%);
}

.ai-v2-icon {
    width: 52px; height: 52px;
    background: rgba(217,27,67,0.2);
    border: 1.5px solid rgba(217,27,67,0.35);
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    margin-bottom: 16px;
    color: #c4b5fd;
}

.ai-v2-title { font-size: 20px; font-weight: 800; margin-bottom: 8px; color: var(--ink); }
.ai-v2-desc { font-size: 13px; color: var(--ink-muted); line-height: 1.6; margin-bottom: 20px; flex: 1; }

.ai-v2-prompts { display: flex; flex-direction: column; gap: 8px; }

.ai-v2-prompt-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: var(--red-pale);
    border: 1px solid rgba(217, 27, 67, 0.15);
    border-radius: 12px;
    color: var(--red-main);
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
}
.ai-v2-prompt-btn:hover {
    background: var(--red-main);
    border-color: var(--red-main);
    color: #ffffff;
    transform: translateX(4px);
}

/* Stats Card */
.stat-v2-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; }
.stat-v2-item {
    background: #f8fafc;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid rgba(217,27,67,0.1);
    text-align: center;
}
.stat-v2-num {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 900;
    color: #d91b43;
    line-height: 1;
}
.stat-v2-lbl { font-size: 11px; color: #64748b; font-weight: 700; margin-top: 4px; }

/* Admissions Steps */
.adm-v2-steps { display: flex; flex-direction: column; gap: 12px; margin-top: 12px; }
.adm-v2-step {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: #f8fafc;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
}
.adm-v2-num {
    width: 32px; height: 32px;
    background: #d91b43;
    color: #fff;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 800;
    flex-shrink: 0;
}
.adm-v2-txt { font-size: 13px; font-weight: 600; color: #334155; }
.btn-adm-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    margin-top: 14px;
    padding: 12px;
    background: #d91b43;
    color: #fff;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: opacity 0.2s, transform 0.2s;
}
.btn-adm-link:hover { opacity: 0.9; transform: translateY(-2px); }

/* Quick links */
.ql-v2-items { display: flex; flex-direction: column; gap: 8px; margin-top: 14px; }
.ql-v2-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f8fafc;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    text-decoration: none;
    transition: all 0.25s;
}
.ql-v2-item:hover { background: #eef2ff; border-color: rgba(217,27,67,0.25); transform: translateX(4px); }
.ql-v2-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
.ql-ic-a { background: rgba(217,27,67,0.12); color: #a8122e; }
.ql-ic-b { background: rgba(217,27,67,0.12); color: #d91b43; }
.ql-ic-c { background: rgba(245,158,11,0.12); color: #d97706; }
.ql-ic-d { background: rgba(239,68,68,0.12); color: #dc2626; }
.ql-v2-name { font-size: 13.5px; font-weight: 700; color: #334155; flex: 1; }
.ql-v2-arrow { color: #94a3b8; font-size: 12px; }

/* Featured news */
.news-v2-featured {
    border-radius: 18px;
    overflow: hidden;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    margin-top: 14px;
}
.news-v2-img { width: 100%; height: 180px; object-fit: cover; }
.news-v2-body { padding: 16px 18px; }
.news-v2-meta { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
.news-v2-tag { font-size: 10.5px; font-weight: 800; background: rgba(217,27,67,0.1); color: #d91b43; padding: 3px 10px; border-radius: 50px; }
.news-v2-date { font-size: 11px; color: #94a3b8; font-weight: 600; }
.news-v2-headline { font-size: 14.5px; font-weight: 800; color: #1e293b; line-height: 1.45; margin-bottom: 8px; }
.news-v2-excerpt { font-size: 12px; color: #64748b; line-height: 1.6; }

/* ---- EXPLORE SECTION ------------------------------------- */
.explore-v2 {
    padding: 100px 0;
    background: #ffffff;
    position: relative;
    overflow: hidden;
}

.explore-v2::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: rgba(217,27,67,0.25);
}

.explore-v2-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-top: 60px;
}

.explore-v2-card {
    background: #ffffff;
    border: 1px solid rgba(217, 27, 67, 0.12);
    border-radius: 28px;
    overflow: hidden;
    text-decoration: none;
    transition: all 0.4s ease;
    position: relative;
    group: true;
    box-shadow: 0 4px 20px rgba(217,27,67,0.04);
}

.explore-v2-card:hover {
    transform: translateY(-8px);
    border-color: rgba(217,27,67,0.4);
    box-shadow: 0 20px 40px rgba(217,27,67,0.12);
}

.explore-v2-img-wrap {
    position: relative;
    overflow: hidden;
    height: 200px;
}

.explore-v2-img-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
    filter: saturate(0.9) brightness(0.95);
}

.explore-v2-card:hover .explore-v2-img-wrap img {
    transform: scale(1.08);
    filter: saturate(1.1) brightness(1);
}

.explore-v2-overlay {
    position: absolute;
    inset: 0;
    background: transparent;
}

.explore-v2-img-badge {
    position: absolute;
    top: 16px; left: 16px;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.5px;
    backdrop-filter: blur(10px);
}

.eb-indigo { background: rgba(217,27,67,0.15); color: var(--red-main); border: 1px solid rgba(217,27,67,0.25); }
.eb-violet { background: rgba(217,27,67,0.15); color: var(--red-main); border: 1px solid rgba(217,27,67,0.25); }
.eb-teal   { background: rgba(217,27,67,0.15); color: var(--red-main); border: 1px solid rgba(217,27,67,0.25); }

.explore-v2-body {
    padding: 24px 28px;
}

.explore-v2-title { font-size: 20px; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
.explore-v2-desc { font-size: 13.5px; color: var(--ink-muted); line-height: 1.65; margin-bottom: 20px; }

.explore-v2-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #f43f6d;
    transition: all 0.2s;
}
.explore-v2-card:hover .explore-v2-link { color: #ff8fab; gap: 12px; }

/* ---- NEWS SECTION ---------------------------------------- */
.news-v2-section {
    padding: 100px 0;
    background: #f0f4ff;
}

.news-v2-cards-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-top: 60px;
}

.news-v2-card {
    background: #ffffff;
    border-radius: 24px;
    overflow: hidden;
    border: 1px solid rgba(217,27,67,0.08);
    box-shadow: 0 4px 20px rgba(217,27,67,0.05);
    transition: all 0.3s ease;
}

.news-v2-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(217,27,67,0.12);
    border-color: rgba(217,27,67,0.2);
}

.news-v2-card-img {
    width: 100%; height: 200px;
    object-fit: cover;
    display: block;
}

.news-v2-card-body { padding: 22px 24px; }
.news-v2-card-cat { font-size: 10.5px; font-weight: 800; color: #d91b43; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
.news-v2-card-title { font-size: 16px; font-weight: 800; color: #1e293b; line-height: 1.45; margin-bottom: 10px; }
.news-v2-card-date { font-size: 11.5px; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 6px; }

/* Section headers (dark bg) */
.sh-dark .section-tag { background: rgba(217,27,67,0.08); border-color: rgba(217,27,67,0.2); color: var(--red-main); }
.sh-dark .section-title { color: var(--ink); }
.sh-dark .section-desc { color: var(--ink-muted); }
.sh-dark .divider-line { background: #d91b43; }

/* Section headers (light bg) */
.sh-light .section-tag { background: rgba(217,27,67,0.1); border-color: rgba(217,27,67,0.2); color: #d91b43; }
.sh-light .section-title { color: #1e293b; }
.sh-light .section-desc { color: #64748b; }
.sh-light .divider-line { background: #d91b43; }

/* Responsive */
@media (max-width: 1100px) {
    .hero-v2-inner { grid-template-columns: 1fr; gap: 40px; }
    .hero-visual-col { display: none; }
    .bento-v2-grid { grid-template-columns: 1fr 1fr; }
    .bv2-tkb, .bv2-ai, .bv2-stat, .bv2-adm, .bv2-ql, .bv2-news { grid-column: span 1; grid-row: span 1; }
}

@media (max-width: 768px) {
    .bento-v2-grid { grid-template-columns: 1fr; }
    .explore-v2-grid { grid-template-columns: 1fr; }
    .news-v2-cards-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ===== HERO V2 ===== -->
<section class="hero-v2">
    <!-- Animated particles -->
    <div class="particles-bg" id="heroParticles"></div>
    <div class="mesh-overlay"></div>

    <div class="hero-v2-inner">
        <!-- Left: Text -->
        <div class="hero-text-col">
            <div class="hero-badge-v2">
                <span class="badge-dot"></span>
                Top 5 Trường Nghề Miền Tây · 2025
            </div>

            <h1 class="hero-title-v2">
                Kiến Tạo
                <span class="gradient-text">Tương Lai</span>
                Của Bạn
            </h1>

            <p class="hero-subtitle-v2">
                Khám phá lộ trình đào tạo nghề đẳng cấp quốc tế tại Cao Đẳng Nghề Việt Nam – Hàn Quốc Cà Mau. Nơi kỹ năng được mài giũa bởi chuyên gia và công nghệ tiên tiến nhất.
            </p>

            <div class="hero-cta-row">
                <a href="/tkb/tuyen_sinh.php" class="btn-hero-primary">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Đăng Ký Tuyển Sinh</span>
                </a>
                <a href="/tkb/gioi_thieu.php" class="btn-hero-secondary">
                    <i class="fas fa-compass"></i>
                    Khám Phá Trường
                </a>
            </div>

            <div class="hero-stats-row">
                <div class="hero-stat">
                    <span class="hero-stat-num">98%</span>
                    <span class="hero-stat-label">Có việc làm ngay</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-num">15+</span>
                    <span class="hero-stat-label">Ngành đào tạo</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-num">50+</span>
                    <span class="hero-stat-label">Đối tác doanh nghiệp</span>
                </div>
            </div>
        </div>

        <!-- Right: Visual -->
        <div class="hero-visual-col">
            <div class="hero-main-card">
                <img src="/tkb/assets/img/campus_showcase.png" alt="VKC Campus">

                <!-- Floating Cards -->
                <div class="float-card float-card-a">
                    <div class="float-card-inner">
                        <div class="float-card-icon fc-teal"><i class="fas fa-user-graduate"></i></div>
                        <div class="float-card-text">
                            <strong>98%</strong>
                            <span>Có việc làm ngay</span>
                        </div>
                    </div>
                </div>

                <div class="float-card float-card-b">
                    <div class="float-card-inner">
                        <div class="float-card-icon fc-violet"><i class="fas fa-briefcase"></i></div>
                        <div class="float-card-text">
                            <strong>50+ Đối tác</strong>
                            <span>Doanh nghiệp liên kết</span>
                        </div>
                    </div>
                </div>

                <div class="float-card float-card-c">
                    <div class="float-card-inner">
                        <div class="float-card-icon fc-indigo"><i class="fas fa-star"></i></div>
                        <div class="float-card-text">
                            <strong>Top 5</strong>
                            <span>Trường nghề miền Tây</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== BENTO V2 SECTION ===== -->
<section class="bento-v2-section">
    <div class="container">
        <div class="section-header centered sh-light">
            <div class="section-tag"><i class="fas fa-cubes"></i> Hệ sinh thái học tập</div>
            <h2 class="section-title">Công Cụ <em>Thông Minh</em> Của Bạn</h2>
            <div class="divider-line center"></div>
            <p class="section-desc">Tra cứu thời khóa biểu, trợ lý AI tuyển sinh và toàn bộ tiện ích học tập – tất cả trong một nơi.</p>
        </div>

        <div class="bento-v2-grid">
            <!-- TKB Widget -->
            <div class="bv2-card bv2-tkb">
                <div class="bv2-card-label label-indigo"><i class="fas fa-calendar-alt"></i> Thời Khóa Biểu</div>
                <div class="bv2-card-title">Tra Cứu Lịch Học Nhanh</div>
                <select id="tkbKhoaSelect" class="tkb-v2-select" onchange="fetchTkbData()">
                    <option value="Công Nghệ Thông Tin">Khoa Công Nghệ Thông Tin</option>
                    <option value="Cơ Khí Ô Tô">Khoa Cơ Khí Ô Tô</option>
                    <option value="Điện - Điện Tử">Khoa Điện - Điện Tử</option>
                    <option value="Quản Trị Doanh Nghiệp">Khoa Quản Trị Doanh Nghiệp</option>
                </select>
                <div class="tkb-v2-days">
                    <button class="tkb-v2-day-btn active" data-day="2" onclick="switchTkbDay(2)">Thứ 2</button>
                    <button class="tkb-v2-day-btn" data-day="3" onclick="switchTkbDay(3)">Thứ 3</button>
                    <button class="tkb-v2-day-btn" data-day="4" onclick="switchTkbDay(4)">Thứ 4</button>
                    <button class="tkb-v2-day-btn" data-day="5" onclick="switchTkbDay(5)">Thứ 5</button>
                    <button class="tkb-v2-day-btn" data-day="6" onclick="switchTkbDay(6)">Thứ 6</button>
                </div>
                <div class="tkb-v2-list" id="tkbListContainer">
                    <div class="tkb-v2-empty"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>
                </div>
            </div>

            <!-- AI Widget -->
            <div class="bv2-card bv2-ai" style="padding:0; background: transparent; border: none; box-shadow: none;">
                <div class="ai-v2-box" style="height:100%; box-sizing: border-box;">
                    <div class="ai-v2-icon"><i class="fas fa-robot"></i></div>
                    <div class="ai-v2-title">Trợ Lý AI Tuyển Sinh</div>
                    <p class="ai-v2-desc">Nhấp vào câu hỏi gợi ý để chat trực tiếp với AI và nhận phản hồi tức thì.</p>
                    <div class="ai-v2-prompts">
                        <button class="ai-v2-prompt-btn" onclick="askAiAssistant('Hồ sơ tuyển sinh khóa 2026 gồm những gì?')">
                            <span>Hồ sơ tuyển sinh gồm gì?</span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="ai-v2-prompt-btn" onclick="askAiAssistant('Học phí của các ngành học như thế nào?')">
                            <span>Học phí của trường?</span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="ai-v2-prompt-btn" onclick="askAiAssistant('Thời gian xét tuyển đợt 1 là khi nào?')">
                            <span>Thời gian xét tuyển?</span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="ai-v2-prompt-btn" onclick="askAiAssistant('Trường hỗ trợ gì cho sinh viên nhập học?')">
                            <span>Chính sách hỗ trợ?</span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="bv2-card bv2-stat">
                <div class="bv2-card-label label-teal"><i class="fas fa-chart-bar"></i> Thống Kê</div>
                <div class="stat-v2-grid">
                    <div class="stat-v2-item">
                        <div class="stat-v2-num">98%</div>
                        <div class="stat-v2-lbl">Có việc làm</div>
                    </div>
                    <div class="stat-v2-item">
                        <div class="stat-v2-num">15+</div>
                        <div class="stat-v2-lbl">Ngành học</div>
                    </div>
                    <div class="stat-v2-item" style="grid-column: span 2;">
                        <div class="stat-v2-num">50+</div>
                        <div class="stat-v2-lbl">Doanh nghiệp đối tác</div>
                    </div>
                </div>
            </div>

            <!-- Admissions Steps -->
            <div class="bv2-card bv2-adm">
                <div class="bv2-card-label label-violet"><i class="fas fa-route"></i> Tuyển Sinh</div>
                <div class="bv2-card-title">Quy Trình 3 Bước</div>
                <div class="adm-v2-steps">
                    <div class="adm-v2-step"><span class="adm-v2-num">1</span><span class="adm-v2-txt">Đăng ký trực tuyến</span></div>
                    <div class="adm-v2-step"><span class="adm-v2-num">2</span><span class="adm-v2-txt">Nộp hồ sơ xét tuyển</span></div>
                    <div class="adm-v2-step"><span class="adm-v2-num">3</span><span class="adm-v2-txt">Nhập học chính thức</span></div>
                </div>
                <a href="/tkb/tuyen_sinh.php" class="btn-adm-link">Xem chi tiết <i class="fas fa-arrow-right"></i></a>
            </div>

            <!-- Quick Links -->
            <div class="bv2-card bv2-ql">
                <div class="bv2-card-label label-green"><i class="fas fa-th-large"></i> Truy Cập Nhanh</div>
                <div class="ql-v2-items">
                    <a href="/tkb/tai_lieu.php" class="ql-v2-item">
                        <div class="ql-v2-icon ql-ic-a"><i class="fas fa-file-alt"></i></div>
                        <span class="ql-v2-name">Tài Liệu Học Tập</span>
                        <i class="fas fa-chevron-right ql-v2-arrow"></i>
                    </a>
                    <a href="/tkb/thu_vien.php" class="ql-v2-item">
                        <div class="ql-v2-icon ql-ic-b"><i class="fas fa-book"></i></div>
                        <span class="ql-v2-name">Thư Viện Số</span>
                        <i class="fas fa-chevron-right ql-v2-arrow"></i>
                    </a>
                    <a href="/tkb/su_kien.php" class="ql-v2-item">
                        <div class="ql-v2-icon ql-ic-c"><i class="fas fa-calendar-star"></i></div>
                        <span class="ql-v2-name">Sự Kiện & Hoạt Động</span>
                        <i class="fas fa-chevron-right ql-v2-arrow"></i>
                    </a>
                    <a href="/tkb/lien_he.php" class="ql-v2-item">
                        <div class="ql-v2-icon ql-ic-d"><i class="fas fa-phone-alt"></i></div>
                        <span class="ql-v2-name">Liên Hệ & Tư Vấn</span>
                        <i class="fas fa-chevron-right ql-v2-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Featured News -->
            <div class="bv2-card bv2-news">
                <div class="bv2-card-label label-indigo"><i class="fas fa-newspaper"></i> Nổi Bật</div>
                <div class="bv2-card-title">Tin Tức Mới Nhất</div>
                <div class="news-v2-featured">
                    <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=700&q=80" alt="Ngày hội doanh nghiệp" class="news-v2-img">
                    <div class="news-v2-body">
                        <div class="news-v2-meta">
                            <span class="news-v2-tag">Sự Kiện</span>
                            <span class="news-v2-date"><i class="fas fa-calendar"></i> 15/08/2026</span>
                        </div>
                        <div class="news-v2-headline">Ngày hội kết nối doanh nghiệp Việt – Hàn 2026</div>
                        <div class="news-v2-excerpt">Cơ hội để sinh viên tiếp cận các tập đoàn đa quốc gia và phỏng vấn trực tiếp tại trường.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== EXPLORE V2 ===== -->
<section class="explore-v2">
    <div class="container">
        <div class="section-header centered sh-dark">
            <div class="section-tag"><i class="fas fa-compass"></i> Khám Phá</div>
            <h2 class="section-title">Hành Trình <em style="color: #f43f6d;">Của Bạn</em> Bắt Đầu Tại Đây</h2>
            <div class="divider-line center"></div>
            <p class="section-desc">Mọi thông tin bạn cần để bắt đầu tương lai cùng Trường Cao Đẳng Nghề Việt Nam – Hàn Quốc Cà Mau.</p>
        </div>

        <div class="explore-v2-grid">
            <a href="/tkb/dao_tao.php" class="explore-v2-card">
                <div class="explore-v2-img-wrap">
                    <img src="/tkb/assets/img/major_cntt.png" alt="Chương trình đào tạo" loading="lazy">
                    <div class="explore-v2-overlay"></div>
                    <span class="explore-v2-img-badge eb-indigo">🎓 Đào Tạo</span>
                </div>
                <div class="explore-v2-body">
                    <div class="explore-v2-title">Chương Trình Đào Tạo</div>
                    <div class="explore-v2-desc">Đa dạng ngành nghề, bám sát thực tiễn công nghệ và kinh tế hiện đại. Học đi đôi với hành.</div>
                    <span class="explore-v2-link">Tìm hiểu thêm <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>

            <a href="/tkb/tuyen_sinh.php" class="explore-v2-card">
                <div class="explore-v2-img-wrap">
                    <img src="/tkb/assets/img/admission_bg.png" alt="Tuyển sinh" loading="lazy">
                    <div class="explore-v2-overlay"></div>
                    <span class="explore-v2-img-badge eb-violet">📋 Tuyển Sinh</span>
                </div>
                <div class="explore-v2-body">
                    <div class="explore-v2-title">Thông Tin Tuyển Sinh</div>
                    <div class="explore-v2-desc">Xét tuyển linh hoạt, cơ hội học bổng hấp dẫn và hỗ trợ toàn diện từ ngày đầu nhập học.</div>
                    <span class="explore-v2-link">Tìm hiểu thêm <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>

            <a href="/tkb/gioi_thieu.php" class="explore-v2-card">
                <div class="explore-v2-img-wrap">
                    <img src="/tkb/assets/img/bg_new.png" alt="Về nhà trường" loading="lazy">
                    <div class="explore-v2-overlay"></div>
                    <span class="explore-v2-img-badge eb-teal">🏛️ Về Trường</span>
                </div>
                <div class="explore-v2-body">
                    <div class="explore-v2-title">Về Cao đẳng Nghề VN – HQ Cà Mau</div>
                    <div class="explore-v2-desc">Khởi nguồn đam mê, kiến tạo tương lai tại môi trường giáo dục hàng đầu với tiêu chuẩn Hàn Quốc.</div>
                    <span class="explore-v2-link">Tìm hiểu thêm <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- ===== NEWS V2 ===== -->
<section class="news-v2-section">
    <div class="container">
        <div class="section-header centered sh-light">
            <div class="section-tag"><i class="fas fa-newspaper"></i> Tin Tức</div>
            <h2 class="section-title">Tin Tức & <em>Sự Kiện</em> Nổi Bật</h2>
            <div class="divider-line center"></div>
        </div>

        <div class="news-v2-cards-grid">
            <div class="news-v2-card">
                <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=700&q=80" alt="Ngày hội doanh nghiệp" class="news-v2-card-img" loading="lazy">
                <div class="news-v2-card-body">
                    <div class="news-v2-card-cat">🎯 Sự Kiện</div>
                    <div class="news-v2-card-title">Ngày hội kết nối doanh nghiệp Việt – Hàn 2026</div>
                    <div class="news-v2-card-date"><i class="fas fa-calendar"></i> 15 Tháng 8, 2026</div>
                </div>
            </div>
            <div class="news-v2-card">
                <img src="https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=700&q=80" alt="Hợp tác Toyota" class="news-v2-card-img" loading="lazy">
                <div class="news-v2-card-body">
                    <div class="news-v2-card-cat">🤝 Hợp Tác</div>
                    <div class="news-v2-card-title">Trường ký kết hợp tác đào tạo với Toyota Việt Nam</div>
                    <div class="news-v2-card-date"><i class="fas fa-calendar"></i> 10 Tháng 8, 2026</div>
                </div>
            </div>
            <div class="news-v2-card">
                <img src="https://images.unsplash.com/photo-1627556704290-2b1f5853ff78?w=700&q=80" alt="Lễ tốt nghiệp" class="news-v2-card-img" loading="lazy">
                <div class="news-v2-card-body">
                    <div class="news-v2-card-cat">🎓 Tốt Nghiệp</div>
                    <div class="news-v2-card-title">Lễ Tốt nghiệp Khóa 2022 – 2026 rực rỡ sắc màu</div>
                    <div class="news-v2-card-date"><i class="fas fa-calendar"></i> 05 Tháng 8, 2026</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== SCRIPTS ===== -->
<script>
// Particle generator
(function() {
    const container = document.getElementById('heroParticles');
    const colors = ['rgba(217,27,67,0.6)', 'rgba(217,27,67,0.5)', 'rgba(217,27,67,0.5)', 'rgba(217,27,67,0.4)'];
    for (let i = 0; i < 40; i++) {
        const el = document.createElement('div');
        el.classList.add('particle');
        const size = Math.random() * 4 + 1;
        el.style.cssText = `
            width: ${size}px; height: ${size}px;
            left: ${Math.random() * 100}%;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            animation-duration: ${Math.random() * 15 + 10}s;
            animation-delay: ${Math.random() * 10}s;
        `;
        container.appendChild(el);
    }
})();

// TKB Logic
let tkbCachedData = [];
let currentSelectedDay = 2;

async function fetchTkbData() {
    const select = document.getElementById('tkbKhoaSelect');
    const khoa = select.value;
    const container = document.getElementById('tkbListContainer');
    container.innerHTML = '<div class="tkb-v2-empty"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...</div>';
    try {
        const response = await fetch(`/tkb/api/get_public_tkb.php?khoa=${encodeURIComponent(khoa)}`);
        const result = await response.json();
        if (result.success) {
            tkbCachedData = result.data;
            renderSelectedDay();
        } else {
            container.innerHTML = `<div class="tkb-v2-empty">Không tìm thấy thời khóa biểu: ${result.message}</div>`;
        }
    } catch (error) {
        container.innerHTML = '<div class="tkb-v2-empty">Lỗi kết nối mạng!</div>';
    }
}

function switchTkbDay(dayNum) {
    currentSelectedDay = dayNum;
    document.querySelectorAll('.tkb-v2-day-btn').forEach(btn => {
        btn.classList.toggle('active', parseInt(btn.getAttribute('data-day')) === dayNum);
    });
    renderSelectedDay();
}

function renderSelectedDay() {
    const container = document.getElementById('tkbListContainer');
    const filtered = tkbCachedData.filter(item => item.thu === currentSelectedDay);
    if (filtered.length === 0) {
        container.innerHTML = '<div class="tkb-v2-empty"><i class="fas fa-umbrella"></i> Không có lịch học ngày này</div>';
        return;
    }
    let html = '';
    filtered.forEach(item => {
        html += `
        <div class="tkb-v2-item">
            <div class="tkb-v2-time">Tiết ${item.tiet_bat_dau}-${item.tiet_ket_thuc}</div>
            <div class="tkb-v2-info">
                <div class="tkb-v2-subject">${item.ten_mon}</div>
                <div class="tkb-v2-meta"><i class="fas fa-door-open"></i> ${item.phong_hoc} · <i class="fas fa-chalkboard-teacher"></i> ${item.ten_gv}</div>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

function askAiAssistant(questionText) {
    if (typeof cToggle === 'function') {
        const cbox = document.getElementById('cbox');
        if (cbox && !cbox.classList.contains('open')) { cToggle(); }
        const cinput = document.getElementById('cinput');
        if (cinput) {
            cinput.value = questionText;
            cinput.focus();
            setTimeout(() => { if (typeof cSend === 'function') cSend(); }, 300);
        }
    } else {
        alert("Khung Trợ Lý Chatbot AI chưa được tải xong, vui lòng thử lại sau!");
    }
}

document.addEventListener('DOMContentLoaded', () => { fetchTkbData(); });
</script>

<?php require_once 'includes/public_footer.php'; ?>