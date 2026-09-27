<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$cache_file = __DIR__ . '/../temp_runs/disabled_ai_models.json';
$disabledModels = [];
if (file_exists($cache_file)) {
    $disabledModels = @json_decode(@file_get_contents($cache_file), true) ?: [];
} else {
    try {
        $db = getDB();
        $res = @$db->query("SELECT `value` FROM system_settings WHERE `key` = 'disabled_ai_models' LIMIT 1");
        if ($res && ($row = $res->fetch_assoc())) {
            $disabledModels = json_decode($row['value'], true) ?: [];
        }
        $db->close();
    } catch (Throwable $e) {}
}

require_once __DIR__ . '/../includes/ai_models_list.php';
$allModels = $SYSTEM_AI_MODELS ?? [];

$total_models = count($allModels);
$disabled_count = 0;
foreach ($allModels as $m) {
    if (in_array($m['id'], $disabledModels, true)) {
        $disabled_count++;
    }
}
$enabled_count = $total_models - $disabled_count;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Models AI Bot Chat - Hệ Thống Quản Trị</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/tkb/assets/style.css">

    <style>
        :root {
            /* Default: Dark Lofi Aesthetic */
            --adm-bg: #0c0717;
            --adm-card-bg: #140d27;
            --adm-card-border: rgba(168, 85, 247, 0.18);
            --adm-card-hover-border: rgba(192, 132, 252, 0.45);
            --adm-text-main: #ffffff;
            --adm-text-muted: #a79bb7;
            --adm-input-bg: #100922;
            --adm-input-border: rgba(168, 85, 247, 0.3);
            --adm-pill-bg: #140d27;
            --adm-id-bg: rgba(255, 255, 255, 0.06);
            --adm-id-border: rgba(168, 85, 247, 0.25);
            --adm-shadow: 0 8px 30px rgba(0,0,0,0.4);

            --adm-purple: #a855f7;
            --adm-cyan: #38bdf8;
            --adm-pink: #ec4899;
            --adm-green: #34d399;
            --adm-orange: #fb923c;
        }

        body.adm-light-mode {
            /* Light Mode Theme */
            --adm-bg: #f8fafc;
            --adm-card-bg: #ffffff;
            --adm-card-border: #e2e8f0;
            --adm-card-hover-border: #cbd5e1;
            --adm-text-main: #0f172a;
            --adm-text-muted: #64748b;
            --adm-input-bg: #f8fafc;
            --adm-input-border: #e2e8f0;
            --adm-pill-bg: #ffffff;
            --adm-id-bg: #f1f5f9;
            --adm-id-border: #e2e8f0;
            --adm-shadow: 0 2px 10px rgba(0,0,0,0.03);

            --adm-purple: #7c3aed;
            --adm-cyan: #0284c7;
            --adm-pink: #db2777;
            --adm-green: #10b981;
            --adm-orange: #f97316;
        }

        body.admin-portal {
            background-color: var(--adm-bg) !important;
            color: var(--adm-text-main) !important;
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
            display: block !important;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .aim-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-title {
            color: var(--adm-text-main) !important;
            font-weight: 800;
        }
        .page-sub {
            color: var(--adm-text-muted) !important;
            font-size: 13.5px;
            margin-top: 4px;
        }

        /* Stats Row */
        .aim-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .aim-stat-card {
            background: var(--adm-card-bg);
            border: 1px solid var(--adm-card-border);
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--adm-shadow);
            transition: all 0.25s ease;
        }
        .aim-stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--adm-card-hover-border);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        .aim-stat-num {
            font-size: 28px;
            font-weight: 800;
            color: var(--adm-text-main);
            line-height: 1;
            margin-bottom: 4px;
        }
        .aim-stat-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--adm-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .aim-stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Action Toolbar */
        .aim-toolbar {
            background: var(--adm-card-bg);
            border: 1px solid var(--adm-card-border);
            border-radius: 18px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            box-shadow: var(--adm-shadow);
            transition: all 0.25s ease;
        }

        .aim-search-wrap {
            position: relative;
            min-width: 280px;
            flex: 1;
            max-width: 420px;
        }
        .aim-search-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--adm-text-muted);
            font-size: 13.5px;
        }
        .aim-search-input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            background: var(--adm-input-bg);
            border: 1.5px solid var(--adm-input-border);
            border-radius: 12px;
            color: var(--adm-text-main);
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .aim-search-input:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.18);
        }
        .aim-search-input::placeholder {
            color: var(--adm-text-muted);
        }

        .aim-quick-btns {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .aim-btn {
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .aim-btn-danger {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.35);
            color: #f87171;
        }
        body.adm-light-mode .aim-btn-danger {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #b91c1c;
        }
        .aim-btn-danger:hover {
            background: #ef4444;
            border-color: #dc2626;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        }
        .aim-btn-success {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.35);
            color: #34d399;
        }
        body.adm-light-mode .aim-btn-success {
            background: #dcfce7;
            border-color: #86efac;
            color: #15803d;
        }
        .aim-btn-success:hover {
            background: #10b981;
            border-color: #059669;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }
        .aim-btn-primary {
            background: rgba(124, 58, 237, 0.15);
            border-color: rgba(124, 58, 237, 0.35);
            color: #c084fc;
        }
        body.adm-light-mode .aim-btn-primary {
            background: #ede9fe;
            border-color: #c4b5fd;
            color: #6d28d9;
        }
        .aim-btn-primary:hover {
            background: #7c3aed;
            border-color: #6d28d9;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
        }

        /* Filter Tabs */
        .aim-filter-pills {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .aim-pill {
            padding: 7px 16px;
            border-radius: 10px;
            background: var(--adm-pill-bg);
            border: 1px solid var(--adm-card-border);
            color: var(--adm-text-muted);
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            box-shadow: var(--adm-shadow);
        }
        .aim-pill:hover {
            border-color: var(--adm-card-hover-border);
            background: var(--adm-input-bg);
            color: var(--adm-text-main);
        }
        .aim-pill.active {
            background: linear-gradient(135deg, #7c3aed, #9333ea);
            border-color: #7c3aed;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
        }

        /* Models Grid */
        .aim-models-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 16px;
        }

        .aim-model-card {
            background: var(--adm-card-bg);
            border: 1px solid var(--adm-card-border);
            border-radius: 16px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            box-shadow: var(--adm-shadow);
        }
        .aim-model-card:hover {
            border-color: var(--adm-card-hover-border);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(124, 58, 237, 0.16);
        }
        .aim-model-card.disabled {
            opacity: 0.75;
            background: var(--adm-input-bg);
            border-color: rgba(239, 68, 68, 0.4);
        }

        .aim-model-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }
        .aim-model-meta h3 {
            font-size: 15px;
            font-weight: 800;
            color: var(--adm-text-main);
            margin: 0 0 6px;
            line-height: 1.25;
        }
        .aim-model-id {
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 11px;
            font-weight: 600;
            color: var(--adm-text-muted);
            background: var(--adm-id-bg);
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid var(--adm-id-border);
            word-break: break-all;
        }

        .aim-provider-badge {
            font-size: 10.5px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 7px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }
        .badge-deepseek { background: rgba(2, 132, 199, 0.15); border: 1px solid rgba(2, 132, 199, 0.35); color: #38bdf8; }
        body.adm-light-mode .badge-deepseek { background: #e0f2fe; border: 1px solid #bae6fd; color: #0284c7; }

        .badge-qwen { background: rgba(124, 58, 237, 0.15); border: 1px solid rgba(124, 58, 237, 0.35); color: #c084fc; }
        body.adm-light-mode .badge-qwen { background: #f5f3ff; border: 1px solid #ddd6fe; color: #7c3aed; }

        .badge-mistral { background: rgba(234, 88, 12, 0.15); border: 1px solid rgba(234, 88, 12, 0.35); color: #fb923c; }
        body.adm-light-mode .badge-mistral { background: #ffedd5; border: 1px solid #fed7aa; color: #c2410c; }

        .badge-minimax { background: rgba(219, 39, 119, 0.15); border: 1px solid rgba(219, 39, 119, 0.35); color: #f472b6; }
        body.adm-light-mode .badge-minimax { background: #fdf2f8; border: 1px solid #fbcfe8; color: #db2777; }

        .badge-other { background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); color: #cbd5e1; }
        body.adm-light-mode .badge-other { background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; }

        .aim-model-desc {
            font-size: 12.5px;
            color: var(--adm-text-muted);
            line-height: 1.45;
            margin-bottom: 14px;
            min-height: 36px;
        }

        .aim-model-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 1px solid var(--adm-card-border);
        }
        .aim-tags-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .aim-tag {
            font-size: 10.5px;
            font-weight: 600;
            padding: 3px 7px;
            border-radius: 6px;
            background: var(--adm-id-bg);
            border: 1px solid var(--adm-id-border);
            color: var(--adm-text-muted);
        }

        /* Modern iOS-Style Toggle Switch */
        .aim-switch-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .aim-status-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.3px;
        }
        .aim-status-open { color: #059669; }
        .aim-status-closed { color: #dc2626; }

        .aim-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }
        .aim-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .aim-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #e2e8f0;
            border: 1px solid #cbd5e1;
            border-radius: 24px;
            transition: .3s;
        }
        .aim-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: #94a3b8;
            border-radius: 50%;
            transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        input:checked + .aim-slider {
            background-color: #10b981;
            border-color: #059669;
        }
        input:checked + .aim-slider:before {
            transform: translateX(20px);
            background-color: #ffffff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.2);
        }

        /* Toast notification */
        .aim-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 12px 20px;
            background: #ffffff;
            border: 1.5px solid #7c3aed;
            border-radius: 12px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: toastSlideIn 0.3s ease;
        }
        @keyframes toastSlideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 900px) {
            .aim-stats-grid { grid-template-columns: repeat(2, 1fr); }
            .aim-models-grid { grid-template-columns: 1fr; }
            .aim-toolbar { flex-direction: column; align-items: stretch; }
            .aim-search-wrap { max-width: 100%; }
        }
    </style>
</head>
<body class="admin-portal <?= (isset($_COOKIE['adm_theme']) && $_COOKIE['adm_theme'] === 'light') ? 'adm-light-mode' : '' ?>">

    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="main-content">
        <div class="aim-container">

            <!-- Page Header -->
            <div class="page-header" style="margin-bottom: 24px;">
                <div>
                    <h1 class="page-title" style="display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-robot" style="color: #7c3aed;"></i>
                        Quản Lý Mô Hình AI Bot Chat (Sinh Viên)
                    </h1>
                    <p class="page-sub">
                        Chủ động Bật / Tắt từng model để quản lý tài nguyên, kiểm soát chi phí token và điều phối mô hình cho sinh viên học tập.
                    </p>
                </div>
                <div style="display:flex; gap:10px;">
                    <a href="/tkb/student/ai.php" target="_blank" class="aim-btn aim-btn-primary">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem Giao Diện Sinh Viên
                    </a>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="aim-stats-grid">
                <div class="aim-stat-card">
                    <div>
                        <div class="aim-stat-num" id="statTotal"><?= $total_models ?></div>
                        <div class="aim-stat-label">Tổng số Mô hình AI</div>
                    </div>
                    <div class="aim-stat-icon" style="background: #f5f3ff; color: #7c3aed;">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                </div>

                <div class="aim-stat-card">
                    <div>
                        <div class="aim-stat-num" id="statEnabled" style="color:#059669;"><?= $enabled_count ?></div>
                        <div class="aim-stat-label">Mô hình đang mở (Khả dụng)</div>
                    </div>
                    <div class="aim-stat-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="aim-stat-card">
                    <div>
                        <div class="aim-stat-num" id="statDisabled" style="color:#dc2626;"><?= $disabled_count ?></div>
                        <div class="aim-stat-label">Mô hình đã khóa (Tạm đóng)</div>
                    </div>
                    <div class="aim-stat-icon" style="background: #fef2f2; color: #dc2626;">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <div class="aim-stat-card">
                    <div>
                        <div class="aim-stat-num" id="statRate" style="color:#0284c7;">
                            <?= $total_models > 0 ? round(($enabled_count / $total_models) * 100) : 100 ?>%
                        </div>
                        <div class="aim-stat-label">Tỉ lệ sinh viên có thể dùng</div>
                    </div>
                    <div class="aim-stat-icon" style="background: #f0f9ff; color: #0284c7;">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                </div>
            </div>

            <!-- Action Toolbar & Search -->
            <div class="aim-toolbar">
                <div class="aim-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="modelSearch" class="aim-search-input" placeholder="Tìm kiếm mô hình (DeepSeek, V4, Qwen...)" oninput="filterModels()">
                </div>

                <div class="aim-quick-btns">
                    <button type="button" class="aim-btn aim-btn-danger" onclick="batchAction('disable_deepseek_v4')" title="Khóa nhanh model DeepSeek V4 Pro & Flash cho sinh viên">
                        <i class="fa-solid fa-ban"></i> Khóa DeepSeek V4
                    </button>
                    <button type="button" class="aim-btn aim-btn-success" onclick="batchAction('enable_deepseek_v4')" title="Mở lại DeepSeek V4">
                        <i class="fa-solid fa-bolt"></i> Mở DeepSeek V4
                    </button>
                    <button type="button" class="aim-btn aim-btn-primary" onclick="batchAction('enable_all')" title="Mở toàn bộ tất cả mô hình">
                        <i class="fa-solid fa-circle-check"></i> Mở Tất Cả
                    </button>
                    <button type="button" class="aim-btn aim-btn-danger" onclick="if(confirm('Bạn có chắc muốn tạm khóa toàn bộ mô hình AI? Sinh viên sẽ không thể chọn mô hình nào!')) batchAction('disable_all')" title="Khóa toàn bộ">
                        <i class="fa-solid fa-lock"></i> Khóa Tất Cả
                    </button>
                </div>
            </div>

            <!-- Filter Pills -->
            <div class="aim-filter-pills">
                <button type="button" class="aim-pill active" onclick="filterByProvider('all', this)">Tất cả (<?= $total_models ?>)</button>
                <button type="button" class="aim-pill" onclick="filterByProvider('deepseek', this)">⚡ DeepSeek</button>
                <button type="button" class="aim-pill" onclick="filterByProvider('qwen', this)">🔮 Qwen</button>
                <button type="button" class="aim-pill" onclick="filterByProvider('mistral', this)">🌪️ Mistral</button>
                <button type="button" class="aim-pill" onclick="filterByProvider('minimax', this)">🚀 MiniMax</button>
            </div>

            <!-- Models Grid -->
            <div class="aim-models-grid" id="modelsGrid">
                <?php foreach ($allModels as $m): 
                    $is_disabled = in_array($m['id'], $disabledModels, true);
                    $prov = strtolower($m['provider'] ?? 'other');
                    $badge_class = 'badge-other';
                    if (strpos($prov, 'deepseek') !== false) $badge_class = 'badge-deepseek';
                    elseif (strpos($prov, 'qwen') !== false) $badge_class = 'badge-qwen';
                    elseif (strpos($prov, 'mistral') !== false) $badge_class = 'badge-mistral';
                    elseif (strpos($prov, 'minimax') !== false) $badge_class = 'badge-minimax';
                ?>
                <div class="aim-model-card <?= $is_disabled ? 'disabled' : '' ?>" 
                     data-id="<?= htmlspecialchars($m['id']) ?>" 
                     data-name="<?= htmlspecialchars(strtolower($m['name'])) ?>" 
                     data-provider="<?= htmlspecialchars($prov) ?>">
                    
                    <div>
                        <div class="aim-model-top">
                            <div class="aim-model-meta">
                                <h3><?= htmlspecialchars($m['name']) ?></h3>
                                <span class="aim-model-id"><?= htmlspecialchars($m['id']) ?></span>
                            </div>
                            <span class="aim-provider-badge <?= $badge_class ?>">
                                <?= htmlspecialchars($m['provider'] ?? 'AI') ?>
                            </span>
                        </div>

                        <p class="aim-model-desc">
                            <?= htmlspecialchars($m['desc'] ?? 'Mô hình xử lý ngôn ngữ và lập trình thông minh.') ?>
                        </p>
                    </div>

                    <div class="aim-model-bottom">
                        <div class="aim-tags-row">
                            <span class="aim-tag"><i class="fa-solid fa-microchip"></i> <?= htmlspecialchars($m['badge'] ?? 'Standard') ?></span>
                            <span class="aim-tag"><i class="fa-regular fa-clone"></i> <?= htmlspecialchars($m['context'] ?? '128K Context') ?></span>
                        </div>

                        <div class="aim-switch-wrap">
                            <span class="aim-status-label <?= $is_disabled ? 'aim-status-closed' : 'aim-status-open' ?>" id="label_<?= md5($m['id']) ?>">
                                <?= $is_disabled ? 'ĐÃ KHÓA' : 'ĐANG MỞ' ?>
                            </span>
                            <label class="aim-switch">
                                <input type="checkbox" 
                                       id="toggle_<?= md5($m['id']) ?>"
                                       <?= $is_disabled ? '' : 'checked' ?> 
                                       onchange="toggleModel('<?= addslashes($m['id']) ?>', this.checked, '<?= md5($m['id']) ?>')">
                                <span class="aim-slider"></span>
                            </label>
                        </div>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <script>
    let currentProviderFilter = 'all';

    // Toggle 1 model cụ thể
    async function toggleModel(modelId, isEnabled, hashKey) {
        const card = document.querySelector(`.aim-model-card[data-id="${CSS.escape(modelId)}"]`);
        const label = document.getElementById(`label_${hashKey}`);
        
        // Optimistic UI
        if (isEnabled) {
            card?.classList.remove('disabled');
            if (label) {
                label.textContent = 'ĐANG MỞ';
                label.className = 'aim-status-label aim-status-open';
            }
        } else {
            card?.classList.add('disabled');
            if (label) {
                label.textContent = 'ĐÃ KHÓA';
                label.className = 'aim-status-label aim-status-closed';
            }
        }

        try {
            const res = await fetch('/tkb/api/ai_models_api.php?action=toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ model_id: modelId, enabled: isEnabled })
            });
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch (parseErr) {
                console.error('Toggle API response (not JSON):', text.substring(0, 300));
                showToast('❌ API trả về không hợp lệ. Có thể bạn chưa đăng nhập Admin hoặc phiên đã hết hạn.', true);
                revertToggle(hashKey, isEnabled, card, label);
                return;
            }
            if (data.success) {
                showToast(isEnabled ? `🟢 Đã MỞ mô hình "${modelId}" cho sinh viên!` : `🔴 Đã KHÓA mô hình "${modelId}" đối với sinh viên!`);
                updateStatCounts();
            } else {
                showToast('❌ Lỗi: ' + (data.error || 'Không thể lưu'), true);
                revertToggle(hashKey, isEnabled, card, label);
            }
        } catch (e) {
            console.error('Toggle fetch error:', e);
            showToast('❌ Lỗi kết nối mạng khi cập nhật trạng thái model', true);
            revertToggle(hashKey, isEnabled, card, label);
        }
    }

    function revertToggle(hashKey, isEnabled, card, label) {
        const cb = document.getElementById(`toggle_${hashKey}`);
        if (cb) cb.checked = !isEnabled;
        if (isEnabled) {
            card?.classList.add('disabled');
            if (label) { label.textContent = 'ĐÃ KHÓA'; label.className = 'aim-status-label aim-status-closed'; }
        } else {
            card?.classList.remove('disabled');
            if (label) { label.textContent = 'ĐANG MỞ'; label.className = 'aim-status-label aim-status-open'; }
        }
    }

    // Thao tác hàng loạt (Batch actions)
    async function batchAction(type, extra = {}) {
        try {
            const res = await fetch('/tkb/api/ai_models_api.php?action=batch', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ type: type, ...extra })
            });
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch (parseErr) {
                console.error('Batch API response (not JSON):', text.substring(0, 300));
                showToast('❌ API trả về không hợp lệ. Có thể bạn chưa đăng nhập Admin hoặc phiên đã hết hạn.', true);
                return;
            }
            if (data.success) {
                showToast(`⚡ ${data.message}`);
                // Refresh danh sách switch trên trang
                const disabledList = data.disabled_models || [];
                document.querySelectorAll('.aim-model-card').forEach(card => {
                    const id = card.getAttribute('data-id');
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    const label = card.querySelector('.aim-status-label');
                    const isDis = disabledList.includes(id);

                    if (checkbox) checkbox.checked = !isDis;
                    if (isDis) {
                        card.classList.add('disabled');
                        if (label) {
                            label.textContent = 'ĐÃ KHÓA';
                            label.className = 'aim-status-label aim-status-closed';
                        }
                    } else {
                        card.classList.remove('disabled');
                        if (label) {
                            label.textContent = 'ĐANG MỞ';
                            label.className = 'aim-status-label aim-status-open';
                        }
                    }
                });
                updateStatCounts();
            } else {
                showToast('❌ Lỗi: ' + (data.error || 'Thao tác thất bại'), true);
            }
        } catch (e) {
            console.error('Batch fetch error:', e);
            showToast('❌ Lỗi kết nối khi gửi lệnh hàng loạt', true);
        }
    }

    // Cập nhật các ô số liệu thống kê trên header
    function updateStatCounts() {
        const total = document.querySelectorAll('.aim-model-card').length;
        const disabled = document.querySelectorAll('.aim-model-card.disabled').length;
        const enabled = total - disabled;
        const rate = total > 0 ? Math.round((enabled / total) * 100) : 100;

        document.getElementById('statTotal').textContent = total;
        document.getElementById('statEnabled').textContent = enabled;
        document.getElementById('statDisabled').textContent = disabled;
        document.getElementById('statRate').textContent = rate + '%';
    }

    // Lọc theo từ khóa tìm kiếm
    function filterModels() {
        const q = (document.getElementById('modelSearch').value || '').toLowerCase().trim();
        document.querySelectorAll('.aim-model-card').forEach(card => {
            const id = card.getAttribute('data-id').toLowerCase();
            const name = card.getAttribute('data-name').toLowerCase();
            const prov = card.getAttribute('data-provider').toLowerCase();

            const matchesSearch = !q || id.includes(q) || name.includes(q) || prov.includes(q);
            const matchesProv = currentProviderFilter === 'all' || prov.includes(currentProviderFilter);

            if (matchesSearch && matchesProv) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Lọc theo nhà cung cấp (Pills)
    function filterByProvider(prov, el) {
        currentProviderFilter = prov;
        document.querySelectorAll('.aim-pill').forEach(p => p.classList.remove('active'));
        if (el) el.classList.add('active');
        filterModels();
    }

    // Toast popup thông báo
    function showToast(text, isError = false) {
        const old = document.querySelector('.aim-toast');
        if (old) old.remove();

        const toast = document.createElement('div');
        toast.className = 'aim-toast';
        if (isError) toast.style.borderColor = 'rgba(239, 68, 68, 0.6)';
        toast.innerHTML = `<i class="fa-solid ${isError ? 'fa-triangle-exclamation' : 'fa-bell'}" style="color:${isError ? '#f87171' : '#c084fc'};"></i> <span>${text}</span>`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s, transform 0.3s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(15px)';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }
    </script>
</body>
</html>
