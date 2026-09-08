<?php
require_once '../config.php';
requireStudent();
require_once '../includes/student_nav.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trò chơi giải trí — Giảm Stress</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* =========================================================
   GAMES HUB – Premium Gaming Design System
   ========================================================= */

.games-page { padding: 10px 0 30px; }

.games-hero {
    background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 40%, #4c1d95 100%);
    border-radius: 20px;
    padding: 32px 36px 30px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    color: #fff;
    box-shadow: 0 10px 30px rgba(124, 58, 237, 0.3);
}

.games-hero::before {
    content: '';
    position: absolute;
    top: -50%; right: -15%;
    width: 380px; height: 380px;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.games-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(255,255,255,0.18);
    border-radius: 50px;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 14px;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.25);
}

.games-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 30px;
    font-weight: 900;
    margin: 0 0 8px;
    position: relative; z-index: 1;
}

.games-hero p {
    font-size: 14px;
    opacity: 0.9;
    margin: 0;
    position: relative; z-index: 1;
    max-width: 540px;
    line-height: 1.6;
}

/* --- Game Grid --- */
.game-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.game-card {
    background: var(--mc-card-bg, #1e1e2e);
    border: 2px solid var(--mc-border, #2a2a3e);
    border-radius: 18px;
    overflow: hidden;
    cursor: pointer !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    flex-direction: column;
    user-select: none;
    pointer-events: auto !important;
}

/* Pass-through clicks on child elements directly to parent .game-card */
.game-card * {
    pointer-events: none !important;
}

.game-card:hover {
    transform: translateY(-6px);
    border-color: #8b5cf6;
    box-shadow: 0 12px 35px rgba(124, 58, 237, 0.25);
}

.game-card-preview {
    height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 60px;
    position: relative;
    overflow: hidden;
}

.game-card-preview::after {
    content: '▶ CHƠI NGAY';
    position: absolute;
    padding: 10px 20px;
    background: rgba(124, 58, 237, 0.95);
    border-radius: 50px;
    font-size: 13px;
    font-weight: 800;
    color: #fff;
    letter-spacing: 0.5px;
    opacity: 0;
    transform: scale(0.85);
    transition: all 0.25s ease;
    backdrop-filter: blur(6px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    pointer-events: none !important;
}

.game-card:hover .game-card-preview::after {
    opacity: 1;
    transform: scale(1);
}

.game-card-body {
    padding: 18px 20px 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.game-card-body h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--mc-text, #f1f5f9);
    margin: 0 0 6px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.game-card-body p {
    font-size: 13px;
    color: var(--mc-text-muted, #94a3b8);
    margin: 0 0 14px;
    line-height: 1.5;
    flex: 1;
}

.game-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    width: fit-content;
}

.tag-easy { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
.tag-medium { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
.tag-hard { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
.tag-relax { background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3); }

/* --- Game Modal --- */
.game-modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
    width: 100vw !important; height: 100vh !important;
    background: rgba(10, 10, 20, 0.88) !important;
    z-index: 999999 !important;
    display: none;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(8px);
    padding: 15px;
    box-sizing: border-box;
}

.game-modal-overlay.active { display: flex !important; animation: fadeIn 0.2s ease forwards; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.game-modal {
    background: #141425;
    border-radius: 24px;
    border: 2px solid #2e2e4a;
    width: 100%;
    max-width: 580px;
    max-height: 94vh;
    overflow-y: auto;
    position: relative;
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 25px 70px rgba(0,0,0,0.8);
    pointer-events: auto !important;
}

.game-modal * {
    pointer-events: auto !important;
}

@keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

.game-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #26263e;
    background: rgba(255,255,255,0.02);
}

.game-modal-header h2 {
    font-size: 19px;
    font-weight: 800;
    color: #f1f5f9;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.game-modal-close {
    width: 38px; height: 38px;
    border-radius: 12px;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
    font-size: 20px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
}

.game-modal-close:hover { background: rgba(239, 68, 68, 0.3); transform: scale(1.08); }

.game-modal-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.game-score-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    margin-bottom: 18px;
    gap: 12px;
}

.score-box {
    background: #0d0d1a;
    border: 1px solid #26263e;
    border-radius: 14px;
    padding: 10px 14px;
    text-align: center;
    flex: 1;
}

.score-box .label {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
}

.score-box .value {
    font-size: 22px;
    font-weight: 900;
    color: #fbbf24;
    margin-top: 2px;
}

.game-btn {
    padding: 11px 26px;
    border-radius: 14px;
    border: none;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.game-btn-primary {
    background: linear-gradient(135deg, #7c3aed, #5b21b6);
    color: #fff;
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.35);
}

.game-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(124, 58, 237, 0.5); }
.game-btn-primary:active { transform: translateY(0); }

.game-btn-secondary {
    background: #1e293b;
    color: #94a3b8;
    border: 1px solid #334155;
}

.game-btn-secondary:hover { background: #334155; color: #e2e8f0; }

.game-msg {
    text-align: center;
    padding: 10px 14px;
    font-size: 14px;
    font-weight: 700;
    color: #cbd5e1;
    min-height: 24px;
    margin-top: 8px;
}

/* Common Game Container Overlay */
.game-canvas-wrap {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.5);
    border: 2px solid #3b3b5c;
    background: #0a0a14;
}

.game-over-overlay {
    position: absolute;
    inset: 0;
    background: rgba(10, 10, 20, 0.88);
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
    backdrop-filter: blur(4px);
    animation: fadeIn 0.2s ease;
}

.game-over-overlay.active { display: flex; }

.game-over-overlay h3 {
    font-size: 26px;
    font-weight: 900;
    color: #f87171;
    margin: 0 0 6px;
    text-shadow: 0 0 15px rgba(248, 113, 113, 0.4);
}

.game-over-overlay p {
    font-size: 14px;
    color: #94a3b8;
    margin: 0 0 16px;
}

.game-over-overlay .final-score {
    font-size: 38px;
    font-weight: 900;
    color: #fbbf24;
    margin-bottom: 18px;
    text-shadow: 0 0 20px rgba(251, 191, 36, 0.4);
}

/* ============ 1. SNAKE GAME ============ */
#snakeCanvas {
    background: #0d0d1a;
    display: block;
    touch-action: none;
}

.snake-controls {
    display: grid;
    grid-template-columns: repeat(3, 48px);
    grid-template-rows: repeat(2, 48px);
    gap: 8px;
    margin-top: 14px;
}

.snake-controls button {
    width: 48px; height: 48px;
    border-radius: 12px;
    background: #1e1e34;
    border: 1px solid #363654;
    color: #e2e8f0;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.15s;
    display: flex; align-items: center; justify-content: center;
    touch-action: manipulation;
}

.snake-controls button:hover, .snake-controls button:active { background: #7c3aed; border-color: #8b5cf6; transform: scale(0.95); }

/* ============ 2. 2048 GAME ============ */
.g2048-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    padding: 12px;
    background: #0d0d1a;
    border-radius: 16px;
    border: 2px solid #2e2e4a;
    width: 320px;
    aspect-ratio: 1;
    touch-action: none;
    box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);
}

.tile-cell {
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 24px;
    transition: all 0.12s ease;
    aspect-ratio: 1;
    user-select: none;
}

.tile-v-0    { background: #161626; color: transparent; }
.tile-v-2    { background: #e0e7ff; color: #3730a3; }
.tile-v-4    { background: #c7d2fe; color: #3730a3; }
.tile-v-8    { background: #f59e0b; color: #ffffff; box-shadow: 0 0 10px rgba(245,158,11,0.3); }
.tile-v-16   { background: #f97316; color: #ffffff; box-shadow: 0 0 12px rgba(249,115,22,0.3); }
.tile-v-32   { background: #ef4444; color: #ffffff; box-shadow: 0 0 12px rgba(239,68,68,0.3); }
.tile-v-64   { background: #dc2626; color: #ffffff; box-shadow: 0 0 14px rgba(220,38,38,0.4); }
.tile-v-128  { background: #8b5cf6; color: #ffffff; font-size: 20px; box-shadow: 0 0 15px rgba(139,92,246,0.4); }
.tile-v-256  { background: #7c3aed; color: #ffffff; font-size: 20px; box-shadow: 0 0 18px rgba(124,58,237,0.5); }
.tile-v-512  { background: #6d28d9; color: #ffffff; font-size: 20px; box-shadow: 0 0 20px rgba(109,40,217,0.5); }
.tile-v-1024 { background: #5b21b6; color: #ffffff; font-size: 16px; box-shadow: 0 0 22px rgba(91,33,182,0.6); }
.tile-v-2048 { background: linear-gradient(135deg, #f59e0b, #ef4444); color: #ffffff; font-size: 16px; box-shadow: 0 0 25px rgba(245,158,11,0.6); }
.tile-v-super{ background: linear-gradient(135deg, #ec4899, #8b5cf6); color: #ffffff; font-size: 14px; box-shadow: 0 0 25px rgba(236,72,153,0.7); }

.tile-pop { animation: tilePop 0.15s ease; }
@keyframes tilePop { 0% { transform: scale(0.5); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }

/* ============ 3. MEMORY GAME ============ */
.memory-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    width: 330px;
    perspective: 1000px;
}

.memory-card {
    aspect-ratio: 1;
    border-radius: 12px;
    cursor: pointer;
    position: relative;
    transform-style: preserve-3d;
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
}

.memory-card.flipped { transform: rotateY(180deg); }
.memory-card.matched { opacity: 0.65; pointer-events: none; }
.memory-card.matched .card-back { border-color: #10b981; box-shadow: 0 0 15px rgba(16, 185, 129, 0.4); }

.memory-card .card-front,
.memory-card .card-back {
    position: absolute;
    inset: 0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.memory-card .card-front {
    background: linear-gradient(135deg, #7c3aed, #5b21b6);
    border: 2px solid #8b5cf6;
    color: #fff;
    font-size: 22px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.memory-card .card-back {
    background: #0d0d1a;
    border: 2px solid #2e2e4a;
    font-size: 32px;
    transform: rotateY(180deg);
}

/* ============ 4. WHACK-A-MOLE ============ */
.mole-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    width: 320px;
}

.mole-hole {
    aspect-ratio: 1;
    background: #141426;
    border-radius: 50%;
    border: 3px solid #2e2e4a;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    transition: all 0.15s;
    user-select: none;
    position: relative;
    overflow: hidden;
    touch-action: manipulation;
}

.mole-hole:hover { border-color: #7c3aed; }

.mole-hole.active {
    background: #064e3b;
    border-color: #10b981;
    animation: molePopUp 0.18s ease;
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
}

.mole-hole.gold {
    background: #78350f;
    border-color: #fbbf24;
    box-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
}

.mole-hole.bomb {
    background: #450a0a;
    border-color: #ef4444;
    box-shadow: 0 0 20px rgba(239, 68, 68, 0.5);
}

.mole-hole.hit {
    background: #581c87;
    border-color: #c084fc;
    transform: scale(0.92);
}

@keyframes molePopUp {
    0% { transform: scale(0.5); }
    70% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.mole-float-score {
    position: absolute;
    font-weight: 900;
    font-size: 18px;
    animation: floatUp 0.6s ease forwards;
    pointer-events: none;
    z-index: 5;
}

@keyframes floatUp {
    0% { opacity: 1; transform: translateY(0); }
    100% { opacity: 0; transform: translateY(-30px); }
}

/* ============ 5. TIC TAC TOE ============ */
.ttt-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    width: 290px;
}

.ttt-cell {
    aspect-ratio: 1;
    background: #0d0d1a;
    border: 2px solid #2e2e4a;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 38px;
    font-weight: 900;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}

.ttt-cell:hover:not(.taken) { border-color: #7c3aed; background: #1a1a30; }

.ttt-cell.x { color: #8b5cf6; text-shadow: 0 0 15px rgba(139,92,246,0.5); }
.ttt-cell.o { color: #fbbf24; text-shadow: 0 0 15px rgba(251,191,36,0.5); }
.ttt-cell.win { background: rgba(16, 185, 129, 0.2); border-color: #10b981; animation: pulse 0.5s ease infinite alternate; }

@keyframes pulse { from { opacity: 0.7; } to { opacity: 1; } }

/* ============ 6. DINO RUNNER ============ */
.dino-canvas-wrap {
    width: 100%;
    max-width: 480px;
    aspect-ratio: 5 / 2;
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    border: 2px solid #2e2e4a;
    background: #0d0d1a;
    touch-action: none;
    cursor: pointer;
}

#dinoCanvas {
    width: 100%;
    height: 100%;
    display: block;
}

/* Responsive fixes */
@media (max-width: 600px) {
    .game-grid { grid-template-columns: 1fr; }
    .games-hero { padding: 24px 20px; }
    .games-hero h1 { font-size: 24px; }
    .game-modal { width: 100%; max-height: 96vh; border-radius: 18px; }
    .game-modal-body { padding: 16px; }
    .g2048-grid, .memory-grid, .mole-grid, .ttt-grid { width: 280px; }
    .g2048-grid { gap: 6px; }
    .tile-cell { font-size: 20px; }
}
</style>
</head>
<body>

<div class="games-page">

    <!-- Hero Header -->
    <div class="games-hero">
        <div class="games-hero-badge"><i class="fas fa-gamepad"></i> Khu Giải Trí Sinh Viên</div>
        <h1>🎮 Trò Chơi Giảm Stress</h1>
        <p>Thư giãn nhẹ nhàng giữa các giờ học với những mini-game hấp dẫn. Thử thách bản thân và lập kỷ lục điểm số cao nhất!</p>
    </div>

    <!-- Game Grid Cards -->
    <div class="game-grid">

        <!-- 1. Snake -->
        <div class="game-card" data-game="snake" onclick="openGame('snake')">
            <div class="game-card-preview" style="background: linear-gradient(135deg, #064e3b, #065f46);">🐍</div>
            <div class="game-card-body">
                <h3>Rắn Săn Mồi <i class="fas fa-chevron-right" style="font-size:13px; opacity:0.5;"></i></h3>
                <p>Điều khiển rắn ăn thức ăn và dài ra. Nhớ tránh đâm vào tường hoặc thân mình!</p>
                <span class="game-tag tag-easy"><i class="fas fa-smile"></i> Dễ chơi</span>
            </div>
        </div>

        <!-- 2. 2048 -->
        <div class="game-card" data-game="g2048" onclick="openGame('g2048')">
            <div class="game-card-preview" style="background: linear-gradient(135deg, #7c2d12, #9a3412);">🧩</div>
            <div class="game-card-body">
                <h3>2048 <i class="fas fa-chevron-right" style="font-size:13px; opacity:0.5;"></i></h3>
                <p>Gộp các ô số giống nhau để ghép thành ô 2048. Trò chơi tư duy cực kỳ gây nghiện!</p>
                <span class="game-tag tag-medium"><i class="fas fa-brain"></i> Trung bình</span>
            </div>
        </div>

        <!-- 3. Memory -->
        <div class="game-card" data-game="memory" onclick="openGame('memory')">
            <div class="game-card-preview" style="background: linear-gradient(135deg, #4c1d95, #5b21b6);">🃏</div>
            <div class="game-card-body">
                <h3>Lật Thẻ Ghi Nhớ <i class="fas fa-chevron-right" style="font-size:13px; opacity:0.5;"></i></h3>
                <p>Lật mở và tìm 8 cặp thẻ giống nhau với số lượt lật ít nhất có thể.</p>
                <span class="game-tag tag-relax"><i class="fas fa-spa"></i> Thư giãn</span>
            </div>
        </div>

        <!-- 4. Whack-a-Mole -->
        <div class="game-card" data-game="mole" onclick="openGame('mole')">
            <div class="game-card-preview" style="background: linear-gradient(135deg, #713f12, #854d0e);">🔨</div>
            <div class="game-card-body">
                <h3>Đập Chuột Chũi <i class="fas fa-chevron-right" style="font-size:13px; opacity:0.5;"></i></h3>
                <p>Phản xạ cực nhanh! Đập chuột chũi và né bom để ghi nhiều điểm nhất trong 30 giây.</p>
                <span class="game-tag tag-easy"><i class="fas fa-bolt"></i> Dễ chơi</span>
            </div>
        </div>

        <!-- 5. Tic Tac Toe -->
        <div class="game-card" data-game="ttt" onclick="openGame('ttt')">
            <div class="game-card-preview" style="background: linear-gradient(135deg, #1e3a5f, #1e40af);">❌⭕</div>
            <div class="game-card-body">
                <h3>Cờ Caro XO <i class="fas fa-chevron-right" style="font-size:13px; opacity:0.5;"></i></h3>
                <p>Đấu trí cùng AI! Tạo hàng 3 ô liên tiếp để giành chiến thắng thuyết phục.</p>
                <span class="game-tag tag-medium"><i class="fas fa-chess"></i> Trung bình</span>
            </div>
        </div>

        <!-- 6. Dino Runner -->
        <div class="game-card" data-game="dino" onclick="openGame('dino')">
            <div class="game-card-preview" style="background: linear-gradient(135deg, #374151, #4b5563);">🦕</div>
            <div class="game-card-body">
                <h3>Khủng Long Chạy <i class="fas fa-chevron-right" style="font-size:13px; opacity:0.5;"></i></h3>
                <p>Điều khiển khủng long nhảy qua xương bo và chim săn mồi. Càng lâu càng nhanh!</p>
                <span class="game-tag tag-hard"><i class="fas fa-fire"></i> Thử thách</span>
            </div>
        </div>

    </div>
</div>

<!-- ============ GAME MODALS ============ -->

<!-- 1. SNAKE Modal -->
<div class="game-modal-overlay" id="modal-snake">
    <div class="game-modal">
        <div class="game-modal-header">
            <h2>🐍 Rắn Săn Mồi</h2>
            <button type="button" class="game-modal-close" onclick="closeGame('snake')">&times;</button>
        </div>
        <div class="game-modal-body">
            <div class="game-score-bar">
                <div class="score-box"><div class="label">Điểm số</div><div class="value" id="snakeScore">0</div></div>
                <div class="score-box"><div class="label">Kỷ lục</div><div class="value" id="snakeBest">0</div></div>
            </div>

            <div class="game-canvas-wrap">
                <canvas id="snakeCanvas" width="320" height="320"></canvas>
                <div class="game-over-overlay" id="snakeGameOver">
                    <h3>💀 GAME OVER</h3>
                    <p>Bạn đã chạm vào chướng ngại vật!</p>
                    <div class="final-score" id="snakeFinalScore">0</div>
                    <button type="button" class="game-btn game-btn-primary" onclick="startSnake()"><i class="fas fa-redo"></i> Chơi lại</button>
                </div>
            </div>

            <div class="snake-controls">
                <div></div>
                <button type="button" onclick="snakeDir('up')"><i class="fas fa-chevron-up"></i></button>
                <div></div>
                <button type="button" onclick="snakeDir('left')"><i class="fas fa-chevron-left"></i></button>
                <button type="button" onclick="snakeDir('down')"><i class="fas fa-chevron-down"></i></button>
                <button type="button" onclick="snakeDir('right')"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="game-msg">Dùng nút bấm, phím mũi tên (WASD) hoặc vuốt màn hình</div>
        </div>
    </div>
</div>

<!-- 2. 2048 Modal -->
<div class="game-modal-overlay" id="modal-g2048">
    <div class="game-modal">
        <div class="game-modal-header">
            <h2>🧩 2048</h2>
            <button type="button" class="game-modal-close" onclick="closeGame('g2048')">&times;</button>
        </div>
        <div class="game-modal-body">
            <div class="game-score-bar">
                <div class="score-box"><div class="label">Điểm số</div><div class="value" id="g2048Score">0</div></div>
                <div class="score-box"><div class="label">Kỷ lục</div><div class="value" id="g2048Best">0</div></div>
            </div>

            <div style="position:relative;">
                <div class="g2048-grid" id="grid2048"></div>
                <div class="game-over-overlay" id="g2048GameOver">
                    <h3 id="g2048OverTitle">💀 GAME OVER</h3>
                    <p id="g2048OverDesc">Không còn nước đi nào khả thi!</p>
                    <div class="final-score" id="g2048FinalScore">0</div>
                    <button type="button" class="game-btn game-btn-primary" onclick="start2048()"><i class="fas fa-redo"></i> Chơi lại</button>
                </div>
            </div>

            <div class="game-msg" id="g2048Msg">Vuốt trên màn hình hoặc dùng phím mũi tên / WASD</div>
            <div style="margin-top:10px;">
                <button type="button" class="game-btn game-btn-primary" onclick="start2048()"><i class="fas fa-redo"></i> Ván mới</button>
            </div>
        </div>
    </div>
</div>

<!-- 3. MEMORY Modal -->
<div class="game-modal-overlay" id="modal-memory">
    <div class="game-modal">
        <div class="game-modal-header">
            <h2>🃏 Lật Thẻ Ghi Nhớ</h2>
            <button type="button" class="game-modal-close" onclick="closeGame('memory')">&times;</button>
        </div>
        <div class="game-modal-body">
            <div class="game-score-bar">
                <div class="score-box"><div class="label">Lượt lật</div><div class="value" id="memMoves">0</div></div>
                <div class="score-box"><div class="label">Đã ghép</div><div class="value" id="memMatched">0/8</div></div>
                <div class="score-box"><div class="label">Kỷ lục</div><div class="value" id="memBest">-</div></div>
            </div>

            <div style="position:relative;">
                <div class="memory-grid" id="memoryGrid"></div>
                <div class="game-over-overlay" id="memoryGameOver">
                    <h3>🎉 CHIẾN THẮNG!</h3>
                    <p>Bạn đã hoàn thành trò chơi tuyệt vời!</p>
                    <div class="final-score" id="memoryFinalScore">0 Lượt</div>
                    <button type="button" class="game-btn game-btn-primary" onclick="startMemory()"><i class="fas fa-redo"></i> Chơi lại</button>
                </div>
            </div>

            <div class="game-msg" id="memMsg">Hãy ghép đúng 8 cặp thẻ bài giống nhau!</div>
            <div style="margin-top:10px;">
                <button type="button" class="game-btn game-btn-primary" onclick="startMemory()"><i class="fas fa-redo"></i> Ván mới</button>
            </div>
        </div>
    </div>
</div>

<!-- 4. WHACK-A-MOLE Modal -->
<div class="game-modal-overlay" id="modal-mole">
    <div class="game-modal">
        <div class="game-modal-header">
            <h2>🔨 Đập Chuột Chũi</h2>
            <button type="button" class="game-modal-close" onclick="closeGame('mole')">&times;</button>
        </div>
        <div class="game-modal-body">
            <div class="game-score-bar">
                <div class="score-box"><div class="label">Điểm số</div><div class="value" id="moleScore">0</div></div>
                <div class="score-box"><div class="label">Thời gian</div><div class="value" id="moleTime">30s</div></div>
                <div class="score-box"><div class="label">Kỷ lục</div><div class="value" id="moleBest">0</div></div>
            </div>

            <div style="position:relative;">
                <div class="mole-grid" id="moleGrid">
                    <div class="mole-hole" onclick="hitMole(0)"></div>
                    <div class="mole-hole" onclick="hitMole(1)"></div>
                    <div class="mole-hole" onclick="hitMole(2)"></div>
                    <div class="mole-hole" onclick="hitMole(3)"></div>
                    <div class="mole-hole" onclick="hitMole(4)"></div>
                    <div class="mole-hole" onclick="hitMole(5)"></div>
                    <div class="mole-hole" onclick="hitMole(6)"></div>
                    <div class="mole-hole" onclick="hitMole(7)"></div>
                    <div class="mole-hole" onclick="hitMole(8)"></div>
                </div>
                <div class="game-over-overlay" id="moleGameOver">
                    <h3>⏰ HẾT GIỜ!</h3>
                    <p>Trò chơi kết thúc</p>
                    <div class="final-score" id="moleFinalScore">0</div>
                    <button type="button" class="game-btn game-btn-primary" onclick="startMole()"><i class="fas fa-play"></i> Chơi lại</button>
                </div>
            </div>

            <div class="game-msg" id="moleMsg">🐹 Chuột: +1 điểm | 🌟 Vàng: +3 điểm | 💣 Bom: -2 điểm</div>
            <div style="margin-top:10px;">
                <button type="button" class="game-btn game-btn-primary" onclick="startMole()"><i class="fas fa-play"></i> Bắt đầu chơi</button>
            </div>
        </div>
    </div>
</div>

<!-- 5. TIC TAC TOE Modal -->
<div class="game-modal-overlay" id="modal-ttt">
    <div class="game-modal">
        <div class="game-modal-header">
            <h2>❌⭕ Cờ Caro XO</h2>
            <button type="button" class="game-modal-close" onclick="closeGame('ttt')">&times;</button>
        </div>
        <div class="game-modal-body">
            <div class="game-score-bar">
                <div class="score-box"><div class="label">Bạn (X)</div><div class="value" id="tttX">0</div></div>
                <div class="score-box"><div class="label">Hòa</div><div class="value" id="tttD">0</div></div>
                <div class="score-box"><div class="label">Máy (O)</div><div class="value" id="tttO">0</div></div>
            </div>

            <div class="ttt-grid" id="tttGrid"></div>
            <div class="game-msg" id="tttMsg">Bạn đi trước (X)!</div>
            <div style="margin-top:12px;">
                <button type="button" class="game-btn game-btn-primary" onclick="startTTT()"><i class="fas fa-redo"></i> Ván mới</button>
            </div>
        </div>
    </div>
</div>

<!-- 6. DINO RUNNER Modal -->
<div class="game-modal-overlay" id="modal-dino">
    <div class="game-modal">
        <div class="game-modal-header">
            <h2>🦕 Khủng Long Chạy</h2>
            <button type="button" class="game-modal-close" onclick="closeGame('dino')">&times;</button>
        </div>
        <div class="game-modal-body">
            <div class="game-score-bar">
                <div class="score-box"><div class="label">Điểm số</div><div class="value" id="dinoScore">0</div></div>
                <div class="score-box"><div class="label">Kỷ lục</div><div class="value" id="dinoBest">0</div></div>
            </div>

            <div class="dino-canvas-wrap" id="dinoWrap">
                <canvas id="dinoCanvas" width="500" height="200"></canvas>
                <div class="game-over-overlay" id="dinoGameOver">
                    <h3>💀 GAME OVER</h3>
                    <p>Khủng long đã va chạm chướng ngại vật!</p>
                    <div class="final-score" id="dinoFinalScore">0</div>
                    <button type="button" class="game-btn game-btn-primary" onclick="startDino()"><i class="fas fa-redo"></i> Chơi lại</button>
                </div>
            </div>

            <div class="game-msg" id="dinoMsg">Nhấn phím Space / Mũi tên lên / Chạm màn hình để Nhảy</div>
            <div style="margin-top:10px;">
                <button type="button" class="game-btn game-btn-primary" onclick="startDino()"><i class="fas fa-play"></i> Bắt đầu</button>
            </div>
        </div>
    </div>
</div>

<script>
// ============ MODAL & GAME CONTROLLER ============
let currentGame = null;

function openGame(name) {
    const modal = document.getElementById('modal-' + name);
    if (!modal) return;

    // Move modal element to document.body to break out of any parent CSS stacking context or overflow:hidden
    if (modal.parentNode !== document.body) {
        document.body.appendChild(modal);
    }

    currentGame = name;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Start respective game
    if (name === 'snake') startSnake();
    else if (name === 'g2048') start2048();
    else if (name === 'memory') startMemory();
    else if (name === 'mole') startMole();
    else if (name === 'ttt') startTTT();
    else if (name === 'dino') startDino();
}

function closeGame(name) {
    const modal = document.getElementById('modal-' + name);
    if (modal) modal.classList.remove('active');
    document.body.style.overflow = '';
    currentGame = null;

    // Clean up game loops and timers
    if (name === 'snake' && snakeInterval) clearInterval(snakeInterval);
    if (name === 'mole') stopMole();
    if (name === 'ttt' && tttAITimeout) clearTimeout(tttAITimeout);
    if (name === 'dino' && dinoRAF) cancelAnimationFrame(dinoRAF);
}

// Add event delegation for robust card clicking
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.game-card').forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const game = this.getAttribute('data-game');
            if (game) openGame(game);
        });
    });

    document.querySelectorAll('.game-modal-overlay').forEach(el => {
        el.addEventListener('click', function(e) {
            if (e.target === this) {
                const name = this.id.replace('modal-', '');
                closeGame(name);
            }
        });
    });
});

// Backup click listener in case DOMContentLoaded already fired
document.querySelectorAll('.game-card').forEach(card => {
    card.addEventListener('click', function(e) {
        const game = this.getAttribute('data-game');
        if (game) openGame(game);
    });
});

document.querySelectorAll('.game-modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) {
            const name = this.id.replace('modal-', '');
            closeGame(name);
        }
    });
});

// Escape key to close modal
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && currentGame) {
        closeGame(currentGame);
    }
});


// ============ 1. 🐍 SNAKE GAME ============
let snakeInterval, snakeGrid = 16, snakeCells, snake, snakeFood, snakeDirection, snakeNextDir, snakeGameOver;
const snakeBestKey = 'game_snake_best';

function startSnake() {
    if (snakeInterval) clearInterval(snakeInterval);
    const canvas = document.getElementById('snakeCanvas');
    const ctx = canvas.getContext('2d');
    snakeCells = canvas.width / snakeGrid; // 320/16 = 20 cells
    snake = [{x:8, y:8}, {x:7, y:8}, {x:6, y:8}];
    snakeDirection = {x:1, y:0};
    snakeNextDir = {x:1, y:0};
    snakeGameOver = false;

    document.getElementById('snakeGameOver').classList.remove('active');
    document.getElementById('snakeScore').textContent = '0';
    document.getElementById('snakeBest').textContent = localStorage.getItem(snakeBestKey) || '0';
    placeSnakeFood();

    snakeInterval = setInterval(() => {
        snakeDirection = {...snakeNextDir};
        const head = {x: snake[0].x + snakeDirection.x, y: snake[0].y + snakeDirection.y};

        // Wall collision or self collision check
        if (head.x < 0 || head.x >= snakeCells || head.y < 0 || head.y >= snakeCells || snake.some(s => s.x === head.x && s.y === head.y)) {
            clearInterval(snakeInterval);
            snakeGameOver = true;
            const score = snake.length - 3;
            const best = Math.max(score, parseInt(localStorage.getItem(snakeBestKey) || '0'));
            localStorage.setItem(snakeBestKey, best);
            document.getElementById('snakeBest').textContent = best;
            document.getElementById('snakeFinalScore').textContent = score;
            document.getElementById('snakeGameOver').classList.add('active');
            return;
        }

        snake.unshift(head);
        if (head.x === snakeFood.x && head.y === snakeFood.y) {
            placeSnakeFood();
        } else {
            snake.pop();
        }

        const currentScore = snake.length - 3;
        document.getElementById('snakeScore').textContent = currentScore;

        // Render Canvas Frame
        ctx.fillStyle = '#0d0d1a';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Grid background lines
        ctx.strokeStyle = '#18182d';
        ctx.lineWidth = 0.5;
        for (let i = 0; i <= snakeCells; i++) {
            ctx.beginPath();
            ctx.moveTo(i * snakeGrid, 0); ctx.lineTo(i * snakeGrid, canvas.height); ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(0, i * snakeGrid); ctx.lineTo(canvas.width, i * snakeGrid); ctx.stroke();
        }

        // Draw Food
        ctx.fillStyle = '#ef4444';
        ctx.shadowBlur = 12;
        ctx.shadowColor = '#ef4444';
        ctx.beginPath();
        ctx.arc(snakeFood.x * snakeGrid + snakeGrid/2, snakeFood.y * snakeGrid + snakeGrid/2, snakeGrid/2 - 2, 0, Math.PI * 2);
        ctx.fill();
        ctx.shadowBlur = 0;

        // Draw Snake Body & Head
        snake.forEach((seg, i) => {
            ctx.fillStyle = i === 0 ? '#a78bfa' : '#7c3aed';
            if (i === 0) {
                ctx.shadowBlur = 10;
                ctx.shadowColor = '#8b5cf6';
            }
            ctx.fillRect(seg.x * snakeGrid + 1, seg.y * snakeGrid + 1, snakeGrid - 2, snakeGrid - 2);
            ctx.shadowBlur = 0;
        });

    }, 110);
}

function placeSnakeFood() {
    snakeFood = {x: Math.floor(Math.random() * snakeCells), y: Math.floor(Math.random() * snakeCells)};
    if (snake && snake.some(s => s.x === snakeFood.x && s.y === snakeFood.y)) placeSnakeFood();
}

function snakeDir(dir) {
    if (dir === 'up' && snakeDirection.y !== 1) snakeNextDir = {x:0, y:-1};
    if (dir === 'down' && snakeDirection.y !== -1) snakeNextDir = {x:0, y:1};
    if (dir === 'left' && snakeDirection.x !== 1) snakeNextDir = {x:-1, y:0};
    if (dir === 'right' && snakeDirection.x !== -1) snakeNextDir = {x:1, y:0};
}

// Key handling for Snake
document.addEventListener('keydown', e => {
    if (currentGame !== 'snake') return;
    if (['ArrowUp','w','W'].includes(e.key)) { e.preventDefault(); snakeDir('up'); }
    if (['ArrowDown','s','S'].includes(e.key)) { e.preventDefault(); snakeDir('down'); }
    if (['ArrowLeft','a','A'].includes(e.key)) { e.preventDefault(); snakeDir('left'); }
    if (['ArrowRight','d','D'].includes(e.key)) { e.preventDefault(); snakeDir('right'); }
});

// Touch swipe support for Snake Canvas
(function() {
    let touchStartX = 0, touchStartY = 0;
    const canvas = document.getElementById('snakeCanvas');
    if (!canvas) return;
    canvas.addEventListener('touchstart', e => {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    }, {passive:true});
    canvas.addEventListener('touchend', e => {
        if (currentGame !== 'snake' || snakeGameOver) return;
        const dx = e.changedTouches[0].clientX - touchStartX;
        const dy = e.changedTouches[0].clientY - touchStartY;
        if (Math.abs(dx) > Math.abs(dy)) {
            if (Math.abs(dx) > 20) snakeDir(dx > 0 ? 'right' : 'left');
        } else {
            if (Math.abs(dy) > 20) snakeDir(dy > 0 ? 'down' : 'up');
        }
    }, {passive:true});
})();


// ============ 2. 🧩 2048 GAME ============
let grid2048, score2048;
const g2048BestKey = 'game_2048_best';

function start2048() {
    grid2048 = Array(16).fill(0);
    score2048 = 0;
    document.getElementById('g2048GameOver').classList.remove('active');
    document.getElementById('g2048Score').textContent = '0';
    document.getElementById('g2048Best').textContent = localStorage.getItem(g2048BestKey) || '0';
    document.getElementById('g2048Msg').textContent = 'Vuốt trên màn hình hoặc dùng phím mũi tên / WASD';

    addTile2048();
    addTile2048();
    render2048();
}

function addTile2048() {
    const empty = [];
    grid2048.forEach((v, i) => { if (v === 0) empty.push(i); });
    if (empty.length === 0) return;
    const idx = empty[Math.floor(Math.random() * empty.length)];
    grid2048[idx] = Math.random() < 0.88 ? 2 : 4;
}

function render2048() {
    const container = document.getElementById('grid2048');
    container.innerHTML = '';
    grid2048.forEach(v => {
        const tile = document.createElement('div');
        let valCls = 'tile-v-' + (v <= 2048 ? v : 'super');
        tile.className = 'tile-cell ' + valCls;
        tile.textContent = v > 0 ? v : '';
        if (v > 0) tile.classList.add('tile-pop');
        container.appendChild(tile);
    });
}

function slideRow2048(row) {
    let arr = row.filter(v => v !== 0);
    let gained = 0;
    for (let i = 0; i < arr.length - 1; i++) {
        if (arr[i] === arr[i+1]) {
            arr[i] *= 2;
            gained += arr[i];
            arr.splice(i+1, 1);
        }
    }
    while (arr.length < 4) arr.push(0);
    return { newRow: arr, gained };
}

function move2048(dir) {
    let moved = false;
    const oldGrid = [...grid2048];
    let turnScoreGained = 0;

    if (dir === 'left' || dir === 'right') {
        for (let r = 0; r < 4; r++) {
            let row = grid2048.slice(r*4, r*4+4);
            if (dir === 'right') row.reverse();
            const res = slideRow2048(row);
            row = res.newRow;
            turnScoreGained += res.gained;
            if (dir === 'right') row.reverse();
            for (let c = 0; c < 4; c++) grid2048[r*4+c] = row[c];
        }
    } else {
        for (let c = 0; c < 4; c++) {
            let col = [grid2048[c], grid2048[c+4], grid2048[c+8], grid2048[c+12]];
            if (dir === 'down') col.reverse();
            const res = slideRow2048(col);
            col = res.newRow;
            turnScoreGained += res.gained;
            if (dir === 'down') col.reverse();
            grid2048[c] = col[0]; grid2048[c+4] = col[1]; grid2048[c+8] = col[2]; grid2048[c+12] = col[3];
        }
    }

    moved = grid2048.some((v, i) => v !== oldGrid[i]);
    if (moved) {
        score2048 += turnScoreGained;
        addTile2048();
        render2048();

        document.getElementById('g2048Score').textContent = score2048;
        const best = Math.max(score2048, parseInt(localStorage.getItem(g2048BestKey) || '0'));
        localStorage.setItem(g2048BestKey, best);
        document.getElementById('g2048Best').textContent = best;

        if (grid2048.includes(2048)) {
            document.getElementById('g2048Msg').textContent = '🎉 Bạn đã tạo thành công ô 2048!';
        }

        if (!canMove2048()) {
            document.getElementById('g2048FinalScore').textContent = score2048;
            document.getElementById('g2048GameOver').classList.add('active');
        }
    }
}

function canMove2048() {
    if (grid2048.includes(0)) return true;
    for (let r = 0; r < 4; r++) {
        for (let c = 0; c < 4; c++) {
            const v = grid2048[r*4+c];
            if (c < 3 && v === grid2048[r*4+c+1]) return true;
            if (r < 3 && v === grid2048[(r+1)*4+c]) return true;
        }
    }
    return false;
}

document.addEventListener('keydown', e => {
    if (currentGame !== 'g2048') return;
    if (['ArrowLeft','a','A'].includes(e.key)) { e.preventDefault(); move2048('left'); }
    if (['ArrowRight','d','D'].includes(e.key)) { e.preventDefault(); move2048('right'); }
    if (['ArrowUp','w','W'].includes(e.key)) { e.preventDefault(); move2048('up'); }
    if (['ArrowDown','s','S'].includes(e.key)) { e.preventDefault(); move2048('down'); }
});

// Touch support for 2048
(function() {
    let startX = 0, startY = 0;
    const el = document.getElementById('grid2048');
    if (!el) return;
    el.addEventListener('touchstart', e => {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    }, {passive:true});
    el.addEventListener('touchend', e => {
        if (currentGame !== 'g2048') return;
        const dx = e.changedTouches[0].clientX - startX;
        const dy = e.changedTouches[0].clientY - startY;
        if (Math.abs(dx) > Math.abs(dy)) {
            if (Math.abs(dx) > 20) move2048(dx > 0 ? 'right' : 'left');
        } else {
            if (Math.abs(dy) > 20) move2048(dy > 0 ? 'down' : 'up');
        }
    }, {passive:true});
})();


// ============ 3. 🃏 MEMORY GAME ============
const memEmojis = ['🎮','🎯','🎲','🎪','🎨','🎬','🎭','🎸'];
let memCards, memFlipped, memMatchedCount, memMovesCount, memLocked;
const memBestKey = 'game_memory_best';

function startMemory() {
    const pairs = [...memEmojis, ...memEmojis];
    // Fisher-Yates Shuffle
    for (let i = pairs.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [pairs[i], pairs[j]] = [pairs[j], pairs[i]];
    }

    memCards = pairs;
    memFlipped = [];
    memMatchedCount = 0;
    memMovesCount = 0;
    memLocked = false;

    document.getElementById('memoryGameOver').classList.remove('active');
    document.getElementById('memMoves').textContent = '0';
    document.getElementById('memMatched').textContent = '0/8';
    document.getElementById('memBest').textContent = localStorage.getItem(memBestKey) || '-';
    document.getElementById('memMsg').textContent = 'Hãy ghép đúng 8 cặp thẻ bài giống nhau!';

    const grid = document.getElementById('memoryGrid');
    grid.innerHTML = '';
    pairs.forEach((emoji, i) => {
        const card = document.createElement('div');
        card.className = 'memory-card';
        card.innerHTML = `
            <div class="card-front"><i class="fas fa-question"></i></div>
            <div class="card-back">${emoji}</div>
        `;
        card.addEventListener('click', () => flipCard(card, i));
        grid.appendChild(card);
    });
}

function flipCard(card, idx) {
    if (memLocked || card.classList.contains('flipped') || card.classList.contains('matched') || memFlipped.length >= 2) return;

    card.classList.add('flipped');
    memFlipped.push({card, idx});

    if (memFlipped.length === 2) {
        memMovesCount++;
        document.getElementById('memMoves').textContent = memMovesCount;
        memLocked = true;

        const [first, second] = memFlipped;

        if (memCards[first.idx] === memCards[second.idx]) {
            first.card.classList.add('matched');
            second.card.classList.add('matched');
            memMatchedCount++;
            document.getElementById('memMatched').textContent = memMatchedCount + '/8';
            memFlipped = [];
            memLocked = false;

            if (memMatchedCount === 8) {
                const best = localStorage.getItem(memBestKey);
                if (!best || memMovesCount < parseInt(best)) {
                    localStorage.setItem(memBestKey, memMovesCount);
                    document.getElementById('memBest').textContent = memMovesCount;
                }
                document.getElementById('memoryFinalScore').textContent = memMovesCount + ' Lượt';
                document.getElementById('memoryGameOver').classList.add('active');
            }
        } else {
            setTimeout(() => {
                first.card.classList.remove('flipped');
                second.card.classList.remove('flipped');
                memFlipped = [];
                memLocked = false;
            }, 600);
        }
    }
}


// ============ 4. 🔨 WHACK-A-MOLE ============
let moleInterval, moleTimer, moleScoreVal, moleTimeLeft, moleActiveIdx, moleType;
const moleBestKey = 'game_mole_best';

function startMole() {
    stopMole();
    moleScoreVal = 0;
    moleTimeLeft = 30;
    moleActiveIdx = -1;

    document.getElementById('moleGameOver').classList.remove('active');
    document.getElementById('moleScore').textContent = '0';
    document.getElementById('moleTime').textContent = '30s';
    document.getElementById('moleBest').textContent = localStorage.getItem(moleBestKey) || '0';
    document.getElementById('moleMsg').textContent = 'Đập chuột nhanh trước khi hết giờ! 🔨';

    const holes = document.querySelectorAll('.mole-hole');
    holes.forEach(h => { h.className = 'mole-hole'; h.innerHTML = ''; });

    moleInterval = setInterval(spawnMole, 750);

    moleTimer = setInterval(() => {
        moleTimeLeft--;
        document.getElementById('moleTime').textContent = moleTimeLeft + 's';

        if (moleTimeLeft <= 0) {
            stopMole();
            const best = Math.max(moleScoreVal, parseInt(localStorage.getItem(moleBestKey) || '0'));
            localStorage.setItem(moleBestKey, best);
            document.getElementById('moleBest').textContent = best;
            document.getElementById('moleFinalScore').textContent = moleScoreVal + ' Điểm';
            document.getElementById('moleGameOver').classList.add('active');
        }
    }, 1000);
}

function stopMole() {
    if (moleInterval) clearInterval(moleInterval);
    if (moleTimer) clearInterval(moleTimer);
    moleInterval = null; moleTimer = null;
    const holes = document.querySelectorAll('.mole-hole');
    holes.forEach(h => { h.className = 'mole-hole'; h.innerHTML = ''; });
}

function spawnMole() {
    const holes = document.querySelectorAll('.mole-hole');
    holes.forEach(h => { h.className = 'mole-hole'; h.innerHTML = ''; });

    const idx = Math.floor(Math.random() * 9);
    moleActiveIdx = idx;

    const rand = Math.random();
    if (rand < 0.15) {
        moleType = 'bomb'; // -2
        holes[idx].classList.add('active', 'bomb');
        holes[idx].innerHTML = '💣';
    } else if (rand < 0.30) {
        moleType = 'gold'; // +3
        holes[idx].classList.add('active', 'gold');
        holes[idx].innerHTML = '🌟';
    } else {
        moleType = 'normal'; // +1
        holes[idx].classList.add('active');
        holes[idx].innerHTML = '🐹';
    }
}

function hitMole(idx) {
    if (moleActiveIdx !== idx || moleTimeLeft <= 0) return;

    const holes = document.querySelectorAll('.mole-hole');
    const target = holes[idx];

    let pts = 1;
    if (moleType === 'gold') pts = 3;
    else if (moleType === 'bomb') pts = -2;

    moleScoreVal = Math.max(0, moleScoreVal + pts);
    document.getElementById('moleScore').textContent = moleScoreVal;

    // Show floating score indicator
    const floatEl = document.createElement('span');
    floatEl.className = 'mole-float-score';
    floatEl.style.color = pts > 0 ? '#34d399' : '#f87171';
    floatEl.textContent = (pts > 0 ? '+' : '') + pts;
    target.appendChild(floatEl);

    target.className = 'mole-hole hit';
    target.innerHTML = pts < 0 ? '💥' : '✨';
    moleActiveIdx = -1;

    setTimeout(() => {
        if (target.contains(floatEl)) floatEl.remove();
    }, 500);
}


// ============ 5. ❌⭕ TIC TAC TOE ============
let tttBoard, tttTurn, tttOver, tttAITimeout;
let tttScores = {x:0, o:0, d:0};

function startTTT() {
    if (tttAITimeout) clearTimeout(tttAITimeout);
    tttBoard = Array(9).fill('');
    tttTurn = 'x';
    tttOver = false;

    document.getElementById('tttMsg').textContent = 'Lượt của bạn (X)!';
    renderTTT();
}

function renderTTT() {
    const grid = document.getElementById('tttGrid');
    grid.innerHTML = '';
    tttBoard.forEach((cell, i) => {
        const div = document.createElement('div');
        div.className = 'ttt-cell' + (cell ? ' taken ' + cell : '');
        div.textContent = cell ? cell.toUpperCase() : '';
        div.addEventListener('click', () => tttPlay(i));
        grid.appendChild(div);
    });
}

function tttPlay(i) {
    if (tttOver || tttBoard[i] || tttTurn !== 'x') return;

    tttBoard[i] = 'x';
    renderTTT();

    const res = checkTTT();
    if (res) return endTTT(res);

    tttTurn = 'o';
    document.getElementById('tttMsg').textContent = '🤖 Máy đang suy nghĩ...';
    tttAITimeout = setTimeout(tttAI, 400);
}

function tttAI() {
    if (tttOver) return;

    const b = tttBoard;
    const lines = [[0,1,2],[3,4,5],[6,7,8],[0,3,6],[1,4,7],[2,5,8],[0,4,8],[2,4,6]];

    // 1. Try to win
    for (const line of lines) {
        const vals = line.map(i => b[i]);
        if (vals.filter(v => v === 'o').length === 2 && vals.includes('')) {
            b[line[vals.indexOf('')]] = 'o';
            renderTTT();
            const r = checkTTT();
            if (r) return endTTT(r);
            tttTurn = 'x';
            document.getElementById('tttMsg').textContent = 'Lượt của bạn (X)!';
            return;
        }
    }

    // 2. Block player X
    for (const line of lines) {
        const vals = line.map(i => b[i]);
        if (vals.filter(v => v === 'x').length === 2 && vals.includes('')) {
            b[line[vals.indexOf('')]] = 'o';
            renderTTT();
            const r = checkTTT();
            if (r) return endTTT(r);
            tttTurn = 'x';
            document.getElementById('tttMsg').textContent = 'Lượt của bạn (X)!';
            return;
        }
    }

    // 3. Strategic pick (Center -> Corners -> Edges)
    const priority = [4, 0, 2, 6, 8, 1, 3, 5, 7];
    for (const p of priority) {
        if (!b[p]) {
            b[p] = 'o';
            renderTTT();
            const r = checkTTT();
            if (r) return endTTT(r);
            tttTurn = 'x';
            document.getElementById('tttMsg').textContent = 'Lượt của bạn (X)!';
            return;
        }
    }
}

function checkTTT() {
    const lines = [[0,1,2],[3,4,5],[6,7,8],[0,3,6],[1,4,7],[2,5,8],[0,4,8],[2,4,6]];
    for (const line of lines) {
        const [a,b,c] = line;
        if (tttBoard[a] && tttBoard[a] === tttBoard[b] && tttBoard[a] === tttBoard[c]) {
            return {winner: tttBoard[a], line};
        }
    }
    if (tttBoard.every(c => c !== '')) return {winner: 'draw'};
    return null;
}

function endTTT(result) {
    tttOver = true;
    const cells = document.querySelectorAll('.ttt-cell');

    if (result.winner === 'draw') {
        tttScores.d++;
        document.getElementById('tttMsg').textContent = '🤝 Hai bên hòa nhau!';
    } else if (result.winner === 'x') {
        tttScores.x++;
        document.getElementById('tttMsg').textContent = '🎉 Bạn đã chiến thắng!';
        if (result.line) result.line.forEach(i => cells[i].classList.add('win'));
    } else {
        tttScores.o++;
        document.getElementById('tttMsg').textContent = '😅 Máy thắng! Hãy thử lại nhé.';
        if (result.line) result.line.forEach(i => cells[i].classList.add('win'));
    }

    document.getElementById('tttX').textContent = tttScores.x;
    document.getElementById('tttD').textContent = tttScores.d;
    document.getElementById('tttO').textContent = tttScores.o;
}


// ============ 6. 🦕 DINO RUNNER GAME ============
let dinoRAF, dinoRunning, dinoY, dinoVel, dinoJumping, dinoScoreVal, dinoObstacles, dinoSpeed, dinoGroundY;
const dinoBestKey = 'game_dino_best';

function startDino() {
    if (dinoRAF) cancelAnimationFrame(dinoRAF);
    const canvas = document.getElementById('dinoCanvas');
    const ctx = canvas.getContext('2d');
    const W = canvas.width, H = canvas.height;

    dinoGroundY = H - 35;
    dinoY = dinoGroundY;
    dinoVel = 0;
    dinoJumping = false;
    dinoRunning = true;
    dinoScoreVal = 0;
    dinoSpeed = 4.5;
    dinoObstacles = [];

    document.getElementById('dinoGameOver').classList.remove('active');
    document.getElementById('dinoScore').textContent = '0';
    document.getElementById('dinoBest').textContent = localStorage.getItem(dinoBestKey) || '0';
    document.getElementById('dinoMsg').textContent = 'Nhấn phím Space / Mũi tên lên / Chạm màn hình để Nhảy';

    let frameCount = 0;

    function gameLoop() {
        if (!dinoRunning) return;

        ctx.fillStyle = '#0d0d1a';
        ctx.fillRect(0, 0, W, H);

        // Ground line
        ctx.strokeStyle = '#2e2e4a';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(0, dinoGroundY + 15);
        ctx.lineTo(W, dinoGroundY + 15);
        ctx.stroke();

        // Moving ground dots
        ctx.fillStyle = '#22223b';
        for (let i = (frameCount * dinoSpeed) % 30; i < W; i += 30) {
            ctx.fillRect(W - i, dinoGroundY + 20, 6, 2);
        }

        // Gravity & Dino Jump Physics
        dinoVel += 0.65;
        dinoY += dinoVel;
        if (dinoY >= dinoGroundY) {
            dinoY = dinoGroundY;
            dinoVel = 0;
            dinoJumping = false;
        }

        // Draw Dino Pixel Art
        ctx.fillStyle = '#a78bfa';
        ctx.shadowBlur = 8;
        ctx.shadowColor = '#8b5cf6';

        // Body & Head
        ctx.fillRect(48, dinoY - 22, 18, 22);
        ctx.fillRect(54, dinoY - 34, 18, 14);
        // Eye
        ctx.fillStyle = '#fbbf24';
        ctx.fillRect(66, dinoY - 30, 3, 3);
        // Legs
        ctx.fillStyle = '#a78bfa';
        const legAnim = Math.floor(frameCount / 4) % 2;
        ctx.fillRect(50, dinoY, 4, 7 + legAnim * 2);
        ctx.fillRect(60, dinoY, 4, 7 + (1 - legAnim) * 2);
        // Tail
        ctx.fillRect(38, dinoY - 18, 10, 6);
        ctx.shadowBlur = 0;

        // Obstacles Generator (Cacti & Birds)
        frameCount++;
        const spawnInterval = Math.max(45, 90 - Math.floor(dinoScoreVal / 8));
        if (frameCount % spawnInterval === 0) {
            const isBird = dinoScoreVal > 30 && Math.random() < 0.35;
            if (isBird) {
                dinoObstacles.push({x: W + 20, w: 16, h: 14, type: 'bird', y: dinoGroundY - 35 - Math.random() * 25});
            } else {
                const h = 22 + Math.random() * 18;
                dinoObstacles.push({x: W + 20, w: 12 + Math.random() * 8, h, type: 'cactus', y: dinoGroundY + 15 - h});
            }
        }

        // Render & Move Obstacles
        for (let i = dinoObstacles.length - 1; i >= 0; i--) {
            const ob = dinoObstacles[i];
            ob.x -= dinoSpeed;

            if (ob.type === 'cactus') {
                ctx.fillStyle = '#ef4444';
                ctx.shadowBlur = 8; ctx.shadowColor = '#ef4444';
                ctx.fillRect(ob.x, ob.y, ob.w, ob.h);
                ctx.shadowBlur = 0;
            } else {
                // Bird
                ctx.fillStyle = '#fbbf24';
                ctx.shadowBlur = 8; ctx.shadowColor = '#fbbf24';
                ctx.fillRect(ob.x, ob.y, ob.w, ob.h);
                const wing = Math.floor(frameCount / 5) % 2 ? -6 : 6;
                ctx.fillRect(ob.x + 4, ob.y + wing, 8, 4);
                ctx.shadowBlur = 0;
            }

            if (ob.x + ob.w < 0) {
                dinoObstacles.splice(i, 1);
                continue;
            }

            // Hitbox Collision Detection
            const dLeft = 40, dRight = 68, dTop = dinoY - 34, dBottom = dinoY + 7;
            const oLeft = ob.x, oRight = ob.x + ob.w, oTop = ob.y, oBottom = ob.y + ob.h;

            if (dRight > oLeft && dLeft < oRight && dBottom > oTop && dTop < oBottom) {
                dinoRunning = false;
                const best = Math.max(dinoScoreVal, parseInt(localStorage.getItem(dinoBestKey) || '0'));
                localStorage.setItem(dinoBestKey, best);
                document.getElementById('dinoBest').textContent = best;
                document.getElementById('dinoFinalScore').textContent = dinoScoreVal;
                document.getElementById('dinoGameOver').classList.add('active');
                return;
            }
        }

        // Score update & Speed ramp up
        if (frameCount % 6 === 0) {
            dinoScoreVal++;
            document.getElementById('dinoScore').textContent = dinoScoreVal;
        }
        dinoSpeed = 4.5 + Math.floor(dinoScoreVal / 25) * 0.4;

        // Background Clouds
        ctx.fillStyle = '#1c1c34';
        ctx.fillRect(((frameCount * 0.4) % (W + 120)) - 60, 25, 45, 8);
        ctx.fillRect(((frameCount * 0.25 + 180) % (W + 120)) - 60, 48, 55, 10);

        dinoRAF = requestAnimationFrame(gameLoop);
    }
    gameLoop();
}

function dinoJump() {
    if (!dinoJumping && dinoRunning) {
        dinoVel = -11.5;
        dinoJumping = true;
    }
}

// Event Listeners for Dino Jump
document.addEventListener('keydown', e => {
    if (currentGame !== 'dino') return;
    if ([' ','ArrowUp','w','W'].includes(e.key)) {
        e.preventDefault();
        dinoJump();
    }
});

document.getElementById('dinoWrap').addEventListener('click', () => {
    if (currentGame === 'dino') dinoJump();
});
document.getElementById('dinoWrap').addEventListener('touchstart', e => {
    e.preventDefault();
    if (currentGame === 'dino') dinoJump();
});
</script>

</div><!-- close main-content from nav -->
</body>
</html>
