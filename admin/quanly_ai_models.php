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
            --adm-bg: #0c0717;
            --adm-card-bg: #140d27;
            --adm-card-border: rgba(168, 85, 247, 0.18);
            --adm-card-hover-border: rgba(192, 132, 252, 0.4);
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
            display: block !important;
            overflow-x: hidden;
        }

        .aim-container {
            max-width: 1400px;
            margin: 0 auto;
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
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
            transition: all 0.25s ease;
        }
        .aim-stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--adm-card-hover-border);
        }
        .aim-stat-num {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            margin-bottom: 4px;
        }
        .aim-stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #a79bb7;
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
            color: #a79bb7;
            font-size: 13.5px;
        }
        .aim-search-input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            background: rgba(168, 85, 247, 0.08);
            border: 1px solid rgba(168, 85, 247, 0.25);
            border-radius: 12px;
            color: #f3e8ff;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .aim-search-input:focus {
            border-color: #c084fc;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2);
            background: rgba(168, 85, 247, 0.14);
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
            font-size: 12px;
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
            color: #fca5a5;
        }
        .aim-btn-danger:hover {
            background: rgba(239, 68, 68, 0.28);
            color: #fff;
            transform: translateY(-1px);
        }
        .aim-btn-success {
            background: rgba(52, 211, 153, 0.15);
            border-color: rgba(52, 211, 153, 0.35);
            color: #6ee7b7;
        }
        .aim-btn-success:hover {
            background: rgba(52, 211, 153, 0.28);
            color: #fff;
            transform: translateY(-1px);
        }
        .aim-btn-primary {
            background: rgba(168, 85, 247, 0.2);
            border-color: rgba(168, 85, 247, 0.4);
            color: #d8b4fe;
        }
        .aim-btn-primary:hover {
            background: rgba(168, 85, 247, 0.35);
            color: #fff;
            transform: translateY(-1px);
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
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(168, 85, 247, 0.18);
            color: #c4b5fd;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .aim-pill:hover, .aim-pill.active {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.4), rgba(236, 72, 153, 0.3));
            border-color: #c084fc;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(168, 85, 247, 0.3);
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
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
        }
        .aim-model-card:hover {
            border-color: var(--adm-card-hover-border);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(168, 85, 247, 0.2);
        }
        .aim-model-card.disabled {
            opacity: 0.7;
            background: #100a1f;
            border-color: rgba(239, 68, 68, 0.3);
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
            color: #ffffff;
            margin: 0 0 4px;
            line-height: 1.25;
        }
        .aim-model-id {
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 11px;
            color: #a79bb7;
            background: rgba(0, 0, 0, 0.35);
            padding: 2px 7px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid rgba(168, 85, 247, 0.15);
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
        .badge-deepseek { background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.35); color: #38bdf8; }
        .badge-qwen { background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.35); color: #c084fc; }
        .badge-mistral { background: rgba(251, 146, 60, 0.15); border: 1px solid rgba(251, 146, 60, 0.35); color: #fb923c; }
        .badge-minimax { background: rgba(244, 114, 182, 0.15); border: 1px solid rgba(244, 114, 182, 0.35); color: #f472b6; }
        .badge-other { background: rgba(148, 163, 184, 0.15); border: 1px solid rgba(148, 163, 184, 0.35); color: #cbd5e1; }

        .aim-model-desc {
            font-size: 12px;
            color: #c4b5fd;
            line-height: 1.4;
            margin-bottom: 14px;
            min-height: 34px;
        }

        .aim-model-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 1px solid rgba(168, 85, 247, 0.12);
        }
        .aim-tags-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .aim-tag {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.06);
            color: #a79bb7;
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
        .aim-status-open { color: #34d399; }
        .aim-status-closed { color: #f87171; }

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
            background-color: #2e1a47;
            border: 1px solid rgba(239, 68, 68, 0.4);
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
            background-color: #f87171;
            border-radius: 50%;
            transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 5px rgba(0,0,0,0.4);
        }
        input:checked + .aim-slider {
            background-color: #064e3b;
            border-color: #10b981;
        }
        input:checked + .aim-slider:before {
            transform: translateX(20px);
            background-color: #34d399;
            box-shadow: 0 0 10px #34d399;
        }

        /* Toast notification */
        .aim-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 12px 20px;
            background: #190e33;
            border: 1px solid rgba(168, 85, 247, 0.4);
            border-radius: 12px;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
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
<body class="admin-portal">

    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="main-content">
        <div class="aim-container">

            <!-- Page Header -->
            <div class="page-header" style="margin-bottom: 24px;">
                <div>
                    <h1 class="page-title" style="display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-robot" style="color: #a855f7;"></i>
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
                    <div class="aim-stat-icon" style="background: rgba(168,85,247,0.15); color: #c084fc;">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                </div>

                <div class="aim-stat-card">
                    <div>
                        <div class="aim-stat-num" id="statEnabled" style="color:#34d399;"><?= $enabled_count ?></div>
                        <div class="aim-stat-label">Mô hình đang mở (Khả dụng)</div>
                    </div>
                    <div class="aim-stat-icon" style="background: rgba(52,211,153,0.15); color: #34d399;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="aim-stat-card">
                    <div>
                        <div class="aim-stat-num" id="statDisabled" style="color:#f87171;"><?= $disabled_count ?></div>
                        <div class="aim-stat-label">Mô hình đã khóa (Tạm đóng)</div>
                    </div>
                    <div class="aim-stat-icon" style="background: rgba(239,68,68,0.15); color: #f87171;">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <div class="aim-stat-card">
                    <div>
                        <div class="aim-stat-num" id="statRate" style="color:#38bdf8;">
                            <?= $total_models > 0 ? round(($enabled_count / $total_models) * 100) : 100 ?>%
                        </div>
                        <div class="aim-stat-label">Tỉ lệ sinh viên có thể dùng</div>
                    </div>
                    <div class="aim-stat-icon" style="background: rgba(56,189,248,0.15); color: #38bdf8;">
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
